<?php

/**
 * @package     GM-Community-Gallery
 * @author      Gabriel Mioni <gabriel@gabrielmioni.com>
 */

namespace GM_community_gallery\admin;

require_once(GM_GALLERY_DIR . 'admin/php/abstract.admin_gallery.php');

use GM_community_gallery\nav\navigate as navigate;

/**
 * Builds HTML for the admin gallery displayed at /wp-admin/admin.php?page=gm-community-submit
 *
 * The class accepts a navigate class object through the constructor. That class object is also shared with
 * the pagination class used to build pagination HTML.
 *
 * @package GM_community_gallery\admin
 */
class admin_gallery extends abstract_admin_gallery
{
    public function __construct(navigate $navigate)
    {
        parent::__construct($navigate);
    }

    /**
     * @return  string  Set the input that specifies the type of action needed for the mass update.
     */
    function create_action_input()
    {
        return "<input type='hidden' name='gm_mass_action_type' value='0'>";
    }

    /**
     * @return string   Set the submit button displayed for the admin gallery.
     */
    function create_submit_button()
    {
        return "<input class='button button-primary' value='Move selected to trash' id='gm_image_delete' name='gm_image_update' type='submit'>";
    }
}