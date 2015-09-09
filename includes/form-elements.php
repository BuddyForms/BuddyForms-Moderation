<?php


/*
 * Add a new form element to the form create view sidebar
 *
 * @param object the form object
 * @param array selected form
 *
 * @return the form object
 */
function bf_review_add_form_element_to_sidebar($form, $form_slug){
    $form->addElement(new Element_HTML('<p><a href="Review-Logic/'.$form_slug.'/unique" class="action">Review Logic</a></p>'));
    return $form;
}
add_filter('buddyforms_add_form_element_to_sidebar','bf_review_add_form_element_to_sidebar',1,2);

/*
 * Create the new Form Builder Form Element
 *
 */
function bf_review_create_new_form_builder_form_element($form_fields, $form_slug, $field_type, $field_id){
    global $field_position;
    $buddyforms_options = get_option('buddyforms_options');

    switch ($field_type) {

        case 'Review-Logic':
            unset($form_fields);
            $form_fields['right']['name']	= new Element_Hidden("buddyforms_options[buddyforms][".$form_slug."][form_fields][".$field_id."][name]", 'Review Logic');
            $form_fields['right']['slug']   = new Element_Hidden("buddyforms_options[buddyforms][".$form_slug."][form_fields][".$field_id."][slug]", 'bf_review_logic');

            $form_fields['right']['type']	= new Element_Hidden("buddyforms_options[buddyforms][".$form_slug."][form_fields][".$field_id."][type]", $field_type);
            $form_fields['right']['order']  = new Element_Hidden("buddyforms_options[buddyforms][".$form_slug."][form_fields][".$field_id."][order]", $field_position, array('id' => 'buddyforms/' . $form_slug .'/form_fields/'. $field_id .'/order'));
            $form_fields['left']['html']    = new Element_HTML(__("There are no settings needed so far. If you add the Review Logic form element to the form, the form will use the Review Logic automatically.<br><br> The Form Submit button will change dynamically dependend on the post status.", 'buddyforms'));
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
        case 'Review-Logic':

            $post = get_post($post_id);

                if($post_id == 0 ){
                    $form->addElement( new Element_Button( 'Save', 'submit', array('class' => 'bf-submit', 'name' => 'edit-draft')));
                } else {
                    if($post->post_status == 'edit-draft'){
                        $form->addElement( new Element_Button( 'Save', 'submit', array('class' => 'bf-submit', 'name' => 'submitted')));
                        $form->addElement( new Element_Button( 'Submit for review', 'submit', array('class' => 'bf-submit', 'name' => 'awaiting-review')));
                    } else {
                        $form->addElement( new Element_Button( 'Save new Draft', 'submit', array('class' => 'bf-submit', 'name' => 'edit-draft')));
                    }
                }

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

    extract($json_args);

    $post = get_post($post_id);

    if(!isset($_POST['form_slug']))
        return $json_args;

    if(!isset($buddyforms['buddyforms'][$_POST['form_slug']]['form_fields']))
        return $json_args;


    $review = false;
    foreach($buddyforms['buddyforms'][$_POST['form_slug']]['form_fields'] as $key => $field ){

        if($field['type'] == 'Review-Logic'){
            $review = true;
        }
    }

    if(!$review)
        return $json_args;

    if($post_id == 0 ){
        $formelements[] = new Element_Button( 'Save', 'submit', array('class' => 'bf-submit', 'name' => 'edit-draft'));
    } else {
        if($post->post_status == 'edit-draft'){
            $formelements[] = new Element_Button( 'Save', 'submit', array('class' => 'bf-submit', 'name' => 'submitted'));
            $formelements[] = new Element_Button( 'Submit for review', 'submit', array('class' => 'bf-submit', 'name' => 'awaiting-review'));
        } else {
            $formelements[] = new Element_Button( 'Save new Draft', 'submit', array('class' => 'bf-submit', 'name' => 'edit-draft'));
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

    $buddyforms_options = get_option('buddyforms_options');

    $bf_form_slug = get_post_meta( $post_id, '_bf_form_slug', true );

    if(!$bf_form_slug)
        return $post_id;

    if(!isset($buddyforms_options['buddyforms'][$bf_form_slug]['form_fields']))
        return $post_id;

    $form_fields = $buddyforms_options['buddyforms'][$bf_form_slug]['form_fields'];

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

    $buddyforms_options = get_option('buddyforms_options');

    $form_fields = $buddyforms_options['buddyforms'][$query_args['form_slug']]['form_fields'];

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
    $buddyforms_options = get_option('buddyforms_options');

    $form_fields = $buddyforms_options['buddyforms'][$form_slug]['form_fields'];

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