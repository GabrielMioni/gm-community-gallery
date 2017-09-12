<?php

/**
 * @package     GM-Community-Gallery
 * @author      Gabriel Mioni <gabriel@gabrielmioni.com>
 */

namespace GM_community_gallery\admin;

require_once('trait.get_gallery_url.php');

/**
 * Builds HTML for the image edit form. The image edit form is displayed when a user clicks on an image in the
 * admin gallery at wp-admin/admin.php?page=gm-community-gallery.  Each image is nested with a link that sets
 * $_GET['edit'] to the alphanumeric id associated with the image record.
 *
 * Changes made to the image's record are processed at class.image_update_process.php
 *
 * @package GM_community_gallery\admin
 * @see image_update_process
 *
 */
class image_update_form
{
    /** @var bool|string    The value of $_GET['edit'] */
    protected $image_id;

    /** @var array|bool     Array data retrieved for the record associated with $this->image_id */
    protected $image_data;

    /** @var string         HTML for the image edit form */
    protected $form_html;

    // gm_gallery_url() and get_settings_page_url() are in this trait
    use get_gallery_url;

    public function __construct()
    {
        $this->image_id = $this->set_image_id();
        $this->image_data = $this->get_image_data($this->image_id);

        $this->form_html = $this->build_edit_form($this->image_data);
    }

    /**
     * Grab the alphanumeric $_GET['edit'] value. This is used to look up the image being edited.
     *
     * @return bool|string  False if no data is found. Else returns value of $_GET['edit']
     */
    protected function set_image_id()
    {
        $out = false;
        if (isset($_GET['edit']))
        {
            $out = strip_tags($_GET['edit']);
        } elseif (isset($_POST['edit']))
        {
            $out = strip_tags($_POST['edit']);
        }

        return $out;
    }

    /**
     * Get an array of data for the image record on the gm_community_gallery MySQL table where id = $id
     *
     * @param   $id     string  Alphanumeric ID set at $_GET['edit']
     * @return  bool|array      If no data is found through the prepared statement, return false. Else return array with image data.
     */
    protected function get_image_data($id)
    {
        $table_name = GM_GALLERY_TABLENAME;
        $query = "SELECT * FROM $table_name WHERE id=%s";

        global $wpdb;

        $prepare = $wpdb->prepare($query, [$id]);
        $result  = $wpdb->get_results($prepare, ARRAY_A);

        if ( ! empty($result) )
        {
            return $result[0];
        }

        return false;
    }


    /**
     * Builds HTML parent element for the edit form. Includes the actual image being edited.
     *
     * @param   array   $image_data     Image data found for the image being edited.
     * @return  string                  HTML for containing element of the edit form (also the actual image and the edit form).
     */
    protected function build_edit_form(array $image_data)
    {
        $id = $image_data['id'];
//        $thumb_url = $this->get_gallery_url('thumbs') . $id . '.jpg';
        $thumb_url = $this->get_gallery_url('images') . $id . '.jpg';
        $image_url = $this->get_gallery_url('images') . $id . '.jpg';
        $form = $this->build_form_table($image_data);

        $html  = "<div id='image_edit_card'>";
        $html .= "<div id='image_thumb'><a href='$image_url' target='_blank'><img src='$thumb_url'></a></div>";
        $html .= "<div id='form_wrapper'>$form</div>";
        $html .= '</div>';

        return $html;
    }

