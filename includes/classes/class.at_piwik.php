<?php

    class AT_Piwik {

        /**
        * Admin Page Hook
        *
        * Generated by 'add_menu_page' function.
        *
        * @var string
        */
        private $page_hook;

        /**
        * Admin Class
        *
        * Generated by 'init_AT_Piwik' method.
        *
        * @var object
        */
        private $admin_at_piwik;

        /**
        * EndPoint Class
        *
        * Generated by 'init_AT_Piwik' method.
        *
        * @var object
        */
        private $endpoint_at_piwik;

        /**
        * API Key
        *
        * api key for remote changes.
        *
        * @var string
        */
        private $api_key;

        /**
        * Plugin Dir Absolute Path
        *
        * @var string
        */
        static protected $plugin_path;

        const slug = 'at-piwik';

        const name = 'AT_Piwik';

        /**
        * Constructor
        * 
        * @return void
        */
        function __construct($api_key) {
            
            $this->api_key = $api_key;

            self::$plugin_path = plugin_dir_path( dirname( __FILE__ ) );

            // Load Dependencies
            $this->load_dependencies_AT_Piwik();

            // Hook up to the init action
            add_action( 'init', array( &$this, 'init_AT_Piwik' ) );

        }

        /**
        * Load the required dependencies for this class.
        *
        * Include the following files that make up the plugin:
        *
        * - AT_Piwik_EndPoint. EndPoint Handler for API Functionality.
        * - Admin_AT_Piwik. Admin Page Functionality.
        * - Network_Admin_AT_Piwik. Network Admin Page Functionality.
        *
        */

        private function load_dependencies_AT_Piwik() {
            /**
            * The class responsible for handling API Requests to the blog.
            */
            require_once self::$plugin_path . 'classes/class.at_piwik_endpoint.php';
            /**
            * The class responsible for defining admin functionality.
            */
            require_once self::$plugin_path . 'classes/class.admin_at_piwik.php';
            /**
            * The class responsible for defining network admin functionality.
            */
            require_once self::$plugin_path . 'classes/class.network_admin_at_piwik.php';
            /**
            * The class responsible for defining helper functionality.
            */
            require_once self::$plugin_path . 'helpers/functions.php';
        }


        /**
        * Runs when the plugin is initialized
        * 
        * @return void
        */
        function init_AT_Piwik() {
            // Setup localization
            load_plugin_textdomain( self::slug, false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );

            // Load Assets
            if (is_admin() ) {

                // Init Admin_AT_Piwik
                $this->admin_at_piwik = new Admin_AT_Piwik();

            } else {

                $this->endpoint_at_piwik = new EndPoint_AT_Piwik($this->api_key);

                add_filter('query_vars', array($this, 'add_query_vars_AT_Piwik'), 0);

                add_action('parse_request', array($this, 'maybe_ping_AT_Piwik'), 0);

                add_action('wp_loaded', array(&$this, 'load_assets_AT_Piwik'), 100);

            }

        }

        function maybe_ping_AT_Piwik () {

            global $wp;

            $response = array(

                'outcome' => 0,

                'pageview' => false,

                'event' => false,

            );

            $is_multisite = is_multisite();

            $at_piwik_admin_tracking_url = false;

            $at_piwik_idsite = get_option('at_piwik_idsite', false);

            $at_piwik_active = get_option('at_piwik_active', false);

            $at_piwik_admin_override = $is_multisite ? get_option('at_piwik_admin_override', false) : true;

            if ($at_piwik_active && $at_piwik_idsite) {

                if ($is_multisite) {

                    if ($at_piwik_admin_override) {

                        $at_piwik_admin_tracking_url = get_option('at_piwik_admin_tracking_url', false);

                    } else {

                        $at_piwik_admin_tracking_url = get_site_option('at_piwik_network_admin_tracking_url', false);

                    }

                } else {

                    $at_piwik_admin_tracking_url = get_option('at_piwik_admin_tracking_url', false);

                }

            }

            if (

            isset($wp->query_vars['at_piwik_ping'], $wp->query_vars['__api']) 

            && 

            $wp->query_vars['__api'] == $this->endpoint_at_piwik->get_api_key_EndPoint_AT_Piwik()

            ) {

                remove_all_actions('shutdown');

                wp_ob_end_flush_all();

                $at_piwik_base_uri = untrailingslashit(dirname($at_piwik_admin_tracking_url));

                $at_piwik_ping = $wp->query_vars['at_piwik_ping'];

                $client_id = $wp->query_vars['client_id'];

                $request_protocol = (isset($_SERVER['HTTPS']) ? 'https' : 'http');

                require_once(self::$plugin_path . 'helpers/PiwikTracker.php');

                // Call PHP Tracker

                PiwikTracker::$URL = $at_piwik_base_uri;

                $t = new PiwikTracker( $at_piwik_idsite, $at_piwik_base_uri);

                // Optional function calls

                $t->setUserAgent( $_SERVER['HTTP_USER_AGENT'] ?: false );

                $t->setBrowserLanguage( get_bloginfo('language') );

                $t->setLocalTime( date('h:i:s') );

                $t->setIp( $_SERVER['REMOTE_ADDR'] ?: false );

                $t->setCustomVariable( 1, 'client_id', $client_id );

                $t->setUrl( "{$request_protocol}://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}" );

                $response['pageview'] = $t->doTrackPageView('ArsTropica Dashboard Ping Request');

                $response['pageview_url'] = $t->getUrlTrackPageView('ArsTropica Dashboard Ping Request');

                $response['event'] = $t->doTrackEvent( 'Dashboard', 'Ping', 'Alive', floatval($at_piwik_ping) );

                $response['event_url'] = $t->getUrlTrackEvent( 'Dashboard', 'Ping', 'Alive', floatval($at_piwik_ping) );

                $response['at_piwik_base_uri'] = $at_piwik_base_uri;

                $response['outcome'] = 1;

                $response = array_map('utf8_encode', $response);

                echo json_encode($response);

                exit;

            }

        }

        /**
        * Loads Assets.
        * 
        * @return void
        */
        function load_assets_AT_Piwik() {

            //this will run when on the frontend
            add_action('wp_print_scripts', array(&$this, 'load_scripts_AT_Piwik'));

            // Output Tracking Code
            add_action('wp_footer', array(&$this, 'load_tracking_script_AT_Piwik'));

            $at_piwik_admin_override = is_multisite() ? get_option('at_piwik_admin_override', false) : true;

            if ($at_piwik_admin_override) {

                $at_piwik_admin_track_admin = get_option('at_piwik_admin_track_admin', false);

            } else {

                $at_piwik_admin_track_admin = get_site_option('at_piwik_network_admin_track_admin', false);

            }

            if ($at_piwik_admin_track_admin) {

                add_action('admin_footer', array(&$this, 'load_tracking_script_AT_Piwik'));

            }
        }

        /**
        * Load Piwik Tracking Code
        * 
        */
        function load_tracking_script_AT_Piwik() {

            global $wp;

            $is_multisite = is_multisite();

            $at_piwik_tracking_code = false;

            $at_piwik_admin_tracking_code = false;

            $at_piwik_admin_tracking_url = false;

            $at_piwik_idsite = get_option('at_piwik_idsite', false);

            $at_piwik_active = get_option('at_piwik_active', false);

            $at_piwik_admin_override = $is_multisite ? get_option('at_piwik_admin_override', false) : true;

            if ($at_piwik_active && $at_piwik_idsite) {

                if ($is_multisite) {

                    if ($at_piwik_admin_override) {

                        $at_piwik_admin_tracking_code = get_option('at_piwik_admin_tracking_code', false);

                        $at_piwik_admin_tracking_url = get_option('at_piwik_admin_tracking_url', false);

                    } else {

                        $at_piwik_admin_tracking_code = get_site_option('at_piwik_network_admin_tracking_code', false);

                        $at_piwik_admin_tracking_url = get_site_option('at_piwik_network_admin_tracking_url', false);

                    }

                } else {

                    $at_piwik_admin_tracking_code = get_option('at_piwik_admin_tracking_code', false);

                    $at_piwik_admin_tracking_url = get_option('at_piwik_admin_tracking_url', false);

                }

                if ($at_piwik_admin_tracking_url) {

                    $at_piwik_admin_tracking_code = $at_piwik_admin_tracking_code ?: file_get_contents( self::$plugin_path . 'public/tracking_template.inc' );

                    // $at_piwik_admin_tracking_code = '<p><img src="//{at_piwik_tracking_uri}/piwik.php?idsite={at_piwik_idsite}{at_piwik_event_attrs}" style="border:0;" alt="" /></p>';

                    if ($at_piwik_admin_tracking_code) {

                        $at_piwik_admin_scheme = parse_url($at_piwik_admin_tracking_url, PHP_URL_SCHEME) ?: "http";

                        $at_piwik_admin_uri = str_replace(parse_url($at_piwik_admin_tracking_url, PHP_URL_SCHEME) . "://", "", dirname($at_piwik_admin_tracking_url));

                        $at_piwik_blog_domain = parse_url(get_site_url(), PHP_URL_HOST);

                        $at_piwik_tracking_code = str_replace('{at_piwik_domain}', $at_piwik_blog_domain, $at_piwik_admin_tracking_code);

                        $at_piwik_tracking_code = str_replace('{at_piwik_tracking_scheme}', $at_piwik_admin_scheme, $at_piwik_tracking_code);

                        $at_piwik_tracking_code = str_replace('{at_piwik_tracking_uri}', $at_piwik_admin_uri, $at_piwik_tracking_code);

                        $at_piwik_tracking_code = str_replace('{at_piwik_idsite}', $at_piwik_idsite, $at_piwik_tracking_code);

                    }

                }

            }

            if ($at_piwik_tracking_code) {

                echo $at_piwik_tracking_code;

            }

        }

        /**
        * Load Front End CSS/JS
        * 
        */
        function load_scripts_AT_Piwik() {
        } // end register_scripts_and_styles

        /** Add public query vars
        *    @param array $vars List of current public query vars
        *    @return array $vars 
        */
        function add_query_vars_AT_Piwik($vars) {
            $vars[] = 'at_piwik_ping';
            $vars[] = 'client_id';
            $vars[] = '__api';

            return $vars;
        }

    }
    // end class

?>