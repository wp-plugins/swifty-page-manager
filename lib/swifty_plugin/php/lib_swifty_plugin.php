<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'SwiftyDPageDManagerLibSPluginView' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'lib_swifty_plugin_view.php';
}
require_once plugin_dir_path( __FILE__ ) . 'lib/swifty_class-tgm-plugin-activation.php';

class SwiftyDPageDManagerLibSPlugin extends SwiftyDPageDManagerLibSPluginView
{
    protected $our_swifty_plugins = array();
    protected $added_swifty_slugs = array();

    public function __construct()
    {
        parent::__construct();


    }

    public function admin_add_swifty_menu( $name, $swiftyname, $key, $func, $register_plugin ) {

        // test if it was added earlier
        if( array_key_exists( $key, $this->added_swifty_slugs ) ) {
            return $this->added_swifty_slugs[ $key ];
        }

        // Add the Swifty main admin menu (once for all plugins).
        if ( empty ( $GLOBALS[ 'admin_page_hooks' ][ 'swifty_admin' ] ) ) {
            add_menu_page(
                'Swifty',
                'Swifty',
                'manage_options',
                'swifty_admin',
                array( &$this, 'admin_swifty_menu_page' )
            );
        }

        // Add the admin submenu for our plugin
        $page = add_submenu_page(
            'swifty_admin',
            $name,
            $name,
            'manage_options',
            $key,
            $func
        );

        $this->added_swifty_slugs[ $key ] = $page;

        if ( $register_plugin ) {
            $this->our_swifty_plugins[] = array('key' => $key, 'name' => $name, 'swiftyname' => $swiftyname );
        }
        return $page;
    }

    // first add the menu item then replace the link in it with the url we want
    public function admin_add_swifty_menu_link( $name, $swiftyname, $url, $register_plugin )
    {
        $this->admin_add_swifty_menu( $name, $swiftyname, 'replace_me', null, $register_plugin );

        // we just added the meuitem, so it is always the last one...
        global $submenu;
        $submenu['swifty_admin'][count($submenu['swifty_admin']) -1][2] = $url;
    }

    // The Swifty admin main menu page (For ALL Swifty plugins)
    function admin_swifty_menu_page() {
        echo '<h1>' . __( 'Swifty plugins', 'swifty' ) . '</h1>';

        echo '<h4><br>' . __( 'Active Swifty plugins', 'swifty' ) . ':</h4>';

        foreach( $this->our_swifty_plugins as $plugin ) {
            echo '<a href="' . admin_url( 'admin.php?page=' . $plugin[ 'key' ] ) . '">' . $plugin[ 'name' ] . '</a><br>';
        }
    }

