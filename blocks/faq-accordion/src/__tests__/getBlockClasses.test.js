/**
 * Tests for getBlockClasses utility function.
 */
import { getBlockClasses } from '../utils/getBlockClasses';

describe( 'getBlockClasses', () => {
	describe( 'icon position classes', () => {
		it( 'returns "has-icon-left" when iconPosition is "left"', () => {
			expect(
				getBlockClasses( { iconPosition: 'left', enableAnimation: false } )
			).toBe( 'has-icon-left' );
		} );

		it( 'returns "has-icon-right" when iconPosition is "right"', () => {
			expect(
				getBlockClasses( { iconPosition: 'right', enableAnimation: false } )
			).toBe( 'has-icon-right' );
		} );

		it( 'returns "has-no-icon" when iconPosition is "none"', () => {
			expect(
				getBlockClasses( { iconPosition: 'none', enableAnimation: false } )
			).toBe( 'has-no-icon' );
		} );

		it( 'falls back to "has-icon-left" when iconPosition is undefined', () => {
			expect(
				getBlockClasses( { enableAnimation: false } )
			).toBe( 'has-icon-left' );
		} );

		it( 'falls back to "has-icon-left" when iconPosition is an invalid string', () => {
			expect(
				getBlockClasses( { iconPosition: 'top', enableAnimation: false } )
			).toBe( 'has-icon-left' );
		} );
	} );

	describe( 'animation class', () => {
		it( 'appends "has-animation" when enableAnimation is true', () => {
			expect(
				getBlockClasses( { iconPosition: 'left', enableAnimation: true } )
			).toBe( 'has-icon-left has-animation' );
		} );

		it( 'does not append "has-animation" when enableAnimation is false', () => {
			expect(
				getBlockClasses( { iconPosition: 'left', enableAnimation: false } )
			).toBe( 'has-icon-left' );
		} );

		it( 'does not append "has-animation" when enableAnimation is undefined', () => {
			expect(
				getBlockClasses( { iconPosition: 'left' } )
			).toBe( 'has-icon-left' );
		} );
	} );

	describe( 'combined classes', () => {
		it( 'combines icon-right with animation', () => {
			expect(
				getBlockClasses( { iconPosition: 'right', enableAnimation: true } )
			).toBe( 'has-icon-right has-animation' );
		} );

		it( 'combines no-icon with animation', () => {
			expect(
				getBlockClasses( { iconPosition: 'none', enableAnimation: true } )
			).toBe( 'has-no-icon has-animation' );
		} );
	} );
} );
