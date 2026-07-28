/**
 * GLM navigation behaviour.
 *
 * Replaces HFE's nav script. Feature parity plus a focus trap, and it
 * reads the breakpoint from CSS rather than hardcoding it — HFE hardcoded
 * 767/1024 while this project breaks at 900, so the two disagreed between
 * 901px and 1024px.
 *
 * No jQuery. No dependencies.
 */
( function () {
	'use strict';

	/** Matches --glm-nav-breakpoint in components.css. */
	function breakpoint( nav ) {
		var v = getComputedStyle( nav ).getPropertyValue( '--glm-nav-breakpoint' ).trim();
		var n = parseInt( v, 10 );
		return isNaN( n ) ? 900 : n;
	}

	function isMobile( nav ) {
		return window.matchMedia( '(max-width: ' + breakpoint( nav ) + 'px)' ).matches;
	}

	/** Elements that can receive focus inside a container. */
	function focusables( root ) {
		return Array.prototype.filter.call(
			root.querySelectorAll( 'a[href], button:not([disabled])' ),
			function ( el ) {
				return el.offsetParent !== null;
			}
		);
	}

	function initNav( nav ) {

		var toggle   = nav.querySelector( '.glm-nav__toggle' );
		var list     = nav.querySelector( '.glm-nav__list' );
		var subToggles = Array.prototype.slice.call( nav.querySelectorAll( '.glm-nav__toggle-sub' ) );

		if ( ! toggle || ! list ) {
			return;
		}

		nav.classList.add( 'glm-nav--ready' );

		/* ---------------------------------------------------------
		 * Sub-menus
		 * ------------------------------------------------------ */

		function closeSub( btn ) {
			btn.setAttribute( 'aria-expanded', 'false' );
			btn.closest( '.glm-nav__item' ).classList.remove( 'is-open' );
		}

		function openSub( btn ) {
			btn.setAttribute( 'aria-expanded', 'true' );
			btn.closest( '.glm-nav__item' ).classList.add( 'is-open' );
		}

		function closeAllSubs( except ) {
			subToggles.forEach( function ( b ) {
				if ( b !== except ) {
					closeSub( b );
				}
			} );
		}

		subToggles.forEach( function ( btn ) {
			btn.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				var open = btn.getAttribute( 'aria-expanded' ) === 'true';
				closeAllSubs( btn );
				if ( open ) {
					closeSub( btn );
				} else {
					openSub( btn );
				}
			} );
		} );

		/* On desktop, hover and focus reveal the submenu. Keep ARIA in
		   step so assistive tech is not told it is collapsed while it
		   is visibly open. */
		nav.querySelectorAll( '.glm-nav__item--has-children' ).forEach( function ( item ) {
			var btn = item.querySelector( '.glm-nav__toggle-sub' );
			if ( ! btn ) {
				return;
			}
			item.addEventListener( 'mouseenter', function () {
				if ( ! isMobile( nav ) ) {
					btn.setAttribute( 'aria-expanded', 'true' );
				}
			} );
			item.addEventListener( 'mouseleave', function () {
				if ( ! isMobile( nav ) ) {
					btn.setAttribute( 'aria-expanded', 'false' );
				}
			} );
		} );

		/* ---------------------------------------------------------
		 * Mobile panel
		 * ------------------------------------------------------ */

		function closeMenu( returnFocus ) {
			toggle.setAttribute( 'aria-expanded', 'false' );
			nav.classList.remove( 'is-open' );
			closeAllSubs();
			document.removeEventListener( 'keydown', trapFocus, true );
			if ( returnFocus ) {
				toggle.focus();
			}
		}

		function openMenu() {
			toggle.setAttribute( 'aria-expanded', 'true' );
			nav.classList.add( 'is-open' );
			document.addEventListener( 'keydown', trapFocus, true );
		}

		function isOpen() {
			return toggle.getAttribute( 'aria-expanded' ) === 'true';
		}

		/* HFE has no focus trap: tabbing out of an open mobile menu lands
		   on page content hidden behind the overlay. */
		function trapFocus( e ) {
			if ( 'Tab' !== e.key || ! isOpen() || ! isMobile( nav ) ) {
				return;
			}
			var items = focusables( nav );
			if ( ! items.length ) {
				return;
			}
			var first = items[ 0 ];
			var last  = items[ items.length - 1 ];

			if ( e.shiftKey && document.activeElement === first ) {
				e.preventDefault();
				last.focus();
			} else if ( ! e.shiftKey && document.activeElement === last ) {
				e.preventDefault();
				first.focus();
			}
		}

		toggle.addEventListener( 'click', function () {
			if ( isOpen() ) {
				closeMenu( false );
			} else {
				openMenu();
			}
		} );

		/* ---------------------------------------------------------
		 * Keyboard
		 * ------------------------------------------------------ */

		nav.addEventListener( 'keydown', function ( e ) {

			// Escape: close the innermost thing that is open.
			if ( 'Escape' === e.key ) {
				var openSubBtn = subToggles.filter( function ( b ) {
					return b.getAttribute( 'aria-expanded' ) === 'true';
				} )[ 0 ];

				if ( openSubBtn && ! isMobile( nav ) ) {
					closeSub( openSubBtn );
					openSubBtn.focus();
					e.preventDefault();
				} else if ( isOpen() ) {
					closeMenu( true );
					e.preventDefault();
				}
				return;
			}

			// Arrow keys move between focusable items.
			if ( 'ArrowDown' !== e.key && 'ArrowUp' !== e.key ) {
				return;
			}

			var items = focusables( nav );
			var i     = items.indexOf( document.activeElement );
			if ( -1 === i ) {
				return;
			}

			e.preventDefault();
			var next = 'ArrowDown' === e.key ? i + 1 : i - 1;
			if ( next < 0 ) {
				next = items.length - 1;
			}
			if ( next >= items.length ) {
				next = 0;
			}
			items[ next ].focus();
		} );

		/* ---------------------------------------------------------
		 * Outside click and resize
		 * ------------------------------------------------------ */

		document.addEventListener( 'click', function ( e ) {
			if ( nav.contains( e.target ) ) {
				return;
			}
			closeAllSubs();
			if ( isOpen() ) {
				closeMenu( false );
			}
		} );

		var lastMobile = isMobile( nav );
		window.addEventListener( 'resize', function () {
			var nowMobile = isMobile( nav );
			if ( nowMobile !== lastMobile ) {
				lastMobile = nowMobile;
				closeMenu( false );
			}
		} );
	}

	function init() {
		document.querySelectorAll( '[data-glm-nav]' ).forEach( initNav );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
