/**
 * Mock for @wordpress/data.
 */
const useSelect = jest.fn( () => null );
const dispatch = jest.fn( () => ( {
	createNotice: jest.fn(),
	removeNotice: jest.fn(),
} ) );

module.exports = {
	useSelect,
	dispatch,
};
