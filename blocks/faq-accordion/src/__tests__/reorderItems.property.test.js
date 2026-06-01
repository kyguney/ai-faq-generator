/**
 * Property-based test for reordering FAQ items.
 *
 * Property 4: Reordering Preserves All Items
 * For any FAQ items array with at least 2 items and any valid index,
 * moving an item up or down should result in an array of the same length
 * containing exactly the same items, with only the moved item and its
 * neighbor swapped.
 *
 * **Validates: Requirements 4.3**
 *
 * @package AiFaqGenerator
 */

import * as fc from 'fast-check';

// --- Pure moveItem logic extracted from edit.js ---

/**
 * Simulates the moveItem logic from edit.js.
 * Given an items array, an index, and a direction (+1 or -1),
 * returns a new array with the item at index swapped with its neighbor.
 * Returns the original array unchanged if the move is out of bounds.
 */
function moveItem( items, index, direction ) {
	const targetIndex = index + direction;
	if ( targetIndex < 0 || targetIndex >= items.length ) {
		return items;
	}
	const updatedItems = [ ...items ];
	const temp = updatedItems[ index ];
	updatedItems[ index ] = updatedItems[ targetIndex ];
	updatedItems[ targetIndex ] = temp;
	return updatedItems;
}

// --- Generators ---

const faqItemArb = fc.record( {
	question: fc.string( { minLength: 1, maxLength: 100 } ),
	answer: fc.string( { minLength: 1, maxLength: 200 } ),
} );

const faqListWithValidMoveArb = fc
	.array( faqItemArb, { minLength: 2, maxLength: 20 } )
	.chain( ( items ) =>
		fc.tuple(
			fc.constant( items ),
			fc.integer( { min: 0, max: items.length - 1 } ),
			fc.constantFrom( -1, 1 )
		)
		.filter( ( [ list, index, direction ] ) => {
			const targetIndex = index + direction;
			return targetIndex >= 0 && targetIndex < list.length;
		} )
	);

// --- Property 4: Reordering Preserves All Items ---

describe( 'Feature: faq-accordion-block, Property 4: Reordering Preserves All Items', () => {
	/**
	 * **Validates: Requirements 4.3**
	 */

	it( 'the resulting array has the same length as the original', () => {
		fc.assert(
			fc.property( faqListWithValidMoveArb, ( [ items, index, direction ] ) => {
				const result = moveItem( items, index, direction );
				expect( result ).toHaveLength( items.length );
			} ),
			{ numRuns: 100 }
		);
	} );

	it( 'the resulting array contains exactly the same items (just reordered)', () => {
		fc.assert(
			fc.property( faqListWithValidMoveArb, ( [ items, index, direction ] ) => {
				const result = moveItem( items, index, direction );

				// Sort both arrays by JSON representation to compare as sets
				const sortFn = ( a, b ) =>
					JSON.stringify( a ).localeCompare( JSON.stringify( b ) );
				const sortedOriginal = [ ...items ].sort( sortFn );
				const sortedResult = [ ...result ].sort( sortFn );

				expect( sortedResult ).toEqual( sortedOriginal );
			} ),
			{ numRuns: 100 }
		);
	} );

	it( 'only the target item and its neighbor are swapped', () => {
		fc.assert(
			fc.property( faqListWithValidMoveArb, ( [ items, index, direction ] ) => {
				const targetIndex = index + direction;
				const result = moveItem( items, index, direction );

				// The item originally at index should now be at targetIndex
				expect( result[ targetIndex ] ).toEqual( items[ index ] );

				// The item originally at targetIndex should now be at index
				expect( result[ index ] ).toEqual( items[ targetIndex ] );
			} ),
			{ numRuns: 100 }
		);
	} );

	it( 'all other items remain in their original positions', () => {
		fc.assert(
			fc.property( faqListWithValidMoveArb, ( [ items, index, direction ] ) => {
				const targetIndex = index + direction;
				const result = moveItem( items, index, direction );

				// Every item not at index or targetIndex should be unchanged
				for ( let i = 0; i < items.length; i++ ) {
					if ( i !== index && i !== targetIndex ) {
						expect( result[ i ] ).toEqual( items[ i ] );
					}
				}
			} ),
			{ numRuns: 100 }
		);
	} );
} );
