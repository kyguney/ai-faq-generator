/**
 * Mock for @wordpress/data.
 */
const useSelect = jest.fn( () => null );

const insertBlocks = jest.fn();

const dispatch = jest.fn( ( storeName ) => {
	if ( storeName === 'core/block-editor' ) {
		return { insertBlocks };
	}
	// Default: core/notices or any other store.
	return {
		createNotice: jest.fn(),
		removeNotice: jest.fn(),
	};
} );

module.exports = {
	useSelect,
	dispatch,
	__mockInsertBlocks: insertBlocks,
};
