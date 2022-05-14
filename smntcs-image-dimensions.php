<?php
/**
 * Plugin Name: SMNTCS Image Dimensions
 * Plugin URI: https://github.com/nielslange/smntcs-image-dimensions
 * Description: Shows the image dimension and the image file size in the media library.
 * Author: Niels Lange
 * Author URI: https://nielslange.de
 * Text Domain: smntcs-image-dimensions
 * Version: 1.2
 * Stable tag: 1.2
 * Requires PHP: 5.6
 * Requires at least: 5.2
 * License: GPLv2+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @category   Plugin
 * @package    WordPress
 * @subpackage SMNTCS Image Dimensions
 * @author     Niels Lange <info@nielslange.de>
 * @license    http://opensource.org/licenses/gpl-license.php GNU Public License
 */

// Avoid direct plugin access.
defined( 'ABSPATH' ) || exit;

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
 * Add image file size to post meta after media upload.
 *
 * @param int $post_ID The post ID of the attachment.
 * @return void
 */
function smntcs_attachment_fields_to_save( $post_ID ) {
	$filesize = filesize( get_attached_file( $post_ID ) );
	update_post_meta( $post_ID, '_filesize', $filesize );
}
add_action( 'add_attachment', 'smntcs_attachment_fields_to_save', 10, 1 );

/**
 * Add custom admin column title.
 *
 * @param array $posts_columns The initial array of column headings.
 * @return array The initial array of column headings.
 */
function smntcs_manage_media_columns( $posts_columns ) {
	$posts_columns['dimensions']               = __( 'Dimensions', 'smntcs-image-dimensions' );
					$posts_columns['filesize'] = __( 'File Size', 'smntcs-image-dimensions' );
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
	if ( 'filesize' === $orderby ) {
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
