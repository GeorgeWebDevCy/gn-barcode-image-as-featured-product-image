<?php
/**
 * GN Barcode Image As Featured Product Image
 *
 * @package       GNBARCODEI
 * @author        George Nicolaou
 * @license       gplv2
 * @version       1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:   GN Barcode Image As Featured Product Image
 * Plugin URI:    https://www.georgenicolaou.me/plugins/gn-barcode-image-as-featured-product-image
 * Description:   Find image from barcode and set featured product image
 * Version:       1.0.0
 * Author:        George Nicolaou
 * Author URI:    https://www.georgenicolaou.me/
 * Text Domain:   gn-barcode-image-as-featured-product-image
 * Domain Path:   /languages
 * License:       GPLv2
 * License URI:   https://www.gnu.org/licenses/gpl-2.0.html
 *
 * You should have received a copy of the GNU General Public License
 * along with GN Barcode Image As Featured Product Image. If not, see <https://www.gnu.org/licenses/gpl-2.0.html/>.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;
// Plugin name
define( 'GNBARCODEI_NAME',			'GN Barcode Image As Featured Product Image' );

// Plugin version
define( 'GNBARCODEI_VERSION',		'1.0.0' );

// Plugin Root File
define( 'GNBARCODEI_PLUGIN_FILE',	__FILE__ );

// Plugin base
define( 'GNBARCODEI_PLUGIN_BASE',	plugin_basename( GNBARCODEI_PLUGIN_FILE ) );

// Plugin Folder Path
define( 'GNBARCODEI_PLUGIN_DIR',	plugin_dir_path( GNBARCODEI_PLUGIN_FILE ) );

// Plugin Folder URL
define( 'GNBARCODEI_PLUGIN_URL',	plugin_dir_url( GNBARCODEI_PLUGIN_FILE ) );

/**
 * Load the main class for the core functionality
 */
require_once GNBARCODEI_PLUGIN_DIR . 'core/class-gn-barcode-image-as-featured-product-image.php';

/* Github plugin updater code */
require 'plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/GeorgeWebDevCy/gn-barcode-image-as-featured-product-image',
    __FILE__,
    'gn-barcode-image-as-featured-product-image'
);
$myUpdateChecker->setBranch('main');

