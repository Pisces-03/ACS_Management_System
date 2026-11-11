// Common JavaScript utilities for ACS Management System

// Security: Check if user is authenticated before making API calls
const checkAuthentication = function() {
    // Check if we have the CSRF token (indicates we're in an authenticated session)
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) {
        window.location.href = '/login';
        return false;
    }
    return true;
};

// Common utility functions
const Utils = {
    // Show loading spinner
    showLoading: function(elementId = 'loading') {
        const element = document.getElementById(elementId);
        if (element) {
            element.style.display = 'block';
        }
    },

    // Hide loading spinner
    hideLoading: function(elementId = 'loading') {
        const element = document.getElementById(elementId);
        if (element) {
            element.style.display = 'none';
        }
    },

    // Enhanced API call with authentication check
    makeApiCall: function(url, options = {}) {
        if (!checkAuthentication()) {
            return Promise.reject(new Error('Authentication required'));
        }

        // Add CSRF token to all requests
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        const defaultOptions = {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        const mergedOptions = { ...defaultOptions, ...options };
        
        // Merge headers properly
        if (options.headers) {
            mergedOptions.headers = { ...defaultOptions.headers, ...options.headers };
        }

        return fetch(url, mergedOptions)
            .then(response => {
                if (response.status === 401) {
                    window.location.href = '/login';
                    throw new Error('Unauthorized');
                }
                if (response.status === 403) {
                    throw new Error('Access denied');
                }
                return response;
            });
    },

    // Show alert message
    showAlert: function(message, type = 'info', duration = 5000) {
        // Remove existing alerts
        const existingAlerts = document.querySelectorAll('.custom-alert');
        existingAlerts.forEach(alert => alert.remove());

        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} custom-alert`;
        alertDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            animation: slideIn 0.3s ease;
        `;
        alertDiv.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span>${message}</span>
                <button type="button" style="background: none; border: none; font-size: 1.2rem; cursor: pointer;" onclick="this.parentElement.parentElement.remove()">×</button>
            </div>
        `;

        document.body.appendChild(alertDiv);

        // Auto remove after duration
        if (duration > 0) {
            setTimeout(() => {
                if (alertDiv.parentElement) {
                    alertDiv.remove();
                }
            }, duration);
        }
    },

    // Format date
    formatDate: function(dateString, includeTime = false) {
        const date = new Date(dateString);
        const options = {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        };
        
        if (includeTime) {
            options.hour = '2-digit';
            options.minute = '2-digit';
        }
        
        return date.toLocaleDateString('en-US', options);
    },

    // Format currency
    formatCurrency: function(amount) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(amount);
    },

    // Validate email
    validateEmail: function(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    },

    // Validate password strength
    validatePassword: function(password) {
        const minLength = password.length >= 8;
        const hasUpper = /[A-Z]/.test(password);
        const hasLower = /[a-z]/.test(password);
        const hasNumber = /\d/.test(password);
        const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);

        return {
            valid: minLength && hasUpper && hasLower && hasNumber && hasSpecial,
            strength: minLength + hasUpper + hasLower + hasNumber + hasSpecial,
            issues: {
                minLength,
                hasUpper,
                hasLower,
                hasNumber,
                hasSpecial
            }
        };
    },

    // Sanitize HTML to prevent XSS
    sanitizeHTML: function(str) {
        const temp = document.createElement('div');
        temp.textContent = str;
        return temp.innerHTML;
    },

    // Generate random ID
    generateId: function(length = 8) {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        let result = '';
        for (let i = 0; i < length; i++) {
            result += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return result;
    },

    // Debounce function
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

    // Format file size
    formatFileSize: function(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
};

// Form validation utilities
const FormValidator = {
    // Validate required fields
    validateRequired: function(formElement) {
        const requiredFields = formElement.querySelectorAll('[required]');
        let isValid = true;

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                this.showFieldError(field, 'This field is required');
                isValid = false;
            } else {
                this.clearFieldError(field);
            }
        });

        return isValid;
    },

    // Show field error
    showFieldError: function(field, message) {
        this.clearFieldError(field);
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error text-danger mt-1';
        errorDiv.textContent = message;
        
        field.parentNode.appendChild(errorDiv);
        field.classList.add('is-invalid');
    },

    // Clear field error
    clearFieldError: function(field) {
        const existingError = field.parentNode.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
        field.classList.remove('is-invalid');
    },

    // Validate form on submit
    setup: function(formElement, validationRules = {}) {
        formElement.addEventListener('submit', (e) => {
            e.preventDefault();
            
            let isValid = this.validateRequired(formElement);
            
            // Custom validation rules
            Object.keys(validationRules).forEach(fieldName => {
                const field = formElement.querySelector(`[name="${fieldName}"]`);
                if (field) {
                    const rule = validationRules[fieldName];
                    if (rule.validate && !rule.validate(field.value)) {
                        this.showFieldError(field, rule.message);
                        isValid = false;
                    }
                }
            });

            if (isValid) {
                // Form is valid, handle submission
                const submitHandler = formElement.getAttribute('data-submit-handler');
                if (submitHandler && window[submitHandler]) {
                    window[submitHandler](e);
                }
            }
        });

        // Real-time validation
        const fields = formElement.querySelectorAll('input, select, textarea');
        fields.forEach(field => {
            field.addEventListener('blur', () => {
                if (field.hasAttribute('required') && !field.value.trim()) {
                    this.showFieldError(field, 'This field is required');
                } else {
                    this.clearFieldError(field);
                }
            });
        });
    }
};

// Data table utilities
const DataTable = {
    // Create a sortable table
    create: function(containerId, data, columns, options = {}) {
        const container = document.getElementById(containerId);
        if (!container) return;

        const table = document.createElement('table');
        table.className = 'table table-striped';
        
        // Create header
        const thead = document.createElement('thead');
        const headerRow = document.createElement('tr');
        
        columns.forEach(column => {
            const th = document.createElement('th');
            th.textContent = column.title;
            th.style.cursor = column.sortable !== false ? 'pointer' : 'default';
            
            if (column.sortable !== false) {
                th.addEventListener('click', () => {
                    this.sort(table, column.key, th);
                });
            }
            
            headerRow.appendChild(th);
        });
        
        thead.appendChild(headerRow);
        table.appendChild(thead);
        
        // Create body
        const tbody = document.createElement('tbody');
        this.populateRows(tbody, data, columns);
        table.appendChild(tbody);
        
        container.innerHTML = '';
        container.appendChild(table);
        
        return table;
    },

    // Populate table rows
    populateRows: function(tbody, data, columns) {
        tbody.innerHTML = '';
        
        data.forEach(row => {
            const tr = document.createElement('tr');
            
            columns.forEach(column => {
                const td = document.createElement('td');
                
                if (column.render) {
                    td.innerHTML = column.render(row[column.key], row);
                } else {
                    td.textContent = row[column.key] || '';
                }
                
                tr.appendChild(td);
            });
            
            tbody.appendChild(tr);
        });
    },

    // Sort table
    sort: function(table, key, headerElement) {
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        
        const isAsc = headerElement.dataset.sortDirection !== 'asc';
        headerElement.dataset.sortDirection = isAsc ? 'asc' : 'desc';
        
        // Clear other sort indicators
        table.querySelectorAll('th').forEach(th => {
            if (th !== headerElement) {
                delete th.dataset.sortDirection;
            }
        });
        
        rows.sort((a, b) => {
            const aValue = a.cells[Array.from(headerElement.parentNode.children).indexOf(headerElement)].textContent;
            const bValue = b.cells[Array.from(headerElement.parentNode.children).indexOf(headerElement)].textContent;
            
            if (isAsc) {
                return aValue.localeCompare(bValue);
            } else {
                return bValue.localeCompare(aValue);
            }
        });
        
        rows.forEach(row => tbody.appendChild(row));
    }
};

// Initialize common functionality when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Add CSS for animations
    if (!document.querySelector('#utils-styles')) {
        const style = document.createElement('style');
        style.id = 'utils-styles';
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            
            .field-error {
                font-size: 0.875rem;
                margin-top: 0.25rem;
            }
            
            .is-invalid {
                border-color: #dc3545 !important;
                box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
            }
            
            .table th[data-sort-direction="asc"]:after {
                content: " ↑";
                color: #4CAF50;
            }
            
            .table th[data-sort-direction="desc"]:after {
                content: " ↓";
                color: #4CAF50;
            }
        `;
        document.head.appendChild(style);
    }

    // Auto-setup forms with validation
    const forms = document.querySelectorAll('form[data-validate="true"]');
    forms.forEach(form => {
        FormValidator.setup(form);
    });
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { Utils, API, Auth, FormValidator, DataTable };
}
