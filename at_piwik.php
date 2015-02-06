<?php
    /*
    Plugin Name: AT Piwik
    Plugin URI: http://arstropica.com
    Description: This plugin adds the Piwik tracking code to blogs registered on the ArsTropica Piwik Manager Framework.
    Version: 2.1
    Author: Akin Williams
    Author Email: aowilliams@arstropica.com
    License: GNU General Public License v2 or later
    License URI: http://www.gnu.org/licenses/gpl-2.0.html
    Text Domain: at-piwik
    Domain Path: /lang

    */

    /**
    * The core plugin class that is used to define internationalization,
    * dashboard-specific hooks, and public-facing site hooks.
    */
    require plugin_dir_path( __FILE__ ) . 'includes/classes/class.at_piwik.php';

    global $at_piwik;

    /**
    * Begins execution of the plugin.
    *
    * Since everything within the plugin is registered via hooks,
    * then kicking off the plugin from this point in the file does
    * not affect the page life cycle.
    *
    * @since    1.0.0
    */
    function init_at_piwik($api_key = "") {

        global $at_piwik;

        $at_piwik = new AT_Piwik($api_key);

    }

    $api_key = 'xxxxxxxxxxxxxxxx';
    
    init_at_piwik($api_key);

?>