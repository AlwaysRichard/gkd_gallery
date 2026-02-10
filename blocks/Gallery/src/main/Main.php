<?php

namespace gkd\Impl;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Main {

    /**
     * Entry point called by Engine.
     *
     * @param array $attributes
     * @return string HTML
     */
    public function run( array $attributes ) : string {

        error_log( 'GKD Gallery Main::run() called with attributes: ' . print_r( $attributes, true ) );

        // 1. Normalize attributes
        $normalized = $this->normalize_attributes( $attributes );
        error_log( 'GKD Gallery normalized: ' . print_r( $normalized, true ) );

        // 2. Detect context (taxonomy/category archive auto-detection)
        $context = $this->detect_context( $normalized );
        error_log( 'GKD Gallery context: ' . print_r( $context, true ) );

        // 3. Validate
        $error = $this->validate( $context, $normalized );
        if ( $error ) {
            error_log( 'GKD Gallery validation error: ' . $error );
            return '<p>' . esc_html( $error ) . '</p>';
        }

        // 4. Run the appropriate query
        $queries = new Queries();
        $attachments = $this->run_query( $queries, $context, $normalized );
        error_log( 'GKD Gallery found ' . count( $attachments ) . ' attachments' );

        if ( empty( $attachments ) ) {
            error_log( 'GKD Gallery: No attachments found, returning message' );
            return '<p>No images found.</p>';
        }

        // 5. Shape data (limit maxImages, etc.)
        $shaped = $this->shape_data( $attachments, $normalized );

        // 6. Build HTML
        $builder = new HtmlBuilder();
        $html = $builder->build( $shaped, $normalized );
        error_log( 'GKD Gallery built HTML length: ' . strlen( $html ) );
        return $html;
    }


    /* --------------------------------------------------------------
     *  Attribute Normalization
     * -------------------------------------------------------------- */

    private function normalize_attributes( array $a ) : array {

        return [
            'sourceType'        => $a['sourceType']        ?? 'gallery',
            'galleries'         => $a['galleries']         ?? [],
            'categories'        => $a['categories']        ?? [],
            'includeUnpublished'=> $a['includeUnpublished']?? false,
            'layout'            => $a['layout']            ?? 'tiled',
            'columns'           => absint( $a['columns']   ?? 3 ),
            'gutter'            => absint( $a['gutter']    ?? 8 ),
            'targetHeight'      => absint( $a['targetHeight'] ?? 250 ),
            'maxImages'         => absint( $a['maxImages'] ?? 0 ),
            'linkToImage'       => $a['linkToImage']       ?? true,
            'linkToPost'        => $a['linkToPost']        ?? false,
            'crop'              => $a['crop']              ?? true,
            'size'              => $a['size']              ?? 'large',
            'exifTemplate'      => $a['exifTemplate']      ?? '',
        ];
    }


    /* --------------------------------------------------------------
     *  Context Detection (taxonomy/category auto-detection)
     * -------------------------------------------------------------- */

    private function detect_context( array $a ) : array {

        $sourceType  = $a['sourceType'];
        $galleries   = $a['galleries'];
        $categories  = $a['categories'];

        // Auto-detect taxonomy archive
        if ( is_tax( 'gkd_gallery' ) ) {
            $term = get_queried_object();
            if ( $term && isset( $term->term_id ) ) {
                $sourceType = 'gallery';
                $galleries  = [ $term->term_id ];
            }
        }

        // Auto-detect category archive
        elseif ( is_category() ) {
            $term = get_queried_object();
            if ( $term && isset( $term->term_id ) ) {
                $sourceType = 'category';
                $categories = [ $term->term_id ];
            }
        }

        return [
            'sourceType' => $sourceType,
            'galleries'  => $galleries,
            'categories' => $categories,
        ];
    }


    /* --------------------------------------------------------------
     *  Validation
     * -------------------------------------------------------------- */

    private function validate( array $context, array $a ) : string {

        if ( $context['sourceType'] === 'gallery' && empty( $context['galleries'] ) ) {
            return 'No galleries selected.';
        }

        if ( $context['sourceType'] === 'category' && empty( $context['categories'] ) ) {
            return 'No category selected.';
        }

        return '';
    }


    /* --------------------------------------------------------------
     *  Query Selection
     * -------------------------------------------------------------- */

    private function run_query( Queries $queries, array $context, array $a ) : array {

        if ( $context['sourceType'] === 'category' ) {
            return $queries->get_featured_images_from_category(
                $context['categories'][0],
                $a['includeUnpublished']
            );
        }

        return $queries->get_attachments_from_galleries(
            $context['galleries'],
            $a['includeUnpublished']
        );
    }


    /* --------------------------------------------------------------
     *  Data Shaping
     * -------------------------------------------------------------- */

    private function shape_data( array $attachments, array $a ) : array {

        if ( $a['maxImages'] > 0 ) {
            return array_slice( $attachments, 0, $a['maxImages'] );
        }

        return $attachments;
    }
}