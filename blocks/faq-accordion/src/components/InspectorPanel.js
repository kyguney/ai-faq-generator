/**
 * WordPress dependencies
 */
import {
	PanelBody,
	SelectControl,
	ToggleControl,
	RangeControl,
	ColorPalette,
	Button,
	BaseControl,
	Dropdown,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { Icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { ICON_REGISTRY, resolveIconId } from '../../../../src/utils/iconRegistry';
import PaddingControl from './PaddingControl';

/**
 * ColorDropdown — compact color picker that shows a label + color indicator.
 * Clicking opens a dropdown with the full ColorPalette.
 * Matches the native WordPress "Background" / "Link" color row pattern.
 *
 * @param {Object}   props
 * @param {string}   props.label    - The control label.
 * @param {string}   props.value    - Current color value (hex/rgb/name or empty).
 * @param {Function} props.onChange - Callback when color changes.
 */
function ColorDropdown( { label, value, onChange } ) {
	return (
		<div
			className="faq-color-dropdown"
			style={ {
				display: 'flex',
				alignItems: 'center',
				justifyContent: 'space-between',
				padding: '8px 0',
				marginBottom: '8px',
				borderBottom: '1px solid #e0e0e0',
				cursor: 'pointer',
			} }
		>
			<Dropdown
				className="faq-color-dropdown__trigger"
				contentClassName="faq-color-dropdown__popover"
				popoverProps={ { placement: 'left-start' } }
				renderToggle={ ( { isOpen, onToggle } ) => (
					<button
						type="button"
						onClick={ onToggle }
						aria-expanded={ isOpen }
						aria-label={ label }
						style={ {
							display: 'flex',
							alignItems: 'center',
							gap: '8px',
							width: '100%',
							padding: '4px 0',
							background: 'none',
							border: 'none',
							cursor: 'pointer',
							fontSize: '12px',
							textTransform: 'uppercase',
							fontWeight: 500,
							letterSpacing: '0.5px',
							color: '#1e1e1e',
						} }
					>
						<span
							style={ {
								width: '24px',
								height: '24px',
								borderRadius: '50%',
								border: '1px solid #ddd',
								backgroundColor: value || 'transparent',
								backgroundImage: ! value
									? 'linear-gradient(45deg, #ccc 25%, transparent 25%), linear-gradient(-45deg, #ccc 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #ccc 75%), linear-gradient(-45deg, transparent 75%, #ccc 75%)'
									: 'none',
								backgroundSize: '8px 8px',
								backgroundPosition: '0 0, 0 4px, 4px -4px, -4px 0px',
								flexShrink: 0,
							} }
						/>
						<span>{ label }</span>
					</button>
				) }
				renderContent={ () => (
					<div style={ { padding: '16px', minWidth: '260px' } }>
						<ColorPalette
							value={ value || undefined }
							onChange={ ( newColor ) => onChange( newColor || '' ) }
						/>
					</div>
				) }
			/>
		</div>
	);
}

/**
 * InspectorPanel component.
 *
 * Renders the block settings panel in the sidebar with controls
 * for Open First Item, Icon Position, Enable Animation,
 * and styling options.
 *
 * @param {Object}   props
 * @param {Object}   props.attributes    - The block attributes.
 * @param {Function} props.setAttributes - Callback to update block attributes.
 */
export default function InspectorPanel( { attributes, setAttributes } ) {
	const {
		openFirstItem,
		iconPosition,
		enableAnimation,
		titleColor,
		titleFontSize,
		titleFontFamily,
		titlePadding,
		titleBackgroundColor,
		titleFontWeight,
		titleFontStyle,
		titleTextDecoration,
		titleTextTransform,
		contentColor,
		contentFontSize,
		contentFontFamily,
		contentPadding,
		contentBackgroundColor,
		itemSpacing,
		selectedIcon,
		iconColor,
		layoutMode,
	} = attributes;

	// Resolve the current icon identifier (handles legacy values)
	const resolvedIcon = resolveIconId( selectedIcon );

	// Font size options
	const FONT_SIZES = [
		{ value: '0', label: __( 'Default', 'ai-faq-generator' ) },
		{ value: '14', label: '14px' },
		{ value: '16', label: '16px' },
		{ value: '18', label: '18px' },
		{ value: '20', label: '20px' },
		{ value: '24', label: '24px' },
		{ value: '28', label: '28px' },
		{ value: '32', label: '32px' },
	];

	// Font families
	const FONT_FAMILIES = [
		{ value: '', label: __( 'Default', 'ai-faq-generator' ) },
		{ value: 'system-ui', label: 'System UI' },
		{ value: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif', label: 'Sans Serif' },
		{ value: 'Georgia, "Times New Roman", serif', label: 'Serif' },
		{ value: 'Monaco, Consolas, monospace', label: 'Monospace' },
	];

	// Font weight options
	const FONT_WEIGHTS = [
		{ value: '', label: __( 'Default', 'ai-faq-generator' ) },
		{ value: '400', label: __( 'Normal', 'ai-faq-generator' ) },
		{ value: '500', label: __( 'Medium', 'ai-faq-generator' ) },
		{ value: '600', label: __( 'Semi-Bold', 'ai-faq-generator' ) },
		{ value: '700', label: __( 'Bold', 'ai-faq-generator' ) },
		{ value: '800', label: __( 'Extra-Bold', 'ai-faq-generator' ) },
	];

	// Text transform options
	const TEXT_TRANSFORMS = [
		{ value: '', label: __( 'None', 'ai-faq-generator' ) },
		{ value: 'uppercase', label: __( 'Uppercase', 'ai-faq-generator' ) },
		{ value: 'lowercase', label: __( 'Lowercase', 'ai-faq-generator' ) },
		{ value: 'capitalize', label: __( 'Capitalize', 'ai-faq-generator' ) },
	];

	return (
		<>
			<PanelBody title={ __( 'Settings', 'ai-faq-generator' ) } initialOpen={ true }>
				<ToggleControl
					label={ __( 'Open first item', 'ai-faq-generator' ) }
					checked={ openFirstItem }
					onChange={ ( value ) =>
						setAttributes( { openFirstItem: value } )
					}
				/>
				<ToggleControl
					label={ __( 'Enable animation', 'ai-faq-generator' ) }
					checked={ enableAnimation }
					onChange={ ( value ) =>
						setAttributes( { enableAnimation: value } )
					}
				/>
				<ToggleControl
					label={ __( 'Visual Preview Mode', 'ai-faq-generator' ) }
					checked={ layoutMode === 'preview' }
					onChange={ ( value ) =>
						setAttributes( { layoutMode: value ? 'preview' : 'edit' } )
					}
				/>
			</PanelBody>

			<PanelBody title={ __( 'Icon', 'ai-faq-generator' ) } initialOpen={ false }>
				<BaseControl
					id="faq-accordion-icon-picker"
					className="faq-accordion-icon-picker-control"
				>
					<div
						className="faq-accordion-icon-grid"
						role="radiogroup"
						aria-label={ __( 'Accordion Icon', 'ai-faq-generator' ) }
						style={ {
							display: 'grid',
							gridTemplateColumns: 'repeat(3, 1fr)',
							gap: '4px',
							marginBottom: '16px',
						} }
					>
						{ Object.entries( ICON_REGISTRY ).map( ( [ iconId, iconEntry ] ) => (
							<Button
								key={ iconId }
								isPressed={ resolvedIcon === iconId }
								onClick={ () => setAttributes( { selectedIcon: iconId } ) }
								label={ iconEntry.label }
								style={ {
									display: 'flex',
									flexDirection: 'column',
									alignItems: 'center',
									justifyContent: 'center',
									padding: '10px 4px',
									height: '64px',
									borderRadius: '4px',
								} }
							>
								{ iconEntry.icon && (
									<Icon icon={ iconEntry.icon } size={ 24 } />
								) }
								{ iconEntry.svg && ! iconEntry.icon && (
									<span className="faq-accordion-icon-svg">{ iconEntry.svg }</span>
								) }
								{ ! iconEntry.icon && ! iconEntry.svg && (
									<span style={ { fontSize: '11px', color: '#757575' } }>{ __( 'None', 'ai-faq-generator' ) }</span>
								) }
								<span style={ { fontSize: '10px', marginTop: '4px', lineHeight: 1, opacity: 0.7 } }>
									{ iconEntry.label }
								</span>
							</Button>
						) ) }
					</div>
				</BaseControl>
				<SelectControl
					label={ __( 'Position', 'ai-faq-generator' ) }
					value={ iconPosition }
					options={ [
						{ label: __( 'Left', 'ai-faq-generator' ), value: 'left' },
						{ label: __( 'Right', 'ai-faq-generator' ), value: 'right' },
						{ label: __( 'None', 'ai-faq-generator' ), value: 'none' },
					] }
					onChange={ ( value ) =>
						setAttributes( { iconPosition: value } )
					}
				/>
				<ColorDropdown
					label={ __( 'Icon Color', 'ai-faq-generator' ) }
					value={ iconColor }
					onChange={ ( value ) => setAttributes( { iconColor: value } ) }
				/>
			</PanelBody>

			<PanelBody title={ __( 'Title Styling', 'ai-faq-generator' ) } initialOpen={ false }>
				<ColorDropdown
					label={ __( 'Background', 'ai-faq-generator' ) }
					value={ titleBackgroundColor }
					onChange={ ( value ) => setAttributes( { titleBackgroundColor: value } ) }
				/>
				<ColorDropdown
					label={ __( 'Text Color', 'ai-faq-generator' ) }
					value={ titleColor }
					onChange={ ( value ) => setAttributes( { titleColor: value } ) }
				/>
				<SelectControl
					label={ __( 'Font Size', 'ai-faq-generator' ) }
					value={ String( titleFontSize || 0 ) }
					options={ FONT_SIZES }
					onChange={ ( value ) =>
						setAttributes( { titleFontSize: parseInt( value, 10 ) } )
					}
				/>
				<SelectControl
					label={ __( 'Font Family', 'ai-faq-generator' ) }
					value={ titleFontFamily || '' }
					options={ FONT_FAMILIES }
					onChange={ ( value ) =>
						setAttributes( { titleFontFamily: value } )
					}
				/>
				<SelectControl
					label={ __( 'Font Weight', 'ai-faq-generator' ) }
					value={ titleFontWeight || '' }
					options={ FONT_WEIGHTS }
					onChange={ ( value ) =>
						setAttributes( { titleFontWeight: value } )
					}
				/>
				<div style={ { display: 'flex', gap: '16px', margin: '12px 0' } }>
					<ToggleControl
						label={ __( 'Italic', 'ai-faq-generator' ) }
						checked={ titleFontStyle === 'italic' }
						onChange={ ( value ) =>
							setAttributes( { titleFontStyle: value ? 'italic' : '' } )
						}
					/>
					<ToggleControl
						label={ __( 'Underline', 'ai-faq-generator' ) }
						checked={ titleTextDecoration === 'underline' }
						onChange={ ( value ) =>
							setAttributes( { titleTextDecoration: value ? 'underline' : '' } )
						}
					/>
				</div>
				<SelectControl
					label={ __( 'Text Transform', 'ai-faq-generator' ) }
					value={ titleTextTransform || '' }
					options={ TEXT_TRANSFORMS }
					onChange={ ( value ) =>
						setAttributes( { titleTextTransform: value } )
					}
				/>
				<PaddingControl
					label={ __( 'Padding', 'ai-faq-generator' ) }
					values={ titlePadding }
					onChange={ ( value ) =>
						setAttributes( { titlePadding: value } )
					}
				/>
			</PanelBody>

			<PanelBody title={ __( 'Content Styling', 'ai-faq-generator' ) } initialOpen={ false }>
				<ColorDropdown
					label={ __( 'Background', 'ai-faq-generator' ) }
					value={ contentBackgroundColor }
					onChange={ ( value ) => setAttributes( { contentBackgroundColor: value } ) }
				/>
				<ColorDropdown
					label={ __( 'Text Color', 'ai-faq-generator' ) }
					value={ contentColor }
					onChange={ ( value ) => setAttributes( { contentColor: value } ) }
				/>
				<SelectControl
					label={ __( 'Font Size', 'ai-faq-generator' ) }
					value={ String( contentFontSize || 0 ) }
					options={ FONT_SIZES }
					onChange={ ( value ) =>
						setAttributes( { contentFontSize: parseInt( value, 10 ) } )
					}
				/>
				<SelectControl
					label={ __( 'Font Family', 'ai-faq-generator' ) }
					value={ contentFontFamily || '' }
					options={ FONT_FAMILIES }
					onChange={ ( value ) =>
						setAttributes( { contentFontFamily: value } )
					}
				/>
				<PaddingControl
					label={ __( 'Padding', 'ai-faq-generator' ) }
					values={ contentPadding }
					onChange={ ( value ) =>
						setAttributes( { contentPadding: value } )
					}
				/>
			</PanelBody>

			<PanelBody title={ __( 'Spacing', 'ai-faq-generator' ) } initialOpen={ false }>
				<RangeControl
					label={ __( 'Item Spacing', 'ai-faq-generator' ) }
					value={ itemSpacing ?? 8 }
					onChange={ ( value ) =>
						setAttributes( { itemSpacing: value } )
					}
					min={ 0 }
					max={ 32 }
					step={ 2 }
					withInputField={ true }
					renderTooltipContent={ ( val ) => `${ val }px` }
				/>
			</PanelBody>
		</>
	);
}
