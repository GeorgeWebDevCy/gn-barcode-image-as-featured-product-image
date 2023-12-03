<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Gn_Barcode_Image_As_Featured_Product_Image_Settings
 *
 * This class contains all of the plugin settings.
 * Here you can configure the whole plugin data.
 *
 * @package		GNBARCODEI
 * @subpackage	Classes/Gn_Barcode_Image_As_Featured_Product_Image_Settings
 * @author		George Nicolaou
 * @since		1.0.0
 */
class Gn_Barcode_Image_As_Featured_Product_Image_Settings{

	/**
	 * The plugin name
	 *
	 * @var		string
	 * @since   1.0.0
	 */
	private $plugin_name;

	/**
	 * Our Gn_Barcode_Image_As_Featured_Product_Image_Settings constructor 
	 * to run the plugin logic.
	 *
	 * @since 1.0.0
	 */
	function __construct(){

		$this->plugin_name = GNBARCODEI_NAME;
	}

	/**
	 * ######################
	 * ###
	 * #### CALLABLE FUNCTIONS
	 * ###
	 * ######################
	 */

	/**
	 * Return the plugin name
	 *
	 * @access	public
	 * @since	1.0.0
	 * @return	string The plugin name
	 */
	public function get_plugin_name(){
		return apply_filters( 'GNBARCODEI/settings/get_plugin_name', $this->plugin_name );
	}
}
