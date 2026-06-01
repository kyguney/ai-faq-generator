/**
 * PreviewModal component — displays generated FAQs for review, editing, and insertion.
 */
import { Modal, Button, TextControl, TextareaControl, Spinner } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { dispatch } from '@wordpress/data';
import { createBlock } from '@wordpress/blocks';
import './preview-modal.scss';

/**
 * Convert FAQ list to WordPress blocks.
 *
 * Each FAQ item becomes a core/heading block (level 3) with the question,
 * followed by a core/paragraph block with the answer.
 *
 * @param {Array} faqList Array of { question, answer } objects.
 * @return {Array} Array of block objects.
 */
function faqsToBlocks( faqList ) {
	const blocks = [];
	for ( const faq of faqList ) {
		blocks.push(
			createBlock( 'core/heading', { level: 3, content: faq.question } )
		);
		blocks.push(
			createBlock( 'core/paragraph', { content: faq.answer } )
		);
	}
	return blocks;
}

/**
 * PreviewModal component.
 *
 * @param {Object}   props                Component props.
 * @param {Array}    props.faqs           Initial FAQ data from generation.
 * @param {number}   props.postId         Current post ID for regeneration.
 * @param {Function} props.onClose        Called when modal closes without insert.
 * @param {Function} props.onInsertSuccess Called after successful block insertion.
 */
function PreviewModal( { faqs, postId, onClose, onInsertSuccess } ) {
	const [ localFaqs, setLocalFaqs ] = useState( faqs );
	const [ isRegenerating, setIsRegenerating ] = useState( false );
	const [ error, setError ] = useState( null );

	/**
	 * Update a question at the given index.
	 *
	 * @param {number} index Array index.
	 * @param {string} value New question text.
	 */
	const handleQuestionChange = ( index, value ) => {
		setLocalFaqs( ( prev ) =>
			prev.map( ( item, i ) =>
				i === index ? { ...item, question: value } : item
			)
		);
	};

	/**
	 * Update an answer at the given index.
	 *
	 * @param {number} index Array index.
	 * @param {string} value New answer text.
	 */
	const handleAnswerChange = ( index, value ) => {
		setLocalFaqs( ( prev ) =>
			prev.map( ( item, i ) =>
				i === index ? { ...item, answer: value } : item
			)
		);
	};

	/**
	 * Remove a FAQ item at the given index.
	 *
	 * @param {number} index Array index to remove.
	 */
	const handleRemove = ( index ) => {
		setLocalFaqs( ( prev ) => prev.filter( ( _, i ) => i !== index ) );
	};

	/**
	 * Regenerate FAQs via AJAX.
	 */
	const handleRegenerate = async () => {
		setIsRegenerating( true );
		setError( null );

		const body = new URLSearchParams();
		body.append( 'action', 'aifaq_generate_faqs' );
		body.append( '_ajax_nonce', aifaqEditor.nonce );
		body.append( 'post_id', postId );

		const controller = new AbortController();
		const timeoutId = setTimeout( () => controller.abort(), 30000 );

		try {
			const response = await fetch( aifaqEditor.ajaxurl, {
				method: 'POST',
				body,
				credentials: 'same-origin',
				signal: controller.signal,
			} );
			clearTimeout( timeoutId );
			const result = await response.json();

			if ( result.success ) {
				setLocalFaqs( result.data.faqs );
			} else {
				setError( result.data?.message || 'Regeneration failed.' );
			}
		} catch ( err ) {
			clearTimeout( timeoutId );
			if ( err.name === 'AbortError' ) {
				setError( 'Request timed out. Please try again.' );
			} else {
				setError( 'Could not reach the server. Please try again.' );
			}
		} finally {
			setIsRegenerating( false );
		}
	};

	/**
	 * Insert FAQs as blocks into the editor.
	 */
	const handleInsert = () => {
		try {
			const blocks = faqsToBlocks( localFaqs );
			dispatch( 'core/block-editor' ).insertBlocks( blocks );
			onInsertSuccess( localFaqs );
		} catch ( err ) {
			setError( 'Failed to insert blocks. Please try again.' );
		}
	};

	return (
		<Modal
			title="Preview Generated FAQs"
			onRequestClose={ onClose }
			className="aifaq-preview-modal-wrapper"
		>
			<div className="aifaq-preview-modal">
				<p className="aifaq-preview-modal__count">
					{ `${ localFaqs.length } FAQ${ localFaqs.length !== 1 ? 's' : '' }` }
				</p>

				{ error && (
					<p className="aifaq-preview-modal__error">{ error }</p>
				) }

				{ localFaqs.length === 0 ? (
					<p className="aifaq-preview-modal__empty">
						No FAQs available. Click "Regenerate" to generate new FAQs.
					</p>
				) : (
					<div className="aifaq-preview-modal__list">
						{ localFaqs.map( ( faq, index ) => (
							<div
								key={ index }
								className="aifaq-preview-modal__item"
							>
								<TextControl
									label={ `Question ${ index + 1 }` }
									value={ faq.question }
									onChange={ ( value ) =>
										handleQuestionChange( index, value )
									}
									disabled={ isRegenerating }
									className={ faq.question === '' ? 'is-invalid' : '' }
								/>
								<TextareaControl
									label={ `Answer ${ index + 1 }` }
									value={ faq.answer }
									onChange={ ( value ) =>
										handleAnswerChange( index, value )
									}
									disabled={ isRegenerating }
									className={ faq.answer === '' ? 'is-invalid' : '' }
								/>
								<Button
									variant="tertiary"
									onClick={ () => handleRemove( index ) }
									disabled={ isRegenerating }
									aria-label={ `Remove FAQ ${ index + 1 }` }
								>
									Remove
								</Button>
							</div>
						) ) }
					</div>
				) }

				{ isRegenerating && <Spinner /> }

				<div className="aifaq-preview-modal__actions">
					<Button
						variant="secondary"
						onClick={ handleRegenerate }
						disabled={ isRegenerating }
					>
						Regenerate
					</Button>
					<Button
						variant="primary"
						onClick={ handleInsert }
						disabled={ isRegenerating || localFaqs.length === 0 }
					>
						Insert
					</Button>
				</div>
			</div>
		</Modal>
	);
}

export { PreviewModal, faqsToBlocks };
