/**
 * WordPress dependencies
 */
import { useState } from '@wordpress/element';
import { RangeControl, Button, ButtonGroup, BaseControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { link, linkOff } from '@wordpress/icons';

/**
 * Predefined padding presets.
 */
const PRESETS = [
	{ label: __( 'None', 'ai-faq-generator' ), value: { top: '0px', right: '0px', bottom: '0px', left: '0px' } },
	{ label: __( 'Tiny', 'ai-faq-generator' ), value: { top: '4px', right: '8px', bottom: '4px', left: '8px' } },
	{ label: __( 'Small', 'ai-faq-generator' ), value: { top: '8px', right: '12px', bottom: '8px', left: '12px' } },
	{ label: __( 'Regular', 'ai-faq-generator' ), value: { top: '16px', right: '20px', bottom: '16px', left: '20px' } },
	{ label: __( 'Large', 'ai-faq-generator' ), value: { top: '24px', right: '32px', bottom: '24px', left: '32px' } },
];

/**
 * Parse a CSS value like "16px" to a number.
 */
function parsePx( val ) {
	return parseInt( val, 10 ) || 0;
}

/**
 * PaddingControl — mimics the native WordPress spacing dimensions panel.
 *
 * Default view: preset buttons (None, Tiny, Small, Regular, Large) + Custom button
 * Custom view: 2 sliders (Top/Bottom, Left/Right) with a link/unlink toggle to expand to 4 sliders
 *
 * @param {Object}   props
 * @param {string}   props.label    - Control label.
 * @param {Object}   props.values   - { top, right, bottom, left } with "Xpx" strings.
 * @param {Function} props.onChange - Callback with updated values object.
 */
export default function PaddingControl( { label, values, onChange } ) {
	const [ isCustom, setIsCustom ] = useState( false );
	const [ isUnlinked, setIsUnlinked ] = useState( false );

	const top = parsePx( values?.top );
	const right = parsePx( values?.right );
	const bottom = parsePx( values?.bottom );
	const left = parsePx( values?.left );

	// Check if current values match a preset
	const activePreset = PRESETS.find(
		( p ) =>
			parsePx( p.value.top ) === top &&
			parsePx( p.value.right ) === right &&
			parsePx( p.value.bottom ) === bottom &&
			parsePx( p.value.left ) === left
	);

	const handlePreset = ( preset ) => {
		onChange( preset.value );
		setIsCustom( false );
	};

	const handleVerticalChange = ( val ) => {
		onChange( {
			...values,
			top: `${ val }px`,
			bottom: `${ val }px`,
		} );
	};

	const handleHorizontalChange = ( val ) => {
		onChange( {
			...values,
			right: `${ val }px`,
			left: `${ val }px`,
		} );
	};

	return (
		<BaseControl label={ label } className="faq-padding-control">
			{ ! isCustom ? (
				<div style={ { display: 'flex', flexWrap: 'wrap', gap: '4px', marginTop: '8px' } }>
					{ PRESETS.map( ( preset ) => (
						<Button
							key={ preset.label }
							isPressed={ activePreset?.label === preset.label }
							onClick={ () => handlePreset( preset ) }
							variant="secondary"
							size="small"
						>
							{ preset.label }
						</Button>
					) ) }
					<Button
						onClick={ () => setIsCustom( true ) }
						isPressed={ ! activePreset }
						variant="secondary"
						size="small"
					>
						{ __( 'Custom', 'ai-faq-generator' ) }
					</Button>
				</div>
			) : (
				<div style={ { marginTop: '8px' } }>
					<div style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '8px' } }>
						<Button
							onClick={ () => setIsCustom( false ) }
							variant="tertiary"
							size="small"
						>
							{ __( '← Presets', 'ai-faq-generator' ) }
						</Button>
						<Button
							icon={ isUnlinked ? linkOff : link }
							label={ isUnlinked ? __( 'Link sides', 'ai-faq-generator' ) : __( 'Unlink sides', 'ai-faq-generator' ) }
							onClick={ () => setIsUnlinked( ! isUnlinked ) }
							isPressed={ isUnlinked }
							size="small"
						/>
					</div>
					{ ! isUnlinked ? (
						<>
							<RangeControl
								label={ __( 'Top / Bottom', 'ai-faq-generator' ) }
								value={ top }
								onChange={ handleVerticalChange }
								min={ 0 }
								max={ 60 }
								step={ 1 }
								withInputField={ true }
							/>
							<RangeControl
								label={ __( 'Left / Right', 'ai-faq-generator' ) }
								value={ right }
								onChange={ handleHorizontalChange }
								min={ 0 }
								max={ 60 }
								step={ 1 }
								withInputField={ true }
							/>
						</>
					) : (
						<>
							<RangeControl
								label={ __( 'Top', 'ai-faq-generator' ) }
								value={ top }
								onChange={ ( val ) => onChange( { ...values, top: `${ val }px` } ) }
								min={ 0 }
								max={ 60 }
								step={ 1 }
								withInputField={ true }
							/>
							<RangeControl
								label={ __( 'Right', 'ai-faq-generator' ) }
								value={ right }
								onChange={ ( val ) => onChange( { ...values, right: `${ val }px` } ) }
								min={ 0 }
								max={ 60 }
								step={ 1 }
								withInputField={ true }
							/>
							<RangeControl
								label={ __( 'Bottom', 'ai-faq-generator' ) }
								value={ bottom }
								onChange={ ( val ) => onChange( { ...values, bottom: `${ val }px` } ) }
								min={ 0 }
								max={ 60 }
								step={ 1 }
								withInputField={ true }
							/>
							<RangeControl
								label={ __( 'Left', 'ai-faq-generator' ) }
								value={ left }
								onChange={ ( val ) => onChange( { ...values, left: `${ val }px` } ) }
								min={ 0 }
								max={ 60 }
								step={ 1 }
								withInputField={ true }
							/>
						</>
					) }
				</div>
			) }
		</BaseControl>
	);
}
