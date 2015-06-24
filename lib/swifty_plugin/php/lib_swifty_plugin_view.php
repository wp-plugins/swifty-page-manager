<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

require_once plugin_dir_path( __FILE__ ) . 'lib/swifty-captcha.php';

class LibSwiftyPluginView
{
    protected static $instance_view;
    protected static $_ss_mode = null;
    protected static $_valid_modes = array( 'ss', 'wp', 'ss_force' );
    protected static $_default_mode = 'ss';

    public function __construct()
    {
        self::$instance_view = $this;

        // allow every plugin to get to the initialization part, all plugins and theme should be loaded then
        add_action( 'after_setup_theme', array( $this, 'action_after_setup_theme' ) );
        add_filter( 'swifty_SS2_hosting_name', array( $this, 'filter_swifty_SS2_hosting_name' ) );
    }

    public static function get_instance()
    {
        return self::$instance_view;
    }

    public static $required_active_plugins = array();

    public static function is_required_plugin_active( $plugin_name )
    {
        // do we already know the answer?
        if( array_key_exists( $plugin_name, self::$required_active_plugins ) ) {
            return self::$required_active_plugins[ $plugin_name ];
        }
        // no then we will find out: get all plugins and look for the plugin name in the directory name

        if( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $keys = array_keys( get_plugins() );

        $slug = $plugin_name;
        foreach( $keys as $key ) {
            if( preg_match( '|^' . $slug . '/|', $key ) ) {
                $slug = $key;
                break;
            }
        }
        return self::$required_active_plugins[ $plugin_name ] = is_plugin_active( $slug );
    }

    public static $required_plugin_active_swifty_site = false;
    public static $required_theme_active_swifty_site_designer = false;

    public function action_after_setup_theme()
    {
        self::$required_plugin_active_swifty_site = defined( 'SWIFTY_MENU_PLUGIN_FILE' );
        self::$required_theme_active_swifty_site_designer = defined( 'SWIFTY_SITE_DESIGNER_THEME_FILE' );
    }

    protected static $filter_swifty_SS2_hosting_name = null;

    // return the name of the SS2 hoster, when set indicates a full SS2 setup with this name as hosting partner,
    // otherwise returns $default
    public function filter_swifty_SS2_hosting_name( $default )
    {
        if ( ! isset( self::$filter_swifty_SS2_hosting_name ) ) {
            self::$filter_swifty_SS2_hosting_name = get_option( 'ss2_hosting_name' );
        }
        return $default || self::$filter_swifty_SS2_hosting_name;
    }

    public static function add_swifty_to_admin_bar()
    {

        // make sure that the font is loaded for the swifty icon:
        // wp_enqueue_style( 'font_swiftysiteui.css', $this->this_plugin_url . 'css/font_swiftysiteui.css', false, $scc_version );
        // in a hook of wp_head

        global $wp_admin_bar;

        if( ! $wp_admin_bar->get_node( 'swifty' ) ) {

            $title = '<span class="ab-icon"></span><span class="ab-label">Swifty</span>'; // Do not translate!
            $title .= '<span class="screen-reader-text">Swifty</span>'; // Do not translate!

            $wp_admin_bar->add_menu( array(
                'id' => 'swifty',
                'title' => $title,
            ) );
        }
    }

    // test if $plugin_name is active
    public static function is_swifty_plugin_active( $plugin_name )
    {
        return in_array( $plugin_name, apply_filters( 'swifty_active_plugins', array() ) );
    }

    // is swifty menu active?
    // make sure all plugins are constructed before using this function
    public static function is_ss_mode()
    {
        if(! isset( self::$_ss_mode ) ) {
            self::$_ss_mode = ( ( ( empty( $_COOKIE[ 'ss_mode' ] ) || $_COOKIE[ 'ss_mode' ] === 'ss' )
                                  && self::is_swifty_plugin_active( 'swifty-site' ) )
                                || self::is_ss_force() );
        }
        return self::$_ss_mode;
    }

    public static function set_ss_mode()
    {
        // reset the ss_mode, after setting cookies the value might change
        self::$_ss_mode = null;

        $mode = '';

        if( ! empty( $_COOKIE[ 'ss_mode' ] ) && in_array( $_COOKIE[ 'ss_mode' ], self::$_valid_modes ) ) {
            $mode = $_COOKIE[ 'ss_mode' ];
        }

        if( ! empty( $_GET[ 'ss_mode' ] ) && in_array( $_GET[ 'ss_mode' ], self::$_valid_modes ) ) {
            $mode = $_GET[ 'ss_mode' ];
        }

        if( ! $mode ) {
            $mode = self::$_default_mode;
        }

        setcookie( 'ss_mode', $mode, 0, '/' );
        $_COOKIE[ 'ss_mode' ] = $mode;
    }

    public static function is_ss_force()
    {
        return ( !empty( $_COOKIE[ 'ss_mode' ] ) && $_COOKIE[ 'ss_mode' ] === 'ss_force' );
    }

    // find newer version of post, or return null if there is no newer autosave version
    public function get_autosave_version_if_newer( $pid )
    {
        // Detect if there exists an autosave newer than the post and if that autosave is different than the post
        $autosave = wp_get_post_autosave( $pid );
        $post = get_post( $pid );
        $newer_revision = null;
        if( $autosave && $post && ( mysql2date( 'U', $autosave->post_modified_gmt, false ) >= mysql2date( 'U', $post->post_modified_gmt, false ) ) ) {
            foreach( _wp_post_revision_fields() as $autosave_field => $_autosave_field ) {
                if( normalize_whitespace( $autosave->$autosave_field ) != normalize_whitespace( $post->$autosave_field ) ) {
                    if( $autosave_field === 'post_content' ) {
                        $newer_revision = $autosave->$autosave_field;
                    }
                }
            }
            unset( $autosave_field, $_autosave_field );
        }

        return $newer_revision;
    }

    public static function lazy_load_js( $handle, $src = false, $deps = array(), $ver = false, $in_footer = false )
    {
        if( self::$required_theme_active_swifty_site_designer ) {
            do_action( 'swifty_lazy_load_js', $handle, $src, $deps, $ver, $in_footer );
        } else {
            wp_enqueue_script( $handle, $src, $deps, $ver, $in_footer );
        }
    }

    public static function lazy_load_js_min( $handle, $src = false, $deps = array(), $ver = false, $in_footer = false )
    {
        global $swifty_buildUse;
        $bust_add = '?swcv=ss2_' . '1.4.5';
        $file = $src;
        if( $swifty_buildUse == 'build' ) {
            $file = preg_replace( '|\.js$|', '.min.js', $file );
        }
        $file .= $bust_add;
        self::lazy_load_js( $handle, $file, $deps, $ver, $in_footer );
    }

    public static function lazy_load_css( $handle, $src = false, $deps = array(), $ver = false, $media = 'all' )
    {
        if( self::$required_theme_active_swifty_site_designer ) {
            do_action( 'swifty_lazy_load_css', $handle, $src, $deps, $ver, $media );
        } else {
            wp_enqueue_style( $handle, $src, $deps, $ver, $media );
        }
    }
}

// load the swifty font, only load the latest version.
if(! function_exists( 'swifty_lib_view_enqueue_styles' ) ) {

    function swifty_lib_view_enqueue_styles()
    {
        if( is_user_logged_in() ) {
            global $swifty_buildUse;

            if( $swifty_buildUse == 'build' ) {
                $swifty_font_url = get_swifty_lib_dir_url( __FILE__ ) . 'css/swifty-font.css';
            } else {
                $swifty_font_url = get_swifty_lib_dir_url( __FILE__ ) . 'lib/swifty_plugin/css/swifty-font.css';
            }

            $font_version = (int)'1432037392000';

            wp_enqueue_style(
                'swifty-font.css',
                $swifty_font_url,
                array(),
                $font_version,
                'all'
            );
        }
    }

    // load swifty font in both view and edit
    add_action( 'wp_enqueue_scripts', 'swifty_lib_view_enqueue_styles' );
    add_action( 'admin_enqueue_scripts', 'swifty_lib_view_enqueue_styles' );
}

if(! function_exists( 'get_swifty_lib_dir_url' ) ) {

    // returns the plugin or theme url depending on the $file that is used
    // when the lib is used in a theme then the lib is located in the sub folder 'ssd', use this
    // to detect that the $file is used in a theme and not in a plugin
    function get_swifty_lib_dir_url( $file )
    {
        // we need to work around the plugin dir link we use in our development systems
        $plugin_dir = dirname( dirname( dirname( dirname( $file ) ) ) );
        // get plugin name
        $plugin_basename = basename( $plugin_dir );

        // make sure we do not use the theme sub-folder of 'ssd' as plugin name
        if( $plugin_basename != 'ssd' ) {
            // this is a plugin
            $dir_url = trailingslashit( plugins_url( rawurlencode( $plugin_basename ) ) );
        } else {
            // this is a theme
            global $swifty_buildUse;

            // get theme name
            $theme_basename = basename( dirname( $plugin_dir ) );
            $dir_url = trailingslashit( get_template_directory_uri( rawurlencode( $theme_basename ) ) );

            // on non builds we also need this 'ssd' sub folder
            if( $swifty_buildUse != 'build' ) {
                $dir_url = trailingslashit( $dir_url . 'ssd' );
            }
        }
        return $dir_url;
    }
}