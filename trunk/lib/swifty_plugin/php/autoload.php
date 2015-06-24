<?php

if( ! function_exists( 'swifty_autoload_lib_helper' ) ) {

    // turns out that glob is not always working:
    // http://php.net/manual/en/function.glob.php#102691
    // so we use a replacement
    function swifty_glob( $pattern )
    {
        $split = explode( '/', str_replace( '\\', '/', $pattern ) );
        $mask = array_pop( $split );
        $path = implode( '/', $split );
        if( ( $dir = opendir( $path ) ) !== false ) {
            $glob = array();
            while( ( $file = readdir( $dir ) ) !== false ) {
                // Match file mask
                if( fnmatch( $mask, $file ) && is_dir( "$path/$file" ) ) {
                    $glob[ ] = "$path/$file/";
                }
            }
            closedir( $dir );
            return $glob;
        } else {
            return false;
        }
    }

    function swifty_autoload_lib_helper_main( $file_path )
    {
        $best_version = -1;
        $best_dir = '';
        $directories = swifty_glob( WP_PLUGIN_DIR . '/swifty*' );
        swifty_autoload_lib_helper( $directories, '/lib/swifty_plugin', $best_version, $best_dir );
        $directories = swifty_glob( get_theme_root() . '/swifty*' );
        swifty_autoload_lib_helper( $directories, '/ssd/lib/swifty_plugin', $best_version, $best_dir );
//            echo 'BEST... #####' . $best_dir . '#####' . $best_version . '<br>';
        if( $best_dir !== '' ) {
            require_once $best_dir . $file_path;
        }
    }

    function swifty_autoload_lib_helper( $directories, $version_path, &$best_version, &$best_dir )
    {
        if( is_array( $directories ) ) {
            foreach( $directories as $dir ) {
                $file = $dir . $version_path . '/swifty_version.txt';
                $version = -1;
                if( file_exists( $file ) ) {
                    $version = intval( file_get_contents( $file ) );
                }
//                echo '#####' . $dir . '#####' . $version . '<br>';
                if( $version > $best_version || ( $version === $best_version && basename( $dir ) === 'swifty-site' ) ) {
                    $best_version = $version;
                    $best_dir = $dir . $version_path;
//                    echo 'BETTER #####' . $dir . '#####' . $version . '<br>';
                }
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