<?php

/**
 * @package     GM-Community-Gallery
 * @author      Gabriel Mioni <gabriel@gabrielmioni.com>
 */

namespace GM_community_gallery\admin;

require_once(GM_GALLERY_DIR . 'admin/php/abstract.admin_gallery.php');

use GM_community_gallery\nav\navigate as navigate;

class admin_gallery_trash extends abstract_admin_gallery
{
    public function __construct(navigate $navigate)
    {
        parent::__construct($navigate);
    }

    function create_action_input()
    {
        return '<select name="gm_mass_action_type"><option>Select an option</option><option value="1">Move selected back to gallery.</option><option value="2">Delete selected.</option></select>';
    }

    /**
     * @return string   Set the submit button displayed for the admin gallery.
     */
    function create_submit_button()
    {
        return "<input class='button button-primary' value='Submit' name='gm_image_update' type='submit'>";
    }
}