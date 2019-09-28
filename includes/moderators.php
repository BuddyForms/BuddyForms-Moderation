<?php


/*
 * Add CPUBLISHING form elementrs in the form elements select box
 */
add_filter( 'buddyforms_add_form_element_select_option', 'buddyforms_moderators_select', 1, 2 );
function buddyforms_moderators_select( $elements_select_options ) {
	global $post;

	if ( $post->post_type != 'buddyforms' ) {
		return;
	}
	$elements_select_options['moderators']['label']                = 'Colaburative Publishing';
	$elements_select_options['moderators']['class']                = 'bf_show_if_f_type_post';
	$elements_select_options['moderators']['fields']['moderators'] = array(
		'label' => __( 'Select Moderators ', 'buddyforms' ),
	);

	return $elements_select_options;
}

/*
 * Create the new CPUBLISHING Form Builder Form Elements
 *
 */
add_filter( 'buddyforms_form_element_add_field', 'buddyforms_moderators_form_builder_form_elements', 1, 5 );
function buddyforms_moderators_form_builder_form_elements( $form_fields, $form_slug, $field_type, $field_id ) {
	global $field_position, $buddyforms;


	switch ( $field_type ) {
		case 'moderators':

			//unset( $form_fields );

			$roles = get_editable_roles();

			$roles_array = array( 'all' => 'All Roles' );
			foreach ( $roles as $role_kay => $role ) {
				$roles_array[ $role_kay ] = $role['name'];
			}


			$moderators = 'false';
			if ( isset( $buddyforms[ $form_slug ]['form_fields'][ $field_id ]['moderators'] ) ) {
				$moderators = $buddyforms[ $form_slug ]['form_fields'][ $field_id ]['moderators'];
			}
			$form_fields['general']['moderators'] = new Element_Select( '<b>' . __( 'Moderators', 'buddyforms' ) . '</b>', "buddyforms_options[form_fields][" . $field_id . "][moderators]", $roles_array, array(
				'value'         => $moderators,
				'data-field_id' => $field_id,
				'shortDesc'     => 'You can enable all users or filter the select for a specific user role'
			) );
//			$multiple_editors                                    = isset( $buddyforms[ $form_slug ]['form_fields'][ $field_id ]['multiple_editors'] ) ? $buddyforms[ $form_slug ]['form_fields'][ $field_id ]['multiple_editors'] : 'false';
//			$form_fields['general']['multiple_editors']          = new Element_Checkbox( '<b>' . __( 'Multiple Editors', 'buddyforms' ) . '</b>', "buddyforms_options[form_fields][" . $field_id . "][multiple_editors]", array( 'multiple_editors' => '<b>' . __( 'Multiple Editors', 'buddyforms' ) . '</b>' ), array( 'value' => $multiple_editors ) );
			$moderators_label                           = isset( $buddyforms[ $form_slug ]['form_fields'][ $field_id ]['moderators_label'] ) ? stripcslashes( $buddyforms[ $form_slug ]['form_fields'][ $field_id ]['moderators_label'] ) : __( 'Select Moderators', 'buddyforms' );
			$form_fields['general']['moderators_label'] = new Element_Textbox( '<b>' . __( 'Label', 'buddyforms' ) . '</b>', "buddyforms_options[form_fields][" . $field_id . "][moderators_label]", array(
				'data'      => $field_id,
				'value'     => $moderators_label,
				'shortDesc' => ''
			) );

			break;
	}

	return $form_fields;
}

/*
 * Display the new CPUBLISHING Fields in the frontend form
 *
 */
add_filter( 'buddyforms_create_edit_form_display_element', 'buddyforms_moderators_frontend_form_elements', 1, 2 );
function buddyforms_moderators_frontend_form_elements( $form, $form_args ) {
	global $buddyforms, $nonce;

	extract( $form_args );

	$post_type = $buddyforms[ $form_slug ]['post_type'];

	if ( ! $post_type ) {
		return $form;
	}

	if ( ! isset( $customfield['type'] ) ) {
		return $form;
	}

	switch ( $customfield['type'] ) {
		case 'moderators':

//			$post_editors = wp_get_object_terms( $post_id, 'buddyforms_editors' );
//			$user_posts = wp_get_object_terms( get_current_user_id(), 'buddyforms_user_posts' );

//			ob_start();
//			echo 'Post Editors:<pre>';
//			print_r( $post_editors );
//			echo '</pre>';

//			echo '<br>User Posts<pre>';
//			print_r( $user_posts );
//			echo '</pre>';
//			$JHG = ob_get_clean();

//			$form->addElement( new Element_HTML( $JHG ) );


			if ( $customfield['moderators'] == 'all' ) {
				$blogusers = get_users();
			} else {
				$blogusers = get_users( array(
					'role' => $customfield['moderators']
				) );
			}
			// Array of WP_User objects.

			$options['none'] = __( 'Select an Editor' );

			foreach ( $blogusers as $user ) {
				$options[ $user->ID ] = $user->user_nicename;
			}


			$element_attr['data-reset'] = 'true';


			$label = __( 'Select Editors', 'buddyforms' );
			if ( isset ( $customfield['moderators_label'] ) ) {
				$label = $customfield['moderators_label'];
			}

			$element_attr['class'] = $element_attr['class'] . ' bf-select2';
			$element_attr['value'] = get_post_meta( $post_id, 'buddyforms_moderators', true );
			$element_attr['id']    = 'col-lab-moderators';


			$element = new Element_Select( $label, 'buddyforms_moderators', $options, $element_attr );

			//if ( isset( $customfield['multiple_editors'] ) && is_array( $customfield['multiple_editors'] ) ) {
			$element->setAttribute( 'multiple', 'multiple' );
			//}

			BuddyFormsAssets::load_select2_assets();

			$form->addElement( $element );


			break;

	}

	return $form;
}


