/**
 * Tests for CSS custom property migration.
 *
 * Validates that the FAQ Accordion block stylesheet uses CSS custom
 * properties for all color values (no bare hardcoded hex colors remain)
 * and that each custom property defines the correct fallback value
 * matching the original design.
 *
 * Validates: Requirements 5.8, 7.2
 */
import fs from 'fs';
import path from 'path';

const styleCssPath = path.resolve(
	__dirname,
	'../../style.css'
);
const cssContent = fs.readFileSync( styleCssPath, 'utf-8' );

describe( 'CSS custom property migration', () => {
	describe( 'no hardcoded color values outside var() fallbacks', () => {
		const hardcodedColors = [
			'#ddd',
			'#f9f9f9',
			'#0073aa',
			'#f0f0f0',
			'#eee',
		];

		it.each( hardcodedColors )(
			'does not contain bare %s outside of var() context',
			( color ) => {
				// Remove all var() expressions to isolate bare usages
				const withoutVars = cssContent.replace(
					/var\([^)]+\)/g,
					'__VAR_PLACEHOLDER__'
				);
				expect( withoutVars ).not.toContain( color );
			}
		);
	} );

	describe( 'custom properties have correct fallback values', () => {
		const expectedMappings = [
			{
				property: '--wp--custom--faq-accordion--border-color',
				fallback: '#ddd',
			},
			{
				property: '--wp--custom--faq-accordion--header-background',
				fallback: '#f9f9f9',
			},
			{
				property: '--wp--custom--faq-accordion--accent-color',
				fallback: '#0073aa',
			},
			{
				property: '--wp--custom--faq-accordion--hover-background',
				fallback: '#f0f0f0',
			},
			{
				property: '--wp--custom--faq-accordion--separator-color',
				fallback: '#eee',
			},
		];

		it.each( expectedMappings )(
			'$property has fallback $fallback',
			( { property, fallback } ) => {
				const varPattern = new RegExp(
					`var\\(\\s*${ property.replace(
						/[.*+?^${}()|[\]\\]/g,
						'\\$&'
					) }\\s*,\\s*${ fallback.replace(
						/[.*+?^${}()|[\]\\]/g,
						'\\$&'
					) }\\s*\\)`
				);
				expect( cssContent ).toMatch( varPattern );
			}
		);
	} );
} );
