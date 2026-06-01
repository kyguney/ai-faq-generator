/**
 * Unit tests for deriveInitialState.
 *
 * @package AiFaqGenerator
 */

import { deriveInitialState, INITIAL_STATE } from '../deriveInitialState';
import * as fc from 'fast-check';

describe( 'deriveInitialState', () => {
	describe( 'INITIAL_STATE constant', () => {
		it( 'has the correct shape with all required fields', () => {
			expect( INITIAL_STATE ).toEqual( {
				sidebarState: 'empty',
				activeBlockClientId: null,
				faqCount: 0,
				isRegenerating: false,
				isGenerating: false,
				error: null,
			} );
		} );
	} );

	describe( 'block exists (block_inserted state)', () => {
		it( 'returns block_inserted when block exists with valid meta', () => {
			const result = deriveInitialState( true, 'client-123', '[{"question":"Q","answer":"A"}]' );
			expect( result.sidebarState ).toBe( 'block_inserted' );
			expect( result.activeBlockClientId ).toBe( 'client-123' );
		} );

		it( 'returns block_inserted when block exists with empty meta', () => {
			const result = deriveInitialState( true, 'client-456', '' );
			expect( result.sidebarState ).toBe( 'block_inserted' );
			expect( result.activeBlockClientId ).toBe( 'client-456' );
		} );

		it( 'returns block_inserted when block exists with null meta', () => {
			const result = deriveInitialState( true, 'client-789', null );
			expect( result.sidebarState ).toBe( 'block_inserted' );
			expect( result.activeBlockClientId ).toBe( 'client-789' );
		} );

		it( 'returns block_inserted when block exists with invalid JSON meta', () => {
			const result = deriveInitialState( true, 'client-abc', 'not-json' );
			expect( result.sidebarState ).toBe( 'block_inserted' );
			expect( result.activeBlockClientId ).toBe( 'client-abc' );
		} );
	} );

	describe( 'no block, valid meta (has_faqs state)', () => {
		it( 'returns has_faqs when no block and meta has one FAQ item', () => {
			const meta = JSON.stringify( [ { question: 'Q1', answer: 'A1' } ] );
			const result = deriveInitialState( false, null, meta );
			expect( result.sidebarState ).toBe( 'has_faqs' );
			expect( result.faqCount ).toBe( 1 );
			expect( result.activeBlockClientId ).toBeNull();
		} );

		it( 'returns has_faqs when no block and meta has multiple FAQ items', () => {
			const meta = JSON.stringify( [
				{ question: 'Q1', answer: 'A1' },
				{ question: 'Q2', answer: 'A2' },
				{ question: 'Q3', answer: 'A3' },
			] );
			const result = deriveInitialState( false, null, meta );
			expect( result.sidebarState ).toBe( 'has_faqs' );
			expect( result.faqCount ).toBe( 3 );
		} );
	} );

	describe( 'no block, invalid/empty meta (empty state)', () => {
		it( 'returns empty when no block and meta is empty string', () => {
			const result = deriveInitialState( false, null, '' );
			expect( result.sidebarState ).toBe( 'empty' );
			expect( result.faqCount ).toBe( 0 );
		} );

		it( 'returns empty when no block and meta is null', () => {
			const result = deriveInitialState( false, null, null );
			expect( result.sidebarState ).toBe( 'empty' );
			expect( result.faqCount ).toBe( 0 );
		} );

		it( 'returns empty when no block and meta is undefined', () => {
			const result = deriveInitialState( false, null, undefined );
			expect( result.sidebarState ).toBe( 'empty' );
			expect( result.faqCount ).toBe( 0 );
		} );

		it( 'returns empty when no block and meta is invalid JSON', () => {
			const result = deriveInitialState( false, null, 'not valid json' );
			expect( result.sidebarState ).toBe( 'empty' );
			expect( result.faqCount ).toBe( 0 );
		} );

		it( 'returns empty when no block and meta is an empty array', () => {
			const result = deriveInitialState( false, null, '[]' );
			expect( result.sidebarState ).toBe( 'empty' );
			expect( result.faqCount ).toBe( 0 );
		} );

		it( 'returns empty when no block and meta is a non-array JSON value', () => {
			const result = deriveInitialState( false, null, '"hello"' );
			expect( result.sidebarState ).toBe( 'empty' );
			expect( result.faqCount ).toBe( 0 );
		} );

		it( 'returns empty when no block and meta is a JSON object (not array)', () => {
			const result = deriveInitialState( false, null, '{"question":"Q","answer":"A"}' );
			expect( result.sidebarState ).toBe( 'empty' );
			expect( result.faqCount ).toBe( 0 );
		} );
	} );

	describe( 'state shape invariants', () => {
		it( 'always returns isRegenerating as false', () => {
			expect( deriveInitialState( true, 'id', null ).isRegenerating ).toBe( false );
			expect( deriveInitialState( false, null, '[]' ).isRegenerating ).toBe( false );
			expect( deriveInitialState( false, null, '[{"question":"Q","answer":"A"}]' ).isRegenerating ).toBe( false );
		} );

		it( 'always returns isGenerating as false', () => {
			expect( deriveInitialState( true, 'id', null ).isGenerating ).toBe( false );
			expect( deriveInitialState( false, null, '[]' ).isGenerating ).toBe( false );
			expect( deriveInitialState( false, null, '[{"question":"Q","answer":"A"}]' ).isGenerating ).toBe( false );
		} );

		it( 'always returns error as null', () => {
			expect( deriveInitialState( true, 'id', null ).error ).toBeNull();
			expect( deriveInitialState( false, null, '[]' ).error ).toBeNull();
			expect( deriveInitialState( false, null, '[{"question":"Q","answer":"A"}]' ).error ).toBeNull();
		} );
	} );
} );


