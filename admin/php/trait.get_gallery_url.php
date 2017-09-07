<?php

namespace GM_community_gallery\admin;

trait get_gallery_url
{
    protected function get_gallery_url($sub_dir = false)
    {
        $wp_get_upload_dir = wp_get_upload_dir();

        switch ($sub_dir)
        {
            case 'images':
                return $wp_get_upload_dir['baseurl'] . '/gm-community-gallery/images/';
                break;
            case 'thumbs':
                return $wp_get_upload_dir['baseurl'] . '/gm-community-gallery/thumbs/';
                break;
            default:
                return $wp_get_upload_dir['baseurl'] . '/gm-community-gallery/';
                break;
        }
    }

    protected function get_settings_page_url()
    {
        return admin_url('admin.php?page=gm-community-submit');
    }
}