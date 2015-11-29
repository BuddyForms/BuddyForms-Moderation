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
    $post_type = get_post_type($post_id);


    foreach($buddyforms[$form_slug]['form_fields'] as $key => $customfield ) {

        if ($customfield['type'] == 'review-logic') {

            if ($post_status == 'publish' && $customfield['review_logic'] != 'many_drafts' ) {
                $args = array(
                    'post_type' => $post_type,
                    'form_slug' => $form_slug,
                    'post_status' => array('edit-draft', 'awaiting-review'),
                    'posts_per_page' => -1,
                    'post_parent' => $post_id,
                    'author' => get_current_user_id()
                );

                $post_parent = new WP_Query($args);

                if ($post_parent->have_posts()) {
                    $edit_post_link = __('New Version in Process', 'buddyforms');
                }
            }

            if ($post_status == 'awaiting-review' && $customfield['review_logic'] != 'many_drafts' ) {
                $edit_post_link = __('Edit is Disabled during Review', 'buddyforms');
            }

        }
    }

    return $edit_post_link;
}

add_action('buddyforms_the_loop_li_last', 'bf_buddyforms_the_loop_li_last');

function bf_buddyforms_the_loop_li_last($post_id){
    global $buddyforms, $the_lp_query;

    $post_parent = $post_id;
    $form_slug = get_post_meta($post_parent, '_bf_form_slug', true);
    $post_type = $buddyforms[$form_slug]['post_type'];

    $args = array(
        'post_type'			=> $post_type,
        'form_slug'         => $form_slug,
        'post_status'		=> array('edit-draft', 'awaiting-review'),
        'posts_per_page'	=> -1,
        'post_parent'		=> $post_parent,
        'author'			=> get_current_user_id()
    );


    $the_lp_query_old = $the_lp_query;

    $the_lp_query = new WP_Query( $args );

	get_currentuserinfo(); ?>

        <?php if ( $the_lp_query->have_posts() ) : ?>

            <ul class="buddyforms-list_sub" role="sub">

                <?php while ( $the_lp_query->have_posts() ) : $the_lp_query->the_post();

                    $the_permalink = get_permalink();
                    $post_status = get_post_status();

                    $post_status_css =  $post_status_name  = $post_status;

                    if( $post_status == 'pending')
                        $post_status_css = 'bf-pending';

                    if( $post_status == 'publish')
                        $post_status_name = 'published';


                    $post_status_css = apply_filters('bf_post_status_css',$post_status_css,$form_slug);

                    do_action( 'bp_before_blog_post' ) ?>

                    <li id="bf_post_li_<?php the_ID() ?>" class="<?php echo $post_status_css; ?>">
                        <div class="item-avatar">

                            <?php
                            $post_thumbnail = get_the_post_thumbnail( get_the_ID(), array(70,70),array('class'=>"avatar"));
                            $post_thumbnail = apply_filters( 'buddyforms_loop_thumbnail', $post_thumbnail);
                            ?>

                            <a href="<?php echo $the_permalink; ?>"><?php echo $post_thumbnail ?></a>
                        </div>

                        <div class="item">
                            <div class="item-title"><a href="<?php echo $the_permalink; ?>" rel="bookmark" title="<?php _e( 'Permanent Link to', 'buddyforms' ) ?> <?php the_title_attribute(); ?>"><?php the_title(); ?></a></div>

                            <div class="item-desc"><?php echo get_the_excerpt(); ?></div>

                        </div>

                        <div class="action">
                            <?php _e( 'Created', 'buddyforms' ); ?> <?php the_time('F j, Y') ?>


                            <?php
                            if (get_the_author_meta('ID') ==  get_current_user_id()){
                                $permalink = get_permalink( $buddyforms[$form_slug]['attached_page'] ); ?>

                                <div class="meta">
                                    <div class="item-status"><?php echo $post_status_name; ?></div>
                                    <?php
                                    if( current_user_can('buddyforms_'.$form_slug.'_edit') ) {

                                        if(isset($buddyforms[$form_slug]['edit_link']) && $buddyforms[$form_slug]['edit_link'] != 'none') {
                                            echo apply_filters( 'bf_edit_post_link','<a title="Edit" id="' . get_the_ID() . '" class="bf_edit_post" href="' . $permalink . 'edit/' . $form_slug. '/' .get_the_ID() . '">' . __( 'Edit', 'buddyforms' ) .'</a>', get_the_ID());
                                        } else {
                                            echo apply_filters( 'bf_edit_post_link', bf_edit_post_link('Edit'), get_the_ID() );
                                        }

                                    }
                                    if( current_user_can('buddyforms_'.$form_slug.'_delete') ) {
                                        echo ' - <a title="Delete"  id="' . get_the_ID() . '" class="bf_delete_post" href="#">' . __( 'Delete', 'buddyforms' ) . '</a>';
                                    }
                                    do_action('buddyforms_the_loop_actions', get_the_ID())
                                    ?>
                                </div>
                            <?php } ?>

                        </div>
                        <?php do_action('buddyforms_the_loop_li_last', get_the_ID()); ?>
                        <div class="clear"></div>
                    </li>

                    <?php do_action( 'bf_after_loop_item' ) ?>


                <?php endwhile; ?>

                <div class="navigation">
                    <?php if(function_exists('wp_pagenavi')) : wp_pagenavi(); else: ?>
                        <div class="alignleft"><?php next_posts_link( '&larr;' . __( ' Previous Entries', 'buddyforms' ), $the_lp_query->max_num_pages ) ?></div>
                        <div class="alignright"><?php previous_posts_link( __( 'Next Entries ', 'buddyforms' ) . '&rarr;' ) ?></div>
                    <?php endif; ?>

                </div>

            </ul>

        <?php endif; ?>

    <?php

wp_reset_query();

    $the_lp_query = $the_lp_query_old;

}