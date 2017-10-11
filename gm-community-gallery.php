<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);
/*
Plugin Name: GM Community Gallery
Version: 1.0
Plugin URI: http://gabrielmioni.com/gm-community-gallery
Description: Automated accept image files and add them to a WordPress submit
Author: Gabriel Mioni
Author URI: http://gabrielmioni.com

Copyright 2017 Gabriel Mioni <email : gabriel@gabrielmioni.com>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * @package     GM-Community-Gallery
 * @author      Gabriel Mioni <gabriel@gabrielmioni.com>
 */

define( 'GM_GALLERY_VERSION', '1.0' );

define( 'GM_GALLERY_DIR', plugin_dir_path(__FILE__) );

define( 'GM_GALLERY_TABLENAME', gm_gallery_define_tablename());

function gm_gallery_define_tablename()
{
    global $wpdb;

    $table_prefix = $wpdb->prefix;

    if (trim($table_prefix) === '')
    {
        $table_prefix = 'wp_';
    }

    $tablename = $table_prefix . 'gm_community_gallery';
    return $tablename;
}

/* ********************************
 * - Activate GM Community Gallery
 * ********************************/

register_activation_hook( __FILE__, 'gm_community_gallery_activated' );
function gm_community_gallery_activated()
{
    gm_gallery_create_sql_db();
    gm_gallery_create_directories();
}

/* ******************************************************
 * - Create the /uploads/gm-community-gallery directory
 * ******************************************************/

function gm_gallery_create_directories()
{
    $wp_uploads_dir = wp_upload_dir();
    $wp_uploads_dir_base = $wp_uploads_dir['basedir'];
    $gm_directory = $wp_uploads_dir_base . '/gm-community-gallery';
    $gm_thumbs = $gm_directory . '/thumbs';
    $gm_images = $gm_directory . '/images';

    wp_mkdir_p($gm_directory);
    wp_mkdir_p($gm_thumbs);
    wp_mkdir_p($gm_images);
}


/* ******************************************************
 * - Create the gm_community_gallery SQL db
 * ******************************************************/

