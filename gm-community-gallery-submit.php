<?php

class gm_community_gallery_submit
{
    /** @var bool|null  flag for whether the honeypot input is empty. */
    protected $honey_pot_is_empty = null;

    /** @var bool|null  flag for whether request is Ajax.  */
    protected $is_ajax = null;

    protected $allowed_mimes = array('jpg' =>'image/jpg','jpeg' =>'image/jpeg', 'gif' => 'image/gif', 'png' => 'image/png');

    /** @var array  Holds input data from the HTML contact form. */
    protected $input_data   = array();
    /** @var array  Holds array elements representing which inputs failed and why. 0 = empty, -1 = invalid. */
    protected $errors       = array();
    /** @var array  Holds text error messages that will be displayed on the email contact form. */
    protected $error_msgs   = array();

    protected $image_index = 'image';

    public function __construct()
    {

        $this->honey_pot_is_empty    = isset($_POST['covfefe']) ? false : true;
        $this->is_ajax               = isset($_POST['is_ajax']) ? true : false;

        $this->input_data['name']    = $this->check_text('name', $this->errors);
        $this->input_data['email']   = $this->check_email('email', $this->errors);
        $this->input_data['message'] = $this->check_text('message', $this->errors);

        $this->check_image($this->image_index, $this->errors);

        $this->error_msgs = $this->build_error_msgs($this->errors);

        $this->try_upload($this->error_msgs, $this->image_index);

        $this->non_ajax_processing($this->is_ajax, $this->error_msgs, $this->input_data);
    }

    /**
     * Checks $_POST values. If the input is required, the error array can be set as an argument. If the $error_array
     * is an array and the input value is blank, $error_array[$post_index] is set to 0.
     *
     * @param   $post_index     string      The index name for the $_POST value being checked.
     * @param   $error_array    null|array  Default is null. If an array is provided, input value is required.
     * @return  string          string      Either whitespace or sanitized value of $_POST[$post_index]
     */
    protected function check_text($post_index, &$error_array = null)
    {
        $input = isset($_POST[$post_index]) ? trim($_POST[$post_index]) : '';

        if ($input === '' && is_array($error_array))
        {
            $error_array[$post_index] = 0;
        }

        return strip_tags($input);
    }

    /**
     * Checks if email is either blank or invalid. If email is blank, $error_array[$email_index] is 0. If email is
     * invalid, $error_array[$email_index] is -1.
     *
     * @param   $email_index        string  The index name for the email input.
     * @param   array $error_array          The error array that will be passed error data by reference.
     * @return  string              string  Either whitespace or sanitized value of $_POST[$email_index]
     */
    protected function check_email($email_index, array &$error_array)
    {
        $email_input = $this->check_text($email_index, $error_array);

        if ($email_input === '')
        {
            return '';
        }

        /* Clean the email input */
        $email = filter_var($email_input, FILTER_SANITIZE_EMAIL);

        /* Validate email */
        $validate = filter_var($email, FILTER_VALIDATE_EMAIL);

        if ($validate === false)
        {
            $error_array[$email_index] = -1;
        }

        return $email;
    }

    /**
     * Checks if the submitted file exists and is valid. If everything is good, try to upload the image. Populates
     * $error_array[$image_index] with values if errors are found.
     *
     * @param       $image_index    string          The index for the $_FILE element we're interested in.
     * @param       array           $error_array    Array that holds
     * @return      bool
     */
    protected function check_image($image_index, array &$error_array)
    {
        if (empty($_FILES))
        {
            $error_array[$image_index] = -2;
            return false;
        }

        // Make sure the image file exists
        if ( isset($_FILES[$image_index]) === false )
        {
            $error_array[$image_index] = -2;
            return false;
        }

        // Check the MIME type
        $file = $_FILES[$image_index];

        $file_name = $file['tmp_name'];

        if (!file_exists($file_name))
        {
            $error_array[$image_index] = -2;
            return false;
        }

        $mime = mime_content_type($file_name);

        if (!in_array($mime, $this->allowed_mimes))
        {
            $error_array[$image_index] = -3;
            return false;
        }

        return true;
    }


    /**
     * Builds an array of validation error messages that can be displayed to the user submitting the email.
     *
     * @param   array   $error_array    The array containing results from $_POST input checks.
     * @return  array                   An array with validation messages for the person submitting the email.
     */
    protected function build_error_msgs(array $error_array)
    {
        $error_msgs = array();

        foreach ($error_array as $key=>$value)
        {
            switch ($value)
            {
                case 0:
                    // The input was blank.
                    $msg = ucfirst("$key cannot be blank");
                    break;
                case -1:
                    // The input was invalid.
                    $msg = "Please make sure the $key field is in valid format";
                    break;
                case -2:
                    // Image wasn't chosen.
                    $msg = "Choose an image to upload.";
                    break;
                case -3:
                    // File selected is not in valid format.
                    $msg = "The file must be in .jpg/jpeg, .gif or .png format.";
                    break;
                case -4:
                    // The upload failed.
                    $msg = "There was a problem uploading your image. Please try again later.";
                    break;
                default:
                    // Something is amiss
                    $msg = "The $key input is incorrect.";
                    break;
            }
            $error_msgs[$key] = $msg;
        }

        return $error_msgs;
    }

    protected function try_upload(&$error_messages, $image_index)
    {
        if (!empty($error_messages))
        {
            return false;
        }

        // Try to stage and upload the image
        require_once('gm-community-gallery-upload.php');

        $upload = new gm_community_gallery_upload();
        $upload_result = $upload->return_upload_flag();

        if ($upload_result === false)
        {
            $error_messages[$image_index] = 'There was a problem uploading your file. Please try again later';
            return false;
        }
    }

    /**
     * If this isn't an Ajax call, then do the following:
     * - 1. Unset previous $_SESSION messages.
     * - 2. If no errors (all inputs are valid and the email has been sent), set $_SESSION success message
     * - 3. If there were errors, set $_SESSION messages.
     * - 4. Redirect to referer.
     *
     * @param $is_ajax  bool    Flag stating whether or not the request is Ajax. Set by $_POST['is_ajax']
     * @param array     $error_msgs     Error messages array. Used to build error session variables.
     * @param array     $input_data     Input data.
     */
    protected function non_ajax_processing($is_ajax, array $error_msgs, array $input_data)
    {
        if ($is_ajax === false)
        {
            if (empty($error_msgs))
            {
                $_SESSION['gm_success'] = 1;
            } else {
                $this->set_session_message($error_msgs, 'gm_error_');
                $this->set_session_message($input_data, 'gm_value_');
            }

            $this->do_redirect();
        }
    }

    /**
     * Loop through $message_array and set $_SESSION error messages with the value of each element in $message_array
     *
     * @param   array   $message_array  Array of error messages.
     * @param   string  $prepend_key    The value that should be used to build the beginning of the new $_SESSION index.
     */
    protected function set_session_message(array $message_array, $prepend_key)
    {
        foreach ($message_array as $key=> $value)
        {
            $session_index = $prepend_key . $key;
            $_SESSION[$session_index] = $value;
        }
    }

    /**
     * Sends user back to the page from which the submit page was submitted.
     */
    protected function do_redirect()
    {
        header('Location: ' . strtok($_SERVER["HTTP_REFERER"],'?'));
        exit();
    }
}