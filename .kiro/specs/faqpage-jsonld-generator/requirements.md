# Requirements Document

## Introduction

This feature generates FAQPage JSON-LD structured data from FAQ content stored in post meta and outputs it in the document `<head>` for SEO rich results eligibility. The generator hooks into the WordPress `wp_head` action and produces valid schema.org FAQPage markup for any post that contains generated FAQs.

## Glossary

- **JSON-LD_Generator**: The component responsible for building and outputting the FAQPage JSON-LD structured data script tag.
- **FAQ_Meta**: The `_aifaq_generated_faqs` post meta field storing a JSON-encoded array of FAQ objects, each with `question` and `answer` string properties.
- **FAQPage_Schema**: A schema.org structured data object with `@type` "FAQPage" containing a `mainEntity` array of Question objects.
- **Script_Tag**: An HTML `<script type="application/ld+json">` element containing JSON-LD structured data.
- **Rich_Results**: Enhanced search engine result appearances enabled by valid structured data markup.

## Requirements

### Requirement 1: Output FAQPage JSON-LD on Posts with FAQ Meta

**User Story:** As a site owner, I want FAQPage structured data automatically output in the page head when a post has generated FAQs, so that search engines can display rich FAQ results for my content.

#### Acceptance Criteria

1. WHEN a singular post is rendered and FAQ_Meta contains one or more FAQ items (up to a maximum of 25), THE JSON-LD_Generator SHALL output a Script_Tag in the document head containing a valid FAQPage_Schema object.
2. IF a singular post is rendered and FAQ_Meta is absent, contains an empty JSON array, or contains a zero-length string, THEN THE JSON-LD_Generator SHALL output no Script_Tag.
3. IF a singular post is rendered and FAQ_Meta contains a value that cannot be decoded as a valid JSON array of FAQ objects, THEN THE JSON-LD_Generator SHALL output no Script_Tag.
4. WHILE a non-singular page is displayed (including but not limited to archive, search, home, 404, category, tag, and author pages), THE JSON-LD_Generator SHALL output no Script_Tag regardless of post meta content.

### Requirement 2: Schema Structure Compliance

**User Story:** As a site owner, I want the generated JSON-LD to follow the schema.org FAQPage specification exactly, so that Google's Rich Results Test validates it without errors.

#### Acceptance Criteria

1. THE JSON-LD_Generator SHALL include a `@context` property set to `"https://schema.org"` in the root object.
2. THE JSON-LD_Generator SHALL include a `@type` property set to `"FAQPage"` in the root object.
3. THE JSON-LD_Generator SHALL include a `mainEntity` property containing a JSON array of one or more Question objects.
4. WHEN producing a Question object, THE JSON-LD_Generator SHALL set `@type` to `"Question"` and `name` to the FAQ question text.
5. WHEN producing a Question object, THE JSON-LD_Generator SHALL include an `acceptedAnswer` object with `@type` set to `"Answer"` and `text` set to the FAQ answer text.
6. IF a FAQ item has an empty or whitespace-only question string or an empty or whitespace-only answer string, THEN THE JSON-LD_Generator SHALL exclude that FAQ item from the `mainEntity` array.

### Requirement 3: Complete FAQ Inclusion

**User Story:** As a site owner, I want all FAQs from a post included in the structured data, so that every question-answer pair is eligible for rich results.

#### Acceptance Criteria

1. THE JSON-LD_Generator SHALL include one Question object in the `mainEntity` array for each FAQ item in FAQ_Meta that contains a non-empty `question` string and a non-empty `answer` string.
2. THE JSON-LD_Generator SHALL preserve the order of FAQ items from FAQ_Meta in the `mainEntity` array.
3. THE JSON-LD_Generator SHALL produce a `mainEntity` array whose length equals the number of valid FAQ items in FAQ_Meta.
4. IF an FAQ item in FAQ_Meta is missing the `question` key, missing the `answer` key, or contains an empty string for either property, THEN THE JSON-LD_Generator SHALL skip that item and not include a corresponding Question object in the `mainEntity` array.

### Requirement 4: Safe JSON Encoding

**User Story:** As a developer, I want the JSON-LD output encoded safely to prevent XSS and encoding issues, so that the structured data does not introduce security vulnerabilities.

#### Acceptance Criteria

1. THE JSON-LD_Generator SHALL encode the FAQPage_Schema object using `wp_json_encode()` with the `JSON_UNESCAPED_UNICODE` flag.
2. WHEN FAQ text content contains any case-insensitive occurrence of `</script`, THE JSON-LD_Generator SHALL replace the sequence with an escaped form (e.g., `<\/script`) so that the browser does not interpret it as a closing script tag.
3. IF `wp_json_encode()` returns false, THEN THE JSON-LD_Generator SHALL output no Script_Tag and SHALL NOT output any partial or malformed content to the document head.

### Requirement 5: WordPress Hook Integration

**User Story:** As a developer, I want the JSON-LD output hooked into `wp_head` following WordPress conventions, so that the markup appears in the correct location and can be controlled by other plugins.

#### Acceptance Criteria

1. THE JSON-LD_Generator SHALL register its output callback on the `wp_head` action hook.
2. THE JSON-LD_Generator SHALL register the `wp_head` callback at priority 20.
3. THE JSON-LD_Generator SHALL read FAQ data from the current post's `_aifaq_generated_faqs` meta field using `get_post_meta()` with the third parameter set to `true` to retrieve a single value.
4. THE JSON-LD_Generator SHALL register the `wp_head` callback using a publicly accessible callable reference so that other plugins can remove it via `remove_action()`.

### Requirement 6: Handle Special Characters in FAQ Content

**User Story:** As a site owner, I want the structured data to correctly handle HTML entities, Unicode characters, and special characters in my FAQ content, so that the schema remains valid regardless of content.

#### Acceptance Criteria

1. WHEN FAQ text contains HTML entities (named, numeric, or hexadecimal), THE JSON-LD_Generator SHALL decode all HTML entities to their Unicode character equivalents in both question and answer text before encoding to JSON.
2. WHEN FAQ text contains double quotes, backslashes, or control characters (U+0000 through U+001F), THE JSON-LD_Generator SHALL produce output that passes JSON validation per RFC 8259 with all such characters correctly escaped.
3. WHEN FAQ answer text contains HTML markup, THE JSON-LD_Generator SHALL preserve the HTML markup in the `text` property as permitted by schema.org Answer type.
4. WHEN FAQ question text contains HTML markup, THE JSON-LD_Generator SHALL strip all HTML tags from the question text before setting the `name` property of the Question object.
