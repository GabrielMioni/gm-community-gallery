<?php

/**
 * @package     GM-Community-Gallery
 * @author      Gabriel Mioni <gabriel@gabrielmioni.com>
 */

/**
 * Creates an alphanumeric id that's used to both name uploaded files placed in the wp-content/uploads/gm-community-gallery
 * directory and serve as the primary key in the gm_community_gallery MySQL table.
 *
 * The id characters will may be upper/lowercase alphabets and/or numbers (probably each). The ID will be equal in length
 * to the value of $id_length. Before returning the id, the class checks to make sure the id is unique.
 */
class gm_id_builder
{
    /** @var string */
    protected $alpha_numbers = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

    /** @var array Holds the array of uppercase and lowercase alphabets in an array */
    protected $alph_array = array();

    /** @var string The array that will be output */
    protected $generated_id;

    /**
     * @param $id_length    int     The requested length that $this->generated_id should be.
     */
    public function __construct($id_length)
    {
        $this->alph_array = str_split($this->alpha_numbers, 1);

        $this->generated_id = $this->set_id($id_length, $this->alph_array);
    }

    /**
     * Tries to create a unique random ID. If one can't be created in less than 100 attempts, returns false.
     *
     * @param   $id_length  int             The length the ID should be.
     * @param   array       $alph_array     The array of upper/lowercase alphabets set in the __constructor
     * @return  bool|string     On failure, returns false. On success returns new unique id.
     */
    protected function set_id($id_length, array $alph_array)
    {
        $table_name = GM_GALLERY_TABLENAME;

        $query = "SELECT * FROM $table_name WHERE id = %s;";

        global $wpdb;

        $attempts = 0;
        $id_out = '';

        while ($id_out === '' && $attempts < 100)
        {
            $new_random_id = $this->build_id($id_length, $alph_array);

            $prepared = $wpdb->prepare($query, [$new_random_id]);
            $results  = $wpdb->get_results($prepared, ARRAY_A);

            if (empty($results))
            {
                $id_out = $new_random_id;
            }
            ++$attempts;
        }

        if ($id_out !== '')
        {
            return $id_out;
        }

        return false;
    }

    /**
     * Build a random id with upper/lowercase alphabets.
     *
     * @param   $id_length    int     The requested length that $this->generated_id should be.
     * @param   array $alph_array     The array of upper/lowecase alphabets set in the __constructor
     * @return  string                The generated id.
     */
    protected function build_id($id_length, array $alph_array)
    {
        $id = '';
        $alph_count = count($alph_array);

        while (strlen($id) < $id_length)
        {
            $rand = rand(0, $alph_count);

            $index = $rand <= 0 ? 0 : $rand -1;

            $id .= $alph_array[$index];
        }

        return $id;
    }

    /**
     * Public access for the ID
     * @return string
     */
    public function return_id()
    {
        return $this->generated_id;
    }
}
