/**
 * Unit tests for PreviewModal component.
 *
 * @package AiFaqGenerator
 */

import { render, screen, fireEvent, waitFor, act } from '@testing-library/react';
import '@testing-library/jest-dom';

import { PreviewModal } from '../PreviewModal';
import { __mockInsertBlocks, dispatch } from '@wordpress/data';

// --- Helpers ---

const defaultFaqs = [
	{ question: 'What is WordPress?', answer: 'A content management system.' },
	{ question: 'How to install plugins?', answer: 'Go to Plugins > Add New.' },
	{ question: 'What is Gutenberg?', answer: 'The WordPress block editor.' },
];

function renderModal( props = {} ) {
	const defaultProps = {
		faqs: defaultFaqs,
		postId: 42,
		onClose: jest.fn(),
		onInsertSuccess: jest.fn(),
	};
	return { ...render( <PreviewModal { ...defaultProps } { ...props } /> ), props: { ...defaultProps, ...props } };
}

// --- Test Suite ---

describe( 'PreviewModal', () => {
	beforeEach( () => {
		jest.clearAllMocks();

		global.aifaqEditor = {
			ajaxurl: 'http://example.com/wp-admin/admin-ajax.php',
			nonce: 'test-nonce-123',
		};

		global.fetch = jest.fn();

		dispatch.mockImplementation( ( storeName ) => {
			if ( storeName === 'core/block-editor' ) {
				return { insertBlocks: __mockInsertBlocks };
			}
			return {
				createNotice: jest.fn(),
				removeNotice: jest.fn(),
			};
		} );
	} );

	afterEach( () => {
		delete global.aifaqEditor;
		delete global.fetch;
	} );

	it( 'renders correct title, FAQ count, and all FAQ items', () => {
		renderModal();

		// Title
		expect( screen.getByText( 'Preview Generated FAQs' ) ).toBeInTheDocument();

		// FAQ count
		expect( screen.getByText( '3 FAQs' ) ).toBeInTheDocument();

		// All question inputs rendered with correct labels
		expect( screen.getByLabelText( 'Question 1' ) ).toHaveValue( 'What is WordPress?' );
		expect( screen.getByLabelText( 'Question 2' ) ).toHaveValue( 'How to install plugins?' );
		expect( screen.getByLabelText( 'Question 3' ) ).toHaveValue( 'What is Gutenberg?' );

		// All answer textareas rendered with correct labels
		expect( screen.getByLabelText( 'Answer 1' ) ).toHaveValue( 'A content management system.' );
		expect( screen.getByLabelText( 'Answer 2' ) ).toHaveValue( 'Go to Plugins > Add New.' );
		expect( screen.getByLabelText( 'Answer 3' ) ).toHaveValue( 'The WordPress block editor.' );
	} );

	it( 'edit question/answer updates local state', () => {
		renderModal();

		const questionInput = screen.getByLabelText( 'Question 1' );
		fireEvent.change( questionInput, { target: { value: 'Updated question?' } } );
		expect( questionInput ).toHaveValue( 'Updated question?' );

		const answerTextarea = screen.getByLabelText( 'Answer 2' );
		fireEvent.change( answerTextarea, { target: { value: 'Updated answer.' } } );
		expect( answerTextarea ).toHaveValue( 'Updated answer.' );

		// Other fields remain unchanged
		expect( screen.getByLabelText( 'Question 2' ) ).toHaveValue( 'How to install plugins?' );
		expect( screen.getByLabelText( 'Answer 1' ) ).toHaveValue( 'A content management system.' );
	} );

	it( 'remove button removes item and re-indexes', () => {
		renderModal();

		// Remove the second FAQ item
		const removeButton = screen.getByLabelText( 'Remove FAQ 2' );
		fireEvent.click( removeButton );

		// Count should update
		expect( screen.getByText( '2 FAQs' ) ).toBeInTheDocument();

		// Remaining items should be re-indexed
		expect( screen.getByLabelText( 'Question 1' ) ).toHaveValue( 'What is WordPress?' );
		expect( screen.getByLabelText( 'Question 2' ) ).toHaveValue( 'What is Gutenberg?' );

		// The removed item should not be present
		expect( screen.queryByDisplayValue( 'How to install plugins?' ) ).not.toBeInTheDocument();
	} );

	it( 'empty state shown when all items removed', () => {
		renderModal( { faqs: [ { question: 'Only Q?', answer: 'Only A.' } ] } );

		// Remove the single item
		fireEvent.click( screen.getByLabelText( 'Remove FAQ 1' ) );

		// Empty state message
		expect( screen.getByText( /No FAQs available/ ) ).toBeInTheDocument();

		// Count should show 0
		expect( screen.getByText( '0 FAQs' ) ).toBeInTheDocument();
	} );

	it( 'insert button disabled when list is empty', () => {
		renderModal( { faqs: [] } );

		const insertButton = screen.getByRole( 'button', { name: 'Insert' } );
		expect( insertButton ).toBeDisabled();
	} );

	it( 'regenerate triggers AJAX and replaces list on success', async () => {
		const newFaqs = [
			{ question: 'New Q1?', answer: 'New A1.' },
			{ question: 'New Q2?', answer: 'New A2.' },
		];

		global.fetch.mockResolvedValueOnce( {
			json: () => Promise.resolve( {
				success: true,
				data: { faqs: newFaqs },
			} ),
		} );

		renderModal();

		await act( async () => {
			fireEvent.click( screen.getByRole( 'button', { name: 'Regenerate' } ) );
		} );

		// Verify AJAX was called
		expect( global.fetch ).toHaveBeenCalledTimes( 1 );
		const [ url, options ] = global.fetch.mock.calls[ 0 ];
		expect( url ).toBe( 'http://example.com/wp-admin/admin-ajax.php' );
		expect( options.body.get( 'action' ) ).toBe( 'aifaq_generate_faqs' );

		// List should be replaced with new FAQs
		expect( screen.getByText( '2 FAQs' ) ).toBeInTheDocument();
		expect( screen.getByLabelText( 'Question 1' ) ).toHaveValue( 'New Q1?' );
		expect( screen.getByLabelText( 'Answer 2' ) ).toHaveValue( 'New A2.' );

		// Old items should be gone
		expect( screen.queryByDisplayValue( 'What is WordPress?' ) ).not.toBeInTheDocument();
	} );

	it( 'regenerate error shows inline message, retains list', async () => {
		global.fetch.mockResolvedValueOnce( {
			json: () => Promise.resolve( {
				success: false,
				data: { message: 'Server error occurred.' },
			} ),
		} );

		renderModal();

		await act( async () => {
			fireEvent.click( screen.getByRole( 'button', { name: 'Regenerate' } ) );
		} );

		// Error message should be displayed inline
		expect( screen.getByText( 'Server error occurred.' ) ).toBeInTheDocument();

		// Original list should be retained
		expect( screen.getByText( '3 FAQs' ) ).toBeInTheDocument();
		expect( screen.getByLabelText( 'Question 1' ) ).toHaveValue( 'What is WordPress?' );
		expect( screen.getByLabelText( 'Question 3' ) ).toHaveValue( 'What is Gutenberg?' );
	} );

	it( 'insert converts FAQs to a single faq-accordion block and calls onInsertSuccess', () => {
		const onInsertSuccess = jest.fn();
		renderModal( { onInsertSuccess } );

		fireEvent.click( screen.getByRole( 'button', { name: 'Insert' } ) );

		// dispatch should be called with 'core/block-editor'
		expect( dispatch ).toHaveBeenCalledWith( 'core/block-editor' );

		// insertBlocks should be called with the correct blocks
		expect( __mockInsertBlocks ).toHaveBeenCalledTimes( 1 );
		const blocks = __mockInsertBlocks.mock.calls[ 0 ][ 0 ];

		// All FAQs should be in a single faq-accordion block
		expect( blocks ).toHaveLength( 1 );
		expect( blocks[ 0 ].name ).toBe( 'wpbits/faq-accordion' );
		expect( blocks[ 0 ].attributes.items ).toEqual( [
			{ question: 'What is WordPress?', answer: 'A content management system.' },
			{ question: 'How to install plugins?', answer: 'Go to Plugins > Add New.' },
			{ question: 'What is Gutenberg?', answer: 'The WordPress block editor.' },
		] );

		// onInsertSuccess should be called with the FAQ list
		expect( onInsertSuccess ).toHaveBeenCalledWith( defaultFaqs );
	} );

	it( 'close button calls onClose without side effects', () => {
		const onClose = jest.fn();
		const onInsertSuccess = jest.fn();
		renderModal( { onClose, onInsertSuccess } );

		// Click the close button
		fireEvent.click( screen.getByLabelText( 'Close' ) );

		// onClose should be called
		expect( onClose ).toHaveBeenCalledTimes( 1 );

		// No blocks inserted, no onInsertSuccess called
		expect( __mockInsertBlocks ).not.toHaveBeenCalled();
		expect( onInsertSuccess ).not.toHaveBeenCalled();
	} );

	it( 'loading state during regeneration disables inputs and buttons', async () => {
		// Use a promise that we control to keep the loading state active
		let resolvePromise;
		global.fetch.mockReturnValueOnce(
			new Promise( ( resolve ) => {
				resolvePromise = resolve;
			} )
		);

		renderModal();

		await act( async () => {
			fireEvent.click( screen.getByRole( 'button', { name: 'Regenerate' } ) );
		} );

		// Spinner should be visible
		expect( screen.getByTestId( 'spinner' ) ).toBeInTheDocument();

		// Buttons should be disabled
		expect( screen.getByRole( 'button', { name: 'Regenerate' } ) ).toBeDisabled();
		expect( screen.getByRole( 'button', { name: 'Insert' } ) ).toBeDisabled();

		// Inputs should be disabled
		expect( screen.getByLabelText( 'Question 1' ) ).toBeDisabled();
		expect( screen.getByLabelText( 'Answer 1' ) ).toBeDisabled();
		expect( screen.getByLabelText( 'Question 2' ) ).toBeDisabled();
		expect( screen.getByLabelText( 'Answer 3' ) ).toBeDisabled();

		// Remove buttons should be disabled
		expect( screen.getByLabelText( 'Remove FAQ 1' ) ).toBeDisabled();

		// Clean up: resolve the promise
		await act( async () => {
			resolvePromise( {
				json: () => Promise.resolve( { success: true, data: { faqs: defaultFaqs } } ),
			} );
		} );
	} );
} );
