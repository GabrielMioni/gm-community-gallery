<?php

/**
 * @package     GM-Community-Gallery
 * @author      Gabriel Mioni <gabriel@gabrielmioni.com>
 */

namespace GM_community_gallery\nav;

/**
 * Abstract class used to gather image data from the GM Community Gallary MySQL table and build HTML to display
 * single images.
 *
 * @package GM_community_gallery\nav
 */
abstract class image_view
{
    protected $id;
    protected $image_data = array();
    protected $html;

    protected $index;

    public function __construct($index)
    {
        $this->index = $index;
        $this->id = $this->set_image_id($this->index);
        $this->image_data = $this->get_image_data($this->id);

        $this->html = $this->build_view($this->image_data);
    }

    /**
     * Concrete instance will build HTML to display the image.
     *
     * @param   array   $image_data     Data from the GM Community MySQL table for the record being viewed.
     * @return  string                  HTML to display the image.
     */
    abstract protected function build_view(array $image_data);

    /**
     * Grab the alphanumeric $_GET['edit'] value. This is used to look up the image being edited.
     *
     * @return bool|string  False if no data is found. Else returns value of $_GET['edit']
     */
    protected function set_image_id($index)
    {
        $out = false;
        if (isset($_GET[$index]))
        {
            $out = strip_tags($_GET[$index]);
        } elseif (isset($_POST[$index]))
        {
            $out = strip_tags($_POST[$index]);
        }

        return $out;
    }

    /**
     * Get an array of data for the image record on the gm_community_gallery MySQL table where id = $id
     *
     * @param   $id     string  Alphanumeric ID set at $_GET['edit']
     * @return  bool|array      If no data is found through the prepared statement, return false. Else return array with image data.
     */
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

    public function return_html()
    {
        return $this->html;
    }
}