    /**
     * Builds HTML for the text inputs and textareas for the edit form. The edit form includes a nonce that is
     * validated at class.image_upload_process.php
     *
     * @param   array $image_data   Array data for the image record being edited.
     * @return  string              HTML for the edit form
     * @see     image_update_process::validate_nonce()
     */
    protected function build_form_table(array $image_data)
    {
        $title = $image_data['title'];
        $submitter = $image_data['name'];
        $email = $image_data['email'];
        $message = $image_data['message'];
        $id = $image_data['id'];
        $ip = $image_data['ip'];
        $comment = $image_data['comment'];

        $form_action = plugin_dir_url( __FILE__ ) . 'index.php?gm_community_admin=1';

        $form  = "<form method='post' action='$form_action'>";
        $form .= '<table class="form-table">';
        $form .= '<tbody>';

        // Title
        $form .= $this->set_form_rows('Title','title', $title);

        // Submitter Name
        $form .= $this->set_form_rows('Submitter Name','name', $submitter);

        // Submitter Email
        $form .= $this->set_form_rows('Submitter Email','email', $email);

        // Submitter IP
        $form .= $this->set_form_rows('IP','ip', $ip);

        // Message
        $form .= $this->set_form_rows('Message','message', $message, 'textarea');

        // Comment
        $form .= $this->set_form_rows('Comment','comment', $comment, 'textarea');

        $form .= '</tbody>';
        $form .= '</table>';
        $form .= "<input id='id' name='id' type='hidden' value='$id'>";
        $form .= wp_nonce_field('gm_admin_image_update');
        $form .= '<tr id="submit_response"></tr>';
        $form .= $this->set_response_message();
        $form .= $this->set_submit_row();
        $form .= '<form>';

        session_destroy();

        return $form;
    }

    /**
     * @return  string  HTML for the trash and submit buttons.
     */
    protected function set_submit_row()
    {
        $row  = '<p class="submit">';
        $row .= '<input type="submit" class="button button-primary" value="Save Changes" id="gm_image_submit" name="gm_image_update">';
        $row .= '<input type="submit" class="button button-primary" value="Move to trash" id="gm_image_delete" name="gm_image_update">';
//        $row .= '<input name="submit" id="submit" class="button button-primary" value="Save Changes" type="submit">';
        $row .= '</p>';

        return $row;
    }

    /**
     * Builds a <tr> element with a label element and either an embeded text input, textarea or nothing.
     *
     * @param $label        string  Text that should appear in the label
     * @param $name_and_id  string  The element's name and id
     * @param $value        string  The value for the input or textarea
     * @param string $type          Sets whether a text input, textarea or just a <td> element will be created.
     *                              'textarea' and 'no_input' can be accepted. Default will create a text input.
     * @return string               HTML for the form <tr> element.
     */
    protected function set_form_rows($label, $name_and_id, $value, $type='text')
    {
        $row  = '<tr>';
        $row .= "<th scope='row'><label for='$name_and_id'>$label</label></th>";

//        $value = htmlspecialchars( stripslashes($value) );
        $value = htmlentities( stripslashes($value), ENT_QUOTES );

        switch ($type)
        {
            case 'textarea':
                $row .= "<td><textarea id='$name_and_id' name='$name_and_id'>" . $value . "</textarea></td>";
                break;
            case 'no_input':
                $row .= "<td id='$name_and_id'>" . $value . "</td>";
                break;
            default:
                $row .= "<td><input name='$name_and_id' id='$name_and_id' value='" . $value . "' class='regular-text' type='text'></td>";
                break;
        }

        $row .= '</tr>';

        return $row;
    }

    /**
     * Sets a response message that's displayed if the user has edited the image data or moved the image to trash
     * (or if there's some kind of error).
     *
     * @return  string  Checks $_SESSION['response'] to look for a response that's set at class.image_update_process.php
     * @see image_update_process::set_response_message()
     */
    protected function set_response_message()
    {
        $html = '';

        if (isset($_SESSION['response']))
        {
            $response = json_decode($_SESSION['response'], true);
        } else {
            return '';
        }

        if (isset($response['error']))
        {
            $message = $response['error'];
            $html .= "<div id=\"response\" class=\"error\">$message</div>";
        } elseif (isset($response['success']))
        {
            $message = $response['success'];
            $html .= "<div id=\"response\" class=\"updated\">$message</div>";
        }

        unset($_SESSION['response']);

        return $html;

    }

    /**
     * @return string   HTML for the image edit form.
     */
    public function return_html_form()
    {
        return $this->form_html;
    }


}