/**
 * Property-based tests for PreviewModal component.
 *
 * @package AiFaqGenerator
 */

import { render, screen, fireEvent } from '@testing-library/react';
import '@testing-library/jest-dom';
import * as fc from 'fast-check';

import { PreviewModal } from '../PreviewModal';
import { __mockInsertBlocks, dispatch } from '@wordpress/data';

// --- Generators ---

const faqItemArb = fc.record( {
	question: fc.string( { minLength: 1 } ),
	answer: fc.string( { minLength: 1 } ),
} );

const faqListArb = fc.array( faqItemArb, { minLength: 1, maxLength: 20 } );

// --- Setup ---

beforeAll( () => {
	global.aifaqEditor = {
		ajaxurl: '/wp-admin/admin-ajax.php',
		nonce: 'test-nonce',
	};
} );

afterAll( () => {
	delete global.aifaqEditor;
} );

// --- Property 1: FAQ list rendering invariant ---

describe( 'Property 1: FAQ list rendering invariant', () => {
	/**
	 * Validates: Requirements 1.3, 2.1, 2.2, 2.3
	 */
	it( 'renders exactly one question input and one answer textarea per FAQ item', () => {
		fc.assert(
			fc.property( faqListArb, ( faqs ) => {
				const { unmount } = render(
					<PreviewModal
						faqs={ faqs }
						postId={ 1 }
						onClose={ jest.fn() }
						onInsertSuccess={ jest.fn() }
					/>
				);

				const questionInputs = screen.getAllByRole( 'textbox', {
					name: /^Question \d+$/,
				} );
				const answerTextareas = screen.getAllByRole( 'textbox', {
					name: /^Answer \d+$/,
				} );

				// Exactly N question inputs rendered (one per FAQ item)
				expect( questionInputs ).toHaveLength( faqs.length );

				// Exactly N answer textareas rendered (one per FAQ item)
				expect( answerTextareas ).toHaveLength( faqs.length );

				unmount();
			} ),
			{ numRuns: 30 }
		);
	} );

	it( 'displays 1-based index labels matching position in the array', () => {
		fc.assert(
			fc.property( faqListArb, ( faqs ) => {
				const { unmount } = render(
					<PreviewModal
						faqs={ faqs }
						postId={ 1 }
						onClose={ jest.fn() }
						onInsertSuccess={ jest.fn() }
					/>
				);

				// Labels contain 1-based index and order is preserved
				for ( let i = 0; i < faqs.length; i++ ) {
					const questionInput = screen.getByRole( 'textbox', {
						name: `Question ${ i + 1 }`,
					} );
					const answerTextarea = screen.getByRole( 'textbox', {
						name: `Answer ${ i + 1 }`,
					} );

					expect( questionInput ).toBeInTheDocument();
					expect( answerTextarea ).toBeInTheDocument();
				}

				unmount();
			} ),
			{ numRuns: 30 }
		);
	} );

	it( 'displays a FAQ count equal to the array length', () => {
		fc.assert(
			fc.property( faqListArb, ( faqs ) => {
				const { unmount } = render(
					<PreviewModal
						faqs={ faqs }
						postId={ 1 }
						onClose={ jest.fn() }
						onInsertSuccess={ jest.fn() }
					/>
				);

				// FAQ count displayed equals array length
				const expectedText = `${ faqs.length } FAQ${ faqs.length !== 1 ? 's' : '' }`;
				expect( screen.getByText( expectedText ) ).toBeInTheDocument();

				unmount();
			} ),
			{ numRuns: 30 }
		);
	} );

	it( 'preserves the original array order in the rendered output', () => {
		fc.assert(
			fc.property( faqListArb, ( faqs ) => {
				const { unmount } = render(
					<PreviewModal
						faqs={ faqs }
						postId={ 1 }
						onClose={ jest.fn() }
						onInsertSuccess={ jest.fn() }
					/>
				);

				// Order preserved: question at index i has label "Question {i+1}"
				const questionInputs = screen.getAllByRole( 'textbox', {
					name: /^Question \d+$/,
				} );

				for ( let i = 0; i < faqs.length; i++ ) {
					expect( questionInputs[ i ] ).toHaveAttribute(
						'aria-label',
						`Question ${ i + 1 }`
					);
				}

				unmount();
			} ),
			{ numRuns: 30 }
		);
	} );
} );

// --- Property 2: Controlled input state synchronization ---

