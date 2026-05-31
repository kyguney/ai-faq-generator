<?php
/**
 * Property-based test for the testConnection failure safety contract.
 *
 * Feature: ai-faq-generator-provider-interface, Property 3: testConnection never throws on failure
 *
 * Property 3: testConnection never throws on failure
 * Validates: Requirements 4.5
 *
 * For any simulated connection failure condition, a conforming provider's
 * `testConnection` method SHALL return `false` without throwing an exception.
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WPBits\AiFaqGenerator\Includes\Interfaces\AIProviderInterface;

/**
 * Mock provider that simulates various failure conditions in testConnection.
 *
 * This provider catches all internal exceptions and returns false,
 * demonstrating the contract that testConnection never throws on failure.
 */
class FailureSimulatingProvider implements AIProviderInterface
{
    private string $failureType;
    private string $failureMessage;
    private int $failureCode;

    public function __construct(string $failureType, string $failureMessage = '', int $failureCode = 0)
    {
        $this->failureType = $failureType;
        $this->failureMessage = $failureMessage;
        $this->failureCode = $failureCode;
    }

    public function generateFaqs(string $prompt): array
    {
        return [];
    }

    public function testConnection(): bool
    {
        try {
            $this->simulateFailure();
            return true;
        } catch (\Throwable $e) {
            // Contract: testConnection never throws on failure, returns false.
            return false;
        }
    }

    /**
     * Simulate the configured failure condition by throwing the appropriate exception.
     */
    private function simulateFailure(): void
    {
        match ($this->failureType) {
            'network_timeout' => throw new \RuntimeException(
                $this->failureMessage ?: 'Connection timed out',
                $this->failureCode ?: 28
            ),
            'dns_resolution' => throw new \RuntimeException(
                $this->failureMessage ?: 'Could not resolve host',
                $this->failureCode ?: 6
            ),
            'connection_refused' => throw new \RuntimeException(
                $this->failureMessage ?: 'Connection refused',
                $this->failureCode ?: 7
            ),
            'ssl_error' => throw new \RuntimeException(
                $this->failureMessage ?: 'SSL certificate problem',
                $this->failureCode ?: 60
            ),
            'auth_failure' => throw new \RuntimeException(
                $this->failureMessage ?: 'Invalid API key',
                $this->failureCode ?: 401
            ),
            'rate_limited' => throw new \RuntimeException(
                $this->failureMessage ?: 'Rate limit exceeded',
                $this->failureCode ?: 429
            ),
            'server_error' => throw new \RuntimeException(
                $this->failureMessage ?: 'Internal server error',
                $this->failureCode ?: 500
            ),
            'bad_gateway' => throw new \RuntimeException(
                $this->failureMessage ?: 'Bad gateway',
                $this->failureCode ?: 502
            ),
            'service_unavailable' => throw new \RuntimeException(
                $this->failureMessage ?: 'Service unavailable',
                $this->failureCode ?: 503
            ),
            'gateway_timeout' => throw new \RuntimeException(
                $this->failureMessage ?: 'Gateway timeout',
                $this->failureCode ?: 504
            ),
            'invalid_response' => throw new \UnexpectedValueException(
                $this->failureMessage ?: 'Invalid JSON response',
                $this->failureCode
            ),
            'empty_response' => throw new \UnexpectedValueException(
                $this->failureMessage ?: 'Empty response body',
                $this->failureCode
            ),
            'malformed_json' => throw new \JsonException(
                $this->failureMessage ?: 'Syntax error in JSON'
            ),
            'type_error' => throw new \TypeError(
                $this->failureMessage ?: 'Unexpected type in response'
            ),
            'overflow' => throw new \OverflowException(
                $this->failureMessage ?: 'Response too large'
            ),
            'invalid_argument' => throw new \InvalidArgumentException(
                $this->failureMessage ?: 'Invalid configuration parameter'
            ),
            'logic_error' => throw new \LogicException(
                $this->failureMessage ?: 'Provider not properly configured'
            ),
            'out_of_range' => throw new \OutOfRangeException(
                $this->failureMessage ?: 'Response status code out of expected range'
            ),
            'domain_exception' => throw new \DomainException(
                $this->failureMessage ?: 'Invalid API endpoint domain'
            ),
            'length_exception' => throw new \LengthException(
                $this->failureMessage ?: 'API key length invalid'
            ),
            default => throw new \RuntimeException(
                $this->failureMessage ?: "Unknown failure: {$this->failureType}",
                $this->failureCode
            ),
        };
    }
}

