<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://codeboxr.com
 * @since      1.0.0
 *
 * @package    CBXPoll
 * @subpackage CBXPoll/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    CBXPoll
 * @subpackage CBXPoll/admin
 * @author     codeboxr <info@codeboxr.com>
 */
class CBXPoll_Admin
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $plugin_name The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current version of this plugin.
     */
    private $version;


    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version Setting api instance.
     */
    private $settings_api;


    /**
     * CBXPoll HTML Session Object.
     *
     * This holds cart items, purchase sessions, and anything else stored in the session.
     *
     * @var object|CBXPOLL_Session
     * @since 1.5
     */
    public $session;

    /**
     * Initialize the class and set its properties.
     *
     * @param  string  $plugin_name  The name of this plugin.
     * @param  string  $version  The version of this plugin.
     *
     * @since    1.0.0
     *
     */
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;

        $this->version = $version;
        if (defined('WP_DEBUG')) {
            $this->version = current_time('timestamp'); //for development time only
        }

        $this->settings_api = new CBXPoll_Settings();
        $this->session      = new CBXPoll_Session();
    }//end constructor

    public function init_cbxpoll_type()
    {
        CBXPollHelper::create_cbxpoll_post_type();

        // Check the option we set on activation.
        if (get_option('cbxpoll_flush_rewrite_rules') == 'true') {
            flush_rewrite_rules();
            delete_option('cbxpoll_flush_rewrite_rules');
        }

    }//end method init_cbxpoll_type

    /**
     * Register and enqueue admin-specific style sheet.
     *
     *
     * @return    null    Return early if no settings page is registered.
     * @since     1.0.0
     *
     */
    public function enqueue_styles($hook)
    {
        $page = isset($_GET['page']) ? esc_attr(wp_unslash($_GET['page'])) : '';

        global $post_type;


        //register css files
        //wp_register_style( 'cbxpoll-chosen', plugins_url( '../assets/css/chosen.min.css', __FILE__ ), array(), CBX_POLL_PLUGIN_VERSION );
        wp_register_style('select2', plugin_dir_url(__FILE__).'../assets/js/select2/css/select2.min.css', array(),
            $this->version);

        wp_register_style('cbxpoll-ui-styles', plugins_url('../assets/css/ui-lightness/jquery-ui.min.css', __FILE__),
            array(), CBX_POLL_PLUGIN_VERSION);
        wp_register_style('cbxpoll-ui-styles-timepicker',
            plugins_url('../assets/js/jquery-ui-timepicker-addon.min.css', __FILE__), array(), CBX_POLL_PLUGIN_VERSION);

        wp_register_style('cbxpoll-ply-styles', plugins_url('../assets/css/ply.css', __FILE__), array(),
            CBX_POLL_PLUGIN_VERSION);
        wp_register_style('cbxpoll-switchery-styles', plugins_url('../assets/css/switchery.min.css', __FILE__), array(),
            CBX_POLL_PLUGIN_VERSION);

        //poll admin edit and listing

        wp_register_style('cbxpoll-admin-styles', plugins_url('../assets/css/cbxpoll_admin.css', __FILE__), array(
            'select2',
            'cbxpoll-ui-styles',
            'cbxpoll-ui-styles-timepicker',
            'cbxpoll-ply-styles',
            'cbxpoll-switchery-styles'
        ), CBX_POLL_PLUGIN_VERSION);


        if (in_array($hook, array('edit.php', 'post.php', 'post-new.php')) && 'cbxpoll' == $post_type) {
            //now enqueue css files
            //wp_enqueue_style( 'cbxpoll-chosen' );
            wp_enqueue_style('select2');
            wp_enqueue_style('cbxpoll-ui-styles');
            wp_enqueue_style('cbxpoll-ui-styles-timepicker');

            wp_enqueue_style('cbxpoll-ply-styles');
            wp_enqueue_style('cbxpoll-switchery-styles');

            wp_enqueue_style('thickbox');

            wp_enqueue_style('cbxpoll-admin-styles');

            do_action('cbxpolladmin_custom_style');
        }

        //poll setting
        wp_register_style('cbxpoll-admin-setting', plugins_url('../assets/css/cbxpoll-admin-setting.css', __FILE__),
            array(
                'wp-color-picker',
                'select2',
                'cbxpoll-ui-styles',
                'cbxpoll-ui-styles-timepicker'
            ), CBX_POLL_PLUGIN_VERSION);
        if ($page == 'cbxpollsetting') {

            wp_enqueue_style('wp-color-picker');
            wp_enqueue_style('select2');
            wp_enqueue_style('cbxpoll-ui-styles');
            wp_enqueue_style('cbxpoll-ui-styles-timepicker');


            wp_enqueue_style('cbxpoll-admin-setting');
        }

        if (($hook == 'post.php' || $hook == 'post-new.php' || $hook == 'edit.php') && $post_type == 'cbxpoll' || $page == 'cbxpollsetting' ||
            $page == 'cbxpollresult' || $page == 'cbxpoll-help-support') {
            wp_register_style('cbxpoll-branding', plugin_dir_url(__FILE__).'../assets/css/cbxpoll-branding.css',
                array(),
                $this->version);
            wp_enqueue_style('cbxpoll-branding');
        }

    }//end of method enqueue_styles


    /**
     * Register and enqueue admin-specific JavaScript.
     *
     * @return    null    Return early if no settings page is registered.
     * @since     1.0.0
     *
     */
    public function enqueue_scripts($hook)
    {
        $page = isset($_GET['page']) ? esc_attr(wp_unslash($_GET['page'])) : '';

        global $post_type;

        wp_register_script('cbxpoll-jseventManager', plugins_url('../assets/js/cbxpolljsactionandfilter.js', __FILE__),
            array(), CBX_POLL_PLUGIN_VERSION, true);

        //wp_register_script( 'cbxpoll-choosen-script', plugins_url( '../assets/js/chosen.jquery.min.js', __FILE__ ), array( 'jquery' ), CBX_POLL_PLUGIN_VERSION, true );
        wp_register_script('select2', plugin_dir_url(__FILE__).'../assets/js/select2/js/select2.min.js',
            array('jquery'), $this->version, true);


        wp_register_script('cbxpoll-ui-time-script',
            plugins_url('../assets/js/jquery-ui-timepicker-addon.js', __FILE__), array(
                'jquery',
                'jquery-ui-datepicker'
            ), CBX_POLL_PLUGIN_VERSION, true);

        wp_register_script('cbxpoll-plyjs', plugins_url('../assets/js/ply.min.js', __FILE__), array('jquery'),
            CBX_POLL_PLUGIN_VERSION, true);
        wp_register_script('cbxpoll-switcheryjs', plugins_url('../assets/js/switchery.min.js', __FILE__),
            array('jquery'), CBX_POLL_PLUGIN_VERSION, true);


        //if ((in_array($hook, array('edit.php', 'post.php', 'post-new.php')) && 'cbxpoll' == $post_type) || ($hook == 'cbxpollsetting')) {

        //admin poll listing
        wp_register_script('cbxpolladminlisting', plugins_url('../assets/js/cbxpoll_admin_listing.js', __FILE__), array(
            'cbxpoll-jseventManager',
            'jquery',
            'cbxpoll-switcheryjs',
            'cbxpoll-plyjs',
            //'cbxpoll-ui-time-script',
            //'cbxpoll-choosen-script',
            'cbxpoll-switcheryjs',
            //'select2',
        ), CBX_POLL_PLUGIN_VERSION, true);

        if (in_array($hook, array('edit.php')) && 'cbxpoll' == $post_type) {
            //adding translation and other variables from php to js for single post edit screen
            $admin_listing_arr = array(
                'copy'                => esc_html__('Click to copy', 'cbxpoll'),
                'copied'              => esc_html__('Copied to clipboard', 'cbxpoll'),
                'remove_label'        => esc_html__('Remove', 'cbxpoll'),
                'move_label'          => esc_html__('Move', 'cbxpoll'),
                'move_title'          => esc_html__('Drag and Drop to reorder answers', 'cbxpoll'),
                'deleteconfirm'       => esc_html__('Are you sure to delete this item?', 'cbxpoll'),
                'deleteconfirmok'     => esc_html__('Sure', 'cbxpoll'),
                'deleteconfirmcancel' => esc_html__('Oh! No', 'cbxpoll'),
                'ajaxurl'             => admin_url('admin-ajax.php'),
                'nonce'               => wp_create_nonce('cbxpoll'),
                'please_select'       => esc_html__('Please select', 'cbxpoll')
            );

            wp_localize_script('cbxpolladminlisting', 'cbxpolladminlistingObj', $admin_listing_arr);

            wp_enqueue_script('cbxpoll-jseventManager');
            wp_enqueue_script('jquery');
            //wp_enqueue_style( 'wp-color-picker' );

            //wp_enqueue_script( 'wp-color-picker' );
            //wp_enqueue_script( 'media-upload' );

            //wp_enqueue_script( 'cbxpoll-choosen-script' );
            //wp_enqueue_script( 'select2' );
            //wp_enqueue_script( 'cbxpoll-ui-time-script' );

            wp_enqueue_script('cbxpoll-plyjs');
            wp_enqueue_script('cbxpoll-switcheryjs');

            wp_enqueue_script('cbxpolladminlisting');

            do_action('cbxpolladmin_custom_script');
        }


        //admin poll single edit
        wp_register_script('cbxpolladminsingle', plugins_url('../assets/js/cbxpoll-admin-single.js', __FILE__), array(
            'cbxpoll-jseventManager',
            'jquery',
            'wp-color-picker',
            //'media-upload',
            'jquery-ui-core',
            'jquery-ui-datepicker',
            'jquery-ui-sortable',
            'select2',
            'cbxpoll-ui-time-script',
            'cbxpoll-plyjs',
            'cbxpoll-switcheryjs',
        ), CBX_POLL_PLUGIN_VERSION, true);

        if (in_array($hook, array('post.php', 'post-new.php')) && 'cbxpoll' == $post_type) {

            if (!class_exists('_WP_Editors', false)) {
                require(ABSPATH.WPINC.'/class-wp-editor.php');
            }

            wp_enqueue_script('cbxpoll-jseventManager');
            wp_enqueue_script('jquery');
            //wp_enqueue_style( 'wp-color-picker' );
            //wp_enqueue_style( 'thickbox' );
            wp_enqueue_script('wp-color-picker');
            //wp_enqueue_script( 'media-upload' );
            wp_enqueue_media();

            wp_enqueue_style('jquery-ui-core'); //jquery ui core
            wp_enqueue_style('jquery-ui-datepicker'); //jquery ui datepicker
            wp_enqueue_style('jquery-ui-sortable'); //jquery ui sortable

            //wp_enqueue_script( 'cbxpoll-choosen-script' );
            wp_enqueue_script('select2');
            wp_enqueue_script('cbxpoll-ui-time-script');

            wp_enqueue_script('cbxpoll-plyjs');
            wp_enqueue_script('cbxpoll-switcheryjs');


            //adding translation and other variables from php to js for single post edit screen
            $admin_single_arr = array(
                'copy'                  => esc_html__('Click to copy', 'cbxpoll'),
                'copied'                => esc_html__('Copied to clipboard', 'cbxpoll'),
                'remove_label'          => esc_html__('Remove', 'cbxpoll'),
                'move_label'            => esc_html__('Move', 'cbxpoll'),
                'move_title'            => esc_html__('Drag and Drop to reorder answers', 'cbxpoll'),
                'answer_label'          => esc_html__('Answer', 'cbxpoll'),
                'deleteconfirm'         => esc_html__('Are you sure to delete this item?', 'cbxpoll'),
                'deleteconfirmok'       => esc_html__('Sure', 'cbxpoll'),
                'deleteconfirmcancel'   => esc_html__('Oh! No', 'cbxpoll'),
                'ajaxurl'               => admin_url('admin-ajax.php'),
                'nonce'                 => wp_create_nonce('cbxpoll'),
                'teeny_editor_settings' => array(
                    'teeny'         => true,
                    'textarea_name' => '',
                    'textarea_rows' => 10,
                    'media_buttons' => false,
                    'editor_class'  => ''
                ),
                'please_select'         => esc_html__('Please select', 'cbxpoll')
            );

            wp_localize_script('cbxpolladminsingle', 'cbxpolladminsingleObj', $admin_single_arr);

            wp_enqueue_script('cbxpolladminsingle');

            do_action('cbxpolladmin_single_custom_script');
        }

        //poll setting
        wp_register_script('cbxpoll-admin-setting', plugins_url('../assets/js/cbxpoll-admin-setting.js', __FILE__),
            array(
                'jquery',
                'select2',
                'wp-color-picker'
            ), CBX_POLL_PLUGIN_VERSION);

        if ($page == 'cbxpollsetting') {

            wp_enqueue_script('jquery');
            wp_enqueue_script('select2');
            wp_enqueue_script('wp-color-picker');
            wp_enqueue_media();


            $cbxpoll_admin_setting_arr = array(
                'ajaxurl'       => admin_url('admin-ajax.php'),
                'nonce'         => wp_create_nonce('cbxpoll'),
                'please_select' => esc_html__('Please select', 'cbxpoll')
            );
            wp_localize_script('cbxpoll-admin-setting', 'cbxpolladminsettingObj', $cbxpoll_admin_setting_arr);
            wp_enqueue_script('cbxpoll-admin-setting');
        }

        //header scroll
        wp_register_script('cbxpoll-scroll', plugins_url('../assets/js/cbxpoll-scroll.js', __FILE__), array('jquery'),
            CBX_POLL_PLUGIN_VERSION);
        if ($page == 'cbxpollsetting' || $page == 'cbxpoll-help-support') {
            wp_enqueue_script('jquery');
            wp_enqueue_script('cbxpoll-scroll');
        }

    }//end method enqueue_scripts

    /**
     * on admin init initialize setting and handle cbxpoll type post delete
     */
    public function admin_init()
    {

        //init setting api
        $this->settings_api->set_sections($this->get_setting_sections());
        $this->settings_api->set_fields($this->get_setting_fields());

        //initialize them
        $this->settings_api->admin_init();


        //handle cbxpoll type post delete
        //add_action( 'delete_post', array( $this, 'on_poll_delete_vote_delete' ), 10 );
        add_action('before_delete_post', array($this, 'on_poll_delete_vote_delete'), 10);
    }//end method admin_init

    /**
     * Delete vote on poll type post delete
     *
     * @param  type  $postid
     */
    function on_poll_delete_vote_delete($poll_id)
    {
        global $wpdb;

        //global $post_type;

        /*if ( $post_type !== 'cbxpoll' ) {
			return;
		}*/


        $votes_name = CBXPollHelper::cbx_poll_table_name();

        $poll_votes = CBXPollHelper::getAllVotes('id', 'desc', -1, 1, $poll_id);
        if (is_array($poll_votes) && sizeof($poll_votes) > 0) {
            //let's delete all single vote and allow hooks
            foreach ($poll_votes as $log_info) {
                $id            = intval($log_info['id']);
                $sql           = $wpdb->prepare("DELETE FROM $votes_name WHERE id=%d", $id);
                $delete_status = $wpdb->query($sql);

                do_action('cbxpoll_vote_delete_before', $log_info);

                if ($delete_status !== false) {
                    do_action('cbxpoll_vote_delete_after', $log_info);

                }//end if delete success
            }
        }//end if array

    }//end method on_poll_delete_vote_delete

    /**
     * On user delete delete user's vote
     *
     * @param $user_id
     */
    public function on_user_delete_vote_delete($user_id)
    {
        global $wpdb;

        $user_id    = intval($user_id);
        $votes_name = CBXPollHelper::cbx_poll_table_name();

        $poll_votes = CBXPollHelper::getAllVotesByUser($user_id, 'id', 'desc', -1, 1);

        if (is_array($poll_votes) && sizeof($poll_votes) > 0) {
            //let's delete all single vote and allow hooks
            foreach ($poll_votes as $log_info) {
                $id            = intval($log_info['id']);
                $poll_id       = intval($log_info['poll_id']);
                $sql           = $wpdb->prepare("DELETE FROM $votes_name WHERE id=%d", $id);
                $delete_status = $wpdb->query($sql);

                do_action('cbxpoll_vote_delete_before', $log_info);

                if ($delete_status !== false) {
                    do_action('cbxpoll_vote_delete_after', $log_info);

                    if (intval($log_info['published']) == 1) {
                        //if already published vote then readjust the total votes
                        $poll_total = absint(get_post_meta($poll_id, '_cbxpoll_total_votes',
                            true)); //at least a single vote


                        $poll_total = $poll_total - 1;
                        update_post_meta($poll_id, '_cbxpoll_total_votes',
                            absint($poll_total)); //can help for showing most voted poll
                    }
                }//end if delete success
            }
        }//end if array
    }//end method on_user_delete_vote_delete

    /**
     * CBX Poll Core Global Setting Sections
     *
     * @return mixed|void
     */
    public function get_setting_sections()
    {
        $sections = array(
            array(
                'id'    => 'cbxpoll_global_settings',
                'title' => esc_html__('Poll Default Settings', 'cbxpoll')
            ),
	        array(
		        'id'    => 'cbxpoll_slugs_settings',
		        'title' => esc_html__('Urls & Slugs', 'cbxpoll')
	        ),
            array(
                'id'    => 'cbxpoll_email_setting',
                'title' => esc_html__('Email Setting', 'cbxpoll')
            ),
            array(
                'id'    => 'cbxpoll_tools',
                'title' => esc_html__('Tools', 'cbxpoll')
            )
        );

        return apply_filters('cbxpoll_setting_sections', $sections);
    }//end method get_setting_sections

    /**
     * CBX Poll Setting Core Fields
     *
     * @return mixed|void
     */
    public function get_setting_fields()
    {

	    $gust_login_forms = CBXPollHelper::guest_login_forms();

        $roles = CBXPollHelper::user_roles(false, true); //

        $reset_data_link = add_query_arg('cbxpoll_fullreset', 1, admin_url('edit.php?post_type=cbxpoll&page=cbxpollsetting'));

        $table_names = CBXPollHelper::getAllDBTablesList();

        $table_html = '<p><a id="cbxpoll_info_trig" href="#">'.esc_html__('Show/hide details', 'cbxpoll').'</a></p>';

        $table_html .= '<div id="cbxpoll_resetinfo" style="display: none;">';

        $table_html .= '<p style="margin-bottom: 15px;" class="cbxpoll_tools_info"><strong>'.esc_html__('Following database tables will be reset/deleted.',
                'cbxpoll').'</strong></p>';

        $table_html .= '<table class="widefat widethin cbxpoll_table_data" style="margin-bottom: 20px;">
	<thead>
	<tr>
		<th class="row-title">'.esc_attr__('Table Name', 'cbxpoll').'</th>
		<th>'.esc_attr__('Table Name in DB', 'cbxpoll').'</th>		
	</tr>
	</thead>';

        $table_html .= '<tbody>';

        $i = 0;
        foreach ($table_names as $key => $value) {
            $alternate_class = ($i % 2 == 0) ? 'alternate' : '';
            $i++;
            $table_html .= '<tr class="'.esc_attr($alternate_class).'">
									<td class="row-title"><label for="tablecell">'.esc_attr($key).'</label></td>
									<td>'.esc_attr($value).'</td>									
								</tr>';
        }

        $table_html .= '</tbody>';
        $table_html .= '<tfoot>
	<tr>
		<th class="row-title">'.esc_attr__('Table Name', 'cbxpoll').'</th>
		<th>'.esc_attr__('Table Name in DB', 'cbxpoll').'</th>		
	</tr>
	</tfoot>
</table>';

        $table_html .= '<p style="margin-bottom: 15px;" class="cbxpoll_tools_info"><strong>'.esc_html__('Following option values created by this plugin(including addon) from wordpress core option table',
                'cbxpoll').'</strong></p>';


        $option_values = CBXPollHelper::getAllOptionNames();

        $table_html .= '<table class="widefat widethin cbxpoll_table_data">
	<thead>
	<tr>
		<th class="row-title">'.esc_attr__('Option Name', 'cbxpoll').'</th>
		<th>'.esc_attr__('Option ID', 'cbxpoll').'</th>		
		<th>'.esc_attr__('Option Value', 'cbxpoll').'</th>		
	</tr>
	</thead>';

        $table_html .= '<tbody>';
        $i          = 0;
        foreach ($option_values as $key => $value) {

            $alternate_class = ($i % 2 == 0) ? 'alternate' : '';
            $i++;
            $table_html .= '<tr class="'.esc_attr($alternate_class).'">
									<td class="row-title"><label for="tablecell">'.esc_attr($value['option_name']).'</label></td>
									<td>'.esc_attr($value['option_id']).'</td>
									<td><code style="overflow-wrap: break-word; word-break: break-all;">'.$value['option_value'].'</code></td>
								</tr>';
        }

        $table_html .= '</tbody>';
        $table_html .= '<tfoot>
	<tr>
		<th class="row-title">'.esc_attr__('Option Name', 'cbxpoll').'</th>
		<th>'.esc_attr__('Option ID', 'cbxpoll').'</th>		
		<th>'.esc_attr__('Option Value', 'cbxpoll').'</th>		
	</tr>
	</tfoot>
</table>';

        $table_html .= '</div>';


        $poll_display_methods = CBXPollHelper::cbxpoll_display_options();
        $poll_display_methods = CBXPollHelper::cbxpoll_display_options_linear($poll_display_methods);


        $fields = array(
            'cbxpoll_global_settings' => apply_filters('cbxpoll_global_general_fields', array(
                'poll_defaults_heading' => array(
                    'name'    => 'poll_defaults_heading',
                    'label'   => esc_html__('Poll Default Settings', 'cbxpoll'),
                    'type'    => 'heading',
                    'default' => '',
                ),
                'result_chart_type'     => array(
                    'name'    => 'result_chart_type',
                    'label'   => esc_html__('Result Chart Style', 'cbxpoll'),
                    'desc'    => __('Poll result display styles, text and polar area display type are free, you can buy more display option from <a href="https://codeboxr.com/product/cbx-poll-for-wordpress/" target="_blank">here</a>',
                        'cbxpoll'),
                    'type'    => 'select',
                    'default' => 'text',
                    'options' => $poll_display_methods,
                ),
                'poll_multivote'        => array(
                    'name'    => 'poll_multivote',
                    'label'   => esc_html__('Enable Multi Choice', 'cbxpoll'),
                    'desc'    => esc_html__('Can user vote multiple option', 'cbxpoll'),
                    'type'    => 'radio',
                    'default' => '0',
                    'options' => array(
                        '1' => esc_html__('Yes', 'cbxpoll'),
                        '0' => esc_html__('No', 'cbxpoll')
                    )
                ),
                'user_roles'            => array(
                    'name'        => 'user_roles',
                    'label'       => esc_html__('Who Can Vote', 'cbxpoll'),
                    'desc'        => esc_html__('which user role will have vote capability', 'cbxpoll'),
                    'type'        => 'multiselect',
                    //'optgroup' => 0,
                    'default'     => array(
                        'administrator',
                        'editor',
                        'author',
                        'contributor',
                        'subscriber',
                        'guest'
                    ),
                    'options'     => $roles,
                    'optgroup'    => 1,
                    'placeholder' => esc_html__('Select user roles', 'cbxpoll')
                ),

                'content'                   => array(
                    'name'    => 'content',
                    'label'   => esc_html__('Show Poll Description', 'cbxpoll'),
                    'desc'    => esc_html__('Show description from poll post type', 'cbxpoll'),
                    'type'    => 'radio',
                    'default' => 1,
                    'options' => array(
                        '1' => esc_html__('Yes', 'cbxpoll'),
                        '0' => esc_html__('No', 'cbxpoll')
                    )
                ),
                'never_expire'              => array(
                    'name'    => 'never_expire',
                    'label'   => esc_html__('Never Expire', 'cbxpoll'),
                    'desc'    => esc_html__('If set polls will never expire. You can also set individual poll end time.',
                        'cbxpoll'),
                    'type'    => 'radio',
                    'default' => 0,
                    'options' => array(
                        '1' => esc_html__('Yes', 'cbxpoll'),
                        '0' => esc_html__('No', 'cbxpoll')
                    )
                ),
                'show_result_before_expire' => array(
                    'name'    => 'show_result_before_expire',
                    'label'   => esc_html__('Show result before expires', 'cbxpoll'),
                    'desc'    => esc_html__('Select if you want poll to show result before expires. After expires the result will be shown always. Please check it if poll never expires.',
                        'cbxpoll'),
                    'type'    => 'radio',
                    'default' => 1, //new change 0 -> 1
                    'options' => array(
                        '1' => esc_html__('Yes', 'cbxpoll'),
                        '0' => esc_html__('No', 'cbxpoll')
                    )
                ),
                'cookiedays'                => array(
                    'name'        => 'cookiedays',
                    'label'       => esc_html__('Cookie Expiration Days', 'cbxpoll'),
                    'desc'        => esc_html__('For guest user cookie is placed in browser, For how many days cookie will not expire. Default is 30 days',
                        'cbxpoll'),
                    'type'        => 'number',
                    'default'     => '30',
                    'placeholder' => esc_html__('Number of days', 'cbxpoll')

                ),
                'logmethod'                 => array(
                    'name'    => 'logmethod',
                    'label'   => esc_html__('Log Method', 'cbxpoll'),
                    'desc'    => __('Logging method. [<strong> Note:</strong> Please Select at least one or a guest user will vote multiple time for a poll.]',
                        'cbxpoll'),
                    'type'    => 'select',
                    'default' => 'both',
                    'options' => array(
                        'ip'     => esc_html__('IP', 'cbxpoll'),
                        'cookie' => esc_html__('Cookie', 'cbxpoll'),
                        'both'   => esc_html__('Both(IP or cookie any one)', 'cbxpoll'),
                    )
                ),
                'answer_grid_list'          => array(
                    'name'    => 'answer_grid_list',
                    'label'   => esc_html__('Answer Display Format', 'cbxpoll'),
                    'desc'    => esc_html__('Traditionally answer is shown as vericala list but sometimes grid presentation better for user experience.',
                        'cbxpoll'),
                    'type'    => 'radio',
                    'default' => 0,
                    'options' => array(
                        '0' => esc_html__('List', 'cbxpoll'),
                        '1' => esc_html__('Grid', 'cbxpoll')
                    )
                ),
                'allow_guest_sign'         => array(
	                'name'    => 'allow_guest_sign',
	                'label'   => esc_html__( 'Allow Guest User to Sign', 'cbxpoll' ),
	                'type'    => 'checkbox',
	                'default' => 'on',
                ),
                'guest_login_form'         => array(
	                'name'    => 'guest_login_form',
	                'label'   => esc_html__( 'Guest User Login Form', 'cbxpoll' ),
	                'desc'    => esc_html__( 'Default guest user is shown wordpress core login form. Pro addon helps to integrate 3rd party plugins like woocommerce, restrict content pro etc.', 'cbxpoll' ),
	                'type'    => 'select',
	                'default' => 'wordpress',
	                'options' => $gust_login_forms
                ),
                'guest_show_register'      => array(
	                'name'    => 'guest_show_register',
	                'label'   => esc_html__( 'Show Register link to guest', 'cbxpoll' ),
	                'desc'    => esc_html__( 'Show register link to guest, depends on if registration is enabled in wordpress core', 'cbxpoll' ),
	                'type'    => 'radio',
	                'default' => 1,
	                'options' => array(
		                1 => esc_html__( 'Yes', 'cbxpoll' ),
		                0 => esc_html__( 'No', 'cbxpoll' ),
	                ),
                ),
            )),
            'cbxpoll_slugs_settings' => apply_filters('cbxpoll_global_slugs_fields', array(
	            'slugs_heading' => array(
		            'name'    => 'slugs_heading',
		            'label'   => esc_html__('Poll Slugs and Urls', 'cbxpoll'),
		            'type'    => 'heading',
		            'default' => '',
	            ),
	            'slugs_subheading' => array(
		            'name'    => 'slugs_subheading',
		            'label'   => sprintf(__('Please save <a target="_blank" href="%s">permalink</a> once after changing any slug.', 'cbxpoll'), admin_url('options-permalink.php')),
		            'type'    => 'subheading',
		            'default' => '',
	            ),
	            'slug_single'          => array(
		            'name'    => 'slug_single',
		            'label'   => esc_html__('Poll details url slug', 'cbxpoll'),
		            'desc'    => esc_html__('Slug used for permalink for poll details url',   'cbxpoll'),
		            'type'    => 'text',
		            'default' => 'cbxpoll',
	            ),
	            'slug_archive'          => array(
		            'name'    => 'slug_archive',
		            'label'   => esc_html__('Poll archive srl slug', 'cbxpoll'),
		            'desc'    => esc_html__('Slug used for permalink for poll archive url',   'cbxpoll'),
		            'type'    => 'text',
		            'default' => 'cbxpoll',
	            ),
            )),
            'cbxpoll_email_setting'   => apply_filters('cbxpoll_global_email_fields', array(
                'email_setting_heading' => array(
                    'name'    => 'email_setting_heading',
                    'label'   => esc_html__('Default Email Settings', 'cbxpoll'),
                    'type'    => 'heading',
                    'default' => '',
                ),
                'headerimage'           => array(
                    'name'     => 'headerimage',
                    'label'    => esc_html__('Header Image', 'cbxpoll'),
                    'desc'     => esc_html__('Url To email you want to show as email header. Upload Image by media uploader.',
                        'cbxpoll'),
                    'type'     => 'file',
                    'default'  => '',
                    'desc_tip' => true,
                ),

                'basecolor'           => array(
                    'name'     => 'basecolor',
                    'label'    => esc_html__('Base Color', 'cbxpoll'),
                    'desc'     => esc_html__('The base color of the email.', 'cbxpoll'),
                    'type'     => 'color',
                    'default'  => '#557da1',
                    'desc_tip' => true,
                ),
                'backgroundcolor'     => array(
                    'name'     => 'backgroundcolor',
                    'label'    => esc_html__('Background Colour', 'cbxpoll'),
                    'desc'     => esc_html__('The background color of the email.', 'cbxpoll'),
                    'type'     => 'color',
                    'default'  => '#f5f5f5',
                    'desc_tip' => true,
                ),
                'bodybackgroundcolor' => array(
                    'name'     => 'bodybackgroundcolor',
                    'label'    => esc_html__('Body Background Color', 'cbxpoll'),
                    'desc'     => esc_html__('The background colour of the main body of email.', 'cbxpoll'),
                    'type'     => 'color',
                    'default'  => '#fdfdfd',
                    'desc_tip' => true,
                ),
                'bodytextcolor'       => array(
                    'name'     => 'bodytextcolor',
                    'label'    => esc_html__('Body Text Color', 'cbxpoll'),
                    'desc'     => esc_html__('The body text colour of the main body of email.', 'cbxpoll'),
                    'type'     => 'color',
                    'default'  => '#505050',
                    'desc_tip' => true,
                ),
                'footertext'          => array(
                    'name'     => 'footertext',
                    'label'    => esc_html__('Footer Text', 'cbxpoll'),
                    'desc'     => esc_html__('The text to appear at the email footer. Syntax available - {sitename}',
                        'cbxpoll'),
                    'type'     => 'wysiwyg',
                    'default'  => '{sitename}',
                    'desc_tip' => true,
                )
            )),
            'cbxpoll_tools'           => apply_filters('cbxpoll_global_tools_fields', array(
                'tools_heading'        => array(
                    'name'    => 'tools_heading',
                    'label'   => esc_html__('Tools Settings', 'cbxpoll'),
                    'type'    => 'heading',
                    'default' => '',
                ),
                'delete_global_config' => array(
                    'name'     => 'delete_global_config',
                    'label'    => esc_html__('On Uninstall delete plugin data', 'cbxpoll'),
                    'desc'     => '<p>'.__('Delete Global Config data and custom table created by this plugin on uninstall.',
                            'cbxpoll').' '.__('Details table information is <a href="#cbxpoll_plg_gfig_info">here</a>',
                            'cbxpoll').'</p>'.'<p>'.__('<strong>Please note that this process can not be undone and it is recommended to keep full database backup before doing this.</strong>',
                            'cbxpoll').'</p>',
                    'type'     => 'radio',
                    'options'  => array(
                        'yes' => esc_html__('Yes', 'cbxpoll'),
                        'no'  => esc_html__('No', 'cbxpoll'),
                    ),
                    'default'  => 'no',
                    'desc_tip' => true,
                ),
                'reset_data'           => array(
                    'name'     => 'reset_data',
                    'label'    => esc_html__('Reset all data', 'cbxpoll'),
                    'desc'     => sprintf(__('Reset option values and all tables created by this plugin. 
<a class="button button-primary" onclick="return confirm(\'%s\')" href="%s">Reset Data</a>', 'cbxpoll'),
                            esc_html__('Are you sure to reset all data, this process can not be undone?', 'cbxpoll'),
                            $reset_data_link).$table_html,
                    'type'     => 'html',
                    'default'  => 'off',
                    'desc_tip' => true,
                )
            ))
        );

        return apply_filters('cbxpoll_global_fields', $fields);
    }//end method get_setting_fields

    /**
     *  add setting page menu
     */
    public function admin_menu()
    {
        global $submenu;
        $setting_page_hook = add_submenu_page('edit.php?post_type=cbxpoll', esc_html__('Poll Settings', 'cbxpoll'),
            esc_html__('Settings', 'cbxpoll'), 'manage_options', 'cbxpollsetting', array(
                $this,
                'admin_menu_setting_page'
            ));

        if (isset($submenu['edit.php?post_type=cbxpoll'][5][0])) {
            $submenu['edit.php?post_type=cbxpoll'][5][0] = esc_html__('Polls', 'cbxpoll');
        }

        $hook = add_submenu_page('edit.php?post_type=cbxpoll', esc_html__('Helps & Updates', 'cbxpoll'),
            esc_html__('Helps & Updates', 'cbxpoll'), 'manage_options', 'cbxpoll-help-support', array(
                $this,
                'cbxpoll_helps_updates_display'
            ));

    }//end method admin_menu

    /**
     * Render the settings page for this plugin.
     *
     * @since    1.0.0
     */
    public function admin_menu_setting_page()
    {
        $plugin_data = get_plugin_data(plugin_dir_path(__DIR__).'/../'.CBX_POLL_PLUGIN_BASE_NAME);
        include('partials/settings-display.php');
    }//end method admin_menu_setting_page


    /**
     * Render the help & support page for this plugin.
     *
     * @since    1.0.0
     */
    public function cbxpoll_helps_updates_display()
    {
        $plugin_data = get_plugin_data(plugin_dir_path(__DIR__).'/../'.CBX_POLL_PLUGIN_BASE_NAME);
        include('partials/dashboard.php');
    }//end method cbxpoll_helps_updates_display

    /**
     * cbxpoll type post listing extra cols
     *
     * @param $cbxpoll_columns
     *
     * @return mixed
     *
     */
    public function add_new_poll_columns($cbxpoll_columns)
    {
        $cbxpoll_columns['title']      = esc_html__('Poll Title', 'cbxpoll');
        $cbxpoll_columns['pollstatus'] = esc_html__('Status', 'cbxpoll');
        $cbxpoll_columns['startdate']  = esc_html__('Start Date', 'cbxpoll');
        $cbxpoll_columns['enddate']    = esc_html__('End Date', 'cbxpoll');
        $cbxpoll_columns['date']       = esc_html__('Created', 'cbxpoll');
        $cbxpoll_columns['pollvotes']  = esc_html__('Votes', 'cbxpoll');
        $cbxpoll_columns['shortcode']  = esc_html__('Shortcode', 'cbxpoll');

        return $cbxpoll_columns;
    }//end method add_new_poll_columns

    /**
     * cbxpoll type post listing extra col values
     *
     * @param $column_name
     *
     */
    public function manage_poll_columns($column_name, $post_id)
    {

        global $post;

        //$post_id = $post->ID;

        $end_date     = get_post_meta($post_id, '_cbxpoll_end_date', true);
        $start_date   = get_post_meta($post_id, '_cbxpoll_start_date', true);
        $never_expire = intval(get_post_meta($post_id, '_cbxpoll_never_expire', true));
        $total_votes  = absint(get_post_meta($post_id, '_cbxpoll_total_votes', true));

        switch ($column_name) {

            case 'pollstatus':
                // Get number of images in gallery
                if ($never_expire == 1) {
                    if (new DateTime($start_date) > new DateTime()) {
                        echo '<span class="dashicons dashicons-calendar"></span> '.esc_html__('Yet to Start',
                                'cbxpoll'); //
                    } else {
                        echo '<span class="dashicons dashicons-yes"></span> '.esc_html__('Active', 'cbxpoll');
                    }

                } else {
                    if (new DateTime($start_date) > new DateTime()) {
                        echo '<span class="dashicons dashicons-calendar"></span> '.__('Yet to Start', 'cbxpoll'); //
                    } else {
                        if (new DateTime($start_date) <= new DateTime() && new DateTime($end_date) > new DateTime()) {
                            echo '<span class="dashicons dashicons-yes"></span> '.esc_html__('Active', 'cbxpoll');
                        } else {
                            if (new DateTime($end_date) <= new DateTime()) {
                                echo '<span class="dashicons dashicons-lock"></span> '.esc_html__('Expired', 'cbxpoll');
                            }
                        }
                    }
                }
                break;
            case 'startdate':
                echo $start_date;
                break;
            case 'enddate':
                echo $end_date;
                break;
            case 'pollvotes':
                echo apply_filters('cbxpoll_admin_listing_votes', $total_votes, $post_id);
                break;
            case 'shortcode':
                echo '<span id="cbxpollshortcode-'.$post_id.'" class="cbxpollshortcode cbxpollshortcode-'.$post_id.'">[cbxpoll id="'.$post_id.'"]</span><span class="cbxpoll_ctp" aria-label="'.esc_html__('Click to copy',
                        'cbxpoll').'" data-balloon-pos="down">&nbsp;</span>';

                break;
            default:
                break;
        } // end switch

    }//end method manage_poll_columns

    /**
     * cbxpoll type post liting extra col sortable
     *
     * make poll table columns sortable
     */
    function cbxpoll_columnsort($columns)
    {
        $columns['startdate']  = 'startdate';
        $columns['enddate']    = 'enddate';
        $columns['pollstatus'] = 'pollstatus';
        $columns['pollvotes']  = 'pollvotes';

        return $columns;
    }//end method cbxpoll_columnsort

    /**
     * Hook custom meta box
     */
    function metaboxes_display()
    {

        //add meta box in left side to show poll setting
        add_meta_box('pollcustom_meta_box',                              // $id
            esc_html__('Poll Options', 'cbxpoll'),  // $title
            array($this, 'metabox_setting_display'),           // $callback
            'cbxpoll',                                      // $page
            'normal',                                           // $context
            'high');                                            // $priority

        //add meta box in right col to show the result
        add_meta_box('pollresult_meta_box',                              // $id
            esc_html__('Poll Result', 'cbxpoll'),  // $title
            array($this, 'metabox_result_display'),           // $callback
            'cbxpoll',                                      // $page
            'side',                                           // $context
            'low');

        //add meta box in right col to show the result
        add_meta_box('pollshortcode_meta_box',                              // $id
            esc_html__('Shortcode', 'cbxpoll'),  // $title
            array($this, 'metabox_shortcode_display'),           // $callback
            'cbxpoll',                                      // $page
            'side',                                           // $context
            'low');
    }//end method metaboxes_display

    /**
     * Meta box display: Setting
     */
    function metabox_setting_display()
    {

        global $post;
        $post_meta_fields = CBXPollHelper::get_meta_fields();


        $poll_postid = isset($post->ID) ? intval($post->ID) : 0;

        $prefix = '_cbxpoll_';

        //$answer_counter = 0;
        $new_index = 0;

        $is_voted     = 0;
        $poll_answers = array();
        $poll_colors  = array();

        if ($poll_postid > 0):
            //$is_voted           = intval( get_post_meta( $poll_postid, '_cbxpoll_is_voted', true ) );
            $is_voted = CBXPollHelper::is_poll_voted($poll_postid);

            $poll_answers       = get_post_meta($poll_postid, '_cbxpoll_answer', true);
            $poll_colors        = get_post_meta($poll_postid, '_cbxpoll_answer_color', true);
            $poll_answers_extra = get_post_meta($poll_postid, '_cbxpoll_answer_extra', true);

            $new_index = isset($poll_answers_extra['answercount']) ? intval($poll_answers_extra['answercount']) : 0;


            if (is_array($poll_answers)) {
                if ($new_index == 0 && sizeof($poll_answers) > 0) {
                    $old_index = $new_index;
                    foreach ($poll_answers as $index => $poll_answer) {
                        if ($index > $old_index) {
                            $old_index = $index;
                        } //find the greater index
                    }

                    if ($old_index > $new_index) {
                        $new_index = intval($old_index) + 1;
                    }
                }
            } else {
                $poll_answers = array();
            }


            wp_nonce_field('cbxpoll_meta_box', 'cbxpoll_meta_box_nonce');

            echo '<div id="cbxpoll_answer_wrap" class="cbxpoll_answer_wrap" data-postid="'.$poll_postid.'">';
            echo '<h4>'.esc_html__('Poll Answers', 'cbxpoll').'</h4>';
            echo __('<p>[<strong>Note : </strong>  <span>Please select different color for each field.]</span></p>',
                'cbxpoll');


            echo '<ul id="cbx_poll_answers_items" class="cbx_poll_answers_items cbx_poll_answers_items_'.$post->ID.'">';


            if (sizeof($poll_answers) > 0) {

                foreach ($poll_answers as $index => $poll_answer) {

                    if (isset($poll_answer)) {
                        $poll_answers_extra[$index] = isset($poll_answers_extra[$index]) ? $poll_answers_extra[$index] : array();
                        echo CBXPollHelper::cbxpoll_answer_field_template($index, $poll_answer, $poll_colors[$index],
                            $is_voted, $poll_answers_extra[$index], $poll_postid);
                    }
                }
            }
            //else {


            //$answer_counter         = 3;
            if (!$is_voted && sizeof($poll_answers) == 0) {
                $default_answers_titles = array(
                    esc_html__('Yes', 'cbxpoll'),
                    esc_html__('No', 'cbxpoll'),
                    esc_html__('No comments', 'cbxpoll')
                );

                $default_answers_colors = array(
                    '#2f7022',
                    '#dd6363',
                    '#e4e4e4'
                );

                $answers_extra = array('type' => 'default');

                foreach ($default_answers_titles as $index => $answers_title) {
                    echo CBXPollHelper::cbxpoll_answer_field_template(intval($index) + $new_index,
                        $default_answers_titles[$index], $default_answers_colors[$index], $is_voted, $answers_extra,
                        $poll_postid);
                }

                $new_index = intval($index) + $new_index + 1;
            }


            //}
            echo '</ul>';
            ?>
            <input type="hidden" id="cbxpoll_answer_extra_answercount" value="<?php echo intval($new_index); ?>"
                   name="_cbxpoll_answer_extra[answercount]"/>
            <?php //if ( ! $is_voted ){
            ?>
            <div class="add-cbx-poll-answer-wrap" data-busy="0" data-postid="<?php echo $poll_postid; ?>">
                <a data-type="default" id="add-cbx-poll-answer-default"
                   class=" button button-primary add-cbx-poll-answer add-cbx-poll-answer-default add-cbx-poll-answer-<?php echo $poll_postid; ?>"><i
                            style="line-height: 25px;"
                            class="dashicons dashicons-media-text"></i> <?php echo esc_html__('Add Text Answer',
                        'cbxpoll'); ?>
                </a>
                <?php do_action('cbxpolladmin_add_answertype', $poll_postid, $new_index); ?>
            </div>
            <?php //}
            ?>
            <br/>

            <?php
            echo '</div>';


            echo '<table class="form-table">';

            foreach ($post_meta_fields as $field) {

                $meta = get_post_meta($poll_postid, $field['id'], true);


                if ($meta == '' && isset($field['default'])) {

                    $meta = $field['default'];
                }

                $label = isset($field['label']) ? $field['label'] : '';

                echo '<tr>';
                echo '<th><label for="'.$field['id'].'">'.$label.'</label></th>';
                echo '<td>';


                switch ($field['type']) {

                    case 'text':
                        echo '<input type="text" class="regular-text" name="'.$field['id'].'" id="'.$field['id'].'-text-'.$poll_postid.'" value="'.$meta.'" size="30" />
			            <span class="description">'.$field['desc'].'</span>';
                        break;
                    case 'number':
                        echo '<input type="number" class="regular-text" name="'.$field['id'].'" id="'.$field['id'].'-number-'.$poll_postid.'" value="'.$meta.'" size="30" />
			            <span class="description">'.$field['desc'].'</span>';
                        break;

                    case 'date':

                        echo '<input type="text" class="cbxpollmetadatepicker" name="'.$field['id'].'" id="'.$field['id'].'-date-'.$poll_postid.'" value="'.$meta.'" size="30" />
			            <span class="description">'.$field['desc'].'</span>';
                        break;

                    case 'colorpicker':


                        echo '<input type="text" class="cbxpoll-colorpicker" name="'.$field['id'].'" id="'.$field['id'].'-date-'.$poll_postid.'" value="'.$meta.'" size="30" />
			             <span class="description">'.$field['desc'].'</span>';
                        break;

                    case 'multiselect':
                        echo '<select name="'.$field['id'].'[]" id="'.$field['id'].'-chosen-'.$poll_postid.'" class="selecttwo-select" multiple="multiple">';
                        if (isset($field['optgroup']) && intval($field['optgroup'])) {

                            foreach ($field['options'] as $optlabel => $data) {
                                echo '<optgroup label="'.$optlabel.'">';
                                foreach ($data as $key => $val) {
                                    echo '<option value="'.$key.'"', is_array($meta) && in_array($key,
                                        $meta) ? ' selected="selected"' : '', ' >'.$val.'</option>';
                                }
                                echo '<optgroup>';
                            }

                        } else {
                            foreach ($field['options'] as $key => $val) {
                                echo '<option value="'.$key.'"', is_array($meta) && in_array($key,
                                    $meta) ? ' selected="selected"' : '', ' >'.$val.'</option>';
                            }
                        }


                        echo '</select><span class="description">'.$field['desc'].'</span>';
                        break;

                    case 'select':
                        echo '<select name="'.$field['id'].'" id="'.$field['id'].'-select-'.$poll_postid.'" class="cb-select select-'.$poll_postid.'">';

                        if (isset($field['optgroup']) && intval($field['optgroup'])) {

                            foreach ($field['options'] as $optlabel => $data) {
                                echo '<optgroup label="'.$optlabel.'">';
                                foreach ($data as $index => $option) {
                                    echo '<option '.(($meta == $index) ? ' selected="selected"' : '').' value="'.$index.'">'.$option.'</option>';
                                }

                            }
                        } else {
                            foreach ($field['options'] as $index => $option) {
                                echo '<option '.(($meta == $index) ? ' selected="selected"' : '').' value="'.$index.'">'.$option.'</option>';
                            }
                        }


                        echo '</select><br/><span class="description">'.$field['desc'].'</span>';
                        break;
                    case 'radio':

                        echo '<fieldset class="radio_fields">
								<legend class="screen-reader-text"><span>input type="radio"</span></legend>';
                        foreach ($field['options'] as $key => $value) {
                            echo '<label title="g:i a" for="'.$field['id'].'-radio-'.$poll_postid.'-'.$key.'">
										<input id="'.$field['id'].'-radio-'.$poll_postid.'-'.$key.'" type="radio" name="'.$field['id'].'" value="'.$key.'" '.(($meta == $key) ? '  checked="checked" ' : '').'  />
										<span>'.$value.'</span>
									</label>';


                        }
                        echo '</fieldset>';
                        echo '<br/><span class="description">'.$field['desc'].'</span>';
                        break;

                    case 'checkbox':
                        echo '<input type="checkbox" name="'.$field['id'].'" id="'.$field['id'].'-checkbox-'.$poll_postid.'" class="cb-checkbox checkbox-'.$poll_postid.'" ', $meta ? ' checked="checked"' : '', '/>
                    <span for="'.$field['id'].'">'.$field['desc'].'</span>';
                        break;
                    case 'checkbox_group':
                        if ($meta == '') {
                            $meta = array();
                            foreach ($field['options'] as $option) {
                                array_push($meta, $option['value']);
                            }
                        }

                        foreach ($field['options'] as $option) {
                            echo '<input type="checkbox" value="'.$option['value'].'" name="'.$field['id'].'[]" id="'.$option['value'].'-mult-chk-'.$poll_postid.'-field-'.$field['id'].'" class="cb-multi-check mult-check-'.$poll_postid.'"', $meta && in_array($option['value'],
                                $meta) ? ' checked="checked"' : '', ' />
                        <label for="'.$option['value'].'">'.$option['label'].'</label><br/>';
                        }

                        echo '<span class="description">'.$field['desc'].'</span>';
                        break;

                }
                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';

        else:
            echo esc_html__('Please save the post once to enter poll answers.', 'cbxpoll');
        endif;

    }//end method metabox_setting_display

    /**
     * Renders metabox in right col to show result
     */
    function metabox_result_display()
    {

        global $post;
        $poll_postid = $post->ID;

        $poll_output = CBXPollHelper::show_single_poll_result($poll_postid, 'shortcode', 'text');

        echo $poll_output;
    }//end method metabox_result_display

    /**
     * Renders metabox in right col to show  shortcode with copy to clipboard
     */
    function metabox_shortcode_display()
    {
        global $post;
        $post_id = $post->ID;

        echo '<span  id="cbxpollshortcode-'.intval($post_id).'" class="cbxpollshortcode cbxpollshortcode-single cbxpollshortcode-'.intval($post_id).'">[cbxpoll id="'.intval($post_id).'"]</span><span class="cbxpoll_ctp" aria-label="'.esc_html__('Click to copy',
                'cbxpoll').'" data-balloon-pos="down">&nbsp;</span>';
        echo '<div class="cbxpollclear"></div>';
    }//end method metabox_shortcode_display

    /**
     * Save cbxpoll metabox
     *
     * @param $post_id
     *
     * @return bool
     */
    function metabox_save($post_id)
    {
        // Check if our nonce is set.
        if (!isset($_POST['cbxpoll_meta_box_nonce'])) {
            return;
        }

        // Verify that the nonce is valid.
        if (!wp_verify_nonce($_POST['cbxpoll_meta_box_nonce'], 'cbxpoll_meta_box')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }


        // Check the user's permissions.
        if (isset($_POST['post_type']) && 'cbxpoll' == $_POST['post_type']) {

            if (!current_user_can('edit_post', $post_id)) {
                return;
            }
        }


        global $post;
        $post   = get_post($post_id);
        $status = $post->post_status;

        $prefix = '_cbxpoll_';

        //handle answer colors
        if (isset($_POST[$prefix.'answer_color'])) {

            $colors = $_POST[$prefix.'answer_color'];
            foreach ($colors as $index => $color) {
                $colors[$index] = CBXPollHelper::sanitize_hex_color($color);
            }

            $unique_color = array_unique($colors);

            if ((count($unique_color)) == (count($colors))) {
                update_post_meta($post_id, $prefix.'answer_color', $colors);
            } else {
                $error = '<div class="error"><p>'.esc_html__('Error: Answer Color repeat error',
                        'cbxpoll').'</p></div>';

                return false;
            }
        } else {
            delete_post_meta($post_id, $prefix.'answer_color');
        }

        //handling extra fields
        if (isset($_POST[$prefix.'answer_extra'])) {
            $extra = $_POST[$prefix.'answer_extra'];
            update_post_meta($post_id, $prefix.'answer_extra', $extra);

        } else {
            delete_post_meta($post_id, $prefix.'answer_extra');
        }

        //handle answer titles
        if (isset($_POST[$prefix.'answer'])) {
            $titles = $_POST[$prefix.'answer'];

            foreach ($titles as $index => $title) {
                $titles[$index] = sanitize_text_field($title);
            }

            update_post_meta($post_id, $prefix.'answer', $titles);
        } else {
            delete_post_meta($post_id, $prefix.'answer');
        }

        $this->metabox_extra_save($post_id);
    }//end method metabox_save

    /**
     * Save cbxpoll meta fields except poll color and titles
     *
     * @param $post_id
     *
     * @return bool|void
     */
    function metabox_extra_save($post_id)
    {
        //global $post_meta_fields;
        $post_meta_fields = CBXPollHelper::get_meta_fields();

        $prefix = '_cbxpoll_';


        $cb_date_array = array();
        foreach ($post_meta_fields as $field) {

            $old = get_post_meta($post_id, $field['id'], true);
            $new = $_POST[$field['id']];

            if (($prefix.'start_date' == $field['id'] && $new == '') || ($prefix.'end_date' == $field['id'] && $new == '')) {

                $cbpollerror = '<div class="notice notice-error inline"><p>'.esc_html__('Error:: Start or End date any one empty',
                        'cbxpoll').'</p></div>';


                return false; //might stop processing here
            } else {


                update_post_meta($post_id, $field['id'], $new);

            }
        }
    }//end method metabox_extra_save

    /**
     * Full reset CBX Poll
     *
     * This will not delete cbxpoll custom post types
     */
    public function plugin_fullreset()
    {
        if (isset($_REQUEST['page']) && $_REQUEST['page'] == 'cbxpollsetting' && isset($_REQUEST['cbxpoll_fullreset']) && $_REQUEST['cbxpoll_fullreset'] == 1) {
            global $wpdb;

            $option_prefix = 'cbxpoll_';

            $option_values = CBXPollHelper::getAllOptionNames();

            foreach ($option_values as $key => $accounting_option_value) {
                delete_option($accounting_option_value['option_name']);
            }

            do_action('cbxpoll_plugin_option_delete');


            //delete tables

            $table_names  = CBXPollHelper::getAllDBTablesList();
            $sql          = "DROP TABLE IF EXISTS ".implode(', ', array_values($table_names));
            $query_result = $wpdb->query($sql);

            do_action('cbxpoll_plugin_table_delete');

            //deleted all 'cbxpoll' type posts
            //global $post;
            $args = array('posts_per_page' => -1, 'post_type' => 'cbxpoll', 'post_status' => 'any');

            $myposts = get_posts($args);
            foreach ($myposts as $post) :
                //CBXPollHelper::setup_admin_postdata( $post );
                $post_id = intval($post->ID);
                //delete the post
                wp_delete_post($post_id, true);
            endforeach;
            //CBXPollHelper::wp_reset_admin_postdata();

            // create plugin's core table tables
            cbxpollHelper::install_table();

            //please note that, the default options will be created by default


            //3rd party plugin's table creation
            do_action('cbxpoll_plugin_reset', $table_names, $option_prefix);


            $this->settings_api->set_sections($this->get_setting_sections());
            $this->settings_api->set_fields($this->get_setting_fields());
            $this->settings_api->admin_init();

            wp_safe_redirect(admin_url('edit.php?post_type=cbxpoll&page=cbxpollsetting#cbxpoll_tools'));
            exit();
        }
    }//end method plugin_fullreset

    /**
     * Get Text answer templte
     */
    public function get_answer_template()
    {

        //security check
        check_ajax_referer('cbxpoll', 'security');

        //get the fields

        $index        = intval($_POST['answer_counter']);
        $answer_color = esc_attr($_POST['answer_color']);
        $is_voted     = intval($_POST['is_voted']);
        $poll_postid  = intval($_POST['poll_postid']);
        $answer_type  = esc_attr($_POST['answer_type']);

        $answers_extra = array('type' => $answer_type);

        $poll_answer = sprintf(esc_html__('Answer %d', 'cbxpoll'), ($index + 1));

        $template = CBXPollHelper::cbxpoll_answer_field_template($index, $poll_answer, $answer_color, $is_voted,
            $answers_extra, $poll_postid);

        echo json_encode($template);
        die();
    }//end method get_answer_template

    /**
     * Add settings action link to the plugins page.
     *
     * @since    1.0.0
     */
    public function plugin_listing_setting_link($links)
    {

        return array_merge(array(
            'settings' => '<a style="color: #2153cc; font-weight: bold;" target="_blank" href="'.admin_url('edit.php?post_type=cbxpoll&page=cbxpollsetting').'">'.esc_html__('Settings',
                    'cbxpoll').'</a>'
        ), $links);

    }//end method plugin_listing_setting_link

    /**
     * Add Pro product link in plugin listing
     *
     * @param $links
     * @param $file
     *
     * @return array
     */
    public function custom_plugin_row_meta($links, $file)
    {
        if (strpos($file, 'cbxpoll.php') !== false) {
            if (!function_exists('is_plugin_active')) {
                include_once(ABSPATH.'wp-admin/includes/plugin.php');
            }

            $new_links = array();

            $new_links['free_support'] = '<a style="color: #2153cc; font-weight: bold;" href="https://wordpress.org/support/plugin/cbxpoll/" target="_blank">'.esc_html__('Free Support',
                    'cbxpoll').'</a>';
            $new_links['reviews']      = '<a style="color: #2153cc; font-weight: bold;" href="https://wordpress.org/plugins/cbxpoll/#reviews" target="_blank">'.esc_html__('Reviews',
                    'cbxpoll').'</a>';

            if (in_array('cbxpollproaddon/cbxpollproaddon.php', apply_filters('active_plugins',
                    get_option('active_plugins'))) || defined('CBX_POLLPROADDON_PLUGIN_NAME')) {

            } else {
                $new_links['pro'] = '<a style="color: #2153cc; font-weight: bold;" href="https://codeboxr.com/product/cbx-poll-for-wordpress/" target="_blank">'.esc_html__('Try Pro',
                        'cbxpoll').'</a>';
            }


            $new_links['doc'] = '<a style="color: #2153cc; font-weight: bold;" href="https://codeboxr.com/cbx-poll-documentation/" target="_blank">'.esc_html__('Documentation',
                    'cbxpoll').'</a>';


            $links = array_merge($links, $new_links);
        }

        return $links;
    }//end method custom_plugin_row_meta

    /**
     * Post installation hook
     *
     * @param $response
     * @param  array  $hook_extra
     * @param  array  $result
     */
    public function upgrader_post_install($response, $hook_extra = array(), $result = array())
    {

        if ($response && isset($hook_extra['type']) && $hook_extra['type'] == 'plugin') {
            if (isset($result['destination_name']) && $result['destination_name'] == 'cbxpoll') {
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
            }
        }
    }

    /**
     * If we need to do something in upgrader process is completed for poll plugin
     *
     * @param $upgrader_object
     * @param $options
     */
    public function plugin_upgrader_process_complete($upgrader_object, $options)
    {
        if ($options['action'] == 'update' && $options['type'] == 'plugin') {
            foreach ($options['plugins'] as $each_plugin) {
                if ($each_plugin == CBX_POLL_PLUGIN_BASE_NAME) {
                    CBXPollHelper::install_table();
                    set_transient('cbxpoll_upgraded_notice', 1);
                }
            }
        }

    }//end method plugin_upgrader_process_complete

    /**
     * Show a notice to anyone who has just installed the plugin for the first time
     * This notice shouldn't display to anyone who has just updated this plugin
     */
    public function plugin_activate_upgrade_notices()
    {
        // Check the transient to see if cbxpollproaddon has been force deactivated
        if (get_transient('cbxpollproaddon_forcedactivated_notice')) {
            echo '<div style="border-left:4px solid #d63638;" class="notice notice-error is-dismissible">';
            echo '<p>'.__('<strong>CBX Poll Pro Addon</strong> has been deactivated as it\'s not compatible with core plugin <strong>CBX Poll</strong> current installed version. Please upgrade CBX Poll Pro Addon to latest version ',
                    'cbxpoll').'</p>';
            echo '</div>';

            // Delete the transient so we don't keep displaying the activation message
            delete_transient('cbxpollproaddon_forcedactivated_notice');
        }

        // Check the transient to see if we've just activated the plugin
        if (get_transient('cbxpoll_activated_notice')) {
            echo '<div style="border-left:4px solid #2153cc;" class="notice notice-success is-dismissible">';
            echo '<p>'.sprintf(__('Thanks for installing/deactivating <strong>CBX Poll</strong> V%s - <a href="%s" target="_blank">Codeboxr Team</a>',
                    'cbxpoll'), CBX_POLL_PLUGIN_VERSION, 'https://codeboxr.com/product/cbx-poll-for-wordpress/').'</p>';
            echo '<p>'.sprintf(__('Explore <a href="%s" target="_blank">Plugin Setting</a> | <a href="%s" target="_blank">Documentation</a>',
                    'cbxpoll'), admin_url('edit.php?post_type=cbxpoll&page=cbxpollsetting'),
                    'https://codeboxr.com/cbx-poll-documentation/').'</p>';
            echo '</div>';

            // Delete the transient so we don't keep displaying the activation message
            delete_transient('cbxpoll_activated_notice');

            $this->plugin_compatibility_check();
        }

        // Check the transient to see if we've just activated the plugin
        if (get_transient('cbxpoll_upgraded_notice')) {
            echo '<div style="border-left:4px solid #2153cc;" class="notice notice-success is-dismissible">';
            echo '<p>'.sprintf(__('Thanks for upgrading <strong>CBX Poll</strong> V%s , enjoy the new features and bug fixes - <a href="%s" target="_blank">Codeboxr Team</a>',
                    'cbxpoll'), CBX_POLL_PLUGIN_VERSION, 'https://codeboxr.com/product/cbx-poll-for-wordpress/').'</p>';
            echo '<p>'.sprintf(__('Explore <a href="%s" target="_blank">Plugin Setting</a> | <a href="%s" target="_blank">Documentation</a>',
                    'cbxpoll'), admin_url('edit.php?post_type=cbxpoll&page=cbxpollsetting'),
                    'https://codeboxr.com/cbx-poll-documentation/').'</p>';
            echo '</div>';

            // Delete the transient so we don't keep displaying the activation message
            delete_transient('cbxpoll_upgraded_notice');

            $this->plugin_compatibility_check();
        }
    }//end method plugin_activate_upgrade_notices

    /**
     * Check plugin compatibility
     */
    public function plugin_compatibility_check()
    {

        if (!function_exists('is_plugin_active')) {
            include_once(ABSPATH.'wp-admin/includes/plugin.php');
        }

        // check for plugin using plugin name
        if (in_array('cbxpollproaddon/cbxpollproaddon.php', apply_filters('active_plugins',
                get_option('active_plugins'))) || defined('CBX_POLLPROADDON_PLUGIN_NAME')) {
            //plugin is activated

            $pro_plugin_version = CBX_POLLPROADDON_PLUGIN_VERSION;


            if (version_compare($pro_plugin_version, '1.1.3', '<')) {
                echo '<div style="border-left:4px solid #d63638;" class="notice notice-error is-dismissible"><p>'.esc_html__('Compatibility issue: CBX Poll V1.2.2 or later needs CBX Poll Pro Addon V1.1.3 or later. Please update CBX Poll Pro Addon to version 1.1.3 or later  - Codeboxr Team',
                        'cbxpoll').'</p></div>';
            }
        } else {
            echo '<div style="border-left:4px solid #2153cc;" class="notice notice-success is-dismissible"><p>'.sprintf(__('<a target="_blank" href="%s">CBX Poll Pro Addon</a> has some extra pro features, try it. - Codeboxr Team',
                    'cbxpoll'), 'https://codeboxr.com/product/cbx-poll-for-wordpress/').'</p></div>';
        }

    }//end method plugin_compatibility_check

    /**
     * Init all gutenberg blocks
     */
    public function gutenberg_blocks()
    {
        if (!function_exists('register_block_type')) {
            return;
        }

        /* global $post;
		 //write_log($post);

		 if ( ! is_admin() ) {
			 global $post;
		 }*/

        $args = array(
            'post_type'      => 'cbxpoll',
            'orderby'        => 'ID',
            'order'          => 'DESC',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
        );

        $cbxpoll_posts = get_posts($args);

        $poll_id_options   = array();
        $poll_id_options[] = array(
            'label' => esc_html__('Select Poll', 'cbxpoll'),
            'value' => 0
        );

        foreach ($cbxpoll_posts as $post) :
            //CBXPollHelper::setup_admin_postdata( $post );
            $post_id    = $post->ID;
            $post_title = get_the_title($post_id);

            $poll_id_options[] = array(
                'label' => esc_attr($post_title),
                'value' => intval($post_id)
            );
        endforeach;
        //CBXPollHelper::wp_reset_admin_postdata();


        $chart_type_arr     = CBXPollHelper::cbxpoll_display_options();
        $chart_type_options = array();
        foreach ($chart_type_arr as $key => $method) {
            $chart_type_options[] = array(
                'label' => esc_attr($method['title']),
                'value' => $key
            );
        }

        $chart_type_options[] = array(
            'label' => esc_html__('Use from poll post setting', 'cbxpoll'),
            'value' => ''
        );


        $description_arr = array(
            '1' => esc_html__('Yes', 'cbxpoll'),
            '0' => esc_html__('No', 'cbxpoll'),
            '2' => esc_html__('Use Poll Post Setting', 'cbxpoll'),
        );

        $description_options = array();
        foreach ($description_arr as $value => $label) {
            $description_options[] = array(
                'label' => $label,
                'value' => $value
            );
        }

        $grid_arr = array(
            0 => esc_html__('List', 'cbxpoll'),
            1 => esc_html__('Grid', 'cbxpoll'),
            2 => esc_html__('Use Poll Post Setting', 'cbxpoll'),
        );

        $grid_options = array();
        foreach ($grid_arr as $value => $label) {
            $grid_options[] = array(
                'label' => $label,
                'value' => intval($value)
            );
        }

        wp_register_script('cbxpoll-singlepoll-block',
            plugin_dir_url(__FILE__).'../assets/js/cbxpoll-singlepoll-block.js', array(
                'wp-blocks',
                'wp-element',
                'wp-components',
                'wp-editor',
                //'jquery',
            ), filemtime(plugin_dir_path(__FILE__).'../assets/js/cbxpoll-singlepoll-block.js'));

        wp_register_style('cbxpoll-block', plugin_dir_url(__FILE__).'../assets/css/cbxpoll-block.css', array(),
            filemtime(plugin_dir_path(__FILE__).'../assets/css/cbxpoll-block.css'));

        $js_vars = apply_filters('cbxpoll_singlepoll_block_js_vars',
            array(
                'block_title'      => esc_html__('CBX Poll Single Block', 'cbxpoll'),
                'block_category'   => 'codeboxr',
                'block_icon'       => 'universal-access-alt',
                'general_settings' => array(
                    'heading'             => esc_html__('CBXPoll Single Block Settings', 'cbxpoll'),
                    'poll_id'             => esc_html__('Poll', 'cbxpoll'),
                    'poll_id_options'     => $poll_id_options,
                    'chart_type'          => esc_html__('Chart Type', 'cbxpoll'),
                    'chart_type_options'  => $chart_type_options,
                    'description'         => esc_html__('Show Poll description', 'cbxpoll'),
                    'description_options' => $description_options,
                    'grid'                => esc_html__('Answer Format', 'cbxpoll'),
                    'grid_options'        => $grid_options
                ),
            ));

        wp_localize_script('cbxpoll-singlepoll-block', 'cbxpoll_singlepoll_block', $js_vars);

        register_block_type('codeboxr/cbxpoll-single', array(
            'editor_script'   => 'cbxpoll-singlepoll-block',
            'editor_style'    => 'cbxpoll-block',
            'attributes'      => apply_filters('cbxpoll_singlepoll_block_attributes', array(
                'poll_id'     => array(
                    'type'    => 'integer',
                    'default' => 0,
                ),
                'chart_type'  => array(
                    'type'    => 'string',
                    'default' => 'text'
                ),
                'description' => array(
                    'type'    => 'integer',
                    'default' => 1
                ),
                'grid'        => array(
                    'type'    => 'integer',
                    'default' => 0
                ),

            )),
            'render_callback' => array($this, 'cbxpoll_single_block_render')
        ));

    }//end gutenberg_blocks

    /**
     * Getenberg server side render
     *
     * @param $settings
     *
     * @return string
     */
    public function cbxpoll_single_block_render($attributes)
    {
        $settings_api = new CBXPoll_Settings();

        $attr = array();

        $id          = $attr['id'] = isset($attributes['poll_id']) ? intval($attributes['poll_id']) : 0;
        $chart_type  = $attr['chart_type'] = isset($attributes['chart_type']) ? sanitize_text_field($attributes['chart_type']) : 'text';
        $description = $attr['description'] = isset($attributes['description']) ? intval($attributes['description']) : 1;
        $grid        = $attr['grid'] = isset($attributes['grid']) ? intval($attributes['grid']) : 0;

        //2 = means ignore shortcode params, use from poll
        if ($description == 2) {
            $description = '';
        }

        //2 = means ignore shortcode params, use from poll
        if ($grid == 2) {
            $grid = '';
        }


        $attr['description'] = $description;
        $attr['grid']        = $grid;

        $attr = apply_filters('cbxpoll_singlepoll_block_shortcode_builder_attr', $attr, $attributes);

        $attr_html = '';

        foreach ($attr as $key => $value) {
            $attr_html .= ' '.$key.'="'.$value.'" ';
        }

        //return do_shortcode( '[cbxpoll ' . $attr_html . ']' );
        return '[cbxpoll '.$attr_html.']';
    }//end cbxpoll_single_block_render

    /**
     * Register New Gutenberg block Category if need
     *
     * @param $categories
     * @param $post
     *
     * @return mixed
     */
    public function gutenberg_block_categories($categories, $post)
    {
        $found = false;
        foreach ($categories as $category) {
            if ($category['slug'] == 'codeboxr') {
                $found = true;
                break;
            }
        }

        if (!$found) {
            return array_merge(
                $categories,
                array(
                    array(
                        'slug'  => 'codeboxr',
                        'title' => esc_html__('CBX Blocks', 'cbxpoll'),
                    ),
                )
            );
        }

        return $categories;
    }//end gutenberg_block_categories


    /**
     * Enqueue style for block editor
     */
    public function enqueue_block_editor_assets()
    {

    }//end enqueue_block_editor_assets

    /**
     * Add our self-hosted autoupdate plugin to the filter transient
     *
     * @param $transient
     *
     * @return object $ transient
     */
    public function pre_set_site_transient_update_plugins_pro_addon($transient){
        // Extra check for 3rd plugins
        if ( isset( $transient->response[ 'cbxpollproaddon/cbxpollproaddon.php' ] ) ) {
            return $transient;
        }

        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugin_info = array();
        $all_plugins = get_plugins();
        if(!isset($all_plugins['cbxpollproaddon/cbxpollproaddon.php'])){
            return $transient;
        }
        else{
            $plugin_info = $all_plugins['cbxpollproaddon/cbxpollproaddon.php'];
        }

        $remote_version = '1.1.7';

        if ( version_compare( $plugin_info['Version'], $remote_version, '<' ) ) {
            $obj = new stdClass();
            $obj->slug = 'cbxpollproaddon';
            $obj->new_version = $remote_version;
            $obj->plugin = 'cbxpollproaddon/cbxpollproaddon.php';
            $obj->url = '';
            $obj->package = false;
            $obj->name = 'CBX Poll Pro Addon';
            $transient->response[ 'cbxpollproaddon/cbxpollproaddon.php' ] = $obj;
        }

        return $transient;
    }//end pre_set_site_transient_update_plugins_pro_addons

    /**
     * Pro Addon update message
     */
    public function plugin_update_message_pro_addons(){
        echo ' '.sprintf(__('Check how to <a style="color:#005ae0 !important; font-weight: bold;" href="%s"><strong>Update manually</strong></a> , download latest version from <a style="color:#005ae0 !important; font-weight: bold;" href="%s"><strong>My Account</strong></a> section of Codeboxr.com', 'cbxwpbookmark'), 'https://codeboxr.com/manual-update-pro-addon/', 'https://codeboxr.com/my-account/');
    }//end plugin_update_message_pro_addons

}//end class CBXPoll
