<?php
/**
 * Plugin Name: SMNTCS Image Dimensions
 * Plugin URI: https://github.com/nielslange/smntcs-image-dimensions
 * Description: Shows the image dimension and the image file size in the media library.
 * Author: Niels Lange
 * Author URI: https://nielslange.de
 * Text Domain: smntcs-image-dimensions
 * Domain Path: /languages/
 * Version: 1.0
 * Requires at least: 3.4
 * Requires PHP: 7.3
 * Tested up to: 5.8
 * License: GPL2+
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * @category   Plugin
 * @package    WordPress
 * @subpackage SMNTCS Image Dimensions
 * @author     Niels Lange <info@nielslange.de>
 * @license    http://opensource.org/licenses/gpl-license.php GNU Public License
 */

/**
 * Avoid direct plugin access
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Add image file size to post meta on plugin activation.
 *
 * @return void
 */
function smntcs_activate_plugin() {
	global $wpdb;
	$attachments = $wpdb->get_results( "SELECT ID FROM $wpdb->posts WHERE post_mime_type LIKE '%image%'" );
	foreach ( $attachments as $attachment ) {
		$filesize = filesize( get_attached_file( $attachment->ID ) );
		update_post_meta( $attachment->ID, '_filesize', $filesize );
	}
}
register_activation_hook( __FILE__, 'smntcs_activate_plugin' );

/**
 * Remove image file size from post meta on plugin activation.
 *
 * @return void
 */
function smntcs_deactivate_plugin() {
	global $wpdb;
	$attachments = $wpdb->get_results( "SELECT ID FROM $wpdb->posts WHERE post_mime_type LIKE '%image%'" );
	foreach ( $attachments as $attachment ) {
		delete_post_meta( $attachment->ID, '_filesize' );
	}
}
register_deactivation_hook( __FILE__, 'smntcs_deactivate_plugin' );

/**
 * Load textdomain
 *
 * @return void
 */
function smntcs_load_textdomain() {
	load_plugin_textdomain( 'smntcs-image-dimensions', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'smntcs_load_textdomain' );

/**
 * Add custom admin column title.
 *
 * @param array $posts_columns The initial array of column headings.
 * @return array The initial array of column headings.
 */
function smntcs_manage_media_columns( $posts_columns ) {
	$posts_columns['dimensions'] = __( 'Dimensions', 'smntcs-image-dimensions' );
	$posts_columns['filesize']   = __( 'File Size', 'smntcs-image-dimensions' );
	return $posts_columns;
}
add_filter( 'manage_media_columns', 'smntcs_manage_media_columns' );

/**
 * Add custom admin column content.
 *
 * @param string $column_name The name of the column to display.
 * @param int    $post_id The ID of the post entry.
 * @return void
 */
function smntcs_manage_media_custom_column( $column_name, $post_id ) {
	if ( 'dimensions' === $column_name ) {
		list($url, $width, $height) = wp_get_attachment_image_src( $post_id, 'full' );
		printf( '%d &times; %d', (int) $width, (int) $height );
	}

	if ( 'filesize' === $column_name ) {
		$filesize = get_post_meta( $post_id, '_filesize', true );
		print( esc_attr( size_format( $filesize, 2 ) ) );
	}
}
add_action( 'manage_media_custom_column', 'smntcs_manage_media_custom_column', 10, 2 );

/**
 * Make image filesize column sortable.
 *
 * @param array $columns The initial array of sortable columns.
 * @return array The updated array of sortable columns.
 */
function smntcs_manage_upload_sortable_columns( $columns ) {
	$columns['filesize'] = 'filesize';
	return $columns;
}
add_filter( 'manage_upload_sortable_columns', 'smntcs_manage_upload_sortable_columns' );

/**
 * Alter posts query when sorting image filesize.
 *
 * @param WP_Query $query The WP_Query instance.
 * @return void
 */
function smntcs_pre_get_posts( $query ) {
	if ( ! is_admin() ) {
		return;
	}

	$orderby = $query->get( 'orderby' );
	if ( 'filesize' == $orderby ) {
		$query->set( 'meta_key', '_filesize' );
		$query->set( 'orderby', 'meta_value_num' );
	}
}
add_action( 'pre_get_posts', 'smntcs_pre_get_posts' );

/**
 * Adjust width of custom admin columns.
 *
 * @return void
 */
function smntcs_admin_head() {
	print( '<style>.column-filesize, .column-dimensions { width: 120px; }</style>' );
}
add_action( 'admin_head', 'smntcs_admin_head' );
