<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * The main class
 *
 * @since 1.0.0
 */
class Wooxml_Settings {

    public $filename1 = 'woocommerce_products.xml';
    public $filename2 = 'woocommerce_products_lv.xml';
    public $folder = 'wooxml';

    /**
     * Main constructor
     *
     * @since 1.0.0
     *
     */
    public function __construct() {

        add_filter( 'upload_mimes', array( $this, 'wooxml_allow_upload_xml' ) );

        add_action( 'admin_init', array( $this, 'wooxml_settings_init' ) );
        add_action( 'admin_menu', array( $this, 'wooxml_options_page' ) );
        
        add_action( 'wp_ajax_wooxml_output_with_button', array( $this, 'wooxml_output_with_button' ) );

        add_action( 'init', array( $this, 'check_cron' ) );
        add_action( 'wooxml_twice_daily_cron', array( $this, 'wooxml_twice_daily_upload' ) );

    }

    /**
     * Ensure XML upload is available
     *
     * @since 1.0
     * @return void
     */
    public function wooxml_allow_upload_xml($mimes) {
        $mimes = array_merge($mimes, array('xml' => 'application/xml'));
        return $mimes;
    }

    /**
     * Check and run cron job
     *
     * @since 1.0
     * @return void
     */
    public function check_cron() {
        if ( ! wp_next_scheduled ( 'wooxml_twice_daily_cron' )) {
            wp_schedule_event( time(), 'twicedaily', 'wooxml_twice_daily_cron');
        }
    }
    
    public function wooxml_twice_daily_upload() {
        $this->wooxml_create_xml();
    }

 
    /**
     * Get the data
     *
     * @since 1.0
     * @return void
     */
    public function wooxml_output_with_button() {

        $this->do_ajax_checks();

        $data = $this->wooxml_create_xml();

        if( ! $data )
            die;

        // send our details to browser
        wp_send_json( array(
            'success' => true,
            'results' => $data,
        ) );

    }



    /**
     * Create the XML
     *
     * @since 1.0
     * @return void
     */
    public function wooxml_create_xml() {

        $data = $this->wooxml_format_products();

	$result = '';

	$result1 = $this->wooxml_create_xml_file($this->filename2, $data['lv']);
	$result .= 'XML LV: ';
	if ($result1['error'] != '') $result .= $result1['error'];
	if ($result1['url'] != '') $result .= $result1['url'];

	$result .= "<br>";

	$result2 = $this->wooxml_create_xml_file($this->filename1, $data['all']);
	$result .= 'XML ALL: ';
	if ($result2['error'] != '') $result .= $result2['error'];
	if ($result2['url'] != '') $result .= $result2['url'];

        return array('url' => $result);
    }

    private function wooxml_create_xml_file($filename, $data) {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><root/>');

        foreach ( $data as $i => $product ) {
            $item = $xml->addChild( 'item' );

            foreach ( $product as $key => $value ) {
                if( $value && $value != '' ) {
                    $item->addChild( $key, $value );
                }                
            }            
        }

        $file = $xml->asXML();
        $result = $this->upload_xml_file( $filename, $file );

        return $result;
    }


    /**
    * Upload file
    *
    * @since 1.0
    * @param string $filename the attachment filename
    * @param string $file the file to write
    * @return string $filename
    */
    private function upload_xml_file($filename, $file) {

        $deprecated = null;
        $time = current_time( 'mysql' );

        $upload_dir = wp_upload_dir();
        $base = $upload_dir['basedir'];
        $file_path = $base . '/' . $this->folder . '/' . $filename;

        $_filter = true; // For the anonymous filter callback below.
        add_filter( 'upload_dir', function( $arr ) use( &$_filter ){
            if ( $_filter ) {
                $arr['path'] = $arr['basedir'] . '/' . $this->folder;
                $arr['url'] = $arr['baseurl'] . '/' . $this->folder;
            }
            return $arr;
        } );

        // delete if already exists
        wp_delete_file( $file_path );

        $upload = wp_upload_bits( $filename, $deprecated, $file, $time );

        $_filter = false; // Disables the filter.

        return $upload;
    }


    /**
     * Get products
     *
     * @since 1.0
     * @return void
     */
    public function wooxml_get_products() {

        $products = get_posts( 
            array( 
                'post_type' => 'product',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'orderby' => 'meta_value title',
                'order' => 'ASC',
            ) 
        );

        return array( 
            'products' => $products,
            'count' => count( $products )
        );

    }


