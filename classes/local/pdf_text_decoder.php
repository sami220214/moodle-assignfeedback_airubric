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
 * Decodes PDF text tokens and normalises extracted text.
 */
class pdf_text_decoder {
    /**
     * Decodes a PDF literal or hexadecimal text token.
     *
     * @param string $token PDF text token.
     * @param array $unicodemap Unicode character map.
     * @return string Decoded text.
     */
    public function decode_token(string $token, array $unicodemap = []): string {
        if ($token === '') {
            return '';
        }

        if ($token[0] === '<') {
            return $this->decode_hex_string($token, $unicodemap);
        }

        return $this->decode_literal_string($token);
    }

    /**
     * Decodes UTF-16BE hexadecimal PDF Unicode text.
     *
     * @param string $hex Hexadecimal Unicode text.
     * @return string Decoded text.
     */
    public function decode_unicode_hex(string $hex): string {
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
     * Normalises extracted text.
     *
     * @param string $text Text to normalise.
     * @return string Normalised text.
     */
    public static function normalise_text(string $text): string {
        $text = self::ensure_utf8($text);
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
     * Decodes a hexadecimal PDF text token.
     *
     * @param string $token Hexadecimal PDF text token.
     * @param array $unicodemap Unicode character map.
     * @return string Decoded text.
     */
    private function decode_hex_string(string $token, array $unicodemap = []): string {
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

        return $this->decode_mapped_hex_or_bytes($hex, $bytes, $unicodemap);
    }

    /**
     * Returns mapped Unicode text when available, otherwise raw bytes.
     *
     * @param string $hex Hexadecimal PDF text.
     * @param string $bytes Raw decoded bytes.
     * @param array $unicodemap Unicode character map.
     * @return string Mapped text or raw bytes.
     */
    private function decode_mapped_hex_or_bytes(string $hex, string $bytes, array $unicodemap): string {
        if (!$unicodemap) {
            return $bytes;
        }

        $mapped = $this->map_hex_text($hex, $unicodemap);
        return $mapped !== '' ? $mapped : $bytes;
    }

    /**
     * Maps hexadecimal PDF text through a Unicode character map.
     *
     * @param string $hex Hexadecimal PDF text.
     * @param array $unicodemap Unicode character map.
     * @return string Mapped text.
     */
    private function map_hex_text(string $hex, array $unicodemap): string {
        $text = '';
        $position = 0;
        $length = strlen($hex);
        $widths = array_unique(array_map('strlen', array_keys($unicodemap)));
        rsort($widths);

        while ($position < $length) {
            $position = $this->append_mapped_hex_character($hex, $position, $widths, $unicodemap, $text);
        }

        return $text;
    }

    /**
     * Appends a single mapped character and returns the next hex position.
     *
     * @param string $hex Hexadecimal PDF text.
     * @param int $position Current hex position.
     * @param int[] $widths Candidate character widths.
     * @param array $unicodemap Unicode character map.
     * @param string $text Mapped text accumulator.
     * @return int Next hex position.
     */
    private function append_mapped_hex_character(
        string $hex,
        int $position,
        array $widths,
        array $unicodemap,
        string &$text
    ): int {
        foreach ($widths as $width) {
            $key = strtoupper(substr($hex, $position, $width));
            if (isset($unicodemap[$key])) {
                $text .= $unicodemap[$key];
                return $position + $width;
            }
        }

        return $position + 2;
    }

    /**
     * Decodes a literal PDF text token.
     *
     * @param string $token Literal PDF text token.
     * @return string Decoded text.
     */
    private function decode_literal_string(string $token): string {
        $value = substr($token, 1, -1);
        $escapes = [
            'n' => "\n",
            'r' => "\r",
            't' => "\t",
            'b' => "\x08",
            'f' => "\f",
            '(' => '(',
            ')' => ')',
            '\\' => '\\',
        ];

        return preg_replace_callback(
            '/\\(?:([nrtbf()\\])|([0-7]{1,3})|\r?\n|\r)/',
            function (array $matches) use ($escapes): string {
                if (!empty($matches[2])) {
                    return chr(octdec($matches[2]));
                }

                return $escapes[$matches[1] ?? ''] ?? '';
            },
            $value
        );
    }

    /**
     * Ensures extracted text is valid UTF-8.
     *
     * @param string $text Text to clean.
     * @return string UTF-8 text.
     */
    private static function ensure_utf8(string $text): string {
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
