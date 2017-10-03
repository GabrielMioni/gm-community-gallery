<?php

namespace GM_community_gallery\submit;

/**
 * Stage an uploaded image and then resize and store images in the 'images' and 'thumbs' directories at
 * wp-content/uploads/gm-community-gallery/
 *
 * Allowed MIME types are .jpg/.jpeg, .png and .gif. By default, .jpeg/.png images are converted to .jpg
 *
 * During the upload, a unique 6 digit alphabetic ID is created. If the upload is successful, uploader::upload_flag is
 * set to the new ID's value. If the upload fails, uploader::upload_flag will be false.
 */
class uploader
{
    /** @var bool|string  */
    protected $new_id;

    protected $file     = array();
    protected $handle   = array();
    protected $stage;

    protected $max_thumb_w = 300;
    protected $max_thumb_h = 400;

    protected $max_image_w = 900;
    protected $max_image_h = 800;

    public function __construct()
    {
        $this->file     = $this->check_file_upload('image');
        $this->new_id   = $this->get_new_id();

        $this->handle   = $this->stage_image($this->file);

        $this->save_thumb($this->handle, $this->new_id);
        $this->save_large($this->handle, $this->new_id);
    }

    protected function check_file_upload($image_index)
    {
        return isset( $_FILES[$image_index] ) ? $_FILES[$image_index] : false;
    }

    /**
     * Create a new 6 character length ID.
     *
     * @return  bool|string Returns false if an ID couldn't be generated (not impossible but unlikely). Else returns the new ID.
     */
    protected function get_new_id()
    {
        require_once('class.id_builder.php');

        $build_id = new id_builder(6);
        return $build_id->return_id();
    }

    /**
     * Temporarily modifies the upload location WordPress uses to stage an uploaded image at wp-content/uploads/gm-community-submit
     *
     * Once the image is staged, convert to .jpg if necessary.
     *
     * @param   $file   array   Specific element of $_FILE
     * @return          array   Returns $handle from wp_handle_uploads. Associative array: 'file' / 'url' / 'type'
     */
    protected function stage_image(array $file)
    {
        if ( ! function_exists('wp_handle_upload') )
        {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }

        $allowed_mimes = array('jpg' =>'image/jpg','jpeg' =>'image/jpeg', 'gif' => 'image/gif', 'png' => 'image/png');
        $upload_overrides = array( 'test_form' => false, 'mimes' => $allowed_mimes );

        // Temporarily set upload directory to wp-content/uploads/gm-community-submit/
        add_action('upload_dir', array( &$this, 'set_to_gm_community_gallery_directory'));
        $handle = wp_handle_upload($file, $upload_overrides);

        // Return default upload directory
        remove_action('upload_dir', array( &$this, 'set_to_gm_community_gallery_directory'));

        return $handle;
    }

    /**
     * Saves a thumbnail sized version of the image.
     *
     * @param array $handle     Created by wp_handle_upload() in uploader::stage_image()
     * @param $image_id         string  The id for the image.
     * @return bool             True if image was saved. Else false.
     */
    function save_thumb(array $handle, $image_id)
    {
        $type = $handle['type'];

        $is_png  = $type === 'image/png'  ? true : false;
        $is_jpeg = $type === 'image/jpeg' ? true : false;
        $is_gif  = $type === 'image/gif'  ? true : false;

        $original_location = $handle['file'];

        if ( $is_png || $is_jpeg || $is_gif )
        {
            $original_location = $this->convert_to_jpg($handle);
        }

        if ( $original_location !== false )
        {
            $save_location = $this->create_save_location('thumbs', $image_id, 'jpg');
            return $this->resize_and_save($save_location, $original_location, $this->max_thumb_w, $this->max_thumb_h);
        }

        return false;
    }

