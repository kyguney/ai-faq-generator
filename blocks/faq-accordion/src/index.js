import { registerBlockType } from '@wordpress/blocks';

import metadata from '../block.json';
import Edit from './edit';
import save from './save';

import '../style.css';
import './editor.scss';

registerBlockType( metadata.name, {
	...metadata,
	edit: Edit,
	save,
} );
