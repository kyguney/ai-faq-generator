<?php
/**
 * Property-based test for the JSON_LD_Generator service.
 *
 * Feature: faqpage-jsonld-generator, Property 9: HTML Handling in Questions and Answers
 * Validates: Requirements 6.3, 6.4
 *
 * For any FAQ item where the question contains HTML tags and the answer contains
 * HTML markup: the `name` field of the Question object SHALL contain no HTML tags
 * (all stripped), while the `text` field of the Answer object SHALL preserve the
 * HTML markup from the answer.
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WPBits\AiFaqGenerator\Includes\Services\JSON_LD_Generator;

class JsonLdHtmlHandlingPropertyTest extends TestCase
{
    private JSON_LD_Generator $generator;

    protected function setUp(): void
    {
        global $afg_test_is_singular, $afg_test_current_post_id, $afg_test_post_meta_values;

        $this->generator = new JSON_LD_Generator();

        // Set up globals for singular post context.
        $afg_test_is_singular = true;
        $afg_test_current_post_id = 42;
        $afg_test_post_meta_values = [];
    }

    protected function tearDown(): void
    {
        global $afg_test_is_singular, $afg_test_current_post_id, $afg_test_post_meta_values;

        $afg_test_is_singular = true;
        $afg_test_current_post_id = 42;
        $afg_test_post_meta_values = [];
    }

    /**
     * **Validates: Requirements 6.3, 6.4**
     *
     * Property 9: HTML Handling in Questions and Answers.
     * For any FAQ item where the question contains HTML tags and the answer
     * contains HTML markup: the `name` field SHALL contain no HTML tags (all
     * stripped), while the `text` field SHALL preserve the HTML markup from
     * the answer.
     */
    #[Test]
    #[DataProvider('htmlFaqItemsProvider')]
    public function name_field_strips_html_and_text_field_preserves_html(
        array $inputItems,
        string $encodedMeta,
        array $expectedHtmlTags
    ): void {
        global $afg_test_post_meta_values;

        // Set the FAQ meta for post ID 42.
        $afg_test_post_meta_values['42__aifaq_generated_faqs'] = $encodedMeta;

        // Capture output from output_schema().
        ob_start();
        $this->generator->output_schema();
        $output = ob_get_clean();

        // Output must not be empty for valid FAQ arrays.
        $this->assertNotEmpty($output, 'output_schema() must produce output for valid FAQ arrays.');

        // Extract JSON from script tag.
        $pattern = '#<script type="application/ld\+json">(.*?)</script>#s';
        preg_match($pattern, $output, $matches);
        $this->assertNotEmpty($matches, 'Output must contain a <script type="application/ld+json"> tag.');

        $jsonContent = $matches[1];

        // Un-escape the script tag escaping for JSON parsing.
        $jsonContent = str_replace('<\\/script', '</script', $jsonContent);

        $schema = json_decode($jsonContent, true);
        $this->assertNotNull($schema, 'JSON content must be valid JSON.');
        $this->assertArrayHasKey('mainEntity', $schema);

        // Assert each mainEntity element's name has no HTML and text preserves HTML.
        foreach ($schema['mainEntity'] as $index => $question) {
            $name = $question['name'];
            $text = $question['acceptedAnswer']['text'];

            // Requirement 6.4: name field must contain NO HTML tags (all stripped).
            $this->assertDoesNotMatchRegularExpression(
                '/<[^>]+>/',
                $name,
                sprintf(
                    'mainEntity[%d] name must not contain any HTML tags. Got: "%s"',
                    $index,
                    $name
                )
            );

            // Also verify by comparing with strip_tags.
            $this->assertSame(
                strip_tags($name),
                $name,
                sprintf(
                    'mainEntity[%d] name must equal its strip_tags() version. Got: "%s"',
                    $index,
                    $name
                )
            );

            // Requirement 6.3: text field must preserve HTML markup from answer.
            // Verify the expected HTML tags are present in the text field.
            foreach ($expectedHtmlTags[$index] as $tag) {
                $this->assertStringContainsString(
                    $tag,
                    $text,
                    sprintf(
                        'mainEntity[%d] text must preserve HTML tag "%s". Got: "%s"',
                        $index,
                        $tag,
                        $text
                    )
                );
            }
        }
    }

    /**
     * Data provider generating 110+ FAQ items with HTML tags in questions and answers.
     *
     * @return array<string, array{array<int, array{question: string, answer: string}>, string, array<int, array<int, string>>}>
     */
    public static function htmlFaqItemsProvider(): array
    {
        $cases = [];

        // Seed for reproducibility.
        mt_srand(99999);

        // HTML tags to use in questions (will be stripped).
        $questionTags = [
            '<strong>%s</strong>',
            '<em>%s</em>',
            '<b>%s</b>',
            '<i>%s</i>',
            '<span>%s</span>',
            '<a href="https://example.com">%s</a>',
            '<p>%s</p>',
            '<h1>%s</h1>',
            '<h2>%s</h2>',
            '<h3>%s</h3>',
            '<br>%s',
            '<div>%s</div>',
        ];

        // HTML tags to use in answers (will be preserved).
        $answerTemplates = [
            ['template' => '<p>%s</p>', 'tag' => '<p>'],
            ['template' => '<strong>%s</strong>', 'tag' => '<strong>'],
            ['template' => '<em>%s</em>', 'tag' => '<em>'],
            ['template' => '<a href="https://example.com">%s</a>', 'tag' => '<a href="https://example.com">'],
            ['template' => '<ul><li>%s</li></ul>', 'tag' => '<ul>'],
            ['template' => '<ol><li>%s</li></ol>', 'tag' => '<ol>'],
            ['template' => '<code>%s</code>', 'tag' => '<code>'],
            ['template' => '<pre>%s</pre>', 'tag' => '<pre>'],
            ['template' => '<br>%s', 'tag' => '<br>'],
            ['template' => '<p><strong>%s</strong></p>', 'tag' => '<strong>'],
            ['template' => '<p><em>%s</em> and <strong>more</strong></p>', 'tag' => '<em>'],
            ['template' => '<ul><li>Item 1</li><li>%s</li></ul>', 'tag' => '<li>'],
        ];

        // Generate 110 random FAQ arrays with HTML in both questions and answers.
        for ($i = 0; $i < 110; $i++) {
            $itemCount = mt_rand(1, 5);
            $items = [];
            $expectedTags = [];

            for ($j = 0; $j < $itemCount; $j++) {
                $questionText = self::generateNonEmptyString(mt_rand(5, 50));
                $answerText = self::generateNonEmptyString(mt_rand(10, 100));

                // Wrap question in random HTML tag(s).
                $tagIndex = mt_rand(0, count($questionTags) - 1);
                $htmlQuestion = sprintf($questionTags[$tagIndex], $questionText);

                // Optionally add more tags around the question.
                if (mt_rand(0, 2) === 0) {
                    $secondTagIndex = mt_rand(0, count($questionTags) - 1);
                    $htmlQuestion = sprintf($questionTags[$secondTagIndex], $htmlQuestion);
                }

                // Wrap answer in random HTML tag(s).
                $answerIndex = mt_rand(0, count($answerTemplates) - 1);
                $htmlAnswer = sprintf($answerTemplates[$answerIndex]['template'], $answerText);
                $expectedTag = $answerTemplates[$answerIndex]['tag'];

                // Optionally add additional HTML around the answer.
                if (mt_rand(0, 2) === 0) {
                    $secondAnswerIndex = mt_rand(0, count($answerTemplates) - 1);
                    $htmlAnswer = sprintf($answerTemplates[$secondAnswerIndex]['template'], $htmlAnswer);
                    $expectedTag = $answerTemplates[$secondAnswerIndex]['tag'];
                }

                $items[] = [
                    'question' => $htmlQuestion,
                    'answer' => $htmlAnswer,
                ];
                $expectedTags[] = [$expectedTag];
            }

            $encodedMeta = json_encode($items, JSON_UNESCAPED_UNICODE);
            $cases["random_html_faq_{$i}"] = [$items, $encodedMeta, $expectedTags];
        }

        // Edge case: question with multiple nested HTML tags.
        $nestedItems = [[
            'question' => '<div><p><strong><em>What is WordPress?</em></strong></p></div>',
            'answer' => '<p>WordPress is a <strong>content management system</strong>.</p>',
        ]];
        $cases['nested_question_tags'] = [
            $nestedItems,
            json_encode($nestedItems, JSON_UNESCAPED_UNICODE),
            [['<strong>']],
        ];

        // Edge case: question with self-closing tags.
        $selfClosingItems = [[
            'question' => 'What is<br>this<br/>about?',
            'answer' => '<p>It is about <em>testing</em>.</p>',
        ]];
        $cases['self_closing_tags_in_question'] = [
            $selfClosingItems,
            json_encode($selfClosingItems, JSON_UNESCAPED_UNICODE),
            [['<em>']],
        ];

        // Edge case: answer with multiple distinct HTML elements.
        $multiHtmlItems = [[
            'question' => '<strong>How</strong> do you <em>use</em> it?',
            'answer' => '<p>First, <strong>install</strong> WordPress.</p><ul><li>Step 1</li><li>Step 2</li></ul>',
        ]];
        $cases['multiple_html_elements_in_answer'] = [
            $multiHtmlItems,
            json_encode($multiHtmlItems, JSON_UNESCAPED_UNICODE),
            [['<p>', '<strong>', '<ul>', '<li>']],
        ];

        // Edge case: question with anchor tags containing attributes.
        $anchorItems = [[
            'question' => 'What is <a href="https://wordpress.org" class="link">WordPress</a>?',
            'answer' => '<p>Visit <a href="https://wordpress.org">wordpress.org</a> for info.</p>',
        ]];
        $cases['anchor_tags_with_attributes'] = [
            $anchorItems,
            json_encode($anchorItems, JSON_UNESCAPED_UNICODE),
            [['<a href="https://wordpress.org">']],
        ];

        // Edge case: question with heading tags.
        $headingItems = [[
            'question' => '<h2>Important Question?</h2>',
            'answer' => '<h3>Answer Heading</h3><p>Details here.</p>',
        ]];
        $cases['heading_tags'] = [
            $headingItems,
            json_encode($headingItems, JSON_UNESCAPED_UNICODE),
            [['<h3>']],
        ];

        // Edge case: all inline HTML tags in question.
        $inlineItems = [[
            'question' => '<b>Bold</b> and <i>italic</i> and <span style="color:red">colored</span> question?',
            'answer' => '<p><code>code_example()</code> is the answer.</p>',
        ]];
        $cases['inline_tags_in_question'] = [
            $inlineItems,
            json_encode($inlineItems, JSON_UNESCAPED_UNICODE),
            [['<code>']],
        ];

        return $cases;
    }

    /**
     * Generate a random non-empty string that is non-empty after trim.
     */
    private static function generateNonEmptyString(int $length): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 .,!?-_()';
        $charsLength = strlen($chars);

        // Ensure at least one non-whitespace character at the start.
        $result = $chars[mt_rand(0, 25)]; // Start with a letter.

        for ($i = 1; $i < $length - 1; $i++) {
            $result .= $chars[mt_rand(0, $charsLength - 1)];
        }

        // End with a non-whitespace character.
        if ($length > 1) {
            $result .= $chars[mt_rand(0, 25)];
        }

        return $result;
    }
}
