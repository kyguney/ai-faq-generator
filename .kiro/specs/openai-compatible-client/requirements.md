# Requirements Document

## Introduction

This document specifies the requirements for an OpenAI-compatible HTTP client within the AI FAQ Generator WordPress plugin. The client implements the `AIProviderInterface` and communicates with any OpenAI-compatible chat completions endpoint (OpenAI, OpenRouter, Ollama, DeepSeek, LocalAI, LM Studio). A single class handles all providers — the configurable base URL is what differentiates them.

## Glossary

- **OpenAI_Client**: The concrete PHP class (`OpenAIClient`) that implements `AIProviderInterface` and communicates with OpenAI-compatible API endpoints using the WordPress HTTP API.
- **Settings**: The existing `WPBits\AiFaqGenerator\Admin\Settings` class responsible for storing and sanitizing plugin configuration.
- **Loader**: The existing `WPBits\AiFaqGenerator\Includes\Loader` class responsible for SPL autoloading of plugin classes.
- **Chat_Completions_Endpoint**: The `/v1/chat/completions` API endpoint used by all OpenAI-compatible providers.
- **FAQ_Item**: An associative array with keys `question` (string) and `answer` (string) representing a single FAQ entry.
- **WP_HTTP_API**: The WordPress HTTP API functions (`wp_remote_post`, `wp_remote_retrieve_response_code`, `wp_remote_retrieve_body`) used for making HTTP requests.

## Requirements

### Requirement 1: HTTP Request Construction

**User Story:** As a plugin developer, I want the client to construct valid OpenAI chat completions requests, so that any OpenAI-compatible provider can process them.

#### Acceptance Criteria

1. WHEN the OpenAI_Client sends a request, THE OpenAI_Client SHALL use `wp_remote_post` to send an HTTP POST request to the Chat_Completions_Endpoint at the configured base URL appended with `/v1/chat/completions`.
2. WHEN the OpenAI_Client constructs a request body, THE OpenAI_Client SHALL include the `model`, `messages`, and `temperature` fields in the JSON-encoded request body.
3. WHEN the OpenAI_Client constructs the messages array, THE OpenAI_Client SHALL include a single message with `role` set to `user` and `content` set to the provided prompt string.
4. WHEN the OpenAI_Client sends a request, THE OpenAI_Client SHALL include an `Authorization` header with the value `Bearer {api_key}` and a `Content-Type` header with the value `application/json`.
5. WHEN the OpenAI_Client sends a request, THE OpenAI_Client SHALL set the request timeout to 30 seconds.

### Requirement 2: Response Parsing

**User Story:** As a plugin developer, I want the client to parse chat completions responses into structured FAQ data, so that the plugin can display generated FAQs.

#### Acceptance Criteria

1. WHEN the API returns a successful response, THE OpenAI_Client SHALL extract the assistant message content from `choices[0].message.content` in the response body.
2. WHEN the assistant message content contains valid JSON representing an array of FAQ_Items, THE OpenAI_Client SHALL decode it and return an array where each element contains both a `question` key and an `answer` key with non-empty string values.
3. WHEN the OpenAI_Client parses the response, THE OpenAI_Client SHALL return the FAQ items as a numerically indexed array matching the `array<int, array{question: string, answer: string}>` type signature.

### Requirement 3: Error Handling

**User Story:** As a plugin developer, I want the client to handle errors with meaningful messages, so that failures can be diagnosed and reported to the user.

#### Acceptance Criteria

1. IF `wp_remote_post` returns a `WP_Error` object, THEN THE OpenAI_Client SHALL throw a `RuntimeException` with the WP_Error message describing the network failure.
2. IF the HTTP response status code is not in the 2xx range, THEN THE OpenAI_Client SHALL throw a `RuntimeException` containing the HTTP status code and the error message from the response body.
3. IF the response body is not valid JSON, THEN THE OpenAI_Client SHALL throw a `RuntimeException` indicating that the response could not be parsed.
4. IF the decoded response does not contain the expected `choices[0].message.content` path, THEN THE OpenAI_Client SHALL throw a `RuntimeException` indicating an unexpected response structure.
5. IF the assistant message content cannot be decoded into a valid array of FAQ_Items, THEN THE OpenAI_Client SHALL throw a `RuntimeException` indicating that the FAQ structure is invalid.

### Requirement 4: Connection Testing

**User Story:** As a plugin administrator, I want to test the API connection, so that I can verify my credentials and endpoint are configured correctly before generating FAQs.

#### Acceptance Criteria

1. WHEN `testConnection` is called, THE OpenAI_Client SHALL send a minimal chat completions request with `max_tokens` set to 1 to verify endpoint reachability and credential validity.
2. WHEN the minimal request receives a successful HTTP response, THE OpenAI_Client SHALL return `true`.
3. IF the minimal request fails for any reason (network error, authentication failure, server error), THEN THE OpenAI_Client SHALL return `false` without throwing an exception.

### Requirement 5: Configuration

**User Story:** As a plugin administrator, I want to configure the base URL for my AI provider, so that I can use any OpenAI-compatible service.

#### Acceptance Criteria

1. THE Settings SHALL include a `base_url` field in the defaults with a default value of `https://api.openai.com`.
2. WHEN the Settings sanitizes the `base_url` input, THE Settings SHALL validate that the value is a valid URL using `esc_url_raw` and reject empty or invalid URLs by retaining the previous stored value.
3. WHEN the OpenAI_Client is instantiated, THE OpenAI_Client SHALL read `api_key`, `model`, `temperature`, and `base_url` from the `afg_settings` option merged with defaults.
4. WHEN the `base_url` contains a trailing slash, THE OpenAI_Client SHALL remove it before appending the endpoint path.

### Requirement 6: Autoloader Registration

**User Story:** As a plugin developer, I want the new client class to be autoloaded, so that it integrates seamlessly with the existing plugin architecture.

#### Acceptance Criteria

1. THE Loader SHALL include the OpenAI_Client class in its class map with the fully qualified class name `WPBits\AiFaqGenerator\Includes\OpenAIClient` mapped to the file path `includes/class-openai-client.php`.
