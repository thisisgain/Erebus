<?php

namespace Origin\WordPress;

class Optimise {
    
    public function __construct()
	{
        add_filter( 'style_loader_src', [$this, 'remove_version'], 9999 );
        add_filter( 'script_loader_src', [$this, 'remove_version'], 9999 );
    }
    
    public function remove_version( $src ) {
        if ( strpos( $src, 'ver=' ) ) {
            $src = remove_query_arg( 'ver', $src );
        }
        return $src;
    }
    
}
