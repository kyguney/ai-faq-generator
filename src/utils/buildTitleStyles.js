/**
 * Builds an inline style object for the title heading tag.
 *
 * @param {Object} attributes - Block attributes containing title font styling values.
 * @returns {Object} A CSS style object with only non-empty/non-default properties.
 */
export function buildTitleHeadingStyle( attributes ) {
	const {
		titleFontWeight = '',
		titleFontStyle = '',
		titleTextDecoration = '',
		titleTextTransform = '',
	} = attributes || {};

	const style = {};

	if ( titleFontWeight && titleFontWeight !== '' ) {
		style.fontWeight = titleFontWeight;
	}
	if ( titleFontStyle === 'italic' ) {
		style.fontStyle = 'italic';
	}
	if ( titleTextDecoration === 'underline' ) {
		style.textDecoration = 'underline';
	}
	if (
		titleTextTransform &&
		titleTextTransform !== '' &&
		titleTextTransform !== 'none'
	) {
		style.textTransform = titleTextTransform;
	}

	return style;
}
