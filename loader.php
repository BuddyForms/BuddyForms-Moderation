<?php
/*
 Plugin Name: BuddyForms Review
 Plugin URI: http://buddyforms.com/downloads/review/
 Description: Create new drafts or pending reviews from new or published posts without changing the live version.
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

add_action('init', 'bf_review_includes', 10);
function bf_review_includes(){
    include_once(dirname(__FILE__) . '/includes/buddyforms-review.php');
    include_once(dirname(__FILE__) . '/includes/form-elements.php');
}

add_action('plugins_loaded', 'bf_review_requirements', 9999);
function bf_review_requirements(){
    if( ! defined( 'BUDDYFORMS_VERSION' )){
        add_action( 'admin_notices', create_function( '', 'printf(\'<div id="message" class="error"><p><strong>\' . __(\'BuddyForms Review needs BuddyForms to be installed. <a target="_blank" href="%s">--> Get it now</a>!\', " wc4bp_xprofile" ) . \'</strong></p></div>\', "http://themekraft.com/store/wordpress-front-end-editor-and-form-builder-buddyforms/" );' ) );
        return;
    }
}

add_filter('bf_edit_post_link', 'bf_review_edit_post_link', 1, 2);

function bf_review_edit_post_link($edit_post_link, $post_id){
    global $buddyforms;

    $form_slug = get_post_meta($post_id ,'_bf_form_slug', true);

    $post_status = get_post_status($post_id);



    foreach($buddyforms[$form_slug]['form_fields'] as $key => $customfield ) {

        if ($customfield['type'] == 'review-logic') {

            if ($post_status == 'awaiting-review' && $customfield['review_logic'] != 'many_drafts' ) {
                $edit_post_link = 'Edit is Disabled during review';
            }

        }
    }

    return $edit_post_link;
}