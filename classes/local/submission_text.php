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
     * Return online text and supported document file contents as one submission text.
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

        foreach ($this->get_supported_files((int)$submission->id) as $file) {
            $text = trim($this->extract_file_text($file));
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

        return array_filter($files, function(stored_file $file): bool {
            $extension = strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION));
            return in_array($extension, ['docx', 'doc', 'pdf'], true);
        });
    }

    /**
     * @param stored_file $file
     * @return string
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
     * Extract text from PDFs that contain a readable text layer.
     *
     * Scanned PDFs require OCR and are intentionally out of scope for this
     * lightweight extractor.
     *
     * @param stored_file $file
     * @return string
     */
    private function extract_pdf_text(stored_file $file): string {
        $content = $file->get_content();
        if (strpos($content, '%PDF') === false) {
            return '';
        }

        $streams = [];
        if (preg_match_all('/(<<.*?>>)\s*stream\r?\n?(.*?)\r?\n?endstream/s', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $stream = $this->decode_pdf_stream($match[1], $match[2]);
                if ($stream !== '') {
                    $streams[] = $stream;
                }
            }
        }

        $unicodeMap = $this->extract_pdf_unicode_map($streams);
        $parts = [];
        foreach ($streams as $stream) {
            $parts[] = $this->extract_pdf_stream_text($stream, $unicodeMap);
        }

        return $this->normalise_text(implode("\n", array_filter($parts)));
    }

    /**
     * @param string $dictionary
     * @param string $stream
     * @return string
     */
    private function decode_pdf_stream(string $dictionary, string $stream): string {
        $stream = preg_replace('/^\r?\n/', '', $stream);
        $stream = preg_replace('/\r?\n$/', '', $stream);

        if (stripos($dictionary, '/FlateDecode') === false) {
            return $stream;
        }

        foreach ([
            @gzuncompress($stream),
            @gzdecode($stream),
            @gzinflate($stream),
        ] as $decoded) {
            if (is_string($decoded) && $decoded !== '') {
                return $decoded;
            }
        }

        return '';
    }

    /**
     * @param string $stream
     * @return string
     */
    private function extract_pdf_stream_text(string $stream, array $unicodeMap = []): string {
        $parts = [];

        if (!preg_match_all('/BT(.*?)ET/s', $stream, $blocks)) {
            return '';
        }

        foreach ($blocks[1] as $block) {
            if (preg_match_all('/\[(.*?)\]\s*TJ/s', $block, $arrays)) {
                foreach ($arrays[1] as $array) {
                    $parts[] = $this->extract_pdf_strings($array, $unicodeMap);
                }
            }

            if (preg_match_all('/((?:\((?:\\\\.|[^\\\\()])*\)|<[\da-fA-F\s]+>))\s*(?:Tj|\'|")/s', $block, $strings)) {
                foreach ($strings[1] as $string) {
                    $parts[] = $this->decode_pdf_text_token($string, $unicodeMap);
                }
            }
        }

        return $this->normalise_text(implode("\n", $parts));
    }

    /**
     * @param string $value
     * @return string
     */
    private function extract_pdf_strings(string $value, array $unicodeMap = []): string {
        $parts = [];

        if (preg_match_all('/\((?:\\\\.|[^\\\\()])*\)|<[\da-fA-F\s]+>/s', $value, $strings)) {
            foreach ($strings[0] as $string) {
                $parts[] = $this->decode_pdf_text_token($string, $unicodeMap);
            }
        }

        return implode('', $parts);
    }

    /**
     * @param string $token
     * @return string
     */
    private function decode_pdf_text_token(string $token, array $unicodeMap = []): string {
        if ($token === '') {
            return '';
        }

        if ($token[0] === '<') {
            return $this->decode_pdf_hex_string($token, $unicodeMap);
        }

        return $this->decode_pdf_literal_string($token);
    }

    /**
     * @param string $token
     * @return string
     */
    private function decode_pdf_hex_string(string $token, array $unicodeMap = []): string {
        $hex = preg_replace('/[<>\s]/', '', $token);
        if ($hex === '') {
            return '';
        }
        if (strlen($hex) % 2 === 1) {
            $hex .= '0';
        }

        $bytes = @hex2bin($hex);
        if ($bytes === false || $bytes === '') {
            return '';
        }

        if (strncmp($bytes, "\xFE\xFF", 2) === 0) {
            return mb_convert_encoding(substr($bytes, 2), 'UTF-8', 'UTF-16BE');
        }

        if ($unicodeMap) {
            $mapped = $this->map_pdf_hex_text($hex, $unicodeMap);
            if ($mapped !== '') {
                return $mapped;
            }
        }

        return $bytes;
    }

    /**
     * @param string[] $streams
     * @return array<string, string>
     */
    private function extract_pdf_unicode_map(array $streams): array {
        $map = [];

        foreach ($streams as $stream) {
            if (strpos($stream, 'beginbfchar') === false && strpos($stream, 'beginbfrange') === false) {
                continue;
            }

            if (preg_match_all('/beginbfchar(.*?)endbfchar/s', $stream, $sections)) {
                foreach ($sections[1] as $section) {
                    if (preg_match_all('/<([\da-fA-F]+)>\s*<([\da-fA-F]+)>/', $section, $chars, PREG_SET_ORDER)) {
                        foreach ($chars as $char) {
                            $map[strtoupper($char[1])] = $this->decode_pdf_unicode_hex($char[2]);
                        }
                    }
                }
            }

            if (preg_match_all('/beginbfrange(.*?)endbfrange/s', $stream, $sections)) {
                foreach ($sections[1] as $section) {
                    if (preg_match_all('/<([\da-fA-F]+)>\s*<([\da-fA-F]+)>\s*<([\da-fA-F]+)>/', $section, $ranges, PREG_SET_ORDER)) {
                        foreach ($ranges as $range) {
                            $start = hexdec($range[1]);
                            $end = hexdec($range[2]);
                            $target = hexdec($range[3]);
                            $width = strlen($range[1]);

                            for ($code = $start; $code <= $end && $code - $start < 256; $code++) {
                                $key = strtoupper(str_pad(dechex($code), $width, '0', STR_PAD_LEFT));
                                $map[$key] = $this->decode_pdf_unicode_hex(dechex($target + ($code - $start)));
                            }
                        }
                    }
                }
            }
        }

        return $map;
    }

    /**
     * @param string $hex
     * @param array<string, string> $unicodeMap
     * @return string
     */
    private function map_pdf_hex_text(string $hex, array $unicodeMap): string {
        $text = '';
        $position = 0;
        $length = strlen($hex);
        $widths = array_unique(array_map('strlen', array_keys($unicodeMap)));
        rsort($widths);

        while ($position < $length) {
            $matched = false;
            foreach ($widths as $width) {
                $key = strtoupper(substr($hex, $position, $width));
                if (isset($unicodeMap[$key])) {
                    $text .= $unicodeMap[$key];
                    $position += $width;
                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                $position += 2;
            }
        }

        return $text;
    }

    /**
     * @param string $hex
     * @return string
     */
    private function decode_pdf_unicode_hex(string $hex): string {
        if (strlen($hex) % 2 === 1) {
            $hex = '0' . $hex;
        }

        $bytes = @hex2bin($hex);
        if ($bytes === false || $bytes === '') {
            return '';
        }

        return mb_convert_encoding($bytes, 'UTF-8', 'UTF-16BE');
    }

    /**
     * @param string $token
     * @return string
     */
    private function decode_pdf_literal_string(string $token): string {
        $value = substr($token, 1, -1);

        return preg_replace_callback('/\\\\(?:([nrtbf()\\\\])|([0-7]{1,3})|\r?\n|\r)/', function(array $matches): string {
            if (!empty($matches[2])) {
                return chr(octdec($matches[2]));
            }

            switch ($matches[1] ?? '') {
                case 'n':
                    return "\n";
                case 'r':
                    return "\r";
                case 't':
                    return "\t";
                case 'b':
                    return "\x08";
                case 'f':
                    return "\f";
                case '(':
                case ')':
                case '\\':
                    return $matches[1];
                default:
                    return '';
            }
        }, $value);
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
        $text = $this->ensure_utf8($text);
        $text = str_replace("\0", '', $text);
        $collapsed = preg_replace('/[ \t]+/', ' ', $text);
        if (is_string($collapsed)) {
            $text = $collapsed;
        }

        $collapsed = preg_replace('/\R{3,}/u', "\n\n", $text);
        if (is_string($collapsed)) {
            $text = $collapsed;
        }

        return trim($text);
    }

    /**
     * @param string $text
     * @return string
     */
    private function ensure_utf8(string $text): string {
        if ($text === '' || preg_match('//u', $text)) {
            return $text;
        }

        if (function_exists('iconv')) {
            $converted = @iconv('Windows-1252', 'UTF-8//IGNORE', $text);
            if (is_string($converted) && preg_match('//u', $converted)) {
                return $converted;
            }
        }

        $cleaned = preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', $text);
        return is_string($cleaned) ? $cleaned : '';
    }
}
