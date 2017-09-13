<?php

namespace GM_community_gallery\admin;

/**
 * This trait is used to retireve the Admin URL for the GM Community Gallery plugin and the directory/sub-directory
 * of the directory where images are are stored ( /wp-content/uploads/gm-community-gallery/ )
 *
 * There's a cleaner way to do this, but I'm leaving it in place for now.
 *
 * @package GM_community_gallery\admin
 * @todo Make a prettier way of sharing this functionality between classes where it's needed.
 */
trait get_gallery_url
{
    /**
     * @param   bool|string     $sub_dir    Default will return the directory for the GM community gallery uploaded
     *                                      images. Settings the argument to 'images' or 'thumbs' will return the associated directory.
     * @return  string                      The directory being requested.
     */
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

    /**
     * @return string   The admin URL for GM Community Gallery
     */
    protected function get_settings_page_url()
    {
        return admin_url('admin.php?page=gm-community-gallery');
    }
}