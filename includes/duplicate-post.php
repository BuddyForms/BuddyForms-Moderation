<?php

/*
 * Function creates post duplicate as a draft and redirects then to the edit post screen
 */
add_action( 'admin_action_buddyforms_moderation_duplicate_post', 'buddyforms_moderation_duplicate_post' );
function buddyforms_moderation_duplicate_post(){
	if(is_user_logged_in()){
		global $wpdb;
		if (! ( isset( $_GET['post_id']) || isset( $_POST['post_id'])  || ( isset($_REQUEST['action']) && 'buddyforms_moderation_duplicate_post' == $_REQUEST['action'] ) ) ) {
			wp_die('No post to duplicate has been supplied!');
		}

		/*
		 * get the original post id
		 */
		$post_id = (isset($_GET['post_id']) ? absint( $_GET['post_id'] ) : absint( $_POST['post_id'] ) );
		/*
		 * and all the original post data then
		 */
		$post = get_post( $post_id );

		/*
		 * if you don't want current user to be the new post author,
		 * then change next couple of lines to this: $new_post_author = $post->post_author;
		 */
		$current_user = wp_get_current_user();
		$new_post_author = $current_user->ID;

		/*
		 * if post data exists, create the post duplicate
		 */
		if (isset( $post ) && $post != null) {

			/*
			 * new post data array
			 */
			$args = array(
				'comment_status' => $post->comment_status,
				'ping_status'    => $post->ping_status,
				'post_author'    => $new_post_author,
				'post_content'   => $post->post_content,
				'post_excerpt'   => $post->post_excerpt,
				'post_name'      => $post->post_name,
				'post_parent'    => $post->ID,
				'post_password'  => $post->post_password,
				'post_status'    => 'edit-draft',
				'post_title'     => $post->post_title,
				'post_type'      => $post->post_type,
				'to_ping'        => $post->to_ping,
				'menu_order'     => $post->menu_order
			);

			/*
			 * insert the post by wp_insert_post() function
			 */
			$new_post_id = wp_insert_post( $args );

			/*
			 * get all current post terms ad set them to the new post draft
			 */
			$taxonomies = get_object_taxonomies($post->post_type); // returns array of taxonomy names for post type, ex array("category", "post_tag");
			foreach ($taxonomies as $taxonomy) {
				$post_terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'slugs'));
				wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
			}

			/* Get original post author and id info */
			$orig_author = get_post_meta($post_id, 'orig_author', true);
			$orig_post_id = get_post_meta($post_id, 'orig_post_id', true);
			if ( $orig_author == '') {
				$orig_author = $post->post_author;
				$orig_post_id = $post->ID;
			}
			/*
			 * duplicate all post meta just in two SQL queries
			 */
			$post_meta_infos = $wpdb->get_results("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$post_id");
			$post_meta_infos['orig_author'] = $orig_author;
			$post_meta_infos['orig_post_id'] = $orig_post_id;
			if (count($post_meta_infos)!=0) {
				$sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
				foreach ($post_meta_infos as $meta_info) {
					$meta_key = $meta_info->meta_key;
					$meta_value = addslashes($meta_info->meta_value);
					$sql_query_sel[]= "SELECT $new_post_id, '$meta_key', '$meta_value'";
				}
				$sql_query.= implode(" UNION ALL ", $sql_query_sel);
				$wpdb->query($sql_query);
			}

			/*
			 * finally, redirect to the new post page
			 */

			if ( defined('DOING_AJAX') && DOING_AJAX    ){
				$results = array(
					'error' => false,
					'error_msg' => '0',
					'redirect' => admin_url( 'post.php?action=edit&post=' . $new_post_id )
				);
				echo json_encode($results);
				die;
			} else {
				wp_redirect( admin_url( 'post.php?action=edit&post=' . $new_post_id ));
			}

		} else {
			if ( defined('DOING_AJAX') && DOING_AJAX ){
				$results = array(
					'error'     => true,
					'error_msg' => 'Post creation failed, could not find original post: ' . $post_id,
					'redirect'  => get_permalink()
				);
				echo json_encode( $results );
				die;
			} else {
				wp_redirect( get_permalink() );
			}
		}
	} else {
		$results = array(
			'error' => true,
			'error_msg' => 'You are not logged in.',
			'redirect' => wp_login_url( get_permalink() )
		);
		echo json_encode($results);
		die;
	}
}
add_action( 'wp_ajax_no_priv_buddyforms_moderation_duplicate_post', 'buddyforms_moderation_duplicate_post' );
add_action( 'wp_ajax_buddyforms_moderation_duplicate_post', 'buddyforms_moderation_duplicate_post' );

/*
 * Add the duplicate button in the page template
 */
function buddyforms_moderation_duplicate_post_button( $post_id ) {
	$link = '<a class="button" href="' . get_admin_url() . wp_nonce_url('admin.php?action=buddyforms_moderation_duplicate_post&post_id=' . $post_id, basename(__FILE__), 'duplicate_nonce' ) . '" title="' . __( 'Create new Edit Draft', 'buddyforms') . '" rel="permalink">' . __( 'Create new Edit Draft', 'buddyforms') . '</a>';
	echo $link;
}

/*
 * Add the duplicate link to action list for post_row_actions
 */
add_filter( 'page_row_actions', 'buddyforms_moderation_duplicate_post_link', 10, 2 );
function buddyforms_moderation_duplicate_post_link( $actions, $post ) {
	if (current_user_can('edit_pages')) {
		$actions['duplicate'] = '<a data-post_id="' . $post->ID . '" href="' . wp_nonce_url('admin.php?action=buddyforms_moderation_duplicate_post&post_id=' . $post->ID, basename(__FILE__), 'duplicate_nonce' ) . '" title="' . __( 'Create new Edit Draft', 'buddyforms') . '" rel="permalink">' . __( 'Create new Edit Draft', 'buddyforms') . '</a>';
	}
	return $actions;
}


/*
 * Add the duplicate link to admin bar
 */
add_action('admin_bar_menu', 'buddyforms_moderation_admin_bar_mod_button', 50);
function buddyforms_moderation_admin_bar_mod_button($wp_admin_bar){
	global $post;

	if( !$post || isset($post) && $post->post_status != 'publish' ){
		return;
	}

	$args = array(
		'id'    => 'buddyforms-admin-moderation',
		'title' => __( 'Create new Edit Draft', 'buddyforms'),
		'href'  => get_admin_url() . wp_nonce_url('admin.php?action=buddyforms_moderation_duplicate_post&post_id=' . $post->ID, basename(__FILE__), 'duplicate_nonce' ),
		'meta'  => array(
			'data-post_id' => $post->ID,
			'class' => 'buddyforms-admin-bar-moderation'
		)
	);
	$wp_admin_bar->add_node($args);
}