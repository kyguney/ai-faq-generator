/**
 * Property-based tests for PHP title tag validation logic (JS simulation).
 *
 * Feature: block-inspector-controls, Property 1: Title tag renders correct heading inside summary
 * Feature: block-inspector-controls, Property 2: Invalid title tag falls back to h3
 *
 * @package AiFaqGenerator
 */

import * as fc from 'fast-check';

/**
 * Validates: Requirements 2.5, 2.6, 7.1, 7.5
 *
 * Simulates the PHP render logic from render.php:
 * - get_validated_title_tag() returns one of 'h2', 'h3', 'h4' or falls back to 'h3'
 * - render_faq_accordion_block() wraps question text in <titleTag> inside <summary>
 */

const VALID_TITLE_TAGS = [ 'h2', 'h3', 'h4' ];

/**
 * Simulates PHP get_validated_title_tag().
 * Returns a validated tag or 'h3' fallback.
 */
function getValidatedTitleTag( attributes ) {
	const tag = attributes.titleTag ?? 'h3';
	return VALID_TITLE_TAGS.includes( tag ) ? tag : 'h3';
}

/**
 * Simulates the relevant part of PHP render_faq_accordion_block().
 * Renders a single FAQ item with the title tag inside summary.
 */
function renderFaqItem( question, answer, titleTag ) {
	const validatedTag = getValidatedTitleTag( { titleTag } );
	return (
		`<details class="faq-accordion-item">` +
		`<summary><${ validatedTag }>${ question }</${ validatedTag }></summary>` +
		`<div class="faq-accordion-content">${ answer }</div>` +
		`</details>`
	);
}

// --- Generators ---

const validTitleTagArb = fc.constantFrom( 'h2', 'h3', 'h4' );

const invalidTitleTagArb = fc.oneof(
	fc.string( { minLength: 0, maxLength: 50 } ).filter(
		( s ) => ! VALID_TITLE_TAGS.includes( s )
	),
	fc.constant( null ),
	fc.constant( undefined ),
	fc.constant( '' ),
	fc.constant( 'h1' ),
	fc.constant( 'h5' ),
	fc.constant( 'h6' ),
	fc.constant( 'div' ),
	fc.constant( 'span' ),
	fc.constant( 'p' ),
	fc.integer()
);

const nonEmptyStringArb = fc.string( { minLength: 1, maxLength: 200 } );

// --- Property 1: Title tag renders correct heading inside summary ---

describe( 'Feature: block-inspector-controls, Property 1: Title tag renders correct heading inside summary', () => {
	/**
	 * Validates: Requirements 2.5, 7.1
	 */

	it( 'for any valid titleTag and non-empty question/answer, rendered HTML contains the question wrapped in the specified heading inside summary', () => {
		fc.assert(
			fc.property(
				validTitleTagArb,
				nonEmptyStringArb,
				nonEmptyStringArb,
				( titleTag, question, answer ) => {
					const html = renderFaqItem( question, answer, titleTag );

					// The rendered HTML should contain <summary><hN>question</hN></summary>
					const expectedSummary = `<summary><${ titleTag }>${ question }</${ titleTag }></summary>`;
					expect( html ).toContain( expectedSummary );
				}
			),
			{ numRuns: 100 }
		);
	} );

	it( 'the heading element inside summary matches exactly the specified title tag', () => {
		fc.assert(
			fc.property(
				validTitleTagArb,
				nonEmptyStringArb,
				nonEmptyStringArb,
				( titleTag, question, answer ) => {
					const html = renderFaqItem( question, answer, titleTag );

					// Extract the tag used in <summary>
					const summaryMatch = html.match(
						/<summary><(h[2-4])>.*?<\/\1><\/summary>/
					);
					expect( summaryMatch ).not.toBeNull();
					expect( summaryMatch[ 1 ] ).toBe( titleTag );
				}
			),
			{ numRuns: 100 }
		);
	} );

	it( 'the question text is preserved inside the heading element', () => {
		fc.assert(
			fc.property(
				validTitleTagArb,
				nonEmptyStringArb,
				nonEmptyStringArb,
				( titleTag, question, answer ) => {
					const html = renderFaqItem( question, answer, titleTag );

					// The question text should appear between heading tags
					const openTag = `<${ titleTag }>`;
					const closeTag = `</${ titleTag }>`;
					const startIdx = html.indexOf( openTag ) + openTag.length;
					const endIdx = html.indexOf( closeTag );
					const renderedQuestion = html.substring( startIdx, endIdx );

					expect( renderedQuestion ).toBe( question );
				}
			),
			{ numRuns: 100 }
		);
	} );
} );

// --- Property 2: Invalid title tag falls back to h3 ---

describe( 'Feature: block-inspector-controls, Property 2: Invalid title tag falls back to h3', () => {
	/**
	 * Validates: Requirements 2.6, 7.5
	 */

	it( 'for any invalid titleTag value, the render function uses h3 as the heading element', () => {
		fc.assert(
			fc.property(
				invalidTitleTagArb,
				nonEmptyStringArb,
				nonEmptyStringArb,
				( titleTag, question, answer ) => {
					const html = renderFaqItem( question, answer, titleTag );

					// Should always fall back to h3
					expect( html ).toContain( `<summary><h3>${ question }</h3></summary>` );
				}
			),
			{ numRuns: 100 }
		);
	} );

	it( 'the getValidatedTitleTag function returns h3 for any non-allowed value', () => {
		fc.assert(
			fc.property( invalidTitleTagArb, ( titleTag ) => {
				const result = getValidatedTitleTag( { titleTag } );
				expect( result ).toBe( 'h3' );
			} ),
			{ numRuns: 100 }
		);
	} );

	it( 'the getValidatedTitleTag function never returns a value outside the allowed set', () => {
		fc.assert(
			fc.property(
				fc.anything(),
				( titleTag ) => {
					const result = getValidatedTitleTag( { titleTag } );
					expect( VALID_TITLE_TAGS ).toContain( result );
				}
			),
			{ numRuns: 100 }
		);
	} );
} );
