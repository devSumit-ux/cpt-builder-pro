<?php
/**
 * Plugin Name:       CPT Builder Pro
 * Plugin URI:        https://wizeplugins.com/cpt-builder-pro
 * Description:       Create Custom Post Types, Taxonomies, and manage custom fields seamlessly without code.
 * Version:           1.0.0
 * Author:            WizePlugins
 * Author URI:        https://wizeplugins.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       cpt-builder-pro
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'CPTBP_VERSION', '1.0.0' );
define( 'CPTBP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CPTBP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CPTBP_CPT_OPTION_NAME', 'cptbp_post_types' );
define( 'CPTBP_TAX_OPTION_NAME', 'cptbp_taxonomies' );

// Include core files
require_once CPTBP_PLUGIN_DIR . 'includes/cptbp-functions.php';
require_once CPTBP_PLUGIN_DIR . 'includes/cptbp-data-manager.php';
require_once CPTBP_PLUGIN_DIR . 'includes/class-cptbp-cpt.php';
require_once CPTBP_PLUGIN_DIR . 'includes/class-cptbp-taxonomy.php';
require_once CPTBP_PLUGIN_DIR . 'includes/class-cptbp-admin.php';


/**
 * Initialize the plugin.
 * Loads admin interface and registers post types/taxonomies.
 */
function cptbp_init_plugin() {
    // Initialize Admin Area
    if ( is_admin() ) {
        new CPTBP_Admin();
    }

    // Register Post Types and Taxonomies on init
    add_action('init', ['CPTBP_CPT', 'register_all'], 0); // Register CPTs early
    add_action('init', ['CPTBP_Taxonomy', 'register_all'], 0); // Register Taxonomies early
}
add_action( 'plugins_loaded', 'cptbp_init_plugin' );

/**
 * Load plugin textdomain.
 */
function cptbp_load_textdomain() {
    load_plugin_textdomain( 'cpt-builder-pro', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'cptbp_load_textdomain' );

/**
 * Activation hook.
 */
function cptbp_activate() {
    if ( false === get_option( CPTBP_CPT_OPTION_NAME ) ) {
        update_option( CPTBP_CPT_OPTION_NAME, [] );
    }
    if ( false === get_option( CPTBP_TAX_OPTION_NAME ) ) {
        update_option( CPTBP_TAX_OPTION_NAME, [] );
    }
    // Ensure CPTs and Taxonomies are registered before flushing
    CPTBP_CPT::register_all();
    CPTBP_Taxonomy::register_all();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'cptbp_activate' );

/**
 * Deactivation hook.
 */
function cptbp_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'cptbp_deactivate' );
