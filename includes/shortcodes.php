<?php

//
// Create a shortcode to display the logged in collaborative user posts
//
function buddyforms_moderators_list_posts_to_moderate( $args ) {
	global $the_lp_query;

	ob_start();


	$user_posts = wp_get_object_terms( get_current_user_id(), 'buddyforms_moderators_posts', array( 'fields' => 'slugs' ) );

	print_r( $user_posts );


	if ( $user_posts ) {
		$the_lp_query = new WP_Query( array( 'post__in' => $user_posts, 'post_status' => 'awaiting-review', 'post_type' => 'any' ) );
		buddyforms_locate_template( 'the-loop' );
		wp_reset_postdata();
	} else {
		echo '<p>There are no collaborative posts for you to edit.</p>';
	}


	$tmp = ob_get_clean();

	return $tmp;
}

add_shortcode( 'buddyforms_list_posts_to_moderate', 'buddyforms_moderators_list_posts_to_moderate' );