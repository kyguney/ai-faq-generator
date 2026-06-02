/**
 * Property-based tests for animation class and boolean validation in the FAQ Accordion block.
 *
 * Feature: block-inspector-controls, Property 6: Animation class presence matches boolean attribute
 * Feature: block-inspector-controls, Property 7: Non-boolean attribute values treated as false
 *
 * These tests simulate the PHP render logic from render.php to verify correctness
 * properties regarding the `enableAnimation` and boolean attribute validation behavior.
 *
 * @package AiFaqGenerator
 */

import * as fc from 'fast-check';

// --- JS simulation of PHP render logic ---

/**
 * Simulates PHP get_validated_boolean().
 * Uses strict === true comparison (mirrors PHP strict comparison).
 *
 * @param {Object} attributes Block attributes.
 * @param {string} key        The attribute key to check.
 * @return {boolean} True only if the attribute is strictly boolean true.
 */
function getValidatedBoolean( attributes, key ) {
	return (
		attributes[ key ] !== undefined &&
		attributes[ key ] !== null &&
		attributes[ key ] === true
	);
}

/**
 * Simulates PHP get_validated_icon_position().
 */
function getValidatedIconPosition( attributes ) {
	const pos = attributes.iconPosition ?? 'left';
	return [ 'left', 'right', 'none' ].includes( pos ) ? pos : 'left';
}

/**
 * Simulates PHP render_faq_accordion_block() — specifically the wrapper class
 * and open attribute logic.
 *
 * Returns an object with:
 * - wrapperClasses: the CSS class string on the wrapper div
 * - detailsElements: array of objects with { hasOpenAttr: boolean }
 *
 * @param {Object} attributes Block attributes.
 * @return {Object} Simulated render output.
 */
function simulateRender( attributes ) {
	const items = attributes.items ?? [];

	if ( ! Array.isArray( items ) || items.length === 0 ) {
		return { wrapperClasses: '', detailsElements: [], empty: true };
	}

	const iconPosition = getValidatedIconPosition( attributes );
	const openFirstItem = getValidatedBoolean( attributes, 'openFirstItem' );
	const enableAnimation = getValidatedBoolean( attributes, 'enableAnimation' );

	const iconClassMap = {
		left: 'has-icon-left',
		right: 'has-icon-right',
		none: 'has-no-icon',
	};

	let classes = 'wp-block-wpbits-faq-accordion ' + iconClassMap[ iconPosition ];

	if ( enableAnimation ) {
		classes += ' has-animation';
	}

	// Simulate details elements
	const detailsElements = [];
	let isFirstValidItem = true;

	for ( const item of items ) {
		if ( typeof item !== 'object' || item === null || Array.isArray( item ) ) {
			continue;
		}

		const question = item.question ?? '';
		const answer = item.answer ?? '';

		if ( typeof question !== 'string' || typeof answer !== 'string' ) {
			continue;
		}

		if ( question === '' || answer === '' ) {
			continue;
		}

		let hasOpenAttr = false;
		if ( openFirstItem && isFirstValidItem ) {
			hasOpenAttr = true;
			isFirstValidItem = false;
		} else {
			isFirstValidItem = false;
		}

		detailsElements.push( { hasOpenAttr } );
	}

	return { wrapperClasses: classes, detailsElements, empty: false };
}

// --- Generators ---

/**
 * Generator for a valid FAQ item (non-empty question and answer).
 */
const validFaqItemArb = fc.record( {
	question: fc.string( { minLength: 1, maxLength: 200 } ),
	answer: fc.string( { minLength: 1, maxLength: 1000 } ),
} );

/**
 * Generator for a non-empty array of valid FAQ items.
 */
const nonEmptyFaqListArb = fc.array( validFaqItemArb, {
	minLength: 1,
	maxLength: 20,
} );

/**
 * Generator for arbitrary non-boolean values (strings, numbers, null, undefined, objects).
 */
const nonBooleanArb = fc.oneof(
	fc.string(),
	fc.integer(),
	fc.float(),
	fc.constant( null ),
	fc.constant( undefined ),
	fc.constant( 'true' ),
	fc.constant( 'false' ),
	fc.constant( 1 ),
	fc.constant( 0 ),
	fc.constant( '' ),
	fc.dictionary( fc.string(), fc.string() )
);

// --- Property 6: Animation class presence matches boolean attribute ---

