<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * AI feedback plugin.
 *
 * @package   assignfeedback_airubric
 * @copyright 2026 Sami Simpanen
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignfeedback_airubric\local;

use assign;
use stored_file;
use stdClass;

/**
 * Reads supported assignment submission text sources for AI feedback.
 */
class submission_text {
    /** @var assign */
    private $assignment;

    /**
     * Creates a submission text reader for the assignment.
     *
     * @param assign $assignment Assignment instance.
     */
    public function __construct(assign $assignment) {
        $this->assignment = $assignment;
    }

    /**
     * Return online text and supported document file contents as one submission text.
     *
     * @param stdClass $submission
     * @return string
     */
    public function get_text(stdClass $submission): string {
        $parts = [];

        $onlinetext = trim(html_to_text($this->get_online_text_submission((int)$submission->id), 0, false));
        if ($onlinetext !== '') {
            $parts[] = $onlinetext;
        }

        foreach ($this->get_supported_files((int)$submission->id) as $file) {
            $extension = strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION));
            if (!in_array($extension, ['docx', 'doc', 'pdf'], true)) {
                throw new \moodle_exception('unreadableattachment', 'assignfeedback_airubric', '', $file->get_filename());
            }
            try {
                $text = trim($this->extract_file_text($file));
            } catch (\Throwable $exception) {
                throw new \moodle_exception('unreadableattachment', 'assignfeedback_airubric', '', $file->get_filename());
            }
            if ($text === '') {
                throw new \moodle_exception('unreadableattachment', 'assignfeedback_airubric', '', $file->get_filename());
            }
            $parts[] = $text;
        }

        return trim(implode("\n\n", $parts));
    }

    /**
     * Returns online text for the submission.
     *
     * @param int $submissionid Submission ID.
     * @return string Online text content.
     */
    public function get_online_text_submission(int $submissionid): string {
        global $DB;

        $record = $DB->get_record('assignsubmission_onlinetext', ['submission' => $submissionid], 'onlinetext', IGNORE_MISSING);
        return $record ? (string)$record->onlinetext : '';
    }

    /**
     * Returns all submitted files for explicit readability validation.
     *
     * @param int $submissionid Submission ID.
     * @return stored_file[] Submitted files.
     */
    private function get_supported_files(int $submissionid): array {
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

        // Inspect every attachment so unsupported files cannot silently disappear.
        return $files;
    }

    /**
     * Extracts readable text from a supported file.
     *
     * @param stored_file $file Submitted file.
     * @return string Extracted text.
     */
    private function extract_file_text(stored_file $file): string {
        $extension = strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION));

        if ($extension === 'docx') {
            return $this->extract_docx_text($file);
        }

        if ($extension === 'doc') {
            return $this->extract_doc_text($file);
        }

        if ($extension === 'pdf') {
            return $this->extract_pdf_text($file);
        }

        return '';
    }

    /**
     * Extracts readable text from a DOCX file.
     *
     * @param stored_file $file DOCX file.
     * @return string Extracted text.
     */
    private function extract_docx_text(stored_file $file): string {
        if (!class_exists('\ZipArchive')) {
            return '';
        }

        $temppath = $file->copy_content_to_temp('assignfeedback_airubric', 'submission.docx');
        $zip = new \ZipArchive();
        $opened = false;

        try {
            if ($zip->open($temppath) !== true) {
                return '';
            }
            $opened = true;

            $parts = [];
            foreach (
                [
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
                ] as $entry
            ) {
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
     * Extract text from PDFs that contain a readable text layer.
     *
     * Scanned PDFs require OCR and are intentionally out of scope for this
     * lightweight extractor.
     *
     * @param stored_file $file
     * @return string
     */
    private function extract_pdf_text(stored_file $file): string {
        return (new pdf_text_extractor())->extract($file);
    }

    /**
     * Extracts plain text from Office Open XML markup.
     *
     * @param string $xml Office Open XML markup.
     * @return string Extracted text.
     */
    private function extract_ooxml_text(string $xml): string {
        $xml = preg_replace('/<w:tab\s*\/>/i', "\t", $xml);
        $xml = preg_replace('/<w:br\s*\/>/i', "\n", $xml);
        $xml = preg_replace('/<\/w:p>/i', "\n", $xml);
        $text = html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');

        return $this->normalise_text($text);
    }

    /**
     * Normalises extracted submission text.
     *
     * @param string $text Text to normalise.
     * @return string Normalised text.
     */
    private function normalise_text(string $text): string {
        return pdf_text_decoder::normalise_text($text);
    }
}
