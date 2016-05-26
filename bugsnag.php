<?php
/*
Plugin Name: Bugsnag
Description: WordPress plugin for tacking errors to Bugsnag
Version: 1.1
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

    set_error_handler( array( $bugsnag, 'errorHandler' ) );
    set_exception_handler( array( $bugsnag, 'exceptionHandler' ) );

    add_action( 'bugsnag_notify', function( $name, $message ) use ( $bugsnag ) {
        $bugsnag->notifyError( $name, $message );
    }, 1, 2 );

}