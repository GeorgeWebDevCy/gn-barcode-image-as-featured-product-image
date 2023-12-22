<?php
/**
 * GN Barcode Image As Featured Product Image
 *
 * @package       GNBARCODEI
 * @author        George Nicolaou
 * @license       gplv2
 * @version       1.1.1
 *
 * @wordpress-plugin
 * Plugin Name:   GN Barcode Image As Featured Product Image
 * Plugin URI:    https://www.georgenicolaou.me/plugins/gn-barcode-image-as-featured-product-image
 * Description:   Find an image from a barcode and set it as the featured product image
 * Version:       1.1.1
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
define('GNBARCODEI_VERSION', '1.1.1');

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
require 'plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
	'https://github.com/GeorgeWebDevCy/gn-barcode-image-as-featured-product-image',
	__FILE__,
	'gn-barcode-image-as-featured-product-image'
);
//Set the branch that contains the stable release.
$myUpdateChecker->setBranch('main');

/**
 * The main function to load the only instance
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

// Look up an image from https://www.discogs.com/search?q=9780141033570&type=all and set it as the featured image

function gn_barcode_image_as_featured_product_image() {
    // Set the number of products to process at a time
    $products_per_batch = -1;

    // Get all products
    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => $products_per_batch,
    );
    $loop = new WP_Query($args);
    gn_log_message_to_file('Before the while loop');
    while ($loop->have_posts()) : $loop->the_post();
        // Check if the product already has a featured image
        gn_log_message_to_file('Processing product ' . get_the_ID());
        if (has_post_thumbnail(get_the_ID())) {
            gn_log_message_to_file('Product ' . get_the_ID() . ' already has a featured image. Skipping product.');
            set_post_meta(get_the_ID(), 'processed_id', '1');
            continue; // Skip to the next product if a featured image is already set

        }

        // Get the barcode
        $barcode = get_post_meta(get_the_ID(), '_sku', true);
        gn_log_message_to_file('Barcode: ' . $barcode . ' for product ' . get_the_ID());

        // Use WordPress HTTP API to get the HTML content
        gn_log_message_to_file('URL being used is ' . 'https://www.discogs.com/search?q=' . $barcode . '&type=all');
        $barcode_html = gn_get_html_content('https://www.discogs.com/search?q=' . $barcode, $barcode, get_the_ID());

        //if the barcode is not found in discogs, try using the product title instead of the barcode to search for the image
        if ($barcode_html === false || empty($barcode_html)) {
            gn_log_message_to_file('Barcode not found in Discogs for product ' . get_the_ID() . ' with barcode ' . $barcode . '. Trying to use the product title instead.');
            $barcode_html = gn_get_html_content('https://www.discogs.com/search?q=' . get_the_title(), $barcode, get_the_ID());
        }

        //if both barcode search and image search by title fail, try then skipm the product
        if ($barcode_html === false || empty($barcode_html)) {
            gn_log_message_to_file('Barcode not found in Discogs for product ' . get_the_ID() . ' with barcode ' . $barcode . '. Image search by title also failed. Skipping product.');
            continue;
        }

        // Extract the image URL
        $image_url = extract_image_url($barcode_html, get_the_ID());
        gn_log_message_to_file('Image URL from extract_image_url before if : ' . $image_url . ' for product ' . get_the_ID());
        // Set the image as the featured image
        if ($image_url !== false) {
            set_featured_image($image_url, get_the_ID(), $barcode);
        }
    endwhile;
    gn_log_message_to_file('After the while loop');
    wp_reset_postdata();
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


function gn_get_html_content($url, $barcode, $product_id) {
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3');

    $html_content = curl_exec($ch);
    $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Log the cURL request details
    gn_log_message_to_file('cURL Request for product ' . $product_id . ' with barcode ' . $barcode . ' to URL: ' . $url);
    gn_log_message_to_file('cURL Response Code: ' . $response_code);

    if ($response_code !== 200) {
        // Log error with response code
        gn_log_message_to_file('Error retrieving HTML content for product ' . $product_id . ' with barcode ' . $barcode . '. Response Code: ' . $response_code);
        return false;
    }

    // Extract the image URL directly
    $image_url = extractImageURL($html_content, $product_id);
    set_featured_image($image_url, $product_id, $barcode);

    if ($image_url === false) {
        // Log error
        gn_log_message_to_file('Image URL not found for product ' . $product_id . ' with barcode ' . $barcode);
        return false;
    }

    // Log extracted image URL for debugging
    gn_log_message_to_file('Barcode image URL from gn_get_html_content: ' . $image_url . ' for product ' . $product_id);

    return $image_url;
}


/**
 * Extract image URL from HTML content
 *
 * @param string $html
 * @param int $product_id
 * @return string|false
 */
