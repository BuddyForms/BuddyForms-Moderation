<?php
/*
 Plugin Name: BuddyForms Moderation (moderation System)
 Plugin URI: http://buddyforms.com/downloads/moderation/
 Description: Create new drafts or pending moderations from new or published posts without changing the live version.
 Version: 1.0.2
 Author: Sven Lehnert
 Author URI: https://profiles.wordpress.org/svenl77
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

function bf_moderation_includes(){
    include_once(dirname(__FILE__) . '/includes/buddyforms-moderation.php');
    include_once(dirname(__FILE__) . '/includes/form-elements.php');
    include_once(dirname(__FILE__) . '/includes/functions.php');
}
add_action('init', 'bf_moderation_includes', 10);

function bf_moderation_requirements(){
    if( ! defined( 'BUDDYFORMS_VERSION' )){
        add_action( 'admin_notices', create_function( '', 'printf(\'<div id="message" class="error"><p><strong>\' . __(\'BuddyForms Moderation needs BuddyForms to be installed. <a target="_blank" href="%s">--> Get it now</a>!\', " wc4bp_xprofile" ) . \'</strong></p></div>\', "http://buddyforms.com/" );' ) );
        return;
    }
}
add_action('plugins_loaded', 'bf_moderation_requirements', 9999);