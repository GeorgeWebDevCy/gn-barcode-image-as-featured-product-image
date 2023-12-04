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

        // Use cURL to get the HTML content
        gn_log_message_to_file('Url being used is '.'https://www.barcodelookup.com/' . $barcode);
        $barcode_html = gn_get_html_content('https://www.barcodelookup.com/' . $barcode, $barcode, get_the_ID());
        //log error if barcode_html is empty
        //gn_log_message_to_file('Barcode HTML: ' . $barcode_html . ' for product ' . get_the_ID());

        // Check if the HTML content was retrieved successfully
        if ($barcode_html === false || empty($barcode_html)) {
            gn_log_message_to_file('Skipped product ' . get_the_ID() . ' with barcode ' . $barcode . ' due to retrieval issues.');
            continue; // Skip to the next product if the HTML content is not available
        }

        // Extract the image URL
        $image_url = extract_image_url($barcode_html, get_the_ID());

        // Set the image as the featured image
        if ($image_url !== false) {
            set_featured_image($image_url, get_the_ID(), $barcode);
        }
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

/**
 * Retrieve HTML content using cURL
 *
 * @param string $url
 * @param string $barcode
 * @param int $product_id
 * @return false|string
 */
function gn_get_html_content($url, $barcode, $product_id) {
    $ch = curl_init();
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    // Set multiple headers to mimic a regular browser
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.9',
        'Accept-Encoding: gzip, deflate, br',
    ]);

    // Sleep for 2 seconds between requests to avoid rate limiting
    sleep(2);

    // Execute cURL and get HTTP response code
    $barcode_html = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Log HTTP response code
    gn_log_message_to_file('HTTP Response Code: ' . $http_code . ' for product ' . $product_id . ' with barcode ' . $barcode);

    // Check for cURL errors
    if ($barcode_html === false) {
        // Log or handle the cURL error here
        gn_log_message_to_file('cURL error: ' . curl_error($ch));
        curl_close($ch);
        return false;
    }
    
    // Check if the HTML content is empty
    if (empty($barcode_html)) {
        gn_log_message_to_file('Empty HTML content for product ' . $product_id . ' with barcode ' . $barcode);
        curl_close($ch);
        return false;
    }

    curl_close($ch);
    return $barcode_html;
}




/**
 * Extract image URL from HTML content
 *
 * @param string $barcode_html
 * @param int $product_id
 * @return false|string
 */
function extract_image_url($barcode_html, $product_id) {
    $dom = new DOMDocument;
    libxml_use_internal_errors(true); // Suppress warnings
    $dom->loadHTML($barcode_html);
    libxml_clear_errors();

    $image_elements = $dom->getElementById('largeProductImage');
    if (!$image_elements) {
        gn_log_message_to_file('Image element not found for product ' . $product_id . ' with barcode ' . $barcode);
        //gn_log_message_to_file('Full HTML content: ' . $barcode_html);
        return false;
    }

    $image_url = $image_elements->getElementsByTagName('img')->item(0)->getAttribute('src');
    gn_log_message_to_file('Barcode image URL: ' . $image_url . ' for product ' . $product_id);

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
    $upload_dir = wp_upload_dir();
    gn_log_message_to_file('Upload dir: ' . print_r($upload_dir, true));
    $image_data = file_get_contents($image_url);
    gn_log_message_to_file('Image data: ' . print_r($image_data, true) . ' for product ' . $product_id);
    $filename   = basename($image_url);
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
    $attach_id    = wp_insert_attachment($attachment, $file, $product_id);
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($attach_id, $file);
    wp_update_attachment_metadata($attach_id, $attach_data);
    set_post_thumbnail($product_id, $attach_id);
    gn_log_message_to_file('Set featured image for product ' . $product_id . ' with barcode ' . $barcode . ' to ' . $image_url);
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
        echo '<h1>Log file deleted</h1>';
    }
    // Display the delete log file button
    echo '<form method="post" action="' . esc_url($_SERVER['REQUEST_URI']) . '">'; // Form post URL
    echo '<input type="submit" name="delete_log_file" value="Delete log file" />';
    echo '</form>';
}

GNBARCODEI();
require 'plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
	'https://github.com/GeorgeWebDevCy/gn-barcode-image-as-featured-product-image',
	__FILE__,
	'gn-barcode-image-as-featured-product-image'
);
//Set the branch that contains the stable release.
$myUpdateChecker->setBranch('main');