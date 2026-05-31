<?php
declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Includes\Interfaces;

/**
 * Interface AIProviderInterface
 *
 * Defines the contract for AI service providers used by the FAQ generator.
 * Each provider (OpenAI, OpenRouter, Ollama, etc.) implements this interface
 * to enable pluggable AI service integration.
 *
 * @package WPBits\AiFaqGenerator\Includes\Interfaces
 */
interface AIProviderInterface
{
    /**
     * Generate FAQ items from the given prompt.
     *
     * Sends the prompt to the AI service and returns structured FAQ data.
     * Each FAQ item is an associative array with 'question' and 'answer' keys.
     *
     * @param string $prompt The instruction/context prompt for FAQ generation.
     * @return array<int, array{question: string, answer: string}> List of FAQ items.
     * @throws \RuntimeException When the AI service returns an error or invalid response.
     */
    public function generateFaqs(string $prompt): array;

    /**
     * Test the connection to the AI service.
     *
     * Verifies that the configured API endpoint is reachable and authentication
     * credentials are valid. Does not throw exceptions on failure.
     *
     * @return bool True if connection is successful, false otherwise.
     */
    public function testConnection(): bool;
}
