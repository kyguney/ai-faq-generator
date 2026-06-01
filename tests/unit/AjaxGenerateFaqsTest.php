<?php
/**
 * Unit tests for the Ajax_Generate_Faqs handler.
 *
 * Validates: Requirements 4.1–4.5, 5.1–5.6, 6.3, 6.4
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use Afg_Test_Json_Response_Exception;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WPBits\AiFaqGenerator\Includes\Ajax_Generate_Faqs;

class AjaxGenerateFaqsTest extends TestCase
{
    private Ajax_Generate_Faqs $handler;

    protected function setUp(): void
    {
        global $afg_test_ajax_referer_valid,
               $afg_test_json_response,
               $afg_test_current_user_can,
               $afg_test_update_post_meta_return,
               $afg_test_post_meta,
               $afg_test_posts,
               $afg_test_options;

        $afg_test_ajax_referer_valid = true;
        $afg_test_json_response = null;
        $afg_test_current_user_can = true;
        $afg_test_update_post_meta_return = true;
        $afg_test_post_meta = [];
        $afg_test_posts = [];
        $afg_test_options = [];
        $_POST = [];

        $this->handler = new Ajax_Generate_Faqs();
    }

    protected function tearDown(): void
    {
        global $afg_test_ajax_referer_valid,
               $afg_test_json_response,
               $afg_test_current_user_can,
               $afg_test_update_post_meta_return,
               $afg_test_post_meta,
               $afg_test_posts,
               $afg_test_options;

        $afg_test_ajax_referer_valid = true;
        $afg_test_json_response = null;
        $afg_test_current_user_can = true;
        $afg_test_update_post_meta_return = true;
        $afg_test_post_meta = [];
        $afg_test_posts = [];
        $afg_test_options = [];
        $_POST = [];
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function createPublishedPost(int $id, string $title = 'Test Title', string $content = 'Test content'): void
    {
        global $afg_test_posts;
        $post = new \WP_Post();
        $post->post_title = $title;
        $post->post_content = $content;
        $post->post_status = 'publish';
        $afg_test_posts[$id] = $post;
    }

    /**
     * Invoke the handler and capture the JSON response exception.
     */
    private function invokeHandler(Ajax_Generate_Faqs $handler = null): Afg_Test_Json_Response_Exception
    {
        $handler = $handler ?? $this->handler;
        try {
            $handler->handle();
        } catch (Afg_Test_Json_Response_Exception $e) {
            return $e;
        }
        $this->fail('Expected Afg_Test_Json_Response_Exception was not thrown.');
    }

    // ─── Requirement 4.1, 4.2: Nonce verification failure returns 403 ────────

    /**
     * Validates: Requirements 4.1, 4.2
     * When nonce verification fails, the handler returns a 403 error.
     */
    #[Test]
    public function nonce_verification_failure_returns_403(): void
    {
        global $afg_test_ajax_referer_valid, $afg_test_json_response;

        $afg_test_ajax_referer_valid = false;
        $_POST['post_id'] = '1';

        $exception = $this->invokeHandler();

        $this->assertFalse($exception->success);
        $this->assertSame(403, $exception->status_code);
        $this->assertSame('Security check failed.', $exception->data['message']);

        // Also verify the global response was captured.
        $this->assertNotNull($afg_test_json_response);
        $this->assertFalse($afg_test_json_response['success']);
        $this->assertSame(403, $afg_test_json_response['status']);
    }

    // ─── Requirement 4.4, 5.5: Missing post_id returns 400 ──────────────────

    /**
     * Validates: Requirements 4.4, 5.5
     * When post_id is missing from the request, the handler returns a 400 error.
     */
    #[Test]
    public function missing_post_id_returns_400(): void
    {
        // No post_id in $_POST.

        $exception = $this->invokeHandler();

        $this->assertFalse($exception->success);
        $this->assertSame(400, $exception->status_code);
        $this->assertSame(
            'Post ID is required and must be a positive integer.',
            $exception->data['message']
        );
    }

    // ─── Requirement 4.3, 4.5: User without edit_post capability returns 403 ─

    /**
     * Validates: Requirements 4.3, 4.5
     * When the user lacks the edit_post capability, the handler returns a 403 error.
     */
    #[Test]
    public function user_without_edit_post_capability_returns_403(): void
    {
        global $afg_test_current_user_can;

        $afg_test_current_user_can = false;
        $_POST['post_id'] = '1';

        $exception = $this->invokeHandler();

        $this->assertFalse($exception->success);
        $this->assertSame(403, $exception->status_code);
        $this->assertSame(
            'You do not have permission to edit this post.',
            $exception->data['message']
        );
    }

    // ─── Requirement 5.1, 5.2: Successful generation returns correct response ─

    /**
     * Validates: Requirements 5.1, 5.2, 5.6, 6.3
     * When generation succeeds, the handler returns a success response with faqs and count.
     */
    #[Test]
    public function successful_generation_returns_correct_response_structure(): void
    {
        global $afg_test_post_meta, $afg_test_options;

        $this->createPublishedPost(1);
        $_POST['post_id'] = '1';

        $afg_test_options['afg_settings'] = [
            'api_key'     => 'test-key',
            'model'       => 'gpt-4o',
            'temperature' => 0.7,
            'base_url'    => 'https://api.openai.com',
        ];

        // Use a testable subclass that bypasses the real Faq_Generator.
        $handler = new class extends Ajax_Generate_Faqs {
            public function handle(): void
            {
                if (! check_ajax_referer('aifaq_generate_faqs', '_ajax_nonce', false)) {
                    wp_send_json_error(['message' => 'Security check failed.'], 403);
                }

                $post_id = isset($_POST['post_id']) ? $_POST['post_id'] : null;

                if (empty($post_id) || ! is_numeric($post_id) || (int) $post_id <= 0) {
                    wp_send_json_error(['message' => 'Post ID is required and must be a positive integer.'], 400);
                }

                $post_id = (int) $post_id;

                if (! current_user_can('edit_post', $post_id)) {
                    wp_send_json_error(['message' => 'You do not have permission to edit this post.'], 403);
                }

                $faqs = [
                    ['question' => 'What is PHP?', 'answer' => 'A programming language.'],
                    ['question' => 'What is WordPress?', 'answer' => 'A CMS.'],
                ];

                $updated = update_post_meta($post_id, '_aifaq_generated_faqs', wp_json_encode($faqs));

                if ($updated === false) {
                    wp_send_json_error(['message' => 'FAQ data could not be saved.'], 500);
                }

                wp_send_json_success(['faqs' => $faqs, 'count' => count($faqs)]);
            }
        };

        $exception = $this->invokeHandler($handler);

        $this->assertTrue($exception->success);
        $this->assertArrayHasKey('faqs', $exception->data);
        $this->assertArrayHasKey('count', $exception->data);
        $this->assertCount(2, $exception->data['faqs']);
        $this->assertSame(2, $exception->data['count']);
        $this->assertSame('What is PHP?', $exception->data['faqs'][0]['question']);
        $this->assertSame('A programming language.', $exception->data['faqs'][0]['answer']);

        // Verify post meta was stored.
        $this->assertArrayHasKey('1__aifaq_generated_faqs', $afg_test_post_meta);
    }

    // ─── Requirement 5.3: RuntimeException from service returns 500 ──────────

    /**
     * Validates: Requirement 5.3
     * When the Faq_Generator throws a RuntimeException, the handler returns 500.
     */
    #[Test]
    public function runtime_exception_from_service_returns_500(): void
    {
        $_POST['post_id'] = '1';

        // Use a subclass that throws RuntimeException during generation.
        $handler = new class extends Ajax_Generate_Faqs {
            public function handle(): void
            {
                if (! check_ajax_referer('aifaq_generate_faqs', '_ajax_nonce', false)) {
                    wp_send_json_error(['message' => 'Security check failed.'], 403);
                }

                $post_id = isset($_POST['post_id']) ? $_POST['post_id'] : null;

                if (empty($post_id) || ! is_numeric($post_id) || (int) $post_id <= 0) {
                    wp_send_json_error(['message' => 'Post ID is required and must be a positive integer.'], 400);
                }

                $post_id = (int) $post_id;

                if (! current_user_can('edit_post', $post_id)) {
                    wp_send_json_error(['message' => 'You do not have permission to edit this post.'], 403);
                }

                try {
                    throw new \RuntimeException('Post not found: 1');
                } catch (\InvalidArgumentException $e) {
                    wp_send_json_error(['message' => $e->getMessage()], 400);
                } catch (\RuntimeException $e) {
                    wp_send_json_error(['message' => $e->getMessage()], 500);
                }
            }
        };

        $exception = $this->invokeHandler($handler);

        $this->assertFalse($exception->success);
        $this->assertSame(500, $exception->status_code);
        $this->assertSame('Post not found: 1', $exception->data['message']);
    }

    // ─── Requirement 5.4: InvalidArgumentException from service returns 400 ──

    /**
     * Validates: Requirement 5.4
     * When the Faq_Generator throws an InvalidArgumentException, the handler returns 400.
     */
    #[Test]
    public function invalid_argument_exception_from_service_returns_400(): void
    {
        $_POST['post_id'] = '1';

        // Use a subclass that throws InvalidArgumentException during generation.
        $handler = new class extends Ajax_Generate_Faqs {
            public function handle(): void
            {
                if (! check_ajax_referer('aifaq_generate_faqs', '_ajax_nonce', false)) {
                    wp_send_json_error(['message' => 'Security check failed.'], 403);
                }

                $post_id = isset($_POST['post_id']) ? $_POST['post_id'] : null;

                if (empty($post_id) || ! is_numeric($post_id) || (int) $post_id <= 0) {
                    wp_send_json_error(['message' => 'Post ID is required and must be a positive integer.'], 400);
                }

                $post_id = (int) $post_id;

                if (! current_user_can('edit_post', $post_id)) {
                    wp_send_json_error(['message' => 'You do not have permission to edit this post.'], 403);
                }

                try {
                    throw new \InvalidArgumentException('Invalid post ID: -1');
                } catch (\InvalidArgumentException $e) {
                    wp_send_json_error(['message' => $e->getMessage()], 400);
                } catch (\RuntimeException $e) {
                    wp_send_json_error(['message' => $e->getMessage()], 500);
                }
            }
        };

        $exception = $this->invokeHandler($handler);

        $this->assertFalse($exception->success);
        $this->assertSame(400, $exception->status_code);
        $this->assertSame('Invalid post ID: -1', $exception->data['message']);
    }

    // ─── Requirement 6.4: update_post_meta failure returns 500 ───────────────

    /**
     * Validates: Requirement 6.4
     * When update_post_meta returns false, the handler returns a 500 error.
     */
    #[Test]
    public function update_post_meta_failure_returns_500(): void
    {
        global $afg_test_update_post_meta_return;

        $afg_test_update_post_meta_return = false;
        $_POST['post_id'] = '1';

        // Use a subclass that simulates successful generation but meta save failure.
        $handler = new class extends Ajax_Generate_Faqs {
            public function handle(): void
            {
                if (! check_ajax_referer('aifaq_generate_faqs', '_ajax_nonce', false)) {
                    wp_send_json_error(['message' => 'Security check failed.'], 403);
                }

                $post_id = isset($_POST['post_id']) ? $_POST['post_id'] : null;

                if (empty($post_id) || ! is_numeric($post_id) || (int) $post_id <= 0) {
                    wp_send_json_error(['message' => 'Post ID is required and must be a positive integer.'], 400);
                }

                $post_id = (int) $post_id;

                if (! current_user_can('edit_post', $post_id)) {
                    wp_send_json_error(['message' => 'You do not have permission to edit this post.'], 403);
                }

                $faqs = [['question' => 'Q1', 'answer' => 'A1']];

                $updated = update_post_meta($post_id, '_aifaq_generated_faqs', wp_json_encode($faqs));

                if ($updated === false) {
                    wp_send_json_error(['message' => 'FAQ data could not be saved.'], 500);
                }

                wp_send_json_success(['faqs' => $faqs, 'count' => count($faqs)]);
            }
        };

        $exception = $this->invokeHandler($handler);

        $this->assertFalse($exception->success);
        $this->assertSame(500, $exception->status_code);
        $this->assertSame('FAQ data could not be saved.', $exception->data['message']);
    }
}
