<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * The main class
 *
 * @since 1.0.0
 */
class Wooxml_Settings {

    public string $filename = 'woocommerce_products.xml';
    public string $folder = 'wooxml';

    /**
     * Main constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_filter( 'upload_mimes', [ $this, 'wooxml_allow_upload_xml' ] );
        add_action( 'admin_init', [ $this, 'wooxml_settings_init' ] );
        add_action( 'admin_menu', [ $this, 'wooxml_options_page' ] );
        add_action( 'wp_ajax_wooxml_output_with_button', [ $this, 'wooxml_output_with_button' ] );
        add_action( 'init', [ $this, 'check_cron' ] );
        add_action( 'wooxml_twice_daily_cron', [ $this, 'wooxml_twice_daily_upload' ] );
    }

    /**
     * Allow XML upload
     */
    public function wooxml_allow_upload_xml(array $mimes): array {
        $mimes['xml'] = 'application/xml';
        return $mimes;
    }

    /**
     * Schedule cron job if not already scheduled
     */
    public function check_cron(): void {
        if ( ! wp_next_scheduled( 'wooxml_twice_daily_cron' ) ) {
            wp_schedule_event( time(), 'twicedaily', 'wooxml_twice_daily_cron' );
        }
    }
    
    public function wooxml_twice_daily_upload(): void {
        $this->wooxml_create_xml();
    }

    /**
     * Handle AJAX output with button
     */
    public function wooxml_output_with_button(): void {
        $this->do_ajax_checks();

        $data = $this->wooxml_create_xml();

        if ( empty( $data ) ) {
            wp_die();
        }

        wp_send_json( [
            'success' => true,
            'results' => $data,
        ] );
    }

    /**
     * Create the XML file
     */
    public function wooxml_create_xml(): ?string {
        $data = $this->wooxml_format_products();
        if ( empty( $data ) ) {
            return null;
        }

        $xml = new SimpleXMLElement('<root/>');

        foreach ( $data as $product ) {
            $item = $xml->addChild( 'item' );
            foreach ( $product as $key => $value ) {
                if ( ! empty( $value ) ) {
                    $item->addChild( $key, htmlspecialchars( (string) $value, ENT_XML1, 'UTF-8' ) );
                }
            }
        }

        $file = str_replace(
            '<?xml version="1.0"?>',
            '<?xml version="1.0" encoding="ISO-8859-1"?>',
            $xml->asXML()
        );
        
        return $this->upload_xml_file( $file );
    }

    private function upload_xml_file(string $file): array {
        $time = current_time( 'mysql' );
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['basedir'] . '/' . $this->folder . '/' . $this->filename;

        add_filter( 'upload_dir', function( $arr ) {
            $arr['path'] = $arr['basedir'] . '/' . $this->folder;
            $arr['url'] = $arr['baseurl'] . '/' . $this->folder;
            return $arr;
        });

        wp_delete_file( $file_path );
        return wp_upload_bits( $this->filename, null, $file, $time );
    }

    public function wooxml_get_products(): array {
        $products = get_posts( [
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
        ]);

        return [ 'products' => $products, 'count' => count( $products ) ];
    }

    public function wooxml_format_products(): ?array {
        $products = $this->wooxml_get_products()['products'];
        if ( empty( $products ) ) {
            return null;
        }

        $data = [];
        foreach ( $products as $index => $post_id ) {
            $product = wc_get_product( $post_id );
            $data[$index] = [
                'name'           => get_the_title( $post_id ),
                'link'           => get_permalink( $post_id ),
                'price'          => $product->get_price(),
                'image'          => get_the_post_thumbnail_url( $post_id, 'full' ),
                'manufacturer'   => strip_tags( get_the_term_list( $post_id, 'product_brand', '', ', ' ) ),
                'category'       => strip_tags( get_the_term_list( $post_id, 'product_cat', '', ', ' ) ),
            ];
        }
        return $data;
    }

    public function do_ajax_checks(): void {
        if ( empty( $_POST ) || ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ), 'nonce' ) ) {
            wp_die( 'Security check failed.' );
        }
    }
}

return new Wooxml_Settings();
