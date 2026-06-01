/**
 * Property-based tests for removeItem logic.
 *
 * Feature: faq-accordion-block, Property 3: Removing an Item Shrinks the List
 *
 * Validates: Requirements 3.5
 *
 * @package AiFaqGenerator
 */

import * as fc from 'fast-check';

/**
 * Simulates the removeItem logic from edit.js:
 * const removeItem = (index) => {
 *     const updatedItems = items.filter((_, i) => i !== index);
 *     setAttributes({ items: updatedItems });
 * };
 */
const removeItem = ( items, index ) => {
	return items.filter( ( _, i ) => i !== index );
};

// --- Generators ---

const faqItemArb = fc.record( {
	question: fc.string( { minLength: 0, maxLength: 500 } ),
	answer: fc.string( { minLength: 0, maxLength: 5000 } ),
} );

const nonEmptyFaqListArb = fc.array( faqItemArb, {
	minLength: 1,
	maxLength: 50,
} );

// --- Property 3: Removing an Item Shrinks the List ---

describe( 'Feature: faq-accordion-block, Property 3: Removing an Item Shrinks the List', () => {
	/**
	 * Validates: Requirements 3.5
	 */

	it( 'removing an item at a valid index decreases the array length by exactly 1', () => {
		fc.assert(
			fc.property(
				nonEmptyFaqListArb.chain( ( list ) =>
					fc.tuple(
						fc.constant( list ),
						fc.integer( { min: 0, max: list.length - 1 } )
					)
				),
				( [ items, index ] ) => {
					const originalLength = items.length;
					const result = removeItem( items, index );

					expect( result ).toHaveLength( originalLength - 1 );
				}
			),
			{ numRuns: 100 }
		);
	} );

	it( 'all other items remain in their relative order after removal', () => {
		fc.assert(
			fc.property(
				nonEmptyFaqListArb.chain( ( list ) =>
					fc.tuple(
						fc.constant( list ),
						fc.integer( { min: 0, max: list.length - 1 } )
					)
				),
				( [ items, index ] ) => {
					const result = removeItem( items, index );

					// Build expected: items before the index + items after the index
					const expected = [
						...items.slice( 0, index ),
						...items.slice( index + 1 ),
					];

					expect( result ).toEqual( expected );
				}
			),
			{ numRuns: 100 }
		);
	} );

	it( 'the removed item is no longer present at its original position', () => {
		fc.assert(
			fc.property(
				nonEmptyFaqListArb.chain( ( list ) =>
					fc.tuple(
						fc.constant( list ),
						fc.integer( { min: 0, max: list.length - 1 } )
					)
				),
				( [ items, index ] ) => {
					const removedItem = items[ index ];
					const result = removeItem( items, index );

					// Verify the item at the removed index is not the same object
					// at any position that would indicate it wasn't removed.
					// More precisely: the result should not contain the removed item
					// at the same sequence position it occupied.
					if ( index < result.length ) {
						// The item now at position `index` should be what was
						// originally at position `index + 1`
						expect( result[ index ] ).toEqual( items[ index + 1 ] );
					}

					// The result array should be the original without the item at index
					const containsRemovedAtOriginalSpot =
						result.length >= items.length;
					expect( containsRemovedAtOriginalSpot ).toBe( false );
				}
			),
			{ numRuns: 100 }
		);
	} );
} );
