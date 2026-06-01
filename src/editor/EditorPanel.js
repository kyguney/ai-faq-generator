/**
 * EditorPanel component — AI FAQ Generator sidebar panel.
 *
 * Consumes the useBlockInsertState hook for state machine logic and renders
 * UI conditionally based on the current sidebar state (empty, has_faqs, block_inserted).
 */
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { Button, Spinner } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { PreviewModal } from './PreviewModal';
import { useBlockInsertState } from './useBlockInsertState';
import './editor.scss';

function EditorPanel() {
	const [ isModalOpen, setIsModalOpen ] = useState( false );
	const [ generatedFaqs, setGeneratedFaqs ] = useState( [] );

	// Get current post type and check custom-fields support.
	const supportsCustomFields = useSelect( ( select ) => {
		const { getEditedPostAttribute } = select( 'core/editor' );
		const { getPostType } = select( 'core' );
		const postType = getEditedPostAttribute( 'type' );
		const postTypeObject = getPostType( postType );

		if ( ! postTypeObject ) {
			return false;
		}

		return postTypeObject.supports?.[ 'custom-fields' ] ?? false;
	}, [] );

	// Get current post type for hook params.
	const postType = useSelect( ( select ) => {
		return select( 'core/editor' ).getEditedPostAttribute( 'type' );
	}, [] );

	// Get current post ID for hook params.
	const postId = useSelect( ( select ) => {
		return select( 'core/editor' ).getCurrentPostId();
	}, [] );

	// Consume the block-insert state machine hook.
	const [ state, actions ] = useBlockInsertState( postId, postType );

	// Do not render if post type does not support custom-fields.
	if ( ! supportsCustomFields ) {
		return null;
	}

	// Determine if buttons should be disabled.
	const isDisabled = state.isGenerating || state.isRegenerating;

	/**
	 * Handle Generate FAQs button click.
	 * Calls the hook's handleGenerate which does the AJAX call,
	 * then opens the PreviewModal with the returned FAQs.
	 */
	const handleGenerateClick = async () => {
		const faqs = await actions.handleGenerate();
		if ( faqs ) {
			setGeneratedFaqs( faqs );
			setIsModalOpen( true );
		}
	};

	return (
		<PluginDocumentSettingPanel
			name="aifaq-editor-panel"
			title="AI FAQ Generator"
		>
			<div className="aifaq-editor-panel">
				{ state.error && (
					<p className="aifaq-error">{ state.error }</p>
				) }

				{ /* Empty state: Generate FAQs button only */ }
				{ state.sidebarState === 'empty' && (
					<div className="aifaq-generate-row">
						<Button
							variant="secondary"
							onClick={ handleGenerateClick }
							isBusy={ state.isGenerating }
							disabled={ isDisabled }
						>
							{ state.isGenerating
								? 'Generating...'
								: 'Generate FAQs' }
						</Button>
						{ state.isGenerating && <Spinner /> }
					</div>
				) }

				{ /* Has FAQs state: FAQ count + Generate */ }
				{ state.sidebarState === 'has_faqs' && (
					<>
						<p className="aifaq-faq-count">
							{ `${ state.faqCount } FAQs generated` }
						</p>
						<div className="aifaq-generate-row">
							<Button
								variant="secondary"
								onClick={ handleGenerateClick }
								isBusy={ state.isGenerating }
								disabled={ isDisabled }
							>
								{ state.isGenerating
									? 'Generating...'
									: 'Generate FAQs' }
							</Button>
							{ state.isGenerating && <Spinner /> }
						</div>
					</>
				) }

				{ /* Block inserted state: status + Regenerate + Edit Block on same row */ }
				{ state.sidebarState === 'block_inserted' && (
					<>
						<p className="aifaq-block-inserted">
							1 FAQ Block inserted
						</p>
						<div className="aifaq-actions-row">
							<Button
								variant="primary"
								onClick={ actions.handleRegenerate }
								isBusy={ state.isRegenerating }
								disabled={ isDisabled }
							>
								{ state.isRegenerating
									? 'Generating...'
									: ( state.blockHasItems ? 'Regenerate' : 'Generate FAQs' ) }
							</Button>
							<Button
								variant="secondary"
								onClick={ actions.handleEditBlock }
								disabled={ isDisabled }
							>
								Edit Block
							</Button>
						</div>
					</>
				) }
			</div>

			{ isModalOpen && (
				<PreviewModal
					faqs={ generatedFaqs }
					postId={ postId }
					onClose={ () => setIsModalOpen( false ) }
					onInsertSuccess={ ( finalFaqs, clientId ) => {
						actions.handleInsertSuccess( finalFaqs, clientId );
						setIsModalOpen( false );
						setGeneratedFaqs( [] );
					} }
				/>
			) }
		</PluginDocumentSettingPanel>
	);
}

export { EditorPanel };
