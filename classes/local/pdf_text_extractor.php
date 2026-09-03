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

use stored_file;

/**
 * Extracts readable text from PDFs that contain a text layer.
 */
class pdf_text_extractor {
    /** @var pdf_text_decoder */
    private $decoder;

    /** @var pdf_unicode_map_extractor */
    private $mapextractor;

    /**
     * Creates a PDF text extractor.
     */
    public function __construct() {
        $this->decoder = new pdf_text_decoder();
        $this->mapextractor = new pdf_unicode_map_extractor($this->decoder);
    }

    /**
     * Extracts text from a PDF file.
     *
     * @param stored_file $file PDF file.
     * @return string Extracted text.
     */
    public function extract(stored_file $file): string {
        $content = $file->get_content();
        if (strpos($content, '%PDF') === false) {
            return '';
        }

        $streams = $this->extract_decoded_streams($content);
        $unicodemap = $this->mapextractor->extract($streams);
        $parts = [];

        foreach ($streams as $stream) {
            $parts[] = $this->extract_pdf_stream_text($stream, $unicodemap);
        }

        return $this->normalise_text(implode("\n", array_filter($parts)));
    }

    /**
     * Extracts and decodes stream bodies from raw PDF content.
     *
     * @param string $content Raw PDF content.
     * @return string[] Decoded streams.
     */
    private function extract_decoded_streams(string $content): array {
        $streams = [];
        if (
            !preg_match_all(
                '/(<<.*?>>)\s*stream\r?\n?(.*?)\r?\n?endstream/s',
                $content,
                $matches,
                PREG_SET_ORDER
            )
        ) {
            return $streams;
        }

        foreach ($matches as $match) {
            $stream = $this->decode_pdf_stream($match[1], $match[2]);
            if ($stream !== '') {
                $streams[] = $stream;
            }
        }

        return $streams;
    }

    /**
     * Decodes a compressed PDF stream when supported.
     *
     * @param string $dictionary PDF stream dictionary.
     * @param string $stream PDF stream content.
     * @return string Decoded stream content.
     */
    private function decode_pdf_stream(string $dictionary, string $stream): string {
        $stream = preg_replace('/^\r?\n/', '', $stream);
        $stream = preg_replace('/\r?\n$/', '', $stream);

        if (stripos($dictionary, '/FlateDecode') === false) {
            return $stream;
        }

        foreach ([@gzuncompress($stream), @gzdecode($stream), @gzinflate($stream)] as $decoded) {
            if (is_string($decoded) && $decoded !== '') {
                return $decoded;
            }
        }

        return '';
    }

    /**
     * Extracts text tokens from a decoded PDF stream.
     *
     * @param string $stream Decoded PDF stream.
     * @param array $unicodemap Unicode character map.
     * @return string Extracted stream text.
     */
    private function extract_pdf_stream_text(string $stream, array $unicodemap = []): string {
        $parts = [];

        if (!preg_match_all('/BT(.*?)ET/s', $stream, $blocks)) {
            return '';
        }

        foreach ($blocks[1] as $block) {
            $parts[] = $this->extract_pdf_array_text($block, $unicodemap);
            $parts[] = $this->extract_pdf_operator_text($block, $unicodemap);
        }

        return $this->normalise_text(implode("\n", array_filter($parts)));
    }

    /**
     * Extracts TJ array text from a PDF text block.
     *
     * @param string $block PDF text block.
     * @param array $unicodemap Unicode character map.
     * @return string Extracted text.
     */
    private function extract_pdf_array_text(string $block, array $unicodemap): string {
        $parts = [];
        if (!preg_match_all('/\[(.*?)\]\s*TJ/s', $block, $arrays)) {
            return '';
        }

        foreach ($arrays[1] as $array) {
            $parts[] = $this->extract_pdf_strings($array, $unicodemap);
        }

        return implode("\n", $parts);
    }

    /**
     * Extracts single string operator text from a PDF text block.
     *
     * @param string $block PDF text block.
     * @param array $unicodemap Unicode character map.
     * @return string Extracted text.
     */
    private function extract_pdf_operator_text(string $block, array $unicodemap): string {
        $parts = [];
        if (
            !preg_match_all(
                '/((?:\((?:\\\\.|[^\\\\()])*\)|<[\da-fA-F\s]+>))\s*(?:Tj|\'|")/s',
                $block,
                $strings
            )
        ) {
            return '';
        }

        foreach ($strings[1] as $string) {
            $parts[] = $this->decoder->decode_token($string, $unicodemap);
        }

        return implode("\n", $parts);
    }

    /**
     * Extracts PDF string tokens from a value.
     *
     * @param string $value PDF text operator value.
     * @param array $unicodemap Unicode character map.
     * @return string Extracted string text.
     */
    private function extract_pdf_strings(string $value, array $unicodemap = []): string {
        $parts = [];

        if (preg_match_all('/\((?:\\\\.|[^\\\\()])*\)|<[\da-fA-F\s]+>/s', $value, $strings)) {
            foreach ($strings[0] as $string) {
                $parts[] = $this->decoder->decode_token($string, $unicodemap);
            }
        }

        return implode('', $parts);
    }

    /**
     * Normalises extracted text.
     *
     * @param string $text Text to normalise.
     * @return string Normalised text.
     */
    private function normalise_text(string $text): string {
        return pdf_text_decoder::normalise_text($text);
    }
}
