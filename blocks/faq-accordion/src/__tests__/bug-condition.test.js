/**
 * Bug Condition Exploration Tests for FAQ Accordion Block.
 *
 * These tests encode the EXPECTED (correct) behavior for the three bugs.
 * They are written BEFORE any fixes and are expected to FAIL on unfixed code,
 * confirming the bugs exist. Once the fixes are applied, these tests should PASS.
 *
 * Bug 1: Preview Toggle — layoutMode="preview" should render visual preview
 * Bug 2: Duplicate Icons — .faq-accordion-icon CSS should NOT have border-chevron
 * Bug 3: Frontend Styles — render.php inline styles should contain !important
 *
 * @package AiFaqGenerator
 */

import { render } from '@testing-library/react';
import '@testing-library/jest-dom';
import * as fc from 'fast-check';
import * as fs from 'fs';
import * as path from 'path';

import Edit from '../edit';

// Resolve paths relative to the block root (blocks/faq-accordion/)
const BLOCK_ROOT = path.resolve( __dirname, '../..' );
const STYLE_CSS_PATH = path.join( BLOCK_ROOT, 'style.css' );
const RENDER_PHP_PATH = path.join( BLOCK_ROOT, 'render.php' );

/**
 * Validates: Requirements 2.1
 *
 * Property 1 — Bug 1: Preview Toggle Renders Visual Accordion
 *
 * For any block state where layoutMode === 'preview' and the block has at
 * least one FAQ item, the editor SHALL render .faq-accordion-summary elements
 * (visual preview) and SHALL NOT render .faq-item-editor elements (edit mode).
 */
describe( 'Bug 1 — Preview Toggle', () => {
	it( 'renders .faq-accordion-summary elements when layoutMode is "preview" with FAQ items', () => {
		// Suppress expected console.error from mocked components
		const consoleSpy = jest.spyOn( console, 'error' ).mockImplementation( () => {} );

		fc.assert(
			fc.property(
				fc.array(
					fc.record( {
						question: fc.string( { minLength: 1, maxLength: 50 } ),
						answer: fc.string( { minLength: 1, maxLength: 100 } ),
					} ),
					{ minLength: 1, maxLength: 5 }
				),
				( items ) => {
					const { container, unmount } = render(
						<Edit
							attributes={ {
								items,
								layoutMode: 'preview',
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
								selectedIcon: 'chevron',
							} }
							setAttributes={ jest.fn() }
						/>
					);

					// Visual preview should render .faq-accordion-summary elements
					const summaryElements = container.querySelectorAll( '.faq-accordion-summary' );
					expect( summaryElements.length ).toBeGreaterThan( 0 );

					// Edit mode elements should NOT be present
					const editorElements = container.querySelectorAll( '.faq-item-editor' );
					expect( editorElements.length ).toBe( 0 );

					unmount();
				}
			),
			{ numRuns: 20 }
		);

		consoleSpy.mockRestore();
	} );
} );

/**
 * Validates: Requirements 2.2
 *
 * Property 1 — Bug 2: Single Icon Display (No CSS Border-Chevron)
 *
 * The .faq-accordion-icon CSS rule in style.css SHALL NOT contain border-right
 * or border-bottom properties, which create a duplicate CSS-drawn chevron
 * alongside the text character icon.
 */