describe( 'Property 2: Controlled input state synchronization', () => {
	/**
	 * Validates: Requirements 3.3, 3.4
	 */
	it( 'editing a question field immediately reflects the new value at that index', () => {
		fc.assert(
			fc.property(
				faqListArb.chain( ( list ) =>
					fc.tuple(
						fc.constant( list ),
						fc.integer( { min: 0, max: list.length - 1 } ),
						fc.string( { minLength: 0 } )
					)
				),
				( [ faqs, index, newValue ] ) => {
					const { unmount } = render(
						<PreviewModal
							faqs={ faqs }
							postId={ 1 }
							onClose={ jest.fn() }
							onInsertSuccess={ jest.fn() }
						/>
					);

					const questionInput = screen.getByRole( 'textbox', {
						name: `Question ${ index + 1 }`,
					} );

					// Simulate editing the question field
					const { fireEvent } = require( '@testing-library/react' );
					fireEvent.change( questionInput, {
						target: { value: newValue },
					} );

					// Verify the input value reflects the new value
					expect( questionInput ).toHaveValue( newValue );

					unmount();
				}
			),
			{ numRuns: 30 }
		);
	} );

	it( 'editing an answer field immediately reflects the new value at that index', () => {
		fc.assert(
			fc.property(
				faqListArb.chain( ( list ) =>
					fc.tuple(
						fc.constant( list ),
						fc.integer( { min: 0, max: list.length - 1 } ),
						fc.string( { minLength: 0 } )
					)
				),
				( [ faqs, index, newValue ] ) => {
					const { unmount } = render(
						<PreviewModal
							faqs={ faqs }
							postId={ 1 }
							onClose={ jest.fn() }
							onInsertSuccess={ jest.fn() }
						/>
					);

					const answerTextarea = screen.getByRole( 'textbox', {
						name: `Answer ${ index + 1 }`,
					} );

					// Simulate editing the answer field
					const { fireEvent } = require( '@testing-library/react' );
					fireEvent.change( answerTextarea, {
						target: { value: newValue },
					} );

					// Verify the textarea value reflects the new value
					expect( answerTextarea ).toHaveValue( newValue );

					unmount();
				}
			),
			{ numRuns: 30 }
		);
	} );

	it( 'editing a question field does not change other items', () => {
		fc.assert(
			fc.property(
				fc.array( faqItemArb, { minLength: 2, maxLength: 20 } ).chain( ( list ) =>
					fc.tuple(
						fc.constant( list ),
						fc.integer( { min: 0, max: list.length - 1 } ),
						fc.string( { minLength: 0 } )
					)
				),
				( [ faqs, index, newValue ] ) => {
					const { unmount } = render(
						<PreviewModal
							faqs={ faqs }
							postId={ 1 }
							onClose={ jest.fn() }
							onInsertSuccess={ jest.fn() }
						/>
					);

					const { fireEvent } = require( '@testing-library/react' );

					// Simulate editing the question at the target index
					const questionInput = screen.getByRole( 'textbox', {
						name: `Question ${ index + 1 }`,
					} );
					fireEvent.change( questionInput, {
						target: { value: newValue },
					} );

					// Verify all other question and answer fields remain unchanged
					for ( let i = 0; i < faqs.length; i++ ) {
						if ( i !== index ) {
							const otherQuestion = screen.getByRole( 'textbox', {
								name: `Question ${ i + 1 }`,
							} );
							const otherAnswer = screen.getByRole( 'textbox', {
								name: `Answer ${ i + 1 }`,
							} );
							expect( otherQuestion ).toHaveValue( faqs[ i ].question );
							expect( otherAnswer ).toHaveValue( faqs[ i ].answer );
						}
					}

					// Also verify the answer at the edited index is unchanged
					const sameAnswer = screen.getByRole( 'textbox', {
						name: `Answer ${ index + 1 }`,
					} );
					expect( sameAnswer ).toHaveValue( faqs[ index ].answer );

					unmount();
				}
			),
			{ numRuns: 30 }
		);
	} );

	it( 'editing an answer field does not change other items', () => {
		fc.assert(
			fc.property(
				fc.array( faqItemArb, { minLength: 2, maxLength: 20 } ).chain( ( list ) =>
					fc.tuple(
						fc.constant( list ),
						fc.integer( { min: 0, max: list.length - 1 } ),
						fc.string( { minLength: 0 } )
					)
				),
				( [ faqs, index, newValue ] ) => {
					const { unmount } = render(
						<PreviewModal
							faqs={ faqs }
							postId={ 1 }
							onClose={ jest.fn() }
							onInsertSuccess={ jest.fn() }
						/>
					);

					const { fireEvent } = require( '@testing-library/react' );

					// Simulate editing the answer at the target index
					const answerTextarea = screen.getByRole( 'textbox', {
						name: `Answer ${ index + 1 }`,
					} );
					fireEvent.change( answerTextarea, {
						target: { value: newValue },
					} );

					// Verify all other question and answer fields remain unchanged
					for ( let i = 0; i < faqs.length; i++ ) {
						if ( i !== index ) {
							const otherQuestion = screen.getByRole( 'textbox', {
								name: `Question ${ i + 1 }`,
							} );
							const otherAnswer = screen.getByRole( 'textbox', {
								name: `Answer ${ i + 1 }`,
							} );
							expect( otherQuestion ).toHaveValue( faqs[ i ].question );
							expect( otherAnswer ).toHaveValue( faqs[ i ].answer );
						}
					}

					// Also verify the question at the edited index is unchanged
					const sameQuestion = screen.getByRole( 'textbox', {
						name: `Question ${ index + 1 }`,
					} );
					expect( sameQuestion ).toHaveValue( faqs[ index ].question );

					unmount();
				}
			),
			{ numRuns: 30 }
		);
	} );
} );

