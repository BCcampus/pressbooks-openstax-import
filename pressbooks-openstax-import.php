<?php
/**
 * Plugin Name:     Openstax Import for Pressbooks
 * Description:     OpenStax Textbook Import. Enables the importing of 'Offline ZIP' files from the cnx.org domain
 * Author:          BCcampus
 * Author URI:      https://github.com/BCcampus
 * Text Domain:     pressbooks-openstax-import
 * Domain Path:     /languages
 * Version:         1.3.3
 * License:         GPL-3.0+
 * License URI:     http://www.gnu.org/licenses/gpl-3.0.txt
 * Tags: pressbooks, OER, publishing, import, cnx, openstax
 * Pressbooks tested up to: 5.7.0
 * Project Sponsor: BCcampus
 *
 * @package         Pressbooks_Openstax_Import
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BCcampus\Import\OpenStax;

/*
|--------------------------------------------------------------------------
| House Keeping
|--------------------------------------------------------------------------
|
|
|
|
*/
/**
 * check requirements before loading our class
 * trigger admin notices to nudge
 */
add_action(
	'init', function () {
		// Must meet miniumum requirements
		if ( ! @include_once( WP_PLUGIN_DIR . '/pressbooks/compatibility.php' ) ) { // @codingStandardsIgnoreLine
			add_action(
				'admin_notices', function () {
					echo '<div id="message" class="error fade"><p>' . __( 'Openstax Import for Pressbooks cannot find a Pressbooks install.', 'pressbooks-openstax-import' ) . '</p></div>';
				}
			);

			return;
		} elseif ( ! version_compare( PB_PLUGIN_VERSION, '5.0.0', '>=' ) ) {
			add_action(
				'admin_notices', function () {
					echo '<div id="message" class="error fade"><p>' . __( 'Openstax Import for Pressbooks requires Pressbooks 5.0.0 or greater.', 'pressbooks-openstax-import' ) . '</p></div>';
				}
			);

			return;
		}
	}
);

/*
|--------------------------------------------------------------------------
| Autoload
|--------------------------------------------------------------------------
|
|
|
|
*/

if ( function_exists( '\HM\Autoloader\register_class_path' ) ) {
	\HM\Autoloader\register_class_path( 'BCcampus', __DIR__ . '/inc' );
} else {
	require_once( __DIR__ . '/autoloader.php' );
}

$composer = __DIR__ . '/vendor/autoload.php';

if ( file_exists( $composer ) ) {
	require_once( $composer );
}

/*
|--------------------------------------------------------------------------
| Business Time
|--------------------------------------------------------------------------
|
|
|
|
*/
/**
 * Will add our flavour of import to the select list on import page
 *
 * @param $types
 *
 * @return mixed
 */
add_filter(
	'pb_select_import_type', function ( $types ) {
		if ( is_array( $types ) && ! array_key_exists( 'cnx', $types ) ) {
			$types['zip'] = __( 'cnx.org (.zip or URL)', 'pressbooks-openstax-import' );
		}

		return $types;
	}
);

/**
 * Inserts our OpenStax Class into the mix
 *
 * @return OpenStax\Cnx
 */
add_filter(
	'pb_initialize_import', function ( $importer ) {
		$importer[] = new OpenStax\Cnx();

		return $importer;
	}
);

/**
 * added for pb5 compatibility, default timeout on http->request is 5
 * new additions to import routine causing unnecessary, early timeouts
 */
add_filter(
	'http_request_timeout', function ( $timeout ) {
		$timeout = 5400;

		return $timeout;
	}
);

/**
 * Pre PB v5.3.0 admins need to be able to activate the
 * wp-quicklatex plugin, set to fire after \Pressbooks\Admin\Plugins\filter_plugins
 */
add_filter(
	'all_plugins', function ( $plugins ) {
		$slug = 'wp-quicklatex';

		// do nothing if it's already set
		if ( isset( $plugins[ $slug . '/' . $slug . '.php' ] ) ) {
			return $plugins;
		}

		if ( ! is_super_admin() ) {
			// if it's not already active
			if ( ! is_plugin_active_for_network( $slug . '/' . $slug . '.php' ) ) {
				$path   = plugin_dir_path( __DIR__ );
				$exists = file_exists( $path . '/' . $slug . '/' . $slug . '.php' );

				// if file is there
				if ( $exists ) {
					$info                                    = get_plugin_data( $path . '/' . $slug . '/' . $slug . '.php', false, false );
					$plugins[ $slug . '/' . $slug . '.php' ] = $info;
				}
			}
		}

		return $plugins;
	}, 11, 1
);

/**
 * add crude notification mechanism
 */
add_action(
	'admin_enqueue_scripts', function () {
		if ( isset( $_REQUEST['page'] ) && 'pb_import' === $_REQUEST['page'] ) { // @codingStandardsIgnoreLine
			wp_enqueue_script( 'poi-notify', plugin_dir_url( __FILE__ ) . 'assets/scripts/notifications.js', [ 'jquery' ], null, true );

			$quicklatex_status       = ( is_plugin_active( 'wp-quicklatex/wp-quicklatex.php' ) ) ? 1 : 0;
			$post_max_size_str       = ini_get( 'post_max_size' );
			$upload_max_filesize_str = ini_get( 'upload_max_filesize' );
			$memory_limit_str        = ini_get( 'memory_limit' );

			wp_localize_script(
				'poi-notify', 'settings', [
					'active'     => $quicklatex_status,
					'post_max'   => $post_max_size_str,
					'upload_max' => $upload_max_filesize_str,
					'memory_max' => $memory_limit_str,
				]
			);
		}
	}
);
