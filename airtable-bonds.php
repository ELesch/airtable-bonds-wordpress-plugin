
<?php
/**
 * Plugin Name: Airtable Bonds Manager
 * Plugin URI: https://yourwebsite.com/airtable-bonds
 * Description: WordPress plugin for managing bonds with Airtable integration. Allows users to submit email addresses and view their bonds.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: airtable-bonds
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AIRTABLE_BONDS_VERSION', '1.0.0');
define('AIRTABLE_BONDS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AIRTABLE_BONDS_PLUGIN_URL', plugin_dir_url(__FILE__));

class AirtableBonds {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Load required files
        $this->load_dependencies();
        
        // Initialize components
        $this->init_hooks();
        
        // Load text domain
        load_plugin_textdomain('airtable-bonds', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    private function load_dependencies() {
        require_once AIRTABLE_BONDS_PLUGIN_DIR . 'includes/class-database.php';
        require_once AIRTABLE_BONDS_PLUGIN_DIR . 'includes/class-airtable.php';
        require_once AIRTABLE_BONDS_PLUGIN_DIR . 'includes/class-ajax.php';
    }
    
    private function init_hooks() {
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Register shortcodes
        add_shortcode('airtable_email_form', array($this, 'email_form_shortcode'));
        add_shortcode('airtable_bonds_display', array($this, 'bonds_display_shortcode'));
        
        // Initialize AJAX handlers
        new AirtableBonds_Ajax();
        
        // Add custom endpoint for bonds display with uid
        add_action('init', array($this, 'add_rewrite_rules'));
        add_action('template_redirect', array($this, 'handle_bonds_endpoint'));
    }
    
    public function enqueue_scripts() {
        wp_enqueue_style('airtable-bonds-style', AIRTABLE_BONDS_PLUGIN_URL . 'assets/css/style.css', array(), AIRTABLE_BONDS_VERSION);
        wp_enqueue_script('airtable-bonds-script', AIRTABLE_BONDS_PLUGIN_URL . 'assets/js/main.js', array('jquery'), AIRTABLE_BONDS_VERSION, true);
        
        // Localize script for AJAX
        wp_localize_script('airtable-bonds-script', 'airtable_bonds_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('airtable_bonds_nonce'),
            'loading_text' => __('Loading...', 'airtable-bonds'),
            'error_text' => __('An error occurred. Please try again.', 'airtable-bonds')
        ));
    }
    
    public function add_rewrite_rules() {
        add_rewrite_rule('^bonds/([^/]+)/?$', 'index.php?bonds_uid=$matches[1]', 'top');
        add_rewrite_tag('%bonds_uid%', '([^&]+)');
    }
    
    public function handle_bonds_endpoint() {
        $uid = get_query_var('bonds_uid');
        if ($uid) {
            include AIRTABLE_BONDS_PLUGIN_DIR . 'templates/bonds-display.php';
            exit;
        }
    }
    
    public function email_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'debug' => false
        ), $atts);
        
        ob_start();
        include AIRTABLE_BONDS_PLUGIN_DIR . 'templates/email-form.php';
        return ob_get_clean();
    }
    
    public function bonds_display_shortcode($atts) {
        $atts = shortcode_atts(array(
            'uid' => '',
            'debug' => false
        ), $atts);
        
        if (empty($atts['uid'])) {
            return '<p>' . __('Invalid access link.', 'airtable-bonds') . '</p>';
        }
        
        ob_start();
        include AIRTABLE_BONDS_PLUGIN_DIR . 'templates/bonds-display.php';
        return ob_get_clean();
    }
    
    public function activate() {
        // Create database tables
        $database = new AirtableBonds_Database();
        $database->create_tables();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set default options
        add_option('airtable_bonds_version', AIRTABLE_BONDS_VERSION);
    }
    
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

// Initialize the plugin
new AirtableBonds();
