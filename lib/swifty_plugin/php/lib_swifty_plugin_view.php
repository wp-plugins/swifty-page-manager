<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

require_once plugin_dir_path( __FILE__ ) . 'lib/swifty-captcha.php';

class SwiftyDPageDManagerLibSPluginView
{
    protected static $instance;
    protected static $_ss_mode = null;
    protected static $_valid_modes = array( 'ss', 'wp', 'ss_force' );
    protected static $_default_mode = 'ss';

    public function __construct()
    {
        self::$instance = $this;

        // allow every plugin to get to the initialization part, all plugins should be loaded then
        add_action( 'plugins_loaded', array( $this, 'action_plugins_loaded' ) );
        add_filter( 'swifty_SS2_hosting_name', array( $this, 'filter_swifty_SS2_hosting_name' ) );
    }

    public static function get_instance()
    {
        return self::$instance;
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

    public function action_plugins_loaded()
    {
        self::$required_plugin_active_swifty_site = defined( 'SWIFTY_SITE_PLUGIN_URL' );
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
        if( $autosave && mysql2date( 'U', $autosave->post_modified_gmt, false ) > mysql2date( 'U', $post->post_modified_gmt, false ) ) {
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
}

// load the swifty font, only load the latest version.
if(! function_exists( 'swifty_lib_view_enqueue_styles' ) ) {

    function swifty_lib_view_enqueue_styles()
    {
        if( is_user_logged_in() ) {
            global $swifty_font_url;
            global $swifty_font_version;

            wp_enqueue_style(
                'swifty-font.css',
                $swifty_font_url,
                array(),
                $swifty_font_version,
                'all'
            );
        }
    }

    // load swifty font in both view and edit
    add_action( 'wp_enqueue_scripts', 'swifty_lib_view_enqueue_styles' );
    add_action( 'admin_enqueue_scripts', 'swifty_lib_view_enqueue_styles' );
}

$font_version = (int)'1432037392000';

global $swifty_font_version;
global $swifty_font_url;
global $swifty_buildUse;

if( !isset( $swifty_font_version ) || ( $swifty_font_version < $font_version ) ) {
    $swifty_font_version = $font_version;

    // we need to work around the plugin dir link we use in our development systems
    $plugin_dir      = dirname( dirname( dirname( dirname( __FILE__ ) ) ) );
    // get plugin name
    $plugin_basename = basename( $plugin_dir );
    $plugin_dir_url  = trailingslashit( plugins_url( rawurlencode( $plugin_basename ) ) );

    if( $swifty_buildUse == 'build' ) {
        $swifty_font_url = $plugin_dir_url . 'css/swifty-font.css';
    } else {
        $swifty_font_url = $plugin_dir_url . 'lib/swifty_plugin/css/swifty-font.css';
    }
}
