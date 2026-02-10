<?php

namespace gkd\Impl;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Queries {

    /**
     * Get featured images from posts in a category.
     *
     * @param int $category_id
     * @param bool $include_unpublished
     * @return array Array of attachment objects with parent_post property
     */
    public function get_featured_images_from_category( $category_id, $include_unpublished = false ) {
        $post_status = $include_unpublished ? [ 'publish', 'draft', 'pending', 'private' ] : [ 'publish' ];

        $posts = get_posts( [
            'post_type'      => 'post',
            'post_status'    => $post_status,
            'category'       => $category_id,
            'posts_per_page' => -1,
            'meta_key'       => '_thumbnail_id',
        ] );

        $attachments = [];
        foreach ( $posts as $post ) {
            $thumbnail_id = get_post_thumbnail_id( $post->ID );
            if ( $thumbnail_id ) {
                $attachment = get_post( $thumbnail_id );
                if ( $attachment ) {
                    $attachment->parent_post = $post;
                    $attachments[] = $attachment;
                }
            }
        }

        return $attachments;
    }

    /**
     * Get featured images from posts in galleries.
     *
     * @param array $gallery_ids Array of gallery term IDs
     * @param bool $include_unpublished
     * @return array Array of attachment objects with parent_post property
     */
    public function get_attachments_from_galleries( $gallery_ids, $include_unpublished = false ) {
        if ( empty( $gallery_ids ) ) {
            error_log( 'GKD Gallery Queries: Empty gallery_ids' );
            return [];
        }

        error_log( 'GKD Gallery Queries: Looking for gallery IDs: ' . implode( ', ', $gallery_ids ) );

        $post_status = $include_unpublished ? [ 'publish', 'draft', 'pending', 'private' ] : [ 'publish' ];

        $posts = get_posts( [
            'post_type'      => 'post',
            'post_status'    => $post_status,
            'posts_per_page' => -1,
            'tax_query'      => [
                [
                    'taxonomy' => 'gkd_gallery',
                    'field'    => 'term_id',
                    'terms'    => $gallery_ids,
                ],
            ],
            'meta_key'       => '_thumbnail_id',
        ] );

        error_log( 'GKD Gallery Queries: Found ' . count( $posts ) . ' posts with gallery taxonomy' );

        $attachments = [];
        foreach ( $posts as $post ) {
            $thumbnail_id = get_post_thumbnail_id( $post->ID );
            error_log( 'GKD Gallery Queries: Post ID ' . $post->ID . ' (' . $post->post_title . ') has thumbnail ID: ' . ( $thumbnail_id ?: 'NONE' ) );
            if ( $thumbnail_id ) {
                $attachment = get_post( $thumbnail_id );
                if ( $attachment ) {
                    $attachment->parent_post = $post;
                    $attachments[] = $attachment;
                }
            }
        }

        error_log( 'GKD Gallery Queries: Returning ' . count( $attachments ) . ' attachments' );
        return $attachments;
    }
}
