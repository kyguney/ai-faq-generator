/**
 * Property-based tests for open first item and icon position rendering.
 *
 * Feature: block-inspector-controls, Property 3, 4, 5
 *
 * Tests simulate the PHP render logic (render.php) in JavaScript to verify
 * correctness properties for the openFirstItem and iconPosition attributes.
 *
 * @package AiFaqGenerator
 */

import * as fc from 'fast-check';

// --- PHP render logic simulation ---

/**
 * Simulates get_validated_icon_position from render.php.
 * Returns one of 'left', 'right', or 'none'. Falls back to 'left'.
 */
function getValidatedIconPosition( attributes ) {
	const pos = attributes.iconPosition ?? 'left';
	return [ 'left', 'right', 'none' ].includes( pos ) ? pos : 'left';
}

/**
 * Simulates get_validated_boolean from render.php.
 * Uses strict === true comparison.
 */
function getValidatedBoolean( attributes, key ) {
	return (
		attributes[ key ] !== undefined && attributes[ key ] === true
	);
}

/**
 * Icon position to CSS class mapping (from render.php).
 */
const ICON_CLASS_MAP = {
	left: 'has-icon-left',
	right: 'has-icon-right',
	none: 'has-no-icon',
};

/**
 * All possible icon-position CSS classes.
 */
const ALL_ICON_CLASSES = [ 'has-icon-left', 'has-icon-right', 'has-no-icon' ];

/**
 * Simulates render_faq_accordion_block from render.php.
 * Returns the rendered HTML string for given attributes and items.
 */
function renderFaqAccordionBlock( attributes ) {
	const items = attributes.items ?? [];

	if ( ! Array.isArray( items ) || items.length === 0 ) {
		return '';
	}

	const iconPosition = getValidatedIconPosition( attributes );
	const openFirstItem = getValidatedBoolean( attributes, 'openFirstItem' );

	// Build CSS class string.
	const classes =
		'wp-block-wpbits-faq-accordion ' + ICON_CLASS_MAP[ iconPosition ];

	let output = `<div class="${ classes }">`;

	let isFirstValidItem = true;

	items.forEach( ( item, index ) => {
		if ( typeof item !== 'object' || item === null ) {
			return;
		}

		const questionValue = item.question ?? '';
		const answerValue = item.answer ?? '';

		if ( typeof questionValue !== 'string' || typeof answerValue !== 'string' ) {
			return;
		}

		if ( questionValue === '' || answerValue === '' ) {
			return;
		}

		const panelId = `faq-panel-${ index + 1 }`;

		let openAttr = '';
		if ( openFirstItem && isFirstValidItem ) {
			openAttr = ' open';
			isFirstValidItem = false;
		} else {
			isFirstValidItem = false;
		}

		output += `<details class="faq-accordion-item"${ openAttr }>`;
		output += `<summary aria-expanded="false" aria-controls="${ panelId }">`;
		output += `<h3>${ questionValue }</h3>`;
		output += '</summary>';
		output += `<div id="${ panelId }" class="faq-accordion-content">`;
		output += answerValue;
		output += '</div>';
		output += '</details>';
	} );

	output += '</div>';

	return output;
}

// --- Generators ---

/**
 * Generator for a valid FAQ item (non-empty question and answer).
 */
const validFaqItemArb = fc.record( {
	question: fc.string( { minLength: 1, maxLength: 200 } ).filter( ( s ) => s.trim().length > 0 ),
	answer: fc.string( { minLength: 1, maxLength: 1000 } ).filter( ( s ) => s.trim().length > 0 ),
} );

/**
 * Generator for a non-empty list of valid FAQ items.
 */
const nonEmptyValidFaqListArb = fc.array( validFaqItemArb, {
	minLength: 1,
	maxLength: 10,
} );

/**
 * Generator for valid icon position values.
 */
const validIconPositionArb = fc.constantFrom( 'left', 'right', 'none' );

/**
 * Generator for invalid icon position values (strings that are not left, right, or none).
 */
const invalidIconPositionArb = fc
	.string( { minLength: 1, maxLength: 50 } )
	.filter( ( s ) => ! [ 'left', 'right', 'none' ].includes( s ) );

// --- Property 3: Open first item applies open attribute exclusively to first details ---

