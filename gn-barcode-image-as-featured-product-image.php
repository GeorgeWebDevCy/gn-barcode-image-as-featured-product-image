<?php
/**
 * GN Barcode Image As Featured Product Image
 *
 * @package       GNBARCODEI
 * @author        George Nicolaou
 * @license       gplv2
 * @version       1.1.15
 *
 * @wordpress-plugin
 * Plugin Name:   GN Barcode Image As Featured Product Image
 * Plugin URI:    https://www.georgenicolaou.me/plugins/gn-barcode-image-as-featured-product-image
 * Description:   Find an image from a barcode and set it as the featured product image
 * Version:       1.1.15
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
define('GNBARCODEI_VERSION', '1.1.15');

// Plugin Root File
define('GNBARCODEI_PLUGIN_FILE', __FILE__);

// Plugin base
define('GNBARCODEI_PLUGIN_BASE', plugin_basename(GNBARCODEI_PLUGIN_FILE));

// Plugin Folder Path
define('GNBARCODEI_PLUGIN_DIR', plugin_dir_path(GNBARCODEI_PLUGIN_FILE));

// Plugin Folder URL
define('GNBARCODEI_PLUGIN_URL', plugin_dir_url(GNBARCODEI_PLUGIN_FILE));

// Include the Discogs API library
require_once GNBARCODEI_PLUGIN_DIR . 'vendor/autoload.php';  // Adjust the path based on your project structure

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
$myUpdateChecker->setBranch('main');

/**
 * The main function to load the only instance
 *
 * @author  George Nicolaou
 * @since   1.0.0
 * @return  object|Gn_Barcode_Image_As_Featured_Product_Image
 */
function GNBARCODEI() {
    // Initialize Guzzle HandlerStack
    $handler = \GuzzleHttp\HandlerStack::create();

    // Create and configure the ThrottleSubscriber
    $throttle = new \Discogs\Subscriber\ThrottleSubscriber();
    $handler->push(\GuzzleHttp\Middleware::retry($throttle->decider(), $throttle->delay()));

    // Initialize Discogs API client with the configured HandlerStack
    $discogsClient = \Discogs\ClientFactory::factory([
        'handler' => $handler,
        'headers' => [
            'User-Agent' => 'All Records Test App/0.1 +https://allrecords.com/',
        ],
    ]);
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

// Look up an image from Discogs and set it as the featured image
function gn_barcode_image_as_featured_product_image() {
    // Set the number of products to process at a time
    $products_per_batch = 1;

    // Get all products
    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => $products_per_batch,
        'status'         => 'publish',
    );
    $loop = new WP_Query($args);
    gn_log_message_to_file('Before the while loop');

    // Your Discogs API credentials
    $consumerKey = 'MefXPqYkBwPUteoNHoOb';
    $consumerSecret = 'nCynliNLRbIOBJHwHYXNVsOxYPzQGgrF';
    $requestTokenUrl = 'https://api.discogs.com/oauth/request_token';
    $authorizeUrl = 'https://www.discogs.com/oauth/authorize';
    $accessTokenUrl = 'https://api.discogs.com/oauth/access_token';

    // Initialize the Discogs API client
    $discogs = new \Discogs\DiscogsClient([
        'key' => $consumerKey,
        'secret' => $consumerSecret,
        'request_token_url' => $requestTokenUrl,
        'authorize_url' => $authorizeUrl,
        'access_token_url' => $accessTokenUrl,
    ]);

    while ($loop->have_posts()) : $loop->the_post();
        // Check if the product already has a featured image and has not been processed
        $processed = get_post_meta(get_the_ID(), 'processed_id', true);
        if ($processed === '1' || has_post_thumbnail(get_the_ID())) {
            gn_log_message_to_file('Product ' . get_the_ID() . ' already has a featured image or has been processed. Skipping product.');
            gn_log_message_to_file('has_post_thumbnail for product ' . get_the_ID() . ': ' . has_post_thumbnail(get_the_ID()));
            gn_log_message_to_file('Processed ID for product ' . get_the_ID() . ': ' . $processed);
            update_post_meta(get_the_ID(), 'processed_id', '1');
            continue; // Skip to the next product if a featured image is already set or the product has been processed
        }

        // Mark the product as processed to avoid reprocessing
        update_post_meta(get_the_ID(), 'processed_id', '1');

        // Get the barcode
        $barcode = get_post_meta(get_the_ID(), '_sku', true);
        gn_log_message_to_file('Barcode: ' . $barcode . ' for product ' . get_the_ID());

        try {
            // Obtain an access token (you may need to store this token securely)
            $accessToken = $discogs->getAccessToken($barcode);

            if (empty($accessToken)) {
                gn_log_message_to_file('Error obtaining access token for product ' . get_the_ID());
                continue;
            }
            
            // Make a request to the Discogs API using the access token
            $results = $discogs->search([
                'q' => $barcode,
                'type' => 'release'
            ], $accessToken['oauth_token'], $accessToken['oauth_token_secret']);

            if (!empty($results['results'])) {
                $release = $results['results'][0];
                $image_url = $release['cover_image'];

                if ($image_url !== false) {
                    set_featured_image($image_url, get_the_ID(), $barcode);
                }
            }
        } catch (\Exception $e) {
            // Handle the exception (log or display an error message)
            error_log('Discogs API Error: ' . $e->getMessage());
            gn_log_message_to_file('Error processing product ' . get_the_ID() . ': ' . $e->getMessage());
        }
    endwhile;
    gn_log_message_to_file('After the while loop');
    // Reset post data
    wp_reset_postdata();
}
/**
 * Use Discogs API to get HTML content
 *
 * @param string $url
 * @param string $barcode
 * @param int $product_id
 * @return string|bool HTML content or false on failure
 */
function gn_get_html_content($url, $barcode, $product_id) {
    try {
        // Initialize Discogs API client
        $client = \Discogs\ClientFactory::factory([
            'headers' => [
                'User-Agent' => 'All Records Test App/0.1 +https://allrecords.com/',
            ],
        ]);

        // Get HTML content from Discogs
        $response = $client->getHttpClient()->get($url);
        $html_content = $response->getBody()->getContents();

        // Log success
        gn_log_message_to_file('Successfully retrieved HTML content from Discogs for product ' . $product_id . ' with barcode ' . $barcode);

        return $html_content;
    } catch (\Exception $e) {
        // Log error
        gn_log_message_to_file('Error retrieving HTML content from Discogs for product ' . $product_id . ' with barcode ' . $barcode . ': ' . $e->getMessage());

        return false;
    }
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
    //log how many meta keys were deleted
    gn_log_message_to_file('Deleted ' . $wpdb->rows_affected . ' processed_id meta keys');
}






GNBARCODEI();



