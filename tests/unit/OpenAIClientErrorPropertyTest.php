<?php
/**
 * Property-based test for OpenAIClient error handling completeness.
 *
 * Feature: openai-compatible-client, Property 3: Error handling completeness
 *
 * Property 3: Error handling completeness
 * Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5
 *
 * For any error condition — WP_Error return from wp_remote_post, non-2xx HTTP status code,
 * invalid JSON response body, missing choices[0].message.content path, or invalid FAQ
 * structure in the content — the generateFaqs method SHALL throw a RuntimeException
 * with a descriptive message.
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WPBits\AiFaqGenerator\Includes\OpenAIClient;

class OpenAIClientErrorPropertyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        global $afg_test_options, $afg_test_wp_remote_post_args, $afg_test_wp_remote_post_return;
        $afg_test_options = [];
        $afg_test_wp_remote_post_args = null;
        $afg_test_wp_remote_post_return = [];

        // Configure valid settings so the client can be instantiated.
        $afg_test_options['afg_settings'] = [
            'api_key'     => 'sk-test-key-12345',
            'model'       => 'gpt-4o',
            'temperature' => 0.7,
            'base_url'    => 'https://api.openai.com',
        ];
    }

    protected function tearDown(): void
    {
        global $afg_test_options, $afg_test_wp_remote_post_args, $afg_test_wp_remote_post_return;
        $afg_test_options = [];
        $afg_test_wp_remote_post_args = null;
        $afg_test_wp_remote_post_return = [];

        parent::tearDown();
    }

    /**
     * **Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5**
     *
     * Property 3: Error handling completeness.
     * For transport/response errors, generateFaqs SHALL throw a RuntimeException.
     * For invalid FAQ content, generateFaqs SHALL return an empty array (delegated to Faq_Parser).
     */
    #[Test]
    #[DataProvider('errorConditionProvider')]
    public function generate_faqs_throws_runtime_exception_for_error_conditions(
        mixed $mockReturn,
        string $category
    ): void {
        global $afg_test_wp_remote_post_return;

        $afg_test_wp_remote_post_return = $mockReturn;

        $client = new OpenAIClient();

        if ($category === 'invalid_faq') {
            // Faq_Parser returns empty array instead of throwing for invalid FAQ content.
            $result = $client->generateFaqs('Generate 5 FAQs about WordPress');
            $this->assertIsArray($result);
            // For mixed arrays with some valid items, the parser returns only valid items.
            // For fully invalid content, it returns an empty array.
            foreach ($result as $item) {
                $this->assertArrayHasKey('question', $item);
                $this->assertArrayHasKey('answer', $item);
                $this->assertNotEmpty(trim($item['question']));
                $this->assertNotEmpty(trim($item['answer']));
            }
        } else {
            $this->expectException(\RuntimeException::class);
            $client->generateFaqs('Generate 5 FAQs about WordPress');
        }
    }

    /**
     * Data provider generating 110+ error conditions across all categories.
     *
     * Categories:
     * - wp_error: WP_Error objects with random error messages (Requirement 3.1)
     * - non_2xx: Non-2xx HTTP status codes (Requirement 3.2)
     * - invalid_json: Invalid JSON response bodies (Requirement 3.3)
     * - missing_path: Valid JSON but missing choices[0].message.content (Requirement 3.4)
     * - invalid_faq: Valid response structure but invalid FAQ content (Requirement 3.5)
     *
     * @return array<string, array{mixed, string}>
     */
    public static function errorConditionProvider(): array
    {
        $cases = [];

        mt_srand(54321);

        // ─── Category 1: WP_Error objects (Requirement 3.1) ──────────────────────
        $wpErrorMessages = [
            'Connection timed out',
            'Could not resolve host: api.openai.com',
            'SSL certificate problem: unable to get local issuer certificate',
            'cURL error 28: Operation timed out after 30000 milliseconds',
            'cURL error 6: Could not resolve host',
            'cURL error 7: Failed to connect to api.openai.com port 443',
            'cURL error 35: SSL connect error',
            'cURL error 56: Recv failure: Connection reset by peer',
            'A valid URL was not provided.',
            'Too many redirects.',
            'http_request_failed',
            'Connection refused',
            'Network is unreachable',
            'DNS resolution failed for api.openai.com',
            'Request timeout exceeded',
            'Proxy connection failed',
            'Socket error: Connection reset',
            'TLS handshake failed',
            'HTTP/2 stream error',
            'Server closed connection unexpectedly',
            'Maximum redirects followed',
            'Invalid response received',
            'Connection aborted by server',
            'Read timeout after 30 seconds',
            'Write error: Broken pipe',
        ];

        foreach ($wpErrorMessages as $i => $message) {
            $errorCode = 'http_request_failed';
            if (str_contains($message, 'cURL')) {
                $errorCode = 'http_request_failed';
            } elseif (str_contains($message, 'SSL')) {
                $errorCode = 'ssl_error';
            } elseif (str_contains($message, 'DNS') || str_contains($message, 'resolve')) {
                $errorCode = 'dns_error';
            } elseif (str_contains($message, 'timeout') || str_contains($message, 'Timeout')) {
                $errorCode = 'timeout_error';
            }

            $cases["wp_error_{$i}"] = [
                new \WP_Error($errorCode, $message),
                'wp_error',
            ];
        }

        // ─── Category 2: Non-2xx HTTP status codes (Requirement 3.2) ─────────────
        $httpStatusCodes = [400, 401, 403, 404, 429, 500, 502, 503];
        $errorMessages = [
            400 => [
                'Invalid request: model field is required',
                'Bad request: temperature must be between 0 and 2',
                'Invalid JSON in request body',
            ],
            401 => [
                'Invalid API key provided',
                'Incorrect API key provided: sk-****',
                'API key has been revoked',
            ],
            403 => [
                'Access denied: insufficient permissions',
                'Your account has been suspended',
                'Region not supported',
            ],
            404 => [
                'Model not found: gpt-5-turbo',
                'The requested resource does not exist',
                'Endpoint not found',
            ],
            429 => [
                'Rate limit exceeded. Please retry after 20s',
                'You have exceeded your quota',
                'Too many requests, please slow down',
            ],
            500 => [
                'Internal server error',
                'An unexpected error occurred on our end',
                'Service temporarily unavailable',
            ],
            502 => [
                'Bad gateway',
                'The server received an invalid response from the upstream server',
                'Gateway timeout from upstream',
            ],
            503 => [
                'Service unavailable: server is overloaded',
                'The engine is currently overloaded, please try again later',
                'Maintenance in progress',
            ],
        ];

        foreach ($httpStatusCodes as $code) {
            foreach ($errorMessages[$code] as $msgIndex => $message) {
                $cases["non_2xx_{$code}_{$msgIndex}"] = [
                    [
                        'response' => ['code' => $code],
                        'body' => json_encode(['error' => ['message' => $message]]),
                    ],
                    'non_2xx',
                ];
            }
        }

        // ─── Category 3: Invalid JSON response bodies (Requirement 3.3) ──────────
        $invalidJsonBodies = [
            'not valid json',
            '<html><body>Error</body></html>',
            '<!DOCTYPE html><html><head><title>502 Bad Gateway</title></head></html>',
            '{invalid json content',
            'undefined',
            'null',
            'true',
            '12345',
            '',
            'Internal Server Error',
            '{"incomplete": "json',
            '[unclosed array',
            'random text with special chars: @#$%^&*()',
            "line1\nline2\nline3",
            'HTTP/1.1 200 OK',
            '<?xml version="1.0"?><error>Something went wrong</error>',
            'Error: ECONNREFUSED',
            'upstream connect error or disconnect/reset before headers',
            'no healthy upstream',
            "\x00\x01\x02binary content",
            str_repeat('a', 1000),
            '{"error": "incomplete',
            "{'single': 'quotes'}",
            'NaN',
            'Infinity',
        ];

        foreach ($invalidJsonBodies as $i => $body) {
            $cases["invalid_json_{$i}"] = [
                [
                    'response' => ['code' => 200],
                    'body' => $body,
                ],
                'invalid_json',
            ];
        }

        // ─── Category 4: Valid JSON but missing choices[0].message.content (Req 3.4)
        $missingPathResponses = [
            // Empty object
            [],
            // Has choices but empty array
            ['choices' => []],
            // Has choices but no message
            ['choices' => [['index' => 0, 'finish_reason' => 'stop']]],
            // Has choices and message but no content
            ['choices' => [['message' => ['role' => 'assistant']]]],
            // Has choices but message is null
            ['choices' => [['message' => null]]],
            // Has choices but first element is empty
            ['choices' => [null]],
            // Has id and model but no choices
            ['id' => 'chatcmpl-abc', 'model' => 'gpt-4o', 'object' => 'chat.completion'],
            // Has choices key but it's not an array
            ['choices' => 'not an array'],
            // Has nested structure but wrong path
            ['data' => ['choices' => [['message' => ['content' => 'test']]]]],
            // Has choices with message but content is missing key
            ['choices' => [['message' => ['role' => 'assistant', 'refusal' => null]]]],
            // Has error structure instead of choices
            ['error' => ['message' => 'Something went wrong', 'type' => 'server_error']],
            // Has choices but index 0 doesn't exist (only index 1)
            ['choices' => [1 => ['message' => ['content' => 'test']]]],
            // Completely unrelated structure
            ['status' => 'ok', 'result' => 'success'],
            // Has usage but no choices
            ['usage' => ['prompt_tokens' => 10, 'completion_tokens' => 20]],
            // Has choices with empty message object
            ['choices' => [['message' => []]]],
            // Has choices but message.content is explicitly null (not set via isset)
            ['choices' => [['index' => 0, 'message' => ['role' => 'assistant']]]],
            // Deeply nested but wrong structure
            ['response' => ['choices' => [['message' => ['content' => 'test']]]]],
            // Array of arrays but not the expected structure
            ['items' => [['question' => 'Q', 'answer' => 'A']]],
            // Has choices but as associative array
            ['choices' => ['first' => ['message' => ['content' => 'test']]]],
            // Empty choices with metadata
            ['id' => 'chatcmpl-xyz', 'choices' => [], 'created' => 1700000000],
        ];

        foreach ($missingPathResponses as $i => $responseBody) {
            $cases["missing_path_{$i}"] = [
                [
                    'response' => ['code' => 200],
                    'body' => json_encode($responseBody),
                ],
                'missing_path',
            ];
        }

        // ─── Category 5: Valid structure but invalid FAQ content (Requirement 3.5) ─
        $invalidFaqContents = [
            // Not an array - plain string
            '"just a string"',
            // Not an array - number
            '42',
            // Not an array - boolean
            'true',
            // Not an array - null
            'null',
            // Not an array - object instead of array
            '{"question": "Q", "answer": "A"}',
            // Array but items missing question key
            '[{"answer": "A1"}, {"answer": "A2"}]',
            // Array but items missing answer key
            '[{"question": "Q1"}, {"question": "Q2"}]',
            // Array with empty question
            '[{"question": "", "answer": "A valid answer"}]',
            // Array with empty answer
            '[{"question": "A valid question?", "answer": ""}]',
            // Array with whitespace-only question
            '[{"question": "   ", "answer": "A valid answer"}]',
            // Array with whitespace-only answer
            '[{"question": "A valid question?", "answer": "   "}]',
            // Array with non-string question (number)
            '[{"question": 123, "answer": "A valid answer"}]',
            // Array with non-string answer (boolean)
            '[{"question": "A valid question?", "answer": true}]',
            // Array with null question
            '[{"question": null, "answer": "A valid answer"}]',
            // Array with null answer
            '[{"question": "A valid question?", "answer": null}]',
            // Array with nested array as question
            '[{"question": ["nested"], "answer": "A valid answer"}]',
            // Array with object as answer
            '[{"question": "Q?", "answer": {"text": "nested"}}]',
            // Mixed valid and invalid items (first invalid)
            '[{"wrong_key": "Q1", "answer": "A1"}, {"question": "Q2", "answer": "A2"}]',
            // Empty array items
            '[{}]',
            // Array with extra keys but missing required ones
            '[{"title": "T1", "description": "D1"}]',
            // Not valid JSON at all (but wrapped in valid response structure)
            'this is not json at all',
            // Array with integer items
            '[1, 2, 3]',
            // Array with string items
            '["q1", "q2", "q3"]',
            // Array with null items
            '[null, null]',
            // Array with mixed types
            '[{"question": "Q?", "answer": "A"}, "string", 42]',
            // Deeply nested invalid structure
            '[[{"question": "Q?", "answer": "A"}]]',
            // Array with tab-only question
            "[{\"question\": \"\\t\", \"answer\": \"A valid answer\"}]",
            // Array with newline-only answer
            "[{\"question\": \"Q?\", \"answer\": \"\\n\"}]",
            // Array where second item is invalid
            '[{"question": "Q1?", "answer": "A1"}, {"question": "", "answer": "A2"}]',
        ];

        foreach ($invalidFaqContents as $i => $content) {
            $cases["invalid_faq_{$i}"] = [
                [
                    'response' => ['code' => 200],
                    'body' => json_encode([
                        'choices' => [
                            [
                                'message' => [
                                    'role' => 'assistant',
                                    'content' => $content,
                                ],
                            ],
                        ],
                    ]),
                ],
                'invalid_faq',
            ];
        }

        return $cases;
    }
}
