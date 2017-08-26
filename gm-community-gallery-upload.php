<?php

if ( ! function_exists( 'wp_handle_upload' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/file.php' );
}

class gm_community_gallery_upload
{
    /** @var bool  */
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
     * Temporarily stages the uploaded image and saves re-sized copies in gm-community-gallery subdirectories (thumbs and images)
     *
     * @param   $image_index  string    The index used to specify the $_FILE element for the image being uploaded.
     * @return  bool
     */
    protected function upload_image($image_index)
    {
        if ( ! isset($_FILES[$image_index]))
        {
            return false;
        }

        $file = $_FILES[$image_index];
        $image_name = $file['name'];

        $staged_image = $this->stage_image($file);

        if (!is_wp_error($staged_image))
        {
            $upload_location = $staged_image['file'];

            // Save the thumbnail
            $this->resize_and_save('thumbs', $image_name, $upload_location, $this->max_thumb_w, $this->max_thumb_h);

            // Save the image
            $this->resize_and_save('images', $image_name, $upload_location, $this->max_image_w, $this->max_image_h);

            // Removed the staged file
            unlink($upload_location);

            return true;
        }

        return false;
    }

    protected function stage_image($file)
    {
        $allowed_mimes = array('jpg' =>'image/jpg','jpeg' =>'image/jpeg', 'gif' => 'image/gif', 'png' => 'image/png');
        $upload_overrides = array( 'test_form' => false, 'mimes' => $allowed_mimes );

        add_action('upload_dir', array( &$this, 'set_to_upload_directory'));
        $handle = wp_handle_upload($file, $upload_overrides);
        remove_action('upload_dir', array( &$this, 'set_to_upload_directory'));

        return $handle;
    }

    /**
     * Used to temporarily change the directory WordPress uses when uploading. Called on the 'upload_dir' hook.
     *
     * @param   $dir
     * @return  array
     */
    function set_to_upload_directory($dir)
    {
        return array(
                'path'   => $dir['basedir'] . '/gm-community-gallery',
                'url'    => $dir['baseurl'] . '/gm-community-gallery',
                'subdir' => '/gm-community-gallery',
            ) + $dir;
    }

    /**
     * Re-sizes image if necessary and saves the image in a subdirectory of /uploads/gm-community-gallery
     *
     * @param $save_dir         string  The subdirectory of /uploads/gm-community-gallery/ where the image is saved.
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
        $save_location = $wp_uploads_dir_base . "/gm-community-gallery/$save_dir/" . $image_name;

        $image_edit = wp_get_image_editor($staged_location);

        if (is_wp_error($image_edit))
        {
            return false;
        }

        $image_edit->resize($max_w, $max_h, false);
        $image_edit->save($save_location);

        return true;
    }

    public function return_upload_flag()
    {
        return $this->upload_flag;
    }

}