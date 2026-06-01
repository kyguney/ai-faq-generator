/**
 * Unit tests for EditorPanel component.
 *
 * @package AiFaqGenerator
 */

import { render, screen, fireEvent, waitFor, act } from '@testing-library/react';
import '@testing-library/jest-dom';

import { useSelect, dispatch } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { EditorPanel } from '../EditorPanel';

// --- Helpers ---

const mockCreateNotice = jest.fn();
const mockRemoveNotice = jest.fn();
const mockSetMeta = jest.fn();
let mockMeta = {};

/**
 * Configure useSelect mock to simulate a post type that supports custom-fields.
 *
 * @param {boolean} supportsCustomFields Whether the post type supports custom-fields.
 * @param {number}  postId              The current post ID.
 */
function setupUseSelect( supportsCustomFields = true, postId = 42 ) {
	useSelect.mockImplementation( ( selector ) => {
		const mockSelect = ( storeName ) => {
			if ( storeName === 'core/editor' ) {
				return {
					getEditedPostAttribute: ( attr ) => {
						if ( attr === 'type' ) return 'post';
						return null;
					},
					getCurrentPostId: () => postId,
				};
			}
			if ( storeName === 'core' ) {
				return {
					getPostType: () => ( {
						supports: {
							'custom-fields': supportsCustomFields,
						},
					} ),
				};
			}
			return {};
		};
		return selector( mockSelect );
	} );
}

/**
 * Set up the global aifaqEditor object and wp.data.
 */
function setupGlobals( postId = 42 ) {
	global.aifaqEditor = {
		ajaxurl: 'http://example.com/wp-admin/admin-ajax.php',
		nonce: 'test-nonce-123',
		postId,
	};
	global.wp = {
		data: {
			select: ( storeName ) => {
				if ( storeName === 'core/editor' ) {
					return {
						getCurrentPostId: () => postId,
					};
				}
				return {};
			},
		},
	};
}

// --- Test Suite ---

