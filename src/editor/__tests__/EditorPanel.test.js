/**
 * Unit tests for EditorPanel component (refactored version).
 *
 * Tests the component rendering based on state returned by useBlockInsertState hook.
 * The hook is mocked directly so we can control state and actions without
 * setting up all underlying mocks (useEntityProp, fetch, etc.).
 *
 * @package AiFaqGenerator
 */

import { render, screen, fireEvent } from '@testing-library/react';
import '@testing-library/jest-dom';

import { useSelect } from '@wordpress/data';
import { EditorPanel } from '../EditorPanel';
import { useBlockInsertState } from '../useBlockInsertState';

// Mock the useBlockInsertState hook.
jest.mock( '../useBlockInsertState' );

// --- Helpers ---

const mockActions = {
	handleGenerate: jest.fn().mockResolvedValue( null ),
	handleInsertSuccess: jest.fn(),
	handleRegenerate: jest.fn().mockResolvedValue( undefined ),
	handleEditBlock: jest.fn(),
	handleClear: jest.fn(),
};

/**
 * Create a mock state object with sensible defaults.
 *
 * @param {Object} overrides Properties to override.
 * @return {Object} Mock state.
 */
function createMockState( overrides = {} ) {
	return {
		sidebarState: 'empty',
		activeBlockClientId: null,
		faqCount: 0,
		isGenerating: false,
		isRegenerating: false,
		error: null,
		...overrides,
	};
}

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

// --- Test Suite ---