class AIProviderTestConnectionPropertyTest extends TestCase
{
    /**
     * **Validates: Requirements 4.5**
     *
     * Property 3: testConnection never throws on failure.
     * For any simulated connection failure condition, a conforming provider's
     * `testConnection` method SHALL return `false` without throwing an exception.
     */
    #[Test]
    #[DataProvider('failureConditionProvider')]
    public function test_connection_returns_false_without_throwing_on_failure(
        string $failureType,
        string $failureMessage,
        int $failureCode
    ): void {
        $provider = new FailureSimulatingProvider($failureType, $failureMessage, $failureCode);

        // testConnection must not throw — capture any exception as a test failure.
        $threwException = false;
        $result = null;

        try {
            $result = $provider->testConnection();
        } catch (\Throwable $e) {
            $threwException = true;
        }

        $this->assertFalse(
            $threwException,
            "testConnection must not throw an exception for failure type '{$failureType}'"
        );

        $this->assertFalse(
            $result,
            "testConnection must return false for failure type '{$failureType}'"
        );
    }

    /**
     * Data provider generating 100+ random failure conditions.
     *
     * Each entry represents a unique failure scenario that the provider
     * must handle gracefully by returning false without throwing.
     *
     * @return array<string, array{string, string, int}>
     */
    public static function failureConditionProvider(): array
    {
        $cases = [];

        // Core failure types to exercise.
        $failureTypes = [
            'network_timeout',
            'dns_resolution',
            'connection_refused',
            'ssl_error',
            'auth_failure',
            'rate_limited',
            'server_error',
            'bad_gateway',
            'service_unavailable',
            'gateway_timeout',
            'invalid_response',
            'empty_response',
            'malformed_json',
            'type_error',
            'overflow',
            'invalid_argument',
            'logic_error',
            'out_of_range',
            'domain_exception',
            'length_exception',
        ];

        mt_srand(777);

        // Generate 100+ cases by cycling through failure types with random messages and codes.
        for ($i = 0; $i < 110; $i++) {
            $failureType = $failureTypes[$i % count($failureTypes)];
            $message = self::generateRandomErrorMessage($i);
            $code = mt_rand(0, 599);

            $cases["failure_{$i}_{$failureType}"] = [$failureType, $message, $code];
        }

        // Additional edge-case scenarios.
        $edgeCases = [
            'empty_message' => ['network_timeout', '', 0],
            'max_code' => ['server_error', 'Maximum error code', PHP_INT_MAX],
            'unicode_message' => ['auth_failure', 'Ünauthorized: Ïnvalid tökën', 401],
            'very_long_message' => ['invalid_response', str_repeat('Error detail. ', 100), 500],
            'null_byte_message' => ['malformed_json', "Null\x00byte in response", 0],
            'newline_message' => ['connection_refused', "Line1\nLine2\nLine3", 7],
            'special_chars_message' => ['ssl_error', 'Error: <html>&amp;"quotes"', 60],
            'negative_code' => ['gateway_timeout', 'Negative code scenario', -1],
            'unknown_failure_type' => ['completely_unknown_error', 'Unexpected failure mode', 999],
            'zero_code_auth' => ['auth_failure', 'No code provided', 0],
        ];

        return array_merge($cases, $edgeCases);
    }

    /**
     * Generate a random error message string for test variety.
     */
    private static function generateRandomErrorMessage(int $index): string
    {
        $templates = [
            'Connection to %s failed after %d ms',
            'API returned HTTP %d: %s',
            'Failed to resolve endpoint: %s',
            'SSL handshake failed with %s',
            'Authentication rejected: %s (code %d)',
            'Rate limit hit: retry after %d seconds',
            'Server responded with %d error',
            'Unexpected response format from %s',
            'Timeout waiting for %s after %d ms',
            'Network unreachable: %s',
            'Invalid credentials for provider %s',
            'Service %s returned empty body',
            'JSON parse error at position %d',
            'Response exceeded %d byte limit',
            'Configuration error: missing %s',
        ];

        $hosts = ['api.openai.com', 'openrouter.ai', 'localhost:11434', 'api.deepseek.com', '127.0.0.1:8080'];
        $template = $templates[$index % count($templates)];
        $host = $hosts[mt_rand(0, count($hosts) - 1)];
        $number = mt_rand(1, 30000);

        return sprintf($template, $host, $number);
    }
}