// Check if WooCommerce is active
function gn_barcode_image_as_featured_product_image_check_for_woocommerce() {
    if (!class_exists('woocommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('Sorry, but this plugin requires WooCommerce to be installed and active. Please install WooCommerce and try again.');
    }
}
register_activation_hook(__FILE__, 'gn_product_image_remover_check_for_woocommerce');


//autoload the composer packages
require_once GNBARCODEI_PLUGIN_DIR . 'vendor/autoload.php';





//add a settings page to admin for the plugin settings
function gn_barcode_image_as_featured_product_image_settings_page() {
    add_menu_page(
        'GN Barcode Image As Featured Product Image',
        'GN Barcode Image',
        'manage_options',
        'gn-barcode-image-as-featured-product-image',
        'gn_barcode_image_as_featured_product_image_settings_page_html',
        'dashicons-images-alt2', // Change this to the desired menu icon (optional)
        30 // Change this to set the position of the menu item
    );
}
add_action( 'admin_menu', 'gn_barcode_image_as_featured_product_image_settings_page' );



function gn_barcode_image_as_featured_product_image_settings_page_html() {
	// check user capabilities
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<form action="options.php" method="post">
	<?php
	// output security fields for the registered setting "gn_barcode_image_as_featured_product_image"
	settings_fields( 'gn_barcode_image_as_featured_product_image' );
	// output setting sections and their fields
	// (sections are registered for "gn_barcode_image_as_featured_product_image", each field is registered to a specific section)
	do_settings_sections( 'gn_barcode_image_as_featured_product_image' );
	// output save settings button
	submit_button( 'Save Settings' );
	?>
	</form>
	</div>
	<?php
}

/*add the settings page html for the following options Consumer Key	wlpINNZHOoNpPWaUFDgV
Consumer Secret	uYVRwbUNNwFkTutbkLdVoSCnBWiuvPcg
Request Token URL	https://api.discogs.com/oauth/request_token
Authorize URL	https://www.discogs.com/oauth/authorize
Access Token URL	https://api.discogs.com/oauth/access_token
*/	

// register our settings
function gn_barcode_image_as_featured_product_image_register_settings() {
	register_setting( 'gn_barcode_image_as_featured_product_image', 'gn_barcode_image_as_featured_product_image' );
	add_settings_section( 'gn_barcode_image_as_featured_product_image_settings', 'GN Barcode Image As Featured Product Image Settings', 'gn_barcode_image_as_featured_product_image_settings_section_callback', 'gn_barcode_image_as_featured_product_image' );
	add_settings_field( 'gn_barcode_image_as_featured_product_image_consumer_key', 'Consumer Key', 'gn_barcode_image_as_featured_product_image_consumer_key_callback', 'gn_barcode_image_as_featured_product_image', 'gn_barcode_image_as_featured_product_image_settings' );
	add_settings_field( 'gn_barcode_image_as_featured_product_image_consumer_secret', 'Consumer Secret', 'gn_barcode_image_as_featured_product_image_consumer_secret_callback', 'gn_barcode_image_as_featured_product_image', 'gn_barcode_image_as_featured_product_image_settings' );
	add_settings_field( 'gn_barcode_image_as_featured_product_image_request_token_url', 'Request Token URL', 'gn_barcode_image_as_featured_product_image_request_token_url_callback', 'gn_barcode_image_as_featured_product_image', 'gn_barcode_image_as_featured_product_image_settings' );
	add_settings_field( 'gn_barcode_image_as_featured_product_image_authorize_url', 'Authorize URL', 'gn_barcode_image_as_featured_product_image_authorize_url_callback', 'gn_barcode_image_as_featured_product_image', 'gn_barcode_image_as_featured_product_image_settings' );
	add_settings_field( 'gn_barcode_image_as_featured_product_image_access_token_url', 'Access Token URL', 'gn_barcode_image_as_featured_product_image_access_token_url_callback', 'gn_barcode_image_as_featured_product_image', 'gn_barcode_image_as_featured_product_image_settings' );
}
$options = get_option( 'gn_barcode_image_as_featured_product_image' );
if ( ! empty( $options ) ) {
	$gn_barcode_image_as_featured_product_image_consumer_key = $options['gn_barcode_image_as_featured_product_image_consumer_key'];
	$gn_barcode_image_as_featured_product_image_consumer_secret = $options['gn_barcode_image_as_featured_product_image_consumer_secret'];
	$gn_barcode_image_as_featured_product_image_request_token_url = $options['gn_barcode_image_as_featured_product_image_request_token_url'];
	$gn_barcode_image_as_featured_product_image_authorize_url = $options['gn_barcode_image_as_featured_product_image_authorize_url'];
	$gn_barcode_image_as_featured_product_image_access_token_url = $options['gn_barcode_image_as_featured_product_image_access_token_url'];
}

//set the default values for the settings
if ( empty( $options ) ) {
	$gn_barcode_image_as_featured_product_image_consumer_key = 'wlpINNZHOoNpPWaUFDgV';
	$gn_barcode_image_as_featured_product_image_consumer_secret = 'uYVRwbUNNwFkTutbkLdVoSCnBWiuvPcg';
	$gn_barcode_image_as_featured_product_image_request_token_url = 'https://api.discogs.com/oauth/request_token';
	$gn_barcode_image_as_featured_product_image_authorize_url = 'https://www.discogs.com/oauth/authorize';
	$gn_barcode_image_as_featured_product_image_access_token_url = 'https://api.discogs.com/oauth/access_token';
}


add_action( 'admin_init', 'gn_barcode_image_as_featured_product_image_register_settings' );
// callback functions
function gn_barcode_image_as_featured_product_image_settings_section_callback() {
	echo '<p>Enter your settings below:</p>';
}
function gn_barcode_image_as_featured_product_image_consumer_key_callback() {
	$options = get_option( 'gn_barcode_image_as_featured_product_image' );
	echo "<input type='text' name='gn_barcode_image_as_featured_product_image[gn_barcode_image_as_featured_product_image_consumer_key]' value='" . $options['gn_barcode_image_as_featured_product_image_consumer_key'] . "' />";
}
function gn_barcode_image_as_featured_product_image_consumer_secret_callback() {
	$options = get_option( 'gn_barcode_image_as_featured_product_image' );
	echo "<input type='text' name='gn_barcode_image_as_featured_product_image[gn_barcode_image_as_featured_product_image_consumer_secret]' value='" . $options['gn_barcode_image_as_featured_product_image_consumer_secret'] . "' />";
}
function gn_barcode_image_as_featured_product_image_request_token_url_callback() {
	$options = get_option( 'gn_barcode_image_as_featured_product_image' );
	echo "<input type='text' name='gn_barcode_image_as_featured_product_image[gn_barcode_image_as_featured_product_image_request_token_url]' value='" . $options['gn_barcode_image_as_featured_product_image_request_token_url'] . "' />";
}
function gn_barcode_image_as_featured_product_image_authorize_url_callback() {
	$options = get_option( 'gn_barcode_image_as_featured_product_image' );
	echo "<input type='text' name='gn_barcode_image_as_featured_product_image[gn_barcode_image_as_featured_product_image_authorize_url]' value='" . $options['gn_barcode_image_as_featured_product_image_authorize_url'] . "' />";
}
function gn_barcode_image_as_featured_product_image_access_token_url_callback() {
	$options = get_option( 'gn_barcode_image_as_featured_product_image' );
	echo "<input type='text' name='gn_barcode_image_as_featured_product_image[gn_barcode_image_as_featured_product_image_access_token_url]' value='" . $options['gn_barcode_image_as_featured_product_image_access_token_url'] . "' />";
}

//add the settings link to the plugins page
function gn_barcode_image_as_featured_product_image_settings_link( $links ) {
	$settings_link = '<a href="admin.php?page=gn-barcode-image-as-featured-product-image">' . __( 'Settings' ) . '</a>';
	array_push( $links, $settings_link );
	return $links;
}
$plugin = plugin_basename( __FILE__ );
add_filter( "plugin_action_links_$plugin", 'gn_barcode_image_as_featured_product_image_settings_link' );




/**
 * The main function to load the only instance
 * of our master class.
 *
 * @author  George Nicolaou
 * @since   1.0.0
 * @return  object|Gn_Barcode_Image_As_Featured_Product_Image
 */
function GNBARCODEI() {
	return Gn_Barcode_Image_As_Featured_Product_Image::instance();
}

GNBARCODEI();
