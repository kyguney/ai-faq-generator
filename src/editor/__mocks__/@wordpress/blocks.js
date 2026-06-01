/**
 * Mock for @wordpress/blocks.
 */
const createBlock = ( name, attributes ) => {
	return { name, attributes, clientId: 'mock-client-id' };
};

module.exports = {
	createBlock,
};
