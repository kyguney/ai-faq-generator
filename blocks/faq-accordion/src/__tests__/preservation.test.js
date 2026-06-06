/**
 * Preservation Property Tests for the FAQ Accordion Block.
 *
 * These tests verify baseline behavior that must remain unchanged after bug fixes.
 * They are written BEFORE implementing any fixes and must PASS on unfixed code.
 *
 * **Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5**
 *
 * @package AiFaqGenerator
 */

import { render, screen, fireEvent, within } from '@testing-library/react';
import '@testing-library/jest-dom';
import fc from 'fast-check';

import Edit from '../edit';
import { getBlockClasses } from '../utils/getBlockClasses';

/**
 * Generators for property-based testing
 */

// Generate a valid FAQ item
const faqItemArb = fc.record( {
	question: fc.string( { minLength: 1, maxLength: 50 } ),
	answer: fc.string( { minLength: 1, maxLength: 100 } ),
} );

// Generate a non-empty array of FAQ items (1 to 5 items for performance)
const faqItemsArb = fc.array( faqItemArb, { minLength: 1, maxLength: 5 } );

// Generate valid icon positions
const iconPositionArb = fc.constantFrom( 'left', 'right', 'none' );

// Generate valid selectedIcon values
const selectedIconArb = fc.constantFrom( 'chevron', 'chevron-right', 'plus', 'arrow', 'none' );

// Generate valid title tags
const titleTagArb = fc.constantFrom( 'h2', 'h3', 'h4', 'h5', 'h6', 'p' );

/**
 * Helper: render the Edit component and return only the block content container
 * (excludes InspectorControls which renders in a separate div).
 */
function renderEdit( attributes ) {
	const setAttributes = jest.fn();
	const { container } = render(
		<Edit attributes={ attributes } setAttributes={ setAttributes } />
	);
	// The block content is the second child div (after inspector-controls)
	// useBlockProps returns className: 'wp-block-mock' + the getBlockClasses result
	const blockWrapper = container.querySelector( '[class*="wp-block-mock"]' );
	return { container, blockWrapper, setAttributes };
}

