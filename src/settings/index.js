/**
 * Settings page entry point.
 *
 * Renders the React-based settings UI into #afg-settings-root.
 */
import { createRoot } from '@wordpress/element';
import SettingsPage from './SettingsPage';

const container = document.getElementById( 'afg-settings-root' );
if ( container ) {
	createRoot( container ).render( <SettingsPage /> );
}
