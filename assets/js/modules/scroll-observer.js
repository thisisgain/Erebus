/**
 * src/js/modules/scroll-observer.js
 * IntersectionObserver-based animate-on-scroll.
 * Add data-animate to any element to trigger a CSS animation when in view.
 */

export function initScrollObserver() {
    if ( ! ( 'IntersectionObserver' in window ) ) return;

    const elements = document.querySelectorAll( '[data-animate]' );
    if ( ! elements.length ) return;

    const observer = new IntersectionObserver(
        ( entries ) => {
            entries.forEach( ( entry ) => {
                if ( entry.isIntersecting ) {
                    entry.target.classList.add( 'is-visible' );
                    observer.unobserve( entry.target );
                }
            } );
        },
        { threshold: 0.15 }
    );

    elements.forEach( ( el ) => observer.observe( el ) );
}

document.addEventListener( 'DOMContentLoaded', initScrollObserver );
