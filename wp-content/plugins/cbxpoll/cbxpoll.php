<?php
/**
 *
 * @link              https://codeboxr.com
 * @since             1.0.0
 * @package           Cbxpoll
 *
 * @wordpress-plugin
 * Plugin Name:       CBX Poll
 * Plugin URI:        https://codeboxr.com/product/cbx-poll-for-wordpress/
 * Description:       Poll and vote system for WordPress
 * Version:           1.2.7
 * Author:            codeboxr
 * Author URI:        https://codeboxr.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       cbxpoll
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

//plugin definition specific constants
defined('CBX_POLL_PLUGIN_NAME') or define('CBX_POLL_PLUGIN_NAME', 'cbxpoll');
defined('CBX_POLL_PLUGIN_VERSION') or define('CBX_POLL_PLUGIN_VERSION', '1.2.7');
defined('CBX_POLL_PLUGIN_BASE_NAME') or define('CBX_POLL_PLUGIN_BASE_NAME', plugin_basename(__FILE__));
defined('CBX_POLL_PLUGIN_ROOT_PATH') or define('CBX_POLL_PLUGIN_ROOT_PATH', plugin_dir_path(__FILE__));
defined('CBX_POLL_PLUGIN_ROOT_URL') or define('CBX_POLL_PLUGIN_ROOT_URL', plugin_dir_url(__FILE__));

//plugin functionality specific constants

defined('CBX_POLL_COOKIE_EXPIRATION') or define('CBX_POLL_COOKIE_EXPIRATION',
    time() + 1209600); //Expiration of 14 days.
defined('CBX_POLL_COOKIE_NAME') or define('CBX_POLL_COOKIE_NAME', 'cbxpoll-cookie');
defined('CBX_POLL_RAND_MIN') or define('CBX_POLL_RAND_MIN', 0);
defined('CBX_POLL_RAND_MAX') or define('CBX_POLL_RAND_MAX', 999999);
defined('CBX_POLL_COOKIE_EXPIRATION_14DAYS') or define('CBX_POLL_COOKIE_EXPIRATION_14DAYS',
    time() + 1209600); //Expiration of 14 days.
defined('CBX_POLL_COOKIE_EXPIRATION_7DAYS') or define('CBX_POLL_COOKIE_EXPIRATION_7DAYS',
    time() + 604800); //Expiration of 7 days.

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-cbxpoll-activator.php
 */
function activate_cbxpoll()
{
    require_once plugin_dir_path(__FILE__).'includes/class-cbxpoll-activator.php';
    CBXPoll_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-cbxpoll-deactivator.php
 */
function deactivate_cbxpoll()
{
    require_once plugin_dir_path(__FILE__).'includes/class-cbxpoll-deactivator.php';
    CBXPoll_Deactivator::deactivate();
}

function uninstall_cbxpoll()
{
    require_once plugin_dir_path(__FILE__).'includes/class-cbxpoll-uninstall.php';
    CBXPoll_Uninstall::uninstall();
}

register_activation_hook(__FILE__, 'activate_cbxpoll');
register_deactivation_hook(__FILE__, 'deactivate_cbxpoll');
register_uninstall_hook(__FILE__, 'uninstall_cbxpoll');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__).'includes/class-cbxpoll.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_cbxpoll()
{

    $plugin = new CBXPoll();
    $plugin->run();

}

run_cbxpoll();
