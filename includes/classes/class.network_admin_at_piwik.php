<?php
    // Network Settings

    class Network_Admin_AT_Piwik
    extends Admin_AT_Piwik
    {

        function __construct() {

            // Load Assets
            add_action('network_admin_menu', array(&$this, 'load_assets_Network_Admin_AT_Piwik'));

            // Single Edit Handler
            add_action( 'admin_action_site_update_network_admin_at_piwik', array($this, 'site_update_Network_Admin_AT_Piwik') );

            // Bulk Edit Handler
            add_action( 'admin_action_bulk_update_network_admin_at_piwik', array($this, 'bulk_update_Network_Admin_AT_Piwik') );

            // Edit Settings Handler
            add_action( 'admin_action_settings_update_network_admin_at_piwik', array($this, 'settings_update_Network_Admin_AT_Piwik') );

        }

        /**
        * Runs when the plugin is initialized
        * 
        * @return void
        */
        function setup_page_Network_Admin_AT_Piwik() {

            $screen = get_current_screen();

            if ( in_array( $screen->id, array( 'toplevel_page_at-piwik-network') ) ) {

                // Filter Network Sites Table Actions
                add_filter('manage_sites_action_links', array($this, 'table_actions_Network_Admin_AT_Piwik'), 10, 3);

                // Filter Network Sites Table Columns
                add_filter('wpmu_blogs_columns', array($this, 'table_columns_Network_Admin_AT_Piwik'), 10, 1);

                // Hook Network Sites Tracking and Updated Row Data
                add_action( 'manage_sites_custom_column', array($this, 'table_rows_Network_Admin_AT_Piwik'), 10, 2 );                

                // Filter Bulk Actions
                add_filter( "bulk_actions-sites-network", array($this, 'bulk_actions_Network_Admin_AT_Piwik'), 10, 1 );

            }

        }

        /**
        * Loads Assets.
        * 
        * @return void
        */
        function load_assets_Network_Admin_AT_Piwik() {

            if ( is_network_admin() ) {

                $this->page_hook = add_menu_page( 'AT Piwik Network Settings', 'AT Piwik', 'manage_network_options', $this::slug, array(&$this, 'display_settings_Network_Admin_AT_Piwik'));

                add_submenu_page( $this::slug, 'AT Piwik Network Settings', 'Settings', 'manage_network_options', 'admin.php?page=' . $this::slug . '&t=settings');

                add_action('load-' . $this->page_hook, array($this, 'setup_page_Network_Admin_AT_Piwik'));
            }

        }

        /**
        * Handle Single Edit Site Update.
        * 
        * @return void
        */        
        function site_update_Network_Admin_AT_Piwik() {

            $id = isset($_POST['id']) ? $_POST['id'] : 0;

            $updated = false;

            if ($id) {

                check_admin_referer( 'at-piwik-edit-site' );

                $details = get_blog_details( $id );

                $attributes = array(
                    'archived' => $details->archived,

                    'spam' => $details->spam,

                    'deleted' => $details->deleted
                );

                if ( ! in_array(1, $attributes) ) {

                    $at_piwik_idsite = isset($_POST['at_piwik_idsite']) ? $_POST['at_piwik_idsite'] : false;

                    $at_piwik_active = isset($_POST['at_piwik_active']) ? 1 : 0;

                    if ( $at_piwik_idsite || ! $at_piwik_active) {

                        $updated = true;

                        switch_to_blog( $id );

                        update_option('at_piwik_idsite', $at_piwik_idsite);

                        update_option('at_piwik_active', $at_piwik_active);

                        update_option('at_piwik_idsite', $at_piwik_idsite);

                        update_option('at_piwik_last_update', time());

                        restore_current_blog();

                    }

                }

            }

            wp_redirect( add_query_arg( array( 'update' => $updated ? 'updated' : 'failed', 'id' => $id ), network_admin_url( 'admin.php?page=' . $this::slug . '&action=edit') ) );

            exit;
        }

        /**
        * Handle Bulk Edit Site Update.
        * 
        * @return void
        */        
        function bulk_update_Network_Admin_AT_Piwik() {

            $blogs = isset($_POST['allblogs']) ? $_POST['allblogs'] : array();

            $site_ids = isset($_POST['site_ids']) ? $_POST['site_ids'] : array();

            $action = isset($_POST['action2']) ? $_POST['action2'] : false;

            $updated = true;

            if ($blogs && $action && $action != '-1') {

                check_admin_referer( 'bulk-sites' );

                $at_piwik_active = $action == 'activate' ? 1 : 0;

                foreach ($blogs as $id) {

                    $at_piwik_idsite = isset($site_ids[$id]) ? $site_ids[$id] : false;

                    if ($at_piwik_idsite || ! $at_piwik_active) {

                        $details = get_blog_details( $id );

                        $attributes = array(

                            'archived' => $details->archived,

                            'spam' => $details->spam,

                            'deleted' => $details->deleted

                        );

                        if ( ! in_array(1, $attributes) ) {

                            switch_to_blog( $id );

                            update_option('at_piwik_active', $at_piwik_active);

                            update_option('at_piwik_idsite', $at_piwik_idsite);

                            update_option('at_piwik_last_update', time());

                            restore_current_blog();
                        }

                    }

                }

            }

            wp_redirect( add_query_arg( array( 'update' => $updated ? 'updated' : 'failed', 'id' => $id ), network_admin_url( 'admin.php?page=' . $this::slug) ) );

            exit;
        }

        /**
        * Handle Settings Tab Update.
        * 
        * @return void
        */        
        function settings_update_Network_Admin_AT_Piwik() {

            $updated = false;

            check_admin_referer( 'at-piwik-edit-settings' );

            $at_piwik_network_admin_tracking_code = isset($_POST['at_piwik_network_admin_tracking_code']) ? wp_unslash($_POST['at_piwik_network_admin_tracking_code']) : false;

            $at_piwik_network_admin_tracking_url = isset($_POST['at_piwik_network_admin_tracking_url']) ? esc_url($_POST['at_piwik_network_admin_tracking_url']) : false;
            
            $at_piwik_network_admin_track_admin = isset($_POST['at_piwik_network_admin_track_admin']) ? 1 : 0;

            if ($at_piwik_network_admin_tracking_url) {

                $updated = true;

                update_site_option('at_piwik_network_admin_track_admin', $at_piwik_network_admin_track_admin);
                
                update_site_option('at_piwik_network_admin_tracking_code', $at_piwik_network_admin_tracking_code);

                update_site_option('at_piwik_network_admin_tracking_url', $at_piwik_network_admin_tracking_url);

            }

            wp_redirect( add_query_arg( array( 'update' => $updated ? 'updated' : 'failed' ), network_admin_url( 'admin.php?page=' . $this::slug . '&t=settings') ) );

            exit;
        }

        /**
        * Displays the Network Admin Page.
        * 
        * @return void
        */        
        function display_settings_Network_Admin_AT_Piwik() {

            $action = isset($_GET['action']) ? $_GET['action'] : 'view';

            $tab = isset($_GET['t']) ? $_GET['t'] : 'network';

            switch ($action) :

            case 'edit' :

                $this->edit_site_Network_Admin_AT_Piwik();

                break;

            case 'view' :

            default     :

            ?>

            <div class="wrap">

                <div id="icon-options-general" class="icon32"><br></div>

                <h2>

                    <?php _e( 'ArsTropica Piwik Plugin Network Settings', 'at-piwik' ) ?>

                    <?php if ( isset( $_REQUEST['s'] ) && $_REQUEST['s'] ) {
                        printf( '<span class="subtitle">' . __( 'Search results for &#8220;%s&#8221;' ) . '</span>', esc_html( $_REQUEST['s'] ) );
                    } ?>

                </h2>

                <br />

                <?php

                    if ( isset($_GET['update']) ) {

                        $messages = array();

                        if ( 'updated' == $_GET['update'] )

                            $messages[1] = __('Site(s) have been updated.');

                        else

                            $messages[0] = __('One or more Sites could not be updated.');

                    }

                    if ( ! empty( $messages ) ) {

                        foreach ( $messages as $status => $msg )

                            echo '<div id="message" class="' . ($status ? 'updated' : 'error') . '"><p>' . $msg . '</p></div>';

                        echo '<br />';

                    }

                ?>

                <?php $this->tabs_Network_Admin_AT_Piwik($tab); ?>

                <br />

                <?php 

                    switch ($tab) :

                    case 'settings' :

                        $this->plugin_settings_Network_Admin_AT_Piwik();

                        break;

                    case 'network' :

                    default :

                        $this->network_settings_Network_Admin_AT_Piwik();

                        break;

                        endswitch;
                ?>

            </div>

            <?php

                break;

                endswitch;

        }

        /**
        * Display Network Section of Settings Page
        * 
        */
        function network_settings_Network_Admin_AT_Piwik() {

            $wp_list_table = _get_list_table( 'WP_MS_Sites_List_Table', array('screen' => 'sites-network', 'plural' => 'sites', 'singular' => 'site') );

            $pagenum = $wp_list_table->get_pagenum();

            $wp_list_table->_actions = $this->bulk_actions_Network_Admin_AT_Piwik();

            $wp_list_table->prepare_items();

        ?>

        <form action="<?php echo network_admin_url('admin.php?page=' . $this::slug); ?>" method="post" id="ms-search">

            <?php $wp_list_table->search_box( __( 'Search Sites' ), 'site' ); ?>

            <input type="hidden" name="action" value="blogs" />

        </form>

        <form action="<?php echo network_admin_url('admin.php'); ?>" class="at-piwik-settings" method="post">

            <input type="hidden" name="action" value="bulk_update_network_admin_at_piwik" />

            <?php $wp_list_table->display(); ?>

        </form>

        <script type="text/javascript">

            jQuery( document ).ready(function( $ ) {

                $('SELECT[name=action2]').on('change', function(e){

                    $('SELECT[name=action2]').val($(this).val());

                });

            });

        </script>

        <?php

        }

        /**
        * Display Edit Site Section of Settings Page
        * 
        */
        function edit_site_Network_Admin_AT_Piwik() {

            $unauthorized = false;

            if ( ! is_multisite() )
                $unauthorized = __( 'Multisite support is not enabled.' );

            if ( ! current_user_can( 'manage_sites' ) )
                $unauthorized = __( 'You do not have sufficient permissions to edit this site.' );

            $id = isset( $_REQUEST['id'] ) ? intval( $_REQUEST['id'] ) : 0;

            if ( ! $id )
                $unauthorized = __('Invalid site ID.');

            $details = get_blog_details( $id );
            if ( !can_edit_network( $details->site_id ) )
                $unauthorized = __( 'You do not have permission to access this page.' );

            if ($unauthorized) {

                echo "<p>{$unauthorized}</p>\n";

                return;

            }

            $site_url_no_http = preg_replace( '#^http(s)?://#', '', get_blogaddress_by_id( $id ) );

            $title_site_url_linked = sprintf( __('Piwik Tracking: <a href="%1$s">%2$s</a>'), get_blogaddress_by_id( $id ), $site_url_no_http );

            $at_piwik_idsite = get_blog_option($id, 'at_piwik_idsite', false);

            $at_piwik_active = get_blog_option($id, 'at_piwik_active', false);

            $at_piwik_last_update = get_blog_option($id, 'at_piwik_last_update', false);

            $date = 'Y/m/d g:i:s a';

            $is_main_site = is_main_site( $id );

            if ( isset($_GET['update']) ) {

                $messages = array();

                if ( 'updated' == $_GET['update'] )

                    $messages[1] = __('Site updated.');

                else

                    $messages[0] = __('Site could not be updated.');

            }

            if ( ! empty( $messages ) ) {

                foreach ( $messages as $status => $msg )

                    echo '<div id="message" class="' . ($status ? 'updated' : 'error') . '"><p>' . $msg . '</p></div>';

            }

        ?>

        <div class="wrap">

            <div id="icon-options-general" class="icon32"><br></div>

            <h2 id="edit-site"><?php echo $title_site_url_linked ?></h2>

            <br />

            <form method="post" action="<?php echo network_admin_url('admin.php'); ?>">

                <?php wp_nonce_field( 'at-piwik-edit-site' ); ?>

                <input type="hidden" name="id" value="<?php echo esc_attr( $id ) ?>" />

                <input type="hidden" name="action" value="site_update_network_admin_at_piwik" />

                <table class="form-table">

                    <tr class="form-field form-required">

                        <th scope="row"><?php _e( 'Domain' ) ?></th>

                        <?php
                            $protocol = is_ssl() ? 'https://' : 'http://';

                            if ( $is_main_site ) { ?>

                            <td><code><?php echo $protocol; echo esc_attr( $details->domain ) ?></code></td>

                            <?php } else { ?>

                            <td><?php echo $protocol; ?><input type="text" id="domain" value="<?php echo esc_attr( $details->domain ) ?>" size="33" readonly="readonly" /></td>

                            <?php } ?>
                    </tr>

                    <tr class="form-field">

                        <th scope="row"><?php _e( 'Piwik Site ID' ) ?></th>

                        <td><input type="text" name="at_piwik_idsite" id="at_piwik_idsite" value="<?php echo $at_piwik_idsite; ?>" size="4" style="width: 50px;" /></td>

                    </tr>

                    <?php

                        $attributes = array();

                        $attributes['archived'] = $details->archived;

                        $attributes['spam']     = $details->spam;

                        $attributes['deleted']  = $details->deleted;

                    ?>

                    <tr>
                        <th scope="row"><?php _e( 'Enable Tracking' ); ?></th>

                        <td>

                            <label><input type="checkbox" name="at_piwik_active" value="1" <?php checked( (bool) $at_piwik_active, true ); disabled( in_array( 1, $attributes ) ); ?> />

                            </label><br/>


                        </td>

                    </tr>

                    <tr class="form-field">

                        <th scope="row"><?php _e( 'Last Updated' ); ?></th>

                        <td>

                            <label><?php echo ( ! $at_piwik_last_update ) ? __( 'Never' ) : mysql2date( $date, date('Y-m-d h:i:s', $at_piwik_last_update) ); ?></label>

                            <input name="at_piwik_last_update" type="hidden" id="at_piwik_last_update" value="<?php echo $at_piwik_last_update ?>" />

                        </td>

                    </tr>

                </table>

                <?php submit_button(); ?>

            </form>

        </div>

        <?php

        }

        /**
        * Plugin Settings Section of the Settings Page
        * 
        */
        function plugin_settings_Network_Admin_AT_Piwik() {

            $unauthorized = false;

            if ( ! is_multisite() )
                $unauthorized = __( 'Multisite support is not enabled.' );

            if ( ! current_user_can( 'manage_sites' ) )
                $unauthorized = __( 'You do not have sufficient permissions to edit this site.' );

            if ($unauthorized) {

                echo "<p>{$unauthorized}</p>\n";

                return;

            }

            $at_piwik_network_admin_tracking_code = get_site_option('at_piwik_network_admin_tracking_code', false);

            $at_piwik_network_admin_tracking_url = get_site_option('at_piwik_network_admin_tracking_url', false);

            $at_piwik_network_admin_track_admin = get_site_option('at_piwik_network_admin_track_admin', false);
            
            $tracking_placeholder = esc_attr(file_get_contents( self::$plugin_path . 'public/tracking_template.inc' ));
            
            if ( isset($_GET['update']) ) {

                $messages = array();

                if ( 'updated' == $_GET['update'] )

                    $messages[1] = __('Settings updated.');

                else

                    $messages[0] = __('Settings could not be updated.');

            }

            if ( ! empty( $messages ) ) {

                foreach ( $messages as $status => $msg )

                    echo '<div id="message" class="' . ($status ? 'updated' : 'error') . '"><p>' . $msg . '</p></div>';

            }

        ?>

        <form method="post" action="<?php echo network_admin_url('admin.php'); ?>">

            <?php wp_nonce_field( 'at-piwik-edit-settings' ); ?>

            <input type="hidden" name="action" value="settings_update_network_admin_at_piwik" />

            <table class="form-table">

                <tr class="form-field form-required">

                    <th scope="row"><?php _e( 'Piwik Server URL' ) ?></th>

                    <td>

                        <input type="text" name="at_piwik_network_admin_tracking_url" id="at_piwik_network_admin_tracking_url" value="<?php echo esc_attr( $at_piwik_network_admin_tracking_url ); ?>" size="33" placeholder="http(s)://" />

                        <p class="description">Piwik Server URL. <br /><strong>Do not change this unless you know what you are doing!</strong></p>

                    </td>

                </tr>

                <tr class="form-field form-required">

                    <th scope="row"><?php _e( 'Sitewide Tracking Code' ); ?></th>

                    <td>

                        <textarea name="at_piwik_network_admin_tracking_code" id="at_piwik_network_admin_tracking_code" cols="40" rows="10" onclick="this.focus();this.select()" placeholder="<?php echo $tracking_placeholder; ?>"><?php echo esc_textarea( $at_piwik_network_admin_tracking_code ); ?></textarea>

                        <p class="description">Tracking Code Template. <br /><strong>Do not change this unless you know what you are doing!</strong></p>

                    </td>

                </tr>

                <tr>
                    <th scope="row"><?php _e( 'Admin Tracking ' ); ?></th>

                    <td>

                        <label><input type="checkbox" id="at_piwik_network_admin_track_admin" name="at_piwik_network_admin_track_admin" value="1" <?php checked( (bool) $at_piwik_network_admin_track_admin, true ); ?> /> <?php _e( 'Enable' ); ?>
                        </label>

                        <p class="description">Track logged in WordPress admins.</p>

                    </td>

                </tr>

            </table>

            <?php submit_button(); ?>

        </form>

        <?php

        }

        /**
        * Add Tabbed Headings
        */
        function tabs_Network_Admin_AT_Piwik($current = 'network') {

            $tabs = array( 'network' => 'Sites', 'settings' => 'Settings' );

            echo '<h2 class="nav-tab-wrapper">';
            foreach( $tabs as $tab => $name ){
                $class = ( $tab == $current ) ? ' nav-tab-active' : '';
                echo "<a class='nav-tab$class' href='" . network_admin_url('admin.php?page=' . $this::slug) . "&t=$tab'>$name</a>";

            }
            echo '</h2>';

        }

        /**
        * Filter Network Table Actions
        */
        function table_actions_Network_Admin_AT_Piwik($actions, $blog_id, $blogname) {

            $new_actions = array();

            $new_actions['backend']    = "<span class='backend'><a href='" . esc_url( get_admin_url( $blog_id ) ) . "' class='edit'>" . __( 'Dashboard' ) . '</a></span>';

            if (
            get_blog_status($blog_id, 'public') == true
            &&
            get_blog_status($blog_id, 'archived') == false
            &&
            get_blog_status($blog_id, 'spam') == false
            &&
            get_blog_status($blog_id, 'deleted') == false
            ) {

                $new_actions['edit']    = '<span class="edit"><a href="' . esc_url( network_admin_url( 'admin.php?page=' . $this::slug . '&action=edit&id=' . $blog_id ) ) . '">' . __( 'Edit' ) . '</a></span>';

            }

            return $new_actions;

        }

        /**
        * Filter Network Table Columns
        */
        function table_columns_Network_Admin_AT_Piwik($sites_columns) {

            $blogname_columns = ( is_subdomain_install() ) ? __( 'Domain' ) : __( 'Path' );

            $sites_columns = array(
                'cb'          => '<input type="checkbox" />',
                'blogname'    => $blogname_columns,
                'piwik_id'       => __( 'Piwik ID' ),
                'piwik_tracking'       => __( 'Tracking' ),
                'piwik_updated' => __( 'Last Updated' )
            );

            if ( has_filter( 'wpmublogsaction' ) )
                $sites_columns['plugins'] = __( 'Actions' );

            return $sites_columns;
        }

        /**
        * Filter Network Table Rows
        */
        function table_rows_Network_Admin_AT_Piwik($column_name, $blog_id) {

            global $mode;

            $output = "";

            switch ($column_name) {

                case 'piwik_updated' : {

                    $at_piwik_last_updated = get_blog_option($blog_id, "at_piwik_last_update", false);

                    if ( 'list' == $mode )
                        $date = 'Y/m/d';
                    else
                        $date = 'Y/m/d \<\b\r \/\> g:i:s a';

                    $output .= ( ! $at_piwik_last_updated ) ? __( 'Never' ) : mysql2date( $date, date('Y-m-d', $at_piwik_last_updated) );

                    break;
                }
                case 'piwik_id' : {

                    $piwik_id = get_blog_option($blog_id, "at_piwik_idsite", false);

                    $output .= ( ! $piwik_id ) ? __( ' - ' ) : __( $piwik_id );

                    break;
                }
                case 'piwik_tracking' : {

                    $piwik_tracking = get_blog_option($blog_id, "at_piwik_active", false);

                    $output .= ( ! $piwik_tracking ) ? __( 'Inactive' ) : __( 'Active' );

                    break;
                }
            }

            if ($output)
                echo $output;

        }

        /**
        * Bulk Actions
        */
        function bulk_actions_Network_Admin_AT_Piwik() {

            $new_bulk_actions = array();

            if ( current_user_can( 'delete_sites' ) ) {
                $new_bulk_actions['activate'] = __( 'Activate', 'at-piwik' );
                $new_bulk_actions['deactivate'] = __( 'Deactivate', 'at-piwik' );
            }

            return $new_bulk_actions;
        }


    }
?>
