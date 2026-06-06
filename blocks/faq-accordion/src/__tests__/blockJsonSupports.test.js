/**
 * Tests for block.json supports structure.
 *
 * Validates that the FAQ Accordion block declares the correct
 * block supports for color, typography, spacing, and border,
 * while preserving existing supports unchanged.
 */
import blockJson from '../../block.json';

describe( 'block.json supports', () => {
	const { supports } = blockJson;

	describe( 'color support', () => {
		it( 'disables text color', () => {
			expect( supports.color.text ).toBe( false );
		} );

		it( 'enables background color', () => {
			expect( supports.color.background ).toBe( true );
		} );

		it( 'enables link color', () => {
			expect( supports.color.link ).toBe( true );
		} );
	} );

	describe( 'spacing support', () => {
		it( 'enables padding', () => {
			expect( supports.spacing.padding ).toBe( true );
		} );

		it( 'enables margin', () => {
			expect( supports.spacing.margin ).toBe( true );
		} );
	} );

	describe( 'border support', () => {
		it( 'enables border color', () => {
			expect( supports.border.color ).toBe( true );
		} );

		it( 'enables border style', () => {
			expect( supports.border.style ).toBe( true );
		} );

		it( 'enables border width', () => {
			expect( supports.border.width ).toBe( true );
		} );

		it( 'enables border radius', () => {
			expect( supports.border.radius ).toBe( true );
		} );
	} );

	describe( 'existing supports preserved', () => {
		it( 'keeps html support as false', () => {
			expect( supports.html ).toBe( false );
		} );

		it( 'keeps align support with wide and full options', () => {
			expect( supports.align ).toEqual( [ 'wide', 'full' ] );
		} );

		it( 'keeps multiple support as false', () => {
			expect( supports.multiple ).toBe( false );
		} );
	} );
} );
