<?php
declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Includes;

use WPBits\AiFaqGenerator\Includes\Interfaces\AIProviderInterface;
use WPBits\AiFaqGenerator\Includes\Services\Faq_Parser;

/**
 * OpenAI-compatible API client.
 *
 * Communicates with any OpenAI-compatible chat completions endpoint
 * (OpenAI, OpenRouter, Ollama, DeepSeek, LocalAI, LM Studio).
 * The configurable base_url differentiates providers.
 *
 * @package WPBits\AiFaqGenerator\Includes
 */
class OpenAIClient implements AIProviderInterface
{
    private string $api_key;
    private string $model;
    private float $temperature;
    private string $base_url;

    /**
     * Read configuration from afg_settings option merged with defaults.
     */
    public function __construct()
    {
        $defaults = [
            'api_key'     => '',
            'model'       => 'gpt-4o',
            'temperature' => 0.7,
            'base_url'    => 'https://api.openai.com',
        ];

        $stored = get_option('afg_settings', []);
        $settings = array_merge($defaults, is_array($stored) ? $stored : []);

        $this->api_key     = (string) $settings['api_key'];
        $this->model       = (string) $settings['model'];
        $this->temperature = (float) $settings['temperature'];
        $this->base_url    = rtrim((string) $settings['base_url'], '/');
    }

    /**
     * Generate FAQ items from the given prompt.
     *
     * @param string $prompt The instruction/context prompt for FAQ generation.
     * @return array<int, array{question: string, answer: string}> List of FAQ items.
     * @throws \RuntimeException When the AI service returns an error or invalid response.
     */
    public function generateFaqs(string $prompt): array
    {
        $body     = $this->buildRequestBody($prompt);
        $response = $this->sendRequest($body);
        $content  = $this->parseResponse($response);

        $parser = new Faq_Parser();
        return $parser->parse($content);
    }

    /**
     * Test the connection to the AI service.
     *
     * Verifies that the configured API endpoint is reachable and authentication
     * credentials are valid. Does not throw exceptions on failure.
     *
     * @return bool True if connection is successful, false otherwise.
     */
    public function testConnection(): bool
    {
        try {
            $body = $this->buildRequestBody('Hi');
            $body['max_tokens'] = 1;
            $this->sendRequest($body);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Construct the full endpoint URL for chat completions.
     *
     * @return string The base URL appended with /v1/chat/completions.
     */
    private function getEndpointUrl(): string
    {
        return $this->base_url . '/v1/chat/completions';
    }

    /**
     * Build HTTP headers for the API request.
     *
     * @return array<string, string> Headers with Authorization and Content-Type.
     */
    private function buildHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type'  => 'application/json',
        ];
    }

    /**
     * Build the JSON request body for chat completions.
     *
     * @param string $prompt The user prompt to include in the messages array.
     * @return array The request body with model, messages, and temperature.
     */
    private function buildRequestBody(string $prompt): array
    {
        return [
            'model'       => $this->model,
            'messages'    => [
                [
                    'role'    => 'user',
                    'content' => $prompt,
                ],
            ],
            'temperature' => $this->temperature,
        ];
    }

    /**
     * Send the request via wp_remote_post and validate the raw response.
     *
     * @param array $body The request body to JSON-encode and send.
     * @return array The decoded JSON response.
     * @throws \RuntimeException On WP_Error, non-2xx status, or invalid JSON.
     */
    private function sendRequest(array $body): array
    {
        $encoded_body = wp_json_encode($body);

        if ($encoded_body === false) {
            throw new \RuntimeException('Failed to encode request body as JSON.');
        }

        $response = wp_remote_post($this->getEndpointUrl(), [
            'headers' => $this->buildHeaders(),
            'body'    => $encoded_body,
            'timeout' => 30,
        ]);

        if ($response instanceof \WP_Error) {
            throw new \RuntimeException($response->get_error_message());
        }

        $status_code = (int) wp_remote_retrieve_response_code($response);
        if ($status_code < 200 || $status_code >= 300) {
            $body_content = wp_remote_retrieve_body($response);
            $decoded = json_decode($body_content, true);
            $error_message = $decoded['error']['message'] ?? $body_content;
            throw new \RuntimeException("HTTP {$status_code}: {$error_message}");
        }

        $body_content = wp_remote_retrieve_body($response);
        $decoded = json_decode($body_content, true);

        if (!is_array($decoded)) {
            throw new \RuntimeException('Response could not be parsed as JSON');
        }

        return $decoded;
    }

    /**
     * Extract assistant message content from the decoded response.
     *
     * @param array $response The decoded API response.
     * @return string The assistant message content.
     * @throws \RuntimeException If choices[0].message.content path is missing.
     */
    private function parseResponse(array $response): string
    {
        if (!isset($response['choices'][0]['message']['content'])) {
            throw new \RuntimeException('Unexpected response structure: missing choices[0].message.content');
        }

        return (string) $response['choices'][0]['message']['content'];
    }
}
