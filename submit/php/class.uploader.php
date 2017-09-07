<?php

namespace GM_community_gallery\submit;

/**
 * Stage an uploaded image and then resize and store images in the 'images' and 'thumbs' directories at
 * wp-content/uploads/gm-community-submit/ .
 *
 * Allowed MIME types are .jpg/.jpeg, .png and .gif. By default, .jpeg/.png images are converted to .jpg
 *
 * During the upload, a unique 6 digit alphabetic ID is created. If the upload is successful, $this->upload_flag is
 * set to the new ID's value. If the upload fails, $this->upload_flag will be false.
 */
class uploader
{
    /** @var bool|string  */
    protected $upload_flag;

    protected $max_thumb_w = 300;
    protected $max_thumb_h = 400;

    protected $max_image_w = 900;
    protected $max_image_h = 800;

    public function __construct()
    {
        $this->upload_flag = $this->upload_image('image');
    }

    /**
     * Temporarily stages the uploaded image and saves re-sized copies in gm-community-submit subdirectories (thumbs and images)
     *
     * @param   $image_index  string    The index used to specify the $_FILE element for the image being uploaded.
     * @return  bool|string
     */
    protected function upload_image($image_index)
    {
        // If the image doesn't exist, fail
        if ( ! isset($_FILES[$image_index]))
        {
            return false;
        }

        // If an ID couldn't be created, fail
        $image_id = $this->get_new_id();
        if ($image_id === false)
        {
            return false;
        }

        $file = $_FILES[$image_index];

        // Stage the image
        $staged_image = $this->stage_image($file);

        if (!is_wp_error($staged_image))
        {
            $upload_location = $staged_image['file'];

            // Save the thumbnail
            $this->resize_and_save('thumbs', $image_id, $upload_location, $this->max_thumb_w, $this->max_thumb_h);

            // Save the image
            $this->resize_and_save('images', $image_id, $upload_location, $this->max_image_w, $this->max_image_h);

            // Removed the staged file
            unlink($upload_location);

            return $image_id;
        }

        return false;
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
     * @return          array   Returns $handle from wp_handle_uploads. If it was necessary to convert to .jpg,
     *                          $handle['file'] will be set with the new .jpg's directory/filename.
     */
    protected function stage_image($file)
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

        // If wp_handle_upload worked, check if it's necessary to convert the uploaded file to .jpg.
        if (!is_wp_error($handle))
        {
            $this->convert_to_jpg($handle);
        }
        // Return default upload directory
        remove_action('upload_dir', array( &$this, 'set_to_gm_community_gallery_directory'));

        return $handle;
    }

    /**
     * Converts the uploaded image to .jpg if the original was either .png or .jpeg.
     *
     * @param   $handle     array   Associative array returned by wp_handle_upload().
     */
    protected function convert_to_jpg(&$handle)
    {
        // Tell me of your homeworld, Usul.
        $is_png  = $handle['type'] === 'image/png'  ? true : false;
        $is_jpeg = $handle['type'] === 'image/jpeg' ? true : false;

        // If this is a .png, convert it to a .jpg
        if ($is_png || $is_jpeg)
        {
            $file = $handle['file'];

            // Get the new .jpg directory.
            $new_file = $this->replace_extension($file);

            $img = null;

            switch ($handle['type'])
            {
                case 'image/png':
                    $img  = imagecreatefrompng($file);
                    break;
                case 'image/jpeg':
                    $img = imagecreatefromjpeg($file);
                    break;
                default:
                    // Should not be possible
                    break;
            }

            $quality = 100;
            imagejpeg($img, $new_file, $quality);
            imagedestroy($img);

            // Replace the value at $handle['file'] with the new .jpg's directory and filename.
            $handle['file'] = $new_file;

            // Destroy the temporarily staged image.
            unlink($file);
        }
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
                'path'   => $dir['basedir'] . '/gm-community-submit',
                'url'    => $dir['baseurl'] . '/gm-community-submit',
                'subdir' => '/gm-community-submit',
            ) + $dir;
    }

    /**
     * Re-sizes image if necessary and saves the image in a subdirectory of /uploads/gm-community-submit
     *
     * @param $save_dir         string  The subdirectory of /uploads/gm-community-submit/ where the image is saved.
     * @param $image_name       string  The name of the image file.
     * @param $staged_location  string  Where the uploaded image is temporarily staged.
     * @param $max_w            int     The px value for max width.
     * @param $max_h            int     The px value for max height.
     * @return bool             False if edit failed. Else, true.
     */
    protected function resize_and_save($save_dir, $image_name, $staged_location, $max_w, $max_h)
    {
        $wp_uploads_dir = wp_get_upload_dir();
        $wp_uploads_dir_base = $wp_uploads_dir['basedir'];
        $save_location = $wp_uploads_dir_base . "/gm-community-submit/$save_dir/" . $image_name;

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
     * @return bool|string  False if the upload was not succesful. Else, return the ID created by $this->get_new_id();
     */
    public function return_upload_flag()
    {
        return $this->upload_flag;
    }

}