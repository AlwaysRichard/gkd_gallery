<?php

namespace gkd\Impl;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class HtmlBuilder {

    /**
     * Build gallery HTML from shaped attachments + normalized attributes.
     */
    public function build( array $attachments, array $a ) : string {

        $layout       = $a['layout'];
        $columns      = $a['columns'];
        $gutter       = $a['gutter'];
        $targetHeight = $a['targetHeight'];
        $linkToImage  = $a['linkToImage'];
        $linkToPost   = $a['linkToPost'];
        $crop         = $a['crop'];
        $size         = $a['size'];
        $exifTemplate = $a['exifTemplate'];

        $showClickMenu = $linkToImage && $linkToPost;

        // CSS classes
        $classes = [ 'cat-gallery', "cat-gallery--{$layout}" ];
        if ( ! $crop && $layout === 'collage' ) {
            $classes[] = 'cat--nocrop';
        }

        // Inline styles + data attributes
        $style_parts = [];
        $data_attrs  = [];

        if ( $layout === 'grid' ) {
            $style_parts[] = "--cat-grid-cols: repeat({$columns}, 1fr)";
            $style_parts[] = "--cat-gap: {$gutter}px";
        } elseif ( $layout === 'masonry' ) {
            $style_parts[] = "--cat-columns: {$columns}";
            $style_parts[] = "--cat-gap: {$gutter}px";
        } elseif ( $layout === 'tiled' ) {
            $style_parts[] = "--cat-gap: {$gutter}px";
            $data_attrs[]  = "data-target-height=\"{$targetHeight}\"";
            $data_attrs[]  = "data-gutter=\"{$gutter}\"";
        } elseif ( $layout === 'collage' ) {
            $style_parts[] = "--cat-cols: {$columns}";
            $style_parts[] = "--cat-gap: {$gutter}px";
            $style_parts[] = "--cat-row: 12px";
        }

        $style_attr    = $style_parts ? ' style="' . esc_attr( implode( '; ', $style_parts ) ) . '"' : '';
        $data_attr_str = $data_attrs ? ' ' . implode( ' ', $data_attrs ) : '';

        $html = '<div class="' . esc_attr( implode( ' ', $classes ) ) . '"' . $style_attr . $data_attr_str . '>';

        foreach ( $attachments as $att ) {

            $att_id = $att->ID;
            $img    = wp_get_attachment_image_src( $att_id, $size );
            $img_url    = $img[0] ?? '';
            $img_width  = $img[1] ?? 0;
            $img_height = $img[2] ?? 0;

            $file_url      = wp_get_attachment_url( $att_id );
            $post_permalink = isset( $att->parent_post ) ? get_permalink( $att->parent_post->ID ) : '';

            // Caption (EXIF or fallback)
            $caption = '';
            if ( ! empty( $exifTemplate ) ) {
                $caption = \gkd\Library\Exif::build_caption( $att_id, $exifTemplate );
            }
            if ( empty( $caption ) ) {
                $caption = wp_get_attachment_caption( $att_id );
            }

            $html .= '<figure class="cat-gallery__item">';

            // Link logic
            if ( $linkToImage && ! $linkToPost ) {
                $html .= '  <a href="' . esc_url( $file_url ) . '" class="cat-gallery__link cat-gallery__lightbox-trigger" data-full-image="' . esc_url( $file_url ) . '">';
                $html .= '    <img class="cat-gallery__img skip-lazy" src="' . esc_url( $img_url ) . '" alt="' . esc_attr( $caption ) . '" width="' . esc_attr( $img_width ) . '" height="' . esc_attr( $img_height ) . '" loading="eager" />';
                $html .= '  </a>';

            } elseif ( ! $linkToImage && $linkToPost && $post_permalink ) {
                $html .= '  <a href="' . esc_url( $post_permalink ) . '" class="cat-gallery__link">';
                $html .= '    <img class="cat-gallery__img skip-lazy" src="' . esc_url( $img_url ) . '" alt="' . esc_attr( $caption ) . '" width="' . esc_attr( $img_width ) . '" height="' . esc_attr( $img_height ) . '" loading="eager" />';
                $html .= '  </a>';

            } elseif ( $showClickMenu ) {
                $html .= '  <div class="cat-gallery__link cat-gallery__menu-trigger" data-full-image="' . esc_url( $file_url ) . '">';
                $html .= '    <img class="cat-gallery__img skip-lazy" src="' . esc_url( $img_url ) . '" alt="' . esc_attr( $caption ) . '" width="' . esc_attr( $img_width ) . '" height="' . esc_attr( $img_height ) . '" loading="eager" />';
                $html .= '  </div>';

            } else {
                $html .= '  <img class="cat-gallery__img skip-lazy" src="' . esc_url( $img_url ) . '" alt="' . esc_attr( $caption ) . '" width="' . esc_attr( $img_width ) . '" height="' . esc_attr( $img_height ) . '" loading="eager" />';
            }

            // Click menu
            if ( $showClickMenu && $post_permalink ) {
                $html .= '  <div class="cat-gallery__menu" role="menu">';
                $html .= '    <span class="cat-gallery__menu-label">View:</span>';
                $html .= '    <button role="menuitem" class="cat-gallery__lightbox-trigger" data-full-image="' . esc_url( $file_url ) . '">Image</button>';
                $html .= '    <span class="cat-gallery__menu-sep">|</span>';
                $html .= '    <a role="menuitem" href="' . esc_url( $post_permalink ) . '">Post</a>';
                $html .= '  </div>';
            }

            $html .= '</figure>';
        }

        $html .= '</div>';
        return $html;
    }
}