function extractImageURL($html, $product_id) {
    $dom = new DOMDocument;
    libxml_use_internal_errors(true); // Suppress warnings
    $dom->loadHTML($html);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);
    $imageElements = $xpath->query('//span[@class="thumbnail_center"]/img');

    if ($imageElements->length === 0) {
        // Log error
        gn_log_message_to_file('Image element not found for product ' . $product_id);
        return false;
    }

    //$image_url = $imageElements->item(0)->getAttribute('src');
    $image_url = $imageElements->item(0)->getAttribute('data-src');

    // Log extracted image URL for debugging
    gn_log_message_to_file('Barcode image URL from extractImageURL: ' . $image_url . ' for product ' . $product_id);
    //gn_log_message_to_file('HTML content from Discogs: ' . $html);


    return $image_url;
}

/**
 * Set the image as the featured image
 *
 * @param string $image_url
 * @param int $product_id
 * @param string $barcode
 */
function set_featured_image($image_url, $product_id, $barcode) {
    gn_log_message_to_file('Setting featured image for product ' . $product_id . ' with barcode ' . $barcode);
    // Check if the image is a data URI
    if (strpos($image_url, 'data:image') === 0) {
        gn_log_message_to_file(' in set_featured_image Data URI image for product ' . $product_id . ' with barcode ' . $barcode);
        handle_data_uri_image($image_url, $product_id, $barcode);
    
    } else {
        gn_log_message_to_file(' in set_featured_image Regular image for product ' . $product_id . ' with barcode ' . $barcode);
        handle_regular_image($image_url, $product_id, $barcode);
    }
}

/**
 * Handle data URI image
 *
 * @param string $data_uri
 * @param int $product_id
 * @param string $barcode
 */
function handle_data_uri_image($data_uri, $product_id, $barcode) {
    gn_log_message_to_file('Data URI image for product ' . $product_id . ' with barcode ' . $barcode);
    // Decode the base64 image data
    $base64_data = explode(',', $data_uri)[1];
    $image_data = base64_decode($base64_data);

    // Save the image to the uploads directory
    $file = save_image_to_upload_dir($image_data);

    // Set the image as the featured image
    set_featured_image_from_file($file, $product_id, $barcode, 'image/jpeg'); // Change to 'image/png' for PNG images
}

/**
 * Handle regular image URL
 *
 * @param string $image_url
 * @param int $product_id
 * @param string $barcode
 */
function handle_regular_image($image_url, $product_id, $barcode) {
    gn_log_message_to_file('Regular image for product ' . $product_id . ' with barcode ' . $barcode);
    // Fetch image data from the URL
    $image_data = wp_remote_get($image_url);

    if (is_array($image_data) && $image_data['response']['code'] === 200) {
        // Save the image to the uploads directory
        $file = save_image_to_upload_dir($image_data['body']);

        // Set the image as the featured image
        set_featured_image_from_file($file, $product_id, $barcode, $image_data['headers']['content-type']);
    } else {
        // Log error
        gn_log_message_to_file('Error retrieving image data for product ' . $product_id . ' with barcode ' . $barcode);
    }
}

