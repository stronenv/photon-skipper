<?php
/**
 * Plugin Name:       Jetpack Site Accelerator Skipper
 * Description:       Adds an option in the Media Library to skip Jetpack Site Accelerator (Photon) for individual images.
 * Version:           1.0.0
 * Author:            stronenv
 * Author URI:        https://vegard.blog/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       photon-skipper
 * Domain Path:       /languages
 *
 * @package           photon-skipper
 * @since             1.0.0
 */

/**
 * Add a checkbox to the Media Library attachment edit form to skip Jetpack Site Accelerator (Photon).
 *
 * @since 1.0.0
 *
 * @param array $form_fields Existing form fields.
 * @param WP_Post $post Attachment post object.
 * @return array Modified form fields.
 */
function photon_skipper_attachment_fields_to_edit( $form_fields, $post ) {
	if ( ! current_user_can( 'upload_files' ) ) {
		return $form_fields;
	}

	// Only show if Jetpack Site Accelerator (Photon/Image CDN) is active.
	$photon_active = false;
	if ( class_exists( 'Jetpack' ) ) {
		if ( method_exists( 'Jetpack', 'is_module_active' ) ) {
			if ( Jetpack::is_module_active( 'photon' ) || Jetpack::is_module_active( 'image-cdn' ) ) {
				$photon_active = true;
			}
		}
	}
	if ( ! $photon_active ) {
		return $form_fields;
	}

	$value   = get_post_meta( $post->ID, '_photon_skipper_skip_photon', true );
	$checked = checked( '1', $value, false );
	$nonce   = wp_create_nonce( 'photon_skipper_save_' . $post->ID );

	$form_fields['photon_skipper_skip_photon'] = array(
		'label' => esc_html__( 'Skip Jetpack Site Accelerator', 'photon-skipper' ),
		'input' => 'html',
		'html'  => '<input type="checkbox" name="attachments[' . esc_attr( $post->ID ) . '][photon_skipper_skip_photon]" value="1" ' . $checked . ' />'
			. '<input type="hidden" name="attachments[' . esc_attr( $post->ID ) . '][photon_skipper_nonce]" value="' . esc_attr( $nonce ) . '" />',
		'helps' => esc_html__( 'Check to skip Jetpack Site Accelerator (Photon) for this image.', 'photon-skipper' ),
	);

	return $form_fields;
}
add_filter( 'attachment_fields_to_edit', 'photon_skipper_attachment_fields_to_edit', 10, 2 );

/**
 * Save the skip Photon checkbox value as attachment meta.
 *
 * @since 1.0.0
 *
 * @param array $post Attachment post data.
 * @param array $attachment Attachment form data.
 * @return array Modified post data.
 */
function photon_skipper_attachment_fields_to_save( $post, $attachment ) {
	if ( ! current_user_can( 'upload_files' ) ) {
		return $post;
	}

	$nonce = isset( $attachment['photon_skipper_nonce'] ) ? $attachment['photon_skipper_nonce'] : '';
	if ( ! wp_verify_nonce( $nonce, 'photon_skipper_save_' . $post['ID'] ) ) {
		return $post;
	}

	$value = isset( $attachment['photon_skipper_skip_photon'] ) ? '1' : '';
	update_post_meta( $post['ID'], '_photon_skipper_skip_photon', $value );

	return $post;
}
add_filter( 'attachment_fields_to_save', 'photon_skipper_attachment_fields_to_save', 10, 2 );

/**
 * Skip Jetpack Photon for images with the skip meta set.
 *
 * @since 1.0.0
 *
 * @param bool   $skip     Whether to skip Photon.
 * @param string $image_url Image URL.
 * @param string $tag      Image tag HTML.
 * @return bool Whether to skip Photon.
 */
function photon_skipper_jetpack_photon_skip_image( $skip, $image_url, $tag ) {
	$attachment_id = attachment_url_to_postid( $image_url );
	if ( $attachment_id && '1' === get_post_meta( $attachment_id, '_photon_skipper_skip_photon', true ) ) {
		return true;
	}
	return $skip;
}
add_filter( 'jetpack_photon_skip_image', 'photon_skipper_jetpack_photon_skip_image', 10, 3 );
