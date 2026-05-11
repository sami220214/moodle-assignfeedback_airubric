<?php
// This file is part of Moodle - http://moodle.org/

namespace assignfeedback_aifeedback\local;

use assign;
use stored_file;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Reads supported assignment submission text sources for AI feedback.
 */
class submission_text {
    /** @var assign */
    private $assignment;

    /**
     * @param assign $assignment
     */
    public function __construct(assign $assignment) {
        $this->assignment = $assignment;
    }

    /**
     * Return online text and supported Word file contents as one submission text.
     *
     * @param stdClass $submission
     * @return string
     */
    public function get_text(stdClass $submission): string {
        $parts = [];

        $onlinetext = trim(strip_tags($this->get_online_text_submission((int)$submission->id)));
        if ($onlinetext !== '') {
            $parts[] = $onlinetext;
        }

        foreach ($this->get_word_files((int)$submission->id) as $file) {
            $text = trim($this->extract_word_text($file));
            if ($text !== '') {
                $parts[] = $text;
            }
        }

        return trim(implode("\n\n", $parts));
    }

    /**
     * @param int $submissionid
     * @return string
     */
    public function get_online_text_submission(int $submissionid): string {
        global $DB;

        $record = $DB->get_record('assignsubmission_onlinetext', ['submission' => $submissionid], 'onlinetext', IGNORE_MISSING);
        return $record ? (string)$record->onlinetext : '';
    }

    /**
     * @param int $submissionid
     * @return stored_file[]
     */
    private function get_word_files(int $submissionid): array {
        $fs = get_file_storage();
        $context = $this->assignment->get_context();
        $files = $fs->get_area_files(
            $context->id,
            'assignsubmission_file',
            'submission_files',
            $submissionid,
            'filename',
            false
        );

        return array_filter($files, function(stored_file $file): bool {
            $extension = strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION));
            return $extension === 'docx' || $extension === 'doc';
        });
    }

    /**
     * @param stored_file $file
     * @return string
     */
    private function extract_word_text(stored_file $file): string {
        $extension = strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION));

        if ($extension === 'docx') {
            return $this->extract_docx_text($file);
        }

        if ($extension === 'doc') {
            return $this->extract_doc_text($file);
        }

        return '';
    }

    /**
     * @param stored_file $file
     * @return string
     */
    private function extract_docx_text(stored_file $file): string {
        if (!class_exists('\ZipArchive')) {
            return '';
        }

        $temppath = $file->copy_content_to_temp('assignfeedback_aifeedback', 'submission.docx');
        $zip = new \ZipArchive();
        $opened = false;

        try {
            if ($zip->open($temppath) !== true) {
                return '';
            }
            $opened = true;

            $parts = [];
            foreach ([
                'word/document.xml',
                'word/header1.xml',
                'word/header2.xml',
                'word/header3.xml',
                'word/footer1.xml',
                'word/footer2.xml',
                'word/footer3.xml',
                'word/footnotes.xml',
                'word/endnotes.xml',
                'word/comments.xml',
            ] as $entry) {
                $xml = $zip->getFromName($entry);
                if ($xml !== false) {
                    $parts[] = $this->extract_ooxml_text($xml);
                }
            }

            return $this->normalise_text(implode("\n\n", $parts));
        } finally {
            if ($opened) {
                $zip->close();
            }
            @unlink($temppath);
        }
    }

    /**
     * Extract readable text from legacy .doc binary content.
     *
     * This supports common Word 97-2003 documents without external binaries. The
     * format is binary, so extraction is intentionally best-effort.
     *
     * @param stored_file $file
     * @return string
     */
    private function extract_doc_text(stored_file $file): string {
        $content = $file->get_content();
        $parts = [];

        if (preg_match_all('/(?:[\x20-\x7E]\x00){4,}/', $content, $matches)) {
            foreach ($matches[0] as $match) {
                $parts[] = mb_convert_encoding($match, 'UTF-8', 'UTF-16LE');
            }
        }

        if (preg_match_all('/[\x20-\x7E]{4,}/', $content, $matches)) {
            foreach ($matches[0] as $match) {
                $parts[] = $match;
            }
        }

        return $this->normalise_text(implode("\n", array_unique($parts)));
    }

    /**
     * @param string $xml
     * @return string
     */
    private function extract_ooxml_text(string $xml): string {
        $xml = preg_replace('/<w:tab\s*\/>/i', "\t", $xml);
        $xml = preg_replace('/<w:br\s*\/>/i', "\n", $xml);
        $xml = preg_replace('/<\/w:p>/i', "\n", $xml);
        $text = html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');

        return $this->normalise_text($text);
    }

    /**
     * @param string $text
     * @return string
     */
    private function normalise_text(string $text): string {
        $text = str_replace("\0", '', $text);
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\R{3,}/u', "\n\n", $text);

        return trim($text);
    }
}
