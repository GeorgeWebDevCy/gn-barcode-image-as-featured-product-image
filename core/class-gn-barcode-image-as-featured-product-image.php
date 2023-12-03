<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'Gn_Barcode_Image_As_Featured_Product_Image' ) ) :

	/**
	 * Main Gn_Barcode_Image_As_Featured_Product_Image Class.
	 *
	 * @package		GNBARCODEI
	 * @subpackage	Classes/Gn_Barcode_Image_As_Featured_Product_Image
	 * @since		1.0.0
	 * @author		George Nicolaou
	 */
	final class Gn_Barcode_Image_As_Featured_Product_Image {

		/**
		 * The real instance
		 *
		 * @access	private
		 * @since	1.0.0
		 * @var		object|Gn_Barcode_Image_As_Featured_Product_Image
		 */
		private static $instance;

		/**
		 * GNBARCODEI helpers object.
		 *
		 * @access	public
		 * @since	1.0.0
		 * @var		object|Gn_Barcode_Image_As_Featured_Product_Image_Helpers
		 */
		public $helpers;

		/**
		 * GNBARCODEI settings object.
		 *
		 * @access	public
		 * @since	1.0.0
		 * @var		object|Gn_Barcode_Image_As_Featured_Product_Image_Settings
		 */
		public $settings;

		/**
		 * Throw error on object clone.
		 *
		 * Cloning instances of the class is forbidden.
		 *
		 * @access	public
		 * @since	1.0.0
		 * @return	void
		 */
		public function __clone() {
			_doing_it_wrong( __FUNCTION__, __( 'You are not allowed to clone this class.', 'gn-barcode-image-as-featured-product-image' ), '1.0.0' );
		}

		/**
		 * Disable unserializing of the class.
		 *
		 * @access	public
		 * @since	1.0.0
		 * @return	void
		 */
		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, __( 'You are not allowed to unserialize this class.', 'gn-barcode-image-as-featured-product-image' ), '1.0.0' );
		}

		/**
		 * Main Gn_Barcode_Image_As_Featured_Product_Image Instance.
		 *
		 * Insures that only one instance of Gn_Barcode_Image_As_Featured_Product_Image exists in memory at any one
		 * time. Also prevents needing to define globals all over the place.
		 *
		 * @access		public
		 * @since		1.0.0
		 * @static
		 * @return		object|Gn_Barcode_Image_As_Featured_Product_Image	The one true Gn_Barcode_Image_As_Featured_Product_Image
		 */
		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Gn_Barcode_Image_As_Featured_Product_Image ) ) {
				self::$instance					= new Gn_Barcode_Image_As_Featured_Product_Image;
				self::$instance->base_hooks();
				self::$instance->includes();
				self::$instance->helpers		= new Gn_Barcode_Image_As_Featured_Product_Image_Helpers();
				self::$instance->settings		= new Gn_Barcode_Image_As_Featured_Product_Image_Settings();

				//Fire the plugin logic
				new Gn_Barcode_Image_As_Featured_Product_Image_Run();

				/**
				 * Fire a custom action to allow dependencies
				 * after the successful plugin setup
				 */
				do_action( 'GNBARCODEI/plugin_loaded' );
			}

			return self::$instance;
		}

		/**
		 * Include required files.
		 *
		 * @access  private
		 * @since   1.0.0
		 * @return  void
		 */
		private function includes() {
			require_once GNBARCODEI_PLUGIN_DIR . 'core/includes/classes/class-gn-barcode-image-as-featured-product-image-helpers.php';
			require_once GNBARCODEI_PLUGIN_DIR . 'core/includes/classes/class-gn-barcode-image-as-featured-product-image-settings.php';

			require_once GNBARCODEI_PLUGIN_DIR . 'core/includes/classes/class-gn-barcode-image-as-featured-product-image-run.php';
		}

		/**
		 * Add base hooks for the core functionality
		 *
		 * @access  private
		 * @since   1.0.0
		 * @return  void
		 */
		private function base_hooks() {
			add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );
		}

		/**
		 * Loads the plugin language files.
		 *
		 * @access  public
		 * @since   1.0.0
		 * @return  void
		 */
		public function load_textdomain() {
			load_plugin_textdomain( 'gn-barcode-image-as-featured-product-image', FALSE, dirname( plugin_basename( GNBARCODEI_PLUGIN_FILE ) ) . '/languages/' );
		}

	}

endif; // End if class_exists check.