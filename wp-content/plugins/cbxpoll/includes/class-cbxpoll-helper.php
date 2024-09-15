<?php
/**
 * The helper functionality of the plugin.
 *
 * @link       https://codeboxr.com
 * @since      1.0.0
 *
 * @package    CBXPoll
 * @subpackage CBXPoll/includes
 */

if (!defined('WPINC')) {
    die;
}

/**
 * Helper functionality of the plugin.
 *
 * lots of micro methods that help get set
 *
 * @package    CBXPoll
 * @subpackage CBXPoll/includes
 * @author     codeboxr <info@codeboxr.com>
 */
class CBXPollHelper
{
    /**
     * initialize cookie
     */
    public static function init_cookie()
    {

        $current_user = wp_get_current_user();
        $user_id      = $current_user->ID;


        if (!is_admin()) {
            if (is_user_logged_in()) {

                $cookie_value = 'user-'.$user_id;

            } else {

                $cookie_value = 'guest-'.rand(CBX_POLL_RAND_MIN, CBX_POLL_RAND_MAX);
            }

            if (!isset($_COOKIE[CBX_POLL_COOKIE_NAME]) && empty($_COOKIE[CBX_POLL_COOKIE_NAME])) {

                setcookie(CBX_POLL_COOKIE_NAME, $cookie_value, CBX_POLL_COOKIE_EXPIRATION_14DAYS, SITECOOKIEPATH,
                    COOKIE_DOMAIN);
                $_COOKIE[CBX_POLL_COOKIE_NAME] = $cookie_value;

            } elseif (isset($_COOKIE[CBX_POLL_COOKIE_NAME])) {


                if (substr($_COOKIE[CBX_POLL_COOKIE_NAME], 0, 5) != 'guest') {
                    setcookie(CBX_POLL_COOKIE_NAME, $cookie_value, CBX_POLL_COOKIE_EXPIRATION_14DAYS, SITECOOKIEPATH,
                        COOKIE_DOMAIN);
                    $_COOKIE[CBX_POLL_COOKIE_NAME] = $cookie_value;
                }
            }
        }

    }

    /**
     * Get IP address
     *
     * @return string|void
     */
    public static function get_ipaddress()
    {

        if (empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {

            $ip_address = $_SERVER["REMOTE_ADDR"];
        } else {

            $ip_address = $_SERVER["HTTP_X_FORWARDED_FOR"];
        }

        if (strpos($ip_address, ',') !== false) {

            $ip_address = explode(',', $ip_address);
            $ip_address = $ip_address[0];
        }

        return esc_attr($ip_address);
    }

    /**
     * Create custom post type poll
     */
    public static function create_cbxpoll_post_type()
    {
    	$settings = new CBXPoll_Settings();

    	$slug_single    = $settings->get_option('slug_single', 'cbxpoll_slugs_settings', 'cbxpoll');
    	$slug_archive   = $settings->get_option('slug_archive', 'cbxpoll_slugs_settings', 'cbxpoll');


        $args = array(
            'labels'          => array(
                'name'          => esc_html__('CBX Polls', 'cbxpoll'),
                'singular_name' => esc_html__('CBX Poll', 'cbxpoll')
            ),
            'menu_icon'       => plugins_url('../assets/images/poll_icon.png', __FILE__), // 16px16
            'public'          => true,
            //'has_archive'     => true,
            'has_archive'         => sanitize_title($slug_archive),
            'capability_type' => 'page',
            'supports'        => apply_filters('cbxpoll_post_type_supports', array(
                'title',
                'editor',
                'author',
                'thumbnail'
            )),
            'rewrite'            => array( 'slug' => sanitize_title($slug_single)),
        );

        register_post_type('cbxpoll', apply_filters('cbxpoll_post_type_args', $args));
    }//end method create_cbxpoll_post_type

    /**
     * create table with plugin activate hook
     */

    public static function install_table()
    {
        global $wpdb;
        $charset_collate = '';
        if (!empty($wpdb->charset)) {
            $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
        }
        if (!empty($wpdb->collate)) {
            $charset_collate .= " COLLATE $wpdb->collate";
        }

        require_once(ABSPATH.'/wp-admin/includes/upgrade.php');

        $votes_name = CBXPollHelper::cbx_poll_table_name();

        $sql = "CREATE TABLE $votes_name (
                  id mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
                  poll_id int(13) NOT NULL,
                  poll_title text NOT NULL,
                  user_name varchar(255) NOT NULL,
                  is_logged_in tinyint(1) NOT NULL,
                  user_cookie varchar(1000) NOT NULL,
                  user_ip varchar(45) NOT NULL,
                  user_id bigint(20) unsigned NOT NULL,
                  user_answer text NOT NULL,
                  published tinyint(3) NOT NULL DEFAULT '1',
                  comment LONGTEXT NOT NULL,
                  guest_hash VARCHAR(32) NOT NULL,
                  guest_name varchar(100) DEFAULT NULL,
                  guest_email varchar(100) DEFAULT NULL,
                  created int(20) NOT NULL,
                  PRIMARY KEY  (id)
            ) $charset_collate;";
        dbDelta($sql);

    }


