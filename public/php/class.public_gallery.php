<?php

/**
 * @package     GM-Community-Gallery
 * @author      Gabriel Mioni <gabriel@gabrielmioni.com>
 */

namespace GM_community_gallery\_public;

require_once(GM_GALLERY_DIR . 'nav/abstract.gallery.php');

use GM_community_gallery\nav\gallery as gallery;

class public_gallery extends gallery
{
    public function __construct(\GM_community_gallery\nav\navigate $navigate)
    {
        parent::__construct($navigate);
    }

    protected function build_gallery_html(array $gallery_data)
    {
        $html = '<div id="gm_community_gallery">';

        // This is the water,
        foreach ($gallery_data as $key=>$image)
        {
            $html .= $this->build_gallery_frame($image);
        }

        // close the #gm_settings_gallery div.
        $html .= '</div>';

        return $html;
    }

    protected function build_gallery_frame(array $image_data)
    {
        $id = $this->set_value($image_data['id']);
        $title = $this->set_value($image_data['title']);
//        $created = date('m/d/Y g:ia', strtotime( $this->set_value($image_data['created']) ) );
//        $submitter = $this->set_value($image_data['name']);

        $image_file  = $id . '.jpg';
        $gallery_url = $this->get_gallery_url();

        $image_url = $gallery_url .'thumbs/' . $image_file;

        $div = "<div class='image_card'>
                    <div class='image_frame'>
                        <span class='helper'></span><img src='$image_url'><a href=''><span>$title</span></a>
                    </div>
                </div>";

        // and this is the well.
        return $div;
    }
}
