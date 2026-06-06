/**
 * Property-based tests for SVG icon rendering in the editor preview.
 *
 * Feature: accordion-style-enhancements, Property 3: SVG icon rendering for valid identifiers
 *
 * For any valid icon identifier in the registry (other than "none"), the rendered
 * accordion output SHALL contain SVG markup, and when the identifier is "none",
 * no icon element SHALL be present.
 *
 * **Validates: Requirements 7.5, 7.6**
 *
 * @package AiFaqGenerator
 */

import { render } from '@testing-library/react';
import '@testing-library/jest-dom';
import * as fc from 'fast-check';

import Edit from '../edit';

// --- Base Attributes ---

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
 * Generator for valid icon identifiers that should render SVG content.
 */
const validIconWithSvgArb = fc.constantFrom(
	'chevron-down',
	'chevron-right',
	'plus-minus',
	'arrow-down',
	'arrow-right'
);

/**
 * Generator for legacy icon identifiers that should map to new icons and still render SVG.
 */
const legacyIconArb = fc.constantFrom( 'chevron', 'plus', 'arrow' );

// --- Property 3: SVG icon rendering for valid identifiers ---

describe( 'Feature: accordion-style-enhancements, Property 3: SVG icon rendering for valid identifiers', () => {
	/**
	 * Validates: Requirements 7.5, 7.6
	 */

	it( 'for any valid icon identifier (not "none"), the rendered output contains a .faq-accordion-icon element', () => {
		fc.assert(
			fc.property( validIconWithSvgArb, ( iconId ) => {
				const attributes = {
					...baseAttributes,
					selectedIcon: iconId,
				};

				const { container } = render(
					<Edit
						attributes={ attributes }
						setAttributes={ jest.fn() }
					/>
				);

				// There should be a .faq-accordion-icon element present
				const iconElements = container.querySelectorAll( '.faq-accordion-icon' );
				expect( iconElements.length ).toBeGreaterThan( 0 );

				// The icon element should contain either an SVG element or the mocked Icon span
				const firstIcon = iconElements[ 0 ];
				const hasSvg = firstIcon.querySelector( 'svg' ) !== null;
				const hasMockedIcon = firstIcon.querySelector( '[data-testid="icon"]' ) !== null;
				expect( hasSvg || hasMockedIcon ).toBe( true );
			} ),
			{ numRuns: 50 }
		);
	} );

	it( 'when selectedIcon is "none", no .faq-accordion-icon element is present in the output', () => {
		const attributes = {
			...baseAttributes,
			selectedIcon: 'none',
		};

		const { container } = render(
			<Edit
				attributes={ attributes }
				setAttributes={ jest.fn() }
			/>
		);

		const iconElements = container.querySelectorAll( '.faq-accordion-icon' );
		expect( iconElements.length ).toBe( 0 );
	} );

	it( 'legacy icon identifiers map to new identifiers and still render an icon element with SVG content', () => {
		fc.assert(
			fc.property( legacyIconArb, ( legacyId ) => {
				const attributes = {
					...baseAttributes,
					selectedIcon: legacyId,
				};

				const { container } = render(
					<Edit
						attributes={ attributes }
						setAttributes={ jest.fn() }
					/>
				);

				// Legacy identifiers should still render an icon (they map to valid new icons)
				const iconElements = container.querySelectorAll( '.faq-accordion-icon' );
				expect( iconElements.length ).toBeGreaterThan( 0 );

				// The icon element should contain either an SVG element or the mocked Icon span
				const firstIcon = iconElements[ 0 ];
				const hasSvg = firstIcon.querySelector( 'svg' ) !== null;
				const hasMockedIcon = firstIcon.querySelector( '[data-testid="icon"]' ) !== null;
				expect( hasSvg || hasMockedIcon ).toBe( true );
			} ),
			{ numRuns: 50 }
		);
	} );

	it( 'when iconPosition is "none", no .faq-accordion-icon element is rendered regardless of selectedIcon', () => {
		fc.assert(
			fc.property( validIconWithSvgArb, ( iconId ) => {
				const attributes = {
					...baseAttributes,
					selectedIcon: iconId,
					iconPosition: 'none',
				};

				const { container } = render(
					<Edit
						attributes={ attributes }
						setAttributes={ jest.fn() }
					/>
				);

				// With iconPosition "none", no icon should render
				const iconElements = container.querySelectorAll( '.faq-accordion-icon' );
				expect( iconElements.length ).toBe( 0 );
			} ),
			{ numRuns: 50 }
		);
	} );
} );
