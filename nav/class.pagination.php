<?php

use GM_community_gallery\nav\navigate as navigate;

/**
 * Builds HTML used to display a pagination bar.
 *
 * This class accepts a navigate class object in it's constructor. The navigate class object should be the same as
 * the one shared with the concrete instance of gallery that's responsible for displaying images meant to be traversed
 * by the pagination bar.
 *
 * @see \GM_community_gallery\nav\gallery
 * @see |GM_community_gallery|nav|navigate
 */
class pagination
{
    /** @var int    The total number of images in the gm_community_gallery table  */
    protected $image_count;

    /** @var int    The max number of images that should be displayed per page. Held in $navigate::$input_array */
    protected $limit;

    /** @var int    The page currently being viewed. Held in $navigate::$input_array */
    protected $current_page;

    /** @var float  The total pages that exist given the value of $this->image_count and $this->current_page */
    protected $total_pages;

    /** @var string  The HTML for the pagination bar */
    protected $pagination_html;

    public function __construct(navigate $navigate)
    {
        $this->image_count   = $this->get_image_count($navigate);
        $this->limit         = $this->get_limit($navigate);
        $this->current_page  = $this->get_current_page($navigate);

        $this->total_pages   = $this->set_total_pages($this->image_count, $this->limit);

        $this->pagination_html = $this->build_pagination($this->total_pages, $this->current_page);
    }

    /**
     * Retrieves the $_GET or $_POST value at index ['paginate'].
     *
     * @param   navigate    $navigate
     * @return  int|bool    If the value at $input_array['paginate'] is false or less than 1, return 1. Else return value
     */
    protected function get_current_page(navigate $navigate)
    {
        $input_array = $navigate->return_input_array();
        $paginate = $input_array['paginate'];

        $paginate = $paginate === false || $paginate < 1 ? 1 : intval($paginate);

        return $paginate;
    }

    /**
     * @param   navigate    $navigate
     * @return  int|bool    If the value at $input_array['limit'] is false or less than 1, return 1. Else return value
     */
    protected function get_limit(navigate $navigate)
    {
        $input_array = $navigate->return_input_array();
        $limit = $input_array['limit'];

        $limit = $limit === false || $limit < 10 ? 10 : intval($limit);

        return intval($limit);
    }

    /**
     * Sets the total number of pages that are necessary to display all images.
     *
     * @param   $image_count    int     The total number of images
     * @param   $limit          int     The max number of images displayed per page
     * @return  int                     The number of pages needed to display all images
     */
    protected function set_total_pages($image_count, $limit)
    {
        $limit = $limit === false ? 10 : ceil($limit);

        return ceil( $image_count / $limit );
    }

    /**
     * Use the navigate class object $navigate to build a prepared statement and retrieve the total number
     * of images in the gm_gallery_table that the MySQL query returns.
     *
     * @param navigate $navigate
     * @return int
     */
    protected function get_image_count(navigate $navigate)
    {
        // $is_count set to true
        $query_data   = $navigate->build_query_and_args(true);

        $query = $query_data['query'];
        $args  = $query_data['args'];

        global $wpdb;

        $prepare = $wpdb->prepare($query, $args);
        $results = $wpdb->get_var($prepare);

        $results = intval($results);

        return $results;
    }

