<?php
/**
 * Variation gallery image helpers.
 *
 * @package Oli_Variation_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Image ID storage, sanitization, and frontend payload helpers.
 */
class Oli_VIG_Images {

	/**
	 * Variation gallery meta key.
	 *
	 * @var string
	 */
	const META_KEY = '_oli_vig_gallery_images';

	/**
	 * Sanitize a list of attachment IDs.
	 *
	 * @param mixed $ids Raw ID list.
	 * @return int[]
	 */
	public static function sanitize_ids( $ids ) {
		$normalized = array();

		foreach ( (array) $ids as $id ) {
			if ( is_array( $id ) ) {
				continue;
			}

			$id = absint( $id );
			if ( $id > 0 ) {
				$normalized[] = $id;
			}
		}

		return array_values( array_unique( $normalized ) );
	}

	/**
	 * Get stored gallery attachment IDs for a variation.
	 *
	 * @param int $variation_id Variation ID.
	 * @return int[]
	 */
	public static function get_ids( $variation_id ) {
		$variation_id = absint( $variation_id );
		if ( $variation_id <= 0 ) {
			return array();
		}

		$image_ids = get_post_meta( $variation_id, self::META_KEY, true );

		if ( empty( $image_ids ) || ! is_array( $image_ids ) ) {
			return array();
		}

		return self::sanitize_ids( $image_ids );
	}

	/**
	 * Save gallery attachment IDs for a variation.
	 *
	 * @param int   $variation_id Variation ID.
	 * @param mixed $image_ids    Image IDs.
	 * @return int[] Saved IDs.
	 */
	public static function save_ids( $variation_id, $image_ids ) {
		$variation_id = absint( $variation_id );
		$image_ids    = self::sanitize_ids( $image_ids );

		if ( $variation_id <= 0 ) {
			return array();
		}

		if ( function_exists( 'wp_attachment_is_image' ) ) {
			$image_ids = array_values(
				array_filter(
					$image_ids,
					static function ( $id ) {
						return wp_attachment_is_image( $id );
					}
				)
			);
		}

		if ( empty( $image_ids ) ) {
			delete_post_meta( $variation_id, self::META_KEY );
			return array();
		}

		update_post_meta( $variation_id, self::META_KEY, $image_ids );

		return $image_ids;
	}

	/**
	 * Compose the gallery IDs shown for a selected variation.
	 *
	 * Extra gallery images replace the parent gallery. The variation featured
	 * image is prepended when it is not already in the extra list. An empty
	 * extra list means "fall back to the main product gallery".
	 *
	 * @param int   $featured_id Featured attachment ID.
	 * @param mixed $gallery_ids Extra gallery IDs.
	 * @return int[] Empty array signals a fallback to the parent gallery.
	 */
	public static function compose_gallery_ids( $featured_id, $gallery_ids ) {
		$gallery_ids = self::sanitize_ids( $gallery_ids );
		$featured_id = absint( $featured_id );

		if ( empty( $gallery_ids ) ) {
			return array();
		}

		if ( $featured_id > 0 && ! in_array( $featured_id, $gallery_ids, true ) ) {
			array_unshift( $gallery_ids, $featured_id );
		}

		return $gallery_ids;
	}

	/**
	 * Native fallback: variation image replaces the first parent-gallery image.
	 *
	 * @param int   $variation_image_id Variation featured image ID.
	 * @param mixed $parent_gallery_ids Parent product gallery IDs including featured.
	 * @return int[]
	 */
	public static function compose_fallback_ids( $variation_image_id, $parent_gallery_ids ) {
		$parent             = self::sanitize_ids( $parent_gallery_ids );
		$variation_image_id = absint( $variation_image_id );

		if ( empty( $parent ) ) {
			return $variation_image_id > 0 ? array( $variation_image_id ) : array();
		}

		if ( $variation_image_id > 0 ) {
			$parent[0] = $variation_image_id;
		}

		return self::sanitize_ids( $parent );
	}

