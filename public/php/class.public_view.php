<?php

/**
 * @package     GM-Community-Gallery
 * @author      Gabriel Mioni <gabriel@gabrielmioni.com>
 */

require_once(GM_GALLERY_DIR . '/nav/abstract.image_view.php');

use GM_community_gallery\nav\image_view as image_view;

/**
 * Displays public images without the JS lightbox.
 */
class public_view extends image_view
{
    public function __construct()
    {
        // Parent constructor collects the id value for the image. Set by $_GET['edit']
        parent::__construct('view');
    }

    /**
     * @param   array   $image_data     Data from the gm_community_gallery MySQL table, corresponding to the id
     *                                  set by the parent __constructor().
     * @return  string                  HTML for the image.
     */
    protected function build_view(array $image_data)
    {
        $id = $image_data['id'];
        $title = $this->remove_slashes_convert_chars($image_data['title']);
        $submitter = $this->remove_slashes_convert_chars($image_data['name']);
        $message = $this->convert_nl2p($image_data['message']);
        $comment = $this->convert_nl2p($image_data['comment']);
        $type    = $image_data['type'];

        $image_url = $this->get_gallery_url('images') . $id . '.' . $type;

        $html = "
                    <div id='gm_image_view'>
                        <div id='gm_image_wrapper'>
                            <h3 id='gm_title'><i>$title</i> by $submitter</h3>
                            <div id='gm_image'><img src='$image_url'></div>
                            <div id='gm_info'>
                                <div id='gm_message'>$message</div>
                                <div id='gm_comment'>$comment</div>
                            </div>                        
                        </div>
                    </div>";

        return $html;
    }


}