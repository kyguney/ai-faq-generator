/**
 * Integration tests for EditorPanel — full flow scenarios.
 *
 * Tests the complete component with real hook logic (useBlockInsertState is NOT mocked).
 * Instead, we mock the underlying WordPress dependencies.
 *
 * @package AiFaqGenerator
 */

import { render, screen, fireEvent, waitFor, act } from '@testing-library/react';
import '@testing-library/jest-dom';

import { useSelect, useDispatch, select, dispatch } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { EditorPanel } from '../EditorPanel';

// --- Mock function references ---

const mockInsertBlocks = jest.fn();
const mockUpdateBlockAttributes = jest.fn();
const mockSelectBlock = jest.fn();
const mockRemoveBlock = jest.fn();
const mockCreateNotice = jest.fn();
const mockRemoveNotice = jest.fn();
const mockSetMeta = jest.fn();

let mockMeta = {};
let mockBlocks = [];

/**
 * Configure useSelect to handle both EditorPanel selectors and the hook's block detection.
 *
 * The useSelect mock is called with a selector function. We provide a mock `select` function
 * that returns appropriate store objects depending on the store name.
 */
function setupUseSelect() {
	useSelect.mockImplementation( ( selectorFn ) => {
		const mockSelectFn = ( storeName ) => {
			if ( storeName === 'core/editor' ) {
				return {
					getEditedPostAttribute: ( attr ) => {
						if ( attr === 'type' ) return 'post';
						return null;
					},
					getCurrentPostId: () => 42,
				};
			}
			if ( storeName === 'core' ) {
				return {
					getPostType: () => ( {
						supports: { 'custom-fields': true },
					} ),
				};
			}
			if ( storeName === 'core/block-editor' ) {
				return {
					getBlocks: () => mockBlocks,
				};
			}
			return {};
		};
		return selectorFn( mockSelectFn );
	} );
}

/**
 * Configure useDispatch to return mock functions for block-editor and notices stores.
 */
function setupUseDispatch() {
	useDispatch.mockImplementation( ( storeName ) => {
		if ( storeName === 'core/block-editor' ) {
			return {
				insertBlocks: mockInsertBlocks,
				updateBlockAttributes: mockUpdateBlockAttributes,
				selectBlock: mockSelectBlock,
				removeBlock: mockRemoveBlock,
			};
		}
		if ( storeName === 'core/notices' ) {
			return {
				createNotice: mockCreateNotice,
				removeNotice: mockRemoveNotice,
			};
		}
		return {};
	} );
}

/**
 * Configure the synchronous `select` (wpSelect) used in async callbacks.
 */
function setupSelect() {
	select.mockImplementation( ( storeName ) => {
		if ( storeName === 'core/block-editor' ) {
			return {
				getBlock: ( clientId ) => {
					// Find the block in mockBlocks by clientId.
					const findBlock = ( blocks, id ) => {
						for ( const block of blocks ) {
							if ( block.clientId === id ) return block;
							if ( block.innerBlocks?.length ) {
								const found = findBlock( block.innerBlocks, id );
								if ( found ) return found;
							}
						}
						return null;
					};
					return findBlock( mockBlocks, clientId );
				},
				getBlocks: () => mockBlocks,
			};
		}
		if ( storeName === 'core/notices' ) {
			return {
				getNotices: () => [],
			};
		}
		return {};
	} );
}

/**
 * Configure the synchronous `dispatch` (wpDispatch) used for notice removal.
 */
function setupDispatch() {
	dispatch.mockImplementation( ( storeName ) => {
		if ( storeName === 'core/block-editor' ) {
			return {
				insertBlocks: mockInsertBlocks,
			};
		}
		if ( storeName === 'core/notices' ) {
			return {
				createNotice: mockCreateNotice,
				removeNotice: mockRemoveNotice,
			};
		}
		return {};
	} );
}

// --- Test Suite ---

