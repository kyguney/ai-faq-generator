/**
 * Mock for @wordpress/editor.
 */
const React = require( 'react' );

const PluginDocumentSettingPanel = ( { children, title, name } ) => {
	return React.createElement(
		'div',
		{ 'data-testid': 'plugin-document-setting-panel', 'data-title': title, 'data-name': name },
		React.createElement( 'h2', null, title ),
		children
	);
};

module.exports = {
	PluginDocumentSettingPanel,
};
