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

    protected $show_trash;

    // Concrete constructors will populate $ths->input_array()
    public function __construct($show_trash = false)
    {
        $this->show_trash = $show_trash;
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
     * Appends new portions of a MySQL query to the prepared statement and pushes values to the array that will also
     * be used in the prepared statement.
     *
     * @param $column       string  Column being set with $value
     * @param $value        string  Value being set to $column
     * @param $append_to    string  Prepared statement being appended to. Passed by reference.
     * @param array $args           Array holding arguments for the prepared statement. Passed by reference.
     * @param string $type          Data type being set in the prepared statement.
     */
    protected function append_query_input($column, $value, &$append_to, array &$args, $type = '%s')
    {
        if ($value !== false)
        {
            $append_to .= "$column LIKE $type AND ";
            $args[] = $value;
        }
    }

    protected function append_query_tags(array $tags, &$query, &$args)
    {
        if (empty($tags))
        {
            return false;
        }

        $tags_query = '';

        foreach ($tags as $value)
        {
            $tags_query .= "tags LIKE %s OR " ;
            $args[] = $value;
        }

        $tags_query = rtrim($tags_query, 'OR ');

        $query .= $tags_query;
    }

    /**
     * Appends a MySQL snippet to the prepared statement looking for records created either on a certain day or
     * between two [2] dates. Dates are pushed to the $args array by reference.
     *
     * @param   array $date             The array holding date data.
     * @param   $append_to      string  Prepared statement being appended to. Passed by reference.
     * @param   array $args             Array holding arguments for the prepared statement. Passed by reference.
     * @return  bool                    Return false if there's no date info to append / push.
     */
    protected function append_query_date(array $date, &$append_to, array &$args)
    {
        if (empty($date))
        {
            return false;
        }

        $append_to .= ' created > %s AND created < %s';

        $date_count = count($date);

        if ($date_count === 1)
        {
            $end = $this->date_formatter($date[0], 86400);

            $args[] = $this->date_formatter($date[0]);
            $args[] = $end;
        }

        if ($date_count === 2)
        {
            $this->sort_date($date);

            $args[] = $this->date_formatter($date[0]);
            $args[] = $this->date_formatter($date[1]);
        }
    }

    /**
     * Formats date for the prepared statement.
     *
     * @param   $date           string      Human readable date.
     * @param   int $modify                 Can be set to alter the date value by seconds.
     * @return  false|string                The formatted date if $date is valid. Else, return false.
     */
    protected function date_formatter($date, $modify = 0)
    {
        return date( 'Y-m-d H:i:s', strtotime($date) + $modify);
    }

    /**
     * If $where_query isn't empty, append it to the $query value. Trim trailing 'AND ' / 'OR ' from the end
     * of $where_query.
     *
     * @param   $query          string      The prepared statement.
     * @param   $where_query    string      The portion of the prepared statement holding the WHERE query.
     */
    protected function append_query_where(&$query, $where_query)
    {
        if (trim($where_query) !== '')
        {
            $where_query = rtrim($where_query, 'AND ');
            $where_query = rtrim($where_query, 'OR ');

            $query .= ' WHERE ' . $where_query;
        }
    }

    /**
     *
     *
     * @param   $is_count   bool    Flag for whether the prepared statement is collecting count or rows as the result.
     * @param   $page       int     Page being requested.
     * @param   $limit      int     Number of results requested per page.
     * @param   $query      string  Prepared statement the LIMIT portion of the query is being appended to.
     * @param   array $args         Array hold arguments for the prepared statement.
     * @return  bool                Returns false if $is_count is true
     */
    protected function append_query_limit($is_count, $page, $limit, &$query, array &$args)
    {
        if ($is_count)
        {
            return false;
        }

        $options  = get_option('gm_community_gallery_options');

        if ( isset( $options['imgs_per_page'] ) ) {
            $default = intval($options['imgs_per_page']);
        } else {
            $default = 10;
        }

        // Prepare $page and $limit defaults. Must be whole numbers.
        $page  = $page  === false ? 0 : ceil($page);
        $limit = $limit === false ? $default : ceil($limit);

        // The minimum value for $limit_start is 0.
        $limit_start = $page -1 <= 0 ? 0 : ($page - 1) * $limit;

        $query .= ' LIMIT %d, %d';
        $args[] = $limit_start;
        $args[] = $limit;
    }

    protected function append_query_trash(&$where_query)
    {
        $show_trash = $this->show_trash;

        if ( $show_trash === true)
        {
            $where_query .= ' trash = 1 ';
        } else {
            $where_query .= ' trash = 0 ';
        }
    }

    /**
     * Returns two integers in an array representing the places where the MySQL query returned rows should start
     * and the maximum returned rows.
     *
     * @param   $page   int     The page being requested.
     * @param   $limit  int     The number of results requested per page.
     * @return  array           Always two integers representing the row start and row stop for the query.
     * @deprecated  Replaced by $this->append_query_limit()
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