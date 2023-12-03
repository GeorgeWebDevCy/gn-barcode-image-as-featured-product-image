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
 * Description:   Find an image from a barcode and set it as the featured product image
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
if (!defined('ABSPATH')) exit;

// Plugin name
define('GNBARCODEI_NAME', 'GN Barcode Image As Featured Product Image');

// Plugin version
define('GNBARCODEI_VERSION', '1.0.0');

// Plugin Root File
define('GNBARCODEI_PLUGIN_FILE', __FILE__);

// Plugin base
define('GNBARCODEI_PLUGIN_BASE', plugin_basename(GNBARCODEI_PLUGIN_FILE));

// Plugin Folder Path
define('GNBARCODEI_PLUGIN_DIR', plugin_dir_path(GNBARCODEI_PLUGIN_FILE));

// Plugin Folder URL
define('GNBARCODEI_PLUGIN_URL', plugin_dir_url(GNBARCODEI_PLUGIN_FILE));

/**
 * Load the main class for the core functionality
 */
require_once GNBARCODEI_PLUGIN_DIR . 'core/class-gn-barcode-image-as-featured-product-image.php';

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

// Check if WooCommerce is installed before activating the plugin
function gn_barcode_image_as_featured_product_image_activate() {
    if (!class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('This plugin requires WooCommerce to be installed and active. Please install WooCommerce and try again.', 'gn-barcode-image-as-featured-product-image'));
    }
}
register_activation_hook(__FILE__, 'gn_barcode_image_as_featured_product_image_activate');

// Look up an image from https://www.barcodelookup.com/9780141033570 and set it as the featured image

// Write a function that looks up the image <div id="largeProductImage"><img src="https://images.barcodelookup.com/77916/779164202-1.jpg" alt="Vaggelis Konitopoulos - Aroma Aigaiou / Greek Folk Music CD 2002 NEW"></div>
// from lookup image from https://www.barcodelookup.com/9780141033570 and set it as the featured image
function gn_barcode_image_as_featured_product_image() {
    // Set the number of products to process at a time
    $products_per_batch = 20;

    // Get all products
    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => $products_per_batch,
    );
    $loop = new WP_Query($args);
    while ($loop->have_posts()) : $loop->the_post();
        // Check if the product already has a featured image
		gn_log_message_to_file('Processing product ' . get_the_ID());
        if (has_post_thumbnail(get_the_ID())) {
            continue; // Skip to the next product if a featured image is already set
        }

        // Get the barcode
        $barcode = get_post_meta(get_the_ID(), '_sku', true);
		gn_log_message_to_file('Barcode: ' . $barcode . ' for product ' . get_the_ID());
        // Get the image
        $barcode_image = @file_get_contents('https://www.barcodelookup.com/' . $barcode);
		gn_log_message_to_file('Barcode image: ' . $barcode_image .'for product ' . get_the_ID());
        // Check if the image was retrieved successfully
        if ($barcode_image === false || empty($barcode_image)) {
			gn_log_message_to_file('Barcode image not found for product ' . get_the_ID() . ' with barcode ' . $barcode);
            continue; // Skip to the next product if the image is not available
        }

        // Get the image URL
        $barcode_image_url = explode('<div id="largeProductImage"><img src="', $barcode_image);
		gn_log_message_to_file('Barcode image URL: ' . $barcode_image_url[1]).' for product ' . get_the_ID();
        $barcode_image_url = explode('" alt="', $barcode_image_url[1]);
        $barcode_image_url = $barcode_image_url[0];

        // Set the image as the featured image
        $upload_dir = wp_upload_dir();
		gn_log_message_to_file('Upload dir: ' . $upload_dir);
        $image_data = file_get_contents($barcode_image_url);
		gn_log_message_to_file('Image data: ' . $image_data). ' for product ' . get_the_ID();
        $filename   = basename($barcode_image_url);
		gn_log_message_to_file('Filename: ' . $filename);
        if (wp_mkdir_p($upload_dir['path'])) $file = $upload_dir['path'] . '/' . $filename;
        else $file = $upload_dir['basedir'] . '/' . $filename;
        file_put_contents($file, $image_data);
        $wp_filetype = wp_check_filetype($filename, null);
        $attachment  = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_title'     => sanitize_file_name($filename),
            'post_content'   => '',
            'post_status'    => 'inherit',
        );
        $attach_id    = wp_insert_attachment($attachment, $file, get_the_ID());
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $file);
        wp_update_attachment_metadata($attach_id, $attach_data);
        set_post_thumbnail(get_the_ID(), $attach_id);
		log_message_to_file('Set featured image for product ' . get_the_ID() . ' with barcode ' . $barcode) . 'to ' . $barcode_image_url;
    endwhile;
    wp_reset_query();
}

// Add custom interval for every 5 minutes
function gn_add_five_minute_interval($schedules) {
    $schedules['5minutes'] = array(
        'interval' => 300,
        'display'  => __('Every 5 Minutes'),
    );
    return $schedules;
}
add_filter('cron_schedules', 'gn_add_five_minute_interval');

// Run the function every 5 minutes
add_action('gn_barcode_image_as_featured_product_image', 'gn_barcode_image_as_featured_product_image');
if (!wp_next_scheduled('gn_barcode_image_as_featured_product_image')) {
    wp_schedule_event(time(), '5minutes', 'gn_barcode_image_as_featured_product_image');
}

// Activation hook to ensure the scheduled event is set
function gn_barcode_image_as_featured_product_image_activation() {
    if (!wp_next_scheduled('gn_barcode_image_as_featured_product_image')) {
        wp_schedule_event(time(), '5minutes', 'gn_barcode_image_as_featured_product_image');
    }
}

function gn_log_message_to_file($message) {
	$log_file = fopen(GNBARCODEI_PLUGIN_DIR . 'log.txt', 'a');
	//message with timestamp
	fwrite($log_file, date('Y-m-d H:i:s') . ' ' . $message . "\n");
	fclose($log_file);
}

//add a log viewer to the admin menu
function gn_barcode_image_as_featured_product_image_admin_menu() {
	add_menu_page('GN Barcode Image As Featured Product Image Log', 'GN Barcode Image As Featured Product Image Log', 'manage_options', 'gn_barcode_image_as_featured_product_image_log', 'gn_barcode_image_as_featured_product_image_log_viewer');
}
add_action('admin_menu', 'gn_barcode_image_as_featured_product_image_admin_menu');
 //log viewer
function gn_barcode_image_as_featured_product_image_log_viewer() {
	$log_file = fopen(GNBARCODEI_PLUGIN_DIR . 'log.txt', 'r');
	//read the log file
	$log_file_contents = fread($log_file, filesize(GNBARCODEI_PLUGIN_DIR . 'log.txt'));
	fclose($log_file);
	//display the log file contents
	echo '<pre>' . $log_file_contents . '</pre>';
}

//show the log file contents on the plugin page
function gn_barcode_image_as_featured_product_image_plugin_page() {
	$log_file = fopen(GNBARCODEI_PLUGIN_DIR . 'log.txt', 'r');
	//read the log file
	$log_file_contents = fread($log_file, filesize(GNBARCODEI_PLUGIN_DIR . 'log.txt'));
	fclose($log_file);
	//display the log file contents
	echo '<pre>' . $log_file_contents . '</pre>';
}
add_action('admin_notices', 'gn_barcode_image_as_featured_product_image_plugin_page');


GNBARCODEI();