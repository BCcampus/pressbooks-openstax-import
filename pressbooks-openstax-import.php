<?php
/**
 * Plugin Name:     Pressbooks OpenStax Import
 * Description:     OpenStax Textbook Import. Enables the importing of 'Offline ZIP' files from the cnx.org domain
 * Author:          Brad Payne, Alex Paredes
 * Author URI:      https://bradpayne.ca
 * Text Domain:     pressbooks-openstax-import
 * Domain Path:     /languages
 * Version:         0.1.0
 * License:         GPL-3.0+
 * License URI:     http://www.gnu.org/licenses/gpl-3.0.txt
 * Project Sponsor: BCcampus
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
			echo '<div id="message" class="error fade"><p>' . __( 'Pressbooks OpenStax Import cannot find a Pressbooks install.', 'pressbooks-openstax-import' ) . '</p></div>';
		} );

		return;
	} elseif ( ! version_compare( PB_PLUGIN_VERSION, '3.9.9', '>=' ) ) {
		add_action( 'admin_notices', function () {
			echo '<div id="message" class="error fade"><p>' . __( 'Pressbooks OpenStax Import requires Pressbooks 4.0.0 or greater.', 'pressbooks-openstax-import' ) . '</p></div>';
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
		$types['zip'] = __( 'ZIP (OpenStax zip file, only from https://cnx.org)', 'pressbooks-openstax-import' );
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
 * Verify WP QuickLaTeX is installed and active, notice goes away once activated or dismissed
 */
function poi_check_latex() {
	$path = 'wp-quicklatex/wp-quicklatex.php';

	$all_plugins = get_plugins();

	if ( is_plugin_active_for_network( $path ) ) {
		// quickLaTeX plugin is installed and active, do nothing
	} else if ( isset( $all_plugins[ $path ] ) ) {
		// quickLaTex is installed but not active at network level, remind the network administrator to network activate it
		add_action( 'network_admin_notices', function () {
			// don't annoy them anymore if they've dismissed the activate notice
			if ( class_exists( 'PAnD' ) && ! PAnD::is_admin_notice_active( 'activate-notice-forever' ) ) {
				return;
			}
			// annoy them if they haven't dismissed the activate notice
			echo '<div data-dismissible="activate-notice-forever" id="message" class="notice notice-warning is-dismissible"><p>' . __( '<b>' . 'OpenStax Import:' . '</b>' . ' Please network activate WP QuickLaTeX for multiline equations and svg image export support. ', 'pressbooks-openstax-import' ) . '</p></div>';
		} );
		// quickLaTex is installed but not active at book level, remind the book administrator to activate it
		if ( ! is_plugin_active( $path ) ) {

			add_action( 'admin_notices', function () {
				// don't annoy them anymore if they've dismissed the activate notice
				if ( class_exists( 'PAnD' ) && ! PAnD::is_admin_notice_active( 'single-activate-notice-forever' ) ) {
					return;
				}
				// annoy them if they haven't dismissed the activate notice
				echo '<div data-dismissible="single-activate-notice-forever" id="message" class="notice notice-warning is-dismissible"><p>' . __( '<b>' . 'OpenStax Import: ' . '</b>' . 'Your Network Administrator has made ' . '<a target="_blank" href="https://en-ca.wordpress.org/plugins/wp-quicklatex/">' . 'WP QuickLaTeX</a>' . ' available to you from your plugins menu. Please activate it to enable multiline equations, and svg image export support. ', 'pressbooks-openstax-import' ) . '</p></div>';
			} );
		}
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
			echo '<div data-dismissible="install-notice-forever" id="message" class="notice notice-warning is-dismissible"><p>' . __( '<b>' . 'OpenStax Import:' . '</b>' . ' Please ' . $install_link . ' ' . $plugin_name . ' for multiline equations and svg image export support. ', 'pressbooks-openstax-import' ) . '</p></div>';
		} );
	}
}

add_action( 'admin_init', 'poi_check_latex' );