/*
 * Save Fields
 *
 */
add_action( 'buddyforms_update_post_meta', 'buddyforms_moderators_update_post_meta', 10, 2 );
function buddyforms_moderators_update_post_meta( $customfield, $post_id ) {

	$global_error = ErrorHandler::get_instance();

	if ( $customfield['type'] == 'moderators' ) {

		$form_slug = get_post_meta( $post_id, '_bf_form_slug' );

		$global_error->add_error( new BF_Error( 'buddyforms_form_' . $form_slug, 'Just a test', '', $form_slug ) );


		// Create a editors array to store all editors.
		$moderators     = array();
		$old_moderators = get_post_meta( $post_id, 'buddyforms_moderators', true );

		// Update the editors post meta
		if ( ! empty( $_POST['buddyforms_moderators'] ) ) {
			update_post_meta( $post_id, 'buddyforms_moderators', $_POST['buddyforms_moderators'] );

			// Update the editors array
			foreach ( $_POST['buddyforms_moderators'] as $key => $moderator ) {
				$moderators[ $moderator ] = $moderator;
			}
		}


		// Loop through the old moderators and remove them from the buddyforms_moderators_posts taxonomy
		foreach ( $old_moderators as $post_moderator ) {
			if ( ! array_key_exists( $post_moderator, $moderators ) ) {
				wp_remove_object_terms( $post_moderator, strval( $post_id ), 'buddyforms_moderators_posts', true );
			}
		}

		// Loop thru all moderators and add the post to the buddyforms_moderators_posts taxonomy
		foreach ( $moderators as $moderators_id ) {
			$moderator_posts = wp_set_object_terms( $moderators_id, strval( $post_id ), 'buddyforms_moderators_posts', true );
		}

	}
}


add_filter( 'buddyforms_form_custom_validation', 'buddyforms_moderators_server_validation', 2, 2 );

function buddyforms_moderators_server_validation( $valid, $form_slug ) {
	global $buddyforms;

	$form = $buddyforms[ $form_slug ];

	if ( isset( $form['form_fields'] ) ) {
		$global_error = ErrorHandler::get_instance();
		foreach ( $form['form_fields'] as $key => $form_field ) {


			// Here I like to ask for the post status

			if ( isset( $_POST['status'] ) && $_POST['status'] == 'awaiting-review' ) {
				if ( $form_field['type'] == 'moderators' ) {

					if ( ! isset( $_POST['buddyforms_moderators'] ) ) {
						$valid                    = false;
						$validation_error_message = __( 'Please select a Moderator!', 'buddyforms' ) . $form_field['validation_min'];
						$global_error->add_error( new BF_Error( 'buddyforms_form_' . $form_slug, $validation_error_message, $form_field['name'] ) );

					}

				}
			}


		}

	}

	return $valid;
}


add_action( 'buddyforms_the_loop_after_actions', 'buddyforms_moderators_the_loop_actions' );
function buddyforms_moderators_the_loop_actions( $post_id ) {

	$user_posts = wp_get_object_terms( get_current_user_id(), 'buddyforms_moderators_posts', array( 'fields' => 'slugs' ) );
	$form_slug  = get_post_meta( $post_id, '_bf_form_slug', true );

	if ( in_array( $post_id, $user_posts ) ) {
		echo '<ul class="edit_links">';
		echo '<li>';
		echo '<a title="' . __( 'Approve', 'buddyforms' ) . '"  id="' . $post_id . '" class="buddyforms_moderators_approve" href="#"><span aria-label="' . __( 'Approve', 'buddyforms' ) . '" title="' . __( 'Approve', 'buddyforms' ) . '" class="dashicons dashicons-trash"> </span> ' . __( 'Approve', 'buddyforms' ) . '</a></li>';
		echo '</li>';
		echo '<li>';
		buddyforms_moderators_reject_post( $post_id, $form_slug );
		echo '</li>';
		echo '</ul>';

	}

}

add_action( 'wp_ajax_buddyforms_moderators_ajax_approve_post', 'buddyforms_moderators_ajax_approve_post' );
function buddyforms_moderators_ajax_approve_post() {
	global $current_user, $buddyforms;
	$current_user = wp_get_current_user();

	$post_id  = intval( $_POST['post_id'] );
	$the_post = get_post( $post_id );

	$form_slug = get_post_meta( $post_id, '_bf_form_slug', true );
	if ( ! $form_slug ) {
		_e( 'You are not allowed to delete this entry! What are you doing here?', 'buddyforms' );
		die();
	}

	$approve = wp_update_post( array(
		'ID'          => $post_id,
		'post_status' => 'approved',
	) );


	// Remove the post from the user posts taxonomy
	wp_remove_object_terms( get_current_user_id(), strval( $post_id ), 'buddyforms_moderators_posts', true );

	// Remove the user from the post editors
	wp_remove_object_terms( $post_id, strval( get_current_user_id() ), 'buddyforms_moderators', true );


	die();
}