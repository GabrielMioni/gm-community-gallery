<?php

require_once(GM_GALLERY_DIR . '/nav/abstract.image_view.php');

use GM_community_gallery\nav\image_view as image_view;

class public_view extends image_view
{
    public function __construct()
    {
        parent::__construct('view');
    }

    protected function build_view(array $image_data)
    {
        $id = $image_data['id'];
        /*
        $title = htmlentities( stripslashes($image_data['title']), ENT_QUOTES );
        $submitter = htmlentities( stripslashes($image_data['name']), ENT_QUOTES );
        $message = htmlentities( stripslashes($image_data['message']), ENT_QUOTES );
        $comment = htmlentities( stripslashes($image_data['comment']), ENT_QUOTES );
        */
        $title = $this->remove_slashes_convert_chars($image_data['title']);
        $submitter = $this->remove_slashes_convert_chars($image_data['name']);
        $message = $this->convert_nl2p($image_data['message']);
        $comment = $this->convert_nl2p($image_data['comment']);

        $image_url = $this->get_gallery_url('images') . $id . '.jpg';

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