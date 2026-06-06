/**
 * Bug Condition Exploration Test - Visual Preview uses div elements instead of details/summary.
 *
 * Property 1: Bug Condition
 *
 * This test is written BEFORE the fix and is EXPECTED TO FAIL on unfixed code.
 * Failure confirms the bug exists: renderVisualPreview() renders <div> elements
 * that don't match style.css selectors targeting <details>/<summary>.
 *
 * Validates: Requirements 1.1, 1.2, 1.3, 1.4, 1.5
 *
 * @package AiFaqGenerator
 */

import { render } from '@testing-library/react';
import '@testing-library/jest-dom';
import * as fc from 'fast-check';

import Edit from '../edit';

// --- Generators ---

/**
 * Generator for a valid FAQ item with random question/answer text.
 */
const faqItemArb = fc.record( {
	question: fc.string( { minLength: 1, maxLength: 100 } ).filter( ( s ) => s.trim().length > 0 ),
	answer: fc.string( { minLength: 1, maxLength: 200 } ).filter( ( s ) => s.trim().length > 0 ),
	_open: fc.boolean(),
} );

/**
 * Generator for a non-empty list of FAQ items (1-10 items).
 */
const faqItemsArb = fc.array( faqItemArb, { minLength: 1, maxLength: 10 } );

/**
 * Generator for valid iconPosition values.
 */
const iconPositionArb = fc.constantFrom( 'left', 'right', 'none' );

/**
 * Generator for valid selectedIcon values.
 */
const selectedIconArb = fc.constantFrom( 'chevron', 'chevron-right', 'plus', 'arrow', 'none' );

/**
 * Generator for full attribute combinations in preview mode.
 */
const previewAttributesArb = fc.record( {
	items: faqItemsArb,
	iconPosition: iconPositionArb,
	openFirstItem: fc.boolean(),
	enableAnimation: fc.boolean(),
	selectedIcon: selectedIconArb,
	layoutMode: fc.constant( 'preview' ),
} );

// --- Tests ---

describe( 'Bug Condition: Visual Preview uses div elements instead of details/summary', () => {
	/**
	 * Validates: Requirements 1.1, 1.2, 1.3, 1.4, 1.5
	 */

	it( 'preview output contains <details> elements with class faq-accordion-item for all generated FAQ items', () => {
		fc.assert(
			fc.property( previewAttributesArb, ( attributes ) => {
				const { container } = render(
					<Edit
						attributes={ attributes }
						setAttributes={ jest.fn() }
					/>
				);

				// Every FAQ item should be rendered as a <details> element
				const detailsElements = container.querySelectorAll( 'details.faq-accordion-item' );
				expect( detailsElements.length ).toBe( attributes.items.length );

				// No <div> elements with class faq-accordion-item should exist
				const divItems = container.querySelectorAll( 'div.faq-accordion-item' );
				expect( divItems.length ).toBe( 0 );
			} ),
			{ numRuns: 50 }
		);
	} );

	it( 'preview output contains <summary> elements (not <div class="faq-accordion-summary">) for all generated FAQ items', () => {
		fc.assert(
			fc.property( previewAttributesArb, ( attributes ) => {
				const { container } = render(
					<Edit
						attributes={ attributes }
						setAttributes={ jest.fn() }
					/>
				);

				// Each FAQ item should have a <summary> element
				const summaryElements = container.querySelectorAll( 'details.faq-accordion-item > summary' );
				expect( summaryElements.length ).toBe( attributes.items.length );

				// No div-based summaries should exist
				const divSummaries = container.querySelectorAll( '.faq-accordion-summary' );
				const divTagSummaries = Array.from( divSummaries ).filter(
					( el ) => el.tagName.toLowerCase() === 'div'
				);
				expect( divTagSummaries.length ).toBe( 0 );
			} ),
			{ numRuns: 50 }
		);
	} );

	it( 'open items have the native open attribute on <details> (not is-open class)', () => {
		// Use attributes where at least one item is open
		const openItemAttributesArb = previewAttributesArb.filter( ( attrs ) => {
			return attrs.items.some( ( item, index ) =>
				item._open || ( attrs.openFirstItem && index === 0 )
			);
		} );

		fc.assert(
			fc.property( openItemAttributesArb, ( attributes ) => {
				const { container } = render(
					<Edit
						attributes={ attributes }
						setAttributes={ jest.fn() }
					/>
				);

				// There should be NO elements with is-open class (bug indicator)
				const isOpenElements = container.querySelectorAll( '.is-open' );
				expect( isOpenElements.length ).toBe( 0 );

				// Open items must use native <details open> attribute
				const detailsElements = container.querySelectorAll( 'details.faq-accordion-item' );
				expect( detailsElements.length ).toBe( attributes.items.length );

				attributes.items.forEach( ( item, index ) => {
					const isOpen = item._open || ( attributes.openFirstItem && index === 0 );

					if ( isOpen ) {
						expect( detailsElements[ index ] ).toHaveAttribute( 'open' );
					}
				} );
			} ),
			{ numRuns: 50 }
		);
	} );

	it( 'content panels have <div class="faq-accordion-content__inner"> inner wrapper', () => {
		fc.assert(
			fc.property( previewAttributesArb, ( attributes ) => {
				const { container } = render(
					<Edit
						attributes={ attributes }
						setAttributes={ jest.fn() }
					/>
				);

				// Every FAQ item should have the inner content wrapper
				const innerWrappers = container.querySelectorAll( '.faq-accordion-content__inner' );
				expect( innerWrappers.length ).toBe( attributes.items.length );

				// Each inner wrapper should be inside .faq-accordion-content
				innerWrappers.forEach( ( wrapper ) => {
					expect( wrapper.parentElement ).toHaveClass( 'faq-accordion-content' );
				} );
			} ),
			{ numRuns: 50 }
		);
	} );

	it( 'icon elements inside <summary> have no inline positioning/transform styles', () => {
		fc.assert(
			fc.property(
				previewAttributesArb.filter(
					( attrs ) => attrs.selectedIcon !== 'none' && attrs.iconPosition !== 'none'
				),
				( attributes ) => {
					const { container } = render(
						<Edit
							attributes={ attributes }
							setAttributes={ jest.fn() }
						/>
					);

					const iconElements = container.querySelectorAll( '.faq-accordion-icon' );

					iconElements.forEach( ( icon ) => {
						const style = icon.getAttribute( 'style' ) || '';
						// No inline positioning or transform styles - let style.css handle them
						expect( style ).not.toContain( 'margin-right' );
						expect( style ).not.toContain( 'order' );
						expect( style ).not.toContain( 'transition' );
						expect( style ).not.toContain( 'transform' );
					} );
				}
			),
			{ numRuns: 50 }
		);
	} );
} );
