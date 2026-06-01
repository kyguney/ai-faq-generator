/**
 * EditorPanel component — AI FAQ Generator sidebar panel.
 */
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { Button, Spinner } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect, dispatch } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { PreviewModal } from './PreviewModal';
import './editor.scss';

/**
 * Parse FAQ meta value into an array.
 *
 * @param {string} raw Raw meta value (JSON string or empty).
 * @return {Array|null} Parsed FAQ array or null if invalid.
 */
function parseFaqMeta( raw ) {
	if ( ! raw ) {
		return null;
	}
	try {
		const parsed = JSON.parse( raw );
		if ( Array.isArray( parsed ) && parsed.length > 0 ) {
			return parsed;
		}
	} catch ( e ) {
		// Invalid JSON — treat as no data.
	}
	return null;
}

/**
 * Show a WordPress notice via the core/notices store.
 *
 * @param {string} type    Notice type: 'success' or 'error'.
 * @param {string} message Notice message.
 * @param {number} autoDismiss Auto-dismiss duration in milliseconds.
 */
function showNotice( type, message, autoDismiss ) {
	dispatch( 'core/notices' ).createNotice( type, message, {
		isDismissible: true,
		type: 'snackbar',
		__unstableHTML: false,
		actions: [],
		// Auto-dismiss via explicit timeout option.
		explicitDismiss: false,
	} );

	// Auto-dismiss after the specified duration.
	setTimeout( () => {
		dispatch( 'core/notices' ).removeNotice( message );
	}, autoDismiss );
}

function EditorPanel() {
	const [ isLoading, setIsLoading ] = useState( false );
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

	// Get current post type for useEntityProp.
	const postType = useSelect( ( select ) => {
		return select( 'core/editor' ).getEditedPostAttribute( 'type' );
	}, [] );

	// Get current post ID for useEntityProp.
	const postId = useSelect( ( select ) => {
		return select( 'core/editor' ).getCurrentPostId();
	}, [] );

	// Read the _aifaq_generated_faqs meta value via REST API.
	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta', postId );
	const rawFaqMeta = meta?.[ '_aifaq_generated_faqs' ] ?? '';
	const faqs = parseFaqMeta( rawFaqMeta );

	// Do not render if post type does not support custom-fields.
	if ( ! supportsCustomFields ) {
		return null;
	}

	/**
	 * Handle Generate FAQs button click.
	 */
	const handleGenerate = async () => {
		setIsLoading( true );

		const currentPostId = wp.data.select( 'core/editor' ).getCurrentPostId();

		const body = new URLSearchParams();
		body.append( 'action', 'aifaq_generate_faqs' );
		body.append( '_ajax_nonce', aifaqEditor.nonce );
		body.append( 'post_id', currentPostId );

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
				const { faqs: newFaqs } = result.data;

				// Open the preview modal with the generated FAQs.
				setGeneratedFaqs( newFaqs );
				setIsModalOpen( true );
			} else {
				// Error response from server.
				const errorMessage =
					result.data?.message || 'FAQ generation failed.';
				showNotice( 'error', errorMessage, 8000 );
			}
		} catch ( error ) {
			clearTimeout( timeoutId );
			// Network error or timeout (AbortError).
			showNotice(
				'error',
				'Could not reach the server. Please try again.',
				8000
			);
		} finally {
			setIsLoading( false );
		}
	};

	return (
		<PluginDocumentSettingPanel
			name="aifaq-editor-panel"
			title="AI FAQ Generator"
		>
			<div className="aifaq-editor-panel">
				<div className="aifaq-generate-row">
					<Button
						variant="secondary"
						onClick={ handleGenerate }
						isBusy={ isLoading }
						disabled={ isLoading }
					>
						{ isLoading ? 'Generating...' : 'Generate FAQs' }
					</Button>
					{ isLoading && <Spinner /> }
				</div>
				{ faqs && (
					<p className="aifaq-faq-count">
						{ `${ faqs.length } FAQs generated` }
					</p>
				) }
			</div>
			{ isModalOpen && (
				<PreviewModal
					faqs={ generatedFaqs }
					postId={ postId }
					onClose={ () => setIsModalOpen( false ) }
					onInsertSuccess={ ( finalFaqs ) => {
						setMeta( { ...meta, _aifaq_generated_faqs: JSON.stringify( finalFaqs ) } );
						setIsModalOpen( false );
						showNotice( 'success', `${ finalFaqs.length } FAQs inserted`, 5000 );
					} }
				/>
			) }
		</PluginDocumentSettingPanel>
	);
}

export { EditorPanel };
