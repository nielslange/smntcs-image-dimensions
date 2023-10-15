<?php
/**
 * Plugin Name:           SMNTCS Image Dimensions
 * Plugin URI:            https://github.com/nielslange/smntcs-image-dimensions
 * Description:           Shows the image dimension and the image file size in the media library.
 * Author:                Niels Lange
 * Author URI:            https://nielslange.de
 * Text Domain:           smntcs-image-dimensions
 * Version:               1.5
 * Requires PHP:          5.6
 * Requires at least:     5.2
 * License:               GPL v2 or later
 * License URI:           https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package SMNTCS_Image_Dimensions
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class SMNTCS_Image_Dimensions
 */
class SMNTCS_Image_Dimensions {

	/**
	 * SMNTCS_Image_Dimensions constructor.
	 */
	public function __construct() {
		register_activation_hook( __FILE__, array( $this, 'activate_plugin' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate_plugin' ) );

		add_action( 'add_attachment', array( $this, 'attachment_fields_to_save' ), 10, 1 );
		add_filter( 'manage_media_columns', array( $this, 'manage_media_columns' ) );
		add_action( 'manage_media_custom_column', array( $this, 'manage_media_custom_column' ), 10, 2 );
		add_filter( 'manage_upload_sortable_columns', array( $this, 'manage_upload_sortable_columns' ) );
		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
		add_action( 'admin_head', array( $this, 'admin_head' ) );
	}

	/**
	 * Activate plugin.
	 */
	public function activate_plugin() {
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

	/**
	 * Deactivate plugin.
	 */
	public function deactivate_plugin() {
		global $wpdb;

		$attachments = $wpdb->get_results( "SELECT ID FROM $wpdb->posts WHERE post_mime_type LIKE '%image%'" );
		foreach ( $attachments as $attachment ) {
			delete_post_meta( $attachment->ID, '_filesize' );
		}
	}

	/**
	 * Save attachment fields.
	 *
	 * @param int $post_id Post ID.
	 */
	public function attachment_fields_to_save( $post_id ) {
		$filesize = filesize( get_attached_file( $post_id ) );
		update_post_meta( $post_id, '_filesize', $filesize );

		list(, $width, $height) = wp_get_attachment_image_src( $post_id, 'full' );
		update_post_meta( $post_id, '_width', intval( $width ) );
		update_post_meta( $post_id, '_height', intval( $height ) );
	}

	/**
	 * Manage media columns.
	 *
	 * @param array $posts_columns Posts columns.
	 * @return array
	 */
	public function manage_media_columns( $posts_columns ) {
		$posts_columns['dimensions'] = __( 'Dimensions', 'smntcs-image-dimensions' );
		$posts_columns['width']      = __( 'Width', 'smntcs-image-dimensions' );
		$posts_columns['height']     = __( 'Height', 'smntcs-image-dimensions' );
		$posts_columns['filesize']   = __( 'File Size', 'smntcs-image-dimensions' );
		return $posts_columns;
	}

	/**
	 * Manage media custom column.
	 *
	 * @param string $column_name Column name.
	 * @param int    $post_id Post ID.
	 */
	public function manage_media_custom_column( $column_name, $post_id ) {
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

	/**
	 * Manage upload sortable columns.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public function manage_upload_sortable_columns( $columns ) {
		$columns['height']   = 'height';
		$columns['width']    = 'width';
		$columns['filesize'] = 'filesize';

		return $columns;
	}

	/**
	 * Pre get posts.
	 *
	 * @param WP_Query $query Query.
	 */
	public function pre_get_posts( $query ) {
		if ( ! is_admin() ) {
			return;
		}

		$orderby = $query->get( 'orderby' );
		if ( 'width' === $orderby ) {
			$query->set( 'meta_key', '_width' );
			$query->set( 'orderby', 'meta_value_num' );
		}

		if ( 'height' === $orderby ) {
			$query->set( 'meta_key', '_height' );
			$query->set( 'orderby', 'meta_value_num' );
		}

		if ( 'filesize' === $orderby ) {
			$query->set( 'meta_key', '_filesize' );
			$query->set( 'orderby', 'meta_value_num' );
		}
	}

	/**
	 * Add admin CSS.
	 */
	public function admin_head() {
		echo '<style>';
		echo '.column-width, .column-height, .column-filesize { text-align: right; }';
		echo '.column-filesize { width: 10%; }';
		echo '</style>';
	}
}

new SMNTCS_Image_Dimensions();
