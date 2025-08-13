
<?php

if (!defined('ABSPATH')) {
    exit;
}

class AirtableBonds_Airtable {
    
    private $api_key;
    private $base_id;
    private $api_url = 'https://api.airtable.com/v0/';
    
    public function __construct() {
        $this->api_key = get_option('airtable_bonds_api_key', '');
        $this->base_id = get_option('airtable_bonds_base_id', '');
    }
    
    public function create_access_request($email) {
        if (empty($this->api_key) || empty($this->base_id)) {
            return new WP_Error('missing_config', 'Airtable API configuration is missing.');
        }
        
        $entity = $this->find_entity_by_email($email);
        
        if (!$entity) {
            return new WP_Error('entity_not_found', 'No entity found with the provided email address.');
        }
        
        $url = $this->api_url . $this->base_id . '/AccessRequest';
        
        $data = array(
            'fields' => array(
                'Requestor' => array($entity['id']),
                'Req Email' => $email,
                'Requested On' => date('Y-m-d'),
                'Active' => true
            )
        );
        
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($data),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code !== 200) {
            return new WP_Error('airtable_error', 'Airtable API error: ' . $body);
        }
        
        $result = json_decode($body, true);
        
        if (isset($result['id'])) {
            // Store in local database
            $this->sync_access_request_to_local($result);
            
            return array(
                'success' => true,
                'airtable_id' => $result['id'],
                'unique_id' => $this->extract_unique_id($result['id'])
            );
        }
        
        return new WP_Error('unexpected_response', 'Unexpected response from Airtable.');
    }
    
    private function find_entity_by_email($email) {
        $url = $this->api_url . $this->base_id . '/Entity';
        $email_lower = strtolower($email);
        
        // Use filterByFormula to search for email
        $filter_formula = "OR({Email} = '$email', LOWER({Email}) = '$email_lower')";
        
        $response = wp_remote_get($url . '?' . http_build_query(array(
            'filterByFormula' => $filter_formula,
            'maxRecords' => 1
        )), array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        if (isset($result['records']) && !empty($result['records'])) {
            return $result['records'][0];
        }
        
        return false;
    }
    
    private function sync_access_request_to_local($airtable_record) {
        $database = new AirtableBonds_Database();
        
        $data = array(
            'airtable_id' => $airtable_record['id'],
            'req_email' => isset($airtable_record['fields']['Req Email']) ? $airtable_record['fields']['Req Email'] : '',
            'unique_id' => $this->extract_unique_id($airtable_record['id']),
            'requested_on' => isset($airtable_record['fields']['Requested On']) ? $airtable_record['fields']['Requested On'] : date('Y-m-d'),
            'expires_on' => isset($airtable_record['fields']['Expires On']) ? $airtable_record['fields']['Expires On'] : date('Y-m-d', strtotime('+3 months')),
            'active' => isset($airtable_record['fields']['Active']) ? $airtable_record['fields']['Active'] : 1
        );
        
        if (isset($airtable_record['fields']['Requestor']) && is_array($airtable_record['fields']['Requestor'])) {
            $data['requestor_id'] = $airtable_record['fields']['Requestor'][0];
        }
        
        return $database->insert_access_request($data);
    }
    
    private function extract_unique_id($airtable_id) {
        // Remove 'rec' prefix from Airtable ID to get UniqueID
        return substr($airtable_id, 3);
    }
    
    public function sync_entities_by_email($email) {
        // This method can be used to sync entity data from Airtable to local database
        $entity = $this->find_entity_by_email($email);
        
        if ($entity) {
            $database = new AirtableBonds_Database();
            $this->sync_entity_to_local($entity, $database);
            return true;
        }
        
        return false;
    }
    
    private function sync_entity_to_local($airtable_record, $database) {
        $table_name = $database->wpdb->prefix . 'airtable_entity';
        $fields = $airtable_record['fields'];
        
        $data = array(
            'airtable_id' => $airtable_record['id'],
            'legal_name' => isset($fields['Legal Name']) ? $fields['Legal Name'] : '',
            'email' => isset($fields['Email']) ? $fields['Email'] : '',
            'first_name' => isset($fields['First Name']) ? $fields['First Name'] : '',
            'last_name' => isset($fields['Last Name']) ? $fields['Last Name'] : '',
            'type' => isset($fields['Type']) ? $fields['Type'] : '',
            'status' => isset($fields['Status']) ? $fields['Status'] : '',
            'phone_direct' => isset($fields['Phone-Direct']) ? $fields['Phone-Direct'] : '',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        // Set email_for_search for case-insensitive searches
        if (!empty($data['email'])) {
            $data['email_for_search'] = strtolower($data['email']);
        }
        
        // Check if entity already exists
        $existing = $database->wpdb->get_row($database->wpdb->prepare(
            "SELECT id FROM $table_name WHERE airtable_id = %s",
            $airtable_record['id']
        ));
        
        if ($existing) {
            // Update existing record
            unset($data['created_at']);
            return $database->wpdb->update($table_name, $data, array('id' => $existing->id));
        } else {
            // Insert new record
            return $database->wpdb->insert($table_name, $data);
        }
    }
    
    public function get_activities_for_requestor($requestor_id) {
        if (empty($this->api_key) || empty($this->base_id)) {
            return array();
        }
        
        $url = $this->api_url . $this->base_id . '/Activity';
        
        // Filter activities by requestor
        $filter_formula = "FIND('$requestor_id', {Requestor})";
        
        $response = wp_remote_get($url . '?' . http_build_query(array(
            'filterByFormula' => $filter_formula,
            'maxRecords' => 100,
            'sort[0][field]' => 'Transaction Date',
            'sort[0][direction]' => 'desc'
        )), array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return array();
        }
        
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        if (isset($result['records'])) {
            return $result['records'];
        }
        
        return array();
    }
    
    public function test_connection() {
        if (empty($this->api_key) || empty($this->base_id)) {
            return false;
        }
        
        $url = $this->api_url . $this->base_id . '/Entity?maxRecords=1';
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        return $response_code === 200;
    }
}
