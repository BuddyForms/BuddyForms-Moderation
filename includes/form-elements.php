<?php
/*
 * Add a new form element to the form create view sidebar
 *
 * @param object the form object
 * @param array selected form
 *
 * @return the form object
 */
function bf_review_add_form_element_to_sidebar($sidebar_elements){
    global $post;

    if($post->post_type != 'buddyforms')
        return;

    $sidebar_elements[] = new Element_HTML('<p><a href="#" data-fieldtype="review-logic" data-unique="unique" class="bf_add_element_action">Review Logic</a></p>');

    return $sidebar_elements;
}
add_filter('buddyforms_add_form_element_to_sidebar','bf_review_add_form_element_to_sidebar',1,2);

/*
 * Create the new Form Builder Form Element
 *
 */
function bf_review_create_new_form_builder_form_element($form_fields, $form_slug, $field_type, $field_id){
    global $field_position, $post;

    $buddyform = get_post_meta($post->ID, '_buddyforms_options', true);

    switch ($field_type) {

        case 'review-logic':
            unset($form_fields);
            $form_fields['general']['name']     = new Element_Hidden("buddyforms_options[form_fields][".$field_id."][name]", 'Review Logic');
            $form_fields['general']['slug']     = new Element_Hidden("buddyforms_options[form_fields][".$field_id."][slug]", 'bf_review_logic');

            $form_fields['general']['type']	    = new Element_Hidden("buddyforms_options[form_fields][".$field_id."][type]", $field_type);
            $form_fields['general']['order']    = new Element_Hidden("buddyforms_options[form_fields][".$field_id."][order]", $field_position, array('id' => 'buddyforms/' . $form_slug .'/form_fields/'. $field_id .'/order'));

            $review_logic = isset($buddyform['form_fields'][$field_id]['review_logic']) ? $buddyform['form_fields'][$field_id]['review_logic'] : 'one_draft';
            echo $review_logic;
            $form_fields['general']['review_logic']    = new Element_Radio(
                '<b>' . __('Review Logic', 'buddyforms') . '</b>',
                "buddyforms_options[form_fields][" . $field_id . "][review_logic]",
                Array(
                    'one_draft'      => 'User can create one new draft and save it until he submit it for review. He can not create a new draft before the last draft gets approved<br>',
                    'hidden_draft'   => 'If the user creates or edit a post he is only able to submit for review. No Save Button.<br>',
                    'many_drafts'    => 'User can create as many new drafts as he like. Also if one earlier draft is waiting for approval he can create new drafts and submit for review. This can end up in multiple drafts and awaiting reviews post status.
                                        If a earlier draft gets aproved it gets merged back to the public version. so if the latest draft gets approved all posts should be merged back recursive<br>')
                ,
                array(
                    'value'      => $review_logic,
                    'shortDesc'  => 'If a post is created or edited and the review logic is enabled the post is saved with post status edit-draft.
                    If a post is submit for review the post status is set to awaiting-approval'
                )
            );

            $label_submit = isset($buddyform['form_fields'][$field_id]['label_submit']) ? $buddyform['form_fields'][$field_id]['label_submit'] : 'Submit';
            $form_fields['labels']['label_submit']   = new Element_Textbox('<b>' . __('Label for Submit Button', 'buddyforms') . '</b>', "buddyforms_options[form_fields][" . $field_id . "][label_submit]", array('value' => $label_submit));

            $label_save = isset($buddyform['form_fields'][$field_id]['label_save']) ? $buddyform['form_fields'][$field_id]['label_save'] : 'Save';
            $form_fields['labels']['label_save']   = new Element_Textbox('<b>' . __('Label for Save Button', 'buddyforms') . '</b>', "buddyforms_options[form_fields][" . $field_id . "][label_save]", array('value' => $label_save));

            $label_review = isset($buddyform['form_fields'][$field_id]['label_review']) ? $buddyform['form_fields'][$field_id]['label_review'] : 'Submit for Review';
            $form_fields['labels']['label_review']   = new Element_Textbox('<b>' . __('Label for Submit for Review Button', 'buddyforms') . '</b>', "buddyforms_options[form_fields][" . $field_id . "][label_review]", array('value' => $label_review));

            $label_new_draft = isset($buddyform['form_fields'][$field_id]['label_new_draft']) ? $buddyform['form_fields'][$field_id]['label_new_draft'] : 'Create new Draft';
            $form_fields['labels']['label_new_draft']   = new Element_Textbox('<b>' . __('Label for Create new Draft Button', 'buddyforms') . '</b>', "buddyforms_options[form_fields][" . $field_id . "][label_new_draft]", array('value' => $label_new_draft));

            $label_no_edit = isset($buddyform['form_fields'][$field_id]['label_no_edit']) ? $buddyform['form_fields'][$field_id]['label_no_edit'] : 'This Post is waiting for approval and can not be changed until it gets approved';
            $form_fields['labels']['label_no_edit']   = new Element_Textarea('<b>' . __('If the form is displayed but edeting is disabled', 'buddyforms') . '</b>', "buddyforms_options[form_fields][" . $field_id . "][label_no_edit]", array('value' => $label_no_edit));

            break;

    }

    return $form_fields;
}
add_filter('buddyforms_form_element_add_field','bf_review_create_new_form_builder_form_element',1,5);

/*
 * Display the new Form Element in the Frontend Form
 *
 */
function bf_review_create_frontend_form_element($form, $form_args){

    extract($form_args);

    if(!isset($customfield['type']))
        return $form;

    switch ($customfield['type']) {
        case 'review-logic':

            $label_review    = new Element_Button( __($customfield['label_review'], 'buddyforms'), 'submit', array('class' => 'bf-submit', 'name' => 'awaiting-review'));
            $label_submit    = new Element_Button( __($customfield['label_submit'], 'buddyforms'), 'submit', array('class' => 'bf-submit', 'name' => 'edit-draft'));
            $label_save      = new Element_Button( __($customfield['label_save'], 'buddyforms'), 'submit', array('class' => 'bf-submit', 'name' => 'submitted'));
            $label_new_draft = new Element_Button( __($customfield['label_new_draft'], 'buddyforms'), 'submit', array('class' => 'bf-submit', 'name' => 'edit-draft'));
            $label_no_edit   = new Element_HTML( '<p>' . __($customfield['label_no_edit'], 'buddyforms') . '</p>'  );

            // Set the post status to edit-draft if edit screen is displayed. This will make sure we never save public post

            $status          = new Element_Hidden("status", 'edit-draft');

            // If post_id is 0 we have a new posts
            if($post_id == 0 ){

                if($customfield['review_logic'] == 'hidden_draft'){
                    $form->addElement( $label_review );
                } else {
                    $form->addElement( $label_submit );
                }

            } else {

                // This is an existing post
                $post_status = get_post_status($post_id); // Get the Posts

                // Check Post Status
                if($post_status == 'edit-draft'){
                    $form->addElement( $label_save );
                    $form->addElement( $label_review );
                }
                if($post_status == 'awaiting-review') {
                    if($customfield['review_logic'] != 'many_drafts' ){
                        $form->addElement( $label_no_edit );
                    } else {
                        $form->addElement( $label_new_draft );
                    }
                }
                if($post_status == 'publish') {
                    $form->addElement( $label_new_draft );
                }
            }
            $form->addElement( $status );

            add_filter('buddyforms_create_edit_form_button', 'bf_review_buddyforms_create_edit_form_button', 10, 1);

            break;
    }

    return $form;
}
add_filter('buddyforms_create_edit_form_display_element','bf_review_create_frontend_form_element',1,2);

function bf_review_buddyforms_create_edit_form_button($form_button){

    return false;

}

function buddyforms_review_ajax_process_edit_post_json_response($json_args){
    global $buddyforms;

    if(isset($json_args))
        extract($json_args);

    if(isset($post_id) && $post_id != 0){
        $post = get_post($post_id);
    } else {
        $post_id = 0;
    }

    if(!isset($_POST['form_slug']))
        return $json_args;

    if(!isset($buddyforms[$_POST['form_slug']]['form_fields']))
        return $json_args;

    foreach($buddyforms[$_POST['form_slug']]['form_fields'] as $key => $customfield ){

        if($customfield['type'] == 'review-logic'){
            $review_status = 'edit-draft';

            if($post_id == 0 ){
                if($customfield['review_logic'] == 'hidden_draft'){
                    $formelements[] = new Element_Button( 'Submit for Review', 'submit', array('class' => 'bf-submit', 'name' => 'awaiting-review'));
                } else {
                    $formelements[] = new Element_Button( 'Save', 'submit', array('class' => 'bf-submit', 'name' => 'edit-draft'));
                }
            } else {
                if($post->post_status == 'edit-draft'){
                    $formelements[] = new Element_Button( 'Save', 'submit', array('class' => 'bf-submit', 'name' => 'submitted'));
                    $formelements[] = new Element_Button( 'Submit for Review', 'submit', array('class' => 'bf-submit', 'name' => 'awaiting-review'));
                } elseif($post->post_status == 'awaiting-review') {
                    if($customfield['review_logic'] != 'many_drafts' ){
                        $formelements[] = new Element_HTML( '<p>This Post is waiting for approval and can not be changed until it gets approved</p>'  );
                    } else {
                        $formelements[] = new Element_Button( 'Save new Draft', 'submit', array('class' => 'bf-submit', 'name' => 'edit-draft'));
                    }
                } else {
                    $formelements[] = new Element_Button( 'Save new Draft', 'submit', array('class' => 'bf-submit', 'name' => 'edit-draft'));
                }
            }
            $formelements[] = new Element_Hidden("status", $review_status);
        }
    }

    ob_start();
    foreach ($formelements as $key => $formelement) {
        $formelement->render();
    }
    $field_html = ob_get_contents();
    ob_end_clean();

    $json_args['form_actions'] = $field_html;
    return $json_args;

}
add_filter('buddyforms_ajax_process_edit_post_json_response','buddyforms_review_ajax_process_edit_post_json_response',10,1);


/*
 * Add the duplicate link to action list for post_row_actions
 *
 */
function bf_review_approve( $actions, $post ) {

    if (current_user_can('edit_posts')) {
        $actions['bf_approve'] = '<a href="#" title="Approve" >Approve</a>';
    }
    return $actions;
}
//add_filter( 'post_row_actions', 'bf_review_approve', 10, 2 );
//add_filter( 'page_row_actions', 'bf_review_approve', 10, 2 );

function bf_review_post_control_args($args){

    if($_POST['submitted'] == 'edit-draft'){
        $args['action'] = 'new-post';
        if($_POST['post_id'] != 0 ){
            $args['post_parent'] = $_POST['post_id'];
        }
        $args['post_status'] = 'edit-draft';
    }

    if($_POST['submitted'] == 'awaiting-review'){
        $args['post_status'] = 'awaiting-review';
    }

    return $args;
}
add_filter('buddyforms_update_post_args', 'bf_review_post_control_args', 10, 1);


add_filter('bf_create_edit_form_post_id', 'bf_review_create_edit_form_post_id', 10, 1);
function bf_review_create_edit_form_post_id($post_id){
    global $buddyforms;
    $buddyforms_options = $buddyforms;

    $bf_form_slug = get_post_meta( $post_id, '_bf_form_slug', true );

    if(!$bf_form_slug)
        return $post_id;

    if(!isset($buddyforms_options[$bf_form_slug]['form_fields']))
        return $post_id;

    $form_fields = $buddyforms_options[$bf_form_slug]['form_fields'];

    if(!$form_fields)
        return $post_id;

    $bf_review_logic = false;
    foreach($form_fields as $key => $form_field){
        if(in_array('bf_review_logic', $form_field))
            $bf_review_logic = true;
    }

    if(!$bf_review_logic)
        return $post_id;

    $args = array(
        'post_parent' => $post_id,
        'posts_per_page' => 1,
        'post_status' => 'edit-draft' );

    $children = get_posts($args, 'ARRAY_N');

    if( count( $children ) != 0 )
        $post_id = $children[0]->ID;

    return $post_id;

}

add_filter('bf_post_to_display_args', 'bf_create_post_status_to_display', 10, 1);

function bf_create_post_status_to_display($query_args){
    global $buddyforms;
    $buddyforms_options = $buddyforms;

    $form_fields = $buddyforms_options[$query_args['form_slug']]['form_fields'];

    $bf_review_logic = false;
    foreach($form_fields as $key => $form_field){
        if(in_array('bf_review_logic', $form_field))
            $bf_review_logic = true;
    }


    if($bf_review_logic)
        $query_args['post_status'] =  array('publish', 'awaiting-review', 'edit-draft');

    return $query_args;

}

add_filter('bf_post_status_css','bf_review_post_status_css', 10, 2);

function bf_review_post_status_css($post_status_css, $form_slug){
    global $buddyforms;
    $buddyforms_options = $buddyforms;

    $form_fields = $buddyforms_options[$form_slug]['form_fields'];

    $bf_review_logic = false;
    foreach($form_fields as $key => $form_field){
        if(in_array('bf_review_logic', $form_field))
            $bf_review_logic = true;
    }

    if(!$bf_review_logic)
        return $post_status_css;

    if( $post_status_css == 'awaiting-review')
        $post_status_css = 'bf-pending';

    if( $post_status_css == 'edit-draft')
        $post_status_css = 'draft';

    return $post_status_css;

}