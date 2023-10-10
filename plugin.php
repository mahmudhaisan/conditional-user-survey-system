<?php

/**
 * Plugin Name: Conditional Users Servey System
 * Plugin URI: https://github.com/mahmudhaisan/
 * Description: Conditional Users Servey System
 * Author: Mahmud haisan
 * Author URI: https://github.com/mahmudhaisan
 * Developer: Mahmud Haisan
 * Developer URI: https://github.com/mahmudhaisan
 * Text Domain: cussys
 * Domain Path: /languages
 * License: GNU General Public License v3.0
 * version: 0.10
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if (!defined('ABSPATH')) {
    die('are you cheating');
}

define('CUSSYS_DIR_PATH', plugin_dir_path(__FILE__));


function cussys_enqueue_custom_scripts()
{
    wp_enqueue_style('cussys-bootstrap-style', plugin_dir_url(__FILE__) . 'assets/bootstrap.css');
    wp_enqueue_style('cussys-style', plugin_dir_url(__FILE__) . 'assets/style.css');

    wp_enqueue_script('cussys-script', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0', true);
}
add_action('wp_enqueue_scripts', 'cussys_enqueue_custom_scripts');

function cussys_admin_enqueue_custom_scripts()
{
    wp_enqueue_style('cussys-bootstrap-admin-style', plugin_dir_url(__FILE__) . 'assets/bootstrap.css');
    // wp_enqueue_style('cussys-admin-style', plugin_dir_url(__FILE__) . 'assets/style.css');

}
add_action('admin_enqueue_scripts', 'cussys_admin_enqueue_custom_scripts');







function cussys_add_sales_admin_roles()
{
    // Sales Admin role
    if (!get_role('survey_applicant')) {
        add_role('survey_applicant', 'Applicant', array(
            'read' => true,
            // Add more capabilities as needed
        ));
    }

    // Sales Partner Admin role
    if (!get_role('survey_reqruiter')) {
        add_role('survey_reqruiter', 'Reqruiter', array(
            'read' => true,
            // Add more capabilities as needed
        ));
    }
}
add_action('init', 'cussys_add_sales_admin_roles');

// Add top-level menu page
function add_application_menu()
{
    add_menu_page(
        'Application Menu',              // Page title
        'Application Menu',              // Menu title
        'manage_options',                // Capability required to access
        'application-menu',              // Menu slug (unique identifier)
        'display_application_menu_page', // Callback function to display the page content
        'dashicons-admin-generic',       // Icon for the menu (use dashicons)
        30                               // Menu position
    );
}
add_action('admin_menu', 'add_application_menu');

// Add submenu page
function add_application_submenu()
{
    add_submenu_page(
        'application-menu',               // Parent menu slug (use the menu slug from add_menu_page)
        'Application Submissions',        // Page title
        'Submissions',                    // Menu title
        'manage_options',                 // Capability required to access
        'application-submissions',        // Menu slug (unique identifier)
        'display_application_submissions_page' // Callback function to display the page content
    );
}
add_action('admin_menu', 'add_application_submenu');

// Callback function to display the top-level menu page
function display_application_menu_page()
{
?>
    <div class="wrap">
        <h2>Application Menu</h2>
        <!-- Your top-level menu content goes here -->
    </div>
    <?php
}




// Callback function to display the submissions page
function display_application_submissions_page()
{
    $myListTable = new Custom_Data_List_Table();
    $myListTable->prepare_items();

    echo '<form method="post">';
    echo '<div class="wrap"><h2>Application Submissions</h2>';

    // Check if the user_id query parameter is set
    if (isset($_GET['user_id'])) {


        include plugin_dir_path(__FILE__) . '/views/users-submissions-single.php';

        // Add additional logic based on user_id if needed
    } else {
        // Default content when user_id is not provided
        $myListTable->display();
    }

    echo '</div>';
    echo '</form>';
}



include plugin_dir_path(__FILE__) . '/surveys-cpt/applicant-survey.php';
include plugin_dir_path(__FILE__) . '/surveys-cpt/recruiter-survey.php';
include plugin_dir_path(__FILE__) . '/functions.php';



