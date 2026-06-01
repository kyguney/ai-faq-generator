/**
 * Custom hook encapsulating the block-insert state machine logic.
 *
 * Manages sidebar state transitions, block detection, AJAX generation/regeneration,
 * and action handlers for the AI FAQ Generator editor panel.
 */
import { useReducer, useEffect, useCallback, useRef } from '@wordpress/element';
import { useSelect, useDispatch, select as wpSelect, dispatch as wpDispatch } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { createBlock } from '@wordpress/blocks';

import { sidebarReducer, BLOCK_DETECTED, BLOCK_REMOVED, GENERATE_START, GENERATE_SUCCESS, GENERATE_ERROR, REGENERATE_START, REGENERATE_SUCCESS, REGENERATE_ERROR, INSERT_SUCCESS, CLEAR } from './sidebarReducer';
import { deriveInitialState } from './deriveInitialState';
import { findFaqBlock } from './findFaqBlock';

/**
 * Show a WordPress snackbar notice via the core/notices store.
 *
 * @param {Function} createNotice The createNotice dispatch function.
 * @param {string}   type         Notice type: 'success' or 'error'.
 * @param {string}   message      Notice message.
 * @param {number}   autoDismiss  Auto-dismiss duration in milliseconds.
 */
function showNotice( createNotice, type, message, autoDismiss ) {
	createNotice( type, message, {
		isDismissible: true,
		type: 'snackbar',
		__unstableHTML: false,
		actions: [],
		explicitDismiss: false,
	} );

	// Auto-dismiss after the specified duration.
	setTimeout( () => {
		const notices = wpSelect( 'core/notices' )?.getNotices?.() || [];
		const notice = notices.find( ( n ) => n.content === message );
		if ( notice ) {
			wpDispatch( 'core/notices' ).removeNotice( notice.id );
		}
	}, autoDismiss );
}

/**
 * Custom hook for the block-insert state machine.
 *
 * @param {number} postId   Current post ID.
 * @param {string} postType Current post type slug.
 * @return {[BlockInsertState, BlockInsertActions]} Tuple of state and action handlers.
 */
