/**
 * Unit tests for FaqItemEditor component.
 *
 * Validates: Requirements 3.4, 3.5, 3.6, 4.3
 *
 * @package AiFaqGenerator
 */

import { render, screen, fireEvent } from '@testing-library/react';
import '@testing-library/jest-dom';

import FaqItemEditor from '../components/FaqItemEditor';

// --- Helpers ---

const defaultProps = {
	item: { question: 'What is WordPress?', answer: 'A content management system.' },
	index: 1,
	onUpdate: jest.fn(),
	onRemove: jest.fn(),
	onMove: jest.fn(),
	isFirst: false,
	isLast: false,
};

function renderComponent( overrides = {} ) {
	const props = { ...defaultProps, ...overrides };
	return render( <FaqItemEditor { ...props } /> );
}

// --- Test Suite ---

describe( 'FaqItemEditor', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	describe( 'rendering', () => {
		it( 'renders question input with the correct value', () => {
			renderComponent();

			const questionInput = screen.getByRole( 'textbox', { name: 'Question' } );
			expect( questionInput ).toHaveValue( 'What is WordPress?' );
		} );

		it( 'renders answer textarea with the correct value', () => {
			renderComponent();

			const answerTextarea = screen.getByRole( 'textbox', { name: 'Answer' } );
			expect( answerTextarea ).toHaveValue( 'A content management system.' );
		} );
	} );

	describe( 'onChange handlers', () => {
		it( 'calls onUpdate with index, "question", and new value when question changes', () => {
			const onUpdate = jest.fn();
			renderComponent( { onUpdate } );

			const questionInput = screen.getByRole( 'textbox', { name: 'Question' } );
			fireEvent.change( questionInput, { target: { value: 'New question?' } } );

			expect( onUpdate ).toHaveBeenCalledWith( 1, 'question', 'New question?' );
		} );

		it( 'calls onUpdate with index, "answer", and new value when answer changes', () => {
			const onUpdate = jest.fn();
			renderComponent( { onUpdate } );

			const answerTextarea = screen.getByRole( 'textbox', { name: 'Answer' } );
			fireEvent.change( answerTextarea, { target: { value: 'Updated answer.' } } );

			expect( onUpdate ).toHaveBeenCalledWith( 1, 'answer', 'Updated answer.' );
		} );
	} );

	describe( 'remove button', () => {
		it( 'calls onRemove with the correct index when remove button is clicked', () => {
			const onRemove = jest.fn();
			renderComponent( { onRemove, index: 2 } );

			const removeButton = screen.getByRole( 'button', { name: 'Remove item' } );
			fireEvent.click( removeButton );

			expect( onRemove ).toHaveBeenCalledWith( 2 );
		} );
	} );

	describe( 'move buttons', () => {
		it( 'calls onMove with index and -1 when move-up button is clicked', () => {
			const onMove = jest.fn();
			renderComponent( { onMove, index: 2, isFirst: false, isLast: false } );

			const moveUpButton = screen.getByRole( 'button', { name: 'Move up' } );
			fireEvent.click( moveUpButton );

			expect( onMove ).toHaveBeenCalledWith( 2, -1 );
		} );

		it( 'calls onMove with index and 1 when move-down button is clicked', () => {
			const onMove = jest.fn();
			renderComponent( { onMove, index: 2, isFirst: false, isLast: false } );

			const moveDownButton = screen.getByRole( 'button', { name: 'Move down' } );
			fireEvent.click( moveDownButton );

			expect( onMove ).toHaveBeenCalledWith( 2, 1 );
		} );

		it( 'does not render move-up button when isFirst is true', () => {
			renderComponent( { isFirst: true, isLast: false } );

			expect( screen.queryByRole( 'button', { name: 'Move up' } ) ).not.toBeInTheDocument();
		} );

		it( 'does not render move-down button when isLast is true', () => {
			renderComponent( { isFirst: false, isLast: true } );

			expect( screen.queryByRole( 'button', { name: 'Move down' } ) ).not.toBeInTheDocument();
		} );

		it( 'renders both move buttons when item is neither first nor last', () => {
			renderComponent( { isFirst: false, isLast: false } );

			expect( screen.getByRole( 'button', { name: 'Move up' } ) ).toBeInTheDocument();
			expect( screen.getByRole( 'button', { name: 'Move down' } ) ).toBeInTheDocument();
		} );

		it( 'renders neither move-up nor move-down when item is both first and last', () => {
			renderComponent( { isFirst: true, isLast: true } );

			expect( screen.queryByRole( 'button', { name: 'Move up' } ) ).not.toBeInTheDocument();
			expect( screen.queryByRole( 'button', { name: 'Move down' } ) ).not.toBeInTheDocument();
		} );
	} );
} );
