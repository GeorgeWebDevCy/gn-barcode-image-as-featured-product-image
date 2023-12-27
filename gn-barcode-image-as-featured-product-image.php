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
    //gn_log_message_to_file('Set featured image for product ' . $product_id . ' with barcode ' . $barcode . ' to ' . $file);
}


//autoload the composer packages
require_once GNBARCODEI_PLUGIN_DIR . 'vendor/autoload.php';

use OAuth\OAuth1\Service\BitBucket;
use OAuth\Common\Storage\Session;
use OAuth\Common\Consumer\Credentials;
require_once GNBARCODEI_PLUGIN_DIR . 'oauthsimple/php/OAuthSimple.php';

ini_set('date.timezone', 'Europe/Athens');

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
    add_settings_field( 'gn_barcode_image_as_featured_product_image_user_agent', 'User Agent', 'gn_barcode_image_as_featured_product_image_user_agent_callback', 'gn_barcode_image_as_featured_product_image', 'gn_barcode_image_as_featured_product_image_settings' );
    add_settings_field( 'gn_barcode_image_as_featured_product_image_your_domain', 'Your Domain', 'gn_barcode_image_as_featured_product_image_your_domain_callback', 'gn_barcode_image_as_featured_product_image', 'gn_barcode_image_as_featured_product_image_settings' );
}
$options = get_option( 'gn_barcode_image_as_featured_product_image' );
if ( ! empty( $options ) ) {
	$gn_barcode_image_as_featured_product_image_consumer_key = $options['gn_barcode_image_as_featured_product_image_consumer_key'];
	$gn_barcode_image_as_featured_product_image_consumer_secret = $options['gn_barcode_image_as_featured_product_image_consumer_secret'];
	$gn_barcode_image_as_featured_product_image_request_token_url = $options['gn_barcode_image_as_featured_product_image_request_token_url'];
	$gn_barcode_image_as_featured_product_image_authorize_url = $options['gn_barcode_image_as_featured_product_image_authorize_url'];
	$gn_barcode_image_as_featured_product_image_access_token_url = $options['gn_barcode_image_as_featured_product_image_access_token_url'];
    $gn_barcode_image_as_featured_product_image_user_agent = $options['gn_barcode_image_as_featured_product_image_user_agent'];
    $gn_barcode_image_as_featured_product_image_your_domain = $options['gn_barcode_image_as_featured_product_image_your_domain'];


    
}

