<?php

/**
 * Leaky Paywall Pro plugin to add audio/video restriction functionality, remove monthly payment and make compatibiltiy with Leaky Paywall with Erphpdown
 *
 * @package wpwebmaster0313
 * @since 1.0.0
 */

/*
Plugin Name: Leaky Paywall Pro
Plugin URI: https://zeen101.com/
Description: Leay Paywall Wall pro plugin to add audio/video restriction, remove monthly payment and make compatibiltiy with Leaky Paywall with Erphpdown
Author: Olek S.
Version: 1.0
Author URI: https://bllue-portfolio.000webhostapp.com/
Tags: paywall, restriction, audio, video, taxonomy, payment, Erphpdown
Text Domain: leaky-paywall-pro
*/

class Leaky_Paywall_Pro_Olek {

    /**
	 * Constructor
	 *
	 * @since 1.0
	 *
	*/
    public function __construct() {
        add_action( 'init', array( $this, 'setup' ) );

        add_action( 'wp', array( $this, 'init' ) );
        add_filter( 'the_content', array( $this, 'get_restricted_medias' ), 9999 );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ), 20 );

        require_once dirname( __FILE__ ) . '/includes/functions.php';
    }

    public function setup() {
        $args = array(
			'hierarchical'      => true,
			'show_ui'           => true,
			'show_in_nav_menus' => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'attachment_cat' ),
			'label'             => __( 'Media Caption', 'leaky-paywall-pro' ),
			'show_in_rest'      => true,
		);
		register_taxonomy( 'attachment_cat', 'attachment', $args );
    }

    public function init() {
        if ( is_page() ) {
            global $restricted_medias;

            if ( empty( $restricted_medias ) ) {
                $restricted_medias = array();
            }
            $page_content = get_the_content();
            global $wpdb;

            $restricted_terms = $this->get_restricted_settings();

            $media_shortcode_list = array( 'sc_embed_player' => 'fileurl' );
            foreach ( $media_shortcode_list as $sc => $param_name ) {
                preg_match_all( '/\[' . $sc . ' ' . $param_name . '="([^"]*)"[^]]*\]/is', $page_content, $matches );
                if ( ! empty( $matches ) && ! empty( $matches[1] ) && ! empty( $matches[1][0] ) ) {
                    $file_url = $matches[1][0];
                    $file_url = preg_replace( '/(.*)\/wp-content\/uploads(.*)/is', '$2', $file_url );
                    $media_id = $wpdb->get_var( $wpdb->prepare( 'SELECT ID FROM ' . $wpdb->posts . ' WHERE post_type="attachment" AND guid LIKE %s', '%' . $file_url ) );
                    if ( $media_id ) {
                        $captions = wp_get_post_terms( $media_id, 'attachment_cat', array( 'fields' => 'ids' ) );
                        foreach ( $captions as $term_id ) {
                            if ( isset( $restricted_terms[ $term_id ] ) ) {
                                $restricted_medias[] = array(
                                    'url' => $file_url,
                                    'allowed_val' => $restricted_terms[ $term_id ]
                                );
                                break;
                            }
                        }
                    }
                }
            }
        }
    }

    public function get_restricted_medias( $content ) {
        global $restricted_medias;
        if ( empty( $restricted_medias ) ) {
            $restricted_medias = array();
        }
        global $wpdb;

        $restricted_terms = $this->get_restricted_settings();
        $media_shortcode_list = array( 'video', 'audio' );
        foreach ( $media_shortcode_list as $sc ) {
            preg_match_all( '/<' . $sc . '[^>]*src="([^"]*)"[^>]*>/is', $content, $matches );
            if ( ! empty( $matches ) && ! empty( $matches[1] ) && ! empty( $matches[1][0] ) ) {
                $file_url = $matches[1][0];
                $file_url = preg_replace( '/(.*)\/wp-content\/uploads(.*)/is', '$2', $file_url );
                $media_id = $wpdb->get_var( $wpdb->prepare( 'SELECT ID FROM ' . $wpdb->posts . ' WHERE post_type="attachment" AND guid LIKE %s', '%' . $file_url ) );
                
                if ( $media_id ) {
                    $captions = wp_get_post_terms( $media_id, 'attachment_cat', array( 'fields' => 'ids' ) );
                    foreach ( $captions as $term_id ) {
                        if ( isset( $restricted_terms[ $term_id ] ) ) {
                            $restricted_medias[] = array(
                                'url' => $file_url,
                                'allowed_val' => $restricted_terms[ $term_id ]
                            );
                            break;
                        }
                    }
                }
            }
        }

        $leaky_settings = get_option( 'issuem-leaky-paywall' );
        wp_localize_script(
            'olek_leaky_script',
            'olek_vars',
            array(
                'restricted_medias' => $restricted_medias,
                'registration_url'  => ! empty( $leaky_settings['page_for_register'] ) ? esc_url( get_permalink( $leaky_settings['page_for_register'] ) ) : site_url(),
            )
        );
        return $content;
    }

    private function get_restricted_settings() {
        $settings = get_option( 'issuem-leaky-paywall' );
        $result   = array();
        if ( ! empty( $settings ) && ! empty( $settings['restrictions']['post_types'] ) ) {
            foreach ( $settings['restrictions']['post_types'] as $s ) {
                if ( 'attachment' == $s['post_type'] ) {
                    $result[ $s['taxonomy'] ] = $s['allowed_value'];
                }
            }
        }
        return $result;
    }

    public function enqueue() {
        wp_enqueue_script( 'olek_leaky_script', plugin_dir_url(__FILE__) . '/assets/script.js', array('jquery'), '1.0', true );
    }
}

new Leaky_Paywall_Pro_Olek;
