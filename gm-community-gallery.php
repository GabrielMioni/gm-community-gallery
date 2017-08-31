<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);
/*
Plugin Name: GM Community Gallery
Version: 1.0
Plugin URI: http://gabrielmioni.com/gm-community-gallery
Description: Automated accept image files and add them to a WordPress gallery
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
    gm_gallery_create_directories();
    gm_gallery_create_sql_db();
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
        "CREATE TABLE $tablename (
            id CHAR(6) PRIMARY KEY NOT NULL,
            type CHAR(4),
            email VARCHAR(100),
            ip VARCHAR(10),
            name VARCHAR(100),
            title VARCHAR(200),
            message VARCHAR(600)
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
    wp_register_style( 'gm-gallery-css', plugins_url( '/css/gm-gallery.css', __FILE__ ), array(), GM_GALLERY_VERSION, 'all' );
}

/* *******************************
 * - Contact Submit Form Shortcode
 * *******************************/

add_shortcode('gm-gallery-form', 'gm_gallery_form_shortcode');
function gm_gallery_form_shortcode()
{
    require_once('gm-community-gallery-html.php');

    // Add the gm-contact.css file
    wp_enqueue_style('gm-gallery-css');

    $auto_p_flag = false;

    // If wpautop filter is set, temporarily disable it.
    if ( has_filter( 'the_content', 'wpautop' ) )
    {
        $auto_p_flag = true;
        remove_filter( 'the_content', 'wpautop' );
        remove_filter( 'the_excerpt', 'wpautop' );
    }

    $build_html = new gm_community_gallery_html();
    echo $build_html->return_html();

    // If wpautop had been set previously, re-enable it.
    if ($auto_p_flag === true)
    {
        add_filter( 'the_content', 'wpautop' );
        add_filter( 'the_excerpt', 'wpautop' );
    }
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
        require_once(dirname(__FILE__) . '/gm-community-gallery-submit.php');
        new gm_community_gallery_submit();
    }
}