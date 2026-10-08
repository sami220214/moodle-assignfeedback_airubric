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

namespace assignfeedback_airubric;

use assignfeedback_airubric\local\pdf_text_decoder;

/**
 * Tests PDF literal string decoding.
 *
 * @covers \assignfeedback_airubric\local\pdf_text_decoder
 */
final class pdf_text_decoder_test extends \basic_testcase {
    /** Tests plain text, escaped characters, octal bytes and line continuations. */
    public function test_decode_literal_strings(): void {
        $decoder = new pdf_text_decoder();
        $cases = [
            ['(Plain text)', 'Plain text'],
            ['()', ''],
            ['(Line\nnext\r\t\b\f)', "Line\nnext\r\t\x08\f"],
            ['(\(text\)\\\\)', '(text)\\'],
            ['(\101\12\0\000\377)', "A\n\0\0\xFF"],
            ["(one\\\ntwo\\\r\nthree\\\rfour)", 'onetwothreefour'],
            ["(\xE4\xF6)", "\xE4\xF6"],
        ];

        foreach ($cases as [$token, $expected]) {
            $this->assertSame($expected, $decoder->decode_token($token));
        }
    }
}
