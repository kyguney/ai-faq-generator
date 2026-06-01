<?php
/**
 * Property-based test for successful response structure invariant.
 *
 * Feature: generate-button, Property 2: Successful response structure invariant
 * Validates: Requirements 5.2
 *
 * For any valid FAQ array returned by the Faq_Generator service (an array of objects
 * each containing non-empty "question" and "answer" strings), the AJAX handler's
 * success response SHALL contain a `faqs` key holding the exact array and a `count`
 * key whose integer value equals the length of the `faqs` array.
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WPBits\AiFaqGenerator\Includes\Ajax_Generate_Faqs;
use WPBits\AiFaqGenerator\Includes\Interfaces\AIProviderInterface;
use WPBits\AiFaqGenerator\Includes\Services\Faq_Generator;
use WPBits\AiFaqGenerator\Includes\Services\Prompt_Builder;

class AjaxSuccessResponsePropertyTest extends TestCase
{
    protected function setUp(): void
    {
        global $afg_test_ajax_referer_valid,
               $afg_test_current_user_can,
               $afg_test_json_response,
               $afg_test_update_post_meta_return,
               $afg_test_posts;

        // Default: all security checks pass
        $afg_test_ajax_referer_valid = true;
        $afg_test_current_user_can = true;
        $afg_test_json_response = null;
        $afg_test_update_post_meta_return = true;
        $afg_test_posts = [];

        // Set up a valid published post for Faq_Generator
        $post = new \WP_Post();
        $post->post_title = 'Test Post';
        $post->post_content = 'Test content for FAQ generation.';
        $post->post_status = 'publish';
        $afg_test_posts[1] = $post;
    }

    protected function tearDown(): void
    {
        global $afg_test_ajax_referer_valid,
               $afg_test_current_user_can,
               $afg_test_json_response,
               $afg_test_update_post_meta_return,
               $afg_test_posts;

        $afg_test_ajax_referer_valid = true;
        $afg_test_current_user_can = true;
        $afg_test_json_response = null;
        $afg_test_update_post_meta_return = true;
        $afg_test_posts = [];

        // Clean up $_POST
        unset($_POST['post_id'], $_POST['_ajax_nonce']);
    }

    /**
     * **Validates: Requirements 5.2**
     *
     * Property 2: Successful response structure invariant.
     *
     * For any valid FAQ array returned by the Faq_Generator service, the AJAX
     * handler's success response SHALL contain a `faqs` key holding the exact
     * array and a `count` key whose integer value equals the length of the array.
     */
    #[Test]
    #[DataProvider('validFaqArrayProvider')]
    public function successful_response_contains_faqs_and_count(array $faqs): void
    {
        global $afg_test_json_response;

        // Set up POST data with valid post_id
        $_POST['post_id'] = '1';
        $_POST['_ajax_nonce'] = 'valid_nonce';

        // Create a mock Faq_Generator that returns the generated FAQ array
        $ai_provider = $this->createMock(AIProviderInterface::class);
        $ai_provider->method('generateFaqs')->willReturn($faqs);

        $prompt_builder = $this->createMock(Prompt_Builder::class);
        $prompt_builder->method('build')->willReturn('test prompt');

        $faq_generator = new Faq_Generator($ai_provider, $prompt_builder);

        // Inject the mock Faq_Generator into the handler
        $handler = new Ajax_Generate_Faqs($faq_generator);

        // Execute the handler — it will throw Afg_Test_Json_Response_Exception
        try {
            $handler->handle();
        } catch (\Afg_Test_Json_Response_Exception $e) {
            // Expected: wp_send_json_success throws this exception
        }

        // Verify the response structure
        $this->assertNotNull($afg_test_json_response, 'Handler should have sent a JSON response.');
        $this->assertTrue($afg_test_json_response['success'], 'Response should be a success response.');

        $data = $afg_test_json_response['data'];

        // Verify `faqs` key contains the exact array
        $this->assertArrayHasKey('faqs', $data, 'Response data must contain a "faqs" key.');
        $this->assertSame($faqs, $data['faqs'], 'The "faqs" key must hold the exact FAQ array returned by the generator.');

        // Verify `count` key equals the array length
        $this->assertArrayHasKey('count', $data, 'Response data must contain a "count" key.');
        $this->assertSame(count($faqs), $data['count'], 'The "count" key must equal the length of the faqs array.');
    }

    /**
     * Data provider generating 100+ valid FAQ arrays.
     *
     * Each FAQ array contains objects with non-empty "question" and "answer" strings.
     *
     * @return array<string, array{array<int, array{question: string, answer: string}>}>
     */
    public static function validFaqArrayProvider(): array
    {
        $cases = [];

        mt_srand(54321);

        // Single item arrays (20 cases)
        for ($i = 0; $i < 20; $i++) {
            $faqs = [self::randomValidFaqItem()];
            $cases["single_item_{$i}"] = [$faqs];
        }

        // Two item arrays (20 cases)
        for ($i = 0; $i < 20; $i++) {
            $faqs = [
                self::randomValidFaqItem(),
                self::randomValidFaqItem(),
            ];
            $cases["two_items_{$i}"] = [$faqs];
        }

        // Three to five item arrays (30 cases)
        for ($i = 0; $i < 30; $i++) {
            $count = mt_rand(3, 5);
            $faqs = [];
            for ($j = 0; $j < $count; $j++) {
                $faqs[] = self::randomValidFaqItem();
            }
            $cases["multi_items_{$i}"] = [$faqs];
        }

        // Larger arrays 6-15 items (20 cases)
        for ($i = 0; $i < 20; $i++) {
            $count = mt_rand(6, 15);
            $faqs = [];
            for ($j = 0; $j < $count; $j++) {
                $faqs[] = self::randomValidFaqItem();
            }
            $cases["large_array_{$i}"] = [$faqs];
        }

        // Very large array (20 items)
        $largeFaqs = [];
        for ($j = 0; $j < 20; $j++) {
            $largeFaqs[] = self::randomValidFaqItem();
        }
        $cases['twenty_items'] = [$largeFaqs];

        // Edge case: items with special characters
        for ($i = 0; $i < 10; $i++) {
            $faqs = [];
            $count = mt_rand(1, 4);
            for ($j = 0; $j < $count; $j++) {
                $faqs[] = [
                    'question' => self::randomStringWithSpecialChars(mt_rand(5, 60)),
                    'answer'   => self::randomStringWithSpecialChars(mt_rand(10, 120)),
                ];
            }
            $cases["special_chars_{$i}"] = [$faqs];
        }

        // Edge case: items with unicode
        $cases['unicode_items'] = [[
            ['question' => 'Qu\'est-ce que c\'est?', 'answer' => 'C\'est une réponse.'],
            ['question' => '这是什么？', 'answer' => '这是一个答案。'],
            ['question' => 'Was ist das?', 'answer' => 'Das ist eine Antwort.'],
        ]];

        // Edge case: items with very long strings
        $cases['long_strings'] = [[
            ['question' => str_repeat('Q', 500), 'answer' => str_repeat('A', 1000)],
        ]];

        // Edge case: single character question and answer
        $cases['minimal_strings'] = [[
            ['question' => 'Q', 'answer' => 'A'],
        ]];

        return $cases;
    }

    /**
     * Generate a random valid FAQ item with non-empty question and answer.
     *
     * @return array{question: string, answer: string}
     */
    private static function randomValidFaqItem(): array
    {
        return [
            'question' => self::randomNonEmptyString(mt_rand(5, 80)),
            'answer'   => self::randomNonEmptyString(mt_rand(10, 200)),
        ];
    }

    /**
     * Generate a random non-empty string of given length.
     */
    private static function randomNonEmptyString(int $length): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789 .,!?-_()';
        $str = '';
        $charsLen = strlen($chars) - 1;

        // Ensure first character is non-whitespace
        $nonSpace = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str .= $nonSpace[mt_rand(0, strlen($nonSpace) - 1)];

        for ($i = 1; $i < $length; $i++) {
            $str .= $chars[mt_rand(0, $charsLen)];
        }

        return $str;
    }

    /**
     * Generate a random string with special characters.
     */
    private static function randomStringWithSpecialChars(int $length): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789<>&"\'!@#$%^*()[]{}|;:,./?~`+=';
        $str = '';
        $charsLen = strlen($chars) - 1;

        // Ensure first character is a letter (non-empty guarantee)
        $letters = 'abcdefghijklmnopqrstuvwxyz';
        $str .= $letters[mt_rand(0, strlen($letters) - 1)];

        for ($i = 1; $i < $length; $i++) {
            $str .= $chars[mt_rand(0, $charsLen)];
        }

        return $str;
    }
}
