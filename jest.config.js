/**
 * Jest configuration for the AI FAQ Generator plugin.
 */
const defaultConfig = require( '@wordpress/scripts/config/jest-unit.config' );

module.exports = {
	...defaultConfig,
	rootDir: '.',
	moduleNameMapper: {
		...( defaultConfig.moduleNameMapper || {} ),
		'^@wordpress/data$': '<rootDir>/src/editor/__mocks__/@wordpress/data.js',
		'^@wordpress/editor$': '<rootDir>/src/editor/__mocks__/@wordpress/editor.js',
		'^@wordpress/components$': '<rootDir>/src/editor/__mocks__/@wordpress/components.js',
		'^@wordpress/core-data$': '<rootDir>/src/editor/__mocks__/@wordpress/core-data.js',
		'^@wordpress/element$': '<rootDir>/src/editor/__mocks__/@wordpress/element.js',
		'^@wordpress/plugins$': '<rootDir>/src/editor/__mocks__/@wordpress/plugins.js',
		'^@wordpress/blocks$': '<rootDir>/src/editor/__mocks__/@wordpress/blocks.js',
	},
};