describe( 'Bug 2 — Duplicate Icons', () => {
	it( '.faq-accordion-icon CSS rule does NOT contain border-right or border-bottom properties', () => {
		const cssContent = fs.readFileSync( STYLE_CSS_PATH, 'utf-8' );

		// Extract the .faq-accordion-icon rule block
		const iconRuleRegex = /\.wp-block-wpbits-faq-accordion\s+\.faq-accordion-icon\s*\{([^}]*)\}/;
		const match = cssContent.match( iconRuleRegex );

		expect( match ).not.toBeNull();

		const ruleBody = match[ 1 ];

		// Assert that the rule does NOT contain border-right or border-bottom
		// These create a CSS-drawn chevron that duplicates the text character icon
		expect( ruleBody ).not.toMatch( /border-right/ );
		expect( ruleBody ).not.toMatch( /border-bottom/ );
	} );

	it( 'for any icon type, the CSS icon rule should be a neutral container without border-chevron', () => {
		fc.assert(
			fc.property(
				fc.constantFrom( 'chevron', 'chevron-right', 'plus', 'arrow' ),
				fc.constantFrom( 'left', 'right' ),
				( selectedIcon, iconPosition ) => {
					const cssContent = fs.readFileSync( STYLE_CSS_PATH, 'utf-8' );

					// The .faq-accordion-icon rule should be a neutral container
					// without border-based drawing properties
					const iconRuleRegex = /\.wp-block-wpbits-faq-accordion\s+\.faq-accordion-icon\s*\{([^}]*)\}/;
					const match = cssContent.match( iconRuleRegex );

					expect( match ).not.toBeNull();

					const ruleBody = match[ 1 ];

					// No border-right or border-bottom (these create CSS chevrons)
					expect( ruleBody ).not.toMatch( /border-right/ );
					expect( ruleBody ).not.toMatch( /border-bottom/ );

					// Should not have transform: rotate(-45deg) which creates a chevron shape
					expect( ruleBody ).not.toMatch( /transform:\s*rotate\(-45deg\)/ );
				}
			),
			{ numRuns: 10 }
		);
	} );
} );

/**
 * Validates: Requirements 2.3
 *
 * Property 1 — Bug 3: Inline Style Application (Frontend Styles with !important)
 *
 * For any block state where custom styling attributes are set (non-empty titleColor,
 * titleFontSize > 0, titlePadding ≠ 16), the frontend rendered output (render.php)
 * SHALL contain !important declarations in its inline styles to override CSS defaults.
 */
describe( 'Bug 3 — Frontend Styles', () => {
	it( 'render.php generates inline styles with !important for custom title color', () => {
		const phpContent = fs.readFileSync( RENDER_PHP_PATH, 'utf-8' );

		// Check that the PHP code appends !important to the color inline style value
		const lines = phpContent.split( '\n' );
		const titleColorLine = lines.find( ( line ) =>
			line.includes( 'summary_styles_arr[]' ) && line.includes( "'color:" )
		);
		expect( titleColorLine ).toBeDefined();
		expect( titleColorLine ).toContain( '!important' );
	} );

	it( 'render.php generates inline styles with !important for custom font-size', () => {
		const phpContent = fs.readFileSync( RENDER_PHP_PATH, 'utf-8' );

		// Check font-size line includes !important
		const lines = phpContent.split( '\n' );
		const fontSizeLine = lines.find( ( line ) =>
			line.includes( 'summary_styles_arr[]' ) && line.includes( "'font-size:" )
		);
		expect( fontSizeLine ).toBeDefined();
		expect( fontSizeLine ).toContain( '!important' );
	} );

	it( 'for any non-default styling attributes, render.php style builder includes !important', () => {
		fc.assert(
			fc.property(
				fc.record( {
					titleColor: fc.hexaString( { minLength: 6, maxLength: 6 } ).map( ( s ) => '#' + s ),
					titleFontSize: fc.integer( { min: 12, max: 48 } ),
					titlePadding: fc.integer( { min: 0, max: 64 } ).filter( ( v ) => v !== 16 ),
				} ),
				( { titleColor, titleFontSize, titlePadding } ) => {
					const phpContent = fs.readFileSync( RENDER_PHP_PATH, 'utf-8' );

					// Extract the style builder section from render.php
					const styleBuilderStart = phpContent.indexOf( '// Build summary (title area) inline styles' );
					const styleBuilderEnd = phpContent.indexOf( '// Legacy icon identifier mapping' );
					const styleBuilderSection = phpContent.substring( styleBuilderStart, styleBuilderEnd );

					// For any non-default attributes, the PHP style builder should include !important
					expect( styleBuilderSection ).toMatch( /!important/ );
				}
			),
			{ numRuns: 10 }
		);
	} );
} );
