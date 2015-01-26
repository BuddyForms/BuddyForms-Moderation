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
    $form->addElement(new Element_HTML('<p><a href="Review-Logic/'.$form_slug.'" class="action">Review Logic</a></p>'));
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
            $form_fields['right']['name']		= new Element_Hidden("buddyforms_options[buddyforms][".$form_slug."][form_fields][".$field_id."][name]", 'Review ogic');
            $form_fields['right']['slug']		= new Element_Hidden("buddyforms_options[buddyforms][".$form_slug."][form_fields][".$field_id."][slug]", 'bf_review_logic');

            $form_fields['right']['type']	    = new Element_Hidden("buddyforms_options[buddyforms][".$form_slug."][form_fields][".$field_id."][type]", $field_type);
            $form_fields['right']['order']		= new Element_Hidden("buddyforms_options[buddyforms][".$form_slug."][form_fields][".$field_id."][order]", $field_position, array('id' => 'buddyforms/' . $form_slug .'/form_fields/'. $field_id .'/order'));

            $review_button = 'false';
            if(isset($buddyforms_options['buddyforms'][$form_slug]['form_fields'][$field_id]['review_button']))
                $review_button = $buddyforms_options['buddyforms'][$form_slug]['form_fields'][$field_id]['review_button'];
            $form_fields['full']['draft']		= new Element_Checkbox('<b>' . __('Display Button', 'buddyforms') . '</b>' ,"buddyforms_options[buddyforms][".$form_slug."][form_fields][".$field_id."][review_button]",array('draft' => __('Show Draft Button', 'buddyforms') ,'review' => __('Show Review Button', 'buddyforms')),array('id' => 'draft'.$form_slug.'_'.$field_id , 'value' => $review_button));
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



            // This is a Parent post
            if( $post->post_parent == 0 ){

                //Check if existing post && $post->post_status != 'awaiting-review'
                if($post_id == 0 ){

                    echo 'new$post_idq '.$post_id.' $post->post_status '. $post->post_status;

                    $form->addElement( new Element_Button( 'Save', 'submit', array('name' => 'edit-draft')));

                } else {

                    echo 'new$post_id '.$post_id.' $post->post_status '. $post->post_status;

                    if($post->post_status == 'edit-draft'){
                        $form->addElement( new Element_Button( 'Save', 'submit', array('name' => 'submitted')));
                        $form->addElement( new Element_Button( 'Submit for review', 'submit', array('name' => 'awaiting-review')));
                    } else {
                        $form->addElement( new Element_Button( 'Save new Draft', 'submit', array('name' => 'edit-draft')));
                    }
                }

            } else {

                if($post->post_status == 'edit-draft'){
                    $form->addElement( new Element_Button( 'Save', 'submit', array('name' => 'submitted')));
                    $form->addElement( new Element_Button( 'Submit for review', 'submit', array('name' => 'awaiting-review')));
                } else {
                    $form->addElement( new Element_Button( 'Save new Draft', 'submit', array('name' => 'edit-draft')));
                }
            }

            add_filter('buddyforms_create_edit_form_button', 'bf_review_buddyforms_create_edit_form_button', 10, 1);

//            if($post_id != 0 ){
//
//                $args = array(
//                    'post_parent' => $post_id,
//                    'posts_per_page' => -1,
//                     );
//
//
//                print_r($args);
//
//                $children = get_posts($args);
//                foreach ( $children as $child ) : setup_postdata( $child );?>
<!--                    <li>-->
<!--                        <a href="--><?php //the_permalink(); ?><!--">--><?php //the_title(); ?><!--</a>-->
<!--                    </li>-->
<!--                --><?php //endforeach;
//
//            }


            break;
    }

    return $form;
}
add_filter('buddyforms_create_edit_form_display_element','bf_review_create_frontend_form_element',1,2);

function bf_review_buddyforms_create_edit_form_button($form_button){

    return false;

}

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

        if($_POST['new_post_id'] != 0 ){
            $args['post_parent'] = $_POST['new_post_id'];
        }
        $args['post_status'] = 'edit-draft';
    }

    if($_POST['submitted'] == 'awaiting-review'){
        $args['post_status'] = 'awaiting-review';
    }

    return $args;
}
add_filter('bf_post_control_args', 'bf_review_post_control_args', 10, 1);


add_filter('bf_create_edit_form_post_id', 'bf_review_create_edit_form_post_id', 10, 1);
function bf_review_create_edit_form_post_id($post_id){

    $args = array(
        'post_parent' => $post_id,
        'posts_per_page' => 1,
        'post_status' => 'edit-draft' );

    $children = get_posts($args, 'ARRAY_N');

    if( count( $children ) != 0 )
        $post_id = $children[0]->ID;

    return $post_id;

}