<?php

/*
 * Update the original parent post
 *
 */

class BF_Moderation_Update_Post {

	public function __construct() {
		add_action( 'wp_insert_post_data', array( $this, 'modify_post_content' ), 99, 2 );
		add_action( 'init', array( $this, 'bf_moderation_post_status' ), 999 );
		add_action( 'post_submitbox_misc_actions', array( $this, 'bf_moderation_submitbox_misc_actions' ) );
		add_action( 'admin_footer-edit.php', array( $this, 'bf_moderation_append_to_inline_status_dropdown' ), 999 );
		add_filter( 'buddyforms_get_post_status_array', array( $this, 'bf_moderation_get_post_status_array' ), 10, 1 );
	}

	public function modify_post_content( $data, $postarr ) {
		global $buddyforms;
		$buddyforms_options = $buddyforms;

		$bf_form_slug = buddyforms_get_form_slug_by_post_id($postarr['ID']);

		if ( ! isset( $bf_form_slug ) ) {
			return $data;
		}

		if ( ! isset( $buddyforms_options[ $bf_form_slug ]['post_type'] ) ) {
			return $data;
		}

		if ( $data['post_type'] != $buddyforms_options[ $bf_form_slug ]['post_type'] ) {
			return $data;
		}


		if ( $data['post_status'] == 'publish' || $data['post_status'] == 'approved' ) {
			if ( $data['post_type'] == 'revision' ) {
				return $data;
			}

			if ( isset( $bf_form_slug ) && $data['post_parent'] != 0 ) {

				$data['post_status'] = 'approved';

				$update_post = array(
					'ID'             => $postarr['post_parent'],
					'post_title'     => $postarr['post_title'],
					'post_content'   => $postarr['post_content'],
					'post_type'      => $postarr['post_type'],
					'post_status'    => 'publish',
					'comment_status' => $postarr['comment_status'],
					'post_excerpt'   => $postarr['post_excerpt'],
				);

				$parent_post_id = wp_update_post( $update_post );

				if ( $parent_post_id ) {
					$this->bf_moderation_copy_post_taxonomies( $parent_post_id, $postarr['ID'] );
					$this->bf_moderation_copy_post_meta_info( $parent_post_id, $postarr['ID'] );

					$args = array(
						'post_type'      => $postarr['post_type'],
						'post_status'    => array( 'edit-draft', 'awaiting-review' ),
						'posts_per_page' => - 1,
						'post_parent'    => $postarr['post_parent'],
					);

					// Get all children
					$the_delete_query = new WP_Query( $args );

					// Check if children exits and move them to trash
					if ( $the_delete_query->have_posts() ) :

						while ( $the_delete_query->have_posts() ) : $the_delete_query->the_post();

							wp_delete_post( get_the_ID() );

						endwhile;
					endif;

					wp_reset_query();
				}

			} else {
				$data['post_status'] = 'publish';
			}
		}

		return $data;

	}

	/**
	 * Copy the taxonomies of a post to another post
	 *
	 * @param $parent_post_id
	 * @param $child_post_id
	 */
	function bf_moderation_copy_post_taxonomies( $parent_post_id, $child_post_id ) {
		global $wpdb;
		if ( isset( $wpdb->terms ) ) {
			// Clear default category (added by wp_insert_post)
			wp_set_object_terms( $parent_post_id, null, 'category' );

			$post = get_post( $child_post_id );

			$post_taxonomies = get_object_taxonomies( $post->post_type );

			foreach ( $post_taxonomies as $taxonomy ) {
				$post_terms = wp_get_object_terms( $post->ID, $taxonomy, array( 'orderby' => 'term_order' ) );
				$terms      = array();
				for ( $i = 0; $i < count( $post_terms ); $i ++ ) {
					$terms[] = $post_terms[ $i ]->slug;
				}
				wp_set_object_terms( $parent_post_id, $terms, $taxonomy );
			}
		}
	}

	/**
	 * Copy the meta information of a post to another post
	 *
	 * @param $parent_post_id
	 * @param $child_post_id
	 *
	 * @internal param $new_id
	 * @internal param $post
	 */
	function bf_moderation_copy_post_meta_info( $parent_post_id, $child_post_id ) {
		$post_meta_keys = get_post_custom_keys( $child_post_id );
		if ( empty( $post_meta_keys ) ) {
			return;
		}

		foreach ( $post_meta_keys as $meta_key ) {
			$meta_values = get_post_custom_values( $meta_key, $child_post_id );
			if( is_array( $meta_values ) ){
				foreach ( $meta_values as $meta_value ) {
					$meta_value = maybe_unserialize( $meta_value );
					update_post_meta( $parent_post_id, $meta_key, $meta_value );
				}
			}
		}
	}


