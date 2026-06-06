/**
 * Property-based tests for iconRegistry.js
 *
 * **Validates: Requirements 8.1, 8.2, 8.3, 8.4**
 */
import fc from 'fast-check';
import { resolveIconId, LEGACY_ICON_MAP, ICON_REGISTRY } from '../iconRegistry';

const VALID_ICON_IDS = Object.keys( ICON_REGISTRY );
const LEGACY_IDS = Object.keys( LEGACY_ICON_MAP );
const ALL_KNOWN_IDS = [ ...VALID_ICON_IDS, ...LEGACY_IDS ];

describe( 'iconRegistry property tests', () => {
	/**
	 * Property 5: Legacy icon migration mapping
	 *
	 * For any legacy icon identifier, resolveIconId returns the corresponding new identifier.
	 *
	 * **Validates: Requirements 8.1, 8.2, 8.3**
	 */
	describe( 'Property 5: Legacy icon migration mapping', () => {
		it( 'maps all legacy identifiers to their correct new identifiers', () => {
			const expectedMapping = {
				chevron: 'chevron-down',
				plus: 'plus-minus',
				arrow: 'arrow-down',
			};

			fc.assert(
				fc.property(
					fc.constantFrom( 'chevron', 'plus', 'arrow' ),
					( legacyId ) => {
						const result = resolveIconId( legacyId );
						expect( result ).toBe( expectedMapping[ legacyId ] );
					}
				)
			);
		} );
	} );

	/**
	 * Property 6: Unrecognized icon fallback
	 *
	 * For any string that is not a recognized icon identifier (neither valid nor legacy),
	 * resolveIconId returns "chevron-down" as the fallback.
	 *
	 * **Validates: Requirements 8.4**
	 */
	describe( 'Property 6: Unrecognized icon fallback', () => {
		it( 'returns "chevron-down" for any unrecognized string', () => {
			fc.assert(
				fc.property(
					fc.string().filter( ( s ) => ! ALL_KNOWN_IDS.includes( s ) ),
					( unknownId ) => {
						const result = resolveIconId( unknownId );
						expect( result ).toBe( 'chevron-down' );
					}
				)
			);
		} );
	} );
} );
