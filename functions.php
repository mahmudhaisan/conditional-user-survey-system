<?php


if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Custom_Data_List_Table extends WP_List_Table
{
    public function __construct()
    {
        parent::__construct([
            'singular' => 'custom_data',
            'plural' => 'custom_data_items',
            'ajax' => false,
        ]);
    }

    public function get_columns()
    {
        return [
            'cb' => '<input type="checkbox" />',
            'user_id' => 'User ID',
            'user_name' => 'User Name',
        ];
    }

    public function prepare_items()
    {
        $data = $this->get_data(); // Implement this method to retrieve your data

        // Define column headers
        $this->_column_headers = [$this->get_columns(), [], []];

        // Set the data for the table
        $this->items = $data;

        // Process bulk actions
        $this->process_bulk_action();

        // Define pagination arguments
        $per_page = 10; // Number of items per page
        $current_page = $this->get_pagenum();
        $total_items = count($data);

        // Create pagination
        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
        ]);

        // Slice the data to display the current page's items
        $this->items = array_slice($data, (($current_page - 1) * $per_page), $per_page);
    }

    public function column_default($item, $column_name)
    {

        if ($column_name === 'user_name') {
            // Make the form name column clickable
            return sprintf(
                '<a href="%s">%s</a>',
                admin_url('admin.php?page=application-submissions&user_id=' . $item['user_id']),
                $item['user_name']
            );
        }

        return $item[$column_name];
    }

    public function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="id[]" value="%s" />',
            $item['user_id']
        );
    }

    public function get_bulk_actions()
    {
        $actions = [
            'delete' => 'Delete',
            // Add more bulk actions as needed
        ];
        return $actions;
    }

    public function process_bulk_action()
    {
        if ('delete' === $this->current_action()) {
            $ids = isset($_POST['id']) ? $_POST['id'] : array();

            if (!empty($ids)) {
                global $wpdb;
                $table_name = $wpdb->prefix . 'wp_wpforms_entries';

                foreach ($ids as $id) {
                    // Implement your deletion logic here
                    $wpdb->delete($table_name, array('entry_id' => $id), array('%d'));
                }

                // Get the current page's URL
                $current_url = add_query_arg($_SERVER['QUERY_STRING'], '', get_permalink());

                // Redirect to the current page, effectively reloading it
                wp_redirect($current_url);
                exit;
            }
        }
    }

    private function get_data()
    {
        global $wpdb;
    
        $table_entries = $wpdb->prefix . 'wpforms_entries';
    
        $data = $wpdb->get_results("
        SELECT 
            DISTINCT e.user_id,
            u.user_login AS user_name
        FROM $table_entries e
        LEFT JOIN $wpdb->users u ON e.user_id = u.ID
        WHERE e.user_id > 0
        ORDER BY e.user_id DESC", ARRAY_A);
    
        return $data;
    }
    
}


function get_post_ids_by_meta_value($meta_key, $meta_value) {
    global $wpdb;

    $sql = $wpdb->prepare(
        "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s  ORDER BY post_id DESC",
        $meta_key,
        $meta_value
    );

    $post_ids = $wpdb->get_col($sql);
    return $post_ids;
}