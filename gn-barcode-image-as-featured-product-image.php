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
