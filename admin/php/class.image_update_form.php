<?php

namespace GM_community_gallery\admin;

require_once('trait.get_gallery_url.php');

class build_image_edit_form
{
    protected $image_id;
    protected $image_data;

    protected $form_html;

    // gm_gallery_url() and get_settings_page_url() are in this trait
    use get_gallery_url;

    public function __construct()
    {
        $this->image_id = $this->set_image_id();
        $this->image_data = $this->get_image_data($this->image_id);

        $this->form_html = $this->build_edit_form($this->image_data);
    }

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

    protected function build_edit_form($image_data)
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

    protected function build_form_table($image_data)
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

    protected function set_submit_row()
    {
        $row  = '<p class="submit">';
        $row .= '<input type="submit" class="button button-primary" value="Save Changes" id="gm_image_submit" name="gm_image_update">';
        $row .= '<input type="submit" class="button button-primary" value="Move to trash" id="gm_image_delete" name="gm_image_update">';
//        $row .= '<input name="submit" id="submit" class="button button-primary" value="Save Changes" type="submit">';
        $row .= '</p>';

        return $row;
    }

    protected function set_form_rows($label, $name_and_id, $value, $type='text')
    {
        $row  = '<tr>';
        $row .= "<th scope='row'><label for='$name_and_id'>$label</label></th>";

        switch ($type)
        {
            case 'textarea':
                $row .= "<td><textarea id='$name_and_id' name='$name_and_id'>$value</textarea></td>";
                break;
            case 'no_input':
                $row .= "<td id='$name_and_id'>$value</td>";
                break;
            default:
                $row .= "<td><input name='$name_and_id' id='$name_and_id' value='$value' class='regular-text' type='text'></td>";
                break;
        }

        $row .= '</tr>';

        return $row;
    }

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
            $html .= "<div id='response' class='error'>$message</div>";
        } elseif (isset($response['success']))
        {
            $message = $response['success'];
            $html .= "<div id='response' class='updated'>$message</div>";
        }

        unset($_SESSION['response']);

        return $html;

    }

    public function return_html_form()
    {
        return $this->form_html;
    }


}