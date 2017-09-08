<?php

/**
 * @package     GM-Community-Gallery
 * @author      Gabriel Mioni <gabriel@gabrielmioni.com>
 */

namespace GM_community_gallery\nav;

/**
 * Concrete instances of this class behave as shared constructors for other classes that need to have the same
 * data. The concrete navigate class will collect $_POST or $_GET variables, which are then used to build MySQL queries and
 * organize arguments that can be used for a prepared statement.
 *
 * Example: PHP that is used to build a gallery display and PHP used for a pagination bar need to know similar data. Class
 * constructors for each will accept a common concrete navigate class object and use the MySQL statements and arguments
 * organized by the navigate class object to build HTML.
 *
 * @package GM_community_gallery\nav
 */
abstract class navigate
{
    /** @var array Holds $_POST or $_GET values */
    protected $input_array = array();

    // Concrete constructors will populate $ths->input_array()
    public function __construct()
    {
        // Nothing to see here.
    }

    /**
     * Builds a prepared statement and array with query values used to query the gm_community_gallery MySQL table,
     *
     * @param   bool  $is_count       Default false will return all columns. True will return the count of rows.
     * @return  array                 $array['query'] is a MySQL query and $array['args'] is a child array holding arguments
     */
    abstract public function build_query_and_args($is_count = false);

    /**
     * Provides access to array elements held in $this->input_array.
     *
     * @return array
     */
    public function return_input_array() {
        return $this->input_array;
    }

    /**
     * Checks $_GET or $_POST values at indexes matching $get_index. If no value is found, return false.
     *
     * @param   $index  string          The index being checked.
     * @return          bool|string     If a value is present, return it. Else return false.
     */
    protected function check_post_or_get_for_value($index)
    {
        $value = isset($_GET[$index]) ? $_GET[$index] : null;

        if ($value === null)
        {
            $value = isset($_POST[$index]) ? $_POST[$index] : null;
        }

        if ($value === null)
        {
            return false;
        }

        return strip_tags($value);
    }

    /**
     * Checks for $_GET or $_POST elements that contain arrays.
     *
     * @param   $index  string      The index for the element being checked.
     * @param   bool $max_count     Sets a hard limit for the number of array elements that can be returned.
     * @return array    If no data is found, array will be empty. Else, associative array
     */
    protected function check_input_array_elements($index, $max_count = false)
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