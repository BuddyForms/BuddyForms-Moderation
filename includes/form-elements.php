<?php


function buddyforms_moderation_admin_settings_sidebar_metabox() {
	add_meta_box( 'buddyforms_moderation', __( "Moderation", 'buddyforms' ), 'buddyforms_moderation_admin_settings_sidebar_metabox_html', 'buddyforms', 'normal', 'low' );
	add_filter('postbox_classes_buddyforms_buddyforms_moderation','buddyforms_metabox_class');
	add_filter('postbox_classes_buddyforms_buddyforms_moderation','buddyforms_metabox_show_if_form_type_post');
	add_filter('postbox_classes_buddyforms_buddyforms_moderation','buddyforms_metabox_show_if_post_type_none');
}

function buddyforms_moderation_admin_settings_sidebar_metabox_html() {
	global $post, $buddyforms;

	if ( $post->post_type != 'buddyforms' ) {
		return;
	}

	$buddyform = get_post_meta( get_the_ID(), '_buddyforms_options', true );

	$form_setup = array();

	$moderation_logic = isset( $buddyform['moderation_logic'] ) ? $buddyform['moderation_logic'] : 'default';

	$form_setup[] = new Element_Radio(
		'<b>' . __( 'Moderation Logic', 'buddyforms' ) . '</b>',
		"buddyforms_options[moderation_logic]",
		Array(
			'default'      => 'Moderation is disabled<br>',
			'one_draft'    => 'Users can create, save and edit a draft until it is submitted for moderation. Once submitted, changes cannot be made until the post is approved. However, the user can delete the submitted post before it is approved.<br>',
			'hidden_draft' => 'Users can only submit a post for moderation. Drafts cannot be saved.<br>',
			'many_drafts'  => 'Users can create as many drafts as they like and submit them for moderation. When a post is approved, all related older posts that are awaiting review get deleted. This could result in the post appearing multiple times in Edit Draft or Awaiting Moderation.<br>'
		)
		,
		array(
			'value' => $moderation_logic,
			// 'shortDesc'  => 'If a post is created or edited and the moderation logic is enabled the post is saved with post status edit-draft.
			//         If a post is submit for moderation the post status is set to awaiting-approval'
		)
	);

	$label_submit                        = isset( $buddyform['moderation']['label_submit'] ) ? $buddyform['moderation']['label_submit'] : 'Submit';
	$form_setup[] = new Element_Textbox( '<b>' . __( 'Label for Submit Button', 'buddyforms' ) . '</b>', "buddyforms_options[moderation][label_submit]", array( 'value' => $label_submit ) );

	$label_save                        = isset( $buddyform['moderation']['label_save'] ) ? $buddyform['moderation']['label_save'] : 'Save';
	$form_setup[] = new Element_Textbox( '<b>' . __( 'Label for Save Button', 'buddyforms' ) . '</b>', "buddyforms_options[moderation][label_save]", array( 'value' => $label_save ) );

	$label_review                        = isset( $buddyform['moderation']['label_review'] ) ? $buddyform['moderation']['label_review'] : 'Submit for moderation';
	$form_setup[] = new Element_Textbox( '<b>' . __( 'Label for Submit for moderation Button', 'buddyforms' ) . '</b>', "buddyforms_options[moderation][label_review]", array( 'value' => $label_review ) );

	$label_new_draft                        = isset( $buddyform['moderation']['label_new_draft'] ) ? $buddyform['moderation']['label_new_draft'] : 'Create new Draft';
	$form_setup[] = new Element_Textbox( '<b>' . __( 'Label for Create new Draft Button', 'buddyforms' ) . '</b>', "buddyforms_options[moderation][label_new_draft]", array( 'value' => $label_new_draft ) );

	$label_no_edit                        = isset( $buddyform['moderation']['label_no_edit'] ) ? $buddyform['moderation']['label_no_edit'] : 'This Post is waiting for approval and can not be changed until it gets approved';
	$form_setup[] = new Element_Textarea( '<b>' . __( 'If the form is displayed but edeting is disabled', 'buddyforms' ) . '</b>', "buddyforms_options[moderation][label_no_edit]", array( 'value' => $label_no_edit ) );

	if ( ! isset( $field_id ) ) {
		$field_id = $mod5 = substr( md5( time() * rand() ), 0, 10 );
	}

	?>

	<?php buddyforms_display_field_group_table( $form_setup ) ?>

	<?php

}
add_filter( 'add_meta_boxes', 'buddyforms_moderation_admin_settings_sidebar_metabox' );

/*
 * Display the new Form Element in the Frontend Form
 *
 */
