<?php

/**
 * @package     GM-Community-Gallery
 * @author      Gabriel Mioni <gabriel@gabrielmioni.com>
 */

namespace GM_community_gallery\admin;

/**
 * Processes the submitted update from the image edit form found at wp-admin/admin.php?page=gm-community-gallery&edit=[id]
 * The row for the associated image in the gm_community_gallery MySQL table will be updated.
 *
 * This class also sets a response message in $_SESSION['response']
 *
 * @package GM_community_gallery\admin
 * @see image_update_form
 */
class image_update_process
{
    /** @var bool TRUE if nonce was validate, else false */
    protected $nonce_ok;

    /** @var string The image id set at $_GET['edit'] */
    protected $image_id;

    /** @var array Holds input values from the image edit form */
    protected $input_array = array();

    /** @var false|int -1 if image was moved to trash. 1 if record was updated, false if not */
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

        $this->set_response_message($this->sql_update_flag);

        $this->do_redirect();
    }

    /**
     * Validate the nonce set class.admin_update_form.php
     *
     * @return bool     True if nonce is valid. Else false.
     * @see image_update_form::build_image_view()
     */
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

    /**
     * Collects input values.
     *
     * @param   $index  string  Checks for values at $_POST[$index]
     * @return          string  If no value is found, return whitespace. Else, return value with tags stripped.
     */
    protected function set_input($index)
    {
        if (isset($_POST[$index]))
        {
            return strip_tags($_POST[$index]);
        }

        return '';
    }

    /**
     * Updates the row in the gm_community_gallery MySQL table associated with the $id.
     *
     * @param   array   $input_array    Array with input values from
     * @param   $id     string          The id for the row that needs to be updated.
     * @return  false|int   -1 if succesfully moved to trash, 1 if updated, false if not updated
     */
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

    /**
     * Sets whether the request is an update to table columns or a moving the image to trash.
     *
     * @return bool|int     If update, return 1, if moving to trash, return -1. Else return false (something is wrong)
     */
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

    /**
     * Checks results of $this->update_gm_community_gallery_table and creates an appropriate message. The message is
     * stored at $_SESSION['error'] or $_SESSION['success'] and is processed for display at class.image_update_form.php
     *
     * @param   $sql_update_flag    int|bool
     * @see image_update_process::set_response_message()
     */
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