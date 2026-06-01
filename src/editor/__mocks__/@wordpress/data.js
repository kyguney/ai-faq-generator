/**
 * Mock for @wordpress/data.
 */
const useSelect = jest.fn( () => null );
const useDispatch = jest.fn( () => ( {} ) );

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

const select = jest.fn( () => ( {} ) );

module.exports = {
	useSelect,
	useDispatch,
	dispatch,
	select,
	__mockInsertBlocks: insertBlocks,
};
