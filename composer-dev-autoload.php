<?php

/**
 * This is supposed to run by composer in development environment only.
 */
(function () {
    if ( ! defined( 'ABSPATH' ) ) {
        // Test if this is a development installation
        $dev_wp_path = __DIR__ . '/vendor/wordpress/wordpress/';
        if ( is_dir($dev_wp_path) ) {
            // Setup ABSPATH accordinlgy
	        define( 'ABSPATH', $dev_wp_path );
        } 
    }
    if ( ! defined( 'WPINC' ) ) {
	    define( 'WPINC', 'wp-includes' );
    }
})();
