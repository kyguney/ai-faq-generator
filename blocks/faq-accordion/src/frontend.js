/**
 * Frontend script for FAQ Accordion block animation.
 *
 * Intercepts <details> toggle to enable CSS transitions on open/close.
 * Without this, browsers instantly hide content when removing the `open` attribute,
 * preventing any CSS transition from running.
 *
 * Strategy: When closing, we add a `.is-closing` class and keep `open` attribute present
 * so the content can animate from 1fr → 0fr. After the transition ends, we remove both
 * the `open` attribute and the `.is-closing` class.
 */
( function () {
	'use strict';

	const ANIMATION_CLASS = 'has-animation';
	const CLOSING_CLASS = 'is-closing';

	function initAccordionAnimation() {
		const accordions = document.querySelectorAll(
			`.wp-block-wpbits-faq-accordion.${ ANIMATION_CLASS }`
		);

		accordions.forEach( ( accordion ) => {
			const details = accordion.querySelectorAll( 'details.faq-accordion-item' );

			details.forEach( ( detail ) => {
				const content = detail.querySelector( '.faq-accordion-content' );
				if ( ! content ) {
					return;
				}

				detail.addEventListener( 'click', ( e ) => {
					// Only intercept clicks on the summary element.
					const summary = detail.querySelector( 'summary' );
					if ( ! summary || ! summary.contains( e.target ) ) {
						return;
					}

					// If currently open and not already closing, animate close.
					if ( detail.open && ! detail.classList.contains( CLOSING_CLASS ) ) {
						e.preventDefault();
						detail.classList.add( CLOSING_CLASS );

						const onTransitionEnd = () => {
							detail.classList.remove( CLOSING_CLASS );
							detail.removeAttribute( 'open' );
							content.removeEventListener( 'transitionend', onTransitionEnd );
						};

						content.addEventListener( 'transitionend', onTransitionEnd );

						// Safety fallback: if transitionend doesn't fire (e.g., reduced motion),
						// remove after the transition duration + buffer.
						setTimeout( () => {
							if ( detail.classList.contains( CLOSING_CLASS ) ) {
								detail.classList.remove( CLOSING_CLASS );
								detail.removeAttribute( 'open' );
								content.removeEventListener( 'transitionend', onTransitionEnd );
							}
						}, 400 );
					}
				} );
			} );
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initAccordionAnimation );
	} else {
		initAccordionAnimation();
	}
} )();
