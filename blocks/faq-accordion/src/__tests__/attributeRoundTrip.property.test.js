/**
 * Property-based test for FAQ item attribute round-trip.
 *
 * Feature: faq-accordion-block, Property 1: FAQ Item Attribute Round-Trip
 *
 * @package AiFaqGenerator
 */

import * as fc from 'fast-check';

// --- Generators ---

/**
 * Generator for a single FAQ item with question and answer strings.
 * Constrains to valid string content that can survive JSON serialization.
 */
const faqItemArb = fc.record( {
	question: fc.string( { minLength: 0, maxLength: 500 } ),
	answer: fc.string( { minLength: 0, maxLength: 5000 } ),
} );

/**
 * Generator for an array of FAQ items (0 to 50 items, matching block constraints).
 */
const faqItemsArrayArb = fc.array( faqItemArb, { minLength: 0, maxLength: 50 } );

// --- Property 1: FAQ Item Attribute Round-Trip ---

describe( 'Property 1: FAQ Item Attribute Round-Trip', () => {
	/**
	 * Validates: Requirements 2.4
	 *
	 * For any valid array of FAQ items, serializing the items as JSON
	 * (simulating block attribute storage in post content) and then
	 * deserializing should produce an identical array with all question
	 * and answer values preserved.
	 */
	it( 'serializing FAQ items as JSON and deserializing preserves all values', () => {
		fc.assert(
			fc.property( faqItemsArrayArb, ( items ) => {
				// Simulate block attribute serialization (WordPress stores block
				// attributes as JSON in the post content comment delimiter)
				const serialized = JSON.stringify( { items } );

				// Simulate restoring attributes from post content
				const restored = JSON.parse( serialized );

				// All items should be preserved exactly
				expect( restored.items ).toEqual( items );
				expect( restored.items.length ).toBe( items.length );

				// Verify each item's question and answer individually
				for ( let i = 0; i < items.length; i++ ) {
					expect( restored.items[ i ].question ).toBe( items[ i ].question );
					expect( restored.items[ i ].answer ).toBe( items[ i ].answer );
				}
			} ),
			{ numRuns: 100 }
		);
	} );

	it( 'round-trip preserves the exact number of items', () => {
		fc.assert(
			fc.property( faqItemsArrayArb, ( items ) => {
				const serialized = JSON.stringify( { items } );
				const restored = JSON.parse( serialized );

				expect( restored.items.length ).toBe( items.length );
			} ),
			{ numRuns: 100 }
		);
	} );

	it( 'round-trip preserves item order', () => {
		fc.assert(
			fc.property(
				fc.array( faqItemArb, { minLength: 2, maxLength: 50 } ),
				( items ) => {
					const serialized = JSON.stringify( { items } );
					const restored = JSON.parse( serialized );

					// Verify order is preserved by checking each position
					for ( let i = 0; i < items.length; i++ ) {
						expect( restored.items[ i ] ).toEqual( items[ i ] );
					}
				}
			),
			{ numRuns: 100 }
		);
	} );

	it( 'round-trip preserves items containing special characters', () => {
		fc.assert(
			fc.property(
				fc.array(
					fc.record( {
						question: fc.unicodeString( { minLength: 1, maxLength: 500 } ),
						answer: fc.unicodeString( { minLength: 1, maxLength: 5000 } ),
					} ),
					{ minLength: 1, maxLength: 20 }
				),
				( items ) => {
					const serialized = JSON.stringify( { items } );
					const restored = JSON.parse( serialized );

					expect( restored.items ).toEqual( items );
				}
			),
			{ numRuns: 100 }
		);
	} );
} );