function gm_gallery_create_sql_db()
{
    $tablename = GM_GALLERY_TABLENAME;

    $create_table =
        "CREATE TABLE IF NOT EXISTS $tablename (
              `id` char(6) NOT NULL,
              `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `email` varchar(100) DEFAULT NULL,
              `ip` varchar(45) DEFAULT NULL,
              `name` varchar(100) DEFAULT NULL,
              `title` varchar(200) DEFAULT NULL,
              `message` varchar(600) DEFAULT NULL,
              `tags` varchar(600) DEFAULT NULL,
              `comment` varchar(600) DEFAULT NULL,
              `trash` tinyint(1) NOT NULL DEFAULT '0',
              `hidden` tinyint(1) NOT NULL DEFAULT '0',
              `type` varchar(4) NOT NULL DEFAULT 'jpg'
            );";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    dbDelta($create_table);
}

/* *******************************
 * - Register Submit Form CSS
 * *******************************/
add_action( 'wp_enqueue_scripts', 'gm_register_submit_form_css');
function gm_register_submit_form_css()
{
    wp_register_style( 'gm-submit-css', plugins_url( 'submit/css/gm-gallery.css', __FILE__ ), array(), GM_GALLERY_VERSION, 'all' );
}

/* *******************************
 * - Register Pagination CSS
 * *******************************/
add_action( 'wp_enqueue_scripts', 'gm_register_pagination_css' );
function gm_register_pagination_css()
{
    wp_register_style( 'gm-pagination-css', plugins_url( 'nav/css/pagination.css', __FILE__ ), array(), GM_GALLERY_VERSION, 'all' );
}

/* *******************************
 * - Register Gallery CSS
 * - Used in both public/admin galleries
 * *******************************/
add_action( 'wp_enqueue_scripts', 'gm_register_gallery_css');
function gm_register_gallery_css()
{
    wp_register_style( 'gm-gallery-css', plugins_url( 'nav/css/gallery.css', __FILE__ ), array(), GM_GALLERY_VERSION, 'all' );
}

/* *******************************
 * - Register Public Gallery CSS
 * *******************************/
add_action( 'wp_enqueue_scripts', 'gm_register_public_css' );
function gm_register_public_css()
{
    wp_register_style( 'gm-public-css', plugins_url( 'public/css/gallery.css', __FILE__ ), array(), GM_GALLERY_VERSION, 'all' );
}


/* *******************************
 * - Upload Form Shortcode
 * *******************************/

add_shortcode('gm-submit-form', 'gm_gallery_form_shortcode');
function gm_gallery_form_shortcode()
{
    wp_enqueue_script('gm_submit');
    wp_enqueue_style('gm-font-awesome');

    require_once('submit/php/class.image_upload_form.php');

    // Add the gm-contact.css file
    wp_enqueue_style('gm-submit-css');

    $auto_p_flag = false;

    // If wpautop filter is set, temporarily disable it.
    if ( has_filter( 'the_content', 'wpautop' ) )
    {
        $auto_p_flag = true;
        remove_filter( 'the_content', 'wpautop' );
        remove_filter( 'the_excerpt', 'wpautop' );
    }

    $build_html = new GM_community_gallery\submit\image_upload_form();
    echo $build_html->return_html();

    // If wpautop had been set previously, re-enable it.
    if ($auto_p_flag === true)
    {
        add_filter( 'the_content', 'wpautop' );
        add_filter( 'the_excerpt', 'wpautop' );
    }
}

add_action( 'wp_enqueue_scripts', 'gm_register_font_awesome' );
function gm_register_font_awesome()
{
    wp_register_style('gm-font-awesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css', GM_GALLERY_VERSION, 'all');
}


/* *********************
 * - Register Public JS
 * *********************/

add_action( 'wp_enqueue_scripts', 'gm_register_public_js' );
function gm_register_public_js()
{
    $is_mobile = wp_is_mobile() === true ? 1 : 0;
    $gif_url = plugins_url('public/images/', __FILE__);

    if ( $is_mobile === 1 )
    {
        wp_register_script( 'tocca', plugins_url( 'public/js/tocca/Tocca.min.js', __FILE__ ), array('jquery'), null, true );
        wp_enqueue_script( 'tocca' );
    }

    wp_register_script( 'gm-public-js', plugins_url( 'public/js/gm_lightbox.js', __FILE__ ), array( 'jquery'), GM_GALLERY_VERSION, true );

    wp_localize_script('gm-public-js', 'gm_js', array( 'is_mobile' => $is_mobile, 'loading_gif' => $gif_url ) );
}

/* **************************
 * - Register JS Upload Form
 * *************************/

// Enqueue the script, in the footer
add_action( 'wp_enqueue_scripts', 'gm_js_upload_form');
function gm_js_upload_form() {

    // Enqueue the script
//    wp_register_script( 'gm_js_form',  plugins_url( 'submit/js/form.js', __FILE__ ), array('jquery'), GM_GALLERY_VERSION, true );
    wp_register_script( 'gm_submit',  plugins_url( 'submit/js/submit.js', __FILE__ ), array('jquery'), GM_GALLERY_VERSION, true );
    wp_enqueue_script('jquery-effects-shake');

    $gif_url = plugins_url('public/images/', __FILE__) . 'wpspin-2x.gif';

    // Get current page protocol.
    $protocol = isset( $_SERVER["HTTPS"]) ? 'https://' : 'http://';

    // create nonce_field
    $gm_nonce = wp_nonce_field('gm_js_submit');

    // Get max file size.
    $options  = get_option('gm_community_gallery_options');

    $max_img_kb = isset( $options['max_img_size'] ) ? intval($options['max_img_size']) : 100;

    // Localize ajaxurl with protocol
    $params = array( 'ajaxurl' => admin_url( 'admin-ajax.php', $protocol), 'gm_nonce_field' => $gm_nonce, 'loading_gif' => $gif_url, 'max_img_kb' => $max_img_kb);
    wp_localize_script( 'gm_submit', 'gm_submit', $params );
}


/* *******************************
 * - Public Gallery Shortcode
 * *******************************/

add_shortcode('gm-public-gallery', 'gm_public_gallery_shortcode');
function gm_public_gallery_shortcode()
{
    wp_enqueue_style('gm-gallery-css');
    wp_enqueue_style('gm-public-css');
    wp_enqueue_style('gm-font-awesome');
    wp_enqueue_script('gm-public-js');

    if (isset($_GET['view']))
    {
        require_once('public/php/class.public_view.php');

        $build_public_view = new public_view();
        echo $build_public_view->return_html();

        return false;
    }

    require_once('public/php/class.public_navigate.php');
    require_once('public/php/class.public_gallery.php');
    require_once('nav/class.pagination.php');

    wp_enqueue_style('gm-pagination-css');

    $auto_p_flag = false;

    // If wpautop filter is set, temporarily disable it.
    if ( has_filter( 'the_content', 'wpautop' ) )
    {
        $auto_p_flag = true;
        remove_filter( 'the_content', 'wpautop' );
        remove_filter( 'the_excerpt', 'wpautop' );
    }

    $show_trash = false;
    $public_navigate = new GM_community_gallery\_public\public_navigate($show_trash);

    $build_public_gallery = new GM_community_gallery\_public\public_gallery($public_navigate);
    $build_public_pagination = new pagination($public_navigate);

    echo $build_public_pagination->return_pagination_html();
    echo $build_public_gallery->return_gallery_html();

    // If wpautop had been set previously, re-enable it.
    if ($auto_p_flag === true)
    {
        add_filter( 'the_content', 'wpautop' );
        add_filter( 'the_excerpt', 'wpautop' );
    }
}

/* *******************************
 * - Settings Page
 * *******************************/

add_action( 'wp_enqueue_scripts', 'gm_register_submit_form_css');
function gm_register_settings_css()
{
    wp_enqueue_style( 'gm-gallery-settings-css', plugins_url( 'admin/css/gm-gallery-settings.css', __FILE__ ), array(), GM_GALLERY_VERSION, 'all' );
}

function gm_print_gallery_css_for_admin()
{
    wp_enqueue_style( 'gm-admin-gallery-css', plugins_url( 'nav/css/gallery.css', __FILE__ ), array(), GM_GALLERY_VERSION, 'all' );
}

function gm_print_font_awesome_for_admin()
{
    wp_enqueue_style('gm-adming-font-awesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css', GM_GALLERY_VERSION, 'all');
}

function gm_enqueue_admin_js()
{
    wp_enqueue_script('gm-adming-js', plugins_url( 'admin/js/admin.js', __FILE__ ), array( 'jquery'), GM_GALLERY_VERSION, true);
}

add_action( 'admin_menu', 'gm_community_gallery_menu' );
function gm_community_gallery_menu()
{
    $gallery_count = gm_get_gallery_count();
    $trash_count = gm_get_gallery_count(true);

    $admin_title = "Admin Gallery ($gallery_count)";
    $trash_title = "Trash ($trash_count)";
    $opts_title  = 'Gallery Options';

    $menu = add_menu_page('GM Community Gallery', 'GM Community Gallery', 'manage_options', 'gm-community-gallery', 'gm_community_admin_gallery' );
    add_submenu_page('gm-community-gallery', $admin_title, $admin_title, 'manage_options', 'gm-community-gallery' );
    $trash_sub = add_submenu_page('gm-community-gallery', $trash_title, $trash_title, 'manage_options', 'gm-community-trash', 'gm_community_admin_trash');
    $opts_sub  = add_submenu_page('gm-community-gallery', $opts_title, $opts_title, 'manage_options', 'gm-community-options', 'gm_community_admin_options');

    add_action( 'admin_init', 'gm_enqueue_admin_js');
    add_action( 'admin_print_styles-' . $menu, 'gm_print_gallery_css_for_admin');
    add_action( 'admin_print_styles-' . $menu, 'gm_register_settings_css' );
    add_action( 'admin_print_styles-' . $menu, 'gm_print_font_awesome_for_admin' );

    add_action( 'admin_print_styles-' . $trash_sub, 'gm_print_gallery_css_for_admin');
    add_action( 'admin_print_styles-' . $trash_sub, 'gm_register_settings_css' );
    add_action( 'admin_print_styles-' . $trash_sub, 'gm_print_font_awesome_for_admin' );

    add_action( 'admin_print_styles-' . $opts_sub, 'gm_print_gallery_css_for_admin');
    add_action( 'admin_print_styles-' . $opts_sub, 'gm_register_settings_css' );
    add_action( 'admin_print_styles-' . $opts_sub, 'gm_print_font_awesome_for_admin' );
}

function gm_get_gallery_count($trash = false)
{
    global $wpdb;

    $trash_value = $trash === false ? 0 : 1;

    $query = "SELECT count(*) FROM " . GM_GALLERY_TABLENAME . " WHERE trash = $trash_value";

    $results = $wpdb->get_var($query);

    return $results;
}

function gm_community_admin_gallery()
{
    if ( !current_user_can( 'manage_options' ) )
    {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }

    echo '<div class="wrap">';

    if ( isset( $_GET['edit'] ) )
    {
        require_once('admin/php/class.image_update_form.php');

        $build_form = new GM_community_gallery\admin\image_update_form();
        echo $build_form->return_html();

    } else {

        $trash_count = gm_get_gallery_count();

        if ($trash_count < 1)
        {
            echo "Nothing here.";
            return false;
        }

        require_once('admin/php/class.admin_navigate.php');
        require_once('admin/php/class.admin_gallery.php');
        require_once('admin/php/class.admin_search_form.php');
        require_once('nav/class.pagination.php');

        // Build the navigate object
        $show_trash = false;
        $admin_navigate = new GM_community_gallery\admin\admin_navigate($show_trash);

        // Build gallery
        $admin_gallery  = new GM_community_gallery\admin\admin_gallery($admin_navigate);
        $html_gallery = $admin_gallery->return_gallery_html();

        // Build pagination
        $admin_pagination = new pagination($admin_navigate);
        $html_pagination = $admin_pagination->return_pagination_html();

        // Build Search Form
        $admin_search_form = new \GM_community_gallery\admin\admin_search_form();
        $html_search_form  = $admin_search_form->return_search_form();

        // Display everything
        echo $html_search_form;
        echo $html_pagination;
        echo $html_gallery;
    }

    echo '</div>';
}

function gm_community_admin_trash()
{
    if ( !current_user_can( 'manage_options' ) )
    {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    } else {

        $trash_count = gm_get_gallery_count(true);

        if ($trash_count < 1)
        {
            echo "Nothing here.";
            return false;
        }

        echo '<div class="wrap">';

        require_once('admin/php/class.admin_navigate.php');
        require_once('admin/php/class.admin_gallery_trash.php');
        require_once('admin/php/class.admin_search_form.php');
        require_once('nav/class.pagination.php');

        // Build the navigate object
        $show_trash = true;
        $admin_navigate = new GM_community_gallery\admin\admin_navigate($show_trash);

        // Build gallery
        $admin_gallery  = new GM_community_gallery\admin\admin_gallery_trash($admin_navigate);
        $html_gallery = $admin_gallery->return_gallery_html();

        // Build pagination
        $admin_pagination = new pagination($admin_navigate);
        $html_pagination = $admin_pagination->return_pagination_html();
/*
        // Build Search Form
        $admin_search_form = new \GM_community_gallery\admin\admin_search_form();
        $html_search_form  = $admin_search_form->return_search_form();
*/
        // Display everything
//        echo $html_search_form;
        echo $html_pagination;
        echo $html_gallery;

        echo '</div>';
    }
}

function gm_community_admin_options()
{
    if ( !current_user_can( 'manage_options' ) )
    {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }
    ?>
    <div class="wrap">
        <form id="gm_gallery_admin_options_table" method="post" action="options.php">

            <table id="gm_gallery_admin_options_table" class="form-table">
                <?php   settings_fields('gm_community_gallery_options');  ?>
                <?php   do_settings_sections('gm-community-options');     ?>
            </table>
            <p class="submit">
                <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
            </p>
        </form>
    </div>
    <?php
}

add_action('admin_init', 'gm_community_gallery_admin_init');
function gm_community_gallery_admin_init()
{
    register_setting('gm_community_gallery_options', 'gm_community_gallery_options', 'gm_community_gallery_validate_settings');
    add_settings_section('gm_gallery_options_main', 'GM Community Gallery Settings', 'gm_gallery_opts_text', 'gm-community-options');

    add_settings_field('gm_send_notification',      'Send Notifications?',  'gm_show_send_email_input',     'gm-community-options', 'gm_gallery_options_main');
    add_settings_field('gm_notification_email',     'Notification Email',   'gm_show_email_input',          'gm-community-options', 'gm_gallery_options_main');
    add_settings_field('gm_max_kb_text_string',     'Max Image Size (kb)',  'gm_show_max_img_input',        'gm-community-options', 'gm_gallery_options_main');
    add_settings_field('gm_images_per_page_string', 'Images per page:',     'gm_show_per_page_select',      'gm-community-options', 'gm_gallery_options_main');
    add_settings_field('gm_images_banned_ips',      'Banned IPs:',          'gm_show_banned_ip_textarea',   'gm-community-options', 'gm_gallery_options_main');

}

function gm_gallery_opts_text() {
    ?>
    Enter some stuff please.
    <?php
}

add_action('admin_notices', 'gm_community_gallery_admin_notices');
function gm_community_gallery_admin_notices(){
    settings_errors();
}

function gm_show_send_email_input() {
    $options  = get_option('gm_community_gallery_options');
    $display = isset($options['send_email']) ? intval($options['send_email']) : 0;

    $select = '<select name="gm_community_gallery_options[send_email]">';

    if ($display === 0)
    {
        $select .= "<option value='0'>No</option><option value='1'>Yes</option>";
    } elseif ($display === 1) {
        $select .= "<option value='0'>No</option><option selected value='1'>Yes</option>";
    }

    $select .= '</select>';

    echo $select;
}

function gm_show_email_input() {
    $options  = get_option('gm_community_gallery_options');
    $display = isset($options['notification_email']) ? $options['notification_email'] : '';

    echo "<input type='text' name='gm_community_gallery_options[notification_email]' value='$display' >";
}

function gm_show_max_img_input() {
    $options  = get_option('gm_community_gallery_options');
    $display = isset($options['max_img_size']) ? $options['max_img_size'] : '';

    echo "<input type='text' name='gm_community_gallery_options[max_img_size]' value='$display' >";
}

function gm_show_per_page_select() {

    $options  = get_option('gm_community_gallery_options');

    $display = isset($options['imgs_per_page']) ? $options['imgs_per_page'] : '';

    $select_values = array(10,15,20,25,30);

    $select = '<select name="gm_community_gallery_options[imgs_per_page]">';

    foreach ($select_values as $opt)
    {
        $selected = intval($display) == $opt ? ' selected' : '';
        $select .= "<option value='$opt' $selected>$opt</option>";
    }

    $select .= '</select>';

    echo $select;
}

function gm_show_banned_ip_textarea() {

    $options  = get_option('gm_community_gallery_options');

    $display  = isset($options['banned_ips']) ? strip_tags( stripslashes( $options['banned_ips'] ) ) : '';

    $textarea = '<textarea name=gm_community_gallery_options[banned_ips]">';

    $textarea .= $display;

    $textarea .= '</textarea>';

    echo $textarea;
}

function gm_community_gallery_validate_settings($input )
{
    $current = get_option('gm_community_gallery_options');

    $check['send_email']            = strip_tags( intval($input['send_email'] ) );
    $check['notification_email']    = strip_tags( trim( $input['notification_email'] ) );
    $check['max_img_size']          = strip_tags( $input['max_img_size'] );
    $check['imgs_per_page']         = strip_tags( $input['imgs_per_page'] );
    $check['banned_ips']            = strip_tags( preg_replace( '/\s+/', '', $input['banned_ips'] ) );

    $page_values = array(10,15,20,25,30);
    $bool_array  = array(1,0);

    $reason = '';

    if ( ! in_array($check['send_email'], $bool_array) )
    {
        add_settings_error( 'gm_send_notification', 'gm_community_gallery_text_error', $reason, 'error' );
    }

    if ($check['notification_email'] !== '' )
    {
        if (filter_var($check['notification_email'], FILTER_VALIDATE_EMAIL) === false) {
            $bad_input = $check['notification_email'];
            $reason = "You submitted '$bad_input.' That's not a valid email address.<br>";
            add_settings_error( 'gm_notification_email', 'gm_notification_error', $reason, 'error' );
        }
    }

    if (filter_var($check['max_img_size'], FILTER_VALIDATE_INT) === false)
    {
        $reason = "Max image size should be an integer.<br>";
        add_settings_error( 'gm_max_kb_text_string', 'gm_max_size_error', $reason, 'error' );
    }

    if(!(in_array($check['imgs_per_page'], $page_values)))
    {
        $reason = 'Invalid option<br>';
        add_settings_error( 'gm_images_per_page_string', 'gm_per_page_error', $reason, 'error' );
    }

    if ($reason !== '')
    {
        return $current;
    }

    return $check;
}

/* *******************************
 * - Ajax Handler
 * *******************************/

add_action('wp_ajax_nopriv_gm_ajax_submit', 'gm_ajax_submit');
add_action('wp_ajax_gm_ajax_submit', 'gm_ajax_submit');

function gm_ajax_submit() {

    $data = $_REQUEST;

    $inputs = $data['inputs'];

    parse_str($inputs, $input_values);

    foreach ($input_values as $key=>$value)
    {
        $_POST[$key] = $value;
    }

    // Just cleanup
    if ( isset($_POST['action']) )
    {
        unset($_POST['action']);
    }
    if ( isset($_POST['inputs']) )
    {
        unset($_POST['inputs']);
    }

    $_POST['is_ajax'] = true;

    require_once('submit/php/class.image_upload_process.php');

    $upload = new GM_community_gallery\submit\image_upload_process();

    $response = $upload->return_response();

    echo json_encode($response);

    die();
}


/* *******************************
 * - API Handler for non-Ajax
 * *******************************/
add_action('init', 'gm_gallery_check_api');
function gm_gallery_check_api()
{
    $api_set = isset($_GET['gm_community_gallery']) ? true : false;

    if ($api_set === true)
    {
        require_once('submit/php/class.image_upload_process.php');

        new GM_community_gallery\submit\image_upload_process();

    }
}

add_action('init', 'gm_update_admin');
function gm_update_admin()
{
    $api_set = isset($_GET['gm_community_admin']) ? true : false;

    if ($api_set === true)
    {
        require_once('admin/php/class.image_update_process.php');

        new GM_community_gallery\admin\image_update_process();

    }
}

add_action('init', 'gm_admin_search');
function gm_admin_search()
{
    $api_set = isset($_GET['gm_gallery_admin_search']) ? true : false;

    if ($api_set === true)
    {
        require_once('admin/php/class.admin_search_process.php');

        new \GM_community_gallery\admin\admin_search_process();
    }
}

add_action('init', 'gm_admin_mass_action');
function gm_admin_mass_action()
{
    $api_set = isset($_GET['gm_community_mass_action']);

    if ($api_set === true)
    {
        require_once('admin/php/class.admin_mass_action.php');
        new \GM_community_gallery\admin\admin_mass_action();
    }
}
