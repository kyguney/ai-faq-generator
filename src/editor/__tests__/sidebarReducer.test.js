/**
 * Property-based tests for sidebarReducer.
 *
 * @package AiFaqGenerator
 */

import * as fc from 'fast-check';
import { INITIAL_STATE } from '../deriveInitialState';
import {
	sidebarReducer,
	BLOCK_DETECTED,
	META_LOADED,
	INSERT_SUCCESS,
	BLOCK_REMOVED,
	CLEAR,
	REGENERATE_START,
	REGENERATE_SUCCESS,
	REGENERATE_ERROR,
	GENERATE_START,
	GENERATE_SUCCESS,
	GENERATE_ERROR,
	CLEAR_ERROR,
} from '../sidebarReducer';

/**
 * Arbitrary: generates a non-empty clientId string.
 */
const clientIdArb = fc.string( { minLength: 1, maxLength: 20 } );

/**
 * Arbitrary: generates a non-negative integer for faqCount.
 */
const faqCountArb = fc.nat( { max: 100 } );

/**
 * Arbitrary: generates an error message string.
 */
const errorMessageArb = fc.string( { minLength: 1, maxLength: 100 } );

/**
 * Arbitrary: generates a valid SidebarAction object.
 */
const sidebarActionArb = fc.oneof(
	clientIdArb.map( ( clientId ) => ( { type: BLOCK_DETECTED, clientId } ) ),
	faqCountArb.map( ( faqCount ) => ( { type: META_LOADED, faqCount } ) ),
	clientIdArb.map( ( clientId ) => ( { type: INSERT_SUCCESS, clientId } ) ),
	fc.constant( { type: BLOCK_REMOVED } ),
	fc.constant( { type: CLEAR } ),
	fc.constant( { type: REGENERATE_START } ),
	fc.constant( { type: REGENERATE_SUCCESS } ),
	errorMessageArb.map( ( message ) => ( { type: REGENERATE_ERROR, message } ) ),
	fc.constant( { type: GENERATE_START } ),
	faqCountArb.map( ( faqCount ) => ( { type: GENERATE_SUCCESS, faqCount } ) ),
	errorMessageArb.map( ( message ) => ( { type: GENERATE_ERROR, message } ) ),
	fc.constant( { type: CLEAR_ERROR } )
);

/**
 * Arbitrary: generates a valid BlockInsertState object.
 */
const blockInsertStateArb = fc.record( {
	sidebarState: fc.constantFrom( 'empty', 'has_faqs', 'block_inserted' ),
	activeBlockClientId: fc.option( clientIdArb, { nil: null } ),
	faqCount: faqCountArb,
	isRegenerating: fc.boolean(),
	isGenerating: fc.boolean(),
	error: fc.option( errorMessageArb, { nil: null } ),
} );

/**
 * Arbitrary: generates a BlockInsertState where sidebarState is 'block_inserted'.
 */
const blockInsertedStateArb = fc.record( {
	sidebarState: fc.constant( 'block_inserted' ),
	activeBlockClientId: clientIdArb,
	faqCount: faqCountArb,
	isRegenerating: fc.boolean(),
	isGenerating: fc.boolean(),
	error: fc.option( errorMessageArb, { nil: null } ),
} );

/**
 * Property 1: State Invariant
 *
 * For any sequence of valid actions dispatched to the sidebar reducer,
 * the resulting sidebarState value SHALL always be one of exactly three
 * values: 'empty', 'has_faqs', or 'block_inserted'.
 *
 * **Validates: Requirements 1.1**
 */
describe( 'sidebarReducer — Property 1: State Invariant', () => {
	it( 'sidebarState is always one of empty, has_faqs, or block_inserted after any action sequence', () => {
		const validStates = [ 'empty', 'has_faqs', 'block_inserted' ];

		fc.assert(
			fc.property(
				fc.array( sidebarActionArb, { minLength: 1, maxLength: 30 } ),
				( actions ) => {
					const finalState = actions.reduce(
						( state, action ) => sidebarReducer( state, action ),
						{ ...INITIAL_STATE }
					);
					expect( validStates ).toContain( finalState.sidebarState );
				}
			),
			{ numRuns: 100 }
		);
	} );
} );

/**
 * Property 4: CLEAR Action Resets to Initial State
 *
 * For any valid BlockInsertState (regardless of current sidebarState,
 * activeBlockClientId, faqCount, or error values), dispatching a CLEAR
 * action to the reducer SHALL produce a state with sidebarState: 'empty',
 * activeBlockClientId: null, faqCount: 0, isRegenerating: false,
 * isGenerating: false, and error: null.
 *
 * **Validates: Requirements 2.6, 4.5**
 */
describe( 'sidebarReducer — Property 4: CLEAR Resets to Initial State', () => {
	it( 'CLEAR always resets to initial state regardless of current state', () => {
		fc.assert(
			fc.property(
				blockInsertStateArb,
				( state ) => {
					const result = sidebarReducer( state, { type: CLEAR } );
					expect( result.sidebarState ).toBe( 'empty' );
					expect( result.activeBlockClientId ).toBeNull();
					expect( result.faqCount ).toBe( 0 );
					expect( result.isRegenerating ).toBe( false );
					expect( result.isGenerating ).toBe( false );
					expect( result.error ).toBeNull();
				}
			),
			{ numRuns: 100 }
		);
	} );
} );

/**
 * Property 5: Regeneration Preserves Block-Inserted State
 *
 * For any state where sidebarState is 'block_inserted', dispatching a
 * REGENERATE_SUCCESS action SHALL produce a state where sidebarState
 * remains 'block_inserted' and activeBlockClientId is unchanged.
 *
 * **Validates: Requirements 5.3**
 */
describe( 'sidebarReducer — Property 5: Regeneration Preserves Block-Inserted State', () => {
	it( 'REGENERATE_SUCCESS preserves sidebarState and activeBlockClientId when in block_inserted', () => {
		fc.assert(
			fc.property(
				blockInsertedStateArb,
				( state ) => {
					const result = sidebarReducer( state, { type: REGENERATE_SUCCESS } );
					expect( result.sidebarState ).toBe( 'block_inserted' );
					expect( result.activeBlockClientId ).toBe( state.activeBlockClientId );
				}
			),
			{ numRuns: 100 }
		);
	} );
} );