// --- Property 2: Controlled input state synchronization ---

// (Property 2 tests are in a separate task — placeholder for ordering)

// --- Property 3: Removal reduces list and re-indexes ---

describe( 'Property 3: Removal reduces list and re-indexes', () => {
	/**
	 * Validates: Requirements 4.2, 4.3, 2.4
	 */
	it( 'removing an item at a valid index produces a list of length N-1', () => {
		fc.assert(
			fc.property(
				faqListArb.chain( ( list ) =>
					fc.tuple(
						fc.constant( list ),
						fc.integer( { min: 0, max: list.length - 1 } )
					)
				),
				( [ faqs, indexToRemove ] ) => {
					const originalLength = faqs.length;
					const { unmount } = render(
						<PreviewModal
							faqs={ faqs }
							postId={ 1 }
							onClose={ jest.fn() }
							onInsertSuccess={ jest.fn() }
						/>
					);

					// Click the remove button for the 1-based index
					const removeButton = screen.getByRole( 'button', {
						name: `Remove FAQ ${ indexToRemove + 1 }`,
					} );
					fireEvent.click( removeButton );

					// After removal, list length should be N-1
					const expectedLength = originalLength - 1;
					if ( expectedLength === 0 ) {
						// Empty state — no question inputs should exist
						const inputs = screen.queryAllByRole( 'textbox', {
							name: /^Question \d+$/,
						} );
						expect( inputs ).toHaveLength( 0 );
					} else {
						const questionInputs = screen.getAllByRole( 'textbox', {
							name: /^Question \d+$/,
						} );
						expect( questionInputs ).toHaveLength( expectedLength );
					}

					unmount();
				}
			),
			{ numRuns: 30 }
		);
	} );

	it( 'the removed item does not appear in the resulting list', () => {
		fc.assert(
			fc.property(
				faqListArb.chain( ( list ) =>
					fc.tuple(
						fc.constant( list ),
						fc.integer( { min: 0, max: list.length - 1 } )
					)
				),
				( [ faqs, indexToRemove ] ) => {
					const removedItem = faqs[ indexToRemove ];
					const { unmount } = render(
						<PreviewModal
							faqs={ faqs }
							postId={ 1 }
							onClose={ jest.fn() }
							onInsertSuccess={ jest.fn() }
						/>
					);

					// Click the remove button
					const removeButton = screen.getByRole( 'button', {
						name: `Remove FAQ ${ indexToRemove + 1 }`,
					} );
					fireEvent.click( removeButton );

					// After removal, check that the removed item's question and answer
					// are not present in any remaining inputs at the same combination.
					// We verify by checking all remaining question inputs don't contain
					// the removed question at the same position it was removed from.
					const remainingQuestionInputs = screen.queryAllByRole(
						'textbox',
						{ name: /^Question \d+$/ }
					);
					const remainingAnswerInputs = screen.queryAllByRole(
						'textbox',
						{ name: /^Answer \d+$/ }
					);

					// Build the remaining FAQ list from the DOM
					const remainingFaqs = remainingQuestionInputs.map(
						( input, i ) => ( {
							question: input.value,
							answer: remainingAnswerInputs[ i ].value,
						} )
					);

					// The removed item (as a question+answer pair) should not appear
					// at the same position it was removed from. More precisely,
					// the resulting list should equal the original list with the item
					// at indexToRemove spliced out.
					const expectedFaqs = [
						...faqs.slice( 0, indexToRemove ),
						...faqs.slice( indexToRemove + 1 ),
					];

					expect( remainingFaqs ).toEqual( expectedFaqs );

					unmount();
				}
			),
			{ numRuns: 30 }
		);
	} );

	it( 'remaining items are numbered sequentially from 1 to N-1', () => {
		fc.assert(
			fc.property(
				faqListArb.chain( ( list ) =>
					fc.tuple(
						fc.constant( list ),
						fc.integer( { min: 0, max: list.length - 1 } )
					)
				),
				( [ faqs, indexToRemove ] ) => {
					const { unmount } = render(
						<PreviewModal
							faqs={ faqs }
							postId={ 1 }
							onClose={ jest.fn() }
							onInsertSuccess={ jest.fn() }
						/>
					);

					// Click the remove button
					const removeButton = screen.getByRole( 'button', {
						name: `Remove FAQ ${ indexToRemove + 1 }`,
					} );
					fireEvent.click( removeButton );

					const expectedLength = faqs.length - 1;

					// After removal, remaining items should be numbered 1 to N-1
					for ( let i = 0; i < expectedLength; i++ ) {
						const questionInput = screen.getByRole( 'textbox', {
							name: `Question ${ i + 1 }`,
						} );
						const answerInput = screen.getByRole( 'textbox', {
							name: `Answer ${ i + 1 }`,
						} );
						expect( questionInput ).toBeInTheDocument();
						expect( answerInput ).toBeInTheDocument();
					}

					// Also verify remove buttons are re-indexed
					for ( let i = 0; i < expectedLength; i++ ) {
						const btn = screen.getByRole( 'button', {
							name: `Remove FAQ ${ i + 1 }`,
						} );
						expect( btn ).toBeInTheDocument();
					}

					unmount();
				}
			),
			{ numRuns: 30 }
		);
	} );
} );

