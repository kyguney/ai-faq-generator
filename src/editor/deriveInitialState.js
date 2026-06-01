/**
 * Initial state derivation logic for the block-insert state machine.
 *
 * Determines the initial sidebar state based on block existence and post meta content.
 * Block presence takes priority over meta content (Requirement 1.3).
 */

/**
 * The default initial state shape for the sidebar state machine.
 */
export const INITIAL_STATE = {
	sidebarState: 'empty',
	activeBlockClientId: null,
	faqCount: 0,
	isRegenerating: false,
	isGenerating: false,
	error: null,
};

/**
 * Parse a meta value to determine if it contains a valid FAQ array.
 *
 * @param {*} metaValue The raw post meta value.
 * @return {{ valid: boolean, count: number }} Whether the meta is valid and the FAQ count.
 */
function parseMetaValue( metaValue ) {
	if ( ! metaValue ) {
		return { valid: false, count: 0 };
	}

	try {
		const parsed = JSON.parse( metaValue );
		if ( Array.isArray( parsed ) && parsed.length > 0 ) {
			return { valid: true, count: parsed.length };
		}
	} catch ( e ) {
		// Invalid JSON — treat as no data.
	}

	return { valid: false, count: 0 };
}

/**
 * Derive the initial state for the sidebar state machine.
 *
 * Decision table:
 * | Block Exists? | Meta Valid? | Initial State    |
 * |---------------|-------------|------------------|
 * | Yes           | Any         | `block_inserted` |
 * | No            | Yes         | `has_faqs`       |
 * | No            | No          | `empty`          |
 *
 * @param {boolean}     blockExists Whether a FAQ block exists in the post content.
 * @param {string|null} clientId    The clientId of the detected FAQ block, or null.
 * @param {*}           metaValue   The raw `_aifaq_generated_faqs` post meta value.
 * @return {import('./sidebarReducer').BlockInsertState} The derived initial state.
 */
export function deriveInitialState( blockExists, clientId, metaValue ) {
	if ( blockExists ) {
		return {
			...INITIAL_STATE,
			sidebarState: 'block_inserted',
			activeBlockClientId: clientId,
		};
	}

	const { valid, count } = parseMetaValue( metaValue );

	if ( valid ) {
		return {
			...INITIAL_STATE,
			sidebarState: 'has_faqs',
			faqCount: count,
		};
	}

	return { ...INITIAL_STATE };
}
