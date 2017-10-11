<?php

/**
 * @package     GM-Community-Gallery
 * @author      Gabriel Mioni <gabriel@gabrielmioni.com>
 */

namespace GM_community_gallery\submit;

/**
 * Used to send email's to the GM Community Gallery admin when a new image is sent.
 *
 * @package GM_community_gallery\submit
 *
 */
class send_email_notification {

    /**
     * send_email constructor.
     *
     * @param   array   $input_data     Data from the submitter. Array('submitter_email'=>email_value,'submitter_name'=>name_value,'image_url'=>url_value)
     */
    public function __construct( array $input_data, $image_id, $image_ext )
    {
        $this->send_notifcation($input_data, $image_id, $image_ext);
    }

    /**
     * Creates a PHPMailer object and send an email.
     *
     * @param   array $input_data     Data passed from the constructor about the image upload.
     * @param   $image_id             string  ID that was created when the image was uploaded.
     * @param   $image_ext            string  Extension for the uploaded image.
     * @return  bool     True if email was sent. Else false.
     */
    protected function send_notifcation(array $input_data, $image_id, $image_ext )
    {
        $php_mailer_path = ABSPATH . WPINC . '/class-phpmailer.php';

        if ( ! file_exists($php_mailer_path) )
        {
            return false;
        }

        require_once( ABSPATH . WPINC . '/class-phpmailer.php' );

        $receiver_email = $this->set_receiver_address();

        if ( $receiver_email === false )
        {
            return false;
        }

        $recipient_site = $this->get_sitename();

        $gallery_email_address = 'gallery@' . $recipient_site;

        $sender_title   = $this->set_from_input_data($input_data, 'title', 'No Title');
        $sender_email   = $this->set_from_input_data($input_data, 'email', 'No Email');
        $sender_name    = $this->set_from_input_data($input_data, 'name', 'No Name');
        $sender_img_url = $this->build_img_url( $image_id, $image_ext );

        $mail = new \PHPMailer;

        $content  = '<!DOCTYPE html><html><body><p>';
        $content .= "Author: $sender_name <br>";
        $content .= "Title: $sender_title<br>";
        $content .= "Email: $sender_email </p>";
        $content .= "<img src='$sender_img_url'>";
        $content .= "<p>$sender_img_url</p>";
        $content .= '</body></html>';

        $mail->setFrom($gallery_email_address, 'GM Community Gallery');
        $mail->addAddress($receiver_email, 'Gallery Admin');
        $mail->Subject  = 'A new image was uploaded to your gallery!';
        $mail->Body     = $content;
        $mail->IsHTML(true);

        if( !$mail->Send() )
        {
            error_log("PHPMailer: " . $mail->ErrorInfo);
            return false;
        }
        else {
            return true;
        }
    }

    /**
     * Checks an array element at $input_data[$index]. If present, return value, else return $return_on_fail
     *
     * @param   array $input_data
     * @param   $index                string
     * @param   $return_on_fail       mixed
     * @return  mixed
     */
    protected function set_from_input_data( array $input_data, $index, $return_on_fail )
    {
        return isset( $input_data[$index] ) ? htmlentities( $input_data[$index] ) : $return_on_fail;
    }

    /**
     * Checks to see if an email address is set from the GM Community Gallery admin page. Try to get the WordPress
     * admin email address.
     *
     * @return string|bool   False if neither an admin or GM Community Gallery email could be found, else return the found email.
     */
    protected function set_receiver_address() {

        $options     = get_option('gm_community_gallery_options');
        
        if ( isset($options['notification_email']) )
        {
            $notification_email = $options['notification_email'];

            if ( filter_var($notification_email, FILTER_VALIDATE_EMAIL) )
            {
                return filter_var($notification_email, FILTER_SANITIZE_EMAIL);
            }
        }

        $admin_email = get_option('admin_email');

        if ( is_string($admin_email) )
        {
            return filter_var($admin_email, FILTER_SANITIZE_EMAIL);
        }

        return false;
    }

    /**
     * @param $img_id       string  Imag
     * @param $image_ext    string
     * @return string
     */
    protected function build_img_url($img_id, $image_ext)
    {
        $wp_uploads_dir = wp_upload_dir();
        $wp_uploads_dir_base = $wp_uploads_dir['baseurl'];
        $gm_directory = $wp_uploads_dir_base . '/gm-community-gallery';
        return $gm_directory . '/images/' .$img_id . '.' . $image_ext;
    }

    /**
     * @return string|bool
     */
    protected function get_sitename()
    {
        $wp_url = get_site_url();

        if (is_string($wp_url) && trim($wp_url) !== '')
        {
            $url_parse = parse_url($wp_url);
            $site_name = isset($url_parse['host']) ? $url_parse['host'] : false;

            return $site_name;
        } else {
            return false;
        }
    }
}

