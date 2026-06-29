/**
 * src/js/modules/navigation.js
 * Accessible mobile navigation toggle.
 */

function debounce( fn, delay ) {
    let timer;
    return ( ...args ) => {
        clearTimeout( timer );
        timer = setTimeout( () => fn.apply( this, args ), delay );
    };
}

export function initNavigation() {

    const toggles = document.querySelectorAll( '[data-nav-toggle]' );

    if(toggles.length) {

        toggles.forEach( ( toggle ) => {

            toggle.addEventListener( 'click', () => {
                // Scroll to top when opening navigation
                // window.scrollTo(0, 0, { behavior: 'smooth' });
                document.body.classList.toggle('nav-is-open');
            } );

        } );

    }

    window.addEventListener( 'scroll', debounce( () => {
        document.body.classList.toggle( 'is-scrolled', window.scrollY > 10 );
    }, 10 ) );

    const languageToggles = document.querySelectorAll('[data-language-toggle]');
    const languageOverlay = document.querySelector('[data-language-overlay]');
    
    if(languageToggles.length) {

        languageToggles.forEach( ( toggle ) => {

            toggle.addEventListener( 'click', () => {
               
                languageOverlay.classList.toggle('is-active');
            } );

        } );

    }


    const menuToggles = document.querySelectorAll( '[data-menu-toggle]' );

    if(menuToggles.length) {

        menuToggles.forEach( ( toggle ) => {

            toggle.addEventListener( 'click', (el) => {
                el.preventDefault();
                el.target.classList.toggle('is-open');
                el.target.setAttribute('aria-expanded', el.target.classList.contains('is-open'));
            } );

        } );

    }

}

document.addEventListener( 'DOMContentLoaded', initNavigation );
