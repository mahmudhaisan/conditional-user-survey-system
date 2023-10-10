<?php



// Add custom post type for surveys
function cussys_create_survey_post_type()
{
    $labels = array(
        'name' => 'Applicant Surveys',
        'singular_name' => 'Applicant Survey',
        'add_new' => 'Add New',
        'add_new_item' => 'Add New Applicant Survey',
        'edit_item' => 'Edit Applicant Survey',
        'new_item' => 'New Applicant Survey',
        'view_item' => 'View Applicant Survey',
        'search_items' => 'Search Applicant Surveys',
        'not_found' => 'No Applicant surveys found',
        'not_found_in_trash' => 'No Applicant surveys found in Trash',
        'parent_item_colon' => '',
        'menu_name' => 'Applicant Surveys'
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'applicants-surveys'),
        'capability_type' => 'post',
        'has_archive' => true,
        'hierarchical' => false,
        'menu_position' => null,
        'supports' => array('title')
    );

    register_post_type('applicants-surveys', $args);
}

add_action('init', 'cussys_create_survey_post_type');




function add_survey_dropdown_meta_box()
{
    add_meta_box(
        'survey_form_selector',
        'Select WPForms Form',
        'render_survey_dropdown_meta_box',
        'applicants-surveys',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'add_survey_dropdown_meta_box');



function render_survey_dropdown_meta_box($post)
{
    $wpforms_forms = get_posts(array(
        'post_type' => 'wpforms',
        'posts_per_page' => -1,
    ));

    if ($wpforms_forms) {
        echo '<label for="wpforms_form_select">Select a WPForms Form:</label>';
        echo '<select name="wpforms_form_select" id="wpforms_form_select">';
        echo '<option value="">Select a Form</option>';

        foreach ($wpforms_forms as $form) {
            echo '<option required value="' . esc_attr($form->ID) . '" ' . selected(get_post_meta($post->ID, 'selected_wpforms_form_id', true), $form->ID, false) . '>' . esc_html($form->post_name) . '</option>';
        }

        echo '</select>';
    } else {
        echo 'No WPForms forms found.';
    }
}




function save_survey_form_data($post_id)
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    if (isset($_POST['wpforms_form_select'])) {
        $selected_form_id = sanitize_text_field($_POST['wpforms_form_select']);

        update_post_meta($post_id, 'selected_wpforms_form_id', $selected_form_id);
        // update_post_meta($post_id, 'wpforms_form_select_user', $selected_form_id);
    }
}
add_action('save_post', 'save_survey_form_data');



function servey_single_template($template)
{
    if (is_singular('applicants-surveys')) {
        $template = CUSSYS_DIR_PATH . 'templates/survey-single-template.php';
    }

    return $template;
}
add_filter('single_template', 'servey_single_template');


// add_shortcode('show_survey_form', 'show_survey_form');
function show_survey_form()
{
    // Get the current user's ID
    $current_user_id = get_current_user_id();

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


    // print_r($all_survey_ids);

    $args = array(
        'post_type' => 'applicants-surveys',  // Change 'survey' to your CPT slug.
        'meta_query' => array(
            array(
                'key' => 'selected_wpforms_form_id',
                'compare' => 'EXISTS',  // Check if the meta key exists.
            ),
        ),
        'order' => 'ASC'
    );

    $survey_query = new WP_Query($args);

    // Initialize a variable to track the loop iteration
    $counter = 0;

    // Check if there are any posts in the query
    if ($survey_query->have_posts()) {
        // Loop through the posts
        while ($survey_query->have_posts()) {
            $survey_query->the_post();
            // Get the ID of the current post (survey)
            $survey_id = get_the_ID();
            $selected_wpforms_form_id = get_post_meta($survey_id, 'selected_wpforms_form_id', true);
            $is_item_visible = get_post_meta($survey_id, 'is_item_visible', true);

            $submitted_forms_meta_key = 'submitted_forms_meta_' . $current_user_id;
            $submitted_forms_meta = get_post_meta($survey_id, $submitted_forms_meta_key);

            if ($submitted_forms_meta) {
                $submitted_form_approval_status = $submitted_forms_meta[0]['approval_status'];
                $submitted_form_user_id = $submitted_forms_meta[0]['user_id'];
            }


            $submitted_forms_user_key = 'is_visible_for_user_' . $current_user_id;

            $is_item_visible_to_current_user = get_post_meta($survey_id, $submitted_forms_user_key, true);

            // $form_approval_status_user_id = get_post_meta($survey_id, 'form_approval_status_user_id', true);
            // $form_approval_status = get_post_meta($survey_id, 'form_approval_status', true);

            $survey_index = array_search($survey_id, $all_survey_ids);

            if ($submitted_forms_meta && $submitted_form_user_id == $current_user_id &&  $submitted_form_approval_status == 'approved') {
                $next_survey_id = isset($all_survey_ids[$survey_index + 1]) ? $all_survey_ids[$survey_index + 1] : false;

                if ($next_survey_id) {
                    update_post_meta($next_survey_id, $submitted_forms_user_key, 'true');
                    // update_post_meta($next_survey_id, 'form_approval_status_user_id', $form_approval_status_user_id);
                } else {
                    // echo "No more survey IDs available.<br>";
                }
            }

?>

            <div class="card show-survey-items">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="h2 p-2"><?php the_title(); ?></span>

                    <?php if ($is_item_visible == 'true' && $all_survey_ids[0]) { ?>
                        <span class="h2 p-2">
                            <a class="btn btn-primary" href="<?php the_permalink(); ?>">View</a>
                        </span>
                    <?php } elseif ($is_item_visible_to_current_user == 'true') { ?>
                        <span class="h2 p-2">
                            <a class="btn btn-primary" href="<?php the_permalink(); ?>">View</a>
                        </span>
                    <?php } else { ?>
                        <!-- Your "else" content goes here -->
                        <button class="btn btn-primary" disabled>View</button>
                    <?php } ?>




                </div>
            </div>
<?php }

        // Restore the global post data
        wp_reset_postdata();
    }
}

add_shortcode('show_survey_form', 'show_survey_form');




function add_meta_to_first_cpt_post()
{
    $cpt_slug = 'applicants-surveys';
    $meta_key = 'is_item_visible';
    $meta_value = 'true';

    // Check if the custom post type has any posts.
    $cpt_posts = get_posts(array(
        'post_type' => $cpt_slug,
        'posts_per_page' => 1, // Retrieve only one post.
        'order' => 'ASC'
    ));

    if (!empty($cpt_posts)) {
        // Get the ID of the first post in the custom post type.
        $first_post_id = $cpt_posts[0]->ID;

        // Check if the meta key already exists for the first post.
        $existing_meta_value = get_post_meta($first_post_id, $meta_key, true);

        if (empty($existing_meta_value)) {
            // Add the meta key and value to the first post.
            update_post_meta($first_post_id, $meta_key, $meta_value);
        }
    }
}

add_action('init', 'add_meta_to_first_cpt_post');
