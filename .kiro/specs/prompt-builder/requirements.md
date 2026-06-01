# Requirements Document

## Introduction

The Prompt Builder is a service class responsible for constructing the prompt string sent to the AI provider for FAQ generation. It takes post data (title and content) along with plugin settings (FAQ count) and produces a well-structured prompt that instructs the AI to return a JSON array of question/answer pairs. The class sanitizes and truncates input content to ensure prompts remain within reasonable size limits.

## Glossary

- **Prompt_Builder**: The service class (`includes/services/class-prompt-builder.php`) that constructs prompt strings from post data and settings for FAQ generation.
- **Post_Title**: The WordPress post or page title provided as input context for FAQ generation.
- **Post_Content**: The WordPress post or page body content provided as input context for FAQ generation.
- **FAQ_Count**: An integer setting (1–20) stored in `afg_settings` that specifies how many FAQ items the AI should generate.
- **Prompt_String**: The final text output produced by the Prompt_Builder, sent to the AI provider via the AIProviderInterface.
- **Content_Limit**: The maximum character length (2000 characters) allowed for post content within the prompt.

## Requirements

### Requirement 1: Build Prompt from Post Data

**User Story:** As a plugin developer, I want the Prompt_Builder to construct a prompt string from post title and content, so that the AI provider receives proper context for generating relevant FAQs.

#### Acceptance Criteria

1. WHEN a Post_Title and Post_Content are provided, THE Prompt_Builder SHALL return a Prompt_String that includes the Post_Title verbatim and the processed Post_Content.
2. WHEN a Post_Title and Post_Content are provided, THE Prompt_Builder SHALL strip all HTML tags from the Post_Content using wp_strip_all_tags before including the content in the Prompt_String.
3. WHEN a Post_Title is provided, THE Prompt_Builder SHALL strip all HTML tags from the Post_Title using wp_strip_all_tags before including the title in the Prompt_String.
4. WHEN the stripped Post_Content exceeds the Content_Limit of 2000 characters, THE Prompt_Builder SHALL truncate the content to exactly 2000 characters by cutting at the 2000th character position.
5. WHEN the stripped Post_Content is within the Content_Limit, THE Prompt_Builder SHALL include the full stripped content in the Prompt_String without truncation.

### Requirement 2: Instruct AI to Return JSON Array

**User Story:** As a plugin developer, I want the prompt to instruct the AI to return a JSON array of FAQ items, so that the response can be reliably parsed by the OpenAIClient.

#### Acceptance Criteria

1. THE Prompt_Builder SHALL include an instruction in the Prompt_String that directs the AI to return a JSON array as the top-level structure.
2. THE Prompt_Builder SHALL include an instruction in the Prompt_String that specifies each array element must be an object containing a "question" key and an "answer" key, both with non-empty string values.
3. THE Prompt_Builder SHALL include an instruction in the Prompt_String that directs the AI to return only the raw JSON array without surrounding prose, markdown code fences, or any other formatting.

### Requirement 3: Specify FAQ Count from Settings

**User Story:** As a plugin developer, I want the prompt to specify the number of FAQs to generate based on plugin settings, so that the AI produces the correct quantity of FAQ items.

#### Acceptance Criteria

1. WHEN a FAQ_Count integer value between 1 and 20 (inclusive) is provided, THE Prompt_Builder SHALL include that FAQ_Count number in the Prompt_String as the requested quantity of FAQ items to generate.
2. WHEN no FAQ_Count value is provided (null or missing parameter), THE Prompt_Builder SHALL use the default FAQ_Count of 5 in the Prompt_String.
3. IF the FAQ_Count value is less than 1 or greater than 20, THEN THE Prompt_Builder SHALL clamp the value to the nearest boundary (1 or 20) before including it in the Prompt_String.

### Requirement 4: Handle Edge Case Inputs

**User Story:** As a plugin developer, I want the Prompt_Builder to handle edge case inputs gracefully, so that FAQ generation does not fail due to unexpected post data.

#### Acceptance Criteria

1. WHEN the Post_Title is an empty string, THE Prompt_Builder SHALL produce a Prompt_String that contains the JSON format instructions, the FAQ_Count, and the Post_Content as context, without including the Post_Title.
2. WHEN the Post_Content is an empty string, THE Prompt_Builder SHALL produce a Prompt_String that contains the JSON format instructions, the FAQ_Count, and the Post_Title as context, without including the Post_Content.
3. WHEN both Post_Title and Post_Content are empty strings, THE Prompt_Builder SHALL produce a Prompt_String that contains the JSON format instructions and FAQ_Count without any post context.
4. WHEN the Post_Content contains only HTML tags with no text content, THE Prompt_Builder SHALL produce a Prompt_String using only the Post_Title as context, following the same behavior as when Post_Content is an empty string.
5. WHEN the Post_Title or Post_Content contains only whitespace characters, THE Prompt_Builder SHALL treat that input as an empty string.

### Requirement 5: Prompt Output Consistency

**User Story:** As a plugin developer, I want the Prompt_Builder to produce deterministic output, so that the same inputs always generate the same prompt string.

#### Acceptance Criteria

1. WHEN the same Post_Title, Post_Content, and FAQ_Count are provided, THE Prompt_Builder SHALL return a byte-for-byte identical Prompt_String on every invocation regardless of call order or number of prior invocations.
2. THE Prompt_Builder SHALL return the Prompt_String as a non-empty string type (minimum 1 character) for all valid inputs, where valid inputs are defined as: Post_Title is a string (including empty), Post_Content is a string (including empty), and FAQ_Count is an integer between 1 and 20 inclusive or is absent (triggering the default of 5).
3. IF the FAQ_Count is provided but is not an integer between 1 and 20 inclusive, THEN THE Prompt_Builder SHALL use the default FAQ_Count of 5 in the Prompt_String.
4. THE Prompt_Builder SHALL NOT include any non-deterministic elements such as timestamps, random identifiers, or invocation counters in the Prompt_String.
