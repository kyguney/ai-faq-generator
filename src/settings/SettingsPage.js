/**
 * Settings Page component.
 *
 * Renders the AI FAQ Generator settings form using WordPress components.
 * Fetches and saves settings via the REST API.
 */
import { useState, useEffect } from '@wordpress/element';
import {
	Button,
	Notice,
	Spinner,
	TextControl,
	SelectControl,
	RangeControl,
	Panel,
	PanelBody,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import './settings.scss';

const PROVIDER_OPTIONS = [
	{ label: 'OpenAI', value: 'openai' },
	{ label: 'OpenRouter', value: 'openrouter' },
	{ label: 'Ollama', value: 'ollama' },
	{ label: 'DeepSeek', value: 'deepseek' },
	{ label: 'LocalAI', value: 'localai' },
	{ label: 'LM Studio', value: 'lmstudio' },
];

const DEFAULT_MODELS = {
	openai: 'gpt-4o',
	openrouter: 'openai/gpt-4o',
	ollama: 'llama3',
	deepseek: 'deepseek-chat',
	localai: 'gpt-4',
	lmstudio: 'local-model',
};

const REST_PATH = '/ai-faq-generator/v1/settings';

function SettingsPage() {
	const [ settings, setSettings ] = useState( {
		provider: 'openai',
		api_key: '',
		model: 'gpt-4o',
		temperature: 0.7,
		faq_count: 5,
		has_api_key: false,
	} );
	const [ isSaving, setIsSaving ] = useState( false );
	const [ notice, setNotice ] = useState( null );
	const [ apiKeyModified, setApiKeyModified ] = useState( false );

	useEffect( () => {
		apiFetch( { path: REST_PATH } )
			.then( ( data ) => {
				setSettings( data );
			} )
			.catch( ( error ) => {
				setNotice( {
					status: 'error',
					message:
						error.message || 'Failed to load settings.',
				} );
			} );
	}, [] );

	const handleSubmit = ( event ) => {
		event.preventDefault();
		setIsSaving( true );
		setNotice( null );

		const payload = {
			provider: settings.provider,
			model: settings.model,
			temperature: settings.temperature,
			faq_count: settings.faq_count,
		};

		// Only send api_key if the user has actually typed a new value.
		if ( apiKeyModified ) {
			payload.api_key = settings.api_key;
		}

		apiFetch( {
			path: REST_PATH,
			method: 'POST',
			data: payload,
		} )
			.then( ( response ) => {
				setSettings( response.settings );
				setApiKeyModified( false );
				setNotice( {
					status: 'success',
					message: 'Settings saved successfully.',
				} );
			} )
			.catch( ( error ) => {
				setNotice( {
					status: 'error',
					message:
						error.message || 'Failed to save settings.',
				} );
			} )
			.finally( () => {
				setIsSaving( false );
			} );
	};

	return (
		<div className="afg-settings-wrap">
			<form onSubmit={ handleSubmit }>
				{ notice && (
					<Notice
						status={ notice.status }
						isDismissible
						onRemove={ () => setNotice( null ) }
					>
						{ notice.message }
					</Notice>
				) }

				<Panel>
					<PanelBody
						title="AI Provider Settings"
						initialOpen={ true }
					>
						<SelectControl
							label="Provider"
							value={ settings.provider }
							options={ PROVIDER_OPTIONS }
							onChange={ ( value ) =>
								setSettings( {
									...settings,
									provider: value,
									model: DEFAULT_MODELS[ value ] || settings.model,
								} )
							}
						/>

						<TextControl
							label="API Key"
							type="password"
							value={ apiKeyModified ? settings.api_key : '' }
							placeholder={
								settings.has_api_key && ! apiKeyModified
									? 'API key is set (leave blank to keep current)'
									: 'Enter your API key'
							}
							onChange={ ( value ) => {
								setApiKeyModified( true );
								setSettings( { ...settings, api_key: value } );
							} }
						/>

						<TextControl
							label="Model"
							value={ settings.model }
							onChange={ ( value ) =>
								setSettings( { ...settings, model: value } )
							}
						/>

						<RangeControl
							label="Temperature"
							value={ settings.temperature }
							onChange={ ( value ) =>
								setSettings( {
									...settings,
									temperature: value,
								} )
							}
							min={ 0 }
							max={ 2 }
							step={ 0.1 }
						/>

						<RangeControl
							label="FAQ Count"
							value={ settings.faq_count }
							onChange={ ( value ) =>
								setSettings( {
									...settings,
									faq_count: value,
								} )
							}
							min={ 1 }
							max={ 20 }
							step={ 1 }
						/>
					</PanelBody>
				</Panel>

				<div className="afg-settings-submit">
					<Button
						variant="primary"
						type="submit"
						isBusy={ isSaving }
						disabled={ isSaving }
					>
						{ isSaving ? 'Saving…' : 'Save Settings' }
					</Button>
					{ isSaving && <Spinner /> }
				</div>
			</form>
		</div>
	);
}

export default SettingsPage;
