<?php

/**
 * @package     GM-Community-Gallery
 * @author      Gabriel Mioni <gabriel@gabrielmioni.com>
 */

namespace GM_community_gallery\submit;

/**
 * Builds either the HTML contact form or a thank you message. The thank you message is set if $_SESSION['gm_success']
 * is set. Else, the contact form is set.
 *
 * Class gm_community_gallery_html is called in the gm_gallery_form_shortcode() function and displayed using the WordPress short code
 * [gm-submit-form][/gm-submit-form]
 */
class image_upload_form
{
    /** @var    string  Holds HTML for either the contact form or a thank you message. */
    protected $html;

    public function __construct()
    {
        // Check query string and set $this->html appropriately
        if (isset($_SESSION['gm_success']))
        {
            $this->html = $this->set_thankyou();
        } else {
            $this->html = $this->set_form();
        }

        $this->unset_session_msgs();
    }

    /**
     * Checks for previously submitted input values and any form input validation errors and builds the HTML email
     * contact form.
     *
     * @return string   HTML for the Contact Form.
     */
    protected function set_form()
    {
        if (!isset($_SESSION))
        {
            session_start();
        }

        $form_action = plugin_dir_url( __FILE__ ) . 'index.php?gm_community_gallery=1';

        $error_name    = $this->set_error('gm_error_name');
        $error_email   = $this->set_error('gm_error_email');
        $error_image   = $this->set_error('gm_error_image');
        $error_title   = $this->set_error('gm_error_title');
        $error_message = $this->set_error('gm_error_message');

        $value_name    = $this->set_input_value('gm_value_name');
        $value_email   = $this->set_input_value('gm_value_email');
        $value_image   = $this->set_input_value('gm_value_company');
        $value_title   = $this->set_input_value('gm_value_titlte');
        $value_message = $this->set_input_value('gm_value_message');


        $form  = '<div id="gm_gallery_submit">';

        $form .= "<form id='gm-gallery' method='post' action='$form_action' enctype='multipart/form-data'>";
        $form .= '<script>(function($) { $(\'#gm-gallery form\').hide();}); </script>';
        $form .=   "<label class='gm_full_width' for='name'>Your Name <span class='gm-asterisk'>*</span> $error_name </label>
                    <input class='gm_full_width' name='name' value='$value_name' type='text' maxlength='100'>        
                    <label class='gm_full_width' for='email'>Your Email <span class='asterisk'>*</span> $error_email </label>
                    <input class='gm_full_width' name='email' value='$value_email' type='text' maxlength='100' >
                    <label class='gm_full_width' for='title'>Title <span class='asterisk'>*</span> $error_title </label>
                    <input class='gm_full_width' name='title' value='$value_title' type='text' maxlength='100'>
                    <label class='gm_full_width' for='image'>Image <span class='asterisk'>* </span> $error_image </label>
                    <input class='gm_full_width' name='image' value='$value_image' type='file'>
                    <label class='gm_full_width' for='message'>Message <span class='asterisk'>* </span> $error_message </label>
                    <textarea class='gm_full_width' name='message' maxlength='600'>$value_message</textarea>
                    <input class='gm_full_width' value='Send' name='submit' type='submit'>
                </form>";
        $form .= '</div>';

        session_destroy();

        return $form;
    }

    /**
     * Returns HTML elements for error messages by checking the value of $_SESSION[$error_index].
     *
     * @param   $error_index    string  The key for the session element that needs to be checked.
     * @return  string  If $_SESSION[$error_index] is set, returns error message HTML element. Else, whitespace.
     */
    protected function set_error($error_index)
    {
        if (isset($_SESSION[$error_index]))
        {
            $error_msg = $_SESSION[$error_index];
            unset($_SESSION[$error_index]);
            return "<div class='gm-error'>$error_msg</div>";
        }

        return '';
    }

    /**
     * Returns previously submitted input values by checking the value of $_SESSION[$input_index]
     *
     * @param $input_index  string  The key for the session element that needs to be checked.
     * @return string   If $_SESSION[$input_index] is set, returns the session element value. Else, whitespace
     */
    protected function set_input_value($input_index)
    {
        if (isset($_SESSION[$input_index]))
        {
            return strip_tags($_SESSION[$input_index]);
        } else {
            return '';
        }
    }

    /**
     * Unset $_SESSION variables that have been previously set by the gm_contact_email_send class.
     *
     * @return void
     */
    protected function unset_session_msgs()
    {
        $session_keys = array_keys($_SESSION);

        foreach ($session_keys as $key)
        {
            $check_key = $this->session_is_gm($key);

            if ($check_key === true)
            {
                unset($_SESSION[$key]);
            }
        }
    }

    /**
     * Checks $_SESSION keys passed as $key. If it matches the pattern for session variable keys used by
     * gm_contact_email_send, returns true. Else false.
     *
     * @param   $key    string  The $_SESSION key being checked.
     * @return  bool    If $key matches the pattern used for gm-contact-email $_SESSION keys, return true. Else, false.
     */
    protected function session_is_gm($key)
    {
        $pattern = '~(^|[^x])gm_(error|value|success)~';

        $check = preg_match($pattern, $key);

        if ($check === 1)
        {
            return true;
        }

        return false;
    }

    /**
     * @return string   HTML thank you message
     */
    protected function set_thankyou()
    {
        return '<div class=\'response\'>Thank you! Your message has been sent.</div>';
    }

    /**
     * @return string   The HTML that's been set (either the HTML contact form or a thank you message.)
     */
    public function return_html()
    {
        return $this->html;
    }
}