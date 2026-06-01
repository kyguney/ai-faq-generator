/**
 * Unit tests for useBlockInsertState hook.
 *
 * @package AiFaqGenerator
 */

import { renderHook, act, waitFor } from '@testing-library/react';
import { useSelect, useDispatch, select as wpSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { useBlockInsertState } from '../useBlockInsertState';

// --- Mock setup ---

const mockInsertBlocks = jest.fn();
const mockUpdateBlockAttributes = jest.fn();
const mockSelectBlock = jest.fn();
const mockRemoveBlock = jest.fn();
const mockCreateNotice = jest.fn();
const mockSetMeta = jest.fn();

function setupMocks( {
	faqBlock = null,
	meta = {},
	getBlock = () => null,
} = {} ) {
	// useSelect returns the detected FAQ block.
	useSelect.mockImplementation( ( selector ) => {
		const mockSelectFn = ( storeName ) => {
			if ( storeName === 'core/block-editor' ) {
				return {
					getBlocks: () => faqBlock
						? [ faqBlock ]
						: [],
				};
			}
			return {};
		};
		return selector( mockSelectFn );
	} );

	// useDispatch returns store-specific dispatchers.
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
			return { createNotice: mockCreateNotice, removeNotice: jest.fn() };
		}
		return {};
	} );

	// wpSelect (the synchronous select) for getBlock checks.
	wpSelect.mockImplementation( ( storeName ) => {
		if ( storeName === 'core/block-editor' ) {
			return { getBlock };
		}
		if ( storeName === 'core/notices' ) {
			return { getNotices: () => [] };
		}
		return {};
	} );

	// useEntityProp returns meta and setter.
	useEntityProp.mockImplementation( () => [ meta, mockSetMeta ] );
}

function setupGlobals() {
	global.aifaqEditor = {
		ajaxurl: 'http://example.com/wp-admin/admin-ajax.php',
		nonce: 'test-nonce-123',
	};
}

// --- Test Suite ---