function bf_moderation_create_frontend_form_element( $form, $form_slug, $post_id ) {
	global $buddyforms, $bf_submit_button;

	if ( ! isset( $buddyforms[ $form_slug ]['moderation_logic'] ) || $buddyforms[ $form_slug ]['moderation_logic'] == 'default' ) {
		return $form;
	}

	$bf_submit_button = false;
	$label_moderation = new Element_Button( __( $buddyforms[ $form_slug ]['moderation']['label_review'], 'buddyforms' ), 'submit', array(
		'class' => 'bf-submit',
		'name'  => 'awaiting-review'
	) );
	$label_submit     = new Element_Button( __( $buddyforms[ $form_slug ]['moderation']['label_submit'], 'buddyforms' ), 'submit', array(
		'class' => 'bf-submit',
		'name'  => 'edit-draft'
	) );
	$label_save       = new Element_Button( __( $buddyforms[ $form_slug ]['moderation']['label_save'], 'buddyforms' ), 'submit', array(
		'class' => 'bf-submit',
		'name'  => 'edit-draft'
	) );
	$label_new_draft  = new Element_Button( __( $buddyforms[ $form_slug ]['moderation']['label_new_draft'], 'buddyforms' ), 'submit', array(
		'class' => 'bf-submit',
		'name'  => 'new-draft'
	) );
	$label_no_edit    = new Element_HTML( '<p>' . __( $buddyforms[ $form_slug ]['moderation']['label_no_edit'], 'buddyforms' ) . '</p>' );


	//	Set the post status to edit-draft if edit screen is displayed. This will make sure we never save public post
//	$status = new Element_Hidden( 'status', 'edit-draft' );

	// If post_id is 0 we have a new posts
	if ( $post_id == 0 ) {

		if ( $buddyforms[ $form_slug ]['moderation_logic'] == 'hidden_draft' ) {
			$form->addElement( $label_moderation );
		} else {
			$form->addElement( $label_submit );
		}

	} else {

		// This is an existing post
		$post_status = get_post_status( $post_id ); // Get the Posts
		$post_type = get_post_type( $post_id ); // Get the Posts

		// Check Post Status
		if ( $post_status == 'edit-draft' || ($post_status == 'auto-draft' && $post_type == 'product' )|| $post_status == 'draft' || $post_status == 'submitted'  ) {
			$form->addElement( $label_save );
			$form->addElement( $label_moderation );
		}
		if ( $post_status == 'awaiting-review' ) {
			if ( $buddyforms[ $form_slug ]['moderation_logic'] != 'many_drafts' ) {
				$form->addElement( $label_no_edit );
			} else {
				$form->addElement( $label_new_draft );
			}
		}
		if ( $post_status == 'publish' ) {
			$form->addElement( $label_new_draft );
		}
	}
//	$form->addElement( $status );

	return $form;
}
add_filter( 'buddyforms_create_edit_form_button', 'bf_moderation_create_frontend_form_element', 9999, 3 );


function bf_moderation_buddyforms_create_edit_form_button( $form_button ) {
	return false;
}

function buddyforms_moderation_ajax_process_edit_post_json_response( $json_args ) {
	global $buddyforms;

	if ( isset( $json_args ) ) {
		extract( $json_args );
	}

	if ( isset( $post_id ) && $post_id != 0 ) {
		$post = get_post( $post_id );
	} else {
		$post_id = 0;
	}

	if ( ! isset( $_POST['form_slug'] ) ) {
		return $json_args;
	}

	$form_slug = $_POST['form_slug'];

	if ( ! isset( $buddyforms[ $form_slug ]['moderation_logic'] ) || $buddyforms[ $form_slug ]['moderation_logic'] == 'default' ) {
		return $json_args;
	}

	$label_moderation = new Element_Button( __( $buddyforms[ $form_slug ]['moderation']['label_review'], 'buddyforms' ), 'submit', array(
		'class' => 'bf-submit',
		'name'  => 'awaiting-review'
	) );
	$label_submit     = new Element_Button( __( $buddyforms[ $form_slug ]['moderation']['label_submit'], 'buddyforms' ), 'submit', array(
		'class' => 'bf-submit',
		'name'  => 'edit-draft'
	) );
	$label_save       = new Element_Button( __( $buddyforms[ $form_slug ]['moderation']['label_save'], 'buddyforms' ), 'submit', array(
		'class' => 'bf-submit',
		'name'  => 'edit-draft'
	) );
	$label_new_draft  = new Element_Button( __( $buddyforms[ $form_slug ]['moderation']['label_new_draft'], 'buddyforms' ), 'submit', array(
		'class' => 'bf-submit',
		'name'  => 'new-draft'
	) );
	$label_no_edit    = new Element_HTML( '<p>' . __( $buddyforms[ $form_slug ]['moderation']['label_no_edit'], 'buddyforms' ) . '</p>' );

	// Set the post status to edit-draft if edit screen is displayed. This will make sure we never save public post
	// $status = new Element_Hidden( "status", 'edit-draft' );

	// If post_id is 0 we have a new posts
	if ( $post_id == 0 ) {

		if ( $buddyforms[ $form_slug ]['moderation_logic'] == 'hidden_draft' ) {
			$formelements[] = $label_moderation;
		} else {
			$formelements[] = $label_submit;
		}

	} else {

		// This is an existing post
		$post_status = get_post_status( $post_id ); // Get the Posts Status

		// Check Post Status
		if ( $post_status == 'edit-draft' ) {
			$formelements[] = $label_save;
			$formelements[] = $label_moderation;
		}
		if ( $post_status == 'awaiting-review' ) {
			if ( $buddyforms[ $form_slug ]['moderation_logic'] != 'many_drafts' ) {
				$formelements[] = $label_no_edit;
			} else {
				$formelements[] = $label_new_draft;
			}
		}
		if ( $post_status == 'publish' ) {
			$formelements[] = $label_new_draft;
		}
	}
//	$formelements[] = $status;


	ob_start();
	foreach ( $formelements as $key => $formelement ) {
		$formelement->render();
	}
	$field_html = ob_get_contents();
	ob_end_clean();

	$json_args['form_actions'] = $field_html;

	return $json_args;

}
add_filter( 'buddyforms_ajax_process_edit_post_json_response', 'buddyforms_moderation_ajax_process_edit_post_json_response', 10, 1 );

