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
class CBXPoll_Uninstall
{

    /**
     * Method for uninstall hook
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function uninstall()
    {
        global $wpdb;

        $settings = new CBXPoll_Settings();

        $delete_global_config = $settings->get_option('delete_global_config', 'cbxpoll_tools', 'no');

        if ($delete_global_config == 'yes') {
            $option_prefix = 'cbxpoll_';

            //delete plugin global options
            $option_values = CBXPollHelper::getAllOptionNames();

            foreach ($option_values as $option_value) {
                delete_option($option_value['option_name']);
            }

            //delete tables created by this plugin

            $table_names  = CBXPollHelper::getAllDBTablesList();
            $sql          = "DROP TABLE IF EXISTS ".implode(', ', array_values($table_names));
            $query_result = $wpdb->query($sql);


            global $post;
            //reset total vote count meta for all cbxpoll type post
            $args = array(
                'post_type'      => 'cbxpoll', // Only get the posts
                'posts_per_page' => -1, // Get every post
                'post_status'    => 'any'
            );

            $posts_array = get_posts($args);

            foreach ($posts_array as $post) : setup_postdata($post);
                $poll_id = get_the_ID();

                // Run a loop and update every meta data
                delete_post_meta($poll_id, '_cbxpoll_total_votes', 0);
            endforeach;
            wp_reset_postdata();

            //end reset total vote count meta for all cbxpoll type post

            do_action('cbxpoll_plugin_uninstall', $table_names, $option_prefix);

        }
    }//end method uninstall
}//end class CBXPoll_Uninstall
