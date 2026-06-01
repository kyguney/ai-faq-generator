/**
 * Mock for @wordpress/i18n.
 *
 * Passthrough functions that return the input string.
 */
module.exports = {
	__: ( str ) => str,
	_x: ( str ) => str,
	_n: ( single, plural, number ) => ( number === 1 ? single : plural ),
	_nx: ( single, plural, number ) => ( number === 1 ? single : plural ),
	sprintf: ( format, ...args ) => {
		let i = 0;
		return format.replace( /%[sd]/g, () => args[ i++ ] );
	},
};
