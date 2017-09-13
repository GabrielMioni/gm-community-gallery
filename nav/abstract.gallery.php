<?php

/**
 * @package     GM Community Gallery
 * @author      Gabriel Mioni <gabriel@gabrielmioni.com>
 */

namespace GM_community_gallery\nav;

/**
 * Abstract class used to collect gallery data from the gm_contact_gallery and build HTML.
 *
 * @package GM_community_gallery\nav
 */
abstract class gallery
{
    protected $url_gm_gallery;

    protected $gallery_data;
    protected $gallery_html;

    public function __construct(navigate $navigate)
    {
        $this->url_gm_gallery = $this->get_gallery_url();
        $this->gallery_data   = $this->get_gallery_data($navigate);

        $this->gallery_html = $this->build_gallery_html($this->gallery_data);
    }

    /**
     * Builds HTML for the gallery being constructed.
     *
     * @param   array   $gallery_data   Array holding data from the gm_community_gallery MySQL table
     * @return  string  HTML for the gallery being constructed
     */
    abstract protected function build_gallery_html(array $gallery_data);

    /**
     * Get an array of data from the gm_community_gallery MySQL table. The MySQL query and arguments are supplied by
     * the navigate class object $navigate. Both the query and arguments are passed to $wpdb for a prepared statement.
     *
     * @param   navigate    $navigate   Class object used to set MySQL query/arguments based on $_GET or $_POST values.
     * @return              array       Result rows from the prepared statement.
     */
    protected function get_gallery_data(navigate $navigate)
    {
        // $is_count is set to false. This will row data instead of a row count.
        $query_data = $navigate->build_query_and_args(false);

        $query = $query_data['query'];
        $args  = $query_data['args'];

        global $wpdb;

        $prepare = $wpdb->prepare($query, $args);
        $results = $wpdb->get_results($prepare, ARRAY_A);

        return $results;
    }

    /**
     * Returns the URL for the GM community gallery uploads directory.
     *
     * @param   bool    $sub_dir    Optional. If set to 'images' or 'thumbs', the function will return the URL for the
     *                              GM community gallery. By default, return the URL without a sub-directory
     * @return  string              The URL for the GM community gallery upload.
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
     * Returns the URL for the GM Community Gallery admin page
     *
     * @return string   The URL for the GM Community Gallery console
     */
    protected function get_settings_page_url()
    {
        return admin_url('admin.php?page=gm-community-gallery');
    }

    /**
     * @return string   HTML for the gallery that's been built.
     */
    public function return_gallery_html()
    {
        return $this->gallery_html;
    }
}