describe( 'Feature: block-inspector-controls, Property 3: Open first item applies open attribute exclusively to first details', () => {
	/**
	 * Validates: Requirements 3.7, 3.8, 7.2
	 */

	it( 'when openFirstItem is true, only the first <details> element has the open attribute', () => {
		fc.assert(
			fc.property( nonEmptyValidFaqListArb, ( items ) => {
				const html = renderFaqAccordionBlock( {
					items,
					openFirstItem: true,
				} );

				// Extract all <details ...> opening tags
				const detailsTags = html.match( /<details[^>]*>/g ) || [];

				// There must be at least one details element (we have valid items)
				expect( detailsTags.length ).toBeGreaterThan( 0 );

				// First details element must have the open attribute
				expect( detailsTags[ 0 ] ).toContain( ' open' );

				// No other details elements should have the open attribute
				for ( let i = 1; i < detailsTags.length; i++ ) {
					expect( detailsTags[ i ] ).not.toContain( ' open' );
				}
			} ),
			{ numRuns: 100 }
		);
	} );

	it( 'when openFirstItem is false, no <details> element has the open attribute', () => {
		fc.assert(
			fc.property( nonEmptyValidFaqListArb, ( items ) => {
				const html = renderFaqAccordionBlock( {
					items,
					openFirstItem: false,
				} );

				// Extract all <details ...> opening tags
				const detailsTags = html.match( /<details[^>]*>/g ) || [];

				// No details element should have the open attribute
				for ( const tag of detailsTags ) {
					expect( tag ).not.toContain( ' open' );
				}
			} ),
			{ numRuns: 100 }
		);
	} );

	it( 'when openFirstItem is true, exactly one details element has open (the first one)', () => {
		fc.assert(
			fc.property(
				fc.array( validFaqItemArb, { minLength: 2, maxLength: 10 } ),
				( items ) => {
					const html = renderFaqAccordionBlock( {
						items,
						openFirstItem: true,
					} );

					// Count open attributes on details tags
					const detailsTags = html.match( /<details[^>]*>/g ) || [];
					const openCount = detailsTags.filter( ( tag ) =>
						tag.includes( ' open' )
					).length;

					expect( openCount ).toBe( 1 );
				}
			),
			{ numRuns: 100 }
		);
	} );
} );

// --- Property 4: Icon position maps to exactly one correct CSS class ---

describe( 'Feature: block-inspector-controls, Property 4: Icon position maps to exactly one correct CSS class', () => {
	/**
	 * Validates: Requirements 4.5, 4.6, 4.7, 7.3
	 */

	it( 'the wrapper contains exactly one icon-position CSS class matching the iconPosition attribute', () => {
		fc.assert(
			fc.property(
				validIconPositionArb,
				nonEmptyValidFaqListArb,
				( iconPosition, items ) => {
					const html = renderFaqAccordionBlock( {
						items,
						iconPosition,
					} );

					const expectedClass = ICON_CLASS_MAP[ iconPosition ];

					// The expected class must be present
					expect( html ).toContain( expectedClass );

					// No other icon-position classes should be present
					const otherClasses = ALL_ICON_CLASSES.filter(
						( cls ) => cls !== expectedClass
					);
					for ( const cls of otherClasses ) {
						expect( html ).not.toContain( cls );
					}
				}
			),
			{ numRuns: 100 }
		);
	} );

	it( 'the wrapper div class attribute contains exactly one icon-position class', () => {
		fc.assert(
			fc.property(
				validIconPositionArb,
				nonEmptyValidFaqListArb,
				( iconPosition, items ) => {
					const html = renderFaqAccordionBlock( {
						items,
						iconPosition,
					} );

					// Extract the wrapper div class attribute
					const wrapperMatch = html.match(
						/<div class="([^"]*)">/
					);
					expect( wrapperMatch ).not.toBeNull();

					const classValue = wrapperMatch[ 1 ];

					// Count how many icon-position classes are in the class attribute
					const iconClassCount = ALL_ICON_CLASSES.filter( ( cls ) =>
						classValue.includes( cls )
					).length;

					expect( iconClassCount ).toBe( 1 );
				}
			),
			{ numRuns: 100 }
		);
	} );
} );

// --- Property 5: Invalid icon position falls back to has-icon-left ---

describe( 'Feature: block-inspector-controls, Property 5: Invalid icon position falls back to has-icon-left', () => {
	/**
	 * Validates: Requirements 4.8, 7.6
	 */

	it( 'any invalid iconPosition value results in has-icon-left on the wrapper', () => {
		fc.assert(
			fc.property(
				invalidIconPositionArb,
				nonEmptyValidFaqListArb,
				( iconPosition, items ) => {
					const html = renderFaqAccordionBlock( {
						items,
						iconPosition,
					} );

					// The wrapper must contain has-icon-left
					expect( html ).toContain( 'has-icon-left' );

					// No other icon-position classes should be present
					expect( html ).not.toContain( 'has-icon-right' );
					expect( html ).not.toContain( 'has-no-icon' );
				}
			),
			{ numRuns: 100 }
		);
	} );

	it( 'undefined iconPosition (missing attribute) falls back to has-icon-left', () => {
		fc.assert(
			fc.property( nonEmptyValidFaqListArb, ( items ) => {
				const html = renderFaqAccordionBlock( {
					items,
					// iconPosition not provided
				} );

				expect( html ).toContain( 'has-icon-left' );
				expect( html ).not.toContain( 'has-icon-right' );
				expect( html ).not.toContain( 'has-no-icon' );
			} ),
			{ numRuns: 100 }
		);
	} );
} );
