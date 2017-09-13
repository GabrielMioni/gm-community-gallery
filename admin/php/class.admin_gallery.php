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
class admin_gallery extends gallery
{
    public function __construct(navigate $navigate)
    {
        // $navigate is passed to parent::get_gallery_data(), which populates the parent::$gallery_data array
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
        $html = '<div id="gm_settings_gallery">';

        // This is the water,
        foreach ($gallery_data as $key=>$image)
        {
            $html .= $this->build_settings_image_card($image);
        }

        // close the #gm_settings_gallery div.
        $html .= '</div>';

        return $html;
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
        $created = date('m/d/Y g:ia', strtotime( $this->set_value($image_data['created']) ) );
        $submitter = $this->set_value($image_data['name']);

        $image_file  = $id . '.jpg';
        $gallery_url = $this->get_gallery_url();

        $image_url = $gallery_url .'thumbs/' . $image_file;

        $edit_url  = $this->get_settings_page_url();
        $edit_url .= '&edit=' . $id;

        $div = "<div class='image_card'>
                    <div class='image_frame'>
                        <img src='$image_url'>
                        <a href='$edit_url'></a>
                    </div>
                    <div class='info'>
                        <div class='title'>$title</div>
                        <div class='submitter'>$submitter</div>
                        <div class='created'>$created</div>
                    </div>
                </div>";

        // and this is the well.
        return $div;
    }

}