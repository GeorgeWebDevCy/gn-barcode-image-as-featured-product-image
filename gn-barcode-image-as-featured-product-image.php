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

//create a submenu in my plugin menu that will get a token from discogs api and save it in the database for later use in the gn_barcode_image_as_featured_product_image_query_discogsapi($querydiscogs) function
function gn_barcode_image_as_featured_product_image_get_token_submenu_page() {
	add_submenu_page(
		'gn-barcode-image-as-featured-product-image',
		'Get Token',
		'Get Token',
		'manage_options',
		'gn-barcode-image-as-featured-product-image-get-token',
		'gn_barcode_image_as_featured_product_image_get_token_submenu_page_callback' );
}
add_action( 'admin_menu', 'gn_barcode_image_as_featured_product_image_get_token_submenu_page' );

function gn_barcode_image_as_featured_product_image_get_token_submenu_page_callback() {
    ?>
    <div class="wrap">
        <h2>Get Token</h2>
        <form method="post" action="">
            <input type="submit" class="button-primary" name="submit" value="Get Token">
        </form>
        <?php
        // Check if the form is submitted
        if (isset($_POST['submit'])) {
            // Call the function to initiate the OAuth process
            gn_barcode_image_as_featured_product_image_get_token();
        }
        ?>
    </div>
    <?php
}

add_action('admin_init', 'gn_barcode_image_as_featured_product_image_handle_callback');


function gn_barcode_image_as_featured_product_image_get_token() {
    $options = get_option('gn_barcode_image_as_featured_product_image');
    $consumerKey = $options['gn_barcode_image_as_featured_product_image_consumer_key'];
    $consumerSecret = $options['gn_barcode_image_as_featured_product_image_consumer_secret'];
    $requestTokenUrl = $options['gn_barcode_image_as_featured_product_image_request_token_url'];
    $authorizeUrl = $options['gn_barcode_image_as_featured_product_image_authorize_url'];
    $accessTokenUrl = $options['gn_barcode_image_as_featured_product_image_access_token_url'];

    $oauthObject = new OAuthSimple();
    $scope = 'http://api.discogs.com';

    // Initialize the output in case we get stuck in the first step.
    $output = 'Authorizing...';

    // Fill in your API key/consumer key you received when you registered your
    // application with Discogs.
    $signatures = array('consumer_key' => $consumerKey, 'shared_secret' => $consumerSecret);

    // Check if verifier exists.  If not, get a request token
    if (!isset($_GET['oauth_verifier'])) {
        // To get a Request Token, we make a request to the OAuthGetRequestToken endpoint,
        // submitting the scope of the access we need (api.discogs.com)
        // and also tell Discogs where to redirect once authorization is submitted
        $result = $oauthObject->sign(array(
            'path' => 'https://api.discogs.com/oauth/request_token',
            'parameters' => array(
                'scope' => $scope,
                'oauth_callback' => 'https://dev.georgenicolaou.me/gn-barcode-image-as-featured-product-image-get-token'	// must be the same as in the application settings
            ),
            'signatures' => $signatures
        ));

        // The above object generates a simple URL that includes a signature, the
        // needed parameters, and the web page that will handle our request.
        // Using the cUrl library, we send a GET request to the signed URL
        // then add the response into a string variable ($r)
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_USERAGENT, 'All records Dev/0.1 +https://dev.georgenicolaou.me');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $result['signed_url']);

        $r = curl_exec($ch);

        // Then we parse the string for the request token and the matching token secret.
        parse_str($r, $returned_items);

        if (isset($returned_items['oauth_token'], $returned_items['oauth_token_secret'])) {
            $request_token = $returned_items['oauth_token'];
            $request_token_secret = $returned_items['oauth_token_secret'];

            // We store the token and secret in a cookie for later when authorization is complete
            setcookie("oauth_token_secret", $request_token_secret, time() + 3600);

            // Next we generate a URL for an authorization request, then redirect to that URL
            // so the user can authorize our request.
            // The user could deny the request, so we should add some code later to handle that situation
            $result = $oauthObject->sign(array(
                'path' => 'http://www.discogs.com/oauth/authorize',
                'parameters' => array(
                    'oauth_token' => $request_token
                ),
                'signatures' => $signatures
            ));

            // Here is where we redirect
            header("Location:$result[signed_url]");
            exit;
        } else {
            // Handle the case where the expected values are not present in the response
            wp_die('Error obtaining request token.');
        }
    } else {
        // If we have an oauth_verifier, fetch the cookie and amend our signature array with the request
        // token and secret.
        $signatures['oauth_secret'] = $_COOKIE['oauth_token_secret'];
        $signatures['oauth_token'] = $_GET['oauth_token'];

        // Build the request-URL
        $result = $oauthObject->sign(array(
            'path' => 'http://api.discogs.com/oauth/access_token',
            'parameters' => array(
                'oauth_verifier' => $_GET['oauth_verifier'],
                'oauth_token' => $_GET['oauth_token']
            ),
            'signatures' => $signatures
        ));

        // ... and get the web page and store it as a string again.
        $ch = curl_init();
        //Set the User-Agent Identifier
        curl_setopt($ch, CURLOPT_USERAGENT, 'All records Dev/0.1 +https://dev.georgenicolaou.me');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $result['signed_url']);
        $r = curl_exec($ch);

        // parse the string to get your access token
        parse_str($r, $returned_items);

        if (isset($returned_items['oauth_token'], $returned_items['oauth_token_secret'])) {
            $access_token = $returned_items['oauth_token'];
            $access_token_secret = $returned_items['oauth_token_secret'];

            // We can use this long-term access token to request Discogs API data,
            // for example, the identity of the authenticated user.
            // All Discogs API data requests will have to be signed just as before,
            // but we can now bypass the authorization process and use the long-term
            // access token you hopefully store somewhere permanently.
            $oauth_props = array('oauth_token' => $access_token, 'oauth_secret' => $access_token_secret);
			wp_die();

            //get oauth token and secret from cookie and save them in the database
            $options = get_option('gn_barcode_image_as_featured_product_image');
            $options['gn_barcode_image_as_featured_product_image_oauth_token'] = $access_token;
            $options['gn_barcode_image_as_featured_product_image_oauth_token_secret'] = $access_token_secret;
            update_option('gn_barcode_image_as_featured_product_image', $options);
        } else {
            // Handle the case where the expected values are not present in the response
            wp_die('Error obtaining access token.');
        }
    }
}

