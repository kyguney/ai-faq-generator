<?php
/**
 * Property-based test for the Prompt_Builder service.
 *
 * Feature: prompt-builder, Property 4: Empty-After-Sanitization Treatment
 *
 * For any input string (title or content) that resolves to an empty string after
 * HTML stripping and whitespace trimming — including whitespace-only strings and
 * HTML-only strings with no text content — the Prompt_Builder SHALL treat that
 * input as absent and not include a corresponding context section in the prompt string.
 *
 * Validates: Requirements 4.1, 4.2, 4.3, 4.4, 4.5
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WPBits\AiFaqGenerator\Includes\Services\Prompt_Builder;

class PromptBuilderEmptyInputPropertyTest extends TestCase
{
    private Prompt_Builder $builder;

    protected function setUp(): void
    {
        $this->builder = new Prompt_Builder();
    }

    /**
     * **Validates: Requirements 4.1, 4.2, 4.3, 4.4, 4.5**
     *
     * Property 4: Empty-After-Sanitization Treatment
     * When title resolves to empty after sanitization, "Title:" must not appear in output.
     */
    #[Test]
    #[DataProvider('emptyAfterSanitizationProvider')]
    public function empty_title_after_sanitization_is_treated_as_absent(string $emptyInput): void
    {
        $result = $this->builder->build($emptyInput, 'Valid content here', 5);

        $this->assertStringNotContainsString('Title:', $result);
        $this->assertStringContainsString('Content: Valid content here', $result);
        $this->assertNotEmpty($result);
    }

    /**
     * **Validates: Requirements 4.1, 4.2, 4.3, 4.4, 4.5**
     *
     * Property 4: Empty-After-Sanitization Treatment
     * When content resolves to empty after sanitization, "Content:" must not appear in output.
     */
    #[Test]
    #[DataProvider('emptyAfterSanitizationProvider')]
    public function empty_content_after_sanitization_is_treated_as_absent(string $emptyInput): void
    {
        $result = $this->builder->build('Valid Title', $emptyInput, 5);

        $this->assertStringNotContainsString('Content:', $result);
        $this->assertStringContainsString('Title: Valid Title', $result);
        $this->assertNotEmpty($result);
    }

    /**
     * **Validates: Requirements 4.1, 4.2, 4.3, 4.4, 4.5**
     *
     * Property 4: Empty-After-Sanitization Treatment
     * When both title and content resolve to empty, neither section appears but prompt is still valid.
     */
    #[Test]
    #[DataProvider('emptyAfterSanitizationProvider')]
    public function both_empty_after_sanitization_produces_valid_prompt(string $emptyInput): void
    {
        $result = $this->builder->build($emptyInput, $emptyInput, 5);

        $this->assertStringNotContainsString('Title:', $result);
        $this->assertStringNotContainsString('Content:', $result);
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('Generate exactly 5', $result);
    }

    /**
     * Data provider generating 100+ strings that resolve to empty after sanitization.
     *
     * All strings resolve to empty after strip_tags() + trim().
     * Uses only whitespace characters in PHP's trim() default mask: " \t\n\r\0\x0B"
     *
     * Categories:
     * - Whitespace-only strings (spaces, tabs, newlines, mixed)
     * - HTML-only strings with no text content
     * - Mixed whitespace + HTML with no text content
     *
     * @return array<string, array{string}>
     */
    public static function emptyAfterSanitizationProvider(): array
    {
        $cases = [];

        // ─── Category 1: Whitespace-only strings (40 cases) ──────────────────
        // Using only characters in PHP trim() default mask: space, \t, \n, \r, \0, \x0B

        // Single whitespace characters
        $cases['single_space'] = [' '];
        $cases['single_tab'] = ["\t"];
        $cases['single_newline'] = ["\n"];
        $cases['single_carriage_return'] = ["\r"];
        $cases['crlf'] = ["\r\n"];

        // Multiple same whitespace
        $cases['multiple_spaces_3'] = ['   '];
        $cases['multiple_spaces_5'] = ['     '];
        $cases['multiple_tabs_3'] = ["\t\t\t"];
        $cases['multiple_tabs_5'] = ["\t\t\t\t\t"];
        $cases['multiple_newlines_3'] = ["\n\n\n"];
        $cases['multiple_newlines_5'] = ["\n\n\n\n\n"];
        $cases['multiple_crlf_3'] = ["\r\n\r\n\r\n"];

        // Mixed whitespace combinations
        $cases['space_tab_mix'] = [" \t \t "];
        $cases['space_newline_mix'] = [" \n \n "];
        $cases['tab_newline_mix'] = ["\t\n\t\n"];
        $cases['space_tab_newline'] = [" \t\n "];
        $cases['tab_cr_space'] = ["\t\r "];
        $cases['all_basic_ws'] = [" \t\n\r"];
        $cases['tabs_and_spaces_long'] = ["\t   \t   \t"];
        $cases['newlines_and_spaces_long'] = ["\n   \n   \n"];

        // Longer whitespace strings
        $cases['20_spaces'] = [str_repeat(' ', 20)];
        $cases['50_spaces'] = [str_repeat(' ', 50)];
        $cases['100_spaces'] = [str_repeat(' ', 100)];
        $cases['20_tabs'] = [str_repeat("\t", 20)];
        $cases['20_newlines'] = [str_repeat("\n", 20)];
        $cases['mixed_long_whitespace'] = [str_repeat(" \t\n", 15)];
        $cases['mixed_long_crlf'] = [str_repeat("\r\n ", 10)];

        // Generate random whitespace strings (only trim-safe characters)
        mt_srand(42);
        $whitespaceChars = [' ', "\t", "\n", "\r"];
        for ($i = 0; $i < 13; $i++) {
            $length = mt_rand(1, 20);
            $str = '';
            for ($j = 0; $j < $length; $j++) {
                $str .= $whitespaceChars[mt_rand(0, count($whitespaceChars) - 1)];
            }
            $cases["random_whitespace_{$i}"] = [$str];
        }

        // ─── Category 2: HTML-only strings with no text content (40 cases) ───

        // Simple empty tags
        $cases['empty_div'] = ['<div></div>'];
        $cases['empty_span'] = ['<span></span>'];
        $cases['empty_p'] = ['<p></p>'];
        $cases['empty_a'] = ['<a href="https://example.com"></a>'];
        $cases['empty_strong'] = ['<strong></strong>'];
        $cases['empty_em'] = ['<em></em>'];
        $cases['empty_h1'] = ['<h1></h1>'];
        $cases['empty_h2'] = ['<h2></h2>'];
        $cases['empty_h3'] = ['<h3></h3>'];
        $cases['empty_ul'] = ['<ul></ul>'];
        $cases['empty_li'] = ['<li></li>'];
        $cases['empty_table'] = ['<table></table>'];
        $cases['empty_tr'] = ['<tr></tr>'];
        $cases['empty_td'] = ['<td></td>'];
        $cases['empty_b'] = ['<b></b>'];
        $cases['empty_i'] = ['<i></i>'];

        // Self-closing / void tags
        $cases['br_tag'] = ['<br>'];
        $cases['br_self_closing'] = ['<br/>'];
        $cases['br_space_closing'] = ['<br />'];
        $cases['hr_tag'] = ['<hr>'];
        $cases['hr_self_closing'] = ['<hr/>'];
        $cases['img_tag'] = ['<img src="test.jpg" alt="">'];
        $cases['input_tag'] = ['<input type="text">'];

        // Nested empty tags
        $cases['nested_div_span'] = ['<div><span></span></div>'];
        $cases['nested_p_strong'] = ['<p><strong></strong></p>'];
        $cases['nested_div_p_span'] = ['<div><p><span></span></p></div>'];
        $cases['nested_ul_li'] = ['<ul><li></li><li></li></ul>'];
        $cases['nested_table_tr_td'] = ['<table><tr><td></td></tr></table>'];
        $cases['deeply_nested'] = ['<div><div><div><div></div></div></div></div>'];

        // Tags with attributes only
        $cases['div_with_class'] = ['<div class="container"></div>'];
        $cases['span_with_id'] = ['<span id="test" class="highlight"></span>'];
        $cases['a_with_href_class'] = ['<a href="#" class="btn" data-toggle="modal"></a>'];
        $cases['div_with_style'] = ['<div style="color: red; font-size: 14px;"></div>'];
        $cases['img_with_attrs'] = ['<img src="photo.png" alt="" width="100" height="100">'];

        // Multiple empty tags in sequence
        $cases['multiple_br'] = ['<br><br><br>'];
        $cases['multiple_empty_divs'] = ['<div></div><div></div><div></div>'];
        $cases['multiple_empty_p'] = ['<p></p><p></p>'];
        $cases['mixed_empty_tags'] = ['<div></div><span></span><p></p>'];
        $cases['br_and_hr'] = ['<br><hr><br>'];

        // Generate random HTML-only strings
        $emptyTags = [
            '<div></div>', '<span></span>', '<p></p>', '<br>', '<hr>',
            '<strong></strong>', '<em></em>', '<a href="#"></a>',
            '<ul></ul>', '<li></li>', '<h3></h3>', '<b></b>', '<i></i>',
        ];
        for ($i = 0; $i < 10; $i++) {
            $count = mt_rand(1, 5);
            $str = '';
            for ($j = 0; $j < $count; $j++) {
                $str .= $emptyTags[mt_rand(0, count($emptyTags) - 1)];
            }
            $cases["random_html_only_{$i}"] = [$str];
        }

        // ─── Category 3: Mixed whitespace + HTML with no text content (30+ cases) ─

        $cases['space_around_div'] = ['  <div></div>  '];
        $cases['tabs_around_span'] = ["\t<span></span>\t"];
        $cases['newlines_around_p'] = ["\n<p></p>\n"];
        $cases['whitespace_between_tags'] = ['<div></div>   <span></span>'];
        $cases['newlines_between_tags'] = ["<div></div>\n\n<p></p>"];
        $cases['tabs_between_tags'] = ["<ul></ul>\t\t<li></li>"];
        $cases['mixed_ws_and_br'] = ["  \t<br>\n  <br>  \t"];
        $cases['spaces_nested_empty'] = ['   <div><span></span></div>   '];
        $cases['crlf_with_tags'] = ["\r\n<div></div>\r\n<p></p>\r\n"];
        $cases['long_ws_with_hr'] = [str_repeat(' ', 10) . '<hr>' . str_repeat(' ', 10)];
        $cases['tabs_with_img'] = ["\t\t<img src=\"x.jpg\" alt=\"\">\t\t"];
        $cases['mixed_ws_nested_tags'] = [" \t<div><span></span></div>\n"];
        $cases['ws_multiple_void_tags'] = ["  <br>  <hr>  <br>  "];
        $cases['cr_around_empty_tags'] = ["\r<em></em>\r"];
        $cases['tab_br_tab'] = ["\t<br>\t"];

        // Generate random mixed whitespace + HTML strings
        for ($i = 0; $i < 15; $i++) {
            $str = '';
            $parts = mt_rand(2, 4);
            for ($j = 0; $j < $parts; $j++) {
                // Alternate between whitespace and empty HTML
                if ($j % 2 === 0) {
                    $wsLen = mt_rand(1, 3);
                    for ($k = 0; $k < $wsLen; $k++) {
                        $str .= $whitespaceChars[mt_rand(0, count($whitespaceChars) - 1)];
                    }
                } else {
                    $str .= $emptyTags[mt_rand(0, count($emptyTags) - 1)];
                }
            }
            $cases["random_mixed_ws_html_{$i}"] = [$str];
        }

        // Empty string itself
        $cases['empty_string'] = [''];

        return $cases;
    }
}
