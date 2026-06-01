import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function AddItemButton( { onClick, disabled, itemCount } ) {
	return (
		<div className="faq-accordion-add-item">
			{ itemCount === 0 && (
				<p className="faq-accordion-placeholder">
					{ __( 'No FAQ items added yet. Click the button below to add your first item.', 'ai-faq-generator' ) }
				</p>
			) }
			<Button
				variant="secondary"
				onClick={ onClick }
				disabled={ disabled }
				className="faq-accordion-add-button"
			>
				{ __( 'Add FAQ Item', 'ai-faq-generator' ) }
			</Button>
			{ disabled && (
				<p className="faq-accordion-limit-message">
					{ __( 'Maximum of 50 FAQ items reached.', 'ai-faq-generator' ) }
				</p>
			) }
		</div>
	);
}
