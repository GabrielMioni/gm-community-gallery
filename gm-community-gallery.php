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
 * - Create the /uploads/gm-community-submit directory
 * ******************************************************/

function gm_gallery_create_directories()
{
    $wp_uploads_dir = wp_upload_dir();
    $wp_uploads_dir_base = $wp_uploads_dir['basedir'];
    $gm_directory = $wp_uploads_dir_base . '/gm-community-submit';
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
        "CREATE TABLE $tablename (
            `id` CHAR(6) PRIMARY KEY NOT NULL,
            `type` CHAR(4),
            `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `email` VARCHAR(100),
            `ip` VARCHAR(10),
            `name` VARCHAR(100),
            `title` VARCHAR(200),
            `message` VARCHAR(600),
            `comment` VARCHAR(600),
            `tags` VARCHAR(600),
            `trash` TINYINT(1) NOT NULL DEFAULT 0
        );";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    dbDelta($create_table);
}

/* *******************************
 * - Register CSS
 * *******************************/
add_action( 'wp_enqueue_scripts', 'gm_register_gallery_css' );
function gm_register_gallery_css()
{
    wp_register_style( 'gm-submit-css', plugins_url( '/css/gm-submit.css', __FILE__ ), array(), GM_GALLERY_VERSION, 'all' );
}

/* *******************************
 * - Contact Submit Form Shortcode
 * *******************************/

add_shortcode('gm-submit-form', 'gm_gallery_form_shortcode');
function gm_gallery_form_shortcode()
{
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

/* *******************************
 * - Settings Page
 * *******************************/

add_action( 'wp_enqueue_scripts', 'gm_register_gallery_css' );
function gm_register_settings_css()
{
    wp_enqueue_style( 'gm-gallery-settings-css', plugins_url( 'admin/css/gm-gallery-settings.css', __FILE__ ), array(), GM_GALLERY_VERSION, 'all' );
}

add_action( 'admin_menu', 'gm_community_gallery_menu' );
function gm_community_gallery_menu()
{
    $menu = add_menu_page( 'GM Community Gallery', 'GM Community Gallery', 'manage_options', 'gm-community-submit', 'gm_community_options' );

    add_action( 'admin_print_styles-' . $menu, 'gm_register_settings_css' );
}

function gm_community_options()
{
    if ( !current_user_can( 'manage_options' ) )
    {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }

    echo '<div class="wrap">';

    if ( isset( $_GET['edit'] ) )
    {
        require_once('admin/php/class.image_update_form.php');

        $build_form = new GM_community_gallery\admin\build_image_edit_form();
        echo $build_form->return_html_form();

    } else {

        require_once('admin/php/class.admin_gallery_build.php');

        $build_admin_gallery = new GM_community_gallery\admin\admin_gallery_build();
        echo $build_admin_gallery->return_admin_gallery_html();
    }

    echo '</div>';
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