describe( 'Feature: block-inspector-controls, Property 6: Animation class presence matches boolean attribute', () => {
	/**
	 * Validates: Requirements 5.4, 7.4
	 *
	 * For any non-empty list of FAQ items, when enableAnimation is true the rendered
	 * wrapper SHALL contain the has-animation CSS class, and when enableAnimation is
	 * false the rendered wrapper SHALL NOT contain the has-animation CSS class.
	 */

	it( 'when enableAnimation is true, wrapper contains has-animation class', () => {
		fc.assert(
			fc.property( nonEmptyFaqListArb, ( items ) => {
				const attributes = {
					items,
					enableAnimation: true,
					iconPosition: 'left',
				};

				const result = simulateRender( attributes );

				expect( result.wrapperClasses ).toContain( 'has-animation' );
			} ),
			{ numRuns: 100 }
		);
	} );

	it( 'when enableAnimation is false, wrapper does NOT contain has-animation class', () => {
		fc.assert(
			fc.property( nonEmptyFaqListArb, ( items ) => {
				const attributes = {
					items,
					enableAnimation: false,
					iconPosition: 'left',
				};

				const result = simulateRender( attributes );

				expect( result.wrapperClasses ).not.toContain( 'has-animation' );
			} ),
			{ numRuns: 100 }
		);
	} );

	it( 'animation class presence is independent of icon position', () => {
		const iconPositionArb = fc.constantFrom( 'left', 'right', 'none' );

		fc.assert(
			fc.property(
				nonEmptyFaqListArb,
				fc.boolean(),
				iconPositionArb,
				( items, enableAnimation, iconPosition ) => {
					const attributes = { items, enableAnimation, iconPosition };
					const result = simulateRender( attributes );

					if ( enableAnimation ) {
						expect( result.wrapperClasses ).toContain(
							'has-animation'
						);
					} else {
						expect( result.wrapperClasses ).not.toContain(
							'has-animation'
						);
					}
				}
			),
			{ numRuns: 100 }
		);
	} );
} );

// --- Property 7: Non-boolean attribute values treated as false ---

describe( 'Feature: block-inspector-controls, Property 7: Non-boolean attribute values treated as false', () => {
	/**
	 * Validates: Requirements 6.5, 7.7
	 *
	 * For any value that is not a boolean true for openFirstItem or enableAnimation
	 * (including strings, numbers, null, undefined, objects), the render function
	 * SHALL behave as if the attribute is false — no open attribute on any details
	 * element and no has-animation class on the wrapper.
	 */

	it( 'non-boolean enableAnimation values result in no has-animation class', () => {
		fc.assert(
			fc.property(
				nonEmptyFaqListArb,
				nonBooleanArb,
				( items, nonBooleanValue ) => {
					const attributes = {
						items,
						enableAnimation: nonBooleanValue,
						iconPosition: 'left',
					};

					const result = simulateRender( attributes );

					expect( result.wrapperClasses ).not.toContain(
						'has-animation'
					);
				}
			),
			{ numRuns: 100 }
		);
	} );

	it( 'non-boolean openFirstItem values result in no open attribute on any details element', () => {
		fc.assert(
			fc.property(
				nonEmptyFaqListArb,
				nonBooleanArb,
				( items, nonBooleanValue ) => {
					const attributes = {
						items,
						openFirstItem: nonBooleanValue,
						iconPosition: 'left',
					};

					const result = simulateRender( attributes );

					for ( const detail of result.detailsElements ) {
						expect( detail.hasOpenAttr ).toBe( false );
					}
				}
			),
			{ numRuns: 100 }
		);
	} );

	it( 'non-boolean values for both openFirstItem and enableAnimation behave as false', () => {
		fc.assert(
			fc.property(
				nonEmptyFaqListArb,
				nonBooleanArb,
				nonBooleanArb,
				( items, nonBoolOpenFirst, nonBoolAnimation ) => {
					const attributes = {
						items,
						openFirstItem: nonBoolOpenFirst,
						enableAnimation: nonBoolAnimation,
						iconPosition: 'left',
					};

					const result = simulateRender( attributes );

					// No has-animation class
					expect( result.wrapperClasses ).not.toContain(
						'has-animation'
					);

					// No open attribute on any details element
					for ( const detail of result.detailsElements ) {
						expect( detail.hasOpenAttr ).toBe( false );
					}
				}
			),
			{ numRuns: 100 }
		);
	} );

	it( 'boolean false is also treated as false (no animation, no open)', () => {
		fc.assert(
			fc.property( nonEmptyFaqListArb, ( items ) => {
				const attributes = {
					items,
					openFirstItem: false,
					enableAnimation: false,
					iconPosition: 'left',
				};

				const result = simulateRender( attributes );

				expect( result.wrapperClasses ).not.toContain(
					'has-animation'
				);

				for ( const detail of result.detailsElements ) {
					expect( detail.hasOpenAttr ).toBe( false );
				}
			} ),
			{ numRuns: 100 }
		);
	} );
} );
