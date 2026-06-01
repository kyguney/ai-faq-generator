/**
 * Recursively searches a block tree for the first `wpbits/faq-accordion` block.
 *
 * Traverses blocks and their innerBlocks in depth-first (document) order,
 * returning the first FAQ accordion block found.
 *
 * @param {Array} blocks - Array of block objects from the block editor.
 * @return {{ clientId: string, items: Array }|null} The first FAQ block's clientId and items, or null if not found.
 */
export function findFaqBlock( blocks ) {
	for ( const block of blocks ) {
		if ( block.name === 'wpbits/faq-accordion' ) {
			return { clientId: block.clientId, items: block.attributes.items };
		}
		if ( block.innerBlocks?.length ) {
			const found = findFaqBlock( block.innerBlocks );
			if ( found ) {
				return found;
			}
		}
	}
	return null;
}
