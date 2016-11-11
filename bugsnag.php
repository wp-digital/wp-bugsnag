<?php
/*
Plugin Name: Bugsnag
Description: WordPress plugin for tacking errors to Bugsnag
Version: 1.2
Plugin URI: https://github.com/shtrihstr/wp-bugsnag
Author: Oleksandr Strikha
Author URI: https://github.com/shtrihstr
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

if ( defined( 'BUGSNAG_API_KEY' ) ) {

    require_once __DIR__ . '/vendor/autoload.php';

    $bugsnag = new Bugsnag_Client( BUGSNAG_API_KEY );
    $bugsnag->setErrorReportingLevel( E_ERROR | E_PARSE );

    set_error_handler( [ $bugsnag, 'errorHandler' ] );
    set_exception_handler( [ $bugsnag, 'exceptionHandler' ] );

    add_action( 'bugsnag_notify', function( $name, $message ) use ( $bugsnag ) {
        $bugsnag->notifyError( $name, $message );
    }, 1, 2 );

}


if ( defined( 'BUGSNAG_FRONTEND_API_KEY' ) && ! is_admin() ) {

    add_action( 'wp_enqueue_scripts', function() {

        wp_enqueue_script( 'bugsnag', '//d2wy8f7a9ursnm.cloudfront.net/bugsnag-3.min.js' );

    }, -1 );

    add_filter( 'script_loader_tag', function( $tag, $handle ) {

        if( 'bugsnag' == $handle ) {
            $tag = str_replace( 'src=', sprintf( "data-apikey='%s' src=", esc_attr( BUGSNAG_FRONTEND_API_KEY ) ), $tag );
        }

        return $tag;
    }, 5, 2 );

}