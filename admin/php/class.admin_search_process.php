<?php

/**
 * @package     GM-Community-Gallery
 * @author      Gabriel Mioni <gabriel@gabrielmioni.com>
 */

namespace GM_community_gallery\admin;

require_once(GM_GALLERY_DIR . '/nav/abstract.collect_input.php');

/**
 * Accepts $_POST data from the admin search form created in class.admin_search_form.php and organizes
 * the data into a URL query string that is passed back to the admin gallery screen.
 *
 * @package GM_community_gallery\admin
 * @see class admin_search_form
 */
class admin_search_process
{
    /** @var array Holds input data from the admin search form */
    protected $input_array;

    /** @var array Holds dates if set in the admin search form */
    protected $date_array;

    /** @var string The URL for the admin gallery with the new query string appeneded.  */
    protected $redirect;

    public function __construct()
    {
        $this->input_array['paginate'] = $this->check_post_or_get_for_value('paginate');
        $this->input_array['title'] = $this->check_post_or_get_for_value('title');
        $this->input_array['name']  = $this->check_post_or_get_for_value('name');
        $this->input_array['email'] = $this->check_post_or_get_for_value('email');
        $this->input_array['ip']    = $this->check_post_or_get_for_value('ip');
        $this->date_array           = $this->set_date_search();

        $this->redirect = $this->build_redirect($this->input_array, $this->date_array);

        $this->set_session_value($this->input_array, $this->date_array);
        $this->do_redirect($this->redirect);
    }

    /**
     * Lookds for $_GET or $_POST data at the index specified by $index.
     *
     * @param   $index      string  The index being checked.
     * @return  bool|string
     */
    protected function check_post_or_get_for_value($index)
    {
        $value = isset($_GET[$index]) ? $_GET[$index] : null;

        if ($value === null)
        {
            $value = isset($_POST[$index]) ? $_POST[$index] : null;
        }

        if ($value === null)
        {
            return false;
        }

        return strip_tags(trim($value));
    }

    /**
     * Returns array with start / end dates if they're being searched for.
     *
     * @return array    If no dates are provided, returns empty array. Else returns star / end dates.
     */
    protected function set_date_search()
    {
        $tmp = array();
        $date_start = $this->check_post_or_get_for_value('date_start');
        $date_end   = $this->check_post_or_get_for_value('date_end');

        if ($date_start !== false || $date_start !== '')
        {
            $tmp[] = $date_start;
            
        }
        if ($date_end !== false || $date_start !== '')
        {
            $tmp[] = $date_end;
        }

        return $tmp;
    }

    /**
     * Builds a query string and appends it to the GM Community Gallery URL. Used to redirect the user.
     *
     * @param   array   $input_array    Array with inputs from the admin search form.
     * @param   array   $date_array     Array with date inputs from the admin search form.
     * @return  string
     */
    protected function build_redirect(array $input_array, array $date_array)
    {
        $url = admin_url('admin.php?page=gm-community-gallery&');

        foreach ($input_array as $key=>$value)
        {
            if ($value !== false && $value !== '')
            {
                $url .=  "$key=$value&";
            }
        }

        foreach ($date_array as $date)
        {
            if ($date !== false && $date !== '')
            {
                $url .= "date[]=$date&";
            }

        }

        $url = rtrim($url, '&');

        return $url;
    }

    /**
     * Set $_SESSION array with values that can be presented in the admin search form so the user doesn't need to
     * re-enter them. These sessions are destroyed at admin_search_form->destroy_gm_sessions() after they're used
     * to build the search form.
     *
     * @param   array   $input_array    Array with inputs from the admin search form.
     * @param   array   $date_array     Array with date inputs from the admin search form.
     */
    protected function set_session_value(array $input_array, array $date_array)
    {
        foreach ($input_array as $key=>$value)
        {
            $index_name = 'gm_value_' . $key;
            $_SESSION[$index_name] = htmlspecialchars($value);
        }

        if (isset($date_array[0]))
        {
            $_SESSION['gm_value_start'] = htmlspecialchars($date_array[0]);
        }

        if (isset($date_array[1]))
        {
            $_SESSION['gm_value_end'] = htmlspecialchars($date_array[1]);
        }
    }

    protected function do_redirect($redirect)
    {
        header('Location: ' . $redirect);
        exit();
    }
}