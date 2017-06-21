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
	} elseif ( ! version_compare( PB_PLUGIN_VERSION, '3.9.9', '>=' ) ) {
		add_action( 'admin_notices', function () {
			echo '<div id="message" class="error fade"><p>' . __( 'Pressbooks OpenStax Import requires Pressbooks 3.9.9 or greater.', 'poi' ) . '</p></div>';
		} );

		return;
	} else {
		require_once POI_DIR . 'inc/modules/import/openstax/class-cnx.php';
	}
	/**
	 * Composer autoloader (if needed)
	 */
	if ( file_exists( $composer = POI_DIR . 'vendor/autoload.php' ) ) {
		require_once( $composer );
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
	if ( is_array( $types ) && ! array_key_exists( 'cnx', $types ) ) {
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
function poi_add_initialize_import() {
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

	if ( is_array( $allowed_file_types ) &&  array_key_exists( 'pb_import_file_types', $allowed_file_types ) ) {
		$allowed_file_types['pb_import_file_types']['zip'] = 'application/zip';
	}

	return $allowed_file_types;

}

add_filter( 'pb_import_file_types', 'poi_import_file_types' );

/**
 * Verify WP QuickLaTeX is installed and active, notice goes away once activated or dismissed
 */
function check_latex() {
	$path = 'wp-quicklatex/wp-quicklatex.php';

	$all_plugins = get_plugins();

	if ( is_plugin_active_for_network( $path ) ) {
		// quickLaTeX plugin is installed and active, do nothing
	} else if ( isset( $all_plugins[ $path ] ) ) {
		// quickLaTex is installed but not active, remind the network administrator to activate it
		add_action( 'network_admin_notices', function () {
			// don't annoy them anymore if they've dismissed the activate notice
			if ( class_exists( 'PAnD' ) && ! PAnD::is_admin_notice_active( 'activate-notice-forever' ) ) {
				return;
			}
			// annoy them if they haven't dismissed the activate notice
			echo '<div data-dismissible="activate-notice-forever" id="message" class="notice notice-warning is-dismissible"><p>' . __( '<b>' . 'OpenStax Import:' . '</b>' . ' Please activate WP QuickLaTeX for multiline equations and svg image export support. ' ) . '</p></div>';
		} );
		// ask book admin to activate it since the Network Admin has made it available to them
		add_action( 'admin_notices', function () {
			// don't annoy them anymore if they've dismissed the activate notice
			if ( class_exists( 'PAnD' ) && ! PAnD::is_admin_notice_active( 'single-activate-notice-forever' ) ) {
				return;
			}
			// annoy them if they haven't dismissed the activate notice
			echo '<div data-dismissible="single-activate-notice-forever" id="message" class="notice notice-warning is-dismissible"><p>' . __( '<b>' . 'OpenStax Import: ' . '</b>' . 'Your Network Administrator has made ' . '<a target="_blank" href="https://en-ca.wordpress.org/plugins/wp-quicklatex/">' . 'WP QuickLaTeX</a>' . ' available to you from your plugins menu. Please activate it to enable multiline equations, and svg image export support. ' ) . '</p></div>';
		} );
	} else {
		// remind Network Admin to install quickLaTeX
		add_action( 'network_admin_notices', function () {
			// don't annoy them if they've dismissed install notice
			if ( class_exists( 'PAnD' ) && ! PAnD::is_admin_notice_active( 'install-notice-forever' ) ) {
				return;
			}
			// annoy them if they haven't dismissed the install notice
			$plugin_name  = 'WP QuickLaTeX';
			$install_link = '<a href="' . esc_url( network_admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . $plugin_name . '&TB_iframe=true&width=600&height=550' ) ) . '" target="_parent" title="More info about ' . $plugin_name . '">install</a> and activate';
			echo '<div data-dismissible="install-notice-forever" id="message" class="notice notice-warning is-dismissible"><p>' . __( '<b>' . 'OpenStax Import:' . '</b>' . ' Please ' . $install_link . ' ' . $plugin_name . ' for multiline equations and svg image export support. ' ) . '</p></div>';
		} );
	}
}

add_action( 'admin_init', 'check_latex' );
