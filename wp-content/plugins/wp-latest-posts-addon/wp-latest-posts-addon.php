<?php
/*
Plugin Name: WP Latest Posts Add-on
Plugin URI: http://www.joomunited.com/wordpress-products/wp-latest-posts
Description: Advanced frontpage and widget news slider
Version: 4.6.5
Text Domain: wp-latest-posts-addon
Domain Path: /languages
Author: JoomUnited
Author URI: http://www.joomunited.com
Update URI: https://www.joomunited.com/juupdater_files/wp-latest-posts-addon.json
License: GPL2
*/

/*
 * @copyright 2014  JoomUnited ( email : support _at_ wpcode-united.com )
 *
 *  Original development of this plugin was kindly funded by WPCode United
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */
if (!defined('WPLPADDON_PLUGIN_DIR')) {
    define('WPLPADDON_PLUGIN_DIR', plugin_dir_url(__FILE__));
}
if (!defined('WPLPADDON_PLUGIN_PATH')) {
    define('WPLPADDON_PLUGIN_PATH', plugin_dir_path(__FILE__));
}
if (!defined('WPLP_ADDON_VERSION')) {
    define('WPLP_ADDON_VERSION', '4.6.5');
}
//Check plugin requirements
if (version_compare(PHP_VERSION, '5.6', '<')) {
    if (!function_exists('wplpAddonDisablePlugin')) {
        /**
         * Disable plugin if phpversion lessthan 5.6
         *
         * @return void
         */
        function wplpAddonDisablePlugin()
        {
            if (current_user_can('activate_plugins') && is_plugin_active(plugin_basename(__FILE__))) {
                deactivate_plugins(__FILE__);
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Internal function used
                unset($_GET['activate']);
            }
        }
    }

    if (!function_exists('wplpAddonShowError')) {
        /**
         * Show notice if phpversion lessthan 5.6
         *
         * @return void
         */
        function wplpAddonShowError()
        {
            $echo = '<div class="error"><p><strong>WP Latest Posts Addon</strong>';
            $echo .= ' need at least PHP 5.6 version, please update php before installing the plugin.</p></div>';
            //phpcs:ignore WordPress.Security.EscapeOutput -- Plain text html, no variables to escape
            echo $echo;
        }
    }

    //Add actions
    add_action('admin_init', 'wplpAddonDisablePlugin');
    add_action('admin_notices', 'wplpAddonShowError');

    //Do not load anything more
    return;
}

/**
 * Get addon path for requirement check
 *
 * @return string
 */
function wplpAddons_getPath()
{
    if (!function_exists('plugin_basename')) {
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');
    }

    return plugin_basename(__FILE__);
}

//JU requirements
if (!class_exists('\Joomunited\WPLPADDON\JUCheckRequirements')) {
    require_once(trailingslashit(dirname(__FILE__)) . 'requirements.php');
}

if (class_exists('\Joomunited\WPLPADDON\JUCheckRequirements')) {
    // Plugins name for translate
    $args           = array(
        'plugin_name'       => esc_html__('WP Latest Posts Addon', 'wp-latest-posts-addon'),
        'plugin_path'       => 'wp-latest-posts-addon/wp-latest-posts-addon.php',
        'plugin_textdomain' => 'wp-latest-posts-addon',
        'plugin_version'    => WPLP_ADDON_VERSION,
        'requirements'      => array(
            'plugins'     => array(
                array(
                    'name' => 'WP Latest Posts',
                    'path' => 'wp-latest-posts/wp-latest-posts.php',
                    'requireVersion' => '4.5.0'
                )
            ),
            'php_version' => '5.6'
        ),
    );

    $wplpAddonCheck = call_user_func('\Joomunited\WPLPADDON\JUCheckRequirements::init', $args);

    if (!$wplpAddonCheck['success']) {
        //Do not load anything more
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Internal function used
        unset($_GET['activate']);

        return;
    }
}

//JUtranslation
    add_filter(
        'wp-latest-posts_get_addons',
        function ($addons) {
            $addon                          = new stdClass();
            $addon->main_plugin_file        = __FILE__;
            $addon->extension_name          = 'WP Latest Posts Addon';
            $addon->extension_slug          = 'wp-latest-posts-addon';
            $addon->text_domain             = 'wp-latest-posts-addon';
            $addon->language_file           = plugin_dir_path(__FILE__) . 'languages';
            $addon->language_file           .= DIRECTORY_SEPARATOR . 'wp-latest-posts-addon-en_US.mo';
            $addons[$addon->extension_slug] = $addon;

            return $addons;
        }
    );

/**
 * Class includes
 **/
    require_once dirname(__FILE__) . '/inc/wplp-addon-admin.inc.php';        // custom classes
    require_once dirname(__FILE__) . '/inc/wplp-addon-view-settings.php';
    require_once dirname(__FILE__) . '/inc/compatibility/class.content_acf.php';

/**
 * Just fill up necessary settings in the configuration array
 * to create a new custom plugin instance...
 */
    global $wpcu_wpfn_p;
    $wpcu_wpfn_p = new WPLPAddonAdmin(
        array(
            'version' => '4.6.5',
            'translation_domain' => 'wp-latest-posts', // must be copied in the widget class!!!
            'plugin_file' => __FILE__,
        )
    );
    if (is_admin()) {
    //config section
        if (!defined('JU_BASE')) {
            define('JU_BASE', 'https://www.joomunited.com/');
        }

        $remote_updateinfo = JU_BASE . 'juupdater_files/wp-latest-posts-addon.json';
    //end config

        include 'juupdater/juupdater.php';
        $UpdateChecker = Jufactory::buildUpdateChecker(
            $remote_updateinfo,
            __FILE__
        );
    }


    if (!function_exists('wplpAddonPluginCheckForUpdates')) {
        /**
         * Plugin check for updates
         *
         * @param object $update      Update
         * @param array  $plugin_data Plugin data
         * @param string $plugin_file Plugin file
         *
         * @return array|boolean|object
         */
        function wplpAddonPluginCheckForUpdates($update, $plugin_data, $plugin_file)
        {
            if ($plugin_file !== 'wp-latest-posts-addon/wp-latest-posts-addon.php') {
                return $update;
            }

            if (empty($plugin_data['UpdateURI']) || !empty($update)) {
                return $update;
            }

            $response = wp_remote_get($plugin_data['UpdateURI']);

            if (is_wp_error($response) || empty($response['body'])) {
                return $update;
            }

            $custom_plugins_data = json_decode($response['body'], true);

            $package = null;
            $token = get_option('ju_user_token');
            if (!empty($token)) {
                $package = $custom_plugins_data['download_url'] . '&token=' . $token . '&siteurl=' . get_option('siteurl');
            }

            return array(
            'version' => $custom_plugins_data['version'],
            'package' => $package
            );
        }
        add_filter('update_plugins_www.joomunited.com', 'wplpAddonPluginCheckForUpdates', 10, 3);
    }