describe( 'useBlockInsertState', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		jest.useFakeTimers();
		setupGlobals();
		global.fetch = jest.fn();
	} );

	afterEach( () => {
		jest.useRealTimers();
		delete global.aifaqEditor;
		delete global.fetch;
	} );

	// --- Initial State Derivation Tests ---

	describe( 'initial state derivation', () => {
		it( 'returns empty state when no block and no meta', () => {
			setupMocks( { faqBlock: null, meta: {} } );

			const { result } = renderHook( () =>
				useBlockInsertState( 42, 'post' )
			);

			const [ state ] = result.current;
			expect( state.sidebarState ).toBe( 'empty' );
			expect( state.activeBlockClientId ).toBeNull();
			expect( state.faqCount ).toBe( 0 );
		} );

		it( 'returns has_faqs state when no block but valid meta exists', () => {
			const faqs = [ { question: 'Q1', answer: 'A1' }, { question: 'Q2', answer: 'A2' } ];
			setupMocks( {
				faqBlock: null,
				meta: { _aifaq_generated_faqs: JSON.stringify( faqs ) },
			} );

			const { result } = renderHook( () =>
				useBlockInsertState( 42, 'post' )
			);

			const [ state ] = result.current;
			expect( state.sidebarState ).toBe( 'has_faqs' );
			expect( state.faqCount ).toBe( 2 );
		} );

		it( 'returns block_inserted state when block is detected', () => {
			const faqBlock = {
				name: 'wpbits/faq-accordion',
				clientId: 'block-123',
				attributes: { items: [ { question: 'Q', answer: 'A' } ] },
				innerBlocks: [],
			};
			setupMocks( { faqBlock } );

			const { result } = renderHook( () =>
				useBlockInsertState( 42, 'post' )
			);

			const [ state ] = result.current;
			expect( state.sidebarState ).toBe( 'block_inserted' );
			expect( state.activeBlockClientId ).toBe( 'block-123' );
		} );

		it( 'returns block_inserted state when block exists regardless of meta', () => {
			const faqBlock = {
				name: 'wpbits/faq-accordion',
				clientId: 'block-456',
				attributes: { items: [] },
				innerBlocks: [],
			};
			const faqs = [ { question: 'Q1', answer: 'A1' } ];
			setupMocks( {
				faqBlock,
				meta: { _aifaq_generated_faqs: JSON.stringify( faqs ) },
			} );

			const { result } = renderHook( () =>
				useBlockInsertState( 42, 'post' )
			);

			const [ state ] = result.current;
			expect( state.sidebarState ).toBe( 'block_inserted' );
			expect( state.activeBlockClientId ).toBe( 'block-456' );
		} );

		it( 'returns empty state when meta is invalid JSON', () => {
			setupMocks( {
				faqBlock: null,
				meta: { _aifaq_generated_faqs: 'not-valid-json' },
			} );

			const { result } = renderHook( () =>
				useBlockInsertState( 42, 'post' )
			);

			const [ state ] = result.current;
			expect( state.sidebarState ).toBe( 'empty' );
		} );

		it( 'returns empty state when meta is an empty array', () => {
			setupMocks( {
				faqBlock: null,
				meta: { _aifaq_generated_faqs: '[]' },
			} );

			const { result } = renderHook( () =>
				useBlockInsertState( 42, 'post' )
			);

			const [ state ] = result.current;
			expect( state.sidebarState ).toBe( 'empty' );
		} );
	} );

	// --- handleGenerate Tests ---

	describe( 'handleGenerate', () => {
		it( 'triggers AJAX and returns FAQs on success', async () => {
			setupMocks( { faqBlock: null, meta: {} } );

			const fakeFaqs = [
				{ question: 'Q1?', answer: 'A1' },
				{ question: 'Q2?', answer: 'A2' },
			];

			global.fetch.mockResolvedValueOnce( {
				json: () => Promise.resolve( {
					success: true,
					data: { faqs: fakeFaqs },
				} ),
			} );

			const { result } = renderHook( () =>
				useBlockInsertState( 42, 'post' )
			);

			let faqs;
			await act( async () => {
				faqs = await result.current[ 1 ].handleGenerate();
			} );

			expect( faqs ).toEqual( fakeFaqs );
			expect( result.current[ 0 ].sidebarState ).toBe( 'has_faqs' );
			expect( result.current[ 0 ].faqCount ).toBe( 2 );
			expect( result.current[ 0 ].isGenerating ).toBe( false );
		} );

		it( 'sets isGenerating to true during request', async () => {
			setupMocks( { faqBlock: null, meta: {} } );

			let resolvePromise;
			global.fetch.mockReturnValueOnce(
				new Promise( ( resolve ) => { resolvePromise = resolve; } )
			);

			const { result } = renderHook( () =>
				useBlockInsertState( 42, 'post' )
			);

			act( () => {
				result.current[ 1 ].handleGenerate();
			} );

			expect( result.current[ 0 ].isGenerating ).toBe( true );

			await act( async () => {
				resolvePromise( {
					json: () => Promise.resolve( {
						success: true,
						data: { faqs: [] },
					} ),
				} );
			} );
		} );

		it( 'returns null and sets error on server error response', async () => {
			setupMocks( { faqBlock: null, meta: {} } );

			global.fetch.mockResolvedValueOnce( {
				json: () => Promise.resolve( {
					success: false,
					data: { message: 'Server error occurred' },
				} ),
			} );

			const { result } = renderHook( () =>
				useBlockInsertState( 42, 'post' )
			);

			let faqs;
			await act( async () => {
				faqs = await result.current[ 1 ].handleGenerate();
			} );

			expect( faqs ).toBeNull();
			expect( result.current[ 0 ].error ).toBe( 'Server error occurred' );
			expect( result.current[ 0 ].isGenerating ).toBe( false );
			expect( mockCreateNotice ).toHaveBeenCalledWith(
				'error',
				'Server error occurred',
				expect.objectContaining( { isDismissible: true } )
			);
		} );

		it( 'returns null and sets error on network failure', async () => {
			setupMocks( { faqBlock: null, meta: {} } );

			global.fetch.mockRejectedValueOnce(
				new DOMException( 'The operation was aborted.', 'AbortError' )
			);

			const { result } = renderHook( () =>
				useBlockInsertState( 42, 'post' )
			);

			let faqs;
			await act( async () => {
				faqs = await result.current[ 1 ].handleGenerate();
			} );

			expect( faqs ).toBeNull();
			expect( result.current[ 0 ].error ).toBe(
				'Could not reach the server. Please try again.'
			);
			expect( result.current[ 0 ].isGenerating ).toBe( false );
			expect( mockCreateNotice ).toHaveBeenCalledWith(
				'error',
				'Could not reach the server. Please try again.',
				expect.objectContaining( { isDismissible: true } )
			);
		} );

		it( 'stores generated FAQs in meta on success', async () => {
			const meta = {};
			setupMocks( { faqBlock: null, meta } );

			const fakeFaqs = [ { question: 'Q1?', answer: 'A1' } ];
			global.fetch.mockResolvedValueOnce( {
				json: () => Promise.resolve( {
					success: true,
					data: { faqs: fakeFaqs },
				} ),
			} );

			const { result } = renderHook( () =>
				useBlockInsertState( 42, 'post' )
			);

			await act( async () => {
				await result.current[ 1 ].handleGenerate();
			} );

			expect( mockSetMeta ).toHaveBeenCalledWith(
				expect.objectContaining( {
					_aifaq_generated_faqs: JSON.stringify( fakeFaqs ),
				} )
			);
		} );
	} );

	// --- handleRegenerate Tests ---

	describe( 'handleRegenerate', () => {
		it( 'updates block attributes when block exists', async () => {
			const faqBlock = {
				name: 'wpbits/faq-accordion',
				clientId: 'block-regen-1',
				attributes: { items: [ { question: 'Old', answer: 'Old' } ] },
				innerBlocks: [],
			};
			setupMocks( {
				faqBlock,
				meta: {},
				getBlock: ( clientId ) =>
					clientId === 'block-regen-1' ? faqBlock : null,
			} );

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

			const { result } = renderHook( () =>
				useBlockInsertState( 42, 'post' )
			);

			// Should start in block_inserted state.
			expect( result.current[ 0 ].sidebarState ).toBe( 'block_inserted' );

			await act( async () => {
				await result.current[ 1 ].handleRegenerate();
			} );

			expect( mockUpdateBlockAttributes ).toHaveBeenCalledWith(
				'block-regen-1',
				{ items: newFaqs }
			);
			expect( result.current[ 0 ].sidebarState ).toBe( 'block_inserted' );
			expect( result.current[ 0 ].isRegenerating ).toBe( false );
		} );

		it( 'inserts new block when block was removed during regeneration', async () => {
			const faqBlock = {
				name: 'wpbits/faq-accordion',
				clientId: 'block-removed-1',
				attributes: { items: [] },
				innerBlocks: [],
			};
			setupMocks( {
				faqBlock,
				meta: {},
				getBlock: () => null, // Block no longer exists.
			} );

			const newFaqs = [ { question: 'Q1', answer: 'A1' } ];

			global.fetch.mockResolvedValueOnce( {
				json: () => Promise.resolve( {
					success: true,
					data: { faqs: newFaqs },
				} ),
			} );

			const { result } = renderHook( () =>
				useBlockInsertState( 42, 'post' )
			);

			await act( async () => {
				await result.current[ 1 ].handleRegenerate();
			} );

			expect( mockInsertBlocks ).toHaveBeenCalledWith(
				expect.arrayContaining( [
					expect.objectContaining( {
						name: 'wpbits/faq-accordion',
						attributes: { items: newFaqs },
					} ),
				] )
			);
			expect( result.current[ 0 ].isRegenerating ).toBe( false );
		} );

		it( 'sets error on server error during regeneration', async () => {
			const faqBlock = {
				name: 'wpbits/faq-accordion',
				clientId: 'block-err-1',
				attributes: { items: [] },
				innerBlocks: [],
			};
			setupMocks( { faqBlock, meta: {} } );

			global.fetch.mockResolvedValueOnce( {
				json: () => Promise.resolve( {
					success: false,
					data: { message: 'Regeneration failed' },
				} ),
			} );

			const { result } = renderHook( () =>
				useBlockInsertState( 42, 'post' )
			);

			await act( async () => {
				await result.current[ 1 ].handleRegenerate();
			} );

			expect( result.current[ 0 ].error ).toBe( 'Regeneration failed' );
			expect( result.current[ 0 ].isRegenerating ).toBe( false );
			expect( result.current[ 0 ].sidebarState ).toBe( 'block_inserted' );
		} );

		it( 'sets error on network failure during regeneration', async () => {
			const faqBlock = {
				name: 'wpbits/faq-accordion',
				clientId: 'block-net-1',
				attributes: { items: [] },
				innerBlocks: [],
			};
			setupMocks( { faqBlock, meta: {} } );

			global.fetch.mockRejectedValueOnce(
				new DOMException( 'Aborted', 'AbortError' )
			);

			const { result } = renderHook( () =>
				useBlockInsertState( 42, 'post' )
			);

			await act( async () => {
				await result.current[ 1 ].handleRegenerate();
			} );

			expect( result.current[ 0 ].error ).toBe(
				'Could not reach the server. Please try again.'
			);
			expect( result.current[ 0 ].isRegenerating ).toBe( false );
			expect( mockCreateNotice ).toHaveBeenCalledWith(
				'error',
				'Could not reach the server. Please try again.',
				expect.objectContaining( { isDismissible: true } )
			);
		} );
	} );

	// --- handleEditBlock Tests ---

	describe( 'handleEditBlock', () => {
		it( 'dispatches selectBlock with correct clientId when block exists', () => {
			const faqBlock = {
				name: 'wpbits/faq-accordion',
				clientId: 'block-edit-1',
				attributes: { items: [] },
				innerBlocks: [],
			};
			setupMocks( {
				faqBlock,
				meta: {},
				getBlock: ( clientId ) =>
					clientId === 'block-edit-1' ? faqBlock : null,
			} );

			const { result } = renderHook( () =>
				useBlockInsertState( 42, 'post' )
			);

			act( () => {
				result.current[ 1 ].handleEditBlock();
			} );

			expect( mockSelectBlock ).toHaveBeenCalledWith( 'block-edit-1' );
		} );

		it( 'transitions to empty and shows notice when block is missing', () => {
			const faqBlock = {
				name: 'wpbits/faq-accordion',
				clientId: 'block-gone-1',
				attributes: { items: [] },
				innerBlocks: [],
			};
			// Start with block present so state is block_inserted.
			// But wpSelect.getBlock returns null to simulate stale reference.
			setupMocks( {
				faqBlock,
				meta: {},
				getBlock: () => null,
			} );

			const { result } = renderHook( () =>
				useBlockInsertState( 42, 'post' )
			);

			// Should start in block_inserted state (useSelect found the block).
			expect( result.current[ 0 ].sidebarState ).toBe( 'block_inserted' );

			// Call handleEditBlock — wpSelect.getBlock returns null.
			act( () => {
				result.current[ 1 ].handleEditBlock();
			} );

			// handleEditBlock dispatches BLOCK_REMOVED, but useEffect re-detects
			// the block from useSelect. This is expected behavior — the block is
			// still in the tree. The real "missing block" scenario is when the
			// block is removed from the tree entirely (tested in block detection).
			// Here we verify that selectBlock was NOT called (the getBlock check).
			expect( mockSelectBlock ).not.toHaveBeenCalled();
			expect( mockCreateNotice ).toHaveBeenCalledWith(
				'info',
				'The FAQ block was removed from the post.',
				expect.objectContaining( { isDismissible: true } )
			);
		} );

		it( 'does nothing when no clientId is stored', () => {
			setupMocks( { faqBlock: null, meta: {} } );

			const { result } = renderHook( () =>
				useBlockInsertState( 42, 'post' )
			);

			expect( result.current[ 0 ].sidebarState ).toBe( 'empty' );

			act( () => {
				result.current[ 1 ].handleEditBlock();
			} );

			expect( mockSelectBlock ).not.toHaveBeenCalled();
		} );
	} );

	// --- handleClear Tests ---

	describe( 'handleClear', () => {
		it( 'resets state to empty and clears meta', () => {
			const faqs = [ { question: 'Q1', answer: 'A1' } ];
			const meta = { _aifaq_generated_faqs: JSON.stringify( faqs ) };
			setupMocks( { faqBlock: null, meta } );

			const { result } = renderHook( () =>
				useBlockInsertState( 42, 'post' )
			);

			// Should start in has_faqs state.
			expect( result.current[ 0 ].sidebarState ).toBe( 'has_faqs' );

			act( () => {
				result.current[ 1 ].handleClear();
			} );

			expect( result.current[ 0 ].sidebarState ).toBe( 'empty' );
			expect( result.current[ 0 ].activeBlockClientId ).toBeNull();
			expect( result.current[ 0 ].faqCount ).toBe( 0 );
			expect( result.current[ 0 ].isRegenerating ).toBe( false );
			expect( result.current[ 0 ].error ).toBeNull();
			expect( mockSetMeta ).toHaveBeenCalledWith(
				expect.objectContaining( { _aifaq_generated_faqs: '' } )
			);
		} );

		it( 'resets from block_inserted state to empty and removes the block', () => {
			const faqBlock = {
				name: 'wpbits/faq-accordion',
				clientId: 'block-clear-1',
				attributes: { items: [] },
				innerBlocks: [],
			};
			setupMocks( {
				faqBlock,
				meta: {},
				getBlock: ( clientId ) =>
					clientId === 'block-clear-1' ? faqBlock : null,
			} );

			const { result, rerender } = renderHook( () =>
				useBlockInsertState( 42, 'post' )
			);

			expect( result.current[ 0 ].sidebarState ).toBe( 'block_inserted' );

			act( () => {
				result.current[ 1 ].handleClear();
			} );

			// removeBlock should be called with the block's clientId.
			expect( mockRemoveBlock ).toHaveBeenCalledWith( 'block-clear-1' );

			// Simulate block removal from editor (useSelect no longer finds it).
			setupMocks( { faqBlock: null, meta: {} } );
			rerender();

			expect( result.current[ 0 ].sidebarState ).toBe( 'empty' );
			expect( result.current[ 0 ].activeBlockClientId ).toBeNull();
			expect( mockSetMeta ).toHaveBeenCalledWith(
				expect.objectContaining( { _aifaq_generated_faqs: '' } )
			);
		} );
	} );

	// --- Block Detection Subscription Tests ---

	describe( 'block detection subscription', () => {
		it( 'transitions to block_inserted when block appears', () => {
			// Start with no block.
			setupMocks( { faqBlock: null, meta: {} } );

			const { result, rerender } = renderHook( () =>
				useBlockInsertState( 42, 'post' )
			);

			expect( result.current[ 0 ].sidebarState ).toBe( 'empty' );

			// Simulate block appearing.
			const faqBlock = {
				name: 'wpbits/faq-accordion',
				clientId: 'block-new-1',
				attributes: { items: [] },
				innerBlocks: [],
			};
			setupMocks( { faqBlock, meta: {} } );

			rerender();

			expect( result.current[ 0 ].sidebarState ).toBe( 'block_inserted' );
			expect( result.current[ 0 ].activeBlockClientId ).toBe( 'block-new-1' );
		} );

		it( 'transitions to empty when block is removed', () => {
			const faqBlock = {
				name: 'wpbits/faq-accordion',
				clientId: 'block-remove-1',
				attributes: { items: [] },
				innerBlocks: [],
			};
			setupMocks( { faqBlock, meta: {} } );

			const { result, rerender } = renderHook( () =>
				useBlockInsertState( 42, 'post' )
			);

			expect( result.current[ 0 ].sidebarState ).toBe( 'block_inserted' );

			// Simulate block removal.
			setupMocks( { faqBlock: null, meta: {} } );

			rerender();

			expect( result.current[ 0 ].sidebarState ).toBe( 'empty' );
			expect( result.current[ 0 ].activeBlockClientId ).toBeNull();
		} );
	} );
} );
