<?php
get_header();


$post_id = get_the_ID();
$selected_form_id = get_post_meta($post_id, 'selected_wpforms_form_id', true);

$survey_ids = get_post_ids_by_meta_value('selected_wpforms_form_id', $selected_form_id);

$is_item_visible = get_post_meta($post_id, 'is_item_visible', true);

$current_user_id = get_current_user_id();
$form_approval_status_user_id = get_post_meta($post_id, 'form_approval_status_user_id', true);

// var_dump($post_id);

$all_survey_ids = get_posts(array(
    'fields'          => 'ids',
    'posts_per_page'  => -1,
    'post_type' => 'applicants-surveys',
    array(
        'key' => 'selected_wpforms_form_id',
        'compare' => 'EXISTS',  // Check if the meta key exists.
    ),
    'order' => 'ASC'
));


// var_dump($form_approval_status_user_id);

$submitted_forms_user_key = 'is_visible_for_user_' . $current_user_id;

$is_item_visible_to_current_user = get_post_meta($post_id, $submitted_forms_user_key, true);



if ($is_item_visible == 'true' && $all_survey_ids[0] == $post_id) {

    if (!empty($selected_form_id)) {
        echo do_shortcode('[wpforms id="' . esc_attr($selected_form_id) . '"]');
    }
} elseif ($is_item_visible_to_current_user) {
    if (!empty($selected_form_id)) {
        echo do_shortcode('[wpforms id="' . esc_attr($selected_form_id) . '"]');
    }
} else {
    echo '<div class="bg-warning text-white h1 p-2 err-msg">you are not allowed in this page. </div>';
}

get_footer();