//set the default values for the settings
if ( empty( $options ) ) {
	$gn_barcode_image_as_featured_product_image_consumer_key = 'wlpINNZHOoNpPWaUFDgV';
	$gn_barcode_image_as_featured_product_image_consumer_secret = 'uYVRwbUNNwFkTutbkLdVoSCnBWiuvPcg';
	$gn_barcode_image_as_featured_product_image_request_token_url = 'https://api.discogs.com/oauth/request_token';
	$gn_barcode_image_as_featured_product_image_authorize_url = 'https://www.discogs.com/oauth/authorize';
	$gn_barcode_image_as_featured_product_image_access_token_url = 'https://api.discogs.com/oauth/access_token';
    $gn_barcode_image_as_featured_product_image_user_agent = 'All records Dev/1.0 +https://dev.georgenicolaou.me';
    $gn_barcode_image_as_featured_product_image_your_domain = 'https://dev.georgenicolaou.me';
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

function gn_barcode_image_as_featured_product_image_user_agent_callback() {
    $options = get_option( 'gn_barcode_image_as_featured_product_image' );
    echo "<input type='text' name='gn_barcode_image_as_featured_product_image[gn_barcode_image_as_featured_product_image_user_agent]' value='" . $options['gn_barcode_image_as_featured_product_image_user_agent'] . "' />";
}

//yout domain callback
function gn_barcode_image_as_featured_product_image_your_domain_callback() {
    $options = get_option( 'gn_barcode_image_as_featured_product_image' );
    echo "<input type='text' name='gn_barcode_image_as_featured_product_image[gn_barcode_image_as_featured_product_image_your_domain]' value='" . $options['gn_barcode_image_as_featured_product_image_your_domain'] . "' />";
}



//add the settings link to the plugins page
function gn_barcode_image_as_featured_product_image_settings_link( $links ) {
	$settings_link = '<a href="admin.php?page=gn-barcode-image-as-featured-product-image">' . __( 'Settings' ) . '</a>';
	array_push( $links, $settings_link );
	return $links;
}
$plugin = plugin_basename( __FILE__ );
add_filter( "plugin_action_links_$plugin", 'gn_barcode_image_as_featured_product_image_settings_link' );



//create a submenu page for the plugin where I can see the output of the gn_barcode_image_as_featured_product_image_query_discogsapi function
function gn_barcode_image_as_featured_product_image_submenu_page() {
    add_submenu_page(
        'gn-barcode-image-as-featured-product-image',
        'GN Barcode Image As Featured Product Image',
        'GN Barcode Image Logger',
        'manage_options',
        'gn-barcode-image-as-featured-product-image-submenu-page',
        'gn_barcode_image_as_featured_product_image_submenu_page_callback' );
}
add_action( 'admin_menu', 'gn_barcode_image_as_featured_product_image_submenu_page' );

//callback function for the submenu page
 function gn_barcode_image_as_featured_product_image_submenu_page_callback() {
    $options = get_option( 'gn_barcode_image_as_featured_product_image' );
    $consumerKey = $options['gn_barcode_image_as_featured_product_image_consumer_key'];
    $consumerSecret = $options['gn_barcode_image_as_featured_product_image_consumer_secret'];
    $requestTokenUrl = $options['gn_barcode_image_as_featured_product_image_request_token_url'];
    $authorizeUrl = $options['gn_barcode_image_as_featured_product_image_authorize_url'];
    $accessTokenUrl = $options['gn_barcode_image_as_featured_product_image_access_token_url'];
    $oauthToken = $options['gn_barcode_image_as_featured_product_image_oauth_token'];
    $oauthTokenSecret = $options['gn_barcode_image_as_featured_product_image_oauth_token_secret'];
    $your_domain = $options['gn_barcode_image_as_featured_product_image_your_domain'];
    //add use user agent option in the database
    $options = get_option( 'gn_barcode_image_as_featured_product_image' );
    $user_agent = $options['gn_barcode_image_as_featured_product_image_user_agent'];
    $your_domain = $options['gn_barcode_image_as_featured_product_image_your_domain'];

    //show current settings
    echo "<h2>Current Settings</h2>";
    echo "<p>Consumer Key: " . $consumerKey . "</p>";
    echo "<p>Consumer Secret: " . $consumerSecret . "</p>";
    echo "<p>Request Token URL: " . $requestTokenUrl . "</p>";
    echo "<p>Authorize URL: " . $authorizeUrl . "</p>";
    echo "<p>Access Token URL: " . $accessTokenUrl . "</p>";
    echo "<p>OAuth Token: " . $oauthToken . "</p>";
    echo "<p>OAuth Token Secret: " . $oauthTokenSecret . "</p>";
    echo "<p>User Agent: " . $user_agent . "</p>";
    echo "<p>Your Domain: " . $your_domain . "</p>";

    
    //since the oauth token and oauth token secret are not saved in the database, I need to get them using the oauthsimple library
    $oauthObject = new OAuthSimple();
    $scope = 'https://api.discogs.com';

    // Initialize the output in case we get stuck in the first step.
$output = 'Authorizing...';

// Fill in your API key/consumer key you received when you registered your 
// application with Discogs.
$signatures = array( 'consumer_key'     => $consumerKey,
                     'shared_secret'    => $consumerSecret);

// Check if verifier exists.  If not, get a request token
if (!isset($_GET['oauth_verifier'])) {
    // To get a Request Token, we make a request to the OAuthGetRequestToken endpoint,
    // submitting the scope of the access we need (api.discogs.com)
	  // and also tell Discogs where to redirect once authorization is submitted
    $result = $oauthObject->sign(array(
        'path'      =>$requestTokenUrl,
        'parameters'=> array(
            'scope'         => $scope,
            'oauth_callback'=> $your_domain . '/wp-admin/admin.php?page=gn-barcode-image-as-featured-product-image-submenu-page'),
        'signatures'=> $signatures));

    // The above object generates a simple URL that includes a signature, the 
    // needed parameters, and the web page that will handle our request.
    // Using the cUrl libary, we send a GET request to the signed URL
	  // then add the response into a string variable ($r)
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_USERAGENT, 'YOUR_USER_AGENT/0.1 +http://yourdomain.com');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $result['signed_url']);
    
    $r = curl_exec($ch);
    curl_close($ch);

    // Then we parse the string for the request token and the matching token secret. 
    parse_str($r, $returned_items);
    $request_token = $returned_items['oauth_token'];
    $request_token_secret = $returned_items['oauth_token_secret'];
	
    // We store the token and secret in a cookie for later when authorization is complete
    setcookie("oauth_token_secret", $request_token_secret, time()+3600);
    
    // Next we generate a URL for an authorization request, then redirect to that URL
    // so the user can authorize our request.  
    // The user could deny the request, so we should add some code later to handle that situation
    $result = $oauthObject->sign(array(
        'path'      => $authorizeUrl,
        'parameters'=> array(
            'oauth_token' => $request_token),
        'signatures'=> $signatures));

    // Here is where we redirect
    header("Location:$result[signed_url]");
    exit;
}
else {
    // If we have a oauth_verifier, fetch the cookie and amend our signature array with the request
    // token and secret.
    $signatures['oauth_secret'] = $_COOKIE['oauth_token_secret'];
    $signatures['oauth_token'] = $_GET['oauth_token'];
    
    // Build the request-URL
    $result = $oauthObject->sign(array(
        'path'      => $accessTokenUrl,
        'parameters'=> array(
            'oauth_verifier' => $_GET['oauth_verifier'],
            'oauth_token'    => $_GET['oauth_token']),
        'signatures'=> $signatures));

    // ... and get the web page and store it as a string again.
    $ch = curl_init();
	  //Set the User-Agent Identifier
    curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $result['signed_url']);
    $r = curl_exec($ch);

    // parse the string to get you access token
    parse_str($r, $returned_items);        
    $access_token = $returned_items['oauth_token'];
    $access_token_secret = $returned_items['oauth_token_secret'];
    
    // We can use this long-term access token to request Discogs API data,
    // for example, the identity of the authenticated user. 
    // All Discogs API data requests will have to be signed just as before,
    // but we can now bypass the authorization process and use the long-term
    // access token you hopefully store somewhere permanently.
    $oauth_props = array( 'oauth_token'     => $access_token,
                     'oauth_secret'    => $access_token_secret);

    // reset the oauth object
    $oauthObject->reset();
    
    // rebuild it with the URL of the resource you want to access and the token/secret
    $params['path'] = "$scope/database/search";
    $params['signatures'] = $oauth_props;
    
    // add optional parameters as needed  
    // For example: when using the search endpoint, and/or when passing pagination options
    $params['parameters'] = 'q=nevermind&artist=nirvana&per_page=3&page=1';
    
    $result = $oauthObject->sign($params);

    // Now that we have our signed URL, we can make one more call to the API
    // which will grant us access to an authenticated resource
    // such as http://api.discogs.com/oauth/identity

    $url = $result['signed_url'];
	
    curl_setopt($ch, CURLOPT_USERAGENT, 'YOUR_USER_AGENT/0.1 +http://yourdomain.com');
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    //Execute the curl session
    $output = curl_exec($ch);
	
    curl_close($ch);
	
    // print the JSON output to the page
    echo $output;
}        
 }



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