/**
 * Property-based tests for deriveInitialState.
 *
 * **Validates: Requirements 1.2, 1.3, 1.4, 6.3, 6.4**
 *
 * Property 2: Initial State Derivation
 * For any combination of post meta value and block existence,
 * the initial state derivation function returns the correct sidebarState.
 */
describe( 'deriveInitialState — Property-Based Tests', () => {
	/**
	 * Arbitrary: generates a valid JSON array string with at least one element.
	 * Elements are arbitrary JSON-serializable objects.
	 */
	const validMetaArb = fc
		.array( fc.dictionary( fc.string(), fc.jsonValue() ), { minLength: 1 } )
		.map( ( arr ) => JSON.stringify( arr ) );

	/**
	 * Arbitrary: generates invalid meta values — invalid JSON strings,
	 * empty strings, null, empty arrays, and non-array JSON values.
	 */
	const invalidMetaArb = fc.oneof(
		// Invalid JSON strings (not parseable)
		fc.string().filter( ( s ) => {
			if ( ! s ) return false; // exclude falsy (handled by null/empty)
			try {
				const parsed = JSON.parse( s );
				return ! ( Array.isArray( parsed ) && parsed.length > 0 );
			} catch {
				return true; // genuinely invalid JSON
			}
		} ),
		// Empty string
		fc.constant( '' ),
		// Null
		fc.constant( null ),
		// Empty array JSON
		fc.constant( '[]' ),
		// Non-array JSON values (objects, numbers, strings, booleans)
		fc.oneof(
			fc.dictionary( fc.string(), fc.jsonValue() ).map( ( obj ) => JSON.stringify( obj ) ),
			fc.integer().map( ( n ) => JSON.stringify( n ) ),
			fc.boolean().map( ( b ) => JSON.stringify( b ) )
		)
	);

	/**
	 * Arbitrary: generates a clientId string (non-empty).
	 */
	const clientIdArb = fc.string( { minLength: 1 } );

	it( 'Property 2a: block exists → sidebarState is always block_inserted (regardless of meta)', () => {
		fc.assert(
			fc.property(
				clientIdArb,
				fc.oneof( validMetaArb, invalidMetaArb ),
				( clientId, metaValue ) => {
					const result = deriveInitialState( true, clientId, metaValue );
					expect( result.sidebarState ).toBe( 'block_inserted' );
					expect( result.activeBlockClientId ).toBe( clientId );
				}
			),
			{ numRuns: 100 }
		);
	} );

	it( 'Property 2b: no block + valid meta (non-empty JSON array) → sidebarState is has_faqs', () => {
		fc.assert(
			fc.property(
				validMetaArb,
				( metaValue ) => {
					const result = deriveInitialState( false, null, metaValue );
					expect( result.sidebarState ).toBe( 'has_faqs' );
					expect( result.faqCount ).toBeGreaterThan( 0 );
					expect( result.activeBlockClientId ).toBeNull();
				}
			),
			{ numRuns: 100 }
		);
	} );

	it( 'Property 2c: no block + invalid/empty meta → sidebarState is empty', () => {
		fc.assert(
			fc.property(
				invalidMetaArb,
				( metaValue ) => {
					const result = deriveInitialState( false, null, metaValue );
					expect( result.sidebarState ).toBe( 'empty' );
					expect( result.faqCount ).toBe( 0 );
					expect( result.activeBlockClientId ).toBeNull();
				}
			),
			{ numRuns: 100 }
		);
	} );

	it( 'Property 2d: faqCount matches the length of the parsed meta array when valid', () => {
		fc.assert(
			fc.property(
				fc.array( fc.dictionary( fc.string(), fc.jsonValue() ), { minLength: 1, maxLength: 50 } ),
				( arr ) => {
					const metaValue = JSON.stringify( arr );
					const result = deriveInitialState( false, null, metaValue );
					expect( result.sidebarState ).toBe( 'has_faqs' );
					expect( result.faqCount ).toBe( arr.length );
				}
			),
			{ numRuns: 100 }
		);
	} );

	it( 'Property 2e: result always contains all INITIAL_STATE fields', () => {
		fc.assert(
			fc.property(
				fc.boolean(),
				fc.option( clientIdArb, { nil: null } ),
				fc.oneof( validMetaArb, invalidMetaArb ),
				( blockExists, clientId, metaValue ) => {
					const result = deriveInitialState( blockExists, clientId, metaValue );
					// All keys from INITIAL_STATE must be present
					for ( const key of Object.keys( INITIAL_STATE ) ) {
						expect( result ).toHaveProperty( key );
					}
					// Invariant fields
					expect( result.isRegenerating ).toBe( false );
					expect( result.isGenerating ).toBe( false );
					expect( result.error ).toBeNull();
				}
			),
			{ numRuns: 100 }
		);
	} );
} );
