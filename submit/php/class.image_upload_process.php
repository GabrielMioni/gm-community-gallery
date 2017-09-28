<?php

namespace GM_community_gallery\submit;

/**
 * @package     GM-Community-Gallery
 * @author      Gabriel Mioni <gabriel@gabrielmioni.com>
 */

/**
 * Validates both text inputs and the uploaded image. If everything is good, update the gm_community_gallery MySQL table,
 * and use WordPress's image library to move/resize images to the 'thumbs' and 'images' directories in
 * uploads/gm-community-submit
 *
 * By default, .jpeg and .png files are converted to .jpg.
 */
class image_upload_process
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
        $this->input_data['title']   = $this->check_text('title', $this->errors);
        $this->input_data['ip']      = $this->check_ip();

        $this->check_image($this->image_index, $this->errors);

        $this->error_msgs = $this->build_error_msgs($this->errors);

        $this->try_upload($this->error_msgs, $this->input_data);

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

        // Clean the email input
        $email = filter_var($email_input, FILTER_SANITIZE_EMAIL);

        // Validate email
        $validate = filter_var($email, FILTER_VALIDATE_EMAIL);

        if ($validate === false)
        {
            $error_array[$email_index] = -1;
        }

        return $email;
    }

    /**
     * Yoinked from https://stackoverflow.com/questions/15699101/get-the-client-ip-address-using-php
     *
     * @return  string  The IP address.
     */
    function check_ip()
    {
        $ip_address = '';
        if (isset($_SERVER['HTTP_CLIENT_IP']))
        {
            $ip_address = $_SERVER['HTTP_CLIENT_IP'];
        } else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        {
            $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else if(isset($_SERVER['HTTP_X_FORWARDED']))
        {
            $ip_address = $_SERVER['HTTP_X_FORWARDED'];
        } else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        {
            $ip_address = $_SERVER['HTTP_FORWARDED_FOR'];
        } else if(isset($_SERVER['HTTP_FORWARDED']))
        {
            $ip_address = $_SERVER['HTTP_FORWARDED'];
        } else if(isset($_SERVER['REMOTE_ADDR']))
        {
            $ip_address = $_SERVER['REMOTE_ADDR'];
        } else
        {
            $ip_address = 'UNKNOWN';
        }

        return $ip_address;
    }

    /**
     * Retrieve the file name submitted.
     *
     * @param $image_index
     * @return string
     * @deprecated Ain't no one got time for that.
     */
    protected function get_image_name($image_index)
    {
        if (isset($_FILES[$image_index]))
        {
            $file = $_FILES[$image_index];

            $file_name = $file['name'];

            return strip_tags($file_name);
        }

        return '';
    }

    /**
     * Checks if the submitted file exists and is valid. If everything is good, try to upload the image. Populates
     * $error_array[$image_index] with values if errors are found. Also passes the file extension to $this->input['type'].
     *
     * @param       $image_index    string          The index for the $_FILE element we're interested in.
     * @param       array           $error_array    Array that holds
     * @return      bool
     */
    protected function check_image($image_index, array &$error_array)
    {
        // Make sure $_FILES were submitted
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

        // Set the $_FILE element being checked
        $file = $_FILES[$image_index];
        $temp_location = $file['tmp_name'];
        $file_name     = $file['name'];

        // Make sure the tmp file exists
        if (!file_exists($temp_location))
        {
            $error_array[$image_index] = -2;
            return false;
        }

        // Check the MIME type provided in $_FILES
        $mime = mime_content_type($temp_location);
        if (!in_array($mime, $this->allowed_mimes))
        {
            $error_array[$image_index] = -3;
            return false;
        }

        // Examine the actual file name and confirm the extension is an allowed type.
        /** @var array $short_mimes Should be like array('gif', 'jpeg', 'jpg', 'png')*/

        $short_mimes = array_keys($this->allowed_mimes);

        $extension = substr($file_name, strrpos($file_name, '.') + 1);

        $_SESSION['ext'] = $extension;

        // Add the file extension to $this->input_data
        $this->input_data['type'] = $extension;

        if (!in_array($extension, $short_mimes))
        {
            $error_array[$image_index] = -4;
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

    /**
     * Try to upload the image and update the gm_community_gallery MySQL table.
     *
     * @param   array   $error_messages     Array holding previous error messages. If either the upload or MySQL insert fails
     *                                      this array will be modified by reference.
     * @param   array   $input_data         Text $_POST values from the image upload form. Passed to $this->update_gm_community_gallery_table()
     * @return  bool    On success, returns true. On failure, returns false.
     */
    protected function try_upload(array &$error_messages, array &$input_data)
    {
        if (!empty($error_messages))
        {
            return false;
        }

        // Try to stage and upload the image
        require_once('class.uploader.php');

        $upload = new uploader();
        $image_id = $upload->return_upload_flag();

        if ($image_id === false)
        {
            $error_messages['generic'] = 'There was a problem uploading your file. Please try again later';
            return false;
        }

        // There was no error uploading the image or any validation errors for the field inputs!

        $update = $this->update_gm_community_gallery_table($image_id, $input_data);

        if ($update === false)
        {
            $error_messages['generic'] = 'There was a problem uploading your file. Please try again later';
            return false;
        }

        return true;
    }

    /**
     * Updates the gm_community_gallery MySQL table using $wpdb->insert().
     *
     * @param   $image_id   string          The unique id created by the gm_community_gallery_upload object.
     * @param   array       $input_data     Array of $_POST text values. Passed to $wpdb->insert().
     * @return  bool                        On success return true. On failure return false.
     */
    protected function update_gm_community_gallery_table($image_id, array &$input_data)
    {
        $table_name = GM_GALLERY_TABLENAME;

        // Add the new $image_id variable to the array
        $input_data['id'] = $image_id;

        global $wpdb;

        $wpdb->insert($table_name, $input_data);

        $sql_error = $wpdb->last_error;

        if ($sql_error !== '')
        {
            error_log($sql_error);
            $_SESSION['generic'] = 'There was a problem uploading your image. Please try again later.';
        }

        if ($sql_error === '')
        {
            return true;
        }

        return false;

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