// --- Property 4: Block conversion correctness ---

import { faqsToBlocks } from '../PreviewModal';

describe( 'Property 4: Block conversion correctness', () => {
	/**
	 * Validates: Requirements 6.2, 6.3
	 */
	it( 'produces exactly 1 faq-accordion block for any FAQ list', () => {
		fc.assert(
			fc.property( faqListArb, ( faqs ) => {
				const blocks = faqsToBlocks( faqs );
				expect( blocks ).toHaveLength( 1 );
				expect( blocks[ 0 ].name ).toBe( 'wpbits/faq-accordion' );
			} ),
			{ numRuns: 30 }
		);
	} );

	it( 'the faq-accordion block items attribute contains all FAQ items in order', () => {
		fc.assert(
			fc.property( faqListArb, ( faqs ) => {
				const blocks = faqsToBlocks( faqs );
				const items = blocks[ 0 ].attributes.items;

				expect( items ).toHaveLength( faqs.length );

				for ( let i = 0; i < faqs.length; i++ ) {
					expect( items[ i ].question ).toBe( faqs[ i ].question );
					expect( items[ i ].answer ).toBe( faqs[ i ].answer );
				}
			} ),
			{ numRuns: 30 }
		);
	} );

	it( 'preserves FAQ list order in the generated block items', () => {
		fc.assert(
			fc.property( faqListArb, ( faqs ) => {
				const blocks = faqsToBlocks( faqs );
				const items = blocks[ 0 ].attributes.items;

				// Verify order matches
				expect( items ).toEqual(
					faqs.map( ( faq ) => ( {
						question: faq.question,
						answer: faq.answer,
					} ) )
				);
			} ),
			{ numRuns: 30 }
		);
	} );
} );

// --- Property 5: Local state isolation ---