describe( 'EditorPanel Integration Tests', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		jest.useFakeTimers();

		mockMeta = {};
		mockBlocks = [];

		setupUseSelect();
		setupUseDispatch();
		setupSelect();
		setupDispatch();

		useEntityProp.mockImplementation( () => [ mockMeta, mockSetMeta ] );

		global.aifaqEditor = {
			ajaxurl: 'http://example.com/wp-admin/admin-ajax.php',
			nonce: 'test-nonce-123',
			postId: 42,
		};
		global.fetch = jest.fn();
	} );

	afterEach( () => {
		jest.useRealTimers();
		delete global.aifaqEditor;
		delete global.fetch;
	} );

	describe( 'Generate → Preview → Insert → block_inserted flow', () => {
		it( 'completes the full generate-preview-insert cycle and transitions to block_inserted', async () => {
			const fakeFaqs = [
				{ question: 'What is AI?', answer: 'Artificial Intelligence.' },
				{ question: 'How does it work?', answer: 'Machine learning.' },
			];

			// Mock fetch to return successful generation response.
			global.fetch.mockResolvedValueOnce( {
				json: () => Promise.resolve( {
					success: true,
					data: { faqs: fakeFaqs },
				} ),
			} );

			render( <EditorPanel /> );

			// Start in empty state — only "Generate FAQs" button visible.
			expect( screen.getByRole( 'button', { name: 'Generate FAQs' } ) ).toBeInTheDocument();
			expect( screen.queryByText( '1 FAQ Block inserted' ) ).not.toBeInTheDocument();

			// Click "Generate FAQs".
			await act( async () => {
				fireEvent.click( screen.getByRole( 'button', { name: 'Generate FAQs' } ) );
			} );

			// Modal should open with preview.
			expect( screen.getByTestId( 'modal' ) ).toBeInTheDocument();
			expect( screen.getByText( 'Preview Generated FAQs' ) ).toBeInTheDocument();

			// Simulate clicking "Insert" in the modal.
			// The PreviewModal's handleInsert calls dispatch('core/block-editor').insertBlocks
			// and then calls onInsertSuccess with the faqs and clientId.
			const insertButton = screen.getByRole( 'button', { name: 'Insert' } );
			expect( insertButton ).toBeInTheDocument();

			// Mock insertBlocks for the PreviewModal's dispatch call.
			mockInsertBlocks.mockReturnValueOnce( undefined );

			await act( async () => {
				fireEvent.click( insertButton );
			} );

			// After insert, state should transition to block_inserted.
			expect( screen.getByText( '1 FAQ Block inserted' ) ).toBeInTheDocument();

			// Modal should be closed.
			expect( screen.queryByTestId( 'modal' ) ).not.toBeInTheDocument();

			// Meta should be cleared.
			expect( mockSetMeta ).toHaveBeenCalledWith(
				expect.objectContaining( { _aifaq_generated_faqs: '' } )
			);
		} );
	} );

	describe( 'Regenerate in block_inserted state', () => {
		it( 'updates block attributes without opening modal', async () => {
			const existingBlock = {
				name: 'wpbits/faq-accordion',
				clientId: 'existing-block-123',
				attributes: { items: [ { question: 'Old Q', answer: 'Old A' } ] },
				innerBlocks: [],
			};
			mockBlocks = [ existingBlock ];

			// Re-setup mocks with the block present.
			setupUseSelect();
			setupSelect();

			render( <EditorPanel /> );

			// Should be in block_inserted state.
			expect( screen.getByText( '1 FAQ Block inserted' ) ).toBeInTheDocument();

			// Regenerate button should be visible (block has items).
			const regenerateButton = screen.getByRole( 'button', { name: 'Regenerate' } );
			expect( regenerateButton ).toBeInTheDocument();

			// Mock fetch for regeneration.
			const newFaqs = [
				{ question: 'New Q1', answer: 'New A1' },
				{ question: 'New Q2', answer: 'New A2' },
			];
			global.fetch.mockResolvedValueOnce( {
				json: () => Promise.resolve( {
					success: true,
					data: { faqs: newFaqs },
				} ),
			} );

			// Click "Regenerate".
			await act( async () => {
				fireEvent.click( regenerateButton );
			} );

			// updateBlockAttributes should be called with new items.
			expect( mockUpdateBlockAttributes ).toHaveBeenCalledWith(
				'existing-block-123',
				{ items: newFaqs }
			);

			// Modal should NOT open.
			expect( screen.queryByTestId( 'modal' ) ).not.toBeInTheDocument();

			// State should remain block_inserted.
			expect( screen.getByText( '1 FAQ Block inserted' ) ).toBeInTheDocument();
		} );
	} );

	describe( 'Block removed externally transitions to empty and clears meta', () => {
		it( 'transitions to empty and clears meta when block is removed from editor', () => {
			const existingBlock = {
				name: 'wpbits/faq-accordion',
				clientId: 'removable-block',
				attributes: { items: [ { question: 'Q', answer: 'A' } ] },
				innerBlocks: [],
			};
			mockBlocks = [ existingBlock ];
			setupUseSelect();
			setupSelect();

			const { rerender } = render( <EditorPanel /> );

			// Should be in block_inserted state.
			expect( screen.getByText( '1 FAQ Block inserted' ) ).toBeInTheDocument();

			// Simulate block removal by clearing mockBlocks and re-rendering.
			mockBlocks = [];
			setupUseSelect();
			setupSelect();

			rerender( <EditorPanel /> );

			// Should transition to empty state.
			expect( screen.getByRole( 'button', { name: 'Generate FAQs' } ) ).toBeInTheDocument();
			expect( screen.queryByText( '1 FAQ Block inserted' ) ).not.toBeInTheDocument();

			// Meta should be cleared automatically.
			expect( mockSetMeta ).toHaveBeenCalledWith(
				expect.objectContaining( { _aifaq_generated_faqs: '' } )
			);
		} );
	} );
} );
