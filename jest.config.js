/**
 * Jest configuration for the AI FAQ Generator plugin.
 */
const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/jest-unit.config' );

module.exports = {
	...defaultConfig,
	rootDir: '.',
	moduleNameMapper: {
		...( defaultConfig.moduleNameMapper || {} ),
		'^react$': path.resolve( __dirname, 'node_modules/react' ),
		'^react-dom$': path.resolve( __dirname, 'node_modules/react-dom' ),
		'^react-dom/(.*)$': path.resolve( __dirname, 'node_modules/react-dom/$1' ),
		'^@wordpress/data$': '<rootDir>/src/editor/__mocks__/@wordpress/data.js',
		'^@wordpress/editor$': '<rootDir>/src/editor/__mocks__/@wordpress/editor.js',
		'^@wordpress/components$': '<rootDir>/src/editor/__mocks__/@wordpress/components.js',
		'^@wordpress/core-data$': '<rootDir>/src/editor/__mocks__/@wordpress/core-data.js',
		'^@wordpress/element$': '<rootDir>/src/editor/__mocks__/@wordpress/element.js',
		'^@wordpress/plugins$': '<rootDir>/src/editor/__mocks__/@wordpress/plugins.js',
		'^@wordpress/blocks$': '<rootDir>/src/editor/__mocks__/@wordpress/blocks.js',
		'^@wordpress/icons$': '<rootDir>/src/editor/__mocks__/@wordpress/icons.js',
		'^@wordpress/i18n$': '<rootDir>/src/editor/__mocks__/@wordpress/i18n.js',
		'^@wordpress/block-editor$': '<rootDir>/src/editor/__mocks__/@wordpress/block-editor.js',
	},
};
