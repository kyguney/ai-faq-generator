/**
 * Mock for @wordpress/blocks.
 */
const createBlock = ( name, attributes ) => {
	return { name, attributes };
};

module.exports = {
	createBlock,
};
