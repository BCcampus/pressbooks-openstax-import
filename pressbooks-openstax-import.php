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
		$types['cnx'] = __( 'ZIP (OpenStax zip file, only from https://cnx.org)' );
	}

	return $types;
}

add_filter( 'pb_select_import_type', 'poi_add_import_type' );