    /**
     * Build HTML for the pagination bar
     *
     * @param   $total_pages    int     The total number of possible pages.
     * @param   $current_page   int     The current page being viewed.
     * @param   $is_admin       bool
     * @return  string                  HTML for the pagination bar.
     */
    protected function build_pagination($total_pages, $current_page, $is_admin = true)
    {
        // Start at 1 if there's no $current_page data
        $current_page = $current_page === false || $current_page <= 1 ? 1 : $current_page;
        $current_page = $current_page >= $total_pages ? $total_pages : $current_page;

        $page_pointer = $current_page - 5 <= 1 ? 1 : $current_page - 5;
        $buttons_added = 0;

        $paginate_html = '<div id="gm_pagination">';

        if ($current_page > 1)
        {
            $start_url = $this->get_url(1);
            $back_one  = $this->get_url($current_page, -1);

            $paginate_html .= "<span class='gm_active page_button'> <a href='$start_url'><<</a> </span>";
            $paginate_html .= "<span class='gm_active page_button'> <a href='$back_one'><</a> </span>";
        } else {
            $paginate_html .= "<span class='gm_faded page_button'> << </span>";
            $paginate_html .= "<span class='gm_faded page_button'> < </span>";
        }

        while ($page_pointer < $current_page && $page_pointer > 0 && $page_pointer <= $total_pages)
        {
            $paginate_html .= $this->set_pagination_span($page_pointer, $current_page, $is_admin);
            ++$page_pointer;
            ++$buttons_added;
        }

        // Back to ... starting position
        $page_pointer = $current_page;

        while ($buttons_added < 11 && $page_pointer <= $total_pages)
        {
            $paginate_html .= $this->set_pagination_span($page_pointer, $current_page, $is_admin);
            ++$page_pointer;
            ++$buttons_added;
        }

        if ($current_page < $total_pages)
        {
            $end_url = $this->get_url($total_pages);
            $forward_one  = $this->get_url($current_page, +1);

            $paginate_html .= "<span class='gm_active page_button'> <a href='$forward_one'>></a> </span>";
            $paginate_html .= "<span class='gm_active page_button'> <a href='$end_url'>>></a> </span>";
        } else {
            $paginate_html .= "<span class='gm_faded page_button'> > </span>";
            $paginate_html .= "<span class='gm_faded page_button'> >> </span>";
        }

        $paginate_html .= "<div id='page_location'> $current_page / $total_pages </div>";
        $paginate_html .= '</div>';

        return $paginate_html;
    }

    /**
     * Returns 'buttons' for the pagination bar.
     *
     * @param   $page_pointer   int     The page the button's link will go to.
     * @param   $current_page   int     The current page being viewed.
     * @return  string                  HTML for the pagination button.
     */
    protected function set_pagination_span($page_pointer, $current_page, $is_admin = true)
    {
        if ($page_pointer === $current_page)
        {
            return "<span class='gm_current page_button'> $page_pointer </span>";
        }
        $url = $this->get_url($page_pointer, 0, $is_admin);

        return "<span class='gm_active page_button'><a href='$url'> $page_pointer </a> </span>";
    }

    /**
     * Sets the URL for pagination buttons.
     *
     * @param   $page_pointer           int     The page the produced URL should be for
     * @param   int $modify_pointer             Can be set to modify the $page_pointer + or -
     * @return  string  URL for pagination button links.
     */
    protected function get_url($page_pointer, $modify_pointer = 0, $is_admin = true)
    {
        $page_pointer = $page_pointer + $modify_pointer;

//        $admin_url = admin_url('admin.php?page=gm-community-submit');

        $url = strtok($_SERVER["REQUEST_URI"],'?');

        $query_string = $this->build_query_string($page_pointer, $is_admin);

        return $url . '?' . $query_string;
    }

    /**
     * Returns a query string with the paginate= value set to $page_pointer
     *
     * @param   $page_pointer   int     The value the returned query string should have for paginate.
     * @param   bool $is_admin
     * @return  string                  The new query string
     */
    protected function build_query_string($page_pointer, $is_admin = true)
    {
        $query_string = $_SERVER['QUERY_STRING'];

        $explode = explode('&', $query_string);

        foreach ($explode as $key=>$value)
        {
            if ( strpos($value, 'gm-community-submit') !== false )
            {
                unset($explode[$key]);
            }

            if ( strpos($value, 'paginate=') !== false )
            {
                $new_paginate = preg_replace('/[0-9]+/', $page_pointer, $value);
                $explode[$key] = $new_paginate;
            }
        }

        $new_query_string = implode('&', $explode);

        if (strpos($new_query_string, 'paginate=') === false)
        {
            $new_query_string .= '&paginate=' . $page_pointer;
        }

        if ($is_admin === true)
        {
            $new_query_string = 'page=gm-community-gallery&' . $new_query_string;
        }

        $new_query_string = rtrim($new_query_string, '&');

        return urldecode($new_query_string);
    }

    public function return_pagination_html()
    {
        return $this->pagination_html;
    }
}