<?php
/**
 * Plugin Name:     Presbooks OpenStax Import
 * Plugin URI:      PLUGIN SITE HERE
 * Description:     PLUGIN DESCRIPTION HERE
 * Author:          Brad Payne, Alex Paredes
 * Author URI:      https://bradpayne.ca
 * Text Domain:     pressbooks-openstax-import
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Pressbooks_Openstax_Import
 */

use BCcampus\Import\OpenStax;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// -------------------------------------------------------------------------------------------------------------------
// Setup some defaults
// -------------------------------------------------------------------------------------------------------------------

if ( ! defined( 'POI_DIR' ) ) {
	define( 'POI_DIR', __DIR__ . '/' );
} // Must have trailing slash!

function poi_init() {
	// Must meet miniumum requirements
	if ( ! @include_once( WP_PLUGIN_DIR . '/pressbooks/compatibility.php' ) ) {
		add_action( 'admin_notices', function () {
			echo '<div id="message" class="error fade"><p>' . __( 'Pressbooks OpenStax Import cannot find a Pressbooks install.', 'poi' ) . '</p></div>';
		} );
		return;
	} elseif( ! version_compare( PB_PLUGIN_VERSION, '3.9.9', '>=' ) ) {
		add_action( 'admin_notices', function () {
			echo '<div id="message" class="error fade"><p>' . __( 'Pressbooks OpenStax Import requires Pressbooks 3.9.9 or greater.', 'poi' ) . '</p></div>';
		} );
		return;
	} else {
		require_once POI_DIR . 'inc/modules/import/openstax/class-cnx.php';
	}
}
add_action( 'init', 'poi_init' );

/**
 * Will add our flavour of import to the select list on import page
 *
 * @param $types
 *
 * @return mixed
 */
function poi_add_import_type( $types ) {
	if ( is_array( $types ) && ! array_key_exists( 'cnx' ) ) {
		$types['zip'] = __( 'ZIP (OpenStax zip file, only from https://cnx.org)' );
	}

	return $types;
}
add_filter( 'pb_select_import_type', 'poi_add_import_type' );

/**
 * Inserts our OpenStax Class into the mix
 *
 * @return OpenStax\Cnx
 */
function poi_add_initialize_import(){
	$importer = new OpenStax\Cnx();

	return $importer;
}
add_filter( 'pb_initialize_import', 'poi_add_initialize_import' );

/**
 * Associates a file type with a mimetype
 *
 * @param $allowed_file_types
 *
 * @return array
 */
function poi_import_file_types( $allowed_file_types ) {
	if ( is_array( $allowed_file_types ) &&  array_key_exists( 'pb_import_file_types' ) ) {
		$allowed_file_types['pb_import_file_types']['zip'] = 'application/zip';
	}

	return $allowed_file_types;

}
add_filter( 'pb_import_file_types', 'poi_import_file_types' );

/**
 * Verify WP QuickLaTeX is installed, message goes away once activated
 */
function check_latex() {
	if ( ! is_plugin_active( 'wp-quicklatex/wp-quicklatex.php' ) ) {
		add_action( 'network_admin_notices', function () {
			$plugin_name  = 'WP QuickLaTeX';
			$install_link = '<a href="' . esc_url( network_admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . $plugin_name . '&TB_iframe=true&width=600&height=550' ) ) . '" target="_parent" title="More info about ' . $plugin_name . '">install</a> and activate';
			echo '<div id="message" class="error fade"><p>' . __( '<b>' . 'OpenStax Import:' . '</b>' . ' Please ' . $install_link . ' ' . $plugin_name . ' for multiline equations and svg image export support. ' ) . '</p></div>';
		} );
	}
}

add_action( 'admin_init', 'check_latex' );