    /**
     * Format products and get the data we need
     *
     * @since 1.0
     * @return void
     */
    public function wooxml_format_products() {

        $products = $this->wooxml_get_products();

        if( ! $products['products'] )
            return;

        $data_all = array();
        $data_lv = array();

        foreach ( $products['products'] as $index => $post_id ) {

            $product = wc_get_product( $post_id );

            $product_cats = wc_get_product_cat_ids( $post_id );
            $product_cats = array_unique( $product_cats, SORT_REGULAR );
            
            $cat_names = strip_tags( get_the_term_list( $post_id, 'product_cat', '', ', ' ) );
            $brand_names = strip_tags( get_the_term_list( $post_id, 'product_brand', '', ', ' ) );

            $last_cat_id = end( $product_cats );
            $cat_name = '';
            if( $term = get_term_by( 'id', $last_cat_id, 'product_cat' ) ){
                $cat_name = $term->name;
            }
            $cat_link = get_term_link( $last_cat_id, 'product_cat' );

	    $data = array();

            $data['name'] = get_the_title( $post_id );
            $data['link'] = get_permalink( $post_id );
            $data['price'] = $product->get_price();
            $data['image'] = get_the_post_thumbnail_url( $post_id, 'full' );
            $data['manufacturer'] = $brand_names;
            $data['category_full'] = $cat_names;
            $data['category'] = $cat_name;
            $data['category_link'] = $cat_link;

	    $data_all[] = $data;

	    if (strpos($data['link'], '/ru/') === false) $data_lv[] = $data;

        }

        return array('lv' => $data_lv, 'all' => $data_all);

    }


    /**
     * custom option and settings
     */
    public function wooxml_settings_init() {
        // register a new setting for "wooxml" page
        register_setting( 'wooxml', 'wooxml_options' );

        // register a new section in the "wooxml" page
        add_settings_section( 'wooxml_settings_section', __( 'XML output of products', 'wooxml' ), array( $this, 'wooxml_section_cb' ), 'wooxml');

    }


    /**
     * custom option and settings:
     * callback functions
     */

    // section callbacks can accept an $args parameter, which is an array.
    // $args have the following keys defined: title, id, callback.
    // the values are defined at the add_settings_section() function.
    public function wooxml_section_cb($args) { ?>
        
        <div id="<?php echo esc_attr($args['id']); ?>">
            <?php 
            $products = $this->wooxml_get_products();
            $count = $products['count']; 
            ?>
            <p><?php esc_html_e( $count ); ?> <?php esc_html_e( 'Total Products.', 'wooxml' ); ?></p>
            <p><input type="button" id="xml_output" class="button button-primary" value="Output XML"></p>

        </div>

    <?php
    }


    /**
     * top level menu
     */
    public function wooxml_options_page() {
        // add top level menu page
        add_menu_page('Woo XML', 'Woo XML', 'manage_options', 'wooxml', array( $this, 'wooxml_options_page_html' ) );
    }



    /**
     * top level menu:
     * callback functions
     */
    public function wooxml_options_page_html() {

        // check user capabilities
        if ( ! current_user_can('manage_options') )
            return;

        // add error/update messages
        // check if the user have submitted the settings
        // wordpress will add the "settings-updated" $_GET parameter to the url
        if (isset($_GET['settings-updated'])) {
            // add settings saved message with the class of "updated"
            add_settings_error( 'wooxml_messages', 'wooxml_message', __('Settings Saved', 'wooxml') , 'updated');
        }

        // show error/update messages
        settings_errors( 'wooxml_messages' );
        
        ?>

        <div class="wrap">
        
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php
                
                // output setting sections and their fields
                // (sections are registered for "wooxml", each field is registered to a specific section)
                do_settings_sections( 'wooxml' );
                
                // output save settings button
                //submit_button( 'Save Settings' );

            ?>

        </div>

    <?php

    }


    /**
     * AJAX checks
     *
     * @since 1.0
     * @return void
     */
    public function do_ajax_checks() {
        // make sure we have data
        if ( ! isset( $_POST ) || empty( $_POST ) )
            wp_die( 'Nothing sent' ); 

        // verify our nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'nonce' ) )
            wp_die( 'Security check' ); 
    }

}

return new Wooxml_Settings();