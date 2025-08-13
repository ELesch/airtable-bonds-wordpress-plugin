
/**
 * Airtable Bonds Plugin JavaScript
 * Handles form submissions, AJAX calls, and UI interactions
 */

(function($) {
    'use strict';
    
    // Plugin namespace
    window.AirtableBonds = window.AirtableBonds || {};
    
    // Configuration
    const config = {
        debounceDelay: 300,
        retryAttempts: 3,
        retryDelay: 1000
    };
    
    // Utility functions
    const utils = {
        // Debounce function for search input
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },
        
        // Email validation
        isValidEmail: function(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        },
        
        // HTML escaping
        escapeHtml: function(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },
        
        // Format currency
        formatCurrency: function(amount) {
            if (!amount || isNaN(amount)) return '0.00';
            return parseFloat(amount).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        },
        
        // Format date
        formatDate: function(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        },
        
        // Show notification
        showNotification: function(message, type = 'info', duration = 5000) {
            const notification = $(`
                <div class="airtable-notification airtable-notification-${type}">
                    <span>${message}</span>
                    <button class="airtable-notification-close">&times;</button>
                </div>
            `);
            
            $('body').append(notification);
            
            // Auto-remove after duration
            setTimeout(() => {
                notification.fadeOut(() => notification.remove());
            }, duration);
            
            // Manual close
            notification.find('.airtable-notification-close').on('click', () => {
                notification.fadeOut(() => notification.remove());
            });
        }
    };
    
    // Email Form Handler
    const EmailForm = {
        init: function() {
            this.bindEvents();
        },
        
        bindEvents: function() {
            $(document).on('submit', '#airtable-email-form', this.handleSubmit.bind(this));
        },
        
        handleSubmit: function(e) {
            e.preventDefault();
            
            const $form = $(e.target);
            const $submitBtn = $form.find('button[type="submit"]');
            const $btnText = $submitBtn.find('.btn-text');
            const $spinner = $submitBtn.find('.loading-spinner');
            const $message = $('#form-message');
            const email = $('#user-email').val().trim();
            
            // Reset previous messages
            $message.hide().removeClass('success error');
            
            // Validate email
            if (!email || !utils.isValidEmail(email)) {
                this.showMessage($message, 'Please enter a valid email address.', 'error');
                return;
            }
            
            // Show loading state
            this.setLoadingState($submitBtn, $btnText, $spinner, true);
            
            // Submit via AJAX
            this.submitEmail(email, $form, $submitBtn, $btnText, $spinner, $message);
        },
        
        submitEmail: function(email, $form, $submitBtn, $btnText, $spinner, $message, attempt = 1) {
            const self = this;
            
            $.ajax({
                url: airtable_bonds_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'submit_email',
                    email: email,
                    nonce: airtable_bonds_ajax.nonce
                },
                timeout: 30000,
                success: function(response) {
                    if (response.success) {
                        self.showMessage($message, response.data.message, 'success');
                        $form[0].reset();
                        
                        // Track successful submission
                        if (typeof gtag !== 'undefined') {
                            gtag('event', 'email_submitted', {
                                event_category: 'airtable_bonds',
                                event_label: 'success'
                            });
                        }
                    } else {
                        self.showMessage($message, response.data.message || 'An error occurred. Please try again.', 'error');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Email submission error:', textStatus, errorThrown);
                    
                    // Retry logic
                    if (attempt < config.retryAttempts) {
                        setTimeout(() => {
                            self.submitEmail(email, $form, $submitBtn, $btnText, $spinner, $message, attempt + 1);
                        }, config.retryDelay * attempt);
                        return;
                    }
                    
                    let errorMessage = 'Network error. Please check your connection and try again.';
                    if (textStatus === 'timeout') {
                        errorMessage = 'Request timed out. Please try again.';
                    }
                    
                    self.showMessage($message, errorMessage, 'error');
                },
                complete: function() {
                    self.setLoadingState($submitBtn, $btnText, $spinner, false);
                }
            });
        },
        
        setLoadingState: function($submitBtn, $btnText, $spinner, loading) {
            $submitBtn.prop('disabled', loading);
            if (loading) {
                $btnText.hide();
                $spinner.show();
            } else {
                $btnText.show();
                $spinner.hide();
            }
        },
        
        showMessage: function($message, text, type) {
            $message.removeClass('success error')
                   .addClass(type)
                   .text(text)
                   .show();
            
            // Scroll to message
            $('html, body').animate({
                scrollTop: $message.offset().top - 100
            }, 500);
        }
    };
    
    // Bonds Display Handler
    const BondsDisplay = {
        bonds: [],
        filteredBonds: [],
        
        init: function() {
            this.bindEvents();
            this.loadBonds();
        },
        
        bindEvents: function() {
            const debouncedFilter = utils.debounce(this.filterBonds.bind(this), config.debounceDelay);
            
            $(document).on('input', '#bonds-search', debouncedFilter);
            $(document).on('change', '#status-filter, #type-filter', this.filterBonds.bind(this));
            $(document).on('click', '#retry-load', this.loadBonds.bind(this));
        },
        
        loadBonds: function() {
            const uid = this.getUID();
            if (!uid) {
                this.showError('Invalid access link. Please use the link provided in your email.');
                return;
            }
            
            this.showLoading();
            this.fetchBonds(uid);
        },
        
        getUID: function() {
            // Get UID from various sources
            const urlParams = new URLSearchParams(window.location.search);
            const uid = urlParams.get('uid') || 
                       window.location.pathname.match(/\/bonds\/([^\/]+)/)?.[1] ||
                       $('#bonds-container').data('uid');
            
            return uid;
        },
        
        fetchBonds: function(uid, attempt = 1) {
            const self = this;
            
            $.ajax({
                url: airtable_bonds_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'load_bonds',
                    uid: uid,
                    nonce: airtable_bonds_ajax.nonce
                },
                timeout: 30000,
                success: function(response) {
                    if (response.success) {
                        self.bonds = response.data.bonds || [];
                        self.filteredBonds = [...self.bonds];
                        self.updateUserInfo(response.data.requestor_name, response.data.total_count);
                        self.displayBonds();
                        self.showBondsContainer();
                        
                        // Update debug panel if present
                        self.updateDebugData(response.data);
                        
                        // Track successful load
                        if (typeof gtag !== 'undefined') {
                            gtag('event', 'bonds_loaded', {
                                event_category: 'airtable_bonds',
                                event_label: 'success',
                                value: self.bonds.length
                            });
                        }
                    } else {
                        self.showError(response.data.message || 'Failed to load bonds.');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Bonds loading error:', textStatus, errorThrown);
                    
                    // Retry logic
                    if (attempt < config.retryAttempts) {
                        setTimeout(() => {
                            self.fetchBonds(uid, attempt + 1);
                        }, config.retryDelay * attempt);
                        return;
                    }
                    
                    let errorMessage = 'Network error. Please check your connection and try again.';
                    if (textStatus === 'timeout') {
                        errorMessage = 'Request timed out. Please try again.';
                    }
                    
                    self.showError(errorMessage);
                }
            });
        },
        
        showLoading: function() {
            $('#bonds-loading').show();
            $('#bonds-error').hide();
            $('#bonds-container').hide();
        },
        
        showError: function(message) {
            $('#bonds-loading').hide();
            $('#bonds-container').hide();
            $('#error-message').text(message);
            $('#bonds-error').show();
        },
        
        showBondsContainer: function() {
            $('#bonds-loading').hide();
            $('#bonds-error').hide();
            $('#bonds-container').show();
        },
        
        updateUserInfo: function(name, count) {
            if (name) {
                $('#user-name').text(name);
            }
            
            const countText = count > 0 ? `(${count} ${count === 1 ? 'bond' : 'bonds'})` : '(0 bonds)';
            $('#bonds-count').text(countText);
        },
        
        filterBonds: function() {
            const searchTerm = $('#bonds-search').val().toLowerCase().trim();
            const statusFilter = $('#status-filter').val().toLowerCase();
            const typeFilter = $('#type-filter').val().toLowerCase();
            
            this.filteredBonds = this.bonds.filter(bond => {
                const matchesSearch = !searchTerm || 
                    (bond.description && bond.description.toLowerCase().includes(searchTerm)) ||
                    (bond.principal_name && bond.principal_name.toLowerCase().includes(searchTerm)) ||
                    (bond.job_name && bond.job_name.toLowerCase().includes(searchTerm)) ||
                    (bond.obligee_name && bond.obligee_name.toLowerCase().includes(searchTerm));
                
                const matchesStatus = !statusFilter || 
                    (bond.status_class && bond.status_class.includes(statusFilter));
                
                const matchesType = !typeFilter || 
                    (bond.type && bond.type.toLowerCase() === typeFilter);
                
                return matchesSearch && matchesStatus && matchesType;
            });
            
            this.displayBonds();
        },
        
        displayBonds: function() {
            const $bondsList = $('#bonds-list');
            const $noBonds = $('#no-bonds');
            
            if (this.filteredBonds.length === 0) {
                $bondsList.empty();
                $noBonds.show();
                return;
            }
            
            $noBonds.hide();
            
            const html = this.filteredBonds.map(bond => this.buildBondCard(bond)).join('');
            $bondsList.html(html);
        },
        
        buildBondCard: function(bond) {
            const statusClass = bond.status_class || 'unknown';
            const description = utils.escapeHtml(bond.description || 'Untitled Bond');
            const status = utils.escapeHtml(bond.status || 'Unknown');
            const principalName = utils.escapeHtml(bond.principal_name || 'N/A');
            const type = utils.escapeHtml(bond.type || 'N/A');
            const amount = utils.formatCurrency(bond.amount);
            const premium = utils.formatCurrency(bond.premium);
            const effectiveDate = bond.effective_date ? utils.formatDate(bond.effective_date) : '';
            const jobName = utils.escapeHtml(bond.job_name || '');
            const obligeeName = utils.escapeHtml(bond.obligee_name || '');
            
            return `
                <div class="bond-card" data-bond-id="${bond.id}">
                    <div class="bond-header">
                        <h3 class="bond-title">${description}</h3>
                        <span class="bond-status status-${statusClass}">${status}</span>
                    </div>
                    
                    <div class="bond-details">
                        <div class="bond-row">
                            <div class="bond-col">
                                <label>Principal:</label>
                                <span>${principalName}</span>
                            </div>
                            <div class="bond-col">
                                <label>Type:</label>
                                <span>${type}</span>
                            </div>
                        </div>
                        
                        <div class="bond-row">
                            <div class="bond-col">
                                <label>Amount:</label>
                                <span class="amount">$${amount}</span>
                            </div>
                            <div class="bond-col">
                                <label>Premium:</label>
                                <span class="amount">$${premium}</span>
                            </div>
                        </div>
                        
                        ${effectiveDate ? `
                        <div class="bond-row">
                            <div class="bond-col">
                                <label>Effective Date:</label>
                                <span>${effectiveDate}</span>
                            </div>
                        </div>
                        ` : ''}
                        
                        ${jobName ? `
                        <div class="bond-row">
                            <div class="bond-col full-width">
                                <label>Job Name:</label>
                                <span>${jobName}</span>
                            </div>
                        </div>
                        ` : ''}
                        
                        ${obligeeName ? `
                        <div class="bond-row">
                            <div class="bond-col full-width">
                                <label>Obligee:</label>
                                <span>${obligeeName}</span>
                            </div>
                        </div>
                        ` : ''}
                    </div>
                </div>
            `;
        },
        
        updateDebugData: function(data) {
            const $debugData = $('#debug-data');
            if ($debugData.length === 0) return;
            
            $debugData.html(`
                <p><strong>Requestor Name:</strong> ${data.requestor_name || 'N/A'}</p>
                <p><strong>Total Bonds:</strong> ${data.total_count}</p>
                <p><strong>Load Time:</strong> ${new Date().toLocaleString()}</p>
                <details>
                    <summary>Raw Data</summary>
                    <pre>${JSON.stringify(data, null, 2)}</pre>
                </details>
            `);
        }
    };
    
    // Debug Panel Handler
    const DebugPanel = {
        init: function() {
            this.bindEvents();
        },
        
        bindEvents: function() {
            $(document).on('click', '#test-airtable', this.testAirtableConnection.bind(this));
        },
        
        testAirtableConnection: function() {
            const $btn = $('#test-airtable');
            const $result = $('#test-result');
            
            $btn.prop('disabled', true).text('Testing...');
            $result.empty();
            
            $.ajax({
                url: airtable_bonds_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'test_airtable',
                    nonce: airtable_bonds_ajax.nonce
                },
                timeout: 15000,
                success: function(response) {
                    const resultClass = response.success ? 'success' : 'error';
                    $result.html(`<p class="${resultClass}">${response.message}</p>`);
                },
                error: function() {
                    $result.html('<p class="error">Test failed - network error</p>');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Test Airtable Connection');
                }
            });
        }
    };
    
    // Main initialization
    $(document).ready(function() {
        // Initialize components based on what's present on the page
        if ($('#airtable-email-form').length) {
            EmailForm.init();
        }
        
        if ($('#bonds-container').length || $('.bonds-display').length) {
            BondsDisplay.init();
        }
        
        if ($('.debug-panel').length) {
            DebugPanel.init();
        }
        
        // Global error handler
        $(document).ajaxError(function(event, jqXHR, ajaxSettings, thrownError) {
            if (jqXHR.status === 0) {
                // Network error
                console.error('Network error in AJAX request:', ajaxSettings.url);
            } else if (jqXHR.status === 403) {
                // Permission denied
                utils.showNotification('Permission denied. Please refresh the page and try again.', 'error');
            } else if (jqXHR.status === 500) {
                // Server error
                console.error('Server error:', jqXHR.responseText);
            }
        });
    });
    
    // Expose public API
    window.AirtableBonds.EmailForm = EmailForm;
    window.AirtableBonds.BondsDisplay = BondsDisplay;
    window.AirtableBonds.DebugPanel = DebugPanel;
    window.AirtableBonds.utils = utils;
    
})(jQuery);
