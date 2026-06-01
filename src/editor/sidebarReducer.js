/**
 * Sidebar state reducer and action types for the block-insert state machine.
 *
 * Manages transitions between `empty`, `has_faqs`, and `block_inserted` states
 * based on user actions and block editor events.
 */

import { INITIAL_STATE } from './deriveInitialState';

/**
 * Action type constants.
 */
export const BLOCK_DETECTED = 'BLOCK_DETECTED';
export const META_LOADED = 'META_LOADED';
export const INSERT_SUCCESS = 'INSERT_SUCCESS';
export const BLOCK_REMOVED = 'BLOCK_REMOVED';
export const CLEAR = 'CLEAR';
export const REGENERATE_START = 'REGENERATE_START';
export const REGENERATE_SUCCESS = 'REGENERATE_SUCCESS';
export const REGENERATE_ERROR = 'REGENERATE_ERROR';
export const GENERATE_START = 'GENERATE_START';
export const GENERATE_SUCCESS = 'GENERATE_SUCCESS';
export const GENERATE_ERROR = 'GENERATE_ERROR';
export const CLEAR_ERROR = 'CLEAR_ERROR';

/**
 * Reducer for the sidebar state machine.
 *
 * @param {BlockInsertState} state  Current state.
 * @param {SidebarAction}    action Dispatched action.
 * @return {BlockInsertState} Next state.
 */
export function sidebarReducer( state, action ) {
	switch ( action.type ) {
		case BLOCK_DETECTED:
			return {
				...state,
				sidebarState: 'block_inserted',
				activeBlockClientId: action.clientId,
				error: null,
			};

		case META_LOADED:
			return {
				...state,
				sidebarState: 'has_faqs',
				faqCount: action.faqCount,
			};

		case INSERT_SUCCESS:
			return {
				...state,
				sidebarState: 'block_inserted',
				activeBlockClientId: action.clientId,
				faqCount: 0,
				isGenerating: false,
				error: null,
			};

		case BLOCK_REMOVED:
			return {
				...INITIAL_STATE,
			};

		case CLEAR:
			return {
				...INITIAL_STATE,
			};

		case REGENERATE_START:
			return {
				...state,
				isRegenerating: true,
				error: null,
			};

		case REGENERATE_SUCCESS:
			return {
				...state,
				isRegenerating: false,
				error: null,
			};

		case REGENERATE_ERROR:
			return {
				...state,
				isRegenerating: false,
				error: action.message,
			};

		case GENERATE_START:
			return {
				...state,
				isGenerating: true,
				error: null,
			};

		case GENERATE_SUCCESS:
			return {
				...state,
				sidebarState: 'has_faqs',
				faqCount: action.faqCount,
				isGenerating: false,
				error: null,
			};

		case GENERATE_ERROR:
			return {
				...state,
				isGenerating: false,
				error: action.message,
			};

		case CLEAR_ERROR:
			return {
				...state,
				error: null,
			};

		default:
			return state;
	}
}