function gn_barcode_image_as_featured_product_image_handle_callback() {
echo 'in callback';
}

function gn_barcode_image_as_featured_product_image_query_discogsapi($querydiscogs)
{
$options = get_option( 'gn_barcode_image_as_featured_product_image' );
$consumerKey = $options['gn_barcode_image_as_featured_product_image_consumer_key'];
$consumerSecret = $options['gn_barcode_image_as_featured_product_image_consumer_secret'];
$requestTokenUrl = $options['gn_barcode_image_as_featured_product_image_request_token_url'];
$authorizeUrl = $options['gn_barcode_image_as_featured_product_image_authorize_url'];
$accessTokenUrl = $options['gn_barcode_image_as_featured_product_image_access_token_url'];
$oauthToken = $options['gn_barcode_image_as_featured_product_image_oauth_token'];
$oauthTokenSecret = $options['gn_barcode_image_as_featured_product_image_oauth_token_secret'];



// Create a new instance of the client
$handler = \GuzzleHttp\HandlerStack::create();
$throttle = new Discogs\Subscriber\ThrottleSubscriber();
$handler->push(\GuzzleHttp\Middleware::retry($throttle->decider(), $throttle->delay()));

$oauth = new GuzzleHttp\Subscriber\Oauth\Oauth1([
    'consumer_key'    => $consumerKey, // from Discogs developer page
    'consumer_secret' => $consumerSecret, // from Discogs developer page
    'token'           => $oauthToken, // get this using a OAuth library
    'token_secret'    => $oauthTokenSecret // get this using a OAuth library
]);
$handler = GuzzleHttp\HandlerStack::create();
$handler->push($oauth);
$client = Discogs\ClientFactory::factory([
    'handler' => $handler,
    'auth' => 'oauth'
]);


return $response;
}
//create a submenu in my plugin meny to query the discogs api and show the results uing the gn_barcode_image_as_featured_product_image_query_discogsapi($querydiscogs) function
function gn_barcode_image_as_featured_product_image_query_discogsapi_submenu_page() {
	add_submenu_page(
		'gn-barcode-image-as-featured-product-image',
		'Query Discogs API',
		'Query Discogs API',
		'manage_options',
		'gn-barcode-image-as-featured-product-image-query-discogsapi',
		'gn_barcode_image_as_featured_product_image_query_discogsapi_submenu_page_callback' );
}
add_action( 'admin_menu', 'gn_barcode_image_as_featured_product_image_query_discogsapi_submenu_page' );



function gn_barcode_image_as_featured_product_image_query_discogsapi_submenu_page_callback() {
    ?>
    <div class="wrap">
        <h2>Query Discogs API</h2>
		<?php gn_barcode_image_as_featured_product_image_show_current_tokens(); ?>
        <form method="post" action="">
            <label for="barcode">Enter Barcode:</label>
            <input type="text" id="barcode" name="barcode" required>
            <input type="submit" class="button-primary" value="Query Discogs API">
        </form>
        <?php
        // Check if the form is submitted
        if (isset($_POST['barcode'])) {
            $barcode = sanitize_text_field($_POST['barcode']);
            // Call the function to query Discogs API
            $result = gn_barcode_image_as_featured_product_image_query_discogsapi($barcode);
			// Display the result
			echo '<pre>';
			print_r($result);
			echo '</pre>';
		
	
        }
        ?>
    </div>
    <?php
}

function gn_barcode_image_as_featured_product_image_show_current_tokens()
{
	$options = get_option( 'gn_barcode_image_as_featured_product_image' );
	$oauthToken = isset($options['gn_barcode_image_as_featured_product_image_oauth_token']) ? $options['gn_barcode_image_as_featured_product_image_oauth_token'] : '';
	$oauthTokenSecret = isset($options['gn_barcode_image_as_featured_product_image_oauth_token_secret']) ? $options['gn_barcode_image_as_featured_product_image_oauth_token_secret'] : '';
	echo '<p>Current OAuth Token: ' . $oauthToken . '</p>';
	echo '<p>Current OAuth Token Secret: ' . $oauthTokenSecret . '</p>';
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
