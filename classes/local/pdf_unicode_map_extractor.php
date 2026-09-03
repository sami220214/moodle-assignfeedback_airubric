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

/**
 * Extracts PDF ToUnicode character maps from decoded streams.
 */
class pdf_unicode_map_extractor {
    /** @var pdf_text_decoder */
    private $decoder;

    /**
     * Creates a Unicode map extractor.
     *
     * @param pdf_text_decoder $decoder PDF text decoder.
     */
    public function __construct(pdf_text_decoder $decoder) {
        $this->decoder = $decoder;
    }

    /**
     * Extracts a PDF ToUnicode character map from streams.
     *
     * @param string[] $streams Decoded PDF streams.
     * @return array<string, string> Unicode character map.
     */
    public function extract(array $streams): array {
        $map = [];

        foreach ($streams as $stream) {
            if (strpos($stream, 'beginbfchar') !== false) {
                $map += $this->extract_chars($stream);
            }

            if (strpos($stream, 'beginbfrange') !== false) {
                $map += $this->extract_ranges($stream);
            }
        }

        return $map;
    }

    /**
     * Extracts direct bfchar mappings.
     *
     * @param string $stream Decoded PDF stream.
     * @return array<string, string> Unicode character map.
     */
    private function extract_chars(string $stream): array {
        $map = [];
        if (!preg_match_all('/beginbfchar(.*?)endbfchar/s', $stream, $sections)) {
            return $map;
        }

        foreach ($sections[1] as $section) {
            $map += $this->extract_char_section($section);
        }

        return $map;
    }

    /**
     * Extracts a direct bfchar section.
     *
     * @param string $section PDF bfchar section.
     * @return array<string, string> Unicode character map.
     */
    private function extract_char_section(string $section): array {
        $map = [];
        if (!preg_match_all('/<([\da-fA-F]+)>\s*<([\da-fA-F]+)>/', $section, $chars, PREG_SET_ORDER)) {
            return $map;
        }

        foreach ($chars as $char) {
            $map[strtoupper($char[1])] = $this->decoder->decode_unicode_hex($char[2]);
        }

        return $map;
    }

    /**
     * Extracts sequential bfrange mappings.
     *
     * @param string $stream Decoded PDF stream.
     * @return array<string, string> Unicode character map.
     */
    private function extract_ranges(string $stream): array {
        $map = [];
        if (!preg_match_all('/beginbfrange(.*?)endbfrange/s', $stream, $sections)) {
            return $map;
        }

        foreach ($sections[1] as $section) {
            $map += $this->extract_range_section($section);
        }

        return $map;
    }

    /**
     * Extracts a sequential bfrange section.
     *
     * @param string $section PDF bfrange section.
     * @return array<string, string> Unicode character map.
     */
    private function extract_range_section(string $section): array {
        $map = [];
        if (
            !preg_match_all(
                '/<([\da-fA-F]+)>\s*<([\da-fA-F]+)>\s*<([\da-fA-F]+)>/',
                $section,
                $ranges,
                PREG_SET_ORDER
            )
        ) {
            return $map;
        }

        foreach ($ranges as $range) {
            $map += $this->build_range($range);
        }

        return $map;
    }

    /**
     * Builds a bounded sequential Unicode range mapping.
     *
     * @param array $range Regex match for a bfrange entry.
     * @return array<string, string> Unicode character map.
     */
    private function build_range(array $range): array {
        $map = [];
        $start = hexdec($range[1]);
        $end = hexdec($range[2]);
        $target = hexdec($range[3]);
        $width = strlen($range[1]);

        for ($code = $start; $code <= $end && $code - $start < 256; $code++) {
            $key = strtoupper(str_pad(dechex($code), $width, '0', STR_PAD_LEFT));
            $map[$key] = $this->decoder->decode_unicode_hex(dechex($target + ($code - $start)));
        }

        return $map;
    }
}
