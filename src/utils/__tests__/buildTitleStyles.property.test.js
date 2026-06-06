/**
 * Property-based tests for buildTitleHeadingStyle utility.
 *
 * Property 2: Title font styling application
 *
 * For any combination of title font styling attributes, `buildTitleHeadingStyle`
 * SHALL produce a style object containing exactly those CSS properties whose
 * attribute values are non-empty and non-default, and SHALL omit all properties
 * whose attribute values are empty or default.
 *
 * Validates: Requirements 3.3, 4.4, 5.4, 6.3, 6.4
 *
 * @package AiFaqGenerator
 */

import * as fc from 'fast-check';
import { buildTitleHeadingStyle } from '../buildTitleStyles';

// --- Generators ---

const fontWeightArb = fc.constantFrom( '', '400', '500', '600', '700', '800' );
const fontStyleArb = fc.constantFrom( '', 'italic', 'normal' );
const textDecorationArb = fc.constantFrom( '', 'underline', 'line-through', 'none' );
const textTransformArb = fc.constantFrom( '', 'none', 'uppercase', 'lowercase', 'capitalize' );

/**
 * Generator for a full combination of title font styling attributes.
 */
const titleFontAttrsArb = fc.record( {
	titleFontWeight: fontWeightArb,
	titleFontStyle: fontStyleArb,
	titleTextDecoration: textDecorationArb,
	titleTextTransform: textTransformArb,
} );

// --- Property 2: Title font styling application ---

describe( 'Property 2: Title font styling application', () => {
	/**
	 * Validates: Requirements 3.3, 4.4, 5.4, 6.3, 6.4
	 */

	it( 'fontWeight is present in output if and only if input titleFontWeight is non-empty', () => {
		fc.assert(
			fc.property( titleFontAttrsArb, ( attrs ) => {
				const result = buildTitleHeadingStyle( attrs );

				if ( attrs.titleFontWeight !== '' ) {
					expect( result.fontWeight ).toBe( attrs.titleFontWeight );
				} else {
					expect( result ).not.toHaveProperty( 'fontWeight' );
				}
			} ),
			{ numRuns: 200 }
		);
	} );

	it( 'fontStyle is present in output if and only if input titleFontStyle is exactly "italic"', () => {
		fc.assert(
			fc.property( titleFontAttrsArb, ( attrs ) => {
				const result = buildTitleHeadingStyle( attrs );

				if ( attrs.titleFontStyle === 'italic' ) {
					expect( result.fontStyle ).toBe( 'italic' );
				} else {
					expect( result ).not.toHaveProperty( 'fontStyle' );
				}
			} ),
			{ numRuns: 200 }
		);
	} );

	it( 'textDecoration is present in output if and only if input titleTextDecoration is exactly "underline"', () => {
		fc.assert(
			fc.property( titleFontAttrsArb, ( attrs ) => {
				const result = buildTitleHeadingStyle( attrs );

				if ( attrs.titleTextDecoration === 'underline' ) {
					expect( result.textDecoration ).toBe( 'underline' );
				} else {
					expect( result ).not.toHaveProperty( 'textDecoration' );
				}
			} ),
			{ numRuns: 200 }
		);
	} );

	it( 'textTransform is present in output if and only if input titleTextTransform is non-empty AND not "none"', () => {
		fc.assert(
			fc.property( titleFontAttrsArb, ( attrs ) => {
				const result = buildTitleHeadingStyle( attrs );

				if ( attrs.titleTextTransform !== '' && attrs.titleTextTransform !== 'none' ) {
					expect( result.textTransform ).toBe( attrs.titleTextTransform );
				} else {
					expect( result ).not.toHaveProperty( 'textTransform' );
				}
			} ),
			{ numRuns: 200 }
		);
	} );

	it( 'output contains no extra properties beyond those matching the inclusion conditions', () => {
		fc.assert(
			fc.property( titleFontAttrsArb, ( attrs ) => {
				const result = buildTitleHeadingStyle( attrs );
				const keys = Object.keys( result );

				// Build the expected set of keys
				const expectedKeys = [];

				if ( attrs.titleFontWeight !== '' ) {
					expectedKeys.push( 'fontWeight' );
				}
				if ( attrs.titleFontStyle === 'italic' ) {
					expectedKeys.push( 'fontStyle' );
				}
				if ( attrs.titleTextDecoration === 'underline' ) {
					expectedKeys.push( 'textDecoration' );
				}
				if ( attrs.titleTextTransform !== '' && attrs.titleTextTransform !== 'none' ) {
					expectedKeys.push( 'textTransform' );
				}

				expect( keys.sort() ).toEqual( expectedKeys.sort() );
			} ),
			{ numRuns: 200 }
		);
	} );
} );