describe( 'Property 5: Local state isolation', () => {
	/**
	 * Validates: Requirements 7.1, 7.2, 1.4
	 *
	 * For any sequence of edits (question changes, answer changes, removals)
	 * performed within the PreviewModal, no post meta or post content SHALL be
	 * modified until the user explicitly clicks "Insert". Closing the modal after
	 * any edit sequence SHALL result in zero external state changes.
	 */

	beforeEach( () => {
		jest.clearAllMocks();
	} );

	// Generator for edit operations
	const editOperationArb = ( maxIndex ) =>
		fc.oneof(
			fc.record( {
				type: fc.constant( 'changeQuestion' ),
				index: fc.integer( { min: 0, max: maxIndex } ),
				value: fc.string( { minLength: 0 } ),
			} ),
			fc.record( {
				type: fc.constant( 'changeAnswer' ),
				index: fc.integer( { min: 0, max: maxIndex } ),
				value: fc.string( { minLength: 0 } ),
			} ),
			fc.record( {
				type: fc.constant( 'remove' ),
				index: fc.integer( { min: 0, max: maxIndex } ),
			} )
		);

	it( 'no external state changes occur during any sequence of edits', () => {
		fc.assert(
			fc.property(
				faqListArb.chain( ( list ) =>
					fc.tuple(
						fc.constant( list ),
						fc.array(
							editOperationArb( list.length - 1 ),
							{ minLength: 1, maxLength: 10 }
						)
					)
				),
				( [ faqs, operations ] ) => {
					const onClose = jest.fn();
					const onInsertSuccess = jest.fn();

					const { unmount } = render(
						<PreviewModal
							faqs={ faqs }
							postId={ 1 }
							onClose={ onClose }
							onInsertSuccess={ onInsertSuccess }
						/>
					);

					// Track current list length for valid index bounds
					let currentLength = faqs.length;

					// Perform the sequence of edit operations
					for ( const op of operations ) {
						if ( currentLength === 0 ) {
							break;
						}

						// Clamp index to current valid range
						const validIndex = op.index % currentLength;

						if ( op.type === 'changeQuestion' ) {
							const input = screen.getByRole( 'textbox', {
								name: `Question ${ validIndex + 1 }`,
							} );
							fireEvent.change( input, {
								target: { value: op.value },
							} );
						} else if ( op.type === 'changeAnswer' ) {
							const textarea = screen.getByRole( 'textbox', {
								name: `Answer ${ validIndex + 1 }`,
							} );
							fireEvent.change( textarea, {
								target: { value: op.value },
							} );
						} else if ( op.type === 'remove' ) {
							const removeBtn = screen.getByRole( 'button', {
								name: `Remove FAQ ${ validIndex + 1 }`,
							} );
							fireEvent.click( removeBtn );
							currentLength--;
						}
					}

					// After all edits, verify no external state was modified
					expect( onInsertSuccess ).not.toHaveBeenCalled();
					expect( __mockInsertBlocks ).not.toHaveBeenCalled();

					unmount();
				}
			),
			{ numRuns: 30 }
		);
	} );

	it( 'closing the modal after edits results in zero external state changes', () => {
		fc.assert(
			fc.property(
				faqListArb.chain( ( list ) =>
					fc.tuple(
						fc.constant( list ),
						fc.array(
							editOperationArb( list.length - 1 ),
							{ minLength: 1, maxLength: 10 }
						)
					)
				),
				( [ faqs, operations ] ) => {
					const onClose = jest.fn();
					const onInsertSuccess = jest.fn();

					const { unmount } = render(
						<PreviewModal
							faqs={ faqs }
							postId={ 1 }
							onClose={ onClose }
							onInsertSuccess={ onInsertSuccess }
						/>
					);

					// Track current list length for valid index bounds
					let currentLength = faqs.length;

					// Perform the sequence of edit operations
					for ( const op of operations ) {
						if ( currentLength === 0 ) {
							break;
						}

						const validIndex = op.index % currentLength;

						if ( op.type === 'changeQuestion' ) {
							const input = screen.getByRole( 'textbox', {
								name: `Question ${ validIndex + 1 }`,
							} );
							fireEvent.change( input, {
								target: { value: op.value },
							} );
						} else if ( op.type === 'changeAnswer' ) {
							const textarea = screen.getByRole( 'textbox', {
								name: `Answer ${ validIndex + 1 }`,
							} );
							fireEvent.change( textarea, {
								target: { value: op.value },
							} );
						} else if ( op.type === 'remove' ) {
							const removeBtn = screen.getByRole( 'button', {
								name: `Remove FAQ ${ validIndex + 1 }`,
							} );
							fireEvent.click( removeBtn );
							currentLength--;
						}
					}

					// Click the close button
					const closeButton = screen.getByRole( 'button', {
						name: 'Close',
					} );
					fireEvent.click( closeButton );

					// Verify onClose WAS called (modal closes)
					expect( onClose ).toHaveBeenCalledTimes( 1 );

					// Verify onInsertSuccess was still NOT called
					expect( onInsertSuccess ).not.toHaveBeenCalled();

					// Verify insertBlocks was NOT called
					expect( __mockInsertBlocks ).not.toHaveBeenCalled();

					unmount();
				}
			),
			{ numRuns: 30 }
		);
	} );
} );


// --- Property 6: Insertion uses final edited state ---