    /**
     * Saves a large image sized version of the image.
     *
     * @param array $handle     Created by wp_handle_upload() in uploader::stage_image()
     * @param $image_id         string  The id for the image.
     * @return bool             True if image was saved. Else false.
     */
    function save_large(array $handle, $image_id)
    {
        $type = $handle['type'];

        $is_png  = $type === 'image/png'  ? true : false;
        $is_jpeg = $type === 'image/jpeg' ? true : false;
        $is_gif  = $type === 'image/gif'  ? true : false;

        $original_location = $handle['file'];

        if ( $is_gif )
        {
            $save_location = $this->create_save_location('images', $image_id, 'gif');

            return copy($original_location, $save_location);
        }

        if ( $is_png || $is_jpeg )
        {
            $original_location = $this->convert_to_jpg($handle);
        }

        if ( $original_location !== false )
        {
            $save_location = $this->create_save_location('images', $image_id, 'jpg');
            return $this->resize_and_save($save_location, $original_location, $this->max_image_w, $this->max_image_h);
        }
    }

    /**
     * Returns the new directory/file name for the image.
     *
     * @param   $save_dir   string  Directory in wp-content/uploads/ where the image should be saved.
     * @param   $image_id   string  Id for the image.
     * @param   $ext        string  File extension
     * @return  string  The full file path where the image should be saved.
     */
    function create_save_location($save_dir, $image_id, $ext)
    {
        $wp_uploads_dir = wp_get_upload_dir();
        $wp_uploads_dir_base = $wp_uploads_dir['basedir'];

        return  $wp_uploads_dir_base . "/gm-community-gallery/$save_dir/" . $image_id . '.' . $ext;
    }

    /**
     * Converts the uploaded image to .jpg if the original was either .png or .jpeg.
     *
     * @param   $handle     array   Associative array returned by wp_handle_upload().
     * @return  bool                True if image was converted. Else false.
     */
    protected function convert_to_jpg(&$handle)
    {
        $file = $handle['file'];
        $type = $handle['type'];

        $img = null;

        switch ($type)
        {
            case 'image/png':
                $img  = imagecreatefrompng($file);
                break;
            case 'image/jpeg':
                $img = imagecreatefromjpeg($file);
                break;
            case 'image/gif':
                $img = imagecreatefromgif($file);
                break;
            default:
                break;
        }

        if ( $img !== null )
        {
            $new_file = $this->replace_extension($file);
            $quality  = 100;
            imagejpeg($img, $new_file, $quality);
            imagedestroy($img);
            return $new_file;
        }

        return false;
    }

    /**
     * Returns the directory for the $filename, replacing the file's extension with 'jpg'
     *
     * @param   $filename     string  Directory for file that needs a new extension.
     * @return  string        The full directory for the file, including the new extension.
     */
    function replace_extension($filename) {
        $info = pathinfo($filename);
        $directory = $info['dirname'];

        $jpg_file  = $info['filename'] . '.' . 'jpg';

        return $directory . '/' . $jpg_file;
    }

    /**
     * Used to temporarily change the directory WordPress uses when uploading. Called on the 'upload_dir' hook.
     *
     * @param   $dir
     * @return  array
     */
    function set_to_gm_community_gallery_directory($dir)
    {
        return array(
                'path'   => $dir['basedir'] . '/gm-community-gallery',
                'url'    => $dir['baseurl'] . '/gm-community-gallery',
                'subdir' => '/gm-community-gallery',
            ) + $dir;
    }

    /**
     * Resizes the image at $staged_location and saves a resized copy at $save_loaction.
     *
     * @param   $save_location      string New file directory where the image is saved
     * @param   $staged_location    string Staged file directory
     * @param   $max_w              int    Max width
     * @param   $max_h              int    Max height
     * @return  bool                       true if image the wp_get_image_editor object was created.
     */
    protected function resize_and_save($save_location, $staged_location, $max_w, $max_h)
    {
        $image_edit = wp_get_image_editor($staged_location);

        if (is_wp_error($image_edit))
        {
            return false;
        }

        $image_edit->resize($max_w, $max_h, false);
        $image_edit->save($save_location);

        return true;
    }

    /**
     * Get rid of the staged image.
     *
     * @param array $handle Associated array created by wp_handle_upload(), called in uploader::stage_image();
     */
    function clean_handle(array $handle)
    {
        if ( isset($handle['file']) )
        {
            unlink( $handle['file'] );
        }
    }

    /**
     * @return bool|string  False if the upload was not succesful. Else, return the ID created by $this->get_new_id();
     */
    public function return_upload_flag()
    {
        return $this->new_id;
    }

}