export function useBlockInsertState( postId, postType ) {
	// Read/write post meta via entity prop.
	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta', postId );
	const rawFaqMeta = meta?.[ '_aifaq_generated_faqs' ] ?? '';

	// Dispatchers for block editor and notices.
	const { insertBlocks, updateBlockAttributes, selectBlock, removeBlock } = useDispatch( 'core/block-editor' );
	const { createNotice } = useDispatch( 'core/notices' );

	// Detect FAQ block in the editor via useSelect subscription.
	const faqBlock = useSelect( ( select ) => {
		const blocks = select( 'core/block-editor' ).getBlocks();
		return findFaqBlock( blocks );
	}, [] );

	// Derive initial state on first render.
	const initialState = deriveInitialState(
		!! faqBlock,
		faqBlock?.clientId ?? null,
		rawFaqMeta
	);

	const [ state, dispatch ] = useReducer( sidebarReducer, initialState );

	// Keep a ref to the latest state for use in async callbacks.
	const stateRef = useRef( state );
	stateRef.current = state;

	// Subscribe to block list changes — dispatch BLOCK_DETECTED or BLOCK_REMOVED.
	useEffect( () => {
		if ( faqBlock ) {
			// Only dispatch if the detected block is different or state isn't block_inserted.
			if (
				stateRef.current.sidebarState !== 'block_inserted' ||
				stateRef.current.activeBlockClientId !== faqBlock.clientId
			) {
				dispatch( { type: BLOCK_DETECTED, clientId: faqBlock.clientId } );
			}
		} else if ( stateRef.current.sidebarState === 'block_inserted' ) {
			dispatch( { type: BLOCK_REMOVED } );
			// Clear meta when block is removed externally.
			setMeta( { ...meta, _aifaq_generated_faqs: '' } );
		}
	}, [ faqBlock ] );

	/**
	 * Handle Generate FAQs action.
	 * Makes AJAX call to generate FAQs, dispatches state transitions.
	 *
	 * @return {Array|null} The generated FAQs array on success, or null on failure.
	 */
	const handleGenerate = useCallback( async () => {
		dispatch( { type: GENERATE_START } );

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
				const { faqs } = result.data;
				dispatch( { type: GENERATE_SUCCESS, faqCount: faqs.length } );

				// Store generated FAQs in meta for PreviewModal consumption.
				setMeta( { ...meta, _aifaq_generated_faqs: JSON.stringify( faqs ) } );

				return faqs;
			}

			const errorMessage = result.data?.message || 'FAQ generation failed.';
			dispatch( { type: GENERATE_ERROR, message: errorMessage } );
			showNotice( createNotice, 'error', errorMessage, 8000 );
			return null;
		} catch ( error ) {
			clearTimeout( timeoutId );
			const errorMessage = 'Could not reach the server. Please try again.';
			dispatch( { type: GENERATE_ERROR, message: errorMessage } );
			showNotice( createNotice, 'error', errorMessage, 8000 );
			return null;
		}
	}, [ postId, meta, setMeta, createNotice ] );

	/**
	 * Handle successful block insertion from PreviewModal.
	 *
	 * @param {Array}  faqs     The FAQ items that were inserted.
	 * @param {string} clientId The clientId of the inserted block.
	 */
	const handleInsertSuccess = useCallback( ( faqs, clientId ) => {
		dispatch( { type: INSERT_SUCCESS, clientId } );

		// Clear meta after successful insertion.
		setMeta( { ...meta, _aifaq_generated_faqs: '' } );

		showNotice( createNotice, 'success', `${ faqs.length } FAQs inserted`, 5000 );
	}, [ meta, setMeta, createNotice ] );

	/**
	 * Handle Regenerate action.
	 * Generates new FAQs and updates the existing block's attributes in-place.
	 * If the block no longer exists, inserts a new one.
	 */
	const handleRegenerate = useCallback( async () => {
		dispatch( { type: REGENERATE_START } );

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
				const { faqs } = result.data;
				const currentClientId = stateRef.current.activeBlockClientId;

				// Check if the block still exists.
				const existingBlock = wpSelect( 'core/block-editor' ).getBlock( currentClientId );

				if ( existingBlock ) {
					// Update existing block attributes in-place.
					await updateBlockAttributes( currentClientId, { items: faqs } );
				} else {
					// Block was removed during regeneration — insert a new one.
					const newBlock = createBlock( 'wpbits/faq-accordion', { items: faqs } );
					await insertBlocks( [ newBlock ] );

					// The new block's clientId will be picked up by the useSelect subscription.
				}

				dispatch( { type: REGENERATE_SUCCESS } );
				showNotice( createNotice, 'success', `${ faqs.length } FAQs regenerated`, 5000 );
			} else {
				const errorMessage = result.data?.message || 'FAQ regeneration failed.';
				dispatch( { type: REGENERATE_ERROR, message: errorMessage } );
				showNotice( createNotice, 'error', errorMessage, 8000 );
			}
		} catch ( error ) {
			clearTimeout( timeoutId );
			const errorMessage = 'Could not reach the server. Please try again.';
			dispatch( { type: REGENERATE_ERROR, message: errorMessage } );
			showNotice( createNotice, 'error', errorMessage, 8000 );
		}
	}, [ postId, updateBlockAttributes, insertBlocks, createNotice ] );

	/**
	 * Handle Edit Block action.
	 * Selects the active FAQ block in the editor, scrolling it into view.
	 * If the block no longer exists, transitions to empty state.
	 */
	const handleEditBlock = useCallback( () => {
		const clientId = stateRef.current.activeBlockClientId;

		if ( ! clientId ) {
			return;
		}

		// Validate block still exists.
		const existingBlock = wpSelect( 'core/block-editor' ).getBlock( clientId );

		if ( existingBlock ) {
			selectBlock( clientId );
		} else {
			// Block was removed externally.
			dispatch( { type: BLOCK_REMOVED } );
			showNotice( createNotice, 'info', 'The FAQ block was removed from the post.', 8000 );
		}
	}, [ selectBlock, createNotice ] );

	/**
	 * Handle Clear & Start Over action.
	 * Removes the FAQ block from the editor, clears meta, and resets state to empty.
	 */
	const handleClear = useCallback( () => {
		// Remove the FAQ block from the editor if one exists.
		const clientId = stateRef.current.activeBlockClientId;
		if ( clientId ) {
			const existingBlock = wpSelect( 'core/block-editor' ).getBlock( clientId );
			if ( existingBlock ) {
				removeBlock( clientId );
			}
		}

		// Clear meta value.
		setMeta( { ...meta, _aifaq_generated_faqs: '' } );

		dispatch( { type: CLEAR } );
	}, [ meta, setMeta, removeBlock ] );

	const actions = {
		handleGenerate,
		handleInsertSuccess,
		handleRegenerate,
		handleEditBlock,
		handleClear,
	};

	// Determine if the active block has FAQ items.
	const blockHasItems = !! ( faqBlock?.items?.length );

	return [ { ...state, blockHasItems }, actions ];
}
