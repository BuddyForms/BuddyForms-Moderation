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
    global $thepostid, $post;

    extract($form_args);

    if(!isset($customfield['type']))
        return $form;

    $thepostid          = $post_id;
    $post               = get_post($post_id);

    switch ($customfield['type']) {
        case 'Review-Logic':
            $form->addElement( new Element_Button( 'Save new Draft', 'submit', array('name' => 'new-draft')));
            $form->addElement( new Element_Button( 'Submit for review', 'submit', array('name' => 'needs-review')));
        break;
    }

    return $form;
}
add_filter('buddyforms_create_edit_form_display_element','bf_review_create_frontend_form_element',1,2);

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
add_filter( 'post_row_actions', 'bf_review_approve', 10, 2 );
add_filter( 'page_row_actions', 'bf_review_approve', 10, 2 );

/*
 * Update the original parent post
 *
 */
class BF_Review_Update_Post {

    public function __construct() {
        add_action( 'wp_insert_post_data', array( $this, 'modify_post_content' ), 99, 2 );
    }

    public function modify_post_content( $data , $postarr ) {

        if($data['post_status'] != 'publish')
            return $data;

        if($data['post_type'] == 'revision')
            return $data;

        $bf_form_slug = get_post_meta($postarr['ID'],'_bf_form_slug', true);

        if(isset($bf_form_slug) && $data['post_parent'] != 0){

            $data['post_status'] = 'approved';

            $update_post = array(
                'ID'        		=> $postarr['post_parent'],
                'post_title' 		=> $postarr['post_title'],
                'post_content' 		=> $postarr['post_content'],
                'post_type' 		=> $postarr['post_type'],
                'post_status' 		=> $postarr['post_status'],
                'comment_status'	=> $postarr['comment_status'],
                'post_excerpt'		=> $postarr['post_excerpt'],
            );

            $parent_post_id = wp_update_post($update_post);

            if($parent_post_id){
                bf_review_copy_post_taxonomies($parent_post_id, $postarr['ID']);
                bf_review_copy_post_meta_info($parent_post_id,  $postarr['ID']);
            }

        }
        return $data;

    }

}
new BF_Review_Update_Post;


/**
 * Copy the taxonomies of a post to another post
 * @param $parent_post_id
 * @param $child_post_id
 */
function bf_review_copy_post_taxonomies($parent_post_id, $child_post_id) {
    global $wpdb;
    if (isset($wpdb->terms)) {
        // Clear default category (added by wp_insert_post)
        wp_set_object_terms( $parent_post_id, NULL, 'category' );

        $post = get_post($child_post_id);

        $post_taxonomies = get_object_taxonomies($post->post_type);

        foreach ($post_taxonomies as $taxonomy) {
            $post_terms = wp_get_object_terms($post->ID, $taxonomy, array( 'orderby' => 'term_order' ));
            $terms = array();
            for ($i=0; $i<count($post_terms); $i++) {
                $terms[] = $post_terms[$i]->slug;
            }
            wp_set_object_terms($parent_post_id, $terms, $taxonomy);
        }
    }
}

/**
 * Copy the meta information of a post to another post
 * @param $new_id
 * @param $post
 */
function bf_review_copy_post_meta_info($parent_post_id, $child_post_id) {
    $post_meta_keys = get_post_custom_keys($child_post_id);
    if (empty($post_meta_keys))
        return;

    foreach ($post_meta_keys as $meta_key) {
        $meta_values = get_post_custom_values($meta_key, $child_post_id);
        foreach ($meta_values as $meta_value) {
            $meta_value = maybe_unserialize($meta_value);
            update_post_meta($parent_post_id, $meta_key, $meta_value);
        }
    }
}

add_filter('bf_post_control_args', 'bf_review_post_control_args', 10, 1);
function bf_review_post_control_args($args){

    if($_POST['submitted'] == 'new-draft'){
        $args['action'] = 'new-draft';
        $args['post_parent'] = $_POST['new_post_id'];
        $args['post_status'] = 'new-draft';
    }

    if($_POST['submitted'] == 'needs-review'){
        $args['action'] = 'needs-review';
        $args['post_parent'] = $_POST['new_post_id'];
        $args['post_status'] = 'needs-review';
    }

    return $args;
}

// Register Custom Status
function custom_post_status() {

    $args = array(
        'label'                     => _x( 'New Draft', 'New Draft', 'buddyforms' ),
        'label_count'               => _n_noop( 'New Draft (%s)',  'New Draft (%s)', 'buddyforms' ),
        'public'                    => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'exclude_from_search'       => true,
    );
    register_post_status( 'new-draft', $args );
    $args = array(
        'label'                     => _x( 'Needs Review', 'Needs Review', 'buddyforms' ),
        'label_count'               => _n_noop( 'Needs Review (%s)',  'Needs Review (%s)', 'buddyforms' ),
        'public'                    => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'exclude_from_search'       => true,
    );
    register_post_status( 'needs-review', $args );
    $args = array(
        'label'                     => _x( 'Approved', 'Approved', 'buddyforms' ),
        'label_count'               => _n_noop( 'Approved (%s)',  'Approved (%s)', 'buddyforms' ),
        'public'                    => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'exclude_from_search'       => true,
    );
    register_post_status( 'approved', $args );

}

// Hook into the 'init' action
add_action( 'init', 'custom_post_status', 999 );