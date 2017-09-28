<?php

/**
 * @package     GM-Community-Gallery
 * @author      Gabriel Mioni <gabriel@gabrielmioni.com>
 */

namespace GM_community_gallery\admin;

require_once(GM_GALLERY_DIR . 'nav/abstract.gallery.php');

use GM_community_gallery\nav\gallery as gallery;
use GM_community_gallery\nav\navigate as navigate;

/**
 * Builds HTML for the admin gallery displayed at /wp-admin/admin.php?page=gm-community-submit
 *
 * The class accepts a navigate class object through the constructor. That class object is also shared with
 * the pagination class used to build pagination HTML.
 *
 * @package GM_community_gallery\admin
 */
abstract class abstract_admin_gallery extends gallery
{
    public function __construct(navigate $navigate)
    {
        parent::__construct($navigate);
    }

    /**
     * Builds HTML for the admin gallery.
     *
     * @param   $gallery_data   array   Gallery data from the gm_contact_gallery MySQL table
     * @return  string                  HTML to display the admin gallery.
     */
    protected function build_gallery_html(array $gallery_data)
    {
        $gallery_html = '<div id="gm_settings_gallery">';

        // This is the water,
        foreach ($gallery_data as $key=>$image)
        {
            $gallery_html .= $this->build_settings_image_card($image);
        }

        // close the #gm_settings_gallery div.
        $gallery_html .= '</div>';

        $form_action = plugin_dir_url( __FILE__ ) . 'index.php?gm_community_mass_action=1';

        $action_input = $this->create_action_input();
        $submit_button = $this->create_submit_button();
        $response = $this->build_response();
        $nonce = wp_nonce_field('gm_mass_action_nonce', 'gm_mass_action_nonce', false);

        $form_html = "<form id='gallery_form' method='post' action='$form_action'>";
        $form_html .= $nonce;

        $form_html .= "<table id='gm_bulk_action_row'><tbody><tr><th><input id='cb-select-all-1' name='gm_bulk_action' type='checkbox'></th><th>$action_input</th><th>$submit_button</th><th>$response</th></tr></tbody></table>";
        $form_html .= $gallery_html;
        $form_html .= '</form>';

        return $form_html;
    }

    /**
     * @return  string  Set the input that specifies the type of action needed for the mass update.
     */
    abstract function create_action_input();

    /**
     * @return string   Set the submit button displayed for the admin gallery.
     */
    abstract function create_submit_button();

    /**
     * Set a response display for the user. This uses $_SESSION data set in class.admin_mass_action.php. Destroys $_SESSION
     * data after HTML is created.
     *
     * @return string   HTML to display a response message.
     * @see admin_mass_action::build_response()
     */
    protected function build_response()
    {
        $response = isset($_SESSION['gm_response']) ? json_decode($_SESSION['gm_response'], true) : false;

        $html = '';

        if (isset($response['success']))
        {
            $message = $response['success'][0];

            $html .=  "<div class='gm_response notice success'>$message</div>";
        } elseif (isset($response['error'])) {

            $message = $response['error'][0];
            $html .= "<div class='gm_response notice error'>$message</div>";
        }

        unset($_SESSION['gm_response']);

        return $html;
    }

    protected function build_undo()
    {

    }

    /**
     * Builds HTML for 'image frames' that display the image being processed with some basic info including
     * the image's title, submitter's name and the submit date.
     *
     * @param $image_data    array   Single nested array element from parent::$gallery_data
     * @return string           HTML to display the image being processed
     */
    protected function build_settings_image_card($image_data)
    {
        $id = $this->set_value($image_data['id']);
        $title = $this->set_value($image_data['title']);
        $type  = $this->set_value($image_data['type']);
        $created = date('m/d/Y g:ia', strtotime( $this->set_value($image_data['created']) ) );
        $submitter = $this->set_value($image_data['name']);
        $checkbox_name = 'gm_mass_update[]';
        $hidden_icon = $this->return_hidden_icon($image_data['hidden']);

//        $image_file  = $id . '.jpg';
        $image_file = "$id.jpg";
        $gallery_url = $this->get_gallery_url();

        $image_url = $gallery_url .'thumbs/' . $image_file;

        $edit_url  = $this->get_settings_page_url();
        $edit_url .= '&edit=' . $id;

        $hidden_class = $image_data['hidden'] === '1' ? "class='gm_hidden_filter'" : "";

        $div = "<div class='image_card'>
                    <div class='gm_img_title'>
                        <input type='checkbox' name='$checkbox_name' value='$id'>
                        <div class='title'>$title</div>
                        $hidden_icon
                    </div>
                    <div class='image_frame'>
                        <img src='$image_url'>
                        <a $hidden_class href='$edit_url'></a>
                    </div>
                    <div class='info'>
                        <div class='submitter'>$submitter</div>
                        <div class='created'>$created</div>
                    </div>
                </div>";

        // and this is the well.
        return $div;
    }

    /**
     * Display a font-awesome icon based on the hidden state of the image record.
     *
     * @param   $hidden     int     Hidden value from the gm_community_gallery table
     * @return  string      Font-awesome element.
     */
    protected function return_hidden_icon($hidden)
    {
        switch ($hidden)
        {
            case 0:
                return '<i class="fa fa-eye" aria-hidden="true"></i>';
                break;
            case 1:
                return '<i class="fa fa-eye-slash" aria-hidden="true"></i>';
                break;
        }
    }

}