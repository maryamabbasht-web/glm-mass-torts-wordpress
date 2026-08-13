/**
 * Tort category tabs.
 *
 * Progressive enhancement: without JavaScript every panel is visible and
 * the page still works. This only adds the filtering behaviour.
 *
 * Replaces the source's showTab(), which used getElementById() and so
 * silently ignored the second of each duplicated panel — the reason 18
 * tort cards were shipping invisibly.
 */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {

		var browsers = document.querySelectorAll( '.glm-tort-browser' );

		Array.prototype.forEach.call( browsers, function ( browser ) {

			// Scoped to this browser, so multiple grids on one page
			// never fight over each other's panels.
			var tabs   = browser.querySelectorAll( '.glm-tort-tab' );
			var panels = browser.querySelectorAll( '.glm-tort-panel' );

			if ( ! tabs.length ) {
				return;
			}

			// Signals to CSS that filtering is now available. Until this
			// lands, every panel stays visible so a no-JS visitor can
			// still read all 40 torts rather than only the first tab.
			browser.classList.add( 'glm-js-ready' );

			function activate( slug ) {
				Array.prototype.forEach.call( tabs, function ( tab ) {
					var on = tab.getAttribute( 'data-target' ) === slug;
					tab.classList.toggle( 'is-active', on );
					tab.setAttribute( 'aria-selected', on ? 'true' : 'false' );
				} );

				Array.prototype.forEach.call( panels, function ( panel ) {
					var on = panel.id === 'glm-panel-' + slug;
					panel.classList.toggle( 'is-active', on );
					if ( on ) {
						panel.removeAttribute( 'hidden' );
					} else {
						panel.setAttribute( 'hidden', '' );
					}
				} );
			}

			Array.prototype.forEach.call( tabs, function ( tab ) {
				tab.addEventListener( 'click', function () {
					activate( tab.getAttribute( 'data-target' ) );
				} );

				// Arrow-key navigation, per the ARIA tabs pattern.
				tab.addEventListener( 'keydown', function ( event ) {
					if ( event.key !== 'ArrowRight' && event.key !== 'ArrowLeft' ) {
						return;
					}
					event.preventDefault();

					var list  = Array.prototype.slice.call( tabs );
					var index = list.indexOf( tab );
					var next  = event.key === 'ArrowRight'
						? ( index + 1 ) % list.length
						: ( index - 1 + list.length ) % list.length;

					list[ next ].focus();
					activate( list[ next ].getAttribute( 'data-target' ) );
				} );
			} );

			// Deep links: /mass-torts/#pharma opens that tab.
			var hash = window.location.hash.replace( '#', '' );
			if ( hash && browser.querySelector( '#glm-panel-' + hash ) ) {
				activate( hash );
			}
		} );
	} );
}() );
