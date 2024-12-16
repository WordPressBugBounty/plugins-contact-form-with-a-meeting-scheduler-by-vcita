<?php
/**
* Settings
* Activates module using ajax call
* @since 0.1.0
*/
function ls_remote_activate_module() {
    // Check if the request is an AJAX request
    if ( ! defined('DOING_AJAX') || ! DOING_AJAX ) {
        wp_die('Unauthorized request');
    }
    
    // Validate nonce for security
    $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
    if ( ! wp_verify_nonce($nonce, 'activate-module') ) {
        wp_die('Unauthorized attempt');
    }
    
    // Initialize response array
    $return = array('status' => false);
    
    // Sanitize module name input
    $module_name = isset($_POST['module_name']) ? sanitize_text_field($_POST['module_name']) : '';
    
    if ( ! empty($module_name) ) {
        // Activate the module
        ls_activate_module($module_name);
        
        // Get module data
        $module_data = ls_get_module_data($module_name);
        
        // Check if module data is retrieved successfully
        if ($module_data) {
            $return['module_slug'] = $module_data['slug'];
            $return['status'] = true;
        }
    }
    
    // Set the content type for JSON response
    header("Content-Type: application/json");
    echo json_encode($return);
    
    // Exit the script
    exit();
}


 // Defines the connection between the ajax request and the wordpress backend function
 add_action("wp_ajax_activate-module", "ls_remote_activate_module");

 // This is for users who are not logged in
 //add_action("wp_ajax_nopriv_request-post", "ls_remote_activate_module");


/**
* Settings
* Creates a custom page for a page
* @since 0.1.0
*/
function ls_remote_create_module_page() {
    // Check if the request is an AJAX request
    if ( ! defined('DOING_AJAX') || ! DOING_AJAX ) {
        wp_die('Unauthorized request');
    }
    
    // Validate nonce for security
    $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
    if ( ! wp_verify_nonce($nonce, 'module-page') ) {
        wp_die('Unauthorized attempt');
    }
    
    $page_id = false;
    
    // Sanitize inputs
    $page_title = isset($_POST['page_title']) ? sanitize_text_field($_POST['page_title']) : '';
    $page_content = isset($_POST['page_content']) ? wp_kses_post($_POST['page_content']) : '';
    $module_name = isset($_POST['module_name']) ? sanitize_text_field($_POST['module_name']) : '';
    
    // Check if required fields are not empty
    if ( ! empty($page_title) && ! empty($page_content) && ! empty($module_name) ) {
        $helpers = new ls_helpers();
        
        // Add a new WordPress page
        $page_id = $helpers->add_wp_page($page_title, $page_content);
        
        // If page is created, update the module settings
        if ( $page_id ) {
            ls_set_settings(array(
                'modules' => array(
                    $module_name => array(
                        'custom_page_id' => $page_id
                    )
                )
            ));
        }
    }
    
    // Return the page ID as a JSON response
    header("Content-Type: application/json");
    echo json_encode(array('page_id' => $page_id));
    
    // Exit the script
    exit();
}


 // Defines the connection between the ajax request and the wordpress backend function
 add_action("wp_ajax_create-module-page", "ls_remote_create_module_page");

 // This is for users who are not logged in
 //add_action("wp_ajax_nopriv_request-post", "ls_remote_activate_module");

/**
* Settings
* Removes custom page
* @since 0.1.0
*/
function ls_remote_remove_module_page() {
    // Check if the request is an AJAX request
    if ( ! defined('DOING_AJAX') || ! DOING_AJAX ) {
        wp_die('Unauthorized request');
    }
    
    // Validate nonce for security
    $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
    if ( ! wp_verify_nonce($nonce, 'module-page') ) {
        wp_die('Unauthorized attempt');
    }
    
    // Initialize response
    $response = array('success' => false, 'page_id' => false);
    
    // Sanitize inputs
    $page_id = isset($_POST['page_id']) ? intval($_POST['page_id']) : 0;
    $module_name = isset($_POST['module_name']) ? sanitize_text_field($_POST['module_name']) : '';
    
    // Check if required fields are not empty
    if ( $page_id > 0 && ! empty($module_name) ) {
        // Update module settings to remove custom page ID
        ls_set_settings(array(
            'modules' => array(
                $module_name => array(
                    'custom_page_id' => false
                )
            )
        ));
        
        // Delete the page permanently, skipping the trash
        $deleted = wp_delete_post($page_id, true);
        
        // Check if the deletion was successful
        if ( $deleted ) {
            $response['success'] = true;
            $response['page_id'] = $page_id;
        }
    }
    
    // Return the response as a JSON object
    header("Content-Type: application/json");
    echo json_encode($response);
    
    // Exit the script
    exit();
}


 // Defines the connection between the ajax request and the wordpress backend function
 add_action("wp_ajax_remove-module-page", "ls_remote_remove_module_page");

 // This is for users who are not logged in
 //add_action("wp_ajax_nopriv_request-post", "ls_remote_activate_module");

 /**
 * Settings
 * Activates module using ajax call
 * @since 0.1.0
 */
function ls_update_livesite_status() {
    // Check if the request is an AJAX request
    if ( ! defined('DOING_AJAX') || ! DOING_AJAX ) {
        wp_die('Unauthorized request');
    }
    
    // Validate nonce for security
    $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
    if ( ! wp_verify_nonce($nonce, 'module-page') ) {
        wp_die('Unauthorized attempt');
    }
    
    // Initialize response
    $response = array('success' => false);
    
    // Sanitize and validate input
    $show_livesite = isset($_POST['show_livesite']) ? filter_var($_POST['show_livesite'], FILTER_VALIDATE_BOOLEAN) : null;
    
    // Check if the input is valid
    if ( ! is_null($show_livesite) ) {
        // Update settings
        ls_set_settings(array(
            'modules' => array(
                'livesite_widget' => array(
                    'show_livesite' => $show_livesite
                )
            )
        ));
        
        // Update response status
        $response['success'] = true;
    }
    
    // Return the response as a JSON object
    header("Content-Type: application/json");
    echo json_encode($response);
    
    // Exit the script
    exit();
}


  // Defines the connection between the ajax request and the wordpress backend function
  add_action("wp_ajax_update-livesite-status", "ls_update_livesite_status");

?>