function bf_moderation_post_control_args( $args ) {

	if ( $_POST['status'] == 'new-draft' ) {
		$args['action'] = 'new-post';
		if ( $_POST['post_id'] != 0 ) {
			$args['post_parent'] = $_POST['post_id'];
		}
		$args['post_status'] = 'edit-draft';
	}

	if ( $_POST['status'] == 'awaiting-review' ) {
		$args['post_status'] = 'awaiting-review';
	}

	return $args;
}
add_filter( 'buddyforms_update_post_args', 'bf_moderation_post_control_args', 10, 1 );


function bf_moderation_create_edit_form_post_id( $post_id ) {
	global $buddyforms;

	$form_slug = buddyforms_get_form_slug_by_post_id($post_id);

	if ( ! $form_slug ) {
		return $post_id;
	}

	if ( ! isset( $buddyforms[ $form_slug ]['moderation_logic'] ) || $buddyforms[ $form_slug ]['moderation_logic'] == 'default' ) {
		return $post_id;
	}

	$args = array(
		'post_parent'    => $post_id,
		'posts_per_page' => 1,
		'post_status'    => 'edit-draft'
	);

	$children = get_posts( $args, 'ARRAY_N' );

	if ( count( $children ) != 0 ) {
		$post_id = $children[0]->ID;
	}

	return $post_id;

}
add_filter( 'buddyforms_create_edit_form_post_id', 'bf_moderation_create_edit_form_post_id', 10, 1 );

function bf_create_post_status_to_display( $query_args ) {
	global $buddyforms;

	if ( isset( $buddyforms[ $query_args['form_slug'] ]['moderation_logic'] ) && $buddyforms[ $query_args['form_slug'] ]['moderation_logic'] != 'default' ) {
		$query_args['post_status'] = array( 'publish', 'awaiting-review', 'edit-draft', 'draft' );
	}

	return $query_args;

}
add_filter( 'buddyforms_post_to_display_args', 'bf_create_post_status_to_display', 9999, 1 );

function bf_moderation_post_status_css( $post_status_css, $form_slug ) {
	global $buddyforms;

	if ( ! isset( $buddyforms[ $form_slug ]['moderation_logic'] ) || $buddyforms[ $form_slug ]['moderation_logic'] == 'default' ) {
		return $post_status_css;
	}

	if ( $post_status_css == 'awaiting-review' ) {
		$post_status_css = 'bf-pending';
	}

	if ( $post_status_css == 'edit-draft' ) {
		$post_status_css = 'draft';
	}

	return $post_status_css;
}
add_filter( 'buddyforms_post_status_css', 'bf_moderation_post_status_css', 10, 2 );


add_filter( 'buddyforms_create_edit_form_post_status', 'buddyforms_moderation_create_edit_form_post_status', 2, 101 );
function buddyforms_moderation_create_edit_form_post_status( $post_status, $form_slug ) {
	global $buddyforms;
	
	if ( empty( $buddyforms[ $form_slug ]['moderation_logic'] ) || ( ! empty( $buddyforms[ $form_slug ]['moderation_logic'] ) && 'default' == $buddyforms[ $form_slug ]['moderation_logic'] ) ) {
		return $post_status;
	}

	if( isset( $_POST['status'] ) ){
		if( $_POST['status'] == 'submitted' ||  $_POST['status'] == 'publish'  ){
			return 'edit-draft';
		}

		// What if someone enter a not existing post status?
		$post_status = $_POST['status'];
	}

	return $post_status;

}