describe( 'Property 6: Insertion uses final edited state', () => {
	/**
	 * Validates: Requirements 7.3
	 */

	beforeEach( () => {
		__mockInsertBlocks.mockClear();
	} );

	it( 'after editing a question, Insert uses the edited value (not the original)', () => {
		fc.assert(
			fc.property(
				fc.array( faqItemArb, { minLength: 2, maxLength: 10 } ).chain( ( list ) =>
					fc.tuple(
						fc.constant( list ),
						fc.integer( { min: 0, max: list.length - 1 } ),
						fc.string( { minLength: 1 } )
					)
				),
				( [ faqs, editIndex, newQuestion ] ) => {
					const onInsertSuccess = jest.fn();
					__mockInsertBlocks.mockClear();

					const { unmount } = render(
						<PreviewModal
							faqs={ faqs }
							postId={ 1 }
							onClose={ jest.fn() }
							onInsertSuccess={ onInsertSuccess }
						/>
					);

					// Edit the question at the generated index
					const questionInput = screen.getByRole( 'textbox', {
						name: `Question ${ editIndex + 1 }`,
					} );
					fireEvent.change( questionInput, {
						target: { value: newQuestion },
					} );

					// Click Insert button
					const insertButton = screen.getByRole( 'button', {
						name: 'Insert',
					} );
					fireEvent.click( insertButton );

					// Build expected FAQ list with the edit applied
					const expectedFaqs = faqs.map( ( faq, i ) =>
						i === editIndex
							? { ...faq, question: newQuestion }
							: faq
					);

					// Verify onInsertSuccess was called with the modified FAQ list and clientId
					expect( onInsertSuccess ).toHaveBeenCalledTimes( 1 );
					expect( onInsertSuccess ).toHaveBeenCalledWith( expectedFaqs, 'mock-client-id' );

					// Verify __mockInsertBlocks was called with blocks reflecting edited values
					expect( __mockInsertBlocks ).toHaveBeenCalledTimes( 1 );
					const insertedBlocks = __mockInsertBlocks.mock.calls[ 0 ][ 0 ];

					// Should be a single faq-accordion block
					expect( insertedBlocks ).toHaveLength( 1 );
					expect( insertedBlocks[ 0 ].name ).toBe( 'wpbits/faq-accordion' );

					// The item at editIndex should have the new question
					expect( insertedBlocks[ 0 ].attributes.items[ editIndex ].question ).toBe( newQuestion );

					unmount();
				}
			),
			{ numRuns: 30 }
		);
	} );

	it( 'after editing an answer, Insert uses the edited value (not the original)', () => {
		fc.assert(
			fc.property(
				fc.array( faqItemArb, { minLength: 2, maxLength: 10 } ).chain( ( list ) =>
					fc.tuple(
						fc.constant( list ),
						fc.integer( { min: 0, max: list.length - 1 } ),
						fc.string( { minLength: 1 } )
					)
				),
				( [ faqs, editIndex, newAnswer ] ) => {
					const onInsertSuccess = jest.fn();
					__mockInsertBlocks.mockClear();

					const { unmount } = render(
						<PreviewModal
							faqs={ faqs }
							postId={ 1 }
							onClose={ jest.fn() }
							onInsertSuccess={ onInsertSuccess }
						/>
					);

					// Edit the answer at the generated index
					const answerTextarea = screen.getByRole( 'textbox', {
						name: `Answer ${ editIndex + 1 }`,
					} );
					fireEvent.change( answerTextarea, {
						target: { value: newAnswer },
					} );

					// Click Insert button
					const insertButton = screen.getByRole( 'button', {
						name: 'Insert',
					} );
					fireEvent.click( insertButton );

					// Build expected FAQ list with the edit applied
					const expectedFaqs = faqs.map( ( faq, i ) =>
						i === editIndex
							? { ...faq, answer: newAnswer }
							: faq
					);

					// Verify onInsertSuccess was called with the modified FAQ list and clientId
					expect( onInsertSuccess ).toHaveBeenCalledTimes( 1 );
					expect( onInsertSuccess ).toHaveBeenCalledWith( expectedFaqs, 'mock-client-id' );

					// Verify __mockInsertBlocks was called with blocks reflecting edited values
					expect( __mockInsertBlocks ).toHaveBeenCalledTimes( 1 );
					const insertedBlocks = __mockInsertBlocks.mock.calls[ 0 ][ 0 ];

					// Should be a single faq-accordion block
					expect( insertedBlocks ).toHaveLength( 1 );
					expect( insertedBlocks[ 0 ].name ).toBe( 'wpbits/faq-accordion' );

					// The item at editIndex should have the new answer
					expect( insertedBlocks[ 0 ].attributes.items[ editIndex ].answer ).toBe( newAnswer );

					unmount();
				}
			),
			{ numRuns: 30 }
		);
	} );

	it( 'after removing an item and editing another, Insert uses the final state', () => {
		fc.assert(
			fc.property(
				fc.array( faqItemArb, { minLength: 3, maxLength: 10 } ).chain( ( list ) =>
					fc.tuple(
						fc.constant( list ),
						fc.integer( { min: 0, max: list.length - 1 } ),
						fc.string( { minLength: 1 } )
					)
				),
				( [ faqs, removeIndex, newQuestion ] ) => {
					const onInsertSuccess = jest.fn();
					__mockInsertBlocks.mockClear();

					const { unmount } = render(
						<PreviewModal
							faqs={ faqs }
							postId={ 1 }
							onClose={ jest.fn() }
							onInsertSuccess={ onInsertSuccess }
						/>
					);

					// Remove the item at removeIndex
					const removeButton = screen.getByRole( 'button', {
						name: `Remove FAQ ${ removeIndex + 1 }`,
					} );
					fireEvent.click( removeButton );

					// After removal, the list has N-1 items. Edit the first remaining item's question.
					const firstQuestionInput = screen.getByRole( 'textbox', {
						name: 'Question 1',
					} );
					fireEvent.change( firstQuestionInput, {
						target: { value: newQuestion },
					} );

					// Click Insert button
					const insertButton = screen.getByRole( 'button', {
						name: 'Insert',
					} );
					fireEvent.click( insertButton );

					// Build expected FAQ list: remove item, then edit first remaining question
					const afterRemoval = [
						...faqs.slice( 0, removeIndex ),
						...faqs.slice( removeIndex + 1 ),
					];
					const expectedFaqs = afterRemoval.map( ( faq, i ) =>
						i === 0
							? { ...faq, question: newQuestion }
							: faq
					);

					// Verify onInsertSuccess was called with the final modified FAQ list
					expect( onInsertSuccess ).toHaveBeenCalledTimes( 1 );
					expect( onInsertSuccess ).toHaveBeenCalledWith( expectedFaqs, 'mock-client-id' );

					// Verify __mockInsertBlocks was called with correct block count
					expect( __mockInsertBlocks ).toHaveBeenCalledTimes( 1 );
					const insertedBlocks = __mockInsertBlocks.mock.calls[ 0 ][ 0 ];
					expect( insertedBlocks ).toHaveLength( 1 );
					expect( insertedBlocks[ 0 ].name ).toBe( 'wpbits/faq-accordion' );
					expect( insertedBlocks[ 0 ].attributes.items ).toHaveLength( expectedFaqs.length );

					// First item should have the edited question
					expect( insertedBlocks[ 0 ].attributes.items[ 0 ].question ).toBe( newQuestion );

					unmount();
				}
			),
			{ numRuns: 30 }
		);
	} );
} );


