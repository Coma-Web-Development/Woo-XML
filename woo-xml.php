<?php
/**
 * Plugin Name: Woo XML
 * Description: Outputs XML of Woocommerce products for Salidzini.lv, KurPirkt.lv, Gudriem.lv.
 * Author: Coma Web Development
 * Author URI: https://coma.lv/?utm_source=woo-xml&utm_medium=link&utm_campaign=plugin-list-author
 * Version: 1.0.1
 * Text Domain: 'woo-xml'
 * Domain Path: languages
 * 
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * Helper function for quick debugging
 */
if (!function_exists('pp')) {
	function pp( $array ) {
		echo '<pre style="white-space:pre-wrap;">';
			print_r( $array );
		echo '</pre>' . "\n";
	}
}

/**
 * Main Class.
 *
 * @since 1.0.0
 */
final class Wooxml {

	/**
	 * @var The one true instance
	 * @since 1.0.0
	 */
	protected static $_instance = null;

	public $version = '1.0.1';

	/**
	 * Main Instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Throw error on object clone.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @return void
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woo-xml' ), '1.0.0' );
	}

	/**
	 * Disable unserializing of the class.
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woo-xml' ), '1.0.0' );
	}

	/**
	 * 
	 * @since 1.0.0
	 */
	public function __construct() {

		$this->define_constants();
		$this->includes();

		do_action( 'wooxml_loaded' );
	}

	/**
	 * Define Constants.
	 * @since  1.0.0
	 */
	private function define_constants() {
		$this->define( 'WOOXML_DIR',plugin_dir_path( __FILE__ ) );
		$this->define( 'WOOXML_URL',plugin_dir_url( __FILE__ ) );
		$this->define( 'WOOXML_BASENAME', plugin_basename( __FILE__ ) );
		$this->define( 'WOOXML_VERSION', $this->version );
	}

	/**
	 * Define constant if not already set.
	 * @since  1.0.0
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}


	/**
	 * Include required files.
	 * @since  1.0.0
	 */
	public function includes() {
		
		include_once ( WOOXML_DIR . 'includes/settings.php' );

		include_once ( WOOXML_DIR . 'frontend/enqueues.php' );
		
	}


}


/**
 * Run the plugin.
 */
function Wooxml() {
	return Wooxml::instance();
}
Wooxml();