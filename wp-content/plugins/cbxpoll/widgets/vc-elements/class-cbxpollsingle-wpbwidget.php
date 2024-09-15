<?php
// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}


class CBXPoll_WPBWidget extends WPBakeryShortCode
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('init', array($this, 'bakery_shortcode_mapping'), 12);
    }// /end of constructor

    /**
     * Element Mapping
     */
    public function bakery_shortcode_mapping()
    {

        $poll_display_methods = \CBXPollHelper::cbxpoll_display_options();
        $display_methods_arr  = array();

        foreach ($poll_display_methods as $key => $method) {
            $display_methods_arr[esc_attr($method['title'])] = $key;
        }
        $display_methods_arr[esc_html__('Use from poll post setting', 'cbxpoll')] = '';

        /*if(!is_admin()){
            global $post;
        }

        $args = array(
            'post_type'      => 'cbxpoll',
            'orderby'        => 'ID',
            'order'          => 'DESC',
            'post_status'    => 'publish',
            'posts_per_page' => - 1,
        );

        $cbxpoll_posts = get_posts( $args );

        $cbxpoll_posts_arr = array();
        $cbxpoll_posts_arr[esc_html__('Select Poll', 'cbxpoll')] = 0;

        foreach ( $cbxpoll_posts as $post ) :
            \CBXPollHelper::setup_admin_postdata( $post );
            $post_id    = get_the_ID();
            $post_title = get_the_title();

            $cbxpoll_posts_arr[ $post_title ] = esc_attr( $post_id );
        endforeach;
        \CBXPollHelper::wp_reset_admin_postdata();*/


        // Map the block with vc_map()
        vc_map(array(
            "name"        => esc_html__("CBX Poll", 'cbxpoll'),
            "description" => esc_html__("CBX Poll Widget", 'cbxpoll'),
            "base"        => "cbxpoll",
            "icon"        => CBX_POLL_PLUGIN_ROOT_URL.'assets/images/singlepoll_icon.png',
            "category"    => esc_html__('CBX Widgets', 'cbxpoll'),
            "params"      => apply_filters('cbxpoll_wpbakery_params',
                array(
                    array(
                        'type'        => 'textfield',
                        "class"       => "",
                        'admin_label' => true,
                        'heading'     => esc_html__('Poll Post ID', 'cbxpoll'),
                        'param_name'  => 'id',
                        //'value'       => $cbxpoll_posts_arr,
                        'std'         => 0,
                        'description' => esc_html__('Put poll post id', 'cbxpoll'),
                    ),
                    array(
                        'type'        => 'dropdown',
                        'heading'     => esc_html__('Chart Type', 'cbxpoll'),
                        'param_name'  => 'chart_type',
                        'admin_label' => true,
                        'value'       => $display_methods_arr,
                        'std'         => 'text',
                        'description' => esc_html__('Select Chart Type', 'cbxpoll'),
                    ),
                    array(
                        'type'        => 'dropdown',
                        "class"       => "",
                        'admin_label' => true,
                        'heading'     => esc_html__('Show Poll description', 'cbxpoll'),
                        'param_name'  => 'description',
                        'value'       => array(
                            esc_html__('Yes', 'cbxpoll')                        => '1',
                            esc_html__('No', 'cbxpoll')                         => '0',
                            esc_html__('Use from poll post setting', 'cbxpoll') => '',
                        ),
                        'std'         => 1,
                    ),
                    array(
                        'type'        => 'dropdown',
                        "class"       => "",
                        'admin_label' => true,
                        'heading'     => esc_html__('Answer Format', 'cbxpoll'),
                        'param_name'  => 'grid',
                        'value'       => array(
                            esc_html__('List', 'cbxpoll')                       => '0',
                            esc_html__('Grid', 'cbxpoll')                       => '1',
                            esc_html__('Use from poll post setting', 'cbxpoll') => '',
                        ),
                        'std'         => 0,
                    ),

                )
            )
        ));
    }//end bakery_shortcode_mapping
}// end class cbxpoll_WPBWidget