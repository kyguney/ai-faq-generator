/**
 * WordPress dependencies
 */
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { Icon } from '@wordpress/icons';
import { cloneElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import FaqItemEditor from './components/FaqItemEditor';
import AddItemButton from './components/AddItemButton';
import InspectorPanel from './components/InspectorPanel';
import { getBlockClasses } from './utils/getBlockClasses';
import { buildTitleHeadingStyle } from '../../../src/utils/buildTitleStyles';
import {
	resolveIconId,
	getIconSize,
	ICON_REGISTRY,
} from '../../../src/utils/iconRegistry';

const MAX_ITEMS = 50;

export default function Edit( { attributes, setAttributes } ) {
	const {
		items,
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

	const className = getBlockClasses( attributes );
	const blockProps = useBlockProps( { className } );

	// Build inline styles for visual preview
	const getTitleStyle = () => {
		const style = {};
		if ( titleColor ) style.color = titleColor;
		if ( titleBackgroundColor ) style.backgroundColor = titleBackgroundColor;
		if ( titleFontSize && titleFontSize > 0 ) {
			style.fontSize = `${ titleFontSize }px`;
		}
		if ( titleFontFamily ) style.fontFamily = titleFontFamily;
		if ( titlePadding ) {
			style.paddingTop = titlePadding.top || '16px';
			style.paddingRight = titlePadding.right || '20px';
			style.paddingBottom = titlePadding.bottom || '16px';
			style.paddingLeft = titlePadding.left || '20px';
		}
		return style;
	};

	const getContentStyle = () => {
		const style = {};
		if ( contentColor ) style.color = contentColor;
		if ( contentBackgroundColor ) style.backgroundColor = contentBackgroundColor;
		if ( contentFontSize && contentFontSize > 0 ) {
			style.fontSize = `${ contentFontSize }px`;
		}
		if ( contentFontFamily ) style.fontFamily = contentFontFamily;
		if ( contentPadding ) {
			style.paddingTop = contentPadding.top || '16px';
			style.paddingRight = contentPadding.right || '20px';
			style.paddingBottom = contentPadding.bottom || '16px';
			style.paddingLeft = contentPadding.left || '20px';
		}
		return style;
	};

	const getItemStyle = () => {
		const style = {};
		if ( itemSpacing !== undefined ) {
			style.marginBottom = `${ itemSpacing }px`;
		}
		return style;
	};

	const addItem = () => {
		if ( items.length >= MAX_ITEMS ) {
			return;
		}
		setAttributes( {
			items: [ ...items, { question: '', answer: '', _open: false } ],
		} );
	};

	const updateItem = ( index, field, value ) => {
		const updatedItems = items.map( ( item, i ) => {
			if ( i !== index ) {
				return item;
			}
			return { ...item, [ field ]: value };
		} );
		setAttributes( { items: updatedItems } );
	};

	const removeItem = ( index ) => {
		const updatedItems = items.filter( ( _, i ) => i !== index );
		setAttributes( { items: updatedItems } );
	};

	const moveItem = ( index, direction ) => {
		const targetIndex = index + direction;
		if ( targetIndex < 0 || targetIndex >= items.length ) {
			return;
		}
		const updatedItems = [ ...items ];
		const temp = updatedItems[ index ];
		updatedItems[ index ] = updatedItems[ targetIndex ];
		updatedItems[ targetIndex ] = temp;
		setAttributes( { items: updatedItems } );
	};

	// Toggle item open state in preview mode
	const toggleItem = ( index ) => {
		const updatedItems = items.map( ( item, i ) => {
			if ( i !== index ) {
				return item;
			}
			return { ...item, _open: ! item._open };
		} );
		setAttributes( { items: updatedItems } );
	};

	// Render visual preview of the accordion
	const renderVisualPreview = () => {
		if ( items.length === 0 ) {
			return (
				<div className="faq-accordion-empty">
					<p>Add FAQ items to see the preview</p>
				</div>
			);
		}

		const resolvedIconId = resolveIconId( selectedIcon );
		const iconEntry = ICON_REGISTRY[ resolvedIconId ];
		const iconSize = getIconSize( titleFontSize );
		const titleHeadingStyle = buildTitleHeadingStyle( attributes );

		const renderIcon = () => {
			if ( resolvedIconId === 'none' ) {
				return null;
			}
			if ( iconEntry.icon ) {
				return <Icon icon={ iconEntry.icon } size={ iconSize } />;
			}
			if ( iconEntry.svg ) {
				return cloneElement( iconEntry.svg, {
					width: iconSize,
					height: iconSize,
				} );
			}
			return null;
		};

		// Icon wrapper style — sets color which SVGs inherit via fill="currentColor"
		const iconStyle = iconColor
			? { color: iconColor }
			: {};

		return (
			<>
				{ items.map( ( item, index ) => {
					const isOpen = item._open || ( openFirstItem && index === 0 );

					return (
						<details
							key={ index }
							className="faq-accordion-item"
							open={ isOpen }
							style={ getItemStyle() }
						>
							<summary
								className="faq-accordion-summary"
								style={ { ...getTitleStyle(), ...titleHeadingStyle } }
								onClick={ ( e ) => {
									e.preventDefault();
									toggleItem( index );
								} }
							>
								{ resolvedIconId !== 'none' && iconPosition !== 'none' && (
									<span className="faq-accordion-icon" style={ iconStyle }>
										{ renderIcon() }
									</span>
								) }
								<span className="faq-accordion-title">
									{ item.question || `Question ${ index + 1 }` }
								</span>
							</summary>
							<div
								className="faq-accordion-content"
								style={ getContentStyle() }
							>
								<div className="faq-accordion-content__inner">
									{ item.answer || 'Answer content will appear here...' }
								</div>
							</div>
						</details>
					);
				} ) }
			</>
		);
	};

	// Render classic editor (input fields)
	const renderClassicEditor = () => {
		return (
			<>
				{ items.map( ( item, index ) => (
					<FaqItemEditor
						key={ index }
						item={ item }
						index={ index }
						onUpdate={ updateItem }
						onRemove={ removeItem }
						onMove={ moveItem }
						isFirst={ index === 0 }
						isLast={ index === items.length - 1 }
						isExpanded={
							openFirstItem && index === 0 && items.length > 0
						}
					/>
				) ) }
				<AddItemButton
					onClick={ addItem }
					disabled={ items.length >= MAX_ITEMS }
					itemCount={ items.length }
				/>
			</>
		);
	};

	return (
		<>
			<InspectorControls>
				<InspectorPanel
					attributes={ attributes }
					setAttributes={ setAttributes }
				/>
			</InspectorControls>
			<div { ...blockProps }>
				{ layoutMode === 'preview' && renderVisualPreview() }
				{ layoutMode !== 'preview' && renderClassicEditor() }
			</div>
		</>
	);
}