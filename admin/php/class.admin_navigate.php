<?php

/**
 * @package     GM-Community-Gallery
 * @author      Gabriel Mioni <gabriel@gabrielmioni.com>
 */

namespace GM_community_gallery\admin;

require_once(GM_GALLERY_DIR . 'nav/abstract.navigate.php');

use GM_community_gallery\nav\navigate as navigate;

/**
 * This class is fed to both admin_gallery and admin_navigate classes through their constructors.
 *
 * From the admin_gallery / admin_navigate classes, the admin_navigate->build_query_and_args() method can be called
 * to produce a MySQL query and associated arguments used for a prepared statement.
 *
 * @package GM_community_gallery\admin
 */
class admin_navigate extends navigate
{
    public function __construct($show_trash = false)
    {
        parent::__construct($show_trash);

        $this->input_array['paginate'] = $this->check_post_or_get_for_value('paginate');
        $this->input_array['name']     = $this->check_post_or_get_for_value('name');
        $this->input_array['title']    = $this->check_post_or_get_for_value('title');
        $this->input_array['email']    = $this->check_post_or_get_for_value('email');
        $this->input_array['ip']       = $this->check_post_or_get_for_value('ip');
        $this->input_array['limit']    = $this->check_post_or_get_for_value('limit');
        $this->input_array['date']     = $this->check_input_array_elements('date', 2);
        $this->input_array['tags']     = $this->check_input_array_elements('tags');
    }

    /**
     * Returns an array holding a MySQL query and a nested array with arguments. Both will be passed to a prepared
     * statement.
     *
     * @param   bool    $is_count   The default is false. False will build a MySQL query to collect rows. True will
     *                              build a MySQL query to return a row count.
     * @return  array               $array['query'] is a MySQL query and $array['args'] is a child array holding arguments
     */
    public function build_query_and_args($is_count = false)
    {
        $table_name = GM_GALLERY_TABLENAME;
        $input_array = $this->input_array;

        $title = $input_array['title'];
        $name  = $input_array['name'];
        $email = $input_array['email'];
        $ip    = $input_array['ip'];
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

        $this->append_query_input('name', $name, $where_query, $args);
        $this->append_query_input('title', $title, $where_query, $args);
        $this->append_query_input('email', $email, $where_query, $args);
        $this->append_query_input('ip', $ip, $where_query, $args);

        $this->append_query_tags($tags, $where_query, $args);

        $this->append_query_date($date, $where_query, $args);

        $this->append_query_trash($where_query);

        $this->append_query_where($query, $where_query);

        $query .= ' ORDER BY created';

        $this->append_query_limit($is_count, $page, $limit, $query, $args);

        $tmp = array();
        $tmp['query'] = $query;
        $tmp['args']  = $args;

        return $tmp;

    }
/*
    protected function append_query_trash(&$where_query)
    {
        if ( $this->trash === true)
        {
            $where_query .= ' trash = 1 ';
        } else {
            $where_query .= ' trash = 0 ';
        }
    }
*/
}