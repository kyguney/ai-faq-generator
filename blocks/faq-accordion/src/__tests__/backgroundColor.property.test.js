/**
 * Property-based tests for background color application in the editor preview.
 *
 * Feature: accordion-style-enhancements, Property 1: Background color attribute application
 *
 * For any non-empty CSS color string stored in `titleBackgroundColor` or
 * `contentBackgroundColor`, the rendered editor preview SHALL include that color
 * as the `background-color` inline style on the corresponding area, and when
 * empty, no background-color SHALL appear.
 *
 * **Validates: Requirements 1.3, 1.4, 2.3, 2.4**
 *
 * @package AiFaqGenerator
 */

import { render } from '@testing-library/react';
import '@testing-library/jest-dom';
import * as fc from 'fast-check';

import Edit from '../edit';

// --- Constants ---

/**
 * Base attributes for rendering the Edit component in preview mode.
 */
const baseAttributes = {
	items: [ { question: 'Test Question', answer: 'Test Answer', _open: true } ],
	titleTag: 'h3',
	openFirstItem: false,
	iconPosition: 'left',
	enableAnimation: false,
	titleColor: '',
	titleFontSize: 0,
	titleFontFamily: '',
	titlePadding: 16,
	contentColor: '',
	contentFontSize: 0,
	contentFontFamily: '',
	contentPadding: 16,
	itemSpacing: 8,
	selectedIcon: 'chevron-down',
	layoutMode: 'preview',
	titleBackgroundColor: '',
	contentBackgroundColor: '',
	titleFontWeight: '',
	titleFontStyle: '',
	titleTextDecoration: '',
	titleTextTransform: '',
};

// --- Generators ---

/**
 * Generator for non-empty CSS color strings.
 */
const nonEmptyCssColorArb = fc.constantFrom(
	'#ff0000',
	'#00ff00',
	'rgb(255,0,0)',
	'rgba(0,0,0,0.5)',
	'blue',
	'#abc'
);

/**
 * Generator for CSS color strings including empty (which means "no color").
 */
const cssColorArb = fc.constantFrom(
	'#ff0000',
	'#00ff00',
	'rgb(255,0,0)',
	'rgba(0,0,0,0.5)',
	'blue',
	'#abc',
	''
);

// --- Property Tests ---

describe( 'Feature: accordion-style-enhancements, Property 1: Background color attribute application', () => {
	describe( 'titleBackgroundColor applies background-color to .faq-accordion-summary', () => {
		it( 'for any non-empty titleBackgroundColor, the summary element has background-color in its inline style', () => {
			fc.assert(
				fc.property( nonEmptyCssColorArb, ( color ) => {
					const attributes = {
						...baseAttributes,
						titleBackgroundColor: color,
					};

					const { container } = render(
						<Edit
							attributes={ attributes }
							setAttributes={ jest.fn() }
						/>
					);

					const summary = container.querySelector( '.faq-accordion-summary' );
					expect( summary ).not.toBeNull();

					const style = summary.getAttribute( 'style' );
					expect( style ).toContain( 'background-color' );
				} ),
				{ numRuns: 50 }
			);
		} );

		it( 'when titleBackgroundColor is empty, no background-color appears on the summary element', () => {
			const attributes = {
				...baseAttributes,
				titleBackgroundColor: '',
			};

			const { container } = render(
				<Edit
					attributes={ attributes }
					setAttributes={ jest.fn() }
				/>
			);

			const summary = container.querySelector( '.faq-accordion-summary' );
			expect( summary ).not.toBeNull();

			const style = summary.getAttribute( 'style' ) || '';
			expect( style ).not.toContain( 'background-color' );
		} );
	} );

	describe( 'contentBackgroundColor applies background-color to .faq-accordion-content', () => {
		it( 'for any non-empty contentBackgroundColor, the content element has background-color in its inline style', () => {
			fc.assert(
				fc.property( nonEmptyCssColorArb, ( color ) => {
					const attributes = {
						...baseAttributes,
						contentBackgroundColor: color,
					};

					const { container } = render(
						<Edit
							attributes={ attributes }
							setAttributes={ jest.fn() }
						/>
					);

					const content = container.querySelector( '.faq-accordion-content' );
					expect( content ).not.toBeNull();

					const style = content.getAttribute( 'style' );
					expect( style ).toContain( 'background-color' );
				} ),
				{ numRuns: 50 }
			);
		} );

		it( 'when contentBackgroundColor is empty, no background-color appears on the content element', () => {
			const attributes = {
				...baseAttributes,
				contentBackgroundColor: '',
			};

			const { container } = render(
				<Edit
					attributes={ attributes }
					setAttributes={ jest.fn() }
				/>
			);

			const content = container.querySelector( '.faq-accordion-content' );
			expect( content ).not.toBeNull();

			const style = content.getAttribute( 'style' ) || '';
			expect( style ).not.toContain( 'background-color' );
		} );
	} );

	describe( 'both background colors applied simultaneously', () => {
		it( 'for any combination of non-empty colors, both areas receive their respective background-color', () => {
			fc.assert(
				fc.property(
					nonEmptyCssColorArb,
					nonEmptyCssColorArb,
					( titleBgColor, contentBgColor ) => {
						const attributes = {
							...baseAttributes,
							titleBackgroundColor: titleBgColor,
							contentBackgroundColor: contentBgColor,
						};

						const { container } = render(
							<Edit
								attributes={ attributes }
								setAttributes={ jest.fn() }
							/>
						);

						const summary = container.querySelector( '.faq-accordion-summary' );
						const content = container.querySelector( '.faq-accordion-content' );

						expect( summary ).not.toBeNull();
						expect( content ).not.toBeNull();

						const summaryStyle = summary.getAttribute( 'style' );
						const contentStyle = content.getAttribute( 'style' );

						expect( summaryStyle ).toContain( 'background-color' );
						expect( contentStyle ).toContain( 'background-color' );
					}
				),
				{ numRuns: 50 }
			);
		} );

		it( 'for any color value (including empty), background-color presence matches non-empty status', () => {
			fc.assert(
				fc.property(
					cssColorArb,
					cssColorArb,
					( titleBgColor, contentBgColor ) => {
						const attributes = {
							...baseAttributes,
							titleBackgroundColor: titleBgColor,
							contentBackgroundColor: contentBgColor,
						};

						const { container } = render(
							<Edit
								attributes={ attributes }
								setAttributes={ jest.fn() }
							/>
						);

						const summary = container.querySelector( '.faq-accordion-summary' );
						const content = container.querySelector( '.faq-accordion-content' );

						expect( summary ).not.toBeNull();
						expect( content ).not.toBeNull();

						const summaryStyle = summary.getAttribute( 'style' ) || '';
						const contentStyle = content.getAttribute( 'style' ) || '';

						if ( titleBgColor !== '' ) {
							expect( summaryStyle ).toContain( 'background-color' );
						} else {
							expect( summaryStyle ).not.toContain( 'background-color' );
						}

						if ( contentBgColor !== '' ) {
							expect( contentStyle ).toContain( 'background-color' );
						} else {
							expect( contentStyle ).not.toContain( 'background-color' );
						}
					}
				),
				{ numRuns: 50 }
			);
		} );
	} );
} );
