/**
 * WordPress dependencies
 */
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * InspectorPanel component.
 *
 * Renders the block settings panel in the sidebar with controls
 * for Title Tag, Open First Item, Icon Position, and Enable Animation.
 *
 * @param {Object}   props
 * @param {Object}   props.attributes    - The block attributes.
 * @param {Function} props.setAttributes - Callback to update block attributes.
 */
export default function InspectorPanel( { attributes, setAttributes } ) {
	const { titleTag, openFirstItem, iconPosition, enableAnimation } = attributes;

	return (
		<PanelBody title={ __( 'Settings', 'ai-faq-generator' ) }>
			<SelectControl
				label={ __( 'Title Tag', 'ai-faq-generator' ) }
				value={ titleTag }
				options={ [
					{ label: 'H2', value: 'h2' },
					{ label: 'H3', value: 'h3' },
					{ label: 'H4', value: 'h4' },
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
		</PanelBody>
	);
}