    /**
     * will call this later when plugin uninstalled
     * in can also be written in uninstall.php file
     */
    public static function delete_tables()
    {
        global $wpdb;
        $votes_name[] = CBXPollHelper::cbx_poll_table_name();
        $sql          = "DROP TABLE IF EXISTS ".implode(', ', $votes_name);
        require_once(ABSPATH.'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Insert user vote
     *
     * @param  array  $user_vote
     *
     * @return bool | vote id
     */
    public static function update_poll($user_vote)
    {
        global $wpdb;

        if (!empty($user_vote)) {
            $votes_table = CBXPollHelper::cbx_poll_table_name();

            $success = $wpdb->insert($votes_table, $user_vote, array(
                '%d',
                '%s',
                '%s',
                '%d',
                '%s',
                '%s',
                '%d',
                '%s',
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
                '%d'
            ));

            return ($success) ? $wpdb->insert_id : false;
        }

        return false;

    }


    /**
     * CBX Poll vote table name
     *
     * @return string
     */
    public static function cbx_poll_table_name()
    {
        global $wpdb;

        return $wpdb->prefix."cbxpoll_votes";
    }

    /**
     * @param $string
     *
     * @return string
     *
     */
    public static function check_value_type($string)
    {
        $t   = gettype($string);
        $ret = '';

        switch ($t) {
            case 'string' :
                $ret = '\'%s\'';
                break;

            case 'integer':
                //$ret = '\'%d\'';
                $ret = '%d';
                break;
        }

        return $ret;
    }

    /**
     * Returns all votes for any poll
     *
     * @param  int  $poll_id  cbxpoll type post id
     * @param  bool  $is_object  array or object return type
     *
     *
     * @return mixed
     *
     */
    public static function get_pollResult($poll_id, $is_object = false)
    {
        global $wpdb;
        $votes_name = CBXPollHelper::cbx_poll_table_name();

        $sql     = $wpdb->prepare("SELECT * FROM $votes_name WHERE poll_id=%d AND published = 1", intval($poll_id));
        $results = $wpdb->get_results($sql, ARRAY_A);


        return $results;
    }// end of function get_pollresult

    /**
     * Is poll voted or not by vote count (not taking publish status into account)
     *
     * @param $poll_id
     *
     * @return int
     */
    public static function is_poll_voted($poll_id)
    {
        global $wpdb;
        $votes_name = CBXPollHelper::cbx_poll_table_name();

        $sql         = $wpdb->prepare("SELECT COUNT(*) AS total_count FROM $votes_name WHERE poll_id=%d",
            intval($poll_id));
        $total_count = intval($wpdb->get_var($sql));


        return ($total_count > 0) ? 1 : 0;
    }//end method is_poll_voted

    /**
     * Returns single vote result by id
     *
     * @param  int  $vote  single vote id
     * @param  bool  $is_object  array or object return type
     *
     *
     * @return mixed
     *
     */
    public static function get_voteResult($vote_id, $is_object = false)
    {
        global $wpdb;
        $votes_name = CBXPollHelper::cbx_poll_table_name();

        $sql     = $wpdb->prepare("SELECT * FROM $votes_name WHERE id=%d", $vote_id);
        $results = $wpdb->get_results($sql, ARRAY_A);


        return $results;
    }// end of function get_pollresult

    /**
     * @param $array
     *
     * @return array
     */
    public static function check_array_element_value_type($array)
    {
        $ret = array();

        if (!empty($array)) {
            foreach ($array as $val) {
                $ret[] = CBXPollHelper::check_value_type($val);
            }
        }

        return $ret;
    } //end of function check_array_element_value_type

    /**
     * Defination of all Poll Display/Chart Types
     *
     * @return array
     */
    public static function cbxpoll_display_options()
    {
        $methods = array();

        return apply_filters('cbxpoll_display_options', $methods);
    }

    /**
     * Return poll display option as associative array
     *
     * @param  array  $methods
     *
     * @return array
     */
    public static function cbxpoll_display_options_linear($methods)
    {

        $linear_methods = array();

        foreach ($methods as $key => $val) {
            $linear_methods[$key] = $val['title'];
        }

        return $linear_methods;
    }

    public static function getVoteCountByStatus($poll_id = 0)
    {

        global $wpdb;

        $votes_name = cbxpollHelper::cbx_poll_table_name();

        $where_sql = '';
        if ($poll_id != 0) {
            $where_sql .= $wpdb->prepare('poll_id=%d', $poll_id);
        }

        if ($where_sql == '') {
            $where_sql = '1';
        }

        $sql_select = "SELECT published, COUNT(*) as vote_counts FROM $votes_name  WHERE   $where_sql GROUP BY published";

        $results = $wpdb->get_results("$sql_select", 'ARRAY_A');

        $total = 0;
        $data  = array(
            '0'     => 0,
            '1'     => 0,
            '2'     => 0,
            '3'     => 0,
            'total' => $total
        );


        if ($results != null) {
            foreach ($results as $result) {
                $total                      += intval($result['vote_counts']);
                $data[$result['published']] = $result['vote_counts'];
            }
            $data['total'] = $total;
        }

        return $data;

    }

    /**
     * Filter the format of the sending mail
     *
     * @param  type  $content_type
     *
     * @return string
     */
    public static function cbxppoll_mail_content_type($content_type = 'text/plain')
    {
        if ($content_type == 'html') {
            return 'text/html';
        } elseif ($content_type == 'multipart') {
            return 'multipart/mixed';
        } else {
            return 'text/plain';
        }
    }

    /**
     * Char Length check  thinking utf8 in mind
     *
     * @param $text
     *
     * @return int
     */
    public static function utf8_compatible_length_check($text)
    {
        if (seems_utf8($text)) {
            $length = mb_strlen($text);
        } else {
            $length = strlen($text);
        }

        return $length;
    }

    /**
     * Returns poll possible status as array, keys are value of status
     *
     * @return array
     */
    public static function cbxpoll_status_by_value()
    {
        $states = array(
            '0' => esc_html__('Unapproved', 'cbxpoll'),
            '1' => esc_html__('Approved', 'cbxpoll'),
            '2' => esc_html__('Spam', 'cbxpoll'),
            '3' => esc_html__('Unverified', 'cbxpoll')
        );

        return apply_filters('cbxpoll_status_by_value', $states);
    }

    /**
     * Returns poll possible status as array, keys are slug of status
     *
     * @return array
     */
    public static function cbxpoll_status_by_slug()
    {
        $states = array(
            'unapprove'  => esc_html__('Unapproved', 'cbxpoll'),
            'approve'    => esc_html__('Approved', 'cbxpoll'),
            'spam'       => esc_html__('Spam', 'cbxpoll'),
            'unverified' => esc_html__('Unverified', 'cbxpoll')
        );

        return apply_filters('cbxpoll_status_by_value', $states);
    }

    /**
     * Returns poll possible status as array, keys are value of status and values are slug
     *
     * @return array
     */
    public static function cbxpoll_status_by_value_with_slug()
    {
        $states = array(
            '0' => 'unapprove',
            '1' => 'approve',
            '2' => 'spam',
            '3' => 'unverified',
        );

        return apply_filters('cbxpoll_status_by_value_with_slug', $states);
    }

    /**
     * Get the user roles for voting purpose
     *
     * @param  string  $useCase
     *
     * @return array
     */
    public static function user_roles($plain = true, $include_guest = false, $ignore = array())
    {
        global $wp_roles;

        if (!function_exists('get_editable_roles')) {
            require_once(ABSPATH.'/wp-admin/includes/user.php');

        }

        $userRoles = array();
        if ($plain) {
            foreach (get_editable_roles() as $role => $roleInfo) {
                if (in_array($role, $ignore)) {
                    continue;
                }
                $userRoles[$role] = $roleInfo['name'];
            }
            if ($include_guest) {
                $userRoles['guest'] = esc_html__("Guest", 'cbxpoll');
            }
        } else {
            //optgroup
            $userRoles_r = array();
            foreach (get_editable_roles() as $role => $roleInfo) {
                if (in_array($role, $ignore)) {
                    continue;
                }
                $userRoles_r[$role] = $roleInfo['name'];
            }

            $userRoles = array(
                'Registered' => $userRoles_r,
            );

            if ($include_guest) {
                $userRoles['Anonymous'] = array(
                    'guest' => esc_html__("Guest", 'cbxpoll')
                );
            }
        }

        return apply_filters('cbxpoll_userroles', $userRoles, $plain, $include_guest);
    }

    /**
     * Get all  core tables list
     */
    public static function getAllDBTablesList()
    {
        global $wpdb;

        $table_names                  = array();
        $table_names['cbxpoll_votes'] = CBXPollHelper::cbx_poll_table_name();


        return apply_filters('cbxpoll_table_list', $table_names);
    }

    /**
     * List all global option name with prefix cbxpoll_
     */
    public static function getAllOptionNames()
    {
        global $wpdb;

        $prefix       = 'cbxpoll_';
        $option_names = $wpdb->get_results("SELECT * FROM {$wpdb->options} WHERE option_name LIKE '{$prefix}%'",
            ARRAY_A);

        return apply_filters('cbxpoll_option_names', $option_names);
    }

    /**
     * (Recommended not to use)Setup a post object and store the original loop item so we can reset it later
     *
     * @param  obj  $post_to_setup  The post that we want to use from our custom loop
     */
    public static function setup_admin_postdata($post_to_setup)
    {

        //only on the admin side
        if (is_admin()) {

            //get the post for both setup_postdata() and to be cached
            global $post;

            //only cache $post the first time through the loop
            if (!isset($GLOBALS['post_cache'])) {
                $GLOBALS['post_cache'] = $post;
            }

            //setup the post data as usual
            $post = $post_to_setup;
            setup_postdata($post);
        } else {
            setup_postdata($post_to_setup);
        }
    }//end method setup_admin_postdata


    /**
     * (Recommended not to use)Reset $post back to the original item
     *
     */
    public static function wp_reset_admin_postdata()
    {

        //only on the admin and if post_cache is set
        if (is_admin() && !empty($GLOBALS['post_cache'])) {

            //globalize post as usual
            global $post;

            //set $post back to the cached version and set it up
            $post = $GLOBALS['post_cache'];
            setup_postdata($post);

            //cleanup
            unset($GLOBALS['post_cache']);
        } else {
            wp_reset_postdata();
        }
    }//end method wp_reset_admin_postdata

    /**
     * List polls
     *
     * @param  int  $user_id
     * @param  int  $per_page
     * @param  int  $page_number
     * @param  string  $chart_type
     * @param  string  $answer_grid_list
     * @param  string  $description
     * @param  string  $reference
     *
     * @return array
     * @throws Exception
     */
    public static function poll_list(
        $user_id = 0,
        $per_page = 10,
        $page_number = 1,
        $chart_type = '',
        $answer_grid_list = '',
        $description = '',
        $reference = 'shortcode'
    ) {
        $setting_api = new CBXPoll_Settings();
        //$global_answer_grid_list = $setting_api->get_option('answer_grid_list', 'cbxpoll_global_settings', 0); //0 = list 1 = grid

        global $post;
        $output = array();

        $args = array(
            'post_type'      => 'cbxpoll',
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'paged'          => $page_number
        );

        if (intval($user_id) > 0) {
            $args['author'] = $user_id;
        }

        $content = '';

        $posts_array = new WP_Query($args);


        $total_count = intval($posts_array->found_posts);

        if ($posts_array->have_posts()) {
            $output['found']         = 1;
            $output['found_posts']   = $total_count;
            $output['max_num_pages'] = ceil($total_count / $per_page);

            //foreach ( $posts_array as $post ) : setup_postdata( $post );
            while ($posts_array->have_posts()) : $posts_array->the_post();
                $poll_id = get_the_ID();

                $content .= CBXPollHelper::cbxpoll_single_display($poll_id, $reference, $chart_type, $answer_grid_list,
                    $description);
                //endforeach;
            endwhile;
            wp_reset_postdata();

        } else {
            $output['found'] = 0;
        }

        $output['content'] = $content;

        return $output;
    }//end method poll_list

    /**
     * Shows a single poll
     *
     * @param  int  $post_id
     * @param  string  $reference
     * @param  string  $result_chart_type
     * @param  string  $grid
     * @param  int  $description
     *
     * @return string
     * @throws Exception
     */
    public static function cbxpoll_single_display( $post_id = 0, $reference = 'shortcode', $result_chart_type = '', $grid = '', $description = '') {
        //if poll id
        if (intval($post_id) == 0) {
            return '';
        }

        global $wpdb;

        $setting_api  = $settings = new CBXPoll_Settings();
        $current_user = wp_get_current_user();
        $user_id      = $current_user->ID;
        $user_ip      = CBXPollHelper::get_ipaddress();
        $poll_output  = '';

        $allow_guest_sign = $settings->get_option('allow_guest_sign', 'cbxpoll_global_settings', 'on');

        $poll_status = get_post_status($post_id);
        if ($poll_status !== 'publish') {
            $this_user_role = $current_user->roles;
            if (in_array('administrator', $this_user_role) || in_array('editor', $this_user_role)) {
                $poll_output .= esc_html__('Note: Poll is not published yet or poll doesn\'t exists. You are checking this as administrator/editor.',
                    'cbxpoll');
            } else {
                return esc_html__('Sorry, poll is not published yet or poll doesn\'t exists.', 'cbxpoll');
            }
        }//end checking publish status

        //todo: need to get it from single poll if we introduce this inside poll setting
        $grid = ($grid == '') ? intval($settings->get_option('answer_grid_list', 'cbxpoll_global_settings',
            0)) : intval($grid);

        $grid_class = ($grid != 0) ? 'cbxpoll-form-insidewrap-grid' : '';


        if ($user_id == 0) {
            $user_session = $_COOKIE[CBX_POLL_COOKIE_NAME]; //this is string

        } elseif (is_user_logged_in()) {
            $user_session = 'user-'.$user_id; //this is string
        }

        //$setting_api = get_option('cbxpoll_global_settings');
        $votes_name = cbxpollHelper::cbx_poll_table_name();

        //poll informations from meta

        $poll_start_date = get_post_meta($post_id, '_cbxpoll_start_date', true); //poll start date
        $poll_end_date   = get_post_meta($post_id, '_cbxpoll_end_date', true); //poll end date
        $poll_user_roles = get_post_meta($post_id, '_cbxpoll_user_roles', true); //poll user roles
        if (!is_array($poll_user_roles)) {
            $poll_user_roles = array();
        }

        $show_description               = intval(get_post_meta($post_id, '_cbxpoll_content',
            true)); //show poll description or not
        $poll_never_expire              = intval(get_post_meta($post_id, '_cbxpoll_never_expire',
            true)); //poll never epire
        $poll_show_result_before_expire = intval(get_post_meta($post_id, '_cbxpoll_show_result_before_expire',
            true)); //poll never epire
        $poll_result_chart_type         = get_post_meta($post_id, '_cbxpoll_result_chart_type', true); //chart type
        $poll_is_voted                  = CBXPollHelper::is_poll_voted($post_id);
        //$poll_show_result_all           = get_post_meta( $post_id, '_cbxpoll_show_result_all', true ); //show_result_all
        //$poll_is_voted          = intval( get_post_meta( $post_id, '_cbxpoll_is_voted', true ) ); //at least a single vote


        $poll_answers_extra = get_post_meta($post_id, '_cbxpoll_answer_extra', true);

        //new field from v1.0.1

        $poll_multivote = intval(get_post_meta($post_id, '_cbxpoll_multivote', true)); //at least a single vote

        $vote_input_type = ($poll_multivote) ? 'checkbox' : 'radio';

        //$global_result_chart_type   = isset($setting_api['result_chart_type'])? $setting_api['result_chart_type']: 'text';
        //$poll_result_chart_type = get_post_meta($post_id, '_cbxpoll_result_chart_type', true);

        $result_chart_type = ($result_chart_type != '') ? $result_chart_type : $poll_result_chart_type;


        $description = ($description != '') ? intval($description) : $show_description;


        //fallback as text if addon no installed
        $result_chart_type = CBXPollHelper::chart_type_fallback($result_chart_type); //make sure that if chart type is from pro addon then it's installed


        $poll_answers = get_post_meta($post_id, '_cbxpoll_answer', true);

        $poll_answers = is_array($poll_answers) ? $poll_answers : array();
        $poll_colors  = get_post_meta($post_id, '_cbxpoll_answer_color', true);

        $log_method = $setting_api->get_option('logmethod', 'cbxpoll_global_settings', 'both');
        /*if (isset($setting_api['logmethod'])) {
            $log_method = $setting_api['logmethod'];
        }*/

        //$setting_api->get_option('user_roles', 'cbxpoll_global_settings', 'both')

        //$log_metod = ($log_method != '') ? $log_method : 'both';

        $is_poll_expired = new DateTime($poll_end_date) < new DateTime(); //check if poll expired from it's end data
        $is_poll_expired = ($poll_never_expire == 1) ? false : $is_poll_expired; //override expired status based on the meta information

        //$poll_allowed_user_group = empty($poll_user_roles) ? $setting_api['user_roles'] : $poll_user_roles;
        //$poll_allowed_user_group = empty( $poll_user_roles ) ? $setting_api->get_option( 'user_roles', 'cbxpoll_global_settings', array() ) : $poll_user_roles;
        $poll_allowed_user_group = $poll_user_roles;

        $cb_question_list_to_find_ans = array();
        foreach ($poll_answers as $poll_answer) {
            array_push($cb_question_list_to_find_ans, $poll_answer);
        }


        $nonce = wp_create_nonce('cbxpolluservote');

        $poll_output .= '<div class="cbxpoll_wrapper cbxpoll_wrapper-'.$post_id.' cbxpoll_wrapper-'.$reference.'" data-reference ="'.$reference.'" >';
        //$poll_output .= '<div class="cbxpoll-qresponse cbxpoll-qresponse-' . $post_id . '"></div>';

        //check if the poll started still
        if (new DateTime($poll_start_date) <= new DateTime()) {


            if ($reference != 'content_hook') {
                $poll_output .= '<h3>'.get_the_title($post_id).'</h3>';
            }

            if ($reference != 'content_hook') {
                //if enabled from shortcode and enabled from post meta field
                if (intval($description) == 1) {
                    $poll_conobj  = get_post($post_id);
                    $poll_content = '';
                    if (is_object($poll_conobj)) {
                        $poll_content = $poll_conobj->post_content;
                        $poll_content = strip_shortcodes($poll_content);
                        $poll_content = wpautop($poll_content);
                        $poll_content = convert_smilies($poll_content);
                        //$poll_content 	= apply_filters('the_content', $poll_content);
                        $poll_content = str_replace(']]>', ']]&gt;', $poll_content);
                    }


                    $poll_output .= '<div class="cbxpoll-description">'.apply_filters('cbxpoll_description',
                            $poll_content, $post_id).'</div>';
                }

            }

            $poll_is_voted_by_user = 0;

            if ($log_method == 'cookie') {

                $sql                   = $wpdb->prepare("SELECT COUNT(ur.id) AS count FROM $votes_name ur WHERE  ur.poll_id=%d AND ur.user_id=%d AND ur.user_cookie = %s",
                    $post_id, $user_id, $user_session);
                $poll_is_voted_by_user = $wpdb->get_var($sql);

            } elseif ($log_method == 'ip') {

                $sql                   = $wpdb->prepare("SELECT COUNT(ur.id) AS count FROM $votes_name ur WHERE  ur.poll_id=%d AND ur.user_id=%d AND ur.user_ip = %s",
                    $post_id, $user_id, $user_ip);
                $poll_is_voted_by_user = $wpdb->get_var($sql);

            } else {
                if ($log_method == 'both') {

                    $sql               = $wpdb->prepare("SELECT COUNT(ur.id) AS count FROM $votes_name ur WHERE  ur.poll_id=%d AND ur.user_id=%d AND ur.user_cookie = %s",
                        $post_id, $user_id, $user_session);
                    $vote_count_cookie = $wpdb->get_var($sql);

                    $sql           = $wpdb->prepare("SELECT COUNT(ur.id) AS count FROM $votes_name ur WHERE  ur.poll_id=%d AND ur.user_id=%d AND ur.user_ip = %s",
                        $post_id, $user_id, $user_ip);
                    $vote_count_ip = $wpdb->get_var($sql);

                    if ($vote_count_cookie >= 1 || $vote_count_ip >= 1) {
                        $poll_is_voted_by_user = 1;
                    }

                }
            }

            $poll_is_voted_by_user = apply_filters('cbxpoll_is_user_voted', $poll_is_voted_by_user);

            if ($is_poll_expired) { // if poll has expired

                $sql           = $wpdb->prepare("SELECT ur.id AS answer FROM $votes_name ur WHERE  ur.poll_id=%d  ",
                    $post_id);
                $cb_has_answer = $wpdb->get_var($sql);

                if ($cb_has_answer != null) {

                    $poll_output .= CBXPollHelper:: show_single_poll_result($post_id, $reference, $result_chart_type);
                }

                $sql             = $wpdb->prepare("SELECT ur.user_answer AS answer FROM $votes_name ur WHERE  ur.poll_id=%d AND ur.user_id=%d AND ur.user_ip = %s AND ur.user_cookie = %s ",
                    $post_id, $user_id, $user_ip, $user_session);
                $answers_by_user = $wpdb->get_var($sql);

                $answers_by_user_html = '';

                if ($answers_by_user !== null) {
                    $answers_by_user = maybe_unserialize($answers_by_user);
                    if (is_array($answers_by_user)) {
                        $user_answers_textual = array();
                        foreach ($answers_by_user as $uchoice) {
                            $user_answers_textual[] = isset($poll_answers[$uchoice]) ? $poll_answers[$uchoice] : esc_html__('Unknown or answer deleted',
                                'cbxpoll');
                        }

                        $answers_by_user_html = implode(", ", $user_answers_textual);
                    } else {
                        $answers_by_user      = intval($answers_by_user);
                        $answers_by_user_html = $poll_answers[$answers_by_user];

                    }

                    if ($answers_by_user_html != "") {
                        $poll_output .= '<p class="cbxpoll-voted-info cbxpoll-voted-info-'.$post_id.'">'.sprintf(__('The Poll is out of date. You have already voted for <strong>"%s"</strong>',
                                'cbxpoll'), $answers_by_user_html).' </p>';
                    } else {
                        $poll_output .= '<p class="cbxpoll-voted-info cbxpoll-voted-info-'.$post_id.'"> '.sprintf(__('The Poll is out of date. You have already voted for <strong>"%s"</strong>',
                                'cbxpoll'), $answers_by_user_html).' </p>';

                    }

                } else {
                    $poll_output .= '<p class="cbxpoll-voted-info cbxpoll-voted-info-'.$post_id.'"> '.__('The Poll is out of date. You have not voted.',
                            'cbxpoll').'</p>';
                }

            } // end of if poll expired
            else {
                if (is_user_logged_in()) {
                    global $current_user;
                    $this_user_role = $current_user->roles;
                } else {
                    $this_user_role = array('guest');
                }

                $allowed_user_group = array_intersect($poll_allowed_user_group, $this_user_role);

                //current user is not allowed
                if ((sizeof($allowed_user_group)) < 1) {

                    //we know poll is not expired, and user is not allowed to vote
                    //now we check if the user i allowed to see result and result is allow to show before expire
                    //if ( $poll_show_result_all == '1' && $poll_show_result_before_expire == '1' ) {
                    if ($poll_show_result_before_expire == 1) {
                        if ($poll_is_voted) {
                            $poll_output .= CBXPollHelper::show_single_poll_result($post_id, $reference,
                                $result_chart_type);
                        }

                        $poll_output .= '<p class="cbxpoll-voted-info cbxpoll-voted-info-'.$post_id.'"> '.__('You are not allowed to vote.', 'cbxpoll').'</p>';

                        //integrate user login for guest user

	                    if(!is_user_logged_in() && $allow_guest_sign == 'on'):
		                    if ( is_singular() ) {
			                    $login_url    = wp_login_url( get_permalink() );
			                    $redirect_url = get_permalink();
		                    } else {
			                    global $wp;
			                    //$login_url =  wp_login_url( home_url( $wp->request ) );
			                    $login_url    = wp_login_url( home_url( add_query_arg( array(), $wp->request ) ) );
			                    $redirect_url = home_url( add_query_arg( array(), $wp->request ) );
		                    }


		                    $guest_html = '<div class="cbxpoll-guest-wrap">';

		                    $guest_html .= '<p class="cbxpoll-title-login">' . __( 'Do you have account and want to vote as registered user? Please <a  href="#">login</a>', 'cbxpoll' ) . '</p>';
		                    $guest_login_html = wp_login_form( array(
			                    'redirect' => $redirect_url,
			                    'echo'     => false
		                    ) );


		                    $guest_login_html = apply_filters( 'cbxpoll_login_html', $guest_login_html, $login_url, $redirect_url );

		                    $guest_register_html = '';
		                    $guest_show_register = intval( $settings->get_option( 'guest_show_register', 'cbxpoll_global_settings', 1 ) );
		                    if ( $guest_show_register ) {
			                    if ( get_option( 'users_can_register' ) ) {
				                    $register_url        = add_query_arg( 'redirect_to', urlencode( $redirect_url ), wp_registration_url() );
				                    $guest_register_html .= '<p class="cbxpoll-guest-register">' . sprintf( __( 'No account yet? <a href="%s">Register</a>', 'cbxpoll' ), $register_url ) . '</p>';
			                    }

			                    $guest_register_html = apply_filters( 'cbxpoll_register_html', $guest_register_html, $redirect_url );

		                    }

		                    $guest_html .= '<div class="cbxpoll-guest-login-wrap">'.$guest_login_html.$guest_register_html.'</div>';


		                    $guest_html .= '</div>';

		                    $poll_output .= $guest_html;
	                    endif;

                    }

                } else {
                    //current user is allowed

                    //current user has voted this once
                    if ($poll_is_voted_by_user) {

                        $sql             = $wpdb->prepare("SELECT ur.user_answer AS answer FROM $votes_name ur WHERE  ur.poll_id=%d AND ur.user_id=%d AND ur.user_ip = %s AND ur.user_cookie = %s ",
                            $post_id, $user_id, $user_ip, $user_session);
                        $answers_by_user = $wpdb->get_var($sql);


                        if ($answers_by_user !== null) {
                            $answers_by_user = maybe_unserialize($answers_by_user);
                            if (is_array($answers_by_user)) {
                                $user_answers_textual = array();
                                foreach ($answers_by_user as $uchoice) {
                                    $user_answers_textual[] = isset($poll_answers[$uchoice]) ? $poll_answers[$uchoice] : esc_html__('Unknown or answer deleted',
                                        'cbxpoll');
                                }

                                $answers_by_user_html = implode(", ", $user_answers_textual);
                            } else {
                                $answers_by_user      = intval($answers_by_user);
                                $answers_by_user_html = $poll_answers[$answers_by_user];

                            }


                            if ($answers_by_user_html != "") {
                                $poll_output .= '<p class="cbxpoll-voted-info cbxpoll-voted-info-'.$post_id.'">'.sprintf(__('You have already voted for <strong>"%s"</strong>',
                                        'cbxpoll'), $answers_by_user_html).' </p>';
                            } else {
                                $poll_output .= '<p class="cbxpoll-voted-info cbxpoll-voted-info-'.$post_id.'">'.esc_html__('You have already voted ',
                                        'cbxpoll').' </p>';

                            }
                        }

                        if ($poll_show_result_before_expire == 1) {
                            $poll_output .= CBXPollHelper:: show_single_poll_result($post_id, $reference,
                                $result_chart_type);
                        }

                    } else {
                        //current user didn't vote yet
                        $poll_form_html = '';

                        $poll_form_html = apply_filters('cbxpoll_form_html_before', $poll_form_html, $post_id);

	                    if(!is_user_logged_in() && $allow_guest_sign == 'on'):
		                    if ( is_singular() ) {
			                    $login_url    = wp_login_url( get_permalink() );
			                    $redirect_url = get_permalink();
		                    } else {
			                    global $wp;
			                    //$login_url =  wp_login_url( home_url( $wp->request ) );
			                    $login_url    = wp_login_url( home_url( add_query_arg( array(), $wp->request ) ) );
			                    $redirect_url = home_url( add_query_arg( array(), $wp->request ) );
		                    }


		                    $guest_html = '<div class="cbxpoll-guest-wrap">';

		                    $guest_html .= '<p class="cbxpoll-title-login">' . __( 'Do you have account and want to vote as registered user? Please <a  href="#">login</a>', 'cbxpoll' ) . '</p>';
		                    $guest_login_html = wp_login_form( array(
			                    'redirect' => $redirect_url,
			                    'echo'     => false
		                    ) );


		                    $guest_login_html = apply_filters( 'cbxpoll_login_html', $guest_login_html, $login_url, $redirect_url );

		                    $guest_register_html = '';
		                    $guest_show_register = intval( $settings->get_option( 'guest_show_register', 'cbxpoll_global_settings', 1 ) );
		                    if ( $guest_show_register ) {
			                    if ( get_option( 'users_can_register' ) ) {
				                    $register_url        = add_query_arg( 'redirect_to', urlencode( $redirect_url ), wp_registration_url() );
				                    $guest_register_html .= '<p class="cbxpoll-guest-register">' . sprintf( __( 'No account yet? <a href="%s">Register</a>', 'cbxpoll' ), $register_url ) . '</p>';
			                    }

			                    $guest_register_html = apply_filters( 'cbxpoll_register_html', $guest_register_html, $redirect_url );

		                    }

		                    $guest_html .= '<div class="cbxpoll-guest-login-wrap">'.$guest_login_html.$guest_register_html.'</div>';


		                    $guest_html .= '</div>';

		                    $poll_form_html .= $guest_html;
	                    endif;

                        $poll_form_html .= '								
                                <div class="cbxpoll_answer_wrapper cbxpoll_answer_wrapper-'.$post_id.'" data-id="'.$post_id.'">
                                    <form action="#" class="cbxpoll-form cbxpoll-form-'.$post_id.'" method="post" novalidate="true">
                                        <div class="cbxpoll-form-insidewrap '.$grid_class.' cbxpoll-form-insidewrap-'.$post_id.'">';

                        $poll_form_html = apply_filters('cbxpoll_form_html_before_question', $poll_form_html, $post_id);

                        $poll_answer_list_class = 'cbxpoll-form-ans-list cbxpoll-form-ans-list-'.$post_id;


                        $poll_form_html .= '<ul class="'.apply_filters('cbxpoll_form_answer_list_style_class',
                                $poll_answer_list_class, $post_id).'">';

                        $poll_form_html = apply_filters('cbxpoll_form_answer_start', $poll_form_html, $post_id);

                        //listing poll answers as radio button
                        foreach ($poll_answers as $index => $answer) {

                            $poll_answers_extra_single = isset($poll_answers_extra[$index]) ? $poll_answers_extra[$index] : array('type' => 'default');

                            $input_name = 'cbxpoll_user_answer';
                            if ($poll_multivote) {
                                $input_name .= '-'.$index;
                            }

                            $poll_answer_listitem_class = 'cbxpoll-form-ans-listitem cbxpoll-form-ans-listitem-'.$post_id;

                            $extra_list_style = '';
                            $extra_list_attr  = '';
                            $poll_form_html   .= '<li class="'.apply_filters('cbxpoll_form_answer_listitem_style_class',
                                    $poll_answer_listitem_class, $post_id, $index, $answer,
                                    $poll_answers_extra_single).'" style="'.apply_filters('cbxpoll_form_answer_listitem_style',
                                    $extra_list_style, $post_id, $index, $answer,
                                    $poll_answers_extra_single).'" '.apply_filters('cbxpoll_form_answer_listitem_attr',
                                    $extra_list_attr, $post_id, $index, $answer, $poll_answers_extra_single).'>';

                            $cbxpoll_form_answer_listitem_inside_html_start = '';
                            $poll_form_html                                 .= apply_filters('cbxpoll_form_answer_listitem_inside_html_start',
                                $cbxpoll_form_answer_listitem_inside_html_start, $post_id, $index, $answer,
                                $poll_answers_extra_single);
                            $poll_form_html                                 .= '<div class="checkbox-alignment">';
                            $poll_form_html                                 .= '<input type="'.$vote_input_type.'" value="'.$index.'" class="cbxpoll_single_answer cbxpoll_single_answer-radio cbxpoll_single_answer-radio-'.$post_id.'" data-pollcolor = "'.$poll_colors[$index].' "data-post-id="'.$post_id.'" name="'.$input_name.'"  data-answer="'.$answer.' " id="cbxpoll_single_answer-radio-'.$index.'-'.$post_id.'"  />';
                            $poll_form_html                                 .= '<label class="cbxpoll_single_answer_label cbxpoll_single_answer_label_radio" for="cbxpoll_single_answer-radio-'.$index.'-'.$post_id.'"><span class="cbxpoll_single_answer cbxpoll_single_answer-text cbxpoll_single_answer-text-'.$post_id.'"  data-post-id="'.$post_id.'" data-answer="'.$answer.' ">'.apply_filters('cbxpoll_form_listitem_answer_title',
                                    $answer, $post_id, $index, $poll_answers_extra_single).'</span></label>';
                            $poll_form_html                                 .= '</div>';
                            $cbxpoll_form_answer_listitem_inside_html_end   = '';
                            $poll_form_html                                 .= apply_filters('cbxpoll_form_answer_listitem_inside_html_end',
                                $cbxpoll_form_answer_listitem_inside_html_end, $post_id, $index, $answer,
                                $poll_answers_extra_single);


                            $poll_form_html .= '</li>';
                        }

                        $poll_form_html = apply_filters('cbxpoll_form_answer_end', $poll_form_html, $post_id);


                        $poll_form_html .= '</ul>';

                        //hook
                        $poll_form_html = apply_filters('cbxpoll_form_html_after_question', $poll_form_html, $post_id);

                        //$poll_form_html .= ' <div class="cbxpoll-qresponse cbxpoll-qresponse-' . $post_id . '"></div>';

                        //show the poll button
                        $poll_form_html .= '<p class = "cbxpoll_ajax_link"><button type="submit" class="btn btn-primary button cbxpoll_vote_btn" data-reference = "'.$reference.'" data-charttype = "'.$result_chart_type.'" data-busy = "0" data-post-id="'.$post_id.'"  data-security="'.$nonce.'" >'.esc_html__('Vote',
                                'cbxpoll').'<span class="cbvoteajaximage cbvoteajaximagecustom"></span></button></p>';
                        $poll_form_html .= '<input type="hidden" name="action" value="cbxpoll_user_vote">';
                        $poll_form_html .= '<input type="hidden" name="reference" value="'.$reference.'">';
                        $poll_form_html .= '<input type="hidden" name="chart_type" value="'.$result_chart_type.'">';
                        $poll_form_html .= '<input type="hidden" name="nonce" value="'.$nonce.'">';
                        $poll_form_html .= '<input type="hidden" name="poll_id" value="'.$post_id.'">';
                        $poll_form_html .= '
                                         </div>
                                    </form>
                                    <div class="cbxpoll_clearfix"></div>
                                </div>
                                <div class="cbxpoll-qresponse cbxpoll-qresponse-'.$post_id.'"></div>
                                <div class="cbxpoll_clearfix"></div>';


                        $poll_form_html = apply_filters('cbxpoll_form_html_after', $poll_form_html, $post_id);

                        $poll_output .= apply_filters('cbxpoll_form_html', $poll_form_html, $post_id);

                    }
                    // end of if voted
                }
                // end of allowed user
            }
            // end of pole expires


        }//poll didn't start yet
        else {
            $poll_output = esc_html__('Poll Status: Yet to start', 'cbxpoll');
        }

        $poll_output .= '</div>'; //end of cbxpoll_wrapper

        return $poll_output;
    }//end method cbxpoll_single_display

    /**
     * Get result from a single poll
     *
     * @param  int  $post_id
     *
     * return string|mixed
     */
    public static function show_single_poll_result($poll_id, $reference, $result_chart_type = 'text')
    {
        global $wpdb;

        $current_user = wp_get_current_user();
        $user_id      = $current_user->ID;


        $user_ip = CBXPollHelper::get_ipaddress();

        if ($user_id == 0) {
            $user_session = $_COOKIE[CBX_POLL_COOKIE_NAME]; //this is string
        } elseif (is_user_logged_in()) {
            $user_session = 'user-'.$user_id; //this is string
        }

        $setting_api     = get_option('cbxpoll_global_settings');
        $poll_start_date = get_post_meta($poll_id, '_cbxpoll_start_date', true); //poll start date
        $poll_end_date   = get_post_meta($poll_id, '_cbxpoll_end_date', true); //poll end date
        $poll_user_roles = get_post_meta($poll_id, '_cbxpoll_user_roles', true); //poll user roles
        if (!is_array($poll_user_roles)) {
            $poll_user_roles = array();
        }

        $poll_content                   = get_post_meta($poll_id, '_cbxpoll_content', true); //poll content
        $poll_never_expire              = intval(get_post_meta($poll_id, '_cbxpoll_never_expire',
            true)); //poll never epire
        $poll_show_result_before_expire = intval(get_post_meta($poll_id, '_cbxpoll_show_result_before_expire',
            true)); //poll never epire


        $poll_result_chart_type = get_post_meta($poll_id, '_cbxpoll_result_chart_type', true); //chart type

        $result_chart_type = CBXPollHelper::chart_type_fallback($result_chart_type);

        $poll_answers = get_post_meta($poll_id, '_cbxpoll_answer', true);
        $poll_answers = is_array($poll_answers) ? $poll_answers : array();

        $poll_colors = get_post_meta($poll_id, '_cbxpoll_answer_color', true);
        $poll_colors = is_array($poll_colors) ? $poll_colors : array();

        $total_results = CBXPollHelper::get_pollResult($poll_id);

        $poll_result = array();

        $poll_result['reference'] = $reference;
        $poll_result['poll_id']   = $poll_id;
        $poll_result['total']     = count($total_results);

        $poll_result['colors'] = $poll_colors;
        $poll_result['answer'] = $poll_answers;
        //$poll_result['results']    		= json_encode($total_results);
        $poll_result['chart_type'] = $result_chart_type;
        $poll_result['text']       = '';

        $poll_answers_weight = array();


        foreach ($total_results as $result) {
            $user_ans = maybe_unserialize($result['user_answer']);

            if (is_array($user_ans)) {

                foreach ($user_ans as $u_ans) {
                    $old_val                     = isset($poll_answers_weight[$u_ans]) ? intval($poll_answers_weight[$u_ans]) : 0;
                    $poll_answers_weight[$u_ans] = ($old_val + 1);
                }
            } else {
                $user_ans                       = intval($user_ans);
                $old_val                        = isset($poll_answers_weight[$user_ans]) ? intval($poll_answers_weight[$user_ans]) : 0;
                $poll_answers_weight[$user_ans] = ($old_val + 1);
            }
        }

        $poll_result['answers_weight'] = $poll_answers_weight;

        //ready mix :)
        $poll_weighted_index  = array();
        $poll_weighted_labels = array();

        foreach ($poll_answers as $index => $answer_title) {
            //$poll_weighted_labels[ $answer ] = isset( $poll_answers_weight[ $index ] ) ? $poll_answers_weight[ $index ] : 0;
            $poll_weighted_index[$index]         = isset($poll_answers_weight[$index]) ? $poll_answers_weight[$index] : 0;
            $poll_weighted_labels[$answer_title] = isset($poll_answers_weight[$index]) ? $poll_answers_weight[$index] : 0;
        }

        $poll_result['weighted_index'] = $poll_weighted_index;
        $poll_result['weighted_label'] = $poll_weighted_labels;


        ob_start();

        do_action('cbxpoll_answer_html_before', $poll_id, $reference, $poll_result);
        echo '<div class="cbxpoll_result_wrap cbxpoll_result_wrap_'.$reference.' cbxpoll_'.$result_chart_type.'_result_wrap cbxpoll_'.$result_chart_type.'_result_wrap_'.$poll_id.' cbxpoll_result_wrap_'.$reference.'_'.$poll_id.' ">';

        do_action('cbxpoll_answer_html_before_question', $poll_id, $reference, $poll_result);

        $poll_display_methods = CBXPollHelper::cbxpoll_display_options();
        $poll_display_method  = $poll_display_methods[$result_chart_type];

        $method = $poll_display_method['method'];

        if ($method != '' && is_callable($method)) {
            call_user_func_array($method, array($poll_id, $reference, $poll_result));
        }

        do_action('cbxpoll_answer_html_after_question', $poll_id, $reference, $poll_result);

        echo '</div>';
        do_action('cbxpoll_answer_html_after', $poll_id, $reference, $poll_result);

        $output = ob_get_contents();
        ob_end_clean();

        return $output;

    }

    /**
     * Chart Type fallback
     *
     * @param $chart_type
     *
     * @return string
     */
    public static function chart_type_fallback($chart_type)
    {
        $poll_display_methods = CBXPollHelper::cbxpoll_display_options();
        $chart_info           = (isset($poll_display_methods[$chart_type])) ? $poll_display_methods[$chart_type] : '';

        if ($chart_info != '' && is_callable($chart_info['method'])) {
            return $chart_type;
        }

        return 'text';
    }//end method chart_type_fallback

    /**
     * Sanitizes a hex color.
     *
     * Returns either '', a 3 or 6 digit hex color (with #), or nothing.
     * For sanitizing values without a #, see sanitize_hex_color_no_hash().
     *
     * @param  string  $color
     *
     * @return string|void
     * @since 3.4.0
     *
     */
    public static function sanitize_hex_color($color)
    {

        if ('' === $color) {
            return '';
        }

        // 3 or 6 hex digits, or the empty string.
        if (preg_match('|^#([A-Fa-f0-9]{3}){1,2}$|', $color)) {
            return $color;
        }
    }//end method sanitize_hex_color

    /**
     * cbxpoll post type meta fields array
     *
     * @return array
     *
     * initialize with init
     */
    public static function get_meta_fields()
    {

        $roles           = CBXPollHelper::user_roles(false, true);
        $global_settings = get_option('cbxpoll_global_settings');


        $default_user_roles = isset($global_settings['user_roles']) ? $global_settings['user_roles'] : CBXPollHelper::user_roles(true,
            true);

        $default_never_expire   = isset($global_settings['never_expire']) ? intval($global_settings['never_expire']) : 0;
        $default_content        = isset($global_settings['content']) ? $global_settings['content'] : 1;
        $default_result_chart   = isset($global_settings['result_chart_type']) ? $global_settings['result_chart_type'] : 'text';
        $default_poll_multivote = isset($global_settings['poll_multivote']) ? intval($global_settings['poll_multivote']) : 0;
        //$default_show_result_all           = isset( $global_settings['show_result_all'] ) ? intval($global_settings['show_result_all']) : 0;
        $default_show_result_before_expire = isset($global_settings['show_result_before_expire']) ? intval($global_settings['show_result_before_expire']) : 0;


        // Field Array
        $prefix = '_cbxpoll_';


        $poll_display_methods = CBXPollHelper::cbxpoll_display_options();
        $poll_display_methods = CBXPollHelper::cbxpoll_display_options_linear($poll_display_methods);


        $start_date = new DateTime();
        $timestamp  = time() - 86400;
        $end_date   = strtotime("+7 day", $timestamp);

        $post_meta_fields = array(

            '_cbxpoll_start_date'   => array(
                'label'   => esc_html__('Start Date', 'cbxpoll'),
                'desc'    => __('Poll Start Date. [<strong> Note:</strong> Field required. Default is today]',
                    'cbxpoll'),
                'id'      => '_cbxpoll_start_date',
                'type'    => 'date',
                'default' => $start_date->format('Y-m-d H:i:s')
            ),
            '_cbxpoll_end_date'     => array(
                'label'   => esc_html__('End Date', 'cbxpoll'),
                'desc'    => __('Poll End Date.  [<strong> Note:</strong> Field required. Default is next seven days. ]',
                    'cbxpoll'),
                'id'      => '_cbxpoll_end_date',
                'type'    => 'date',
                'default' => date('Y-m-d H:i:s', $end_date)
            ),
            '_cbxpoll_user_roles'   => array(
                'label'    => esc_html__('Who Can Vote', 'cbxpoll'),
                'desc'     => esc_html__('Which user role will have vote capability', 'cbxpoll'),
                'id'       => '_cbxpoll_user_roles',
                'type'     => 'multiselect',
                'options'  => $roles,
                'optgroup' => 1,
                'default'  => $default_user_roles
            ),
            '_cbxpoll_content'      => array(
                'label'   => esc_html__('Show Poll Description in shortcode', 'cbxpoll'),
                'desc'    => esc_html__('Select if you want to show content.', 'cbxpoll'),
                'id'      => '_cbxpoll_content',
                'type'    => 'radio',
                'default' => $default_content,
                'options' => array(
                    '1' => esc_html__('Yes', 'cbxpoll'),
                    '0' => esc_html__('No', 'cbxpoll')
                )

            ),
            '_cbxpoll_never_expire' => array(
                'label'   => esc_html__('Never Expire', 'cbxpoll'),
                'desc'    => 'Select if you want your poll to never expire.(can be override from shortcode param)',
                'id'      => '_cbxpoll_never_expire',
                'type'    => 'radio',
                'default' => $default_never_expire,
                'options' => array(
                    '1' => esc_html__('Yes', 'cbxpoll'),
                    '0' => esc_html__('No', 'cbxpoll')
                )
            ),

            '_cbxpoll_show_result_before_expire' => array(
                'label'   => esc_html__('Show result before expires', 'cbxpoll'),
                'desc'    => esc_html__('Select if you want poll to show result before expires. After expires the result will be shown always. Please check it if poll never expires.',
                    'cbxpoll'),
                'id'      => '_cbxpoll_show_result_before_expire',
                'type'    => 'radio',
                'default' => $default_show_result_before_expire,
                'options' => array(
                    '1' => esc_html__('Yes', 'cbxpoll'),
                    '0' => esc_html__('No', 'cbxpoll')
                )
            ),
            /*'_cbxpoll_show_result_all'           => array(
                'label'   => esc_html__( 'Show result to all', 'cbxpoll' ),
                'desc'    => esc_html__( 'Check this if you want to show result to them who can not vote.', 'cbxpoll' ),
                'id'      => '_cbxpoll_show_result_all',
                'type'    => 'radio',
                'default' => $default_show_result_all,
                'options' => array(
                    '1' => esc_html__( 'Yes', 'cbxpoll' ),
                    '0' => esc_html__( 'No', 'cbxpoll' )
                )
            ),*/  //removed for good
            '_cbxpoll_result_chart_type'         => array(
                'label'   => esc_html__('Result Chart Style', 'cbxpoll'),
                'desc'    => esc_html__('Select how you want to show poll result.', 'cbxpoll'),
                'id'      => '_cbxpoll_result_chart_type',
                'type'    => 'select',
                'options' => $poll_display_methods,  //new poll display method can be added via plugin
                'default' => $default_result_chart
            ),
            '_cbxpoll_multivote'                 => array(
                'label'   => esc_html__('Enable Multi Choice', 'cbxpoll'),
                'desc'    => esc_html__('Can user vote multiple option', 'cbxpoll'),
                'id'      => '_cbxpoll_multivote',
                'type'    => 'radio',
                'default' => $default_poll_multivote,
                'options' => array(
                    '1' => esc_html__('Yes', 'cbxpoll'),
                    '0' => esc_html__('No', 'cbxpoll')
                )
            ),
        );

        return apply_filters('cbxpoll_fields', $post_meta_fields);
    }//end method get_meta_fields


    /**
     * Single answer field template
     *
     * @param  int  $index
     * @param  string  $answers_title
     * @param  string  $answers_color
     * @param  int  $is_voted
     * @param        $answers_extra
     * @param        $poll_postid
     *
     * @return string
     */
    public static function cbxpoll_answer_field_template(
        $index = 0,
        $answers_title = '',
        $answers_color = '',
        $is_voted = 0,
        $answers_extra,
        $poll_postid
    ) {


        $input_type  = 'text';
        $color_class = 'cbxpoll_answer_color';


        $answer_type = isset($answers_extra['type']) ? $answers_extra['type'] : 'default';

        $answer_fields_html = '<li class="cbx_poll_items" id="cbx-poll-answer-'.$index.'">';


        $answer_fields_html .= '<span class="cbx_pollmove"><i title="'.esc_html__('Drag and Drop to reorder poll answers',
                'cbxpoll').'" class="cbpollmoveicon">'.esc_html__('Move', 'cbxpoll').'</i></span>';


        $answer_fields_html .= '
                        <input type="'.$input_type.'" style="width:330px;" name="_cbxpoll_answer['.$index.']" value="'.$answers_title.'"   id="cbxpoll_answer-'.$index.'" class="cbxpoll_answer"/>
                        <input type="'.$input_type.'" id="cbxpoll_answer_color-'.$index.'" class="'.$color_class.'" name="_cbxpoll_answer_color['.$index.']" size="8"  value="'.$answers_color.'" />';

        $answer_fields_html_extra = '<input type="hidden" id="cbxpoll_answer_extra_type_'.$index.'" value="'.$answer_type.'" name="_cbxpoll_answer_extra['.$index.'][type]" />';

        $answer_fields_html_extra = apply_filters('cbxpoll_answer_extra_fields', $answer_fields_html_extra, $index,
            $answers_extra, $is_voted, $poll_postid);

        $answer_fields_html .= $answer_fields_html_extra;


        $answer_fields_html .= '<span class="cbx_pollremove dashicons dashicons-trash" title="'.esc_html__('Remove',
                'cbxpoll').'"></span>';


        $answer_fields_html .= '<div class="clear clearfix"></div></li>';

        return $answer_fields_html;
    }//end method cbxpoll_answer_field_template

    /**
     * Get all votes of a user by various criteria
     *
     * @param  int  $user_id
     * @param  string  $orderby
     * @param  string  $order
     * @param  int  $perpage
     * @param  int  $page
     * @param  string  $status
     *
     * @return array|null|object
     */
    public static function getAllVotesByUser(
        $user_id = 0,
        $orderby = 'id',
        $order = 'desc',
        $perpage = 20,
        $page = 1,
        $status = 'all'
    ) {

        $user_id = intval($user_id);
        $data    = array();
        if (intval($user_id) == 0) {
            return $data;
        }

        global $wpdb;
        $votes_name = cbxpollHelper::cbx_poll_table_name();


        $sql_select = "SELECT * FROM $votes_name";

        $where_sql = '';


        if (is_numeric($status)) {
            if ($where_sql != '') {
                $where_sql .= ' AND ';
            }
            $where_sql .= $wpdb->prepare('published=%d', intval($status));
        }

        if (intval($user_id) > 0) {
            if ($where_sql != '') {
                $where_sql .= ' AND ';
            }
            $where_sql .= $wpdb->prepare('user_id=%d', intval($user_id));
        }


        if ($where_sql == '') {
            $where_sql = '1';
        }

        $limit_sql = '';

        if ($perpage != -1) {
            $perpage     = intval($perpage);
            $start_point = ($page * $perpage) - $perpage;
            $limit_sql   .= "LIMIT";
            $limit_sql   .= ' '.$start_point.',';
            $limit_sql   .= ' '.$perpage;
        }


        $sortingOrder = " ORDER BY $orderby $order ";


        $data = $wpdb->get_results("$sql_select  WHERE  $where_sql $sortingOrder  $limit_sql", 'ARRAY_A');

        return $data;
    }//end method getAllVotesByUser

    /**
     * Get all votes by different criteria
     *
     * @param  string  $orderby
     * @param  string  $order
     * @param  int  $perpage
     * @param  int  $page
     * @param  int  $poll_id
     * @param  string  $status
     * @param  int  $vote_id
     *
     * @return array|null|object
     */
    public static function getAllVotes(
        $orderby = 'id',
        $order = 'desc',
        $perpage = 20,
        $page = 1,
        $poll_id = 0,
        $status = 'all',
        $vote_id = 0
    ) {
        $poll_id = intval($poll_id);
        $vote_id = intval($vote_id);

        global $wpdb;
        $votes_name = cbxpollHelper::cbx_poll_table_name();


        $sql_select = "SELECT * FROM $votes_name";

        $where_sql = '';
        if ($poll_id != 0) {
            if ($where_sql != '') {
                $where_sql .= ' AND ';
            }
            $where_sql .= $wpdb->prepare('poll_id=%d', $poll_id);
        }

        if ($vote_id > 0) {
            if ($where_sql != '') {
                $where_sql .= ' AND ';
            }
            $where_sql .= $wpdb->prepare('id=%d', $vote_id);
        }

        if (is_numeric($status)) {
            if ($where_sql != '') {
                $where_sql .= ' AND ';
            }
            $where_sql .= $wpdb->prepare('published=%d', intval($status));
        }


        if ($where_sql == '') {
            $where_sql = '1';
        }

        $limit_sql = '';

        if ($perpage != -1) {
            $perpage     = intval($perpage);
            $start_point = ($page * $perpage) - $perpage;
            $limit_sql   .= "LIMIT";
            $limit_sql   .= ' '.$start_point.',';
            $limit_sql   .= ' '.$perpage;
        }


        $sortingOrder = " ORDER BY $orderby $order ";


        $data = $wpdb->get_results("$sql_select  WHERE  $where_sql $sortingOrder  $limit_sql", 'ARRAY_A');

        return $data;
    }//end method getAllVotes


    /**
     * Get total vote count based on multiple criteria
     *
     * @param  int  $poll_id
     * @param  string  $status
     * @param  int  $vote_id
     *
     * @return null|string
     */
    public static function getVoteCount($poll_id = 0, $status = 'all', $vote_id = 0)
    {

        $poll_id = intval($poll_id);
        $vote_id = intval($vote_id);

        global $wpdb;
        $votes_name = cbxpollHelper::cbx_poll_table_name();

        $sql_select = "SELECT COUNT(*) FROM $votes_name";

        $where_sql = '';
        if ($poll_id != 0) {
            if ($where_sql != '') {
                $where_sql .= ' AND ';
            }
            $where_sql .= $wpdb->prepare('poll_id=%d', $poll_id);
        }

        if ($vote_id > 0) {
            if ($where_sql != '') {
                $where_sql .= ' AND ';
            }
            $where_sql .= $wpdb->prepare('id=%d', $vote_id);
        }

        if (is_numeric($status)) {
            if ($where_sql != '') {
                $where_sql .= ' AND ';
            }
            $where_sql .= $wpdb->prepare('published=%d', intval($status));
        }


        if ($where_sql == '') {
            $where_sql = '1';
        }


        $count = $wpdb->get_var("$sql_select  WHERE  $where_sql");

        return $count;
    }//end method getVoteCount

    /**
     * Get single vote information usign vote id
     *
     * @param $vote_id
     *
     * @return array|null|object|void
     */
    public static function getVoteInfo($vote_id)
    {
        global $wpdb;

        $votes_name = cbxpollHelper::cbx_poll_table_name();
        $sql        = $wpdb->prepare("SELECT * FROM $votes_name WHERE id=%d ", intval($vote_id));
        $log_info   = $wpdb->get_row($sql, ARRAY_A);

        return $log_info;
    }//end method getVoteInfo

    /**
     * Add utm params to any url
     *
     * @param  string  $url
     *
     * @return string
     */
    public static function url_utmy($url = '')
    {
        if ($url == '') {
            return $url;
        }

        $url = add_query_arg(array(
            'utm_source'   => 'plgsidebarinfo',
            'utm_medium'   => 'plgsidebar',
            'utm_campaign' => 'wpfreemium',
        ), $url);

        return $url;
    }//end url_utmy

    /**
     * Random color
     *
     * https://thisinterestsme.com/random-rgb-hex-color-php/
     *
     * @return string[]
     */
    public static function randomColor()
    {
        $result = array('rgb' => '', 'hex' => '');
        foreach (array('r', 'b', 'g') as $col) {
            $rand = mt_rand(0, 255);
            //$result['rgb'][$col] = $rand;
            $dechex = dechex($rand);
            if (strlen($dechex) < 2) {
                $dechex = '0'.$dechex;
            }
            $result['hex'] .= $dechex;
        }

        return $result;
    }//end randomColor

	/**
	 * Bookmark login form
	 *
	 * @since 1.2.4
	 *
	 * @return array
	 */
	public static function guest_login_forms() {
		$forms = array();

		$forms['wordpress'] = esc_html__( 'WordPress Core Login Form', 'cbxpoll' );

		return apply_filters( 'cbxpoll_guest_login_forms', $forms );
	}//end guest_login_forms

}//end class CBXPollHelper