import { buildTitleHeadingStyle } from './buildTitleStyles';

describe( 'buildTitleHeadingStyle', () => {
	it( 'returns an empty object when all attributes are default/empty', () => {
		const result = buildTitleHeadingStyle( {
			titleFontWeight: '',
			titleFontStyle: '',
			titleTextDecoration: '',
			titleTextTransform: '',
		} );
		expect( result ).toEqual( {} );
	} );

	it( 'returns an empty object when attributes is undefined', () => {
		const result = buildTitleHeadingStyle( undefined );
		expect( result ).toEqual( {} );
	} );

	it( 'returns an empty object when attributes is null', () => {
		const result = buildTitleHeadingStyle( null );
		expect( result ).toEqual( {} );
	} );

	it( 'returns an empty object when attributes is an empty object', () => {
		const result = buildTitleHeadingStyle( {} );
		expect( result ).toEqual( {} );
	} );

	it( 'includes fontWeight when titleFontWeight is non-empty', () => {
		const result = buildTitleHeadingStyle( { titleFontWeight: '700' } );
		expect( result ).toEqual( { fontWeight: '700' } );
	} );

	it( 'includes fontStyle when titleFontStyle is "italic"', () => {
		const result = buildTitleHeadingStyle( { titleFontStyle: 'italic' } );
		expect( result ).toEqual( { fontStyle: 'italic' } );
	} );

	it( 'does not include fontStyle for non-italic values', () => {
		const result = buildTitleHeadingStyle( { titleFontStyle: 'normal' } );
		expect( result ).toEqual( {} );
	} );

	it( 'includes textDecoration when titleTextDecoration is "underline"', () => {
		const result = buildTitleHeadingStyle( {
			titleTextDecoration: 'underline',
		} );
		expect( result ).toEqual( { textDecoration: 'underline' } );
	} );

	it( 'does not include textDecoration for non-underline values', () => {
		const result = buildTitleHeadingStyle( {
			titleTextDecoration: 'line-through',
		} );
		expect( result ).toEqual( {} );
	} );

	it( 'includes textTransform when titleTextTransform is "uppercase"', () => {
		const result = buildTitleHeadingStyle( {
			titleTextTransform: 'uppercase',
		} );
		expect( result ).toEqual( { textTransform: 'uppercase' } );
	} );

	it( 'includes textTransform when titleTextTransform is "lowercase"', () => {
		const result = buildTitleHeadingStyle( {
			titleTextTransform: 'lowercase',
		} );
		expect( result ).toEqual( { textTransform: 'lowercase' } );
	} );

	it( 'includes textTransform when titleTextTransform is "capitalize"', () => {
		const result = buildTitleHeadingStyle( {
			titleTextTransform: 'capitalize',
		} );
		expect( result ).toEqual( { textTransform: 'capitalize' } );
	} );

	it( 'does not include textTransform when titleTextTransform is "none"', () => {
		const result = buildTitleHeadingStyle( {
			titleTextTransform: 'none',
		} );
		expect( result ).toEqual( {} );
	} );

	it( 'does not include textTransform when titleTextTransform is empty', () => {
		const result = buildTitleHeadingStyle( { titleTextTransform: '' } );
		expect( result ).toEqual( {} );
	} );

	it( 'includes all properties when all attributes are set to active values', () => {
		const result = buildTitleHeadingStyle( {
			titleFontWeight: '600',
			titleFontStyle: 'italic',
			titleTextDecoration: 'underline',
			titleTextTransform: 'uppercase',
		} );
		expect( result ).toEqual( {
			fontWeight: '600',
			fontStyle: 'italic',
			textDecoration: 'underline',
			textTransform: 'uppercase',
		} );
	} );
} );