    // Our plugin admin menu page
    function admin_options_menu_page( $admin_page )
    {
        $settings_tabs = array();
        foreach( $this->our_swifty_plugins as $plugin ) {
            $settings_tabs[$plugin[ 'key' ]] = ( $this->is_ss_mode() ? $plugin[ 'swiftyname' ] : $plugin[ 'name' ] );
        }

        // make sure the selected tab exists, last active might be not added this time for some reason
        $tab = isset( $_GET[ 'page' ] ) && array_key_exists( $_GET[ 'page' ], $settings_tabs ) ? $_GET[ 'page' ] : $admin_page;

        // ask plugin for setting pages of this tab
        $settings_links = array();
        $settings_links = apply_filters( 'swifty_admin_page_links_' . $tab, $settings_links );

        reset($settings_links);
        $first_link = key($settings_links);

        $link = isset( $_GET[ 'link' ] ) && array_key_exists( $_GET[ 'link' ], $settings_links ) ? $_GET[ 'link' ] : $first_link;

        ?>

        <script type="text/javascript">

            jQuery( function( $ ) {
                <?php if( $this->is_ss_mode() ) : ?>
                $( '#wpadminbar' ).hide();
                $( '#adminmenuback' ).hide();
                $( '#adminmenuwrap' ).hide();
                $( '#wpcontent' ).css( 'margin-left', '0px' );
                //$('.wp-toolbar').css('padding-top', '0px');
                $( '.updated' ).hide();
                $( '.error' ).hide();
                <?php endif ?>

                $( document ).on( 'click', '.swifty_back_button', function( /*ev*/ ) {
                    // in worst case fallback to main page
                    var backLocation = window.location.protocol + '//' +
                        window.location.hostname + ':' +
                        window.location.port + '/';

                    if ( typeof Storage !== 'undefined' ) {
                        if ( sessionStorage.back_location ) {
                            backLocation = sessionStorage.back_location;
                        }
                    }

                    window.location = backLocation;
                });

            } );


        </script>



        <div class='wrap'>

            <?php if( $this->is_ss_mode() ) : ?>
                <div class="swifty_panel_title_container">
                    <div class="swifty_panel_title_strike"></div>
                    <div class="swifty_panel_title_pos">
                        <div class="swifty_title">
                            <?php endif ?>
                            <h2><?php echo __( 'Swifty Settings', 'swifty' ); ?></h2>
                            <?php if( $this->is_ss_mode() ) : ?>
                        </div>
                    </div>
                </div>
                <br><br>
            <?php endif ?>

            <?php if( $this->is_ss_mode() ) : ?>
                <div class="swifty_back_button swifty_button">
                    <i class="fa fa-caret-left"></i>
                </div>
            <?php endif ?>

            <div class="swifty-wrapper<?php echo $this->is_ss_mode() ? ' swifty_content_right' : ''; ?>">

                <?php settings_errors( 'api-manager' ); ?>

                <h2 class="nav-tab-wrapper">
                    <?php
                    foreach( $settings_tabs as $tab_page => $tab_info ) {
                        $active_tab = $tab == $tab_page ? 'nav-tab-active' : '';
                        echo '<a class="nav-tab ' . $active_tab . '" href="?page=' . $tab_page . '">' . $tab_info . '</a>';
                    }
                    ?>
                </h2>

                <form action='options.php' method='post'>
                    <div class="main">
                        <br><br>
                        <?php
                            $active_link_method = null;

                            foreach ( $settings_links as $link_name => $link_info ) {
                                $active_link= $link == $link_name ? 'nav-link-active' : '';;
                                if($link == $link_name) {
                                    $active_link_method = $link_info[ 'method' ];
                                }
                                echo '<a class="nav-link ' . $active_link . '" href="?page=' . $tab . '&link='. $link_name . '">' . $link_info['title'] . '</a>';
                            }
                            if($active_link_method) {
                                call_user_func( $active_link_method );
                            }
                        ?>
                    </div>
                </form>
            </div>
        </div>
    <?php
    }

    // change the permalink to postname option. Call this on plugin activation:
    //register_activation_hook( __FILE__, array( SwiftyDPageDManagerLibSPlugin::get_instance(), 'change_permalinks' ) );
    public function change_permalinks()
    {
        add_action( 'permalink_structure_changed', array( &$this, 'action_permalink_structure_changed'), 10, 2 );

        global $wp_rewrite;
        $wp_rewrite->set_permalink_structure( '/%postname%/' );

        remove_action( 'permalink_structure_changed', array( &$this, 'action_permalink_structure_changed' ) );
    }

    // helper function
    public function action_permalink_structure_changed( $old_permalink_structure, $permalink_structure )
    {
        // make sure that the functions needed for writing htaccess are available
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/misc.php' );

        // is only triggered when something actually has changed
        global $wp_rewrite;
        $wp_rewrite->flush_rules();
    }

}

function admin_enqueue_styles()
{
    if ( is_user_logged_in() ) {

        global $swifty_css_admin_url;
        global $swifty_css_admin_version;

        wp_enqueue_style(
            'swifty-admin.css',
            $swifty_css_admin_url,
            array(),
            $swifty_css_admin_version,
            'all'
        );

        wp_enqueue_style( 'swifty-font-awesome', '//netdna.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css' );
    }
}

$css_admin_version = (int)'undefined';

global $swifty_css_admin_version;
global $swifty_css_admin_url;
global $scc_buildUse;

if( !isset( $swifty_css_admin_version ) || ( $swifty_css_admin_version < $css_admin_version ) ) {
    $swifty_css_admin_version = $css_admin_version;

    // we need to work around the plugin dir link we use in our development systems
    $plugin_dir      = dirname( dirname( dirname( dirname( __FILE__ ) ) ) );
    // get plugin name
    $plugin_basename = basename( $plugin_dir );
    $plugin_dir_url  = trailingslashit( plugins_url( rawurlencode( $plugin_basename ) ) );

    if( $scc_buildUse == 'build' ) {
        $swifty_css_admin_url = $plugin_dir_url . 'css/swifty-admin.css';
    } else {
        $swifty_css_admin_url = $plugin_dir_url . 'lib/swifty_plugin/css/swifty-admin.css';
    }
}

add_action( 'admin_enqueue_scripts', 'admin_enqueue_styles' );