<?php

namespace GM_community_gallery\admin;

class image_update_process
{
    protected $nonce_ok;

    protected $image_id;
    protected $input_array = array();

    protected $sql_update_flag;

    public function __construct()
    {
        $this->nonce_ok = $this->validate_nonce();

        $this->image_id = $this->set_input('id');

        $this->input_array['title'] = $this->set_input('title');
        $this->input_array['name']  = $this->set_input('name');
        $this->input_array['email'] = $this->set_input('email');
        $this->input_array['ip']    = $this->set_input('ip');
        $this->input_array['message'] = $this->set_input('message');
        $this->input_array['comment'] = $this->set_input('comment');

        $this->validate_nonce();

        $this->sql_update_flag = $this->update_gm_community_gallery_table($this->input_array, $this->image_id);

        /*  This is used for testing
        foreach ($_POST as $key=>$value)
        {
            $_SESSION[$key] = $value;
        }
        */

        $this->set_response_message($this->sql_update_flag);

        $this->do_redirect();
    }

    protected function validate_nonce()
    {
        $nonce = $_POST['_wpnonce'];
        $nonce_ok = wp_verify_nonce($nonce, 'gm_admin_image_update');

        if ($nonce_ok !== false)
        {
            return true;
        }

        die('You have now power here.');
    }

    protected function set_input($index)
    {
        if (isset($_POST[$index]))
        {
            return strip_tags($_POST[$index]);
        }

        return '';
    }

    protected function update_gm_community_gallery_table(array $input_array, $id)
    {
        $table_name = GM_GALLERY_TABLENAME;
        $args = array();

        $query = "UPDATE $table_name SET";

        foreach ($input_array as $key=>$value)
        {
            if (trim($value) !== '')
            {
                $query .= " $key = %s,";
                $args[] = $value;
            }
        }

        $query = rtrim($query, ',');

        $action_type = $this->set_action_type();

        if ($action_type === -1)
        {
            $query .= ', trash = %d';
            $args[] = 1;
        } else {
            $query .= ', trash = %d';
            $args[] = 0;
        }

        $query .= " WHERE id=%s LIMIT 1";
        $args[] = $id;

        $_SESSION['args'] = $args;


        global $wpdb;

        $_SESSION['query'] = $query;

        $prepare = $wpdb->prepare($query, $args);
        $result = $wpdb->query($prepare);

        // Image was moved to trash successfully
        if ($result === 1 && $action_type === -1)
        {
            return -1;
        }

        return $result;
    }

    protected function set_action_type()
    {
        $action_type = isset($_POST['gm_image_update']) ? trim($_POST['gm_image_update']) : false;

        switch ($action_type)
        {
            case 'Save Changes':
                return 1;
                break;
            case 'Move to trash':
                return -1;
                break;
            default:
                return false;
                break;
        }
    }

    protected function set_response_message($sql_update_flag)
    {
        $message = array();

        switch ($sql_update_flag)
        {
            case 0:
                $message['error'] = 'No update was performed';
                break;
            case -1:
                $message['error'] = 'Image was moved to trash';
                break;
            case false:
                $message['error'] = 'There was a problem processing your request';
                break;
            default:
                $message['success'] = 'Update successful';
                break;
        }

        $_SESSION['response'] = json_encode($message);
    }

    /**
     * Sends user back to the page from which the submit page was submitted.
     */
    protected function do_redirect()
    {
        header( 'Location: ' . $_SERVER["HTTP_REFERER"] );
        exit();
    }
}