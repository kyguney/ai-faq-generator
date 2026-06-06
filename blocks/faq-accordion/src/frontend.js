/**
 * Frontend script for FAQ Accordion block.
 *
 * Handles:
 * 1. CSS transition animation on open/close (for .has-animation blocks)
 * 2. Dynamic aria-expanded toggling for accessibility
 * 3. Screen reader state change announcements via aria-live region
 *
 * Strategy for animations: When closing, we add a `.is-closing` class and keep
 * the `open` attribute present so the content can animate from 1fr → 0fr.
 * After the transition ends, we remove both the `open` attribute and the class.
 */
( function () {
	'use strict';

	const ANIMATION_CLASS = 'has-animation';
	const CLOSING_CLASS = 'is-closing';

	/**
	 * Create a visually-hidden aria-live region for screen reader announcements.
	 */
	function getOrCreateLiveRegion() {
		let region = document.getElementById( 'faq-accordion-live-region' );
		if ( ! region ) {
			region = document.createElement( 'div' );
			region.id = 'faq-accordion-live-region';
			region.setAttribute( 'aria-live', 'polite' );
			region.setAttribute( 'aria-atomic', 'true' );
			region.setAttribute( 'role', 'status' );
			region.style.cssText =
				'position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;';
			document.body.appendChild( region );
		}
		return region;
	}

	/**
	 * Announce state change to screen readers.
	 */
	function announce( message ) {
		const region = getOrCreateLiveRegion();
		// Clear then set to trigger announcement even if same text.
		region.textContent = '';
		requestAnimationFrame( () => {
			region.textContent = message;
		} );
	}

	/**
	 * Update aria-expanded on a summary element.
	 */
	function updateAriaExpanded( summary, isOpen ) {
		summary.setAttribute( 'aria-expanded', isOpen ? 'true' : 'false' );
	}

	/**
	 * Initialize all FAQ accordion blocks.
	 */
	function initAccordions() {
		const allAccordions = document.querySelectorAll( '.wp-block-wpbits-faq-accordion' );

		allAccordions.forEach( ( accordion ) => {
			const isAnimated = accordion.classList.contains( ANIMATION_CLASS );
			const details = accordion.querySelectorAll( 'details.faq-accordion-item' );

			details.forEach( ( detail ) => {
				const summary = detail.querySelector( 'summary' );
				const content = detail.querySelector( '.faq-accordion-content' );
				if ( ! summary ) {
					return;
				}

				// Set initial aria-expanded based on the open attribute.
				updateAriaExpanded( summary, detail.hasAttribute( 'open' ) );

				// Listen for the native toggle event to keep aria-expanded in sync.
				detail.addEventListener( 'toggle', () => {
					const isOpen = detail.open;
					updateAriaExpanded( summary, isOpen );

					// Get question text for screen reader announcement.
					const titleEl = summary.querySelector( '.faq-accordion-title' );
					const title = titleEl ? titleEl.textContent : '';
					announce(
						isOpen
							? title + ', expanded'
							: title + ', collapsed'
					);
				} );

				// Animation handling (only for blocks with has-animation class).
				if ( isAnimated && content ) {
					detail.addEventListener( 'click', ( e ) => {
						if ( ! summary.contains( e.target ) ) {
							return;
						}

						// Animate close.
						if ( detail.open && ! detail.classList.contains( CLOSING_CLASS ) ) {
							e.preventDefault();
							detail.classList.add( CLOSING_CLASS );

							// Update aria immediately for the closing state.
							updateAriaExpanded( summary, false );

							const titleEl = summary.querySelector( '.faq-accordion-title' );
							const title = titleEl ? titleEl.textContent : '';
							announce( title + ', collapsed' );

							const onTransitionEnd = () => {
								detail.classList.remove( CLOSING_CLASS );
								detail.removeAttribute( 'open' );
								content.removeEventListener( 'transitionend', onTransitionEnd );
							};

							content.addEventListener( 'transitionend', onTransitionEnd );

							// Safety fallback.
							setTimeout( () => {
								if ( detail.classList.contains( CLOSING_CLASS ) ) {
									detail.classList.remove( CLOSING_CLASS );
									detail.removeAttribute( 'open' );
									content.removeEventListener( 'transitionend', onTransitionEnd );
								}
							}, 400 );
						}
					} );
				}
			} );
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initAccordions );
	} else {
		initAccordions();
	}
} )();
