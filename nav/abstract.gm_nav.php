<?php

namespace GM_community_gallery\nav;

abstract class gm_nav_abstract
{
    protected $input_array = array();

    public function __construct()
    {
        $this->input_array['paginate'] = $this->check_post_or_get_for_value('paginate');
        $this->input_array['limit']    = $this->check_post_or_get_for_value('limit');
        $this->input_array['date']     = $this->set_post_or_query_string_values('date', 2);
        $this->input_array['tags']     = $this->set_post_or_query_string_values('tags');
    }

    protected function check_post_or_get_for_value($get_index)
    {
        $value = isset($_GET[$get_index]) ? $_GET[$get_index] : null;

        if ($value === null)
        {
            $value = isset($_POST[$get_index]) ? $_POST[$get_index] : null;
        }

        if ($value === null)
        {
            return false;
        }

        return strip_tags($value);
    }

    /**
     * @param $index
     * @param bool $max_count
     * @return array
     */
    protected function set_post_or_query_string_values($index, $max_count = false)
    {
        $tmp = array();

        $max = 9999;
        if ($max_count !== false)
        {
            $max = intval($max_count);
        }

        $is_get  = isset($_GET[$index]) ? true : false;
        $is_post = isset($_GET[$index]) ? true : false;

        $value = false;

        if ($is_get)
        {
            $value = $_GET[$index];
        } elseif ($is_post)
        {
            $value = $_POST[$index];
        }

        if ($value === false)
        {
            return $tmp;
        }

        if (!is_array($value))
        {
            $tmp[] = strip_tags($value);
            return $tmp;
        }

        foreach ($value as $sub_value)
        {
            if ( count($tmp) <= $max )
            {
                $tmp[] = strip_tags($sub_value);
            }
        }

        return $tmp;
    }

    /**
     * Builds a prepared statement and array with query values used to query the gm_community_gallery MySQL table,
     *
     * @param   array $input_array    Contains $_POST or $_GET values.
     * @param   bool  $is_count       Default false will return all columns. True will return the count of rows.
     * @return  array                 To array elements containing the prepared stat
     */
    protected function build_query_and_args(array $input_array, $is_count = false)
    {
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

    /**
     * Returns two integers in an array representing the places where the MySQL query returned rows should start
     * and the maximum returned rows.
     *
     * @param   $page   int     The page being requested.
     * @param   $limit  int     The number of results requested per page.
     * @return  array           Always two integers representing the row start and row stop for the query.
     */
    protected function calculate_limit_start_stop($page, $limit)
    {
        $tmp = array();

        // Prepare $page and $limit defaults. Must be whole numbers.
        $page  = $page  === false ? 0 : ceil($page);
        $limit = $limit === false ? 10 : ceil($limit);

        // If $limit is odd for some reason, round it up.
        $limit_stop  = ($limit % 2 == 0) === true ? $limit : $limit + 1;

        // The minimum value for $limit_start is 0.
        $limit_start = $page -1 <= 0 ? 0 : ($page - 1) * $limit_stop;

        $tmp[] = $limit_start;
        $tmp[] = $limit_stop;

        return $tmp;
    }

    /**
     * Sorts the $date array by oldest to newest.
     *
     * @param array $date
     */
    protected function sort_date(array &$date)
    {
        usort($date, array($this, 'date_sorter'));
    }

    /**
     * Callback for $this->sort_date()
     *
     * @param $a string Date
     * @param $b string Date
     * @return false|int
     */
    protected function date_sorter($a, $b)
    {
        return strtotime($a) - strtotime($b);
    }
    
    
}