	function bf_moderation_post_status() {

		$args = array(
			'label'                     => _x( 'Edit Draft', 'Edit Draft', 'buddyforms' ),
			'label_count'               => _n_noop( 'Edit Draft (%s)', 'Edit Draft (%s)', 'buddyforms' ),
			'public'                    => false,
			'show_in_admin_all_list'    => false,
			'show_in_admin_status_list' => true,
			'exclude_from_search'       => true,
			'protected'                 => true,
		);
		register_post_status( 'edit-draft', $args );

		$args = array(
			'label'                     => _x( 'Awaiting moderation', 'Awaiting moderation', 'buddyforms' ),
			'label_count'               => _n_noop( 'Awaiting moderation (%s)', 'Awaiting moderation (%s)', 'buddyforms' ),
			'public'                    => false,
			'show_in_admin_all_list'    => false,
			'show_in_admin_status_list' => true,
			'exclude_from_search'       => true,
			'protected'                 => true,
		);
		register_post_status( 'awaiting-review', $args );

		$args = array(
			'label'                     => _x( 'Approved', 'Approved', 'buddyforms' ),
			'label_count'               => _n_noop( 'Approved (%s)', 'Approved (%s)', 'buddyforms' ),
			'public'                    => false,
			'show_in_admin_all_list'    => false,
			'show_in_admin_status_list' => true,
			'exclude_from_search'       => true,
			'protected'                 => true,
		);
		register_post_status( 'approved', $args );

	}

	function bf_moderation_submitbox_misc_actions() {
		global $post, $buddyforms;

		$buddyforms_options = $buddyforms;

		$bf_form_slug =  buddyforms_get_form_slug_by_post_id( $post->ID );

		if ( ! isset( $bf_form_slug ) ) {
			return;
		}

		if ( ! isset( $buddyforms_options[ $bf_form_slug ]['post_type'] ) ) {
			return;
		}

		if ( $post->post_type != $buddyforms_options[ $bf_form_slug ]['post_type'] ) {
			return;
		}

		$complete = '';
		$label    = '';

		echo '<script>';
		echo ' jQuery(document).ready(function($){';
		if ( $post->post_status == 'edit-draft' ) {
			$complete = ' selected=\"selected\"';
			$label    = '<span id=\"post-status-display\"> Edit Draft</span>';
		}
		echo '$("select#post_status").append("<option value=\"' . $post->post_status . '\" ' . $complete . '>Edit Draft</option>");
            $(".misc-pub-section label").append("' . $label . '");';
		$complete = '';
		$label    = '';
		if ( $post->post_status == 'awaiting-review' ) {
			$complete = ' selected=\"selected\"';
			$label    = '<span id=\"post-status-display\"> Awaiting moderation</span>';
		}
		echo '$("select#post_status").append("<option value=\"' . $post->post_status . '\" ' . $complete . '>Awaiting moderation</option>");
            $(".misc-pub-section label").append("' . $label . '");';
		$complete = '';
		$label    = '';
		if ( $post->post_status == 'approved' ) {
			$complete = ' selected=\"selected\"';
			$label    = '<span id=\"post-status-display\"> Approved</span>';
		}
		echo '$("select#post_status").append("<option value=\"' . $post->post_status . '\" ' . $complete . '>Approved</option>");
            $(".misc-pub-section label").append("' . $label . '");';

		echo ' });</script>';

	}


	/**
	 * Append the custom post type to the post status
	 * dropdown in the quick edit area on the post
	 * listing page.
	 * @return null
	 */
	function bf_moderation_append_to_inline_status_dropdown() {
		global $post, $buddyforms;

		if ( ! $post ) {
			return;
		}

		$buddyforms_options = $buddyforms;

		$bf_form_slug = buddyforms_get_form_slug_by_post_id( $post->ID );

		if ( ! isset( $bf_form_slug ) ) {
			return;
		}

		if ( ! isset( $buddyforms_options[ $bf_form_slug ]['post_type'] ) ) {
			return;
		}

		if ( $post->post_type != $buddyforms_options[ $bf_form_slug ]['post_type'] ) {
			return;
		}

		echo "
        <script>
        jQuery(document).ready(function ($){
            jQuery('.inline-edit-status select').append('<option value=\"edit-draft\">Edit Draft</option>' +
             '<option value=\"awaiting-review\">Awaiting moderation</option>' +
             '<option value=\"approved\">Approved</option>');
        });
        </script>
        ";

	}

	function bf_moderation_get_post_status_array( $status_array ) {
		$status_array['edit-draft']      = 'Edit Draft';
		$status_array['awaiting-review'] = 'Awaiting moderation';
		$status_array['approved']        = 'Approved';

		return $status_array;
	}

}

new BF_Moderation_Update_Post;
