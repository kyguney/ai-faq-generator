/**
 * WordPress dependencies
 */
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import FaqItemEditor from './components/FaqItemEditor';
import AddItemButton from './components/AddItemButton';
import InspectorPanel from './components/InspectorPanel';
import { getBlockClasses } from './utils/getBlockClasses';

const MAX_ITEMS = 50;

// Available icons
const ICONS = {
	chevron: '▾',
	'chevron-right': '▸',
	plus: '+',
	arrow: '→',
	none: null,
};

export default function Edit( { attributes, setAttributes } ) {
	const {
		items,
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

	const className = getBlockClasses( attributes );
	const blockProps = useBlockProps( { className } );

	// Build inline styles for visual preview
	const getTitleStyle = () => {
		const style = {};
		if ( titleColor ) style.color = titleColor;
		if ( titleFontSize && titleFontSize > 0 ) {
			style.fontSize = `${ titleFontSize }px`;
		}
		if ( titleFontFamily ) style.fontFamily = titleFontFamily;
		if ( titlePadding !== undefined ) {
			style.padding = `${ titlePadding }px`;
		}
		return style;
	};

	const getContentStyle = () => {
		const style = {};
		if ( contentColor ) style.color = contentColor;
		if ( contentFontSize && contentFontSize > 0 ) {
			style.fontSize = `${ contentFontSize }px`;
		}
		if ( contentFontFamily ) style.fontFamily = contentFontFamily;
		if ( contentPadding !== undefined ) {
			style.padding = `${ contentPadding }px`;
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

		const TitleTag = titleTag || 'h3';
		const iconChar = ICONS[ selectedIcon ] || ICONS.chevron;

		return (
			<div className="wp-block-wpbits-faq-accordion">
				{ items.map( ( item, index ) => {
					const isOpen = item._open || ( openFirstItem && index === 0 );

					return (
						<div
							key={ index }
							className={ `faq-accordion-item ${ isOpen ? 'is-open' : '' }` }
							style={ getItemStyle() }
						>
							<div
								className="faq-accordion-summary"
								style={ getTitleStyle() }
								onClick={ () => toggleItem( index ) }
								onKeyDown={ ( e ) => {
									if ( e.key === 'Enter' || e.key === ' ' ) {
										e.preventDefault();
										toggleItem( index );
									}
								} }
								role="button"
								tabIndex={ 0 }
							>
								{ iconChar && iconPosition !== 'none' && (
									<span
										className="faq-accordion-icon"
										style={ {
											marginRight: '0.75em',
											display: 'inline-block',
											transition: enableAnimation ? 'transform 0.2s ease' : 'none',
											transform: isOpen ? 'rotate(45deg)' : 'rotate(-45deg)',
										} }
									>
										{ iconChar }
									</span>
								) }
								<span>
									{ item.question || `Question ${ index + 1 }` }
								</span>
							</div>
							{ isOpen && (
								<div
									className="faq-accordion-content"
									style={ getContentStyle() }
								>
									{ item.answer || 'Answer content will appear here...' }
								</div>
							) }
						</div>
					);
				} ) }
			</div>
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