describe( 'EditorPanel', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		setupUseSelect( true, 42 );
		useBlockInsertState.mockReturnValue( [ createMockState(), mockActions ] );
	} );

	describe( 'empty state', () => {
		it( 'renders only the "Generate FAQs" button', () => {
			useBlockInsertState.mockReturnValue( [
				createMockState( { sidebarState: 'empty' } ),
				mockActions,
			] );

			render( <EditorPanel /> );

			expect( screen.getByRole( 'button', { name: 'Generate FAQs' } ) ).toBeInTheDocument();
			expect( screen.queryByText( /FAQs generated/ ) ).not.toBeInTheDocument();
			expect( screen.queryByRole( 'button', { name: 'Edit Block' } ) ).not.toBeInTheDocument();
			expect( screen.queryByRole( 'button', { name: 'Regenerate' } ) ).not.toBeInTheDocument();
			expect( screen.queryByRole( 'button', { name: /Clear/ } ) ).not.toBeInTheDocument();
		} );
	} );

	describe( 'has_faqs state', () => {
		it( 'renders FAQ count and Generate button', () => {
			useBlockInsertState.mockReturnValue( [
				createMockState( { sidebarState: 'has_faqs', faqCount: 3 } ),
				mockActions,
			] );

			render( <EditorPanel /> );

			expect( screen.getByText( '3 FAQs generated' ) ).toBeInTheDocument();
			expect( screen.getByRole( 'button', { name: 'Generate FAQs' } ) ).toBeInTheDocument();
			expect( screen.queryByRole( 'button', { name: 'Edit Block' } ) ).not.toBeInTheDocument();
			expect( screen.queryByRole( 'button', { name: /Clear/ } ) ).not.toBeInTheDocument();
		} );
	} );

	describe( 'block_inserted state', () => {
		it( 'renders success text, Generate FAQs button (no items), and Edit Block', () => {
			useBlockInsertState.mockReturnValue( [
				createMockState( {
					sidebarState: 'block_inserted',
					activeBlockClientId: 'abc-123',
					blockHasItems: false,
				} ),
				mockActions,
			] );

			render( <EditorPanel /> );

			expect( screen.getByText( '1 FAQ Block inserted' ) ).toBeInTheDocument();
			expect( screen.getByRole( 'button', { name: 'Generate FAQs' } ) ).toBeInTheDocument();
			expect( screen.getByRole( 'button', { name: 'Edit Block' } ) ).toBeInTheDocument();
			expect( screen.queryByRole( 'button', { name: /Clear/ } ) ).not.toBeInTheDocument();
		} );

		it( 'renders Regenerate button when block has items', () => {
			useBlockInsertState.mockReturnValue( [
				createMockState( {
					sidebarState: 'block_inserted',
					activeBlockClientId: 'abc-123',
					blockHasItems: true,
				} ),
				mockActions,
			] );

			render( <EditorPanel /> );

			expect( screen.getByText( '1 FAQ Block inserted' ) ).toBeInTheDocument();
			expect( screen.getByRole( 'button', { name: 'Regenerate' } ) ).toBeInTheDocument();
			expect( screen.getByRole( 'button', { name: 'Edit Block' } ) ).toBeInTheDocument();
			expect( screen.queryByRole( 'button', { name: 'Generate FAQs' } ) ).not.toBeInTheDocument();
		} );
	} );

	describe( 'loading states', () => {
		it( 'disables Generate button and shows "Generating..." when isGenerating is true', () => {
			useBlockInsertState.mockReturnValue( [
				createMockState( { sidebarState: 'empty', isGenerating: true } ),
				mockActions,
			] );

			render( <EditorPanel /> );

			const button = screen.getByRole( 'button', { name: 'Generating...' } );
			expect( button ).toBeDisabled();
			expect( button ).toHaveAttribute( 'data-is-busy', 'true' );
			expect( screen.getByTestId( 'spinner' ) ).toBeInTheDocument();
		} );

		it( 'disables all buttons and shows "Generating..." when isRegenerating is true', () => {
			useBlockInsertState.mockReturnValue( [
				createMockState( {
					sidebarState: 'block_inserted',
					activeBlockClientId: 'abc-123',
					isRegenerating: true,
				} ),
				mockActions,
			] );

			render( <EditorPanel /> );

			const regenButton = screen.getByRole( 'button', { name: 'Generating...' } );
			expect( regenButton ).toBeDisabled();
			expect( regenButton ).toHaveAttribute( 'data-is-busy', 'true' );

			expect( screen.getByRole( 'button', { name: 'Edit Block' } ) ).toBeDisabled();
		} );

		it( 'disables Generate button in has_faqs state when isGenerating is true', () => {
			useBlockInsertState.mockReturnValue( [
				createMockState( {
					sidebarState: 'has_faqs',
					faqCount: 5,
					isGenerating: true,
				} ),
				mockActions,
			] );

			render( <EditorPanel /> );

			expect( screen.getByRole( 'button', { name: 'Generating...' } ) ).toBeDisabled();
		} );
	} );

	describe( 'error display', () => {
		it( 'renders error message when state.error is set', () => {
			useBlockInsertState.mockReturnValue( [
				createMockState( {
					sidebarState: 'empty',
					error: 'Something went wrong',
				} ),
				mockActions,
			] );

			render( <EditorPanel /> );

			expect( screen.getByText( 'Something went wrong' ) ).toBeInTheDocument();
		} );

		it( 'does not render error paragraph when state.error is null', () => {
			useBlockInsertState.mockReturnValue( [
				createMockState( { sidebarState: 'empty', error: null } ),
				mockActions,
			] );

			render( <EditorPanel /> );

			expect( screen.queryByText( 'Something went wrong' ) ).not.toBeInTheDocument();
		} );
	} );

	describe( 'post type support', () => {
		it( 'does not render panel when post type lacks custom-fields support', () => {
			setupUseSelect( false, 42 );

			const { container } = render( <EditorPanel /> );

			expect( container ).toBeEmptyDOMElement();
		} );
	} );

	describe( 'button click handlers', () => {
		it( 'calls handleGenerate when Generate FAQs button is clicked', () => {
			useBlockInsertState.mockReturnValue( [
				createMockState( { sidebarState: 'empty' } ),
				mockActions,
			] );

			render( <EditorPanel /> );

			fireEvent.click( screen.getByRole( 'button', { name: 'Generate FAQs' } ) );

			expect( mockActions.handleGenerate ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'calls handleEditBlock when Edit Block button is clicked', () => {
			useBlockInsertState.mockReturnValue( [
				createMockState( {
					sidebarState: 'block_inserted',
					activeBlockClientId: 'abc-123',
				} ),
				mockActions,
			] );

			render( <EditorPanel /> );

			fireEvent.click( screen.getByRole( 'button', { name: 'Edit Block' } ) );

			expect( mockActions.handleEditBlock ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'calls handleRegenerate when Generate FAQs button is clicked in block_inserted state', () => {
			useBlockInsertState.mockReturnValue( [
				createMockState( {
					sidebarState: 'block_inserted',
					activeBlockClientId: 'abc-123',
				} ),
				mockActions,
			] );

			render( <EditorPanel /> );

			fireEvent.click( screen.getByRole( 'button', { name: 'Generate FAQs' } ) );

			expect( mockActions.handleRegenerate ).toHaveBeenCalledTimes( 1 );
		} );
	} );
} );
