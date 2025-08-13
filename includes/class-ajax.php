
<?php

if (!defined('ABSPATH')) {
    exit;
}

class AirtableBonds_Ajax {
    
    public function __construct() {
        // AJAX handlers for logged-in and non-logged-in users
        add_action('wp_ajax_submit_email', array($this, 'handle_email_submission'));
        add_action('wp_ajax_nopriv_submit_email', array($this, 'handle_email_submission'));
        
        add_action('wp_ajax_load_bonds', array($this, 'handle_load_bonds'));
        add_action('wp_ajax_nopriv_load_bonds', array($this, 'handle_load_bonds'));
        
        add_action('wp_ajax_test_airtable', array($this, 'handle_test_airtable'));
    }
    
    public function handle_email_submission() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'airtable_bonds_nonce')) {
            wp_die('Security check failed');
        }
        
        $email = sanitize_email($_POST['email']);
        
        if (empty($email) || !is_email($email)) {
            wp_send_json_error(array(
                'message' => __('Please enter a valid email address.', 'airtable-bonds')
            ));
        }
        
        // Create Airtable instance
        $airtable = new AirtableBonds_Airtable();
        
        // Check if entity exists locally first
        $database = new AirtableBonds_Database();
        $entity = $database->get_entity_by_email($email);
        
        if (!$entity) {
            // Try to sync from Airtable
            $sync_result = $airtable->sync_entities_by_email($email);
            
            if (!$sync_result) {
                wp_send_json_error(array(
                    'message' => __('No account found with this email address. Please contact support.', 'airtable-bonds')
                ));
            }
            
            // Try to get entity again after sync
            $entity = $database->get_entity_by_email($email);
            
            if (!$entity) {
                wp_send_json_error(array(
                    'message' => __('Unable to access your account. Please contact support.', 'airtable-bonds')
                ));
            }
        }
        
        // Create access request in Airtable
        $result = $airtable->create_access_request($email);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message()
            ));
        }
        
        if ($result['success']) {
            wp_send_json_success(array(
                'message' => __('Access request created successfully! You will receive an email with your access link shortly.', 'airtable-bonds'),
                'uid' => $result['unique_id']
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to create access request. Please try again.', 'airtable-bonds')
            ));
        }
    }
    
    public function handle_load_bonds() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'airtable_bonds_nonce')) {
            wp_die('Security check failed');
        }
        
        $uid = sanitize_text_field($_POST['uid']);
        
        if (empty($uid)) {
            wp_send_json_error(array(
                'message' => __('Invalid access link.', 'airtable-bonds')
            ));
        }
        
        // Get access request
        $database = new AirtableBonds_Database();
        $access_request = $database->get_access_request_by_uid($uid);
        
        if (!$access_request) {
            wp_send_json_error(array(
                'message' => __('Access request not found or expired.', 'airtable-bonds')
            ));
        }
        
        // Check if expired
        if (strtotime($access_request->expires_on) < time()) {
            wp_send_json_error(array(
                'message' => __('This access link has expired. Please request a new one.', 'airtable-bonds')
            ));
        }
        
        // Get activities/bonds for this requestor
        $activities = $database->get_activities_by_requestor($access_request->requestor_id);
        
        if (empty($activities)) {
            // Try to sync from Airtable
            $airtable = new AirtableBonds_Airtable();
            $airtable_activities = $airtable->get_activities_for_requestor($access_request->requestor_id);
            
            // Process and sync activities (simplified for this example)
            $activities = $this->process_airtable_activities($airtable_activities);
        }
        
        // Format activities for display
        $formatted_activities = $this->format_activities_for_display($activities);
        
        wp_send_json_success(array(
            'bonds' => $formatted_activities,
            'requestor_name' => $access_request->req_name,
            'total_count' => count($formatted_activities)
        ));
    }
    
    public function handle_test_airtable() {
        // Admin only
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $airtable = new AirtableBonds_Airtable();
        $test_result = $airtable->test_connection();
        
        wp_send_json(array(
            'success' => $test_result,
            'message' => $test_result ? 'Connection successful' : 'Connection failed'
        ));
    }
    
    private function process_airtable_activities($airtable_activities) {
        // This is a simplified version - in a production system,
        // you would sync all the activities to the local database
        $processed = array();
        
        foreach ($airtable_activities as $activity) {
            $processed[] = (object) array(
                'airtable_id' => $activity['id'],
                'description' => isset($activity['fields']['Description']) ? $activity['fields']['Description'] : '',
                'principal_name' => isset($activity['fields']['Principal Name']) ? $activity['fields']['Principal Name'] : '',
                'status' => isset($activity['fields']['Status']) ? $activity['fields']['Status'] : '',
                'amount' => isset($activity['fields']['Amount']) ? $activity['fields']['Amount'] : 0,
                'effective_date' => isset($activity['fields']['Effective Date']) ? $activity['fields']['Effective Date'] : '',
                'type' => isset($activity['fields']['Type']) ? $activity['fields']['Type'] : '',
                'premium' => isset($activity['fields']['Premium']) ? $activity['fields']['Premium'] : 0,
                'job_name' => isset($activity['fields']['Job Name']) ? $activity['fields']['Job Name'] : '',
                'obligee_name' => isset($activity['fields']['Obligee Name']) ? $activity['fields']['Obligee Name'] : ''
            );
        }
        
        return $processed;
    }
    
    private function format_activities_for_display($activities) {
        $formatted = array();
        
        foreach ($activities as $activity) {
            $formatted[] = array(
                'id' => $activity->airtable_id,
                'description' => $activity->description,
                'principal_name' => $activity->principal_name,
                'status' => $activity->status,
                'amount' => number_format($activity->amount, 2),
                'effective_date' => $activity->effective_date ? date('M j, Y', strtotime($activity->effective_date)) : '',
                'type' => $activity->type,
                'premium' => number_format($activity->premium, 2),
                'job_name' => $activity->job_name,
                'obligee_name' => $activity->obligee_name,
                'status_class' => strtolower(str_replace(' ', '-', $activity->status))
            );
        }
        
        return $formatted;
    }
}
