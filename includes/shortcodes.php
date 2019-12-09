<?php

//
// Create a shortcode to display the logged in collaborative user posts
//
function buddyforms_moderators_list_posts_to_moderate( $args ) {
	global $the_lp_query;
	$tmp = '';

	$bfmod_fs = bfmod_fs();
	if ( ! empty( $bfmod_fs ) && $bfmod_fs->is__premium_only() ) {
		if ( $bfmod_fs->is_plan( 'professional', true ) ) {
			ob_start();

			$user_posts = wp_get_object_terms( get_current_user_id(), 'buddyforms_moderators_posts', array( 'fields' => 'slugs' ) );

			$errormessage = "No posts to moderate at the moment.";

			if ( $user_posts ) {
				$the_lp_query = new WP_Query( array(
					'post__in'    => $user_posts,
					'post_status' => 'awaiting-review',
					'post_type'   => 'any'
				) );
				if ( $the_lp_query->have_posts() ) {
					buddyforms_locate_template( 'the-loop' );
				} else {
					echo '<p>' . $errormessage . '</p>';
				}
				wp_reset_postdata();
			} else {
				echo '<p>' . $errormessage . '</p>';
			}


			$tmp = ob_get_clean();
		}
	}

	return $tmp;
}

add_shortcode( 'buddyforms_list_posts_to_moderate', 'buddyforms_moderators_list_posts_to_moderate' );