<?php

/**
 * @package     GM-Community-Gallery
 * @author      Gabriel Mioni <gabriel@gabrielmioni.com>
 */

namespace GM_community_gallery\admin;

/**
 * Processes mass action requests from the admin gallery
 *
 * @package GM_community_gallery\admin
 */
class admin_mass_action
{
    protected $action;
    /** @var array Holds id values for images selected from the admin gallery. */
    protected $image_ids = array();
    /** @var string Set in gm-community-gallery.php */
    protected $tablename = GM_GALLERY_TABLENAME;
    /** @var bool|string */
    protected $query;
    /** @var false|int */
    protected $rows;

    protected $perma_delete = false;

    public function __construct()
    {
        $this->validate_nonce();

        $this->action    = isset($_POST['gm_mass_action_type']) ? intval($_POST['gm_mass_action_type']) : null;
        $this->image_ids = $this->get_image_ids();
        $this->query     = $this->set_query($this->image_ids, $this->action);
//        $this->rows      = $this->process_query($this->query, $this->image_ids);
        $this->rows     = $this->process_query($this->query, $this->image_ids, $this->action);

        $this->unset_deleted_files($this->image_ids);

        $this->build_response($this->rows, $this->action, $this->image_ids);
        $this->do_redirect();
    }

    /**
     * Builds a query based on the value of $_POST['gm_mass_action_type'].
     *
     * @param   array           $image_ids  Array of ids selected from the admin gallery.
     * @return  bool|string     Return false if $_POST['gm_mass_action_type'] is not set. Else, appropriate query.
     */
    protected function set_query(array $image_ids, $action)
    {
        $query = false;

        switch ($action)
        {
            case 0:
                $query = $this->build_query_trash($image_ids, false);
                break;
            case 1:
                $query = $this->build_query_trash($image_ids, true);
                break;
            case 2:
                $query = $this->build_query_delete($image_ids);
                break;
            default:
                break;
        }

        return $query;
    }

    /**
     * Validate nonce.
     *
     * @return  bool    If nonce is ok, return true. Else, dieeeeee.
     */
    protected function validate_nonce()
    {
        $nonce = $_POST['gm_mass_action_nonce'];
        $nonce_ok = wp_verify_nonce($nonce, 'gm_mass_action_nonce');

        if ($nonce_ok !== false)
        {
            return true;
        }

        die('You have now power here.');
    }

    /**
     * Builds a query to update the gm_community_gallery table and set the selected images to trash = 1.
     *
     * @param   array       $image_ids      ids for the selected images from the admin gallery.
     * @return  bool|string                 If $image_ids is empty, return false. Else, return query.
     */
    protected function build_query_trash(array $image_ids, $trash = false)
    {
        $id_count = count($image_ids);

        if ( $id_count < 1 )
        {
            return false;
        }

        $tn = $this->tablename;

        $trash_value = $trash === false ? 1 : 0;

        $query = "UPDATE $tn SET trash = $trash_value WHERE ";

        $index = 0;

        while ($index < $id_count)
        {
            $query .= 'id = %s OR ';
            ++$index;
        }

        $query = rtrim($query, 'OR ');

        $query .= " LIMIT $id_count";

        return $query;
    }

    protected function build_query_delete(array $image_ids)
    {
        $id_count = count($image_ids);

        if ( $id_count < 1 )
        {
            return false;
        }

        $tn = $this->tablename;

        $query = "DELETE from $tn WHERE id IN (";

        $index = 0;

        while ($index < $id_count)
        {
            $query .= '%s,';
            ++$index;
        }

        $query = rtrim($query, ',');

        $query .= ") LIMIT $id_count;";

        return $query;
    }

    /**
     * Build array with ids from the images selected in the admin gallery.
     *
     * @return array
     */
    protected function get_image_ids()
    {
        $tmp = array();

        if ( isset( $_POST['gm_mass_update']) )
        {
            foreach ( $_POST['gm_mass_update'] as $value )
            {
                $tmp[] = $value;
            }
        }

        return $tmp;
    }

    /**
     * Run the query built in $this->set_query().
     *
     * @param   $query  string      Query being prepared/executed.
     * @param   array   $image_ids  Passed to the prepared statement.
     * @param   int     $action
     * @return  int     If $query is false or no rows were affected, return 0. Else return number of rows affected.
     */
    protected function process_query($query, array $image_ids, $action)
    {
        if ( $query === false )
        {
            return 0;
        }

        global $wpdb;

        $prepare = $wpdb->prepare($query, $image_ids);
        $rows    = $wpdb->query($prepare);

//        $rows = 5;
        if ($action === 2 && $rows > 0)
        {
            $this->perma_delete = true;
        }

        return $rows;
    }

    protected function unset_deleted_files(array $image_ids)
    {
        if ($this->perma_delete === false)
        {
            return false;
        }

        $filepaths = array();
        $upload_dir = wp_get_upload_dir();
        $upload_path = $upload_dir['basedir'];

        foreach ($image_ids as $id)
        {
            $thumb = glob($upload_path . '/gm-community-gallery/thumbs/' . $id . '.*');
            $image = glob($upload_path . '/gm-community-gallery/images/' . $id . '.*');

            if ( isset($thumb[0]) )
            {
                $filepaths[] = $thumb[0];
            }

            if ( isset($image[0]) )
            {
                $filepaths[] = $image[0];
            }
        }

        foreach ($filepaths as $file)
        {
            $unlink = unlink($file);

            if (!$unlink)
            {
                error_log('Unable to delete ' . $file);
            }
        }
    }

    /**
     * Set $_SESSION['gm_response'], which is used to display results to the user at the admin gallery.
     *
     * @param   $rows   int         Number of rows affected during $this->process_query()
     * @param   $action int
     * @param   array   $image_ids
     */
    protected function build_response($rows, $action, array $image_ids)
    {
        $tmp = array();
        $msg = '';

        switch ($action)
        {
            case 0:
                $msg = 'moved to trash.';
                break;
            case 1:
                $msg = 'moved to gallery.';
                break;
            case 2:
                $msg = 'deleted.';
                break;
        }

        if ( $rows < 1 )
        {
            $tmp['error'][] = 'No rows were affected.';
        } else {

            if ($rows === 1)
            {
                $tmp['success'][] = "[1] item $msg";
            } else {
                $tmp['success'][] = "[$rows] items $msg";
            }
            $tmp['ids'] = $image_ids;
        }

        $_SESSION['gm_response'] = json_encode($tmp, true);
    }

    protected function do_redirect()
    {
        header( 'Location: ' . $_SERVER["HTTP_REFERER"] );
        exit();
    }
}
