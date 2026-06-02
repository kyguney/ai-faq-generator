const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

// Main plugin scripts (admin UI)
const mainConfig = {
	...defaultConfig,
	entry: {
		index: './src/index.js',
		settings: './src/settings/index.js',
	},
};

// FAQ Accordion block
const faqAccordionBlockConfig = {
	...defaultConfig,
	entry: {
		index: './blocks/faq-accordion/src/index.js',
		frontend: './blocks/faq-accordion/src/frontend.js',
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( __dirname, 'blocks/faq-accordion/build' ),
	},
};

module.exports = [ mainConfig, faqAccordionBlockConfig ];
