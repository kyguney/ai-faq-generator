/**
 * Editor panel registration.
 */
import { registerPlugin } from '@wordpress/plugins';
import { EditorPanel } from './EditorPanel';

registerPlugin( 'aifaq-editor-panel', {
	render: EditorPanel,
} );