// --- Property 7: Post meta persistence after insertion ---

describe( 'Property 7: Post meta persistence after insertion', () => {
	/**
	 * Validates: Requirements 7.4
	 */
	it( 'onInsertSuccess is called with the current FAQ list after clicking Insert', () => {
		fc.assert(
			fc.property( faqListArb, ( faqs ) => {
				const onInsertSuccess = jest.fn();
				const { unmount } = render(
					<PreviewModal
						faqs={ faqs }
						postId={ 1 }
						onClose={ jest.fn() }
						onInsertSuccess={ onInsertSuccess }
					/>
				);

				// Click the Insert button
				const insertButton = screen.getByRole( 'button', {
					name: 'Insert',
				} );
				fireEvent.click( insertButton );

				// onInsertSuccess should have been called exactly once
				expect( onInsertSuccess ).toHaveBeenCalledTimes( 1 );

				// It should have been called with the FAQ list and clientId
				expect( onInsertSuccess ).toHaveBeenCalledWith( faqs, 'mock-client-id' );

				onInsertSuccess.mockClear();
				unmount();
			} ),
			{ numRuns: 30 }
		);
	} );

	it( 'the FAQ list passed to onInsertSuccess survives JSON round-trip serialization', () => {
		fc.assert(
			fc.property( faqListArb, ( faqs ) => {
				const onInsertSuccess = jest.fn();
				const { unmount } = render(
					<PreviewModal
						faqs={ faqs }
						postId={ 1 }
						onClose={ jest.fn() }
						onInsertSuccess={ onInsertSuccess }
					/>
				);

				// Click the Insert button
				const insertButton = screen.getByRole( 'button', {
					name: 'Insert',
				} );
				fireEvent.click( insertButton );

				// Verify round-trip JSON serialization produces identical data
				const passedFaqs = onInsertSuccess.mock.calls[ 0 ][ 0 ];
				const roundTripped = JSON.parse( JSON.stringify( passedFaqs ) );
				expect( roundTripped ).toEqual( faqs );

				onInsertSuccess.mockClear();
				unmount();
			} ),
			{ numRuns: 30 }
		);
	} );

	it( 'after edits and removals, onInsertSuccess receives the final edited state for meta persistence', () => {
		fc.assert(
			fc.property(
				fc.array( faqItemArb, { minLength: 2, maxLength: 20 } ).chain( ( list ) =>
					fc.tuple(
						fc.constant( list ),
						fc.integer( { min: 0, max: list.length - 1 } ),
						fc.integer( { min: 0, max: list.length - 1 } ),
						fc.string( { minLength: 1 } )
					)
				),
				( [ faqs, removeIndex, editIndex, newQuestion ] ) => {
					const onInsertSuccess = jest.fn();
					const { unmount } = render(
						<PreviewModal
							faqs={ faqs }
							postId={ 1 }
							onClose={ jest.fn() }
							onInsertSuccess={ onInsertSuccess }
						/>
					);

					// Remove an item first
					const removeButton = screen.getByRole( 'button', {
						name: `Remove FAQ ${ removeIndex + 1 }`,
					} );
					fireEvent.click( removeButton );

					// Compute expected state after removal
					const afterRemoval = [
						...faqs.slice( 0, removeIndex ),
						...faqs.slice( removeIndex + 1 ),
					];

					// If there are remaining items, edit one
					if ( afterRemoval.length > 0 ) {
						const safeEditIndex = editIndex % afterRemoval.length;
						const questionInput = screen.getByRole( 'textbox', {
							name: `Question ${ safeEditIndex + 1 }`,
						} );
						fireEvent.change( questionInput, {
							target: { value: newQuestion },
						} );

						// Update expected state
						afterRemoval[ safeEditIndex ] = {
							...afterRemoval[ safeEditIndex ],
							question: newQuestion,
						};
					}

					// Click Insert
					const insertButton = screen.getByRole( 'button', {
						name: 'Insert',
					} );
					fireEvent.click( insertButton );

					// Verify onInsertSuccess received the final edited state
					expect( onInsertSuccess ).toHaveBeenCalledTimes( 1 );
					const passedFaqs = onInsertSuccess.mock.calls[ 0 ][ 0 ];

					// Round-trip serialization should produce identical data
					const roundTripped = JSON.parse( JSON.stringify( passedFaqs ) );
					expect( roundTripped ).toEqual( afterRemoval );

					onInsertSuccess.mockClear();
					unmount();
				}
			),
			{ numRuns: 30 }
		);
	} );
} );


