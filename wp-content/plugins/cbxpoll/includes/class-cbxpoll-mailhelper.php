<?php

/**
 * Class CBXPollMailHelper
 *
 *  Mail send helper class
 */
class CBXPollMailHelper
{
    public $mail_format;
    //public $mail_from_address;
    //public $mail_from_name;


    public function __construct($mail_format)
    {
        $this->mail_format = $mail_format;
        //$this->mail_from_address = $mail_from_address;
        //$this->mail_from_name    = $mail_from_name;

    }

    /**
     * Callback for 'wp_mail_from' filter
     *
     * @param  type  $content_type
     *
     * @return type
     */
    public function filter_wp_mail_from($content_type)
    {
        return $this->mail_from_address;
    }

    /**
     * Callback for 'wp_mail_from_name' filter
     *
     * @param  type  $name
     *
     * @return type
     */
    public function filter_wp_mail_from_name($name)
    {
        return str_replace('{sitename}', get_bloginfo('name'), $this->mail_from_name);
    }

    /**
     * Filter the format of the sending mail
     *
     * @param  type  $content_type
     *
     * @return string
     */
    public function filter_mail_content_type($content_type = 'text/plain')
    {
        if ($this->mail_format == 'html') {
            return 'text/html';
        } elseif ($this->mail_format == 'plain') {
            return 'text/plain';
        } elseif ($this->mail_format == 'multipart') {
            return 'multipart/mixed';
        } else {
            return $content_type;
        }
    }

    /**
     * Formats the header of the mail
     *
     * @param  string  $from
     * @param  string  $name
     * @param  string  $cc
     * @param  string  $bcc
     * @param  string  $reply_to
     *
     * @return array
     */
    public function email_header($from = '', $name = '', $cc = '', $bcc = '', $reply_to = '')
    {
        $cc_array  = explode(',', $cc);
        $bcc_array = explode(',', $bcc);

        foreach ($cc_array as $key => $cc) {
            $headers[] = 'CC: '.$cc;
        }

        foreach ($bcc_array as $key => $bcc) {
            $headers[] = 'BCC: '.$bcc;
        }

        if ($reply_to != '') {
            $headers[] = 'Reply-To: '.$reply_to;
        }

        if ($from != '') {
            if ($name != '') {
                $name      = str_replace('{sitename}', get_bloginfo('name'), $name);
                $headers[] = 'From: '.$name.'<'.$from.'>';
            } else {
                $headers[] = 'From: '.$from;
            }
        }

        return $headers;
    }


    /**
     * Run email related filters before sending email
     */
    public function applyPreMailSendFilters()
    {
        //add_filter( 'wp_mail_from', array( $this, 'filter_wp_mail_from' ) );
        //add_filter( 'wp_mail_from_name', array( $this, 'filter_wp_mail_from_name' ) );
        add_filter('wp_mail_content_type', array($this, 'filter_mail_content_type'));
    }

    /**
     * Run email related filters after sending email
     */
    public function applyPostMailSendFilters()
    {
        //remove_filter( 'wp_mail_from', array( $this, 'filter_wp_mail_from' ) );
        //remove_filter( 'wp_mail_from_name', array( $this, 'filter_wp_mail_from_name' ) );
        remove_filter('wp_mail_content_type', array($this, 'filter_mail_content_type'));
    }

    /**
     * sends email and return email send status
     *
     * @param  string  $to
     * @param  string  $subject
     * @param  string  $message
     * @param  array  $header
     * @param  array  $attachments
     *
     * @return bool
     */
    public function wp_mail($to = '', $subject = '', $message = '', $header = array(), $attachments = array())
    {
        $this->applyPreMailSendFilters();

        $email_status = wp_mail($to, $subject, $message, $header, $attachments);

        $this->applyPostMailSendFilters();

        return $email_status;
    }
}//end method CBXPollMailHelper