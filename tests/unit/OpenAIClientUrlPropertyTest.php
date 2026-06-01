<?php
/**
 * Property-based test for OpenAIClient URL construction.
 *
 * Feature: openai-compatible-client, Property 5: URL construction
 *
 * Property 5: URL construction
 * Validates: Requirements 1.1, 5.4
 *
 * For any base_url string (with or without a trailing slash), the endpoint URL
 * used in requests SHALL equal the base_url with trailing slashes removed,
 * followed by /v1/chat/completions.
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WPBits\AiFaqGenerator\Includes\OpenAIClient;

class OpenAIClientUrlPropertyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        global $afg_test_options, $afg_test_wp_remote_post_args, $afg_test_wp_remote_post_return;
        $afg_test_options = [];
        $afg_test_wp_remote_post_args = null;
        $afg_test_wp_remote_post_return = [];
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
     * **Validates: Requirements 1.1, 5.4**
     *
     * Property 5: URL construction.
     * For any base_url string (with or without a trailing slash), the endpoint URL
     * used in requests SHALL equal rtrim(base_url, '/') . '/v1/chat/completions'.
     */
    #[Test]
    #[DataProvider('baseUrlProvider')]
    public function endpoint_url_is_correctly_constructed_from_base_url(string $baseUrl): void
    {
        global $afg_test_options, $afg_test_wp_remote_post_args, $afg_test_wp_remote_post_return;

        // Set up a valid response to avoid exceptions.
        $afg_test_wp_remote_post_return = [
            'response' => ['code' => 200],
            'body' => json_encode([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                ['question' => 'Q', 'answer' => 'A'],
                            ]),
                        ],
                    ],
                ],
            ]),
        ];

        // Configure the client with the test base_url.
        $afg_test_options['afg_settings'] = [
            'api_key'     => 'sk-test-key-12345',
            'model'       => 'gpt-4o',
            'temperature' => 0.7,
            'base_url'    => $baseUrl,
        ];

        // Instantiate the client and trigger a request.
        $client = new OpenAIClient();
        $client->generateFaqs('test');

        // Verify wp_remote_post was called.
        $this->assertNotNull(
            $afg_test_wp_remote_post_args,
            'wp_remote_post must be called'
        );

        // Compute the expected URL.
        $expectedUrl = rtrim($baseUrl, '/') . '/v1/chat/completions';

        // Verify the URL matches the expected construction.
        $this->assertSame(
            $expectedUrl,
            $afg_test_wp_remote_post_args['url'],
            sprintf(
                'Endpoint URL must be rtrim(base_url, "/") . "/v1/chat/completions". base_url="%s"',
                $baseUrl
            )
        );
    }

    /**
     * Data provider generating 120+ base_url variants.
     *
     * Covers:
     * - With and without trailing slash
     * - Various domains (localhost, custom domains, IP addresses)
     * - Various ports (8080, 11434, 443)
     * - Various path prefixes (/api, /v1/proxy)
     *
     * @return array<string, array{string}>
     */
    public static function baseUrlProvider(): array
    {
        $cases = [];

        // ─── Standard domains without trailing slash ─────────────────────
        $domains = [
            'https://api.openai.com',
            'https://openrouter.ai',
            'https://api.deepseek.com',
            'https://api.anthropic.com',
            'https://api.mistral.ai',
            'https://generativelanguage.googleapis.com',
            'https://api.together.xyz',
            'https://api.fireworks.ai',
            'https://api.groq.com',
            'https://api.perplexity.ai',
        ];

        foreach ($domains as $i => $domain) {
            $cases["standard_domain_{$i}"] = [$domain];
        }

        // ─── Standard domains with trailing slash ────────────────────────
        foreach ($domains as $i => $domain) {
            $cases["standard_domain_trailing_slash_{$i}"] = [$domain . '/'];
        }

        // ─── Localhost variants ──────────────────────────────────────────
        $localhostVariants = [
            'http://localhost',
            'http://localhost/',
            'http://localhost:8080',
            'http://localhost:8080/',
            'http://localhost:11434',
            'http://localhost:11434/',
            'http://localhost:3000',
            'http://localhost:3000/',
            'http://localhost:5000',
            'http://localhost:5000/',
            'http://localhost:8000',
            'http://localhost:8000/',
            'http://localhost:9090',
            'http://localhost:9090/',
            'https://localhost',
            'https://localhost/',
            'https://localhost:443',
            'https://localhost:443/',
            'https://localhost:8443',
            'https://localhost:8443/',
        ];

        foreach ($localhostVariants as $i => $url) {
            $cases["localhost_{$i}"] = [$url];
        }

        // ─── IP address variants ─────────────────────────────────────────
        $ipVariants = [
            'http://127.0.0.1',
            'http://127.0.0.1/',
            'http://127.0.0.1:8080',
            'http://127.0.0.1:8080/',
            'http://127.0.0.1:11434',
            'http://127.0.0.1:11434/',
            'http://192.168.1.100',
            'http://192.168.1.100/',
            'http://192.168.1.100:8080',
            'http://192.168.1.100:8080/',
            'http://10.0.0.1',
            'http://10.0.0.1/',
            'http://10.0.0.1:3000',
            'http://10.0.0.1:3000/',
            'https://172.16.0.50:443',
            'https://172.16.0.50:443/',
            'http://192.168.0.1:11434',
            'http://192.168.0.1:11434/',
            'http://10.10.10.10:9090',
            'http://10.10.10.10:9090/',
        ];

        foreach ($ipVariants as $i => $url) {
            $cases["ip_address_{$i}"] = [$url];
        }

        // ─── Custom domains ──────────────────────────────────────────────
        $customDomains = [
            'https://my-ai-server.example.com',
            'https://my-ai-server.example.com/',
            'https://ai.internal.company.org',
            'https://ai.internal.company.org/',
            'https://llm-proxy.myapp.io',
            'https://llm-proxy.myapp.io/',
            'http://ollama.local',
            'http://ollama.local/',
            'https://openai-proxy.vercel.app',
            'https://openai-proxy.vercel.app/',
            'https://gateway.ai.cloudflare.com',
            'https://gateway.ai.cloudflare.com/',
            'https://models.inference.ai.azure.com',
            'https://models.inference.ai.azure.com/',
        ];

        foreach ($customDomains as $i => $url) {
            $cases["custom_domain_{$i}"] = [$url];
        }

        // ─── Path prefix variants ────────────────────────────────────────
        $pathPrefixes = [
            'https://api.example.com/api',
            'https://api.example.com/api/',
            'https://api.example.com/v1/proxy',
            'https://api.example.com/v1/proxy/',
            'http://localhost:8080/api',
            'http://localhost:8080/api/',
            'http://localhost:11434/api',
            'http://localhost:11434/api/',
            'https://my-server.com/openai',
            'https://my-server.com/openai/',
            'https://gateway.example.com/ai/openai',
            'https://gateway.example.com/ai/openai/',
            'http://192.168.1.50:3000/proxy',
            'http://192.168.1.50:3000/proxy/',
            'https://cloud.example.org/services/llm',
            'https://cloud.example.org/services/llm/',
            'https://api.example.com/v2/ai/completions-proxy',
            'https://api.example.com/v2/ai/completions-proxy/',
            'http://localhost:4000/litellm',
            'http://localhost:4000/litellm/',
        ];

        foreach ($pathPrefixes as $i => $url) {
            $cases["path_prefix_{$i}"] = [$url];
        }

        // ─── Various port numbers ────────────────────────────────────────
        $ports = [80, 443, 1234, 3000, 4000, 5000, 8000, 8080, 8443, 9090, 11434, 27017, 50051];

        foreach ($ports as $i => $port) {
            $cases["port_no_slash_{$i}"] = ["http://localhost:{$port}"];
            $cases["port_with_slash_{$i}"] = ["http://localhost:{$port}/"];
        }

        // ─── Multiple trailing slashes ───────────────────────────────────
        $multiSlash = [
            'https://api.openai.com//',
            'https://api.openai.com///',
            'http://localhost:8080//',
            'http://localhost:11434///',
        ];

        foreach ($multiSlash as $i => $url) {
            $cases["multi_slash_{$i}"] = [$url];
        }

        return $cases;
    }
}
