<?php
/**
 * Plugin Name:       SiteRig Toolbox
 * Plugin URI:        https://www.siterig.io/developers/wordpress/siterig-toolbox
 * Description:       Useful utility tools to manage and enhance your WordPress site.
 * Version:           1.0.0
 * Requires at least: 4.0.0
 * Requires PHP:      7.4
 * Author:            SiteRig
 * Author URI:        https://www.siterig.io
 * Contributors:      mattstone
 * License:           GPL v3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       siterig-toolbox
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    die( 'Invalid request.' );
}

// Set the path to this plugin
define( 'SITERIG_TB_PLUGIN_FILE', __FILE__ );
define( 'SITERIG_TB_PLUGIN_PATH', plugin_dir_path( SITERIG_TB_PLUGIN_FILE ) );
define( 'SITERIG_TB_PLUGIN_BASENAME', plugin_basename( SITERIG_TB_PLUGIN_FILE ) );
define( 'SITERIG_TB_PLUGIN_URL', plugin_dir_url( SITERIG_TB_PLUGIN_FILE ) );

// Check SiteRig Core was not already loaded by another SiteRig plugin or theme
if ( ! class_exists( '\SiteRig\Core' ) ) {
    require_once( SITERIG_TB_PLUGIN_PATH . 'lib/core/class-siterig-core.php' );
}

// Load plugin class
require( SITERIG_TB_PLUGIN_PATH . 'lib/class-siterig-toolbox.php' );

// Create a new instance
new \SiteRig\Toolbox( SITERIG_TB_PLUGIN_FILE );
