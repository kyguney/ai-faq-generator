<?php
/**
 * Property-based test for OpenAIClient testConnection never throws.
 *
 * Feature: openai-compatible-client, Property 4: testConnection never throws
 *
 * Property 4: testConnection never throws
 * Validates: Requirements 4.2, 4.3
 *
 * For any failure condition (network error, authentication failure, server error,
 * malformed response), the testConnection method SHALL return false without throwing
 * an exception; and for any successful response (2xx status), it SHALL return true.
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WPBits\AiFaqGenerator\Includes\OpenAIClient;

class OpenAIClientTestConnectionPropertyTest extends TestCase
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
     * **Validates: Requirements 4.2, 4.3**
     *
     * Property 4: testConnection never throws.
     * For any failure condition, testConnection SHALL return false without throwing.
     */
    #[Test]
    #[DataProvider('failureConditionProvider')]
    public function test_connection_returns_false_without_throwing_for_failure_conditions(
        mixed $mockReturn,
        string $category
    ): void {
        global $afg_test_wp_remote_post_return;

        $afg_test_wp_remote_post_return = $mockReturn;

        $client = new OpenAIClient();

        // testConnection must not throw — capture any exception as a test failure.
        $threwException = false;
        $result = null;

        try {
            $result = $client->testConnection();
        } catch (\Throwable $e) {
            $threwException = true;
        }

        $this->assertFalse(
            $threwException,
            "testConnection must not throw an exception for category '{$category}'"
        );

        $this->assertFalse(
            $result,
            "testConnection must return false for failure category '{$category}'"
        );
    }

    /**
     * **Validates: Requirements 4.2**
     *
     * Verify testConnection returns true when the mock returns a valid 200 response.
     */
    #[Test]
    public function test_connection_returns_true_on_successful_response(): void
    {
        global $afg_test_wp_remote_post_return;

        // Mock a valid 200 response with proper structure.
        $afg_test_wp_remote_post_return = [
            'response' => ['code' => 200],
            'body' => json_encode([
                'choices' => [
                    [
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'Hello',
                        ],
                    ],
                ],
            ]),
        ];

        $client = new OpenAIClient();

        $threwException = false;
        $result = null;

        try {
            $result = $client->testConnection();
        } catch (\Throwable $e) {
            $threwException = true;
        }

        $this->assertFalse(
            $threwException,
            'testConnection must not throw an exception on success'
        );

        $this->assertTrue(
            $result,
            'testConnection must return true when the API returns a valid 200 response'
        );
    }

    /**
     * Data provider generating 110+ failure conditions across all categories.
     *
     * Categories:
     * - wp_error: WP_Error objects with various error messages (Requirement 4.3)
     * - non_2xx: Non-2xx HTTP status codes (Requirement 4.3)
     * - invalid_json: Invalid JSON response bodies (Requirement 4.3)
     *
     * Note: testConnection() only calls sendRequest() which validates WP_Error,
     * HTTP status codes, and JSON parsing. It does NOT call parseResponse(), so
     * a 200 response with valid JSON (even if missing choices path) is a success.
     *
     * @return array<string, array{mixed, string}>
     */
    public static function failureConditionProvider(): array
    {
        $cases = [];

        mt_srand(99999);

        // ─── Category 1: WP_Error objects (network failures) ─────────────────────
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
            'cURL error 52: Empty reply from server',
            'cURL error 55: Failed sending network data',
            'cURL error 16: HTTP/2 error',
            'cURL error 47: Too many redirects',
            'cURL error 60: SSL peer certificate was not OK',
        ];

        foreach ($wpErrorMessages as $i => $message) {
            $cases["wp_error_{$i}"] = [
                new \WP_Error('http_request_failed', $message),
                'wp_error',
            ];
        }

        // ─── Category 2: Non-2xx HTTP status codes ───────────────────────────────
        $httpStatusCodes = [400, 401, 403, 404, 405, 408, 429, 500, 502, 503, 504];
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
            405 => [
                'Method not allowed',
                'Only POST requests are accepted',
            ],
            408 => [
                'Request timeout',
                'Server timed out waiting for the request',
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
            504 => [
                'Gateway timeout',
                'Upstream server did not respond in time',
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

        // ─── Category 3: Invalid JSON response bodies ────────────────────────────
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

        // ─── Category 4: Additional WP_Error edge cases ────────────────────────────
        $additionalWpErrors = [
            'Empty reply from server',
            'Failed sending data to the peer',
            'Peer certificate cannot be authenticated with given CA certificates',
            'SSL: no alternative certificate subject name matches target host name',
            'Operation too slow. Less than 1 bytes/sec transferred the last 30 seconds',
            'Resolving timed out after 5000 milliseconds',
            'Could not resolve proxy: proxy.example.com',
            'SOCKS5 communication to proxy failed',
            'Transferred a partial file',
            'Upload failed (at start/before it took off)',
            'Out of memory',
            'Unrecognized transfer encoding',
            'Login denied',
            'Remote file not found',
            'Error in the HTTP2 framing layer',
            'Received HTTP/0.9 when not allowed',
            'FTP: unknown PASS reply',
            'Weird server reply',
            'Remote access denied',
            'Failure when receiving data from the peer',
        ];

        foreach ($additionalWpErrors as $i => $message) {
            $cases["wp_error_extra_{$i}"] = [
                new \WP_Error('http_request_failed', $message),
                'wp_error',
            ];
        }

        // ─── Category 5: Additional edge cases with unusual status codes ─────────
        $unusualStatusCodes = [
            100, 101, 102, 103,   // Informational (non-2xx)
            300, 301, 302, 303, 307, 308, // Redirects (non-2xx)
            410, 413, 414, 415, 418, 422, 451, // Client errors
            505, 507, 508, 511,   // Server errors
        ];

        foreach ($unusualStatusCodes as $i => $code) {
            $cases["unusual_status_{$code}"] = [
                [
                    'response' => ['code' => $code],
                    'body' => json_encode(['error' => ['message' => "HTTP {$code} error"]]),
                ],
                'non_2xx',
            ];
        }

        return $cases;
    }
}
