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

export default function Edit( { attributes, setAttributes } ) {
	const { items, titleTag, openFirstItem, iconPosition, enableAnimation } =
		attributes;
	const className = getBlockClasses( attributes );
	const blockProps = useBlockProps( { className } );

	const addItem = () => {
		if ( items.length >= MAX_ITEMS ) {
			return;
		}
		setAttributes( {
			items: [ ...items, { question: '', answer: '' } ],
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

	return (
		<>
			<InspectorControls>
				<InspectorPanel
					attributes={ attributes }
					setAttributes={ setAttributes }
				/>
			</InspectorControls>
			<div { ...blockProps }>
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
			</div>
		</>
	);
}
