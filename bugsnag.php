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

    /**
     * @var Bugsnag\Client $bugsnag
     */
    $bugsnag = new \Bugsnag\Client( BUGSNAG_API_KEY );
    \Bugsnag\Handler::register( $bugsnag );

    $bugsnag_error_level = E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED & ~E_USER_DEPRECATED;
    $bugsnag->getConfig()->setErrorReportingLevel( $bugsnag_error_level );

    $bugsnag->registerCallback( function ( $report ) {

        if ( function_exists( 'wp_get_current_user' ) ) {
            $user = wp_get_current_user();

            if ( ! empty( $user ) && $user->ID > 0 ) {
                $report->setUser( [
                    'id' => $user->ID,
                    'name' => $user->get( 'display_name' ),
                    'email' => $user->get( 'user_email' ),
                ] );
            }
        }

    } );

    add_action( 'bugsnag_notify', function( $name, $message ) use ( $bugsnag ) {
        $bugsnag->notifyError( $name, $message );
    }, 1, 2 );

    add_action( 'bugsnag_notify_exception', function( $e ) use ( $bugsnag ) {
        $bugsnag->notifyException( $e );
    }, 1, 2 );

}

if ( defined( 'BUGSNAG_FRONTEND_API_KEY' ) && ! is_admin() ) {

    add_action( 'wp_head', function() {

        echo "<script src='//d2wy8f7a9ursnm.cloudfront.net/bugsnag-3.min.js' async onload='bugsnagLoaded()'></script>";

        $escaped_key = json_encode( BUGSNAG_FRONTEND_API_KEY );

        // track only local scripts
        $notify_script_hosts = [
            $_SERVER['HTTP_HOST'], // current host
            parse_url( get_home_url(), PHP_URL_HOST ), // site host
            parse_url( apply_filters( 'script_loader_src', get_template_directory_uri() . '/none.js' ), PHP_URL_HOST ), // cdn host
        ];

        $notify_script_hosts = apply_filters( 'bugsnag_notify_script_hosts', array_unique( $notify_script_hosts ) );
        $escaped_hosts = json_encode( $notify_script_hosts );

        echo "<script type=\"text/javascript\">\n";
        echo "\n
            (function(w) {
                w.bugsnagLoaded = function() {
                    var hosts = $escaped_hosts || [];
                    hosts.push(w.location.host);
                    w.Bugsnag.apiKey = $escaped_key;
                    w.Bugsnag.beforeNotify = function(payload) {
                        for (var i = 0; i < hosts.length; i++)
                            if (payload.file.indexOf(hosts[i]) != -1) return true;
                        return false;
                    };
                }
            })(window);";
        echo "\n</script>";

    }, 5 );

}