// --- Property 8: Accessible names for all interactive elements ---

describe( 'Property 8: Accessible names for all interactive elements', () => {
	/**
	 * Validates: Requirements 4.1, 8.7
	 *
	 * For any FAQ list of length N, every interactive element within the
	 * PreviewModal SHALL have an accessible name: each remove button SHALL have
	 * an aria-label containing its 1-based FAQ index, each question input SHALL
	 * have a label, each answer textarea SHALL have a label, and the Regenerate
	 * and Insert buttons SHALL have discernible text content.
	 */

	it( 'each remove button has aria-label "Remove FAQ {i+1}" for every FAQ item', () => {
		fc.assert(
			fc.property( faqListArb, ( faqs ) => {
				const { unmount } = render(
					<PreviewModal
						faqs={ faqs }
						postId={ 1 }
						onClose={ jest.fn() }
						onInsertSuccess={ jest.fn() }
					/>
				);

				for ( let i = 0; i < faqs.length; i++ ) {
					const removeButton = screen.getByRole( 'button', {
						name: `Remove FAQ ${ i + 1 }`,
					} );
					expect( removeButton ).toBeInTheDocument();
				}

				unmount();
			} ),
			{ numRuns: 30 }
		);
	} );

	it( 'each question input has accessible name "Question {i+1}"', () => {
		fc.assert(
			fc.property( faqListArb, ( faqs ) => {
				const { unmount } = render(
					<PreviewModal
						faqs={ faqs }
						postId={ 1 }
						onClose={ jest.fn() }
						onInsertSuccess={ jest.fn() }
					/>
				);

				for ( let i = 0; i < faqs.length; i++ ) {
					const questionInput = screen.getByRole( 'textbox', {
						name: `Question ${ i + 1 }`,
					} );
					expect( questionInput ).toBeInTheDocument();
				}

				unmount();
			} ),
			{ numRuns: 30 }
		);
	} );

	it( 'each answer textarea has accessible name "Answer {i+1}"', () => {
		fc.assert(
			fc.property( faqListArb, ( faqs ) => {
				const { unmount } = render(
					<PreviewModal
						faqs={ faqs }
						postId={ 1 }
						onClose={ jest.fn() }
						onInsertSuccess={ jest.fn() }
					/>
				);

				for ( let i = 0; i < faqs.length; i++ ) {
					const answerTextarea = screen.getByRole( 'textbox', {
						name: `Answer ${ i + 1 }`,
					} );
					expect( answerTextarea ).toBeInTheDocument();
				}

				unmount();
			} ),
			{ numRuns: 30 }
		);
	} );

	it( 'the "Regenerate" button is findable by accessible name', () => {
		fc.assert(
			fc.property( faqListArb, ( faqs ) => {
				const { unmount } = render(
					<PreviewModal
						faqs={ faqs }
						postId={ 1 }
						onClose={ jest.fn() }
						onInsertSuccess={ jest.fn() }
					/>
				);

				const regenerateButton = screen.getByRole( 'button', {
					name: 'Regenerate',
				} );
				expect( regenerateButton ).toBeInTheDocument();

				unmount();
			} ),
			{ numRuns: 30 }
		);
	} );

	it( 'the "Insert" button is findable by accessible name', () => {
		fc.assert(
			fc.property( faqListArb, ( faqs ) => {
				const { unmount } = render(
					<PreviewModal
						faqs={ faqs }
						postId={ 1 }
						onClose={ jest.fn() }
						onInsertSuccess={ jest.fn() }
					/>
				);

				const insertButton = screen.getByRole( 'button', {
					name: 'Insert',
				} );
				expect( insertButton ).toBeInTheDocument();

				unmount();
			} ),
			{ numRuns: 30 }
		);
	} );

	it( 'the modal dialog has accessible name matching the title "Preview Generated FAQs"', () => {
		fc.assert(
			fc.property( faqListArb, ( faqs ) => {
				const { unmount } = render(
					<PreviewModal
						faqs={ faqs }
						postId={ 1 }
						onClose={ jest.fn() }
						onInsertSuccess={ jest.fn() }
					/>
				);

				const dialog = screen.getByRole( 'dialog', {
					name: 'Preview Generated FAQs',
				} );
				expect( dialog ).toBeInTheDocument();

				unmount();
			} ),
			{ numRuns: 30 }
		);
	} );
} );
