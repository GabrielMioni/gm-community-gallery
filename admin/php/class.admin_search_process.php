<?php

namespace GM_community_gallery\admin;

require_once(GM_GALLERY_DIR . '/nav/abstract.collect_input.php');

class admin_search_process
{
    protected $input_array;
    protected $date_array;

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

    protected function set_session_value(array $input_array, $date_array)
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