describe( 'EditorPanel', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		jest.useFakeTimers();
		mockMeta = {};

		// Configure dispatch mock.
		dispatch.mockReturnValue( {
			createNotice: mockCreateNotice,
			removeNotice: mockRemoveNotice,
		} );

		// Configure useEntityProp mock.
		useEntityProp.mockImplementation( () => [ mockMeta, mockSetMeta ] );

		setupGlobals();
		setupUseSelect( true, 42 );
		global.fetch = jest.fn();
	} );

	afterEach( () => {
		jest.useRealTimers();
		delete global.aifaqEditor;
		delete global.wp;
		delete global.fetch;
	} );

	it( 'renders panel with title "AI FAQ Generator" and button text "Generate FAQs"', () => {
		render( <EditorPanel /> );

		expect( screen.getByText( 'AI FAQ Generator' ) ).toBeInTheDocument();
		expect( screen.getByRole( 'button', { name: 'Generate FAQs' } ) ).toBeInTheDocument();
	} );

	it( 'button click triggers AJAX with correct action, nonce, and post_id', async () => {
		global.fetch.mockResolvedValueOnce( {
			json: () => Promise.resolve( { success: true, data: { faqs: [], count: 0 } } ),
		} );

		render( <EditorPanel /> );

		await act( async () => {
			fireEvent.click( screen.getByRole( 'button', { name: 'Generate FAQs' } ) );
		} );

		expect( global.fetch ).toHaveBeenCalledTimes( 1 );

		const [ url, options ] = global.fetch.mock.calls[ 0 ];
		expect( url ).toBe( 'http://example.com/wp-admin/admin-ajax.php' );
		expect( options.method ).toBe( 'POST' );

		const body = options.body;
		expect( body.get( 'action' ) ).toBe( 'aifaq_generate_faqs' );
		expect( body.get( '_ajax_nonce' ) ).toBe( 'test-nonce-123' );
		expect( body.get( 'post_id' ) ).toBe( '42' );
	} );

	it( 'shows loading state: spinner visible, button disabled, text "Generating..."', async () => {
		// Never resolve the fetch to keep loading state active.
		let resolvePromise;
		global.fetch.mockReturnValueOnce(
			new Promise( ( resolve ) => {
				resolvePromise = resolve;
			} )
		);

		render( <EditorPanel /> );

		await act( async () => {
			fireEvent.click( screen.getByRole( 'button', { name: 'Generate FAQs' } ) );
		} );

		// Button should show "Generating..." and be disabled.
		const button = screen.getByRole( 'button', { name: 'Generating...' } );
		expect( button ).toBeDisabled();
		expect( button ).toHaveAttribute( 'data-is-busy', 'true' );

		// Spinner should be visible.
		expect( screen.getByTestId( 'spinner' ) ).toBeInTheDocument();

		// Clean up: resolve the promise.
		await act( async () => {
			resolvePromise( {
				json: () => Promise.resolve( { success: true, data: { faqs: [], count: 0 } } ),
			} );
		} );
	} );

	it( 'success response updates FAQ count display and dispatches success notice', async () => {
		const fakeFaqs = [
			{ question: 'Q1?', answer: 'A1' },
			{ question: 'Q2?', answer: 'A2' },
			{ question: 'Q3?', answer: 'A3' },
		];

		global.fetch.mockResolvedValueOnce( {
			json: () => Promise.resolve( {
				success: true,
				data: { faqs: fakeFaqs, count: 3 },
			} ),
		} );

		render( <EditorPanel /> );

		await act( async () => {
			fireEvent.click( screen.getByRole( 'button', { name: 'Generate FAQs' } ) );
		} );

		// Should dispatch success notice.
		expect( mockCreateNotice ).toHaveBeenCalledWith(
			'success',
			'3 FAQs generated',
			expect.objectContaining( { isDismissible: true } )
		);

		// Should update meta via setMeta.
		expect( mockSetMeta ).toHaveBeenCalledWith(
			expect.objectContaining( {
				_aifaq_generated_faqs: JSON.stringify( fakeFaqs ),
			} )
		);
	} );

	it( 'error response with message dispatches error notice', async () => {
		global.fetch.mockResolvedValueOnce( {
			json: () => Promise.resolve( {
				success: false,
				data: { message: 'You do not have permission to edit this post.' },
			} ),
		} );

		render( <EditorPanel /> );

		await act( async () => {
			fireEvent.click( screen.getByRole( 'button', { name: 'Generate FAQs' } ) );
		} );

		expect( mockCreateNotice ).toHaveBeenCalledWith(
			'error',
			'You do not have permission to edit this post.',
			expect.objectContaining( { isDismissible: true } )
		);
	} );

	it( 'error response without message dispatches generic error notice', async () => {
		global.fetch.mockResolvedValueOnce( {
			json: () => Promise.resolve( {
				success: false,
				data: {},
			} ),
		} );

		render( <EditorPanel /> );

		await act( async () => {
			fireEvent.click( screen.getByRole( 'button', { name: 'Generate FAQs' } ) );
		} );

		expect( mockCreateNotice ).toHaveBeenCalledWith(
			'error',
			'FAQ generation failed.',
			expect.objectContaining( { isDismissible: true } )
		);
	} );

	it( 'network timeout dispatches server unreachable notice', async () => {
		// Simulate an AbortError (network timeout).
		global.fetch.mockImplementationOnce( () => {
			return Promise.reject( new DOMException( 'The operation was aborted.', 'AbortError' ) );
		} );

		render( <EditorPanel /> );

		await act( async () => {
			fireEvent.click( screen.getByRole( 'button', { name: 'Generate FAQs' } ) );
		} );

		expect( mockCreateNotice ).toHaveBeenCalledWith(
			'error',
			'Could not reach the server. Please try again.',
			expect.objectContaining( { isDismissible: true } )
		);
	} );

	it( 'existing meta displays FAQ count on initial load', () => {
		const existingFaqs = [
			{ question: 'Q1?', answer: 'A1' },
			{ question: 'Q2?', answer: 'A2' },
		];
		mockMeta = { _aifaq_generated_faqs: JSON.stringify( existingFaqs ) };
		useEntityProp.mockImplementation( () => [ mockMeta, mockSetMeta ] );

		render( <EditorPanel /> );

		expect( screen.getByText( '2 FAQs generated' ) ).toBeInTheDocument();
	} );

	it( 'panel does not render when post type lacks custom-fields support', () => {
		setupUseSelect( false, 42 );

		const { container } = render( <EditorPanel /> );

		expect( container ).toBeEmptyDOMElement();
	} );
} );
