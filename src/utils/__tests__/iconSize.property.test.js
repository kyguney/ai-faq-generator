/**
 * Property-based tests for SVG icon proportional sizing.
 *
 * Feature: accordion-style-enhancements, Property 4: SVG icon proportional sizing
 *
 * For any title font size value, the rendered SVG icon dimensions SHALL equal
 * `Math.round(titleFontSize * 1.1)` when the font size is greater than 0, and
 * SHALL equal 20 pixels when the font size is 0 or unset.
 *
 * **Validates: Requirements 7.7**
 *
 * @package AiFaqGenerator
 */

import * as fc from 'fast-check';
import { getIconSize, DEFAULT_ICON_SIZE } from '../iconRegistry';

describe( 'Feature: accordion-style-enhancements, Property 4: SVG icon proportional sizing', () => {
	it( 'for any positive font size, getIconSize returns Math.round(fontSize * 1.1)', () => {
		fc.assert(
			fc.property(
				fc.integer( { min: 1, max: 200 } ),
				( fontSize ) => {
					const result = getIconSize( fontSize );
					const expected = Math.round( fontSize * 1.1 );
					expect( result ).toBe( expected );
				}
			),
			{ numRuns: 200 }
		);
	} );

	it( 'for zero or falsy values, getIconSize returns DEFAULT_ICON_SIZE (20)', () => {
		fc.assert(
			fc.property(
				fc.constantFrom( 0, -1, -100, null, undefined, NaN ),
				( falsyValue ) => {
					const result = getIconSize( falsyValue );
					expect( result ).toBe( DEFAULT_ICON_SIZE );
					expect( result ).toBe( 20 );
				}
			),
			{ numRuns: 50 }
		);
	} );

	it( 'for negative font sizes, getIconSize returns DEFAULT_ICON_SIZE (20)', () => {
		fc.assert(
			fc.property(
				fc.integer( { min: -1000, max: -1 } ),
				( negativeFontSize ) => {
					const result = getIconSize( negativeFontSize );
					expect( result ).toBe( DEFAULT_ICON_SIZE );
				}
			),
			{ numRuns: 100 }
		);
	} );

	it( 'getIconSize with no argument returns DEFAULT_ICON_SIZE (20)', () => {
		expect( getIconSize() ).toBe( 20 );
		expect( getIconSize( undefined ) ).toBe( 20 );
		expect( getIconSize( null ) ).toBe( 20 );
		expect( getIconSize( 0 ) ).toBe( 20 );
	} );
} );
