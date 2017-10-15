<?php

/**
 * @package     GM-Community-Gallery
 * @author      Gabriel Mioni <gabriel@gabrielmioni.com>
 */

namespace GM_community_gallery\_public;

use GM_community_gallery\nav\navigate;

require_once(GM_GALLERY_DIR . '/nav/abstract.navigate.php');

/**
 * Builds a MySQL query that can be used to produce HTML for display of public gallery/pagination.
 */
class public_navigate extends navigate
{
    public function __construct($show_trash = false)
    {
        parent::__construct($show_trash);

        $this->input_array['name']     = $this->check_post_or_get_for_value('name');
        $this->input_array['title']    = $this->check_post_or_get_for_value('title');
        $this->input_array['paginate'] = $this->check_post_or_get_for_value('paginate');
        $this->input_array['limit']    = $this->check_post_or_get_for_value('limit');
        $this->input_array['date']     = $this->check_input_array_elements('date', 2);
        $this->input_array['tags']     = $this->check_input_array_elements('tags');
    }

    public function build_query_and_args($is_count = false)
    {
        $table_name = GM_GALLERY_TABLENAME;
        $input_array = $this->input_array;

        $name  = $input_array['name'];
        $title = $input_array['title'];
        $page  = $input_array['paginate'];
        $limit = $input_array['limit'];
        $date  = $input_array['date'];
        $tags  = $input_array['tags'];

        $query = '';
        $args  = array();

        if ($is_count)
        {
            $query .= "SELECT COUNT(*) FROM $table_name ";
        } else {
            $query .= "SELECT * FROM $table_name ";
        }

        $where_query = '';

        $this->append_query_trash($where_query);

        $this->append_query_input('name', $name, $where_query, $args);
        $this->append_query_input('title', $title, $where_query, $args);

        $this->append_query_tags($tags, $where_query, $args);

        $this->append_query_date($date, $where_query, $args);

        $this->append_query_where($query, $where_query);


        $query .= ' ORDER BY created';

        $this->append_query_limit($is_count, $page, $limit, $query, $args);

        $tmp = array();
        $tmp['query'] = $query;
        $tmp['args']  = $args;

        return $tmp;
    }

    protected function append_query_trash(&$where_query)
    {
        $where_query .= ' trash = 0';
    }
}