	/**
	 * Whether source IDs should be written over the existing stored IDs.
	 *
	 * A non-empty destination is left alone unless $force is true, so a later
	 * import does not overwrite galleries edited after the first run.
	 *
	 * @param mixed $existing_ids Currently stored IDs.
	 * @param mixed $source_ids   Incoming IDs.
	 * @param bool  $force        Overwrite a non-empty destination.
	 * @return bool
	 */
	public static function should_import( $existing_ids, $source_ids, $force = false ) {
		$source_ids = self::sanitize_ids( $source_ids );
		if ( empty( $source_ids ) ) {
			return false;
		}

		$existing_ids = self::sanitize_ids( $existing_ids );
		if ( $existing_ids === $source_ids ) {
			return false;
		}

		if ( ! empty( $existing_ids ) && ! $force ) {
			return false;
		}

		return true;
	}

	/**
	 * Format a single attachment for the frontend / REST payloads.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return array<string,mixed>|null
	 */
	public static function format_attachment( $attachment_id ) {
		$attachment_id = absint( $attachment_id );
		if ( $attachment_id <= 0 || ! function_exists( 'wp_get_attachment_image_src' ) ) {
			return null;
		}

		$single = wp_get_attachment_image_src( $attachment_id, 'woocommerce_single' );
		$full   = wp_get_attachment_image_src( $attachment_id, 'full' );
		$thumb  = wp_get_attachment_image_src( $attachment_id, 'woocommerce_gallery_thumbnail' );

		if ( ! $single && ! $full ) {
			return null;
		}

		if ( ! $single ) {
			$single = $full;
		}

		if ( ! $thumb ) {
			$thumb = wp_get_attachment_image_src( $attachment_id, 'woocommerce_thumbnail' );
		}

		if ( ! $thumb ) {
			$thumb = $single;
		}

		if ( ! $full ) {
			$full = $single;
		}

		$alt = '';
		if ( function_exists( 'get_post_meta' ) ) {
			$alt = (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
		}

		$title = '';
		if ( function_exists( 'get_the_title' ) ) {
			$title = (string) get_the_title( $attachment_id );
		}

		return array(
			'id'          => $attachment_id,
			'src'         => isset( $single[0] ) ? $single[0] : '',
			'src_w'       => isset( $single[1] ) ? (int) $single[1] : 0,
			'src_h'       => isset( $single[2] ) ? (int) $single[2] : 0,
			'srcset'      => (string) wp_get_attachment_image_srcset( $attachment_id, 'woocommerce_single' ),
			'sizes'       => (string) wp_get_attachment_image_sizes( $attachment_id, 'woocommerce_single' ),
			'full_src'    => isset( $full[0] ) ? $full[0] : '',
			'full_src_w'  => isset( $full[1] ) ? (int) $full[1] : 0,
			'full_src_h'  => isset( $full[2] ) ? (int) $full[2] : 0,
			'thumb_src'   => isset( $thumb[0] ) ? $thumb[0] : '',
			'thumb_src_w' => isset( $thumb[1] ) ? (int) $thumb[1] : 0,
			'thumb_src_h' => isset( $thumb[2] ) ? (int) $thumb[2] : 0,
			'alt'         => sanitize_text_field( $alt ),
			'title'       => sanitize_text_field( $title ),
			'thumb_alt'   => sanitize_text_field( $alt ),
		);
	}

	/**
	 * Format many attachments, skipping missing files.
	 *
	 * @param mixed $ids Attachment IDs.
	 * @return array<int,array<string,mixed>>
	 */
	public static function format_ids( $ids ) {
		$formatted = array();

		foreach ( self::sanitize_ids( $ids ) as $id ) {
			$image = self::format_attachment( $id );
			if ( is_array( $image ) && '' !== $image['src'] ) {
				$formatted[] = $image;
			}
		}

		return $formatted;
	}

	/**
	 * Build the gallery payload for a variation object or ID.
	 *
	 * @param mixed $variation Variation product or ID.
	 * @return array<int,array<string,mixed>>
	 */
	public static function get_variation_gallery( $variation ) {
		$variation_id = 0;
		$featured_id  = 0;

		if ( is_object( $variation ) && method_exists( $variation, 'get_id' ) ) {
			$variation_id = absint( $variation->get_id() );
			if ( method_exists( $variation, 'get_image_id' ) ) {
				$featured_id = absint( $variation->get_image_id() );
			}
		} else {
			$variation_id = absint( $variation );
			if ( $variation_id > 0 && function_exists( 'get_post_thumbnail_id' ) ) {
				$featured_id = absint( get_post_thumbnail_id( $variation_id ) );
			}
		}

		$ids = self::compose_gallery_ids( $featured_id, self::get_ids( $variation_id ) );

		return self::format_ids( $ids );
	}
}
