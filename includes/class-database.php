
<?php

if (!defined('ABSPATH')) {
    exit;
}

class AirtableBonds_Database {
    
    private $wpdb;
    private $table_prefix;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_prefix = $wpdb->prefix . 'airtable_';
    }
    
    public function create_tables() {
        $this->create_entity_table();
        $this->create_access_request_table();
        $this->create_activity_table();
        $this->create_docgen_table();
    }
    
    private function create_entity_table() {
        $table_name = $this->table_prefix . 'entity';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            airtable_id varchar(20) NOT NULL,
            legal_name text,
            mail_addr_street text,
            mail_addr_city varchar(255),
            mail_addr_state varchar(10),
            mail_addr_zip varchar(20),
            surety_notes longtext,
            website varchar(500),
            type varchar(50),
            relation_type varchar(50),
            short_name varchar(255),
            incorp_year int,
            status varchar(50),
            relation_start date,
            relation_end date,
            is_hq tinyint(1) DEFAULT 0,
            ap_contact varchar(255),
            phys_addr_street text,
            phys_addr_city varchar(255),
            phys_addr_state varchar(10),
            phys_addr_zip varchar(20),
            phone_direct varchar(20),
            phone_cell varchar(20),
            phone_ext int,
            first_name varchar(255),
            last_name varchar(255),
            year_started int,
            year_ended int,
            email varchar(255),
            middle_name varchar(255),
            title varchar(255),
            gender varchar(10),
            surety_start date,
            principal_premium_rates text,
            surcharge decimal(5,2),
            dividend decimal(5,2),
            dividend_start date,
            bondability_date date,
            bondability_single decimal(15,2),
            bondability_aggregate decimal(15,2),
            fiscal_yearend varchar(3),
            appointment_status varchar(50),
            am_best_rating varchar(20),
            am_best_fsc varchar(20),
            dividend_premium_min decimal(10,2),
            premium_min decimal(10,2),
            premium_change_min decimal(10,2),
            premium_auto_close_under decimal(10,2),
            fax varchar(20),
            incorp_state varchar(10),
            phys_addr_county varchar(255),
            is_notary tinyint(1) DEFAULT 0,
            phys_addr_country varchar(100),
            name varchar(500),
            agency_auth_date date,
            agency_auth_single decimal(15,2),
            agency_auth_aggr decimal(15,2),
            public_entity varchar(20),
            acct_id varchar(100),
            notes longtext,
            ex_date date,
            ex_reason text,
            mail_addr_street_2 text,
            imported tinyint(1) DEFAULT 0,
            record_id varchar(20),
            loa_date date,
            most_recent_activity datetime,
            activity_count int DEFAULT 0,
            tags text,
            email_for_search varchar(255),
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY airtable_id (airtable_id),
            KEY email (email),
            KEY email_for_search (email_for_search),
            KEY type (type),
            KEY relation_type (relation_type)
        ) $this->get_charset_collate();";
        
        $this->execute_table_creation($sql);
    }
    
    private function create_access_request_table() {
        $table_name = $this->table_prefix . 'access_request';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            airtable_id varchar(20) NOT NULL,
            requestor_id varchar(20),
            req_email varchar(255),
            unique_id varchar(20),
            req_phone varchar(20),
            req_name varchar(500),
            company text,
            requestor_activities text,
            expires_on date,
            requested_on date,
            approved_activities text,
            producer_activities text,
            active tinyint(1) DEFAULT 1,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY airtable_id (airtable_id),
            UNIQUE KEY unique_id (unique_id),
            KEY req_email (req_email),
            KEY requestor_id (requestor_id),
            KEY expires_on (expires_on)
        ) $this->get_charset_collate();";
        
        $this->execute_table_creation($sql);
    }
    
    private function create_activity_table() {
        $table_name = $this->table_prefix . 'activity';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            airtable_id varchar(20) NOT NULL,
            request varchar(500),
            req_date date,
            req_phone varchar(20),
            req_address text,
            contract_with text,
            obligee_address text,
            owner_name varchar(500),
            job_name varchar(500),
            job_scope text,
            job_city varchar(255),
            start_date date,
            duration int,
            lds text,
            retainage decimal(5,2),
            subbed decimal(5,2),
            subs text,
            financing varchar(50),
            design tinyint(1) DEFAULT 0,
            special_instructions text,
            estimate decimal(15,2),
            bb_penalty text,
            bid_date date,
            originals int,
            req_company varchar(500),
            job_no varchar(100),
            contract_no varchar(100),
            contract_amount decimal(15,2),
            bid_results text,
            mt_sov_amount decimal(15,2),
            mt_bond_percent int,
            contract_date date,
            mt_acceptance_date date,
            obligee_name varchar(500),
            contract_with_address text,
            req_email text,
            job_state varchar(10),
            req_name varchar(500),
            req_id varchar(100),
            status varchar(50),
            principal_id varchar(20),
            obligee_id varchar(20),
            effective_date date,
            transaction_date date,
            rate_premium_id varchar(20),
            premium decimal(12,2),
            surety_id varchar(20),
            commission decimal(12,2),
            notes text,
            premium_prev decimal(12,2),
            commission_prev decimal(12,2),
            transaction_index int,
            csr_id varchar(20),
            attorney_in_fact_id varchar(20),
            witness_id varchar(20),
            notary_id varchar(20),
            principal_signator_id varchar(20),
            transaction_latest tinyint(1) DEFAULT 0,
            finish_date date,
            approved_by_id varchar(20),
            approved_on date,
            bid_bond_id varchar(20),
            bid_bond_for_id varchar(20),
            closed_date date,
            completion_date date,
            last_close_email_sent date,
            num_close_email_sent int DEFAULT 0,
            requestor_id varchar(20),
            type varchar(50),
            bond_description varchar(500),
            transaction_code varchar(10),
            penalty decimal(15,2),
            penalty_prev decimal(15,2),
            penalty_change decimal(15,2),
            surcharge decimal(5,2),
            commission_change decimal(12,2),
            renewing tinyint(1) DEFAULT 0,
            term int,
            letter_salutation varchar(255),
            consent_type varchar(255),
            delivery_method varchar(255),
            deliver_to_obligee tinyint(1) DEFAULT 0,
            obligee_email varchar(255),
            obligee_contact varchar(255),
            consent_amount decimal(15,2),
            row_flag tinyint(1) DEFAULT 0,
            eff_year int,
            eff_year_month varchar(7),
            description varchar(500),
            principal_name varchar(500),
            urgency varchar(255),
            imported tinyint(1) DEFAULT 0,
            letter_options text,
            letter_to text,
            date date,
            amount decimal(15,2),
            dividend decimal(5,2),
            prem_uncommitted decimal(12,2),
            comm_uncommitted decimal(12,2),
            prem_change_uncommitted decimal(12,2),
            comm_change_uncommitted decimal(12,2),
            rate_calc_error text,
            rate_calc_details text,
            rate_commission_id varchar(20),
            deleted_date date,
            bond_assign_note varchar(500),
            dividend_amount decimal(12,2),
            dividend_paid_on date,
            next_close_email_date date,
            bond_number_text text,
            req_first_name varchar(255),
            transactions_prior text,
            transaction_result varchar(500),
            transaction_deleted text,
            penalty_original decimal(15,2),
            premium_original decimal(12,2),
            commission_original decimal(12,2),
            contract_amount_original decimal(15,2),
            dividend_pending decimal(12,2),
            dividend_result text,
            finish_result text,
            rate_calc_short_desc varchar(500),
            rate_calc_pretty_all text,
            docgen_id varchar(20),
            kbrc_flag tinyint(1) DEFAULT 0,
            name_from_principal varchar(500),
            dividend_amount_prev decimal(12,2),
            contract_amount_prev decimal(15,2),
            rate_calc_pretty_premium text,
            producer_id varchar(20),
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY airtable_id (airtable_id),
            KEY principal_id (principal_id),
            KEY requestor_id (requestor_id),
            KEY status (status),
            KEY type (type),
            KEY effective_date (effective_date),
            KEY transaction_date (transaction_date),
            KEY req_email (req_email),
            KEY producer_id (producer_id)
        ) $this->get_charset_collate();";
        
        $this->execute_table_creation($sql);
    }
    
    private function create_docgen_table() {
        $table_name = $this->table_prefix . 'docgen';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            airtable_id varchar(20) NOT NULL,
            name varchar(500),
            notes text,
            activity_id varchar(20),
            run_date datetime,
            templates text,
            transaction int,
            created_time datetime,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY airtable_id (airtable_id),
            KEY activity_id (activity_id),
            KEY run_date (run_date)
        ) $this->get_charset_collate();";
        
        $this->execute_table_creation($sql);
    }
    
    private function execute_table_creation($sql) {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    private function get_charset_collate() {
        return $this->wpdb->get_charset_collate();
    }
    
    // Utility methods for data operations
    public function get_entity_by_email($email) {
        $table_name = $this->table_prefix . 'entity';
        $email_lower = strtolower(sanitize_email($email));
        
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM $table_name WHERE email_for_search = %s OR email = %s LIMIT 1",
            $email_lower, $email
        ));
    }
    
    public function get_access_request_by_uid($uid) {
        $table_name = $this->table_prefix . 'access_request';
        
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM $table_name WHERE unique_id = %s AND active = 1 LIMIT 1",
            $uid
        ));
    }
    
    public function get_activities_by_requestor($requestor_id) {
        $table_name = $this->table_prefix . 'activity';
        
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM $table_name WHERE requestor_id = %s ORDER BY transaction_date DESC, id DESC",
            $requestor_id
        ));
    }
    
    public function insert_access_request($data) {
        $table_name = $this->table_prefix . 'access_request';
        
        $result = $this->wpdb->insert($table_name, $data);
        
        if ($result) {
            return $this->wpdb->insert_id;
        }
        
        return false;
    }
    
    public function update_access_request($id, $data) {
        $table_name = $this->table_prefix . 'access_request';
        
        return $this->wpdb->update($table_name, $data, array('id' => $id));
    }
}
