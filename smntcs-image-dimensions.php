<?php
/**
 * Plugin Name:           SMNTCS Image Dimensions
 * Plugin URI:            https://github.com/nielslange/smntcs-image-dimensions
 * Description:           Shows the image dimension and the image file size in the media library.
 * Author:                Niels Lange
 * Author URI:            https://nielslange.de
 * Text Domain:           smntcs-image-dimensions
 * Version:               1.4
 * Requires PHP:          5.6
 * Requires at least:     5.2
 * License:               GPL v2 or later
 * License URI:           https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package SMNTCS_Image_Dimensions
 */

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

		list(, $width, $height) = wp_get_attachment_image_src( $attachment->ID, 'full' );
		update_post_meta( $attachment->ID, '_width', intval( $width ) );
		update_post_meta( $attachment->ID, '_height', intval( $height ) );
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
 * @param int $post_id The post ID of the attachment.
 * @return void
 */
function smntcs_attachment_fields_to_save( $post_id ) {
	$filesize = filesize( get_attached_file( $post_id ) );
	update_post_meta( $post_id, '_filesize', $filesize );

	list(, $width, $height) = wp_get_attachment_image_src( $post_id, 'full' );
	update_post_meta( $post_id, '_width', intval( $width ) );
	update_post_meta( $post_id, '_height', intval( $height ) );
}
add_action( 'add_attachment', 'smntcs_attachment_fields_to_save', 10, 1 );

/**
 * Add custom admin column title.
 *
 * @param array $posts_columns The initial array of column headings.
 * @return array The initial array of column headings.
 */
function smntcs_manage_media_columns( $posts_columns ) {
	$posts_columns['dimensions'] = __( 'Dimensions', 'smntcs-image-dimensions' );
	$posts_columns['width']      = __( 'Width', 'smntcs-image-dimensions' );
	$posts_columns['height']     = __( 'Height', 'smntcs-image-dimensions' );
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

	if ( 'width' === $column_name ) {
		$width = get_post_meta( $post_id, '_width', true );
		print( esc_attr( $width ) );
	}

	if ( 'height' === $column_name ) {
		$height = get_post_meta( $post_id, '_height', true );
		print( esc_attr( $height ) );
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
	$columns['height']   = 'height';
	$columns['width']    = 'width';
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
	if ( 'width' === $orderby ) {
		$query->set( 'meta_key', '_width' );
		$query->set( 'meta_query', array( 'type' => 'NUMERIC' ) );
		$query->set( 'orderby', 'meta_value_num' );
	}

	if ( 'height' === $orderby ) {
		$query->set( 'meta_key', '_height' );
		$query->set( 'meta_query', array( 'type' => 'NUMERIC' ) );
		$query->set( 'orderby', 'meta_value_num' );
	}

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
	print( '<style>.column-dimensions, .column-height, .column-width, .column-filesize { width: 120px; }</style>' );
}
add_action( 'admin_head', 'smntcs_admin_head' );
