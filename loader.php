<?php
/*
 Plugin Name: BuddyForms Review
 Plugin URI: http://themekraft.com/store/wordpress-front-end-editor-and-form-builder-buddyforms/
 Description: Create new drafts or pending reviews from new or published posts without changing the live version.
 Version: 1.0.1
 Author: Sven Lehnert
 Author URI: http://themekraft.com/members/svenl77/
 License: GPLv2 or later
 Network: false

 *****************************************************************************
 *
 * This script is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 ****************************************************************************
 */

add_action('init', 'bf_review_includes', 10);
function bf_review_includes(){
    include_once(dirname(__FILE__) . '/includes/buddyforms-review.php');
    include_once(dirname(__FILE__) . '/includes/form-elements.php');
}

add_action('plugins_loaded', 'bf_review_requirements');
function bf_review_requirements(){
    if( ! defined( 'buddyforms' )){
        add_action( 'admin_notices', create_function( '', 'printf(\'<div id="message" class="error"><p><strong>\' . __(\'BuddyForms Review needs BuddyForms to be installed. <a target="_blank" href="%s">--> Get it now</a>!\', " wc4bp_xprofile" ) . \'</strong></p></div>\', "http://themekraft.com/store/wordpress-front-end-editor-and-form-builder-buddyforms/" );' ) );
        return;
    }
}