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

        $submitter = ctype_space($image_data['name'])    ? '' : $this->remove_slashes_convert_chars($image_data['name']);
        $message   = ctype_space($image_data['message']) ? '' : $this->convert_nl2p($image_data['message']);
        $comment   = ctype_space($image_data['comment']) ? '' : $this->convert_nl2p($image_data['comment']);


        $image_file  = $id . '.jpg';
        $gallery_url = $this->get_gallery_url();

        $is_mobile = wp_is_mobile();

        $image_url = '';
        if ($is_mobile)
        {
            $image_url .= $gallery_url .'images/' . $image_file;
        } else {
            $image_url .= $gallery_url .'thumbs/' . $image_file;
        }


        $link_url  = $this->build_url($id);

        $div = "<div class='image_card'>
                    <div class='image_frame'>
                        <span class='helper'></span><img src='$image_url'><a class='gm_image_hover' href='$link_url'><span>$title</span></a>
                    </div>
                    <div class='gm_hidden_info'>
                        <span class='gm_title'>$title</span>
                        <span class='gm_submitter'>$submitter</span>
                        <span class='gm_message'>$message</span>
                        <span class='gm_reply'>$comment</span>                        
                    </div>
                </div>";

        // and this is the well.
        return $div;
    }

    protected function convert_nl2p($value)
    {
        $html = '';

        $exp = explode(PHP_EOL, $value);

        foreach ($exp as $value)
        {
            if ( ctype_space($value) === false )
            {
                $html .= '<p>' . $this->remove_slashes_convert_chars($value) .'</p>';
            }

        }

        return $html;
    }

    protected function remove_slashes_convert_chars($value)
    {
        return htmlentities( stripslashes($value) );
    }

    protected function build_url($id)
    {
        $url = strtok($_SERVER["REQUEST_URI"],'?');
        return $url . '?view=' . $id;
    }
}
