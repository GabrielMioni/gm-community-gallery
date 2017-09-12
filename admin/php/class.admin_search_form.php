<?php

/**
 * @package     GM-Community-Gallery
 * @author      Gabriel Mioni <gabriel@gabrielmioni.com>
 */

namespace GM_community_gallery\admin;

// use GM_community_gallery\nav\collect_input;

require_once(GM_GALLERY_DIR . 'nav/abstract.collect_input.php');

/**
 * Builds HTML for the admin search form found at /wp-admin/admin.php?page=gm-community-gallery&edit=3PudKn
 * The form action is set to index.php/gm_gallery_admin_search=1 - This triggers the WordPress init hook, which looks
 * if $_GET['gm_gallery_admin_search'] isset. If so, a admin_search_process object is created.
 *
 * Display of results is handled by the admin_gallery class.
 *
 * @package GM_community_gallery\admin
 * @see admin_search_process
 * @see admin_gallery
 */
class admin_search_form
{
    protected $form_html;

    public function __construct()
    {
        $this->form_html = $this->build_form();
    }

    /**
     * @return  string  HTML for the admin search form
     */
    protected function build_form()
    {
        $name_value  = $this->set_value_from_session('gm_value_name');
        $email_value = $this->set_value_from_session('gm_value_email');
        $title_value = $this->set_value_from_session('gm_value_title');
        $ip_value    = $this->set_value_from_session('gm_value_ip');

        $start_value = $this->set_value_from_session('gm_value_start');
        $end_value   = $this->set_value_from_session('gm_value_end');

        $form_action = plugin_dir_url( __FILE__ ) . 'index.php?gm_gallery_admin_search=1';

        $html  = "  
                    <form id='gm_gallery_search' method='post' action='$form_action'>
                        <table class='form-table'>
                            <tbody>
                                <tr>
                                    <th scope='row'><label for='name'>Submitter Name</label></th>
                                    <td><input class='regular-text' id='name' name='name' value='$name_value' type='text'></td>
                                </tr>
                                <tr>
                                    <th scope='row'><label for='email'>Submitter Email</label></th>
                                    <td><input class='regular-text' id='email' name='email' value='$email_value' type='text'></td>
                                </tr>
                                <tr>
                                    <th scope='row'><label for='title'>Title</label></th>
                                    <td><input class='regular-text' id='title' name='title' value='$title_value' type='text'></td>
                                </tr>
                                <tr>
                                    <th scope='row'><label for='ip'>IP</label></th>
                                    <td><input class='regular-text' id='ip' name='ip' value='$ip_value' type='text'></td>
                                </tr>
                        
                                <tr>
                                    <th scope='row'><label for='date_start'>Date Start</label></th>
                                    <td><input class='regular-text' id='date_start' name='date_start' value='$start_value' type='text'></td>
                                </tr>
                                <tr>
                                    <th scope='row'><label for='date_end'>Date End</label></th>
                                    <td><input class='regular-text' id='date_end' name='date_end' value='$end_value' type='text'></td>
                                </tr>
                                <tr class='full_row button_container'>
                                    <td>
                                        <input class='button' type='submit'>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </form>";

        $this->destroy_gm_sessions();

        return $html;
    }

    /**
     * Checks if $_SESSION variables are present. If so, return the value. Else return whitespace. This is used to
     * retain field values entered previously when the admin_search_process class finishes running and redirects back
     * to the admin gallery.
     *
     * @param   $index  string  The index for the $_SESSION variable being checked.
     * @return  string          If data is at $_SESSION[$index], return value. Else, return whitespace
     */
    protected function set_value_from_session($index)
    {
        return isset($_SESSION[$index]) ? htmlentities($_SESSION[$index], ENT_QUOTES) : '';
    }

    /**
     * Destroys $_SESSION variables associated with the admin search. If the page reloads we don't want the input
     * values to remain forever.
     */
    protected function destroy_gm_sessions()
    {
        foreach ($_SESSION as $key=>$value)
        {
            if ( strpos($key, 'gm_value_') !== false )
            {
                unset($_SESSION[$key]);
            }
        }
    }

    /**
     * @return string   HTML for the admin search form.
     */
    public function return_search_form()
    {
        return $this->form_html;
    }
}