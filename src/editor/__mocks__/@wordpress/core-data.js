/**
 * Mock for @wordpress/core-data.
 */
const useEntityProp = jest.fn( () => [ {}, jest.fn() ] );

module.exports = {
	useEntityProp,
};
