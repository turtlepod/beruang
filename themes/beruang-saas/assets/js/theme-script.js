/* Beruang SaaS – theme scripts */

( function () {
	function initPwaInstallTrigger() {
		const triggers = Array.from( document.querySelectorAll( '.beruang-install-app-btn,[data-beruang-install-app]' ) );

		if ( ! triggers.length ) {
			return;
		}

		const isStandalone =
			window.matchMedia( '(display-mode: standalone)' ).matches ||
			window.navigator.standalone === true;

		if ( isStandalone ) {
			triggers.forEach( ( trigger ) => ( trigger.hidden = true ) );
			return;
		}

		let deferredPrompt = null;

		function setTriggerState( canInstall ) {
			triggers.forEach( ( trigger ) => {
				trigger.classList.toggle( 'is-ready', canInstall );
				trigger.setAttribute( 'aria-disabled', canInstall ? 'false' : 'true' );
				trigger.setAttribute( 'title', canInstall ? '' : 'If install prompt does not appear, use your browser menu to install this app.' );

				if ( trigger.tagName === 'BUTTON' ) {
					trigger.disabled = false;
				}
			} );
		}

		setTriggerState( false );

		window.addEventListener( 'beforeinstallprompt', function ( event ) {
			event.preventDefault();
			deferredPrompt = event;
			setTriggerState( true );
		} );

		window.addEventListener( 'appinstalled', function () {
			deferredPrompt = null;
			setTriggerState( false );
		} );

		triggers.forEach( ( trigger ) => {
			trigger.addEventListener( 'click', function ( event ) {
				if ( ! deferredPrompt ) {
					event.preventDefault();
					window.alert( 'Install prompt is not available here. On your browser, open menu ( ... ) > Apps > Install this site as an app.' );
					return;
				}

				event.preventDefault();
				deferredPrompt.prompt();
				deferredPrompt.userChoice.then( function () {
					deferredPrompt = null;
					setTriggerState( false );
				} );
			} );
		} );
	}

	initPwaInstallTrigger();

	/**
	 * Initialise a single tab group.
	 *
	 * @param {HTMLElement}       navEl     Element containing .beruang-tab buttons (data-tabs-nav).
	 * @param {HTMLElement|null}  contentEl Element containing .beruang-tab-panel sections (data-tabs-content).
	 */
	function initTabGroup( navEl, contentEl ) {
		const tabs = Array.from( navEl.querySelectorAll( '.beruang-tab' ) );
		const panels = Array.from( contentEl ? contentEl.querySelectorAll( '.beruang-tab-panel' ) : [] );

		if ( ! tabs.length || ! panels.length ) return;

		const swipeContainer = ( contentEl && contentEl.dataset.swipeTabs === 'true' ) ? contentEl : null;
		const tabOrder = tabs.map( ( tab ) => tab.dataset.tab );
		const indicator = navEl.querySelector( '.beruang-tab-indicator' );
		const strip = swipeContainer ? swipeContainer.querySelector( '.beruang-tabs-strip' ) : null;
		let indicatorReady = false;
		let stripReady = false;

		function positionIndicator( tabId, instant = false ) {
			if ( ! indicator ) return;
			const activeTab = tabs.find( ( t ) => t.dataset.tab === tabId );
			if ( ! activeTab ) return;
			if ( ! indicatorReady || instant ) {
				indicator.style.transition = 'none';
			}
			indicator.style.width = activeTab.offsetWidth + 'px';
			indicator.style.transform = 'translateX(' + activeTab.offsetLeft + 'px)';
			if ( ! indicatorReady || instant ) {
				indicator.getBoundingClientRect();
				indicator.style.transition = '';
				indicatorReady = true;
			}
		}

		function getPanelWidth() {
			const gap = strip ? ( parseFloat( getComputedStyle( strip ).columnGap ) || 0 ) : 0;
			return ( swipeContainer ? swipeContainer.getBoundingClientRect().width : 0 ) + gap;
		}

		function slideStrip( tabId, instant = false ) {
			if ( ! strip ) return;
			const idx = tabOrder.indexOf( tabId );
			const panelW = getPanelWidth();
			if ( ! stripReady || instant ) {
				strip.style.transition = 'none';
			} else {
				strip.style.transition = 'margin-left var(--saas-transition)';
			}
			strip.style.marginLeft = ( -idx * panelW ) + 'px';
			if ( ! stripReady ) {
				strip.getBoundingClientRect();
				stripReady = true;
			}
		}

		function updateContentHeight( tabId, instant = false ) {
			if ( ! swipeContainer ) return;
			const activePanel = panels.find( ( p ) => p.dataset.panel === tabId );
			if ( ! activePanel ) return;
			if ( instant ) {
				swipeContainer.style.transition = 'none';
				swipeContainer.style.height = activePanel.scrollHeight + 'px';
				swipeContainer.getBoundingClientRect();
				swipeContainer.style.transition = '';
			} else {
				swipeContainer.style.height = activePanel.scrollHeight + 'px';
			}
		}

		if ( window.ResizeObserver ) {
			const ro = new ResizeObserver( () => {
				const active = tabs.find( ( t ) => t.classList.contains( 'is-active' ) );
				if ( active ) updateContentHeight( active.dataset.tab, true );
			} );
			panels.forEach( ( p ) => ro.observe( p ) );
		}

		function activateTab( tabId, shouldFocus = false ) {
			if ( ! tabOrder.includes( tabId ) ) return;

			tabs.forEach( ( tab ) => {
				const isActive = tab.dataset.tab === tabId;
				tab.classList.toggle( 'is-active', isActive );
				tab.setAttribute( 'aria-selected', isActive ? 'true' : 'false' );
				tab.setAttribute( 'tabindex', isActive ? '0' : '-1' );

				if ( isActive && shouldFocus ) {
					tab.focus();
				}
			} );

			panels.forEach( ( panel ) => {
				const isActive = panel.dataset.panel === tabId;
				panel.classList.toggle( 'is-active', isActive );
				panel.setAttribute( 'aria-hidden', isActive ? 'false' : 'true' );
			} );

			document.dispatchEvent( new CustomEvent( 'beruang-tab-activated', { detail: { tab: tabId } } ) );
			positionIndicator( tabId );
			updateContentHeight( tabId, true );
			slideStrip( tabId );

			if ( window.location.hash.slice( 1 ) !== tabId ) {
				history.replaceState( null, '', '#' + tabId );
			}
		}

		function moveToAdjacentTab( currentTabId, direction, shouldFocus = true ) {
			const currentIndex = tabOrder.indexOf( currentTabId );
			if ( currentIndex < 0 ) return;
			const nextIndex = Math.max( 0, Math.min( tabOrder.length - 1, currentIndex + direction ) );
			activateTab( tabOrder[ nextIndex ], shouldFocus );
		}

		tabs.forEach( ( tab ) => {
			tab.addEventListener( 'click', function () {
				activateTab( tab.dataset.tab );
			} );

			tab.addEventListener( 'keydown', function ( event ) {
				if ( event.key === 'ArrowRight' || event.key === 'ArrowLeft' ) {
					event.preventDefault();
					moveToAdjacentTab( tab.dataset.tab, event.key === 'ArrowRight' ? 1 : -1 );
				}

				if ( event.key === 'Home' ) {
					event.preventDefault();
					activateTab( tabOrder[ 0 ], true );
				}

				if ( event.key === 'End' ) {
					event.preventDefault();
					activateTab( tabOrder[ tabOrder.length - 1 ], true );
				}
			} );
		} );

		// Only respond to hash changes that belong to this group.
		window.addEventListener( 'hashchange', function () {
			const hash = window.location.hash ? window.location.hash.slice( 1 ) : '';
			if ( tabOrder.includes( hash ) ) {
				activateTab( hash );
			}
		} );

		if ( swipeContainer ) {
			let startX = null;
			let startY = null;
			let dragging = false;
			let activeIndexAtStart = 0;

			// Attach touch events to the container, not document, so multiple groups don't interfere.
			swipeContainer.addEventListener( 'touchstart', function ( event ) {
				const touch = event.changedTouches[ 0 ];
				startX = touch.clientX;
				startY = touch.clientY;
				dragging = false;
				activeIndexAtStart = tabOrder.indexOf(
					( tabs.find( ( t ) => t.classList.contains( 'is-active' ) ) || tabs[ 0 ] ).dataset.tab
				);
				if ( strip ) strip.style.transition = 'none';
			}, { passive: true } );

			swipeContainer.addEventListener( 'touchmove', function ( event ) {
				if ( startX === null ) return;
				const touch = event.changedTouches[ 0 ];
				const dx = touch.clientX - startX;
				const dy = touch.clientY - startY;

				if ( ! dragging ) {
					if ( Math.abs( dx ) < 5 ) return;
					if ( Math.abs( dy ) >= Math.abs( dx ) ) {
						startX = null;
						return;
					}
					dragging = true;
				}

				event.preventDefault();

				const panelW = getPanelWidth();
				const basePx = -activeIndexAtStart * panelW;
				const min = -( tabOrder.length - 1 ) * panelW;
				const clamped = Math.max( min, Math.min( 0, basePx + dx ) );
				if ( strip ) strip.style.marginLeft = clamped + 'px';
			}, { passive: false } );

			swipeContainer.addEventListener( 'touchend', function ( event ) {
				if ( ! dragging ) {
					startX = null;
					startY = null;
					return;
				}
				const dx = event.changedTouches[ 0 ].clientX - startX;
				startX = null;
				startY = null;
				dragging = false;

				if ( Math.abs( dx ) >= 40 ) {
					const direction = dx < 0 ? 1 : -1;
					const nextIndex = Math.max( 0, Math.min( tabOrder.length - 1, activeIndexAtStart + direction ) );
					activateTab( tabOrder[ nextIndex ] );
				} else {
					if ( strip ) {
						strip.style.transition = 'margin-left var(--saas-transition)';
						strip.style.marginLeft = ( -activeIndexAtStart * getPanelWidth() ) + 'px';
					}
				}
			}, { passive: true } );
		}

		// Initial activation: prefer URL hash if it belongs to this group.
		const initialHash = window.location.hash ? window.location.hash.slice( 1 ) : '';
		activateTab( tabOrder.includes( initialHash ) ? initialHash : tabOrder[ 0 ] );

		window.addEventListener( 'resize', function () {
			const active = tabs.find( ( t ) => t.classList.contains( 'is-active' ) );
			if ( active ) {
				positionIndicator( active.dataset.tab, true );
				slideStrip( active.dataset.tab, true );
				updateContentHeight( active.dataset.tab, true );
			}
		} );
	}

	/**
	 * Discover all tab groups on the page (via data-tabs-nav) and initialise each.
	 */
	function initTabs() {
		document.querySelectorAll( '[data-tabs-nav]' ).forEach( function ( navEl ) {
			const groupId = navEl.dataset.tabsNav;
			const contentEl = groupId
				? document.querySelector( '[data-tabs-content="' + groupId + '"]' )
				: null;
			initTabGroup( navEl, contentEl );
		} );
	}

	initTabs();

	const hamburger = document.getElementById( 'site-hamburger' );
	const drawer    = document.getElementById( 'site-drawer' );
	const overlay   = document.getElementById( 'site-drawer-overlay' );
	const closeBtn  = document.getElementById( 'site-drawer-close' );

	if ( ! hamburger || ! drawer || ! overlay ) return;

	function openDrawer() {
		drawer.classList.add( 'is-open' );
		overlay.classList.add( 'is-open' );
		drawer.setAttribute( 'aria-hidden', 'false' );
		hamburger.setAttribute( 'aria-expanded', 'true' );
		closeBtn && closeBtn.focus();
	}

	function closeDrawer() {
		drawer.classList.remove( 'is-open' );
		overlay.classList.remove( 'is-open' );
		drawer.setAttribute( 'aria-hidden', 'true' );
		hamburger.setAttribute( 'aria-expanded', 'false' );
		hamburger.focus();
	}

	hamburger.addEventListener( 'click', openDrawer );
	overlay.addEventListener( 'click', closeDrawer );
	closeBtn && closeBtn.addEventListener( 'click', closeDrawer );

	document.addEventListener( 'keydown', function ( e ) {
		if ( e.key === 'Escape' && drawer.classList.contains( 'is-open' ) ) {
			closeDrawer();
		}
	} );
}() );
