<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://codeboxr.com
 * @since      1.0.0
 *
 * @package    CBXPoll
 * @subpackage CBXPoll/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    CBXPoll
 * @subpackage CBXPoll/includes
 * @author     codeboxr <info@codeboxr.com>
 */
class CBXPoll_Deactivator
{

    /**
     * Plugin Deactivation method
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function deactivate()
    {
        delete_option('cbxpoll_flush_rewrite_rules');
    }//end method deactivate
}//end class CBXPoll_Deactivator
