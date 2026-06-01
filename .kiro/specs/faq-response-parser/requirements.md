# Requirements Document

## Introduction

The FAQ Response Parser is a dedicated service class responsible for parsing raw AI response content (JSON strings) into validated FAQ item arrays. It extracts the parsing logic currently embedded in the `OpenAIClient::parseFaqItems()` method into a standalone, reusable service at `includes/services/class-faq-parser.php`. The parser receives the raw string content from `choices[0].message.content`, validates it as a JSON array of question/answer objects, and returns a clean array of FAQ items or an empty array on failure.

## Glossary

- **FAQ_Parser**: The service class (`WPBits\AiFaqGenerator\Includes\Services\Faq_Parser`) responsible for parsing and validating AI response content into FAQ item arrays.
- **FAQ_Item**: An associative array with exactly two keys: `question` (non-empty string) and `answer` (non-empty string).
- **Raw_Content**: The string value extracted from the AI API response at `choices[0].message.content`, expected to contain a JSON array of FAQ items.
- **Valid_JSON_Array**: A string that, when decoded, produces a PHP indexed array (list).
- **Markdown_Fence**: A code block wrapper (e.g., ` ```json ... ``` `) that some AI models include around JSON output.

## Requirements

### Requirement 1: Parse Valid JSON into FAQ Array

**User Story:** As a plugin developer, I want the parser to decode valid JSON arrays into structured FAQ items, so that downstream components receive clean, typed data.

#### Acceptance Criteria

1. WHEN a Raw_Content string containing a Valid_JSON_Array of FAQ_Item objects is provided, THE FAQ_Parser SHALL return a zero-based indexed array of FAQ_Item associative arrays, each containing only the `question` and `answer` keys, discarding any additional keys present in the JSON objects.
2. WHEN a Raw_Content string contains a Valid_JSON_Array with two or more FAQ_Item objects, THE FAQ_Parser SHALL preserve the order of items as they appear in the JSON and return an array whose count equals the number of valid FAQ_Item objects in the input.
3. WHEN a Raw_Content string contains a Valid_JSON_Array with a single FAQ_Item object, THE FAQ_Parser SHALL return an array containing exactly one FAQ_Item.

### Requirement 2: Handle Invalid JSON Gracefully

**User Story:** As a plugin developer, I want the parser to return an empty array when the AI response contains invalid JSON, so that the plugin does not crash on malformed responses.

#### Acceptance Criteria

1. WHEN a Raw_Content string is not valid JSON after any Markdown_Fence stripping has been applied, THE FAQ_Parser SHALL return an empty array.
2. WHEN a Raw_Content string is empty or contains only whitespace characters, THE FAQ_Parser SHALL return an empty array.
3. WHEN a Raw_Content string decodes to a non-array type (e.g., a JSON object, string, number, boolean, or null), THE FAQ_Parser SHALL return an empty array.
4. WHEN a Raw_Content string contains truncated or incomplete JSON (e.g., due to token limit cutoff), THE FAQ_Parser SHALL return an empty array.

### Requirement 3: Handle Missing or Invalid Fields

**User Story:** As a plugin developer, I want the parser to skip items with missing or empty fields, so that only complete FAQ items are returned to the caller.

#### Acceptance Criteria

1. WHEN a decoded array item does not contain a `question` key, THE FAQ_Parser SHALL exclude that item from the result.
2. WHEN a decoded array item does not contain an `answer` key, THE FAQ_Parser SHALL exclude that item from the result.
3. WHEN a decoded array item has a `question` value that is not a string, or is a string that is empty or contains only whitespace after trimming, THE FAQ_Parser SHALL exclude that item from the result.
4. WHEN a decoded array item has an `answer` value that is not a string, or is a string that is empty or contains only whitespace after trimming, THE FAQ_Parser SHALL exclude that item from the result.
5. WHEN a decoded array contains a mix of valid and invalid items, THE FAQ_Parser SHALL return only the valid FAQ_Item entries in their original order.
6. WHEN a decoded array item contains keys beyond `question` and `answer`, THE FAQ_Parser SHALL include that item in the result but return only the `question` and `answer` keys in the output FAQ_Item.
7. WHEN a decoded array entry is not an associative array (e.g., a scalar value, a numeric string, or a nested indexed array), THE FAQ_Parser SHALL exclude that entry from the result.

### Requirement 4: Trim Whitespace from FAQ Values

**User Story:** As a plugin developer, I want the parser to trim leading and trailing whitespace from question and answer values, so that stored FAQ data is clean and consistent.

#### Acceptance Criteria

1. WHEN a valid FAQ_Item has leading or trailing whitespace characters (spaces, tabs, newlines, or carriage returns) in the `question` value, THE FAQ_Parser SHALL remove all leading and trailing whitespace characters from the `question` value before returning the item.
2. WHEN a valid FAQ_Item has leading or trailing whitespace characters (spaces, tabs, newlines, or carriage returns) in the `answer` value, THE FAQ_Parser SHALL remove all leading and trailing whitespace characters from the `answer` value before returning the item.
3. WHEN a valid FAQ_Item contains internal whitespace (whitespace between non-whitespace characters) in the `question` or `answer` value, THE FAQ_Parser SHALL preserve the internal whitespace unchanged.

### Requirement 5: Strip Markdown Code Fences from Raw Content

**User Story:** As a plugin developer, I want the parser to handle AI responses wrapped in markdown code fences, so that parsing succeeds even when the AI model adds formatting around the JSON.

#### Acceptance Criteria

1. WHEN a Raw_Content string is wrapped in a Markdown_Fence with a language identifier (e.g., ` ```json\n[...]\n``` `), THE FAQ_Parser SHALL strip the opening fence line (including the language identifier) and the closing fence marker before attempting JSON decoding.
2. WHEN a Raw_Content string is wrapped in a plain Markdown_Fence without a language identifier (e.g., ` ```\n[...]\n``` `), THE FAQ_Parser SHALL strip the opening and closing fence markers before attempting JSON decoding.
3. WHEN a Raw_Content string has leading or trailing whitespace surrounding a Markdown_Fence, THE FAQ_Parser SHALL strip the whitespace along with the fence markers before attempting JSON decoding.
4. WHEN a Raw_Content string contains a malformed or partial Markdown_Fence (e.g., only an opening fence without a closing fence), THE FAQ_Parser SHALL attempt JSON decoding on the content as-is without stripping.

### Requirement 6: Return Type Contract

**User Story:** As a plugin developer, I want the parser to always return a consistently typed result, so that callers do not need null checks or exception handling.

#### Acceptance Criteria

1. THE FAQ_Parser SHALL always return a value of type `array<int, array{question: string, answer: string}>` with zero-based sequential integer keys.
2. THE FAQ_Parser SHALL never throw an exception during parsing or validation; IF an unexpected internal error occurs (e.g., a caught exception from JSON decoding or regex operations), THEN THE FAQ_Parser SHALL return an empty array.
3. WHEN all items in the decoded array are invalid, THE FAQ_Parser SHALL return an empty array.
4. WHEN valid items remain after filtering invalid entries, THE FAQ_Parser SHALL re-index the result array starting from key 0 with no gaps in the integer sequence.

### Requirement 7: Parse Method Signature

**User Story:** As a plugin developer, I want a single public entry point with a clear signature, so that integration with the OpenAIClient is straightforward.

#### Acceptance Criteria

1. THE FAQ_Parser SHALL expose a public method named `parse` that accepts a single `string` parameter and returns `array<int, array{question: string, answer: string}>`.
2. THE FAQ_Parser SHALL be instantiable without constructor arguments.
