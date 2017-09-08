<?php

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
    public function __construct()
    {
        $this->input_array['paginate'] = $this->check_post_or_get_for_value('paginate');
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
        $input_array = $this->input_array;

        $page  = $input_array['paginate'];
        $limit = $input_array['limit'];
        $date  = $input_array['date'];
        $tag   = $input_array['tags'];

        $table_name = GM_GALLERY_TABLENAME;

        /** @var $query string  Hold the prepared statement query */
        $query = '';

        /** @var $query_data array  Holds data that will be passed to the WordPress MySQL prepare statement */
        $query_data = array();

        switch ($is_count)
        {
            case true:
                $query .= "SELECT COUNT(*) FROM $table_name ";
                break;
            default:
                $query .= "SELECT * FROM $table_name ";
                break;
        }

        // Set the 'WHERE' portion, but wait to see if it's needed before adding it to the query.
        $where = 'WHERE ';

        // Set create_date queries if necessary.
        if (is_array($date))
        {
            $date_count = count($date);

            switch ($date_count)
            {
                case 1:
                    $where .= ' create_date = %s';
                    break;
                case 2:
                    $where .= ' create_date > %s AND create_date < %s';
                    break;
                default:
                    break;
            }

            $this->sort_date($date);

            // Format the dates requested for MySQL
            foreach ($date as $value)
            {
                $query_data[] = date('Y-m-d H:i:s', strtotime($value));
            }
        }

        if ($where !== 'WHERE ' && !empty($tag))
        {
            $where .= ' AND ';
        }

        if (!empty($tag))
        {
            $tags_query = '';

            foreach ($tag as $value)
            {
                $tags_query .= "tags LIKE %s OR ";
                $query_data[] = $value;
            }

            $straggle_or  = ' OR ';
            $pos_or  = strrpos($tags_query, $straggle_or);

            if ($pos_or !== false)
            {
                $tags_query = substr_replace($tags_query, '', $pos_or, strlen($straggle_or));
            }

            $where .= $tags_query;
        }

        // If any content was added to $where, appended it to $query
        if ($where !== 'WHERE ')
        {
            $query .= $where;
        }

        $query .= ' ORDER BY created';

        // Add a LIMIT to the query if the query is looking for column values and not a row count.
        if ($is_count === false)
        {
            $limit_values = $this->calculate_limit_start_stop($page, $limit);

            $query .= ' LIMIT %d, %d';
            $query_data[] = $limit_values[0];
            $query_data[] = $limit_values[1];
        }

        $tmp = array();
        $tmp['query'] = $query;
        $tmp['args'] = $query_data;

        return $tmp;
    }
}