/**
 * Save image data to the uploads directory
 *
 * @param string $image_data
 * @return string File path
 */
function save_image_to_upload_dir($image_data) {
    $upload_dir = wp_upload_dir();
    $file_name = 'data_image_' . md5($image_data) . '.jpg';
    $file = $upload_dir['path'] . '/' . $file_name;
    file_put_contents($file, $image_data);
    return $file;
}

/**
 * Set featured image from file
 *
 * @param string $file
 * @param int $product_id
 * @param string $barcode
 * @param string $mime_type
 */
function set_featured_image_from_file($file, $product_id, $barcode, $mime_type) {
    $attachment = array(
        'post_mime_type' => $mime_type,
        'post_title'     => sanitize_file_name(basename($file)),
        'post_content'   => '',
        'post_status'    => 'inherit',
    );

    $attach_id = wp_insert_attachment($attachment, $file, $product_id);

    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($attach_id, $file);
    wp_update_attachment_metadata($attach_id, $attach_data);

    set_post_thumbnail($product_id, $attach_id);
    gn_log_message_to_file('Set featured image for product ' . $product_id . ' with barcode ' . $barcode . ' to ' . $file);
}



/**
 * Log a message to a file
 *
 * @param mixed $message
 */
function gn_log_message_to_file($message) {
    $log_file = fopen(GNBARCODEI_PLUGIN_DIR . 'log.txt', 'a');
    // Message with timestamp
    $formatted_message = date('Y-m-d H:i:s') . ' ' . print_r($message, true) . "\n";
    fwrite($log_file, $formatted_message);
    fclose($log_file);
}

// Create plugin menu in the admin area for admin users only where we can manually view the contents of the log file and delete it
function gn_barcode_image_as_featured_product_image_menu() {
    add_menu_page('GN Barcode Image As Featured Product Image Log', 'GN Barcode Image As Featured Product Image Log', 'manage_options', 'gn_barcode_image_as_featured_product_image_log', 'gn_barcode_image_as_featured_product_image_log_page', 'dashicons-media-code', 6);
}
add_action('admin_menu', 'gn_barcode_image_as_featured_product_image_menu');

// Check if the user is an admin to allow access to the log page
function gn_barcode_image_as_featured_product_image_log_page_capability() {
    return 'manage_options';
}
add_filter('option_page_capability_gn_barcode_image_as_featured_product_image_log', 'gn_barcode_image_as_featured_product_image_log_page_capability');

// Log page
function gn_barcode_image_as_featured_product_image_log_page() {
    // Check if the user is an admin
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    // Check if the log file exists
    if (!file_exists(GNBARCODEI_PLUGIN_DIR . 'log.txt')) {
        echo '<h1>Log file not found</h1>';
        return;
    }
    // Check if the log file is empty
    if (filesize(GNBARCODEI_PLUGIN_DIR . 'log.txt') == 0) {
        echo '<h1>Log file is empty</h1>';
        return;
    }
    // Display the log file contents
    echo '<h1>Log file contents</h1>';
    echo '<pre>';
    echo file_get_contents(GNBARCODEI_PLUGIN_DIR . 'log.txt');
    echo '</pre>';
    // Delete the log file
    if (isset($_POST['delete_log_file'])) {
        unlink(GNBARCODEI_PLUGIN_DIR . 'log.txt');
        //delete the processed_id meta keys
        delete_processed_id_meta_key();
        echo '<h1>Log file deleted</h1>';

    }
    // Display the delete log file button
    echo '<form method="post" action="' . esc_url($_SERVER['REQUEST_URI']) . '">'; // Form post URL
    echo '<input type="submit" name="delete_log_file" value="Delete log file" />';
    echo '</form>';
}

function delete_processed_id_meta_key() {
    global $wpdb;
    $wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key = 'processed_id'");
    //log the deleted meta keys
    gn_log_message_to_file('Deleted processed_id meta keys');
}






GNBARCODEI();



