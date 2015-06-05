<?php

if( ! function_exists( 'swifty_autoload_lib_helper' ) ) {
    function swifty_autoload_lib_helper_main( $file_path ) {
        $best_version = -1;
        $best_dir = '';
        $directories = glob( WP_PLUGIN_DIR . '/swifty*', GLOB_ONLYDIR );
        swifty_autoload_lib_helper( $directories, '/lib/swifty_plugin', $best_version, $best_dir );
        $directories = glob( get_theme_root() . '/swifty*', GLOB_ONLYDIR );
        swifty_autoload_lib_helper( $directories, '/ssd/lib/swifty_plugin', $best_version, $best_dir );
//            echo 'BEST... #####' . $best_dir . '#####' . $best_version . '<br>';
        if( $best_dir !== '' ) {
            require_once $best_dir . $file_path;
        }
    }

    function swifty_autoload_lib_helper( $directories, $version_path, &$best_version, &$best_dir ) {
        foreach( $directories as $dir ) {
            $file = $dir . $version_path . '/swifty_version.txt';
            $version = -1;
            if( file_exists( $file ) ) {
                $version = intval( file_get_contents( $file ) );
            }
//            echo '#####' . $dir . '#####' . $version . '<br>';
            if( $version > $best_version || ( $version === $best_version && basename( $dir ) === 'swifty-site' ) ) {
                $best_version = $version;
                $best_dir = $dir . $version_path;
//                echo 'BETTER #####' . $dir . '#####' . $version . '<br>';
            }
        }
    }

    function swifty_autoload_function( $class_name ) {
        if( $class_name === 'LibSwiftyPlugin' ) {
            swifty_autoload_lib_helper_main( '/php/lib_swifty_plugin.php' );

            if( is_null( LibSwiftyPlugin::get_instance() ) ) {
                new LibSwiftyPlugin();
            }
        }
        if( $class_name === 'LibSwiftyPluginView' ) {
            swifty_autoload_lib_helper_main( '/php/lib_swifty_plugin_view.php' );

            if( is_null( LibSwiftyPluginView::get_instance() ) ) {
                new LibSwiftyPluginView();
            }
        }
    }

    spl_autoload_register( 'swifty_autoload_function' );

}