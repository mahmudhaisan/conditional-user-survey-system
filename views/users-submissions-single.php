<?php
ob_start(); // Start output buffering

// Retrieve the user_id from the query string
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// Check if a valid user_id is provided
if ($user_id > 0) {
    global $wpdb;

    // Query to fetch user's entries
    $table_entries = $wpdb->prefix . 'wpforms_entries';
    $entries = $wpdb->get_results(
        $wpdb->prepare("
        SELECT entry_id, form_id, fields, user_id
        FROM $table_entries
        WHERE user_id = %d
        ORDER BY entry_id DESC ", $user_id),
        ARRAY_A
    );

    if ($entries) {
?>
        <form method="post" action="">
            <?php foreach ($entries as $entry) {

                // print_r( $entry);
                $user_id = $entry['user_id'];
                $entry_id = $entry['entry_id'];
                $form_id = $entry['form_id'];
                $form_name = get_post_field('post_name', $form_id);


                $post_ids = get_post_ids_by_meta_value('selected_wpforms_form_id', $form_id);

                // var_dump($post_ids);
                $post_id = $post_ids[0];

                $entry_field_array = json_decode($entry['fields'], true);

                // Get the current approval status for the entry
                $current_approval_status = get_post_meta($post_id, 'form_approval_status', true);


                $submitted_form_meta_key = 'submitted_forms_meta_' . $user_id;
                // Get the existing submitted forms meta data if it exists
                $submitted_form_existing_data = get_post_meta($post_id, $submitted_form_meta_key, true);


               // print_r($submitted_form_existing_data);

            ?>
                <!-- Start a Bootstrap panel for each entry -->
                <div class="panel panel-default">
                    <div class="panel-heading"> Form Name: <?php echo $form_name; ?></div>
                    <div class="panel-body">
                        <div class="row">
                            <?php foreach ($entry_field_array as $field) { ?>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><?php echo $field['name']; ?></label>
                                        <?php if ($field['type'] === 'file-upload') { ?>
                                            <a href="<?php echo $field['value']; ?>" target="_blank">Link</a>
                                        <?php } else { ?>
                                            <p><?php echo $field['value']; ?></p>
                                        <?php } ?>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>

                        <!-- Dropdown for approving or disapproving the entry -->
                        <select name="approve_disapprove[<?php echo $entry_id; ?>]">
                            <option value="pending" <?php if ($submitted_form_existing_data && $submitted_form_existing_data['approval_status'] === 'pending') echo 'selected'; ?>>Pending</option>
                            <option value="approved" <?php if ($submitted_form_existing_data && $submitted_form_existing_data['approval_status'] === 'approved') echo 'selected'; ?>>Approved</option>
                        </select>

                        <!-- Hidden field to store the entry ID and form ID -->
                        <input type="hidden" name="entry_id[]" value="<?php echo $entry_id; ?>">
                        <input type="hidden" name="form_id[<?php echo $entry_id; ?>]" value="<?php echo $form_id; ?>">
                        <input type="hidden" name="user_id[<?php echo $entry_id; ?>]" value="<?php echo $user_id; ?>">
                        <input type="hidden" name="survey_id[<?php echo $entry_id; ?>]" value="<?php echo $post_id; ?>">

                        <!-- Submit button for each entry -->
                        <button type="submit" name="submit_entry_status_<?php echo $entry_id; ?>">Submit</button>
                    </div>
                </div>
            <?php } ?>
        </form>
<?php
        // Handle form submission for approving/disapproving here
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            foreach ($_POST['entry_id'] as $entry_id) {
                if (isset($_POST['submit_entry_status_' . $entry_id])) {

                    $selected_option = $_POST['approve_disapprove'][$entry_id];
                    $form_id = $_POST['form_id'][$entry_id];
                    $user_id = $_POST['user_id'][$entry_id];
                    $post_id = $_POST['survey_id'][$entry_id];


                    // Define the unique post meta key for the user
                    $meta_key = 'submitted_forms_meta_' . $user_id;

                    // Get the existing submitted forms meta data if it exists
                    $existing_data = get_post_meta($post_id, $meta_key, true);

                    // Prepare the data for the user
                    $user_data = array(
                        'user_id' => $user_id,
                        'approval_status' => $selected_option
                    );

                    if ($existing_data) {
                        // If data exists, update it by merging with the new data
                        $updated_data = array_merge($existing_data, $user_data);
                    } else {
                        // If data doesn't exist, use the new data
                        $updated_data = $user_data;
                    }

                    // Update the post meta with the updated data
                    update_post_meta($post_id, $meta_key, $updated_data);
                }
            }

            // Redirect to the same page after form submission using PHP's header function
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        }
    } else {
        echo 'No entries found for this user.';
    }
} else {
    echo 'Invalid user ID.';
}

// Flush the output buffer and send it to the browser
ob_end_flush();













?>