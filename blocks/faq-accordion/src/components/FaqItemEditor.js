/**
 * WordPress dependencies
 */
import { Button, TextControl, TextareaControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { chevronUp, chevronDown, close } from '@wordpress/icons';

/**
 * FaqItemEditor component.
 *
 * Renders a single FAQ item with question input, answer textarea,
 * and action controls (move up/down, remove).
 *
 * @param {Object}   props
 * @param {Object}   props.item     - The FAQ item { question, answer }.
 * @param {number}   props.index    - The index of this item in the list.
 * @param {Function} props.onUpdate - Callback: (index, field, value) => void.
 * @param {Function} props.onRemove - Callback: (index) => void.
 * @param {Function} props.onMove   - Callback: (index, direction) => void.
 * @param {boolean}  props.isFirst  - Whether this is the first item.
 * @param {boolean}  props.isLast   - Whether this is the last item.
 */
export default function FaqItemEditor( {
	item,
	index,
	onUpdate,
	onRemove,
	onMove,
	isFirst,
	isLast,
} ) {
	return (
		<div className="faq-item-editor">
			<div className="faq-item-editor__fields">
				<TextControl
					label={ __( 'Question', 'ai-faq-generator' ) }
					value={ item.question }
					onChange={ ( value ) => onUpdate( index, 'question', value ) }
					maxLength={ 500 }
					placeholder={ __( 'Enter your question…', 'ai-faq-generator' ) }
				/>
				<TextareaControl
					label={ __( 'Answer', 'ai-faq-generator' ) }
					value={ item.answer }
					onChange={ ( value ) => onUpdate( index, 'answer', value ) }
					maxLength={ 5000 }
					placeholder={ __( 'Enter the answer…', 'ai-faq-generator' ) }
				/>
			</div>
			<div className="faq-item-editor__actions">
				{ ! isFirst && (
					<Button
						icon={ chevronUp }
						label={ __( 'Move up', 'ai-faq-generator' ) }
						onClick={ () => onMove( index, -1 ) }
						size="small"
					/>
				) }
				{ ! isLast && (
					<Button
						icon={ chevronDown }
						label={ __( 'Move down', 'ai-faq-generator' ) }
						onClick={ () => onMove( index, 1 ) }
						size="small"
					/>
				) }
				<Button
					icon={ close }
					label={ __( 'Remove item', 'ai-faq-generator' ) }
					onClick={ () => onRemove( index ) }
					isDestructive
					size="small"
				/>
			</div>
		</div>
	);
}
