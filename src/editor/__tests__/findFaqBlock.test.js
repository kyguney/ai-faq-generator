/**
 * Property-based tests for findFaqBlock utility.
 *
 * Feature: block-insert-state
 * Property 3: Block Detection Finds First FAQ Block Recursively
 *
 * Validates: Requirements 6.1, 6.2, 8.1, 8.2
 */
import fc from 'fast-check';
import { findFaqBlock } from '../findFaqBlock';

/**
 * Arbitrary: generates a random block name that is NOT the FAQ block.
 */
const nonFaqBlockName = fc.constantFrom(
	'core/paragraph',
	'core/heading',
	'core/group',
	'core/columns',
	'core/column',
	'core/image',
	'core/list',
	'core/quote',
	'core/separator'
);

/**
 * Arbitrary: generates a random clientId string.
 */
const clientIdArb = fc.uuid();

/**
 * Arbitrary: generates a random FAQ items array.
 */
const faqItemsArb = fc.array(
	fc.record( {
		question: fc.string( { minLength: 1, maxLength: 50 } ),
		answer: fc.string( { minLength: 1, maxLength: 100 } ),
	} ),
	{ minLength: 1, maxLength: 5 }
);

/**
 * Arbitrary: generates a non-FAQ block (leaf node, no innerBlocks).
 */
const leafNonFaqBlock = fc.record( {
	name: nonFaqBlockName,
	clientId: clientIdArb,
	attributes: fc.record( {} ),
	innerBlocks: fc.constant( [] ),
} );

/**
 * Arbitrary: generates a FAQ block (leaf node).
 */
const faqBlock = fc.record( {
	name: fc.constant( 'wpbits/faq-accordion' ),
	clientId: clientIdArb,
	attributes: fc.record( { items: faqItemsArb } ),
	innerBlocks: fc.constant( [] ),
} );

/**
 * Arbitrary: generates a block tree of arbitrary depth containing no FAQ blocks.
 */
const blockTreeWithoutFaq = fc.letrec( ( tie ) => ( {
	tree: fc.array(
		fc.oneof(
			{ weight: 3, arbitrary: leafNonFaqBlock },
			{
				weight: 1,
				arbitrary: fc.record( {
					name: nonFaqBlockName,
					clientId: clientIdArb,
					attributes: fc.record( {} ),
					innerBlocks: tie( 'tree' ),
				} ),
			}
		),
		{ minLength: 0, maxLength: 5 }
	),
} ) ).tree;

/**
 * Arbitrary: generates a block tree that contains at least one FAQ block.
 * Returns { blocks, expectedClientId } where expectedClientId is the first FAQ
 * block in depth-first order.
 */
const blockTreeWithFaq = fc.record( {
	prefix: blockTreeWithoutFaq,
	faq: faqBlock,
	suffix: blockTreeWithoutFaq,
} ).chain( ( { prefix, faq, suffix } ) =>
	fc.record( {
		// Optionally nest the FAQ block inside a container
		nestDepth: fc.nat( { max: 3 } ),
		wrapperBlocks: fc.array( leafNonFaqBlock, { minLength: 0, maxLength: 3 } ),
	} ).map( ( { nestDepth, wrapperBlocks } ) => {
		// Build the FAQ block possibly nested inside containers
		let faqContainer = faq;
		for ( let i = 0; i < nestDepth; i++ ) {
			faqContainer = {
				name: 'core/group',
				clientId: `wrapper-${ i }-${ faq.clientId }`,
				attributes: {},
				innerBlocks: [ ...wrapperBlocks.slice( 0, i ), faqContainer ],
			};
		}

		const blocks = [ ...prefix, faqContainer, ...suffix ];
		return { blocks, expectedClientId: faq.clientId, expectedItems: faq.attributes.items };
	} )
);

/**
 * Helper: finds the first FAQ block in depth-first order manually (reference implementation).
 */
function findFirstFaqDFS( blocks ) {
	for ( const block of blocks ) {
		if ( block.name === 'wpbits/faq-accordion' ) {
			return { clientId: block.clientId, items: block.attributes.items };
		}
		if ( block.innerBlocks && block.innerBlocks.length > 0 ) {
			const found = findFirstFaqDFS( block.innerBlocks );
			if ( found ) {
				return found;
			}
		}
	}
	return null;
}

describe( 'findFaqBlock — Property 3: Block Detection Finds First FAQ Block Recursively', () => {
	it( 'returns null when no FAQ block exists in the tree', () => {
		fc.assert(
			fc.property( blockTreeWithoutFaq, ( blocks ) => {
				const result = findFaqBlock( blocks );
				expect( result ).toBeNull();
			} ),
			{ numRuns: 100 }
		);
	} );

	it( 'returns the first FAQ block in depth-first order when one exists', () => {
		fc.assert(
			fc.property( blockTreeWithFaq, ( { blocks, expectedClientId, expectedItems } ) => {
				const result = findFaqBlock( blocks );
				expect( result ).not.toBeNull();
				expect( result.clientId ).toBe( expectedClientId );
				expect( result.items ).toEqual( expectedItems );
			} ),
			{ numRuns: 100 }
		);
	} );

	it( 'matches a reference depth-first search implementation on arbitrary trees', () => {
		// Generate trees that may or may not contain FAQ blocks
		const mixedTree = fc.letrec( ( tie ) => ( {
			tree: fc.array(
				fc.oneof(
					{ weight: 5, arbitrary: leafNonFaqBlock },
					{ weight: 1, arbitrary: faqBlock },
					{
						weight: 2,
						arbitrary: fc.record( {
							name: nonFaqBlockName,
							clientId: clientIdArb,
							attributes: fc.record( {} ),
							innerBlocks: tie( 'tree' ),
						} ),
					}
				),
				{ minLength: 0, maxLength: 6 }
			),
		} ) ).tree;

		fc.assert(
			fc.property( mixedTree, ( blocks ) => {
				const result = findFaqBlock( blocks );
				const expected = findFirstFaqDFS( blocks );

				if ( expected === null ) {
					expect( result ).toBeNull();
				} else {
					expect( result ).not.toBeNull();
					expect( result.clientId ).toBe( expected.clientId );
					expect( result.items ).toEqual( expected.items );
				}
			} ),
			{ numRuns: 100 }
		);
	} );

	it( 'returns the first FAQ block when multiple FAQ blocks exist at different depths', () => {
		const treeWithMultipleFaqs = fc.record( {
			faq1: faqBlock,
			faq2: faqBlock,
			between: fc.array( leafNonFaqBlock, { minLength: 0, maxLength: 3 } ),
		} ).map( ( { faq1, faq2, between } ) => ( {
			blocks: [ faq1, ...between, faq2 ],
			expectedClientId: faq1.clientId,
			expectedItems: faq1.attributes.items,
		} ) );

		fc.assert(
			fc.property( treeWithMultipleFaqs, ( { blocks, expectedClientId, expectedItems } ) => {
				const result = findFaqBlock( blocks );
				expect( result ).not.toBeNull();
				expect( result.clientId ).toBe( expectedClientId );
				expect( result.items ).toEqual( expectedItems );
			} ),
			{ numRuns: 100 }
		);
	} );
} );