describe( 'Preservation Property Tests', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	describe( 'Property: Edit mode always renders FaqItemEditor components', () => {
		/**
		 * **Validates: Requirements 3.1**
		 *
		 * For all attribute combinations where layoutMode="edit" and items are non-empty,
		 * the editor renders FaqItemEditor components (not visual preview).
		 */
		it( 'renders FaqItemEditor for each item when layoutMode is "edit"', () => {
			fc.assert(
				fc.property(
					fc.record( {
						items: faqItemsArb,
						titleTag: titleTagArb,
						openFirstItem: fc.boolean(),
						iconPosition: iconPositionArb,
						enableAnimation: fc.boolean(),
						selectedIcon: selectedIconArb,
					} ),
					( partialAttrs ) => {
						const attributes = {
							...partialAttrs,
							layoutMode: 'edit',
							titleColor: '',
							titleFontSize: 0,
							titleFontFamily: '',
							titlePadding: 16,
							contentColor: '',
							contentFontSize: 0,
							contentFontFamily: '',
							contentPadding: 16,
							itemSpacing: 8,
						};
						const { blockWrapper } = renderEdit( attributes );

						// Each item should produce a .faq-item-editor element
						const editors = blockWrapper.querySelectorAll( '.faq-item-editor' );
						expect( editors.length ).toBe( attributes.items.length );

						// Visual preview elements should NOT be present
						const previewSummaries = blockWrapper.querySelectorAll( '.faq-accordion-summary' );
						expect( previewSummaries.length ).toBe( 0 );
					}
				),
				{ numRuns: 30 }
			);
		} );

		it( 'renders placeholder when items array is empty in edit mode', () => {
			fc.assert(
				fc.property(
					fc.record( {
						iconPosition: iconPositionArb,
						enableAnimation: fc.boolean(),
						selectedIcon: selectedIconArb,
						titleTag: titleTagArb,
					} ),
					( partialAttrs ) => {
						const attributes = {
							items: [],
							layoutMode: 'edit',
							openFirstItem: false,
							titleColor: '',
							titleFontSize: 0,
							titleFontFamily: '',
							titlePadding: 16,
							contentColor: '',
							contentFontSize: 0,
							contentFontFamily: '',
							contentPadding: 16,
							itemSpacing: 8,
							...partialAttrs,
						};
						const { blockWrapper } = renderEdit( attributes );

						// Placeholder text should appear within the block wrapper
						const placeholder = blockWrapper.querySelector( '.faq-accordion-placeholder' );
						expect( placeholder ).not.toBeNull();
						expect( placeholder.textContent ).toContain( 'No FAQ items added yet' );
					}
				),
				{ numRuns: 20 }
			);
		} );
	} );

	describe( 'Property: Icon position "none" hides icons in preview mode', () => {
		/**
		 * **Validates: Requirements 3.2**
		 *
		 * For all attribute combinations where iconPosition="none",
		 * no .faq-accordion-icon element appears in rendered HTML.
		 */
		it( 'hides icon element when iconPosition is "none" in preview mode', () => {
			fc.assert(
				fc.property(
					fc.record( {
						items: faqItemsArb,
						titleTag: titleTagArb,
						openFirstItem: fc.boolean(),
						selectedIcon: selectedIconArb,
						enableAnimation: fc.boolean(),
					} ),
					( partialAttrs ) => {
						const attributes = {
							...partialAttrs,
							iconPosition: 'none',
							layoutMode: 'preview',
							titleColor: '',
							titleFontSize: 0,
							titleFontFamily: '',
							titlePadding: 16,
							contentColor: '',
							contentFontSize: 0,
							contentFontFamily: '',
							contentPadding: 16,
							itemSpacing: 8,
						};
						const { blockWrapper } = renderEdit( attributes );

						// No icon element should be present
						const icons = blockWrapper.querySelectorAll( '.faq-accordion-icon' );
						expect( icons.length ).toBe( 0 );
					}
				),
				{ numRuns: 30 }
			);
		} );
	} );

	describe( 'Property: Default styling produces no inline style overrides in preview', () => {
		/**
		 * **Validates: Requirements 3.3**
		 *
		 * For all attribute combinations with default styling values (empty colors, 0 font sizes),
		 * the rendered preview does not inject color/fontSize/fontFamily inline styles
		 * on the .faq-accordion-summary elements.
		 */
		it( 'does not inject custom color or font-size styles with default attributes in preview', () => {
			fc.assert(
				fc.property(
					fc.record( {
						items: faqItemsArb,
						titleTag: titleTagArb,
						openFirstItem: fc.boolean(),
						iconPosition: iconPositionArb,
						enableAnimation: fc.boolean(),
						selectedIcon: selectedIconArb,
					} ),
					( partialAttrs ) => {
						const attributes = {
							...partialAttrs,
							layoutMode: 'preview',
							// All default styling values
							titleColor: '',
							titleFontSize: 0,
							titleFontFamily: '',
							titlePadding: 16,
							contentColor: '',
							contentFontSize: 0,
							contentFontFamily: '',
							contentPadding: 16,
							itemSpacing: 8,
						};
						const { blockWrapper } = renderEdit( attributes );

						// Check .faq-accordion-summary elements for inline styles
						const summaries = blockWrapper.querySelectorAll( '.faq-accordion-summary' );
						summaries.forEach( ( summary ) => {
							const style = summary.style;
							// With defaults, no color or font-size should be in inline styles
							expect( style.color ).toBe( '' );
							expect( style.fontSize ).toBe( '' );
							expect( style.fontFamily ).toBe( '' );
						} );
					}
				),
				{ numRuns: 30 }
			);
		} );
	} );

	describe( 'Property: Item add/remove/reorder operations work correctly', () => {
		/**
		 * **Validates: Requirements 3.4**
		 *
		 * For all valid item arrays, add/remove/reorder operations produce
		 * correct results in edit mode.
		 */
		it( 'add operation appends a new empty item', () => {
			fc.assert(
				fc.property(
					fc.array( faqItemArb, { minLength: 0, maxLength: 5 } ),
					( items ) => {
						const attributes = {
							items,
							layoutMode: 'edit',
							titleTag: 'h3',
							openFirstItem: false,
							iconPosition: 'left',
							enableAnimation: false,
							selectedIcon: 'chevron',
							titleColor: '',
							titleFontSize: 0,
							titleFontFamily: '',
							titlePadding: 16,
							contentColor: '',
							contentFontSize: 0,
							contentFontFamily: '',
							contentPadding: 16,
							itemSpacing: 8,
						};
						const { blockWrapper, setAttributes } = renderEdit( attributes );

						// Find the Add FAQ Item button within the block wrapper
						const addButton = within( blockWrapper ).getByRole( 'button', { name: 'Add FAQ Item' } );
						fireEvent.click( addButton );

						expect( setAttributes ).toHaveBeenCalledWith( {
							items: [ ...items, { question: '', answer: '', _open: false } ],
						} );
					}
				),
				{ numRuns: 20 }
			);
		} );

		it( 'remove operation removes the item at the given index', () => {
			fc.assert(
				fc.property(
					fc.array( faqItemArb, { minLength: 1, maxLength: 5 } ).chain( ( items ) =>
						fc.record( {
							items: fc.constant( items ),
							indexToRemove: fc.nat( { max: items.length - 1 } ),
						} )
					),
					( { items, indexToRemove } ) => {
						const attributes = {
							items,
							layoutMode: 'edit',
							titleTag: 'h3',
							openFirstItem: false,
							iconPosition: 'left',
							enableAnimation: false,
							selectedIcon: 'chevron',
							titleColor: '',
							titleFontSize: 0,
							titleFontFamily: '',
							titlePadding: 16,
							contentColor: '',
							contentFontSize: 0,
							contentFontFamily: '',
							contentPadding: 16,
							itemSpacing: 8,
						};
						const { blockWrapper, setAttributes } = renderEdit( attributes );

						// Click the remove button for the item at indexToRemove
						const removeButtons = within( blockWrapper ).getAllByRole( 'button', { name: 'Remove item' } );
						fireEvent.click( removeButtons[ indexToRemove ] );

						const expectedItems = items.filter( ( _, i ) => i !== indexToRemove );
						expect( setAttributes ).toHaveBeenCalledWith( {
							items: expectedItems,
						} );
					}
				),
				{ numRuns: 20 }
			);
		} );

		it( 'move down operation swaps item with the next item', () => {
			fc.assert(
				fc.property(
					fc.array( faqItemArb, { minLength: 2, maxLength: 5 } ).chain( ( items ) =>
						fc.record( {
							items: fc.constant( items ),
							// Can move down any item except the last
							indexToMove: fc.nat( { max: items.length - 2 } ),
						} )
					),
					( { items, indexToMove } ) => {
						const attributes = {
							items,
							layoutMode: 'edit',
							titleTag: 'h3',
							openFirstItem: false,
							iconPosition: 'left',
							enableAnimation: false,
							selectedIcon: 'chevron',
							titleColor: '',
							titleFontSize: 0,
							titleFontFamily: '',
							titlePadding: 16,
							contentColor: '',
							contentFontSize: 0,
							contentFontFamily: '',
							contentPadding: 16,
							itemSpacing: 8,
						};
						const { blockWrapper, setAttributes } = renderEdit( attributes );

						// Click the "Move down" button for the item at indexToMove
						const moveDownButtons = within( blockWrapper ).getAllByRole( 'button', { name: 'Move down' } );
						fireEvent.click( moveDownButtons[ indexToMove ] );

						const expectedItems = [ ...items ];
						const temp = expectedItems[ indexToMove ];
						expectedItems[ indexToMove ] = expectedItems[ indexToMove + 1 ];
						expectedItems[ indexToMove + 1 ] = temp;
						expect( setAttributes ).toHaveBeenCalledWith( {
							items: expectedItems,
						} );
					}
				),
				{ numRuns: 20 }
			);
		} );

		it( 'add button is disabled at 50 items', () => {
			const items = Array.from( { length: 50 }, ( _, i ) => ( {
				question: `Q${ i }`,
				answer: `A${ i }`,
			} ) );
			const attributes = {
				items,
				layoutMode: 'edit',
				titleTag: 'h3',
				openFirstItem: false,
				iconPosition: 'left',
				enableAnimation: false,
				selectedIcon: 'chevron',
				titleColor: '',
				titleFontSize: 0,
				titleFontFamily: '',
				titlePadding: 16,
				contentColor: '',
				contentFontSize: 0,
				contentFontFamily: '',
				contentPadding: 16,
				itemSpacing: 8,
			};
			const { blockWrapper, setAttributes } = renderEdit( attributes );

			const addButton = within( blockWrapper ).getByRole( 'button', { name: 'Add FAQ Item' } );
			expect( addButton ).toBeDisabled();

			fireEvent.click( addButton );
			expect( setAttributes ).not.toHaveBeenCalled();
		} );
	} );

	describe( 'Property: Animation class present when enableAnimation=true', () => {
		/**
		 * **Validates: Requirements 3.5**
		 *
		 * For all attribute combinations, getBlockClasses includes 'has-animation'
		 * when enableAnimation=true and excludes it when enableAnimation=false.
		 */
		it( 'getBlockClasses includes has-animation when enableAnimation is true', () => {
			fc.assert(
				fc.property(
					fc.record( {
						iconPosition: iconPositionArb,
						enableAnimation: fc.constant( true ),
					} ),
					( attributes ) => {
						const classes = getBlockClasses( attributes );
						expect( classes ).toContain( 'has-animation' );
					}
				),
				{ numRuns: 20 }
			);
		} );

		it( 'getBlockClasses excludes has-animation when enableAnimation is false', () => {
			fc.assert(
				fc.property(
					fc.record( {
						iconPosition: iconPositionArb,
						enableAnimation: fc.constant( false ),
					} ),
					( attributes ) => {
						const classes = getBlockClasses( attributes );
						expect( classes ).not.toContain( 'has-animation' );
					}
				),
				{ numRuns: 20 }
			);
		} );

		it( 'getBlockClasses produces correct icon position classes', () => {
			fc.assert(
				fc.property(
					fc.record( {
						iconPosition: iconPositionArb,
						enableAnimation: fc.boolean(),
					} ),
					( attributes ) => {
						const classes = getBlockClasses( attributes );

						if ( attributes.iconPosition === 'left' ) {
							expect( classes ).toContain( 'has-icon-left' );
						} else if ( attributes.iconPosition === 'right' ) {
							expect( classes ).toContain( 'has-icon-right' );
						} else if ( attributes.iconPosition === 'none' ) {
							expect( classes ).toContain( 'has-no-icon' );
						}
					}
				),
				{ numRuns: 20 }
			);
		} );
	} );
} );
