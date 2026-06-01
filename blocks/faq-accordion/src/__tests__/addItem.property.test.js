/**
 * Property-based tests for addItem logic in the FAQ Accordion Block.
 *
 * Feature: faq-accordion-block, Property 2: Adding an Item Grows the List
 *
 * @package AiFaqGenerator
 */

import * as fc from 'fast-check';

/**
 * Validates: Requirements 3.3, 4.1
 *
 * The addItem logic from edit.js:
 * - If items.length >= MAX_ITEMS (50), do nothing.
 * - Otherwise, append { question: '', answer: '' } to the items array.
 */

const MAX_ITEMS = 50;

/**
 * Simulates the addItem logic from edit.js.
 * Returns the new items array after adding an item.
 */
function addItem( items ) {
	if ( items.length >= MAX_ITEMS ) {
		return items;
	}
	return [ ...items, { question: '', answer: '' } ];
}

// --- Generator ---

const faqItemArb = fc.record( {
	question: fc.string( { minLength: 0, maxLength: 500 } ),
	answer: fc.string( { minLength: 0, maxLength: 5000 } ),
} );

const faqListArb = fc.array( faqItemArb, { minLength: 0, maxLength: 49 } );

// --- Property 2: Adding an Item Grows the List ---

describe( 'Feature: faq-accordion-block, Property 2: Adding an Item Grows the List', () => {
	/**
	 * Validates: Requirements 3.3, 4.1
	 */

	it( 'adding an item increases the array length by exactly 1', () => {
		fc.assert(
			fc.property( faqListArb, ( items ) => {
				const originalLength = items.length;
				const result = addItem( items );

				expect( result ).toHaveLength( originalLength + 1 );
			} ),
			{ numRuns: 100 }
		);
	} );

	it( 'the last item in the resulting array has empty question and answer strings', () => {
		fc.assert(
			fc.property( faqListArb, ( items ) => {
				const result = addItem( items );
				const lastItem = result[ result.length - 1 ];

				expect( lastItem ).toEqual( { question: '', answer: '' } );
			} ),
			{ numRuns: 100 }
		);
	} );

	it( 'all original items are preserved in their original order', () => {
		fc.assert(
			fc.property( faqListArb, ( items ) => {
				const result = addItem( items );

				// All original items should be preserved at their original indices
				for ( let i = 0; i < items.length; i++ ) {
					expect( result[ i ] ).toEqual( items[ i ] );
				}
			} ),
			{ numRuns: 100 }
		);
	} );

	it( 'does not mutate the original items array', () => {
		fc.assert(
			fc.property( faqListArb, ( items ) => {
				const originalCopy = [ ...items ];
				addItem( items );

				// Original array should not be mutated
				expect( items ).toEqual( originalCopy );
				expect( items ).toHaveLength( originalCopy.length );
			} ),
			{ numRuns: 100 }
		);
	} );
} );
