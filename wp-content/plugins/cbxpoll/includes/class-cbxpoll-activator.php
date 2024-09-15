<?php

/**
 * Fired during plugin activation
 *
 * @link       https://codeboxr.com
 * @since      1.0.0
 *
 * @package    Cbxpoll
 * @subpackage Cbxpoll/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    CBXPoll
 * @subpackage CBXPoll/includes
 * @author     codeboxr <info@codeboxr.com>
 */
class CBXPoll_Activator
{

    /**
     * Plugin activation method
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function activate()
    {
        CBXPollHelper::install_table();

        add_option('cbxpoll_flush_rewrite_rules', 'true');

        set_transient('cbxpoll_activated_notice', 1);

        if (!function_exists('is_plugin_active')) {
            include_once(ABSPATH.'wp-admin/includes/plugin.php');
        }

        if (in_array('cbxpollproaddon/cbxpollproaddon.php', apply_filters('active_plugins',
                get_option('active_plugins'))) || defined('CBX_POLLPROADDON_PLUGIN_NAME')) {
            //plugin is activated

            $pro_plugin_version = CBX_POLLPROADDON_PLUGIN_VERSION;


            if (version_compare($pro_plugin_version, '1.1.3', '<')) {
                deactivate_plugins('cbxpollproaddon/cbxpollproaddon.php');
                set_transient('cbxpollproaddon_forcedactivated_notice', 1);
            }
        }

    }//end method activate
}//end class CBXPoll_Activator
