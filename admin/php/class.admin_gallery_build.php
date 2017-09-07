<?php

namespace GM_community_gallery\admin;

use GM_community_gallery\nav\gm_nav_abstract;

require_once(GM_GALLERY_DIR . 'nav/abstract.gm_nav.php');
require_once('trait.get_gallery_url.php');

class admin_gallery_build extends gm_nav_abstract
{
    protected $gallery_url;
    protected $image_data;

    protected $admin_gallery_html;

    // gm_gallery_url() and get_settings_page_url() are in this trait
    use get_gallery_url;

    public function __construct()
    {
        parent::__construct();

        $this->gallery_url = $this->get_gallery_url();

        $this->image_data = $this->get_gallery_data();
        $this->admin_gallery_html = $this->build_admin_gallery($this->image_data);
    }

    protected function get_gallery_data()
    {
        $query_stuff = $this->build_query_and_args($this->input_array, false);

        if (!is_array($query_stuff))
        {
            return false;
        }

        $query = $query_stuff['query'];
        $args  = $query_stuff['args'];

        global $wpdb;

        $prep    = $wpdb->prepare($query, $args);
        $results = $wpdb->get_results($prep, ARRAY_A);

        if (is_array($results))
        {
            return $results;
        }

        return false;
    }


    protected function build_admin_gallery($image_data)
    {
        $html = '<div id=\'gm_settings_gallery\'>';

        // This is the water,
        foreach ($image_data as $key=>$image)
        {
            $html .= $this->build_settings_image_card($image);

        }

        // close the #gm_settings_gallery div.
        $html .= '</div>';

        return $html;
    }

    protected function build_settings_image_card($image)
    {
        $id = strip_tags($image['id']);
        $title = strip_tags($image['title']);
        $created = date('m/d/Y g:ia', strtotime(strip_tags($image['created'])));
        $submitter = strip_tags($image['name']);

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

    public function return_admin_gallery_html()
    {
        return $this->admin_gallery_html;
    }


}