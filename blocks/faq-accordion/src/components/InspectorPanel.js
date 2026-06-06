/**
 * WordPress dependencies
 */
import { PanelBody, PanelRow, SelectControl, ToggleControl, TextControl, RangeControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * InspectorPanel component.
 *
 * Renders the block settings panel in the sidebar with controls
 * for Title Tag, Open First Item, Icon Position, Enable Animation,
 * and styling options.
 *
 * @param {Object}   props
 * @param {Object}   props.attributes    - The block attributes.
 * @param {Function} props.setAttributes - Callback to update block attributes.
 */
export default function InspectorPanel( { attributes, setAttributes } ) {
	const {
		titleTag,
		openFirstItem,
		iconPosition,
		enableAnimation,
		titleColor,
		titleFontSize,
		titleFontFamily,
		titlePadding,
		contentColor,
		contentFontSize,
		contentFontFamily,
		contentPadding,
		itemSpacing,
		selectedIcon,
		layoutMode,
	} = attributes;

	// Available icons for the accordion toggle
	const ICONS = [
		{ value: 'chevron', label: 'Chevron ▾' },
		{ value: 'chevron-right', label: 'Chevron Right ▸' },
		{ value: 'plus', label: 'Plus / Minus ±' },
		{ value: 'arrow', label: 'Arrow →' },
		{ value: 'none', label: 'None —' },
	];

	// Font size options
	const FONT_SIZES = [
		{ value: '0', label: 'Default' },
		{ value: '14', label: 'Small (14px)' },
		{ value: '16', label: 'Normal (16px)' },
		{ value: '18', label: 'Medium (18px)' },
		{ value: '20', label: 'Large (20px)' },
		{ value: '24', label: 'Larger (24px)' },
		{ value: '32', label: 'Huge (32px)' },
	];

	// Font families
	const FONT_FAMILIES = [
		{ value: '', label: 'Default' },
		{ value: 'system-ui', label: 'System UI' },
		{ value: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif', label: 'Sans Serif' },
		{ value: 'Georgia, "Times New Roman", serif', label: 'Serif' },
		{ value: 'Monaco, Consolas, monospace', label: 'Monospace' },
	];

	return (
		<>
			<PanelBody title={ __( 'FAQ Settings', 'ai-faq-generator' ) } initialOpen={ true }>
				<SelectControl
					label={ __( 'Title Tag', 'ai-faq-generator' ) }
					value={ titleTag }
					options={ [
						{ label: 'H2', value: 'h2' },
						{ label: 'H3', value: 'h3' },
						{ label: 'H4', value: 'h4' },
						{ label: 'H5', value: 'h5' },
						{ label: 'H6', value: 'h6' },
						{ label: 'Paragraph', value: 'p' },
					] }
					onChange={ ( value ) =>
						setAttributes( { titleTag: value } )
					}
				/>
				<ToggleControl
					label={ __( 'Open first item', 'ai-faq-generator' ) }
					checked={ openFirstItem }
					onChange={ ( value ) =>
						setAttributes( { openFirstItem: value } )
					}
				/>
				<SelectControl
					label={ __( 'Icon Position', 'ai-faq-generator' ) }
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

			<PanelBody title={ __( 'Icon Selection', 'ai-faq-generator' ) } initialOpen={ false }>
				<PanelRow>
					<SelectControl
						label={ __( 'Accordion Icon', 'ai-faq-generator' ) }
						value={ selectedIcon }
						options={ ICONS }
						onChange={ ( value ) =>
							setAttributes( { selectedIcon: value } )
						}
					/>
				</PanelRow>
			</PanelBody>

			<PanelBody title={ __( 'Title Styling', 'ai-faq-generator' ) } initialOpen={ false }>
				<PanelRow>
					<TextControl
						label={ __( 'Title Color', 'ai-faq-generator' ) }
						type="color"
						value={ titleColor || '' }
						onChange={ ( value ) =>
							setAttributes( { titleColor: value } )
						}
					/>
				</PanelRow>
				<PanelRow>
					<SelectControl
						label={ __( 'Title Font Size', 'ai-faq-generator' ) }
						value={ String( titleFontSize || 0 ) }
						options={ FONT_SIZES }
						onChange={ ( value ) =>
							setAttributes( { titleFontSize: parseInt( value, 10 ) } )
						}
					/>
				</PanelRow>
				<PanelRow>
					<SelectControl
						label={ __( 'Title Font Family', 'ai-faq-generator' ) }
						value={ titleFontFamily || '' }
						options={ FONT_FAMILIES }
						onChange={ ( value ) =>
							setAttributes( { titleFontFamily: value } )
						}
					/>
				</PanelRow>
				<PanelRow>
					<RangeControl
						label={ __( 'Title Padding', 'ai-faq-generator' ) }
						value={ titlePadding ?? 16 }
						onChange={ ( value ) =>
							setAttributes( { titlePadding: value } )
						}
						min={ 0 }
						max={ 48 }
						step={ 4 }
					/>
				</PanelRow>
			</PanelBody>

			<PanelBody title={ __( 'Content Styling', 'ai-faq-generator' ) } initialOpen={ false }>
				<PanelRow>
					<TextControl
						label={ __( 'Content Color', 'ai-faq-generator' ) }
						type="color"
						value={ contentColor || '' }
						onChange={ ( value ) =>
							setAttributes( { contentColor: value } )
						}
					/>
				</PanelRow>
				<PanelRow>
					<SelectControl
						label={ __( 'Content Font Size', 'ai-faq-generator' ) }
						value={ String( contentFontSize || 0 ) }
						options={ FONT_SIZES }
						onChange={ ( value ) =>
							setAttributes( { contentFontSize: parseInt( value, 10 ) } )
						}
					/>
				</PanelRow>
				<PanelRow>
					<SelectControl
						label={ __( 'Content Font Family', 'ai-faq-generator' ) }
						value={ contentFontFamily || '' }
						options={ FONT_FAMILIES }
						onChange={ ( value ) =>
							setAttributes( { contentFontFamily: value } )
						}
					/>
				</PanelRow>
				<PanelRow>
					<RangeControl
						label={ __( 'Content Padding', 'ai-faq-generator' ) }
						value={ contentPadding ?? 16 }
						onChange={ ( value ) =>
							setAttributes( { contentPadding: value } )
						}
						min={ 0 }
						max={ 48 }
						step={ 4 }
					/>
				</PanelRow>
			</PanelBody>

			<PanelBody title={ __( 'Spacing', 'ai-faq-generator' ) } initialOpen={ false }>
				<PanelRow>
					<RangeControl
						label={ __( 'Item Spacing', 'ai-faq-generator' ) }
						value={ itemSpacing ?? 8 }
						onChange={ ( value ) =>
							setAttributes( { itemSpacing: value } )
						}
						min={ 0 }
						max={ 32 }
						step={ 4 }
					/>
				</PanelRow>
			</PanelBody>
		</>
	);
}