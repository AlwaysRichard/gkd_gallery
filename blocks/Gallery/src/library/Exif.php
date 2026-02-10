<?php

namespace gkd\Library;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Exif {

    /**
     * Build caption from EXIF metadata template with conditional text support.
     */
    public static function build_caption( int $att_id, string $template ) : string {

        $meta = wp_get_attachment_metadata( $att_id );
        if ( empty( $meta ) || empty( $meta['image_meta'] ) ) {
            return '';
        }

        $exif = $meta['image_meta'];
        $file_path = get_attached_file( $att_id );

        // Raw EXIF
        $exif_data = [];
        if ( function_exists( 'exif_read_data' ) && file_exists( $file_path ) ) {
            $raw = @exif_read_data( $file_path );
            if ( $raw ) {
                $exif_data = $raw;
            }
        }

        // Base replacements
        $repl = [];
        $repl['{FileName}'] = basename( $file_path );

        // Copyright
        $copyright =
            $exif['copyright'] ??
            $exif_data['Copyright'] ??
            '';

        $repl['{Copyright}'] = $copyright;

        // Handle {Copyright,default}
        if ( preg_match( '/\{Copyright,([^}]+)\}/', $template, $m ) ) {
            $default = trim( $m[1] );
            $template = str_replace( $m[0], $copyright ?: $default, $template );
        }

        // Camera Make
        $repl['{CameraMake}'] =
            $exif['camera'] ??
            $exif_data['Make'] ??
            '';

        // Camera Model
        $repl['{CameraModel}'] =
            $exif_data['Model'] ??
            '';

        // ISO
        $iso = $exif_data['ISOSpeedRatings'] ?? '';
        $repl['{ISOSpeedRatings}'] = $iso ? 'ISO-' . $iso : '';

        // Date/Time
        $repl['{DateTimeOriginal}'] =
            ( ! empty( $exif['created_timestamp'] )
                ? date( 'Y-m-d H:i:s', $exif['created_timestamp'] )
                : ( $exif_data['DateTimeOriginal'] ?? '' )
            );

        // Focal Length
        $focal =
            $exif['focal_length'] ??
            $exif_data['FocalLength'] ??
            '';

        if ( $focal && strpos( $focal, '/' ) !== false ) {
            [ $n, $d ] = explode( '/', $focal );
            if ( $d != 0 ) {
                $focal = round( $n / $d ) . 'mm';
            }
        } elseif ( is_numeric( $focal ) ) {
            $focal = round( $focal ) . 'mm';
        }

        $repl['{FocalLength}'] = $focal;

        // Shutter Speed
        $shutter =
            $exif['shutter_speed'] ??
            $exif_data['ExposureTime'] ??
            '';

        if ( $shutter && strpos( $shutter, '/' ) !== false ) {
            [ $n, $d ] = explode( '/', $shutter );
            if ( $n != 0 ) {
                $decimal = $d / $n;
                $shutter = $decimal >= 1
                    ? '1/' . round( $decimal ) . 's'
                    : round( 1 / $decimal, 1 ) . 's';
            }
        } elseif ( is_numeric( $shutter ) && $shutter > 0 ) {
            $shutter = $shutter >= 1
                ? round( $shutter, 1 ) . 's'
                : '1/' . round( 1 / $shutter ) . 's';
        } else {
            $shutter = '';
        }

        $repl['{ShutterSpeedValue}'] = $shutter;

        // Aperture
        $ap =
            $exif['aperture'] ??
            $exif_data['FNumber'] ??
            '';

        if ( $ap && strpos( $ap, '/' ) !== false ) {
            [ $n, $d ] = explode( '/', $ap );
            if ( $d != 0 ) {
                $ap = 'f/' . round( $n / $d, 1 );
            }
        } elseif ( is_numeric( $ap ) ) {
            $ap = 'f/' . $ap;
        }

        $repl['{FNumber}'] = $ap;

        // Conditional text: {'text', Placeholder}
        $template = preg_replace_callback(
            '/\{([\'"])(.*?)\1\s*,\s*(\w+)\}/',
            function( $m ) use ( $repl ) {
                $text = $m[2];
                $ph   = '{' . $m[3] . '}';
                return ( ! empty( $repl[$ph] ) )
                    ? $text . $repl[$ph]
                    : '';
            },
            $template
        );

        // Standard replacements
        $caption = str_replace( array_keys( $repl ), array_values( $repl ), $template );

        // Cleanup
        $caption = preg_replace( '/\{[^}]+\}/', '', $caption );
        $caption = preg_replace( '/\s*\|\s*\|/', ' |', $caption );
        $caption = preg_replace( '/^\s*\|\s*/', '', $caption );
        $caption = preg_replace( '/\s*\|\s*$/', '', $caption );

        return trim( $caption );
    }
}