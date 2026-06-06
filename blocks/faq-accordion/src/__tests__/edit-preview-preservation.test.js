/**
 * Preservation property-based tests for Edit component.
 *
 * Feature: preview-frontend-mismatch, Property 2: Preservation
 *
 * These tests verify that existing behaviors in the Edit component
 * are preserved after the bug fix. They run on UNFIXED code and must PASS,
 * confirming the baseline behavior that the fix must not break.
 *
 * **Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5**
 *
 * @package AiFaqGenerator
 */

import { render, screen, fireEvent } from '@testing-library/react';
import '@testing-library/jest-dom';
import * as fc from 'fast-check';

import Edit from '../edit';

// --- Generators ---

/**
 * Generator for hex color strings (e.g., "#a1b2c3").
 */
const hexColorArb = fc
	.tuple(
		fc.integer( { min: 0, max: 255 } ),
		fc.integer( { min: 0, max: 255 } ),
		fc.integer( { min: 0, max: 255 } )
	)
	.map(
		( [ r, g, b ] ) =>
			`#${ r.toString( 16 ).padStart( 2, '0' ) }${ g
				.toString( 16 )
				.padStart( 2, '0' ) }${ b.toString( 16 ).padStart( 2, '0' ) }`
	);

/**
 * Generator for titleFontSize (0-72).
 */
const titleFontSizeArb = fc.integer( { min: 0, max: 72 } );

/**
 * Generator for contentPadding (0-100).
 */
const contentPaddingArb = fc.integer( { min: 0, max: 100 } );

/**
 * Generator for itemSpacing (0-50).
 */
const itemSpacingArb = fc.integer( { min: 0, max: 50 } );

/**
 * Generator for a set of styling attributes.
 */
const stylingAttributesArb = fc.record( {
	titleColor: hexColorArb,
	titleFontSize: titleFontSizeArb,
	contentColor: hexColorArb,
	contentPadding: contentPaddingArb,
	itemSpacing: itemSpacingArb,
} );

/**
 * Fixed items for testing (non-empty with content).
 */
const testItems = [
	{ question: 'What is WordPress?', answer: 'A CMS platform.', _open: false },
	{ question: 'What is React?', answer: 'A JS library.', _open: false },
	{ question: 'What is PHP?', answer: 'A server language.', _open: false },
];

// --- Property Tests ---

describe( 'Feature: preview-frontend-mismatch, Property 2: Preservation - Classic Edit Mode and Inline Styles Unchanged', () => {
	describe( 'Edit mode renders FaqItemEditor components (no faq-accordion-item class)', () => {
		/**
		 * Validates: Requirements 3.1
		 *
		 * For all generated attribute sets with layoutMode = "edit":
		 * assert output contains FaqItemEditor elements (input fields),
		 * no faq-accordion-item class in output.
		 */
		it( 'for all styling attribute combinations, edit mode renders input fields and no faq-accordion-item class', () => {
			fc.assert(
				fc.property( stylingAttributesArb, ( styling ) => {
					const attributes = {
						items: testItems,
						layoutMode: undefined, // classic edit mode (default)
						...styling,
					};

					const { container } = render(
						<Edit
							attributes={ attributes }
							setAttributes={ jest.fn() }
						/>
					);

					// Should have input fields (FaqItemEditor renders TextControl with <input>)
					const inputs = container.querySelectorAll( 'input[type="text"]' );
					expect( inputs.length ).toBeGreaterThan( 0 );

					// Should have textarea fields (FaqItemEditor renders TextareaControl)
					const textareas = container.querySelectorAll( 'textarea' );
					expect( textareas.length ).toBeGreaterThan( 0 );

					// Should NOT have faq-accordion-item class elements
					const accordionItems = container.querySelectorAll( '.faq-accordion-item' );
					expect( accordionItems.length ).toBe( 0 );

					// Should NOT have <details> or <summary> elements
					const details = container.querySelectorAll( 'details' );
					const summaries = container.querySelectorAll( 'summary' );
					expect( details.length ).toBe( 0 );
					expect( summaries.length ).toBe( 0 );
				} ),
				{ numRuns: 50 }
			);
		} );
	} );

	describe( 'Preview mode applies inline color style for titleColor', () => {
		/**
		 * Validates: Requirements 3.4
		 *
		 * For all generated attribute sets with layoutMode = "preview"
		 * and non-empty titleColor: assert inline color style is applied to title.
		 */
		it( 'for all non-empty titleColor values, preview mode applies inline color style', () => {
			fc.assert(
				fc.property( hexColorArb, ( titleColor ) => {
					const attributes = {
						items: testItems,
						layoutMode: 'preview',
						titleColor,
						openFirstItem: true, // ensure items are visible
					};

					const { container } = render(
						<Edit
							attributes={ attributes }
							setAttributes={ jest.fn() }
						/>
					);

					// The title style should contain the color.
					// In unfixed code, the summary div has the inline style.
					const summaryDivs = container.querySelectorAll( '.faq-accordion-summary' );

					// There must be summary elements rendered
					expect( summaryDivs.length ).toBeGreaterThan( 0 );

					// The first summary div should have the color style applied.
					// React/jsdom normalizes hex colors to rgb(), so we check
					// the computed style property directly.
					const computedColor = summaryDivs[ 0 ].style.color;
					expect( computedColor ).not.toBe( '' );
				} ),
				{ numRuns: 50 }
			);
		} );
	} );

	describe( 'Preview mode applies inline fontSize style for titleFontSize > 0', () => {
		/**
		 * Validates: Requirements 3.4
		 *
		 * For all generated attribute sets with layoutMode = "preview"
		 * and titleFontSize > 0: assert inline fontSize style is applied.
		 */
		it( 'for all titleFontSize > 0, preview mode applies inline fontSize style', () => {
			fc.assert(
				fc.property(
					fc.integer( { min: 1, max: 72 } ),
					( titleFontSize ) => {
						const attributes = {
							items: testItems,
							layoutMode: 'preview',
							titleFontSize,
							openFirstItem: true,
						};

						const { container } = render(
							<Edit
								attributes={ attributes }
								setAttributes={ jest.fn() }
							/>
						);

						// The summary div should have fontSize in inline style
						const summaryDivs = container.querySelectorAll( '.faq-accordion-summary' );
						expect( summaryDivs.length ).toBeGreaterThan( 0 );

						const style = summaryDivs[ 0 ].getAttribute( 'style' );
						expect( style ).toContain( 'font-size' );
						expect( style ).toContain( `${ titleFontSize }px` );
					}
				),
				{ numRuns: 50 }
			);
		} );
	} );

	describe( 'Preview mode with openFirstItem=true shows first item open', () => {
		/**
		 * Validates: Requirements 3.5
		 *
		 * For all generated attribute sets with layoutMode = "preview"
		 * and openFirstItem=true: assert first item is in open state.
		 */
		it( 'for all attribute combinations with openFirstItem=true, first item is open in preview', () => {
			fc.assert(
				fc.property( stylingAttributesArb, ( styling ) => {
					const items = [
						{ question: 'First Q', answer: 'First A', _open: false },
						{ question: 'Second Q', answer: 'Second A', _open: false },
					];

					const attributes = {
						items,
						layoutMode: 'preview',
						openFirstItem: true,
						...styling,
					};

					const { container } = render(
						<Edit
							attributes={ attributes }
							setAttributes={ jest.fn() }
						/>
					);

					// In unfixed code, open items get the 'is-open' class
					const accordionItems = container.querySelectorAll( '.faq-accordion-item' );
					expect( accordionItems.length ).toBe( 2 );

					// First item should be open (has native open attribute on <details>)
					expect( accordionItems[ 0 ].hasAttribute( 'open' ) ).toBe( true );

					// First item's content should be visible
					const firstItemContent = accordionItems[ 0 ].querySelector( '.faq-accordion-content' );
					expect( firstItemContent ).not.toBeNull();
				} ),
				{ numRuns: 50 }
			);
		} );
	} );

	describe( 'Preview mode toggle click flips _open attribute', () => {
		/**
		 * Validates: Requirements 3.3
		 *
		 * Clicking on a summary div in preview mode toggles the _open attribute.
		 */
		it( 'clicking on an item summary in preview toggles _open via setAttributes', () => {
			const items = [
				{ question: 'Toggle Q', answer: 'Toggle A', _open: false },
				{ question: 'Other Q', answer: 'Other A', _open: false },
			];

			const setAttributes = jest.fn();

			const { container } = render(
				<Edit
					attributes={ { items, layoutMode: 'preview' } }
					setAttributes={ setAttributes }
				/>
			);

			// Click first summary to toggle
			const summaryDivs = container.querySelectorAll( '.faq-accordion-summary' );
			expect( summaryDivs.length ).toBe( 2 );

			fireEvent.click( summaryDivs[ 0 ] );

			// setAttributes should have been called with toggled _open
			expect( setAttributes ).toHaveBeenCalledWith( {
				items: [
					{ question: 'Toggle Q', answer: 'Toggle A', _open: true },
					{ question: 'Other Q', answer: 'Other A', _open: false },
				],
			} );
		} );
	} );
} );
