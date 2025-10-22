/**
 * Dynamic Fields Loader
 * 
 * Dynamically loads and renders category-specific fields on ticket creation
 */

const DynamicFields = {
    currentCategoryId: null,
    currentFields: [],
    container: null,
    
    /**
     * Initialize dynamic fields
     */
    init: function() {
        this.container = document.getElementById('dynamicFieldsContainer');
        
        if (!this.container) {
            console.warn('Dynamic fields container not found');
            return;
        }
        
        // Listen to category selection
        const categorySelect = document.getElementById('category_id');
        if (categorySelect) {
            categorySelect.addEventListener('change', (e) => {
                this.loadCategoryFields(e.target.value);
            });
            
            // Load fields if category is already selected
            if (categorySelect.value) {
                this.loadCategoryFields(categorySelect.value);
            }
        }
    },
    
    /**
     * Load fields for a category
     */
    loadCategoryFields: function(categoryId) {
        if (!categoryId) {
            this.clearFields();
            return;
        }
        
        // Don't reload if same category
        if (categoryId == this.currentCategoryId) {
            return;
        }
        
        this.currentCategoryId = categoryId;
        
        // Show loading state
        this.showLoading();
        
        // Fetch fields from API
        fetch(`../api/get_category_fields.php?category_id=${categoryId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.currentFields = data.fields;
                    this.renderFields(data.fields);
                } else {
                    console.error('Error loading fields:', data.error);
                    this.showError('Fout bij laden velden');
                }
            })
            .catch(error => {
                console.error('Error loading fields:', error);
                this.showError('Fout bij laden velden');
            });
    },
    
    /**
     * Render all fields
     */
    renderFields: function(fields) {
        if (!fields || fields.length === 0) {
            this.clearFields();
            return;
        }
        
        let html = '<div class="card mt-3"><div class="card-header"><h6 class="mb-0">Aanvullende Informatie</h6></div><div class="card-body">';
        
        fields.forEach(field => {
            html += this.renderField(field);
        });
        
        html += '</div></div>';
        
        this.container.innerHTML = html;
        
        // Setup conditional logic
        this.setupConditionalLogic();
        
        // Setup validation
        this.setupValidation();
    },
    
    /**
     * Render a single field
     */
    renderField: function(field) {
        const fieldId = `field_${field.field_name}`;
        const isRequired = field.is_required == 1;
        
        let html = `<div class="mb-3" id="container_${fieldId}" data-field-name="${field.field_name}">`;
        
        // Label
        html += `<label for="${fieldId}" class="form-label">${this.escapeHtml(field.field_label)}`;
        if (isRequired) {
            html += ' <span class="text-danger">*</span>';
        }
        html += '</label>';
        
        // Field based on type
        switch (field.field_type) {
            case 'text':
            case 'email':
            case 'tel':
            case 'number':
                html += this.renderTextField(field, fieldId, isRequired);
                break;
            case 'textarea':
                html += this.renderTextareaField(field, fieldId, isRequired);
                break;
            case 'select':
                html += this.renderSelectField(field, fieldId, isRequired);
                break;
            case 'radio':
                html += this.renderRadioField(field, fieldId, isRequired);
                break;
            case 'checkbox':
                html += this.renderCheckboxField(field, fieldId, isRequired);
                break;
            case 'date':
                html += this.renderDateField(field, fieldId, isRequired);
                break;
        }
        
        // Help text
        if (field.help_text) {
            html += `<small class="form-text text-muted">${this.escapeHtml(field.help_text)}</small>`;
        }
        
        html += '</div>';
        
        return html;
    },
    
    /**
     * Render text field
     */
    renderTextField: function(field, fieldId, isRequired) {
        return `<input type="${field.field_type}" 
                       class="form-control" 
                       id="${fieldId}" 
                       name="fields[${field.field_name}]" 
                       placeholder="${this.escapeHtml(field.placeholder || '')}"
                       ${isRequired ? 'required' : ''}>`;
    },
    
    /**
     * Render textarea field
     */
    renderTextareaField: function(field, fieldId, isRequired) {
        return `<textarea class="form-control" 
                          id="${fieldId}" 
                          name="fields[${field.field_name}]" 
                          rows="3"
                          placeholder="${this.escapeHtml(field.placeholder || '')}"
                          ${isRequired ? 'required' : ''}></textarea>`;
    },
    
    /**
     * Render select field
     */
    renderSelectField: function(field, fieldId, isRequired) {
        let html = `<select class="form-select" 
                            id="${fieldId}" 
                            name="fields[${field.field_name}]"
                            ${isRequired ? 'required' : ''}>`;
        
        html += '<option value="">-- Selecteer --</option>';
        
        if (field.field_options) {
            const options = Array.isArray(field.field_options) 
                ? field.field_options 
                : JSON.parse(field.field_options);
            
            options.forEach(option => {
                html += `<option value="${this.escapeHtml(option)}">${this.escapeHtml(option)}</option>`;
            });
        }
        
        html += '</select>';
        return html;
    },
    
    /**
     * Render radio field
     */
    renderRadioField: function(field, fieldId, isRequired) {
        let html = '';
        
        if (field.field_options) {
            const options = Array.isArray(field.field_options) 
                ? field.field_options 
                : JSON.parse(field.field_options);
            
            options.forEach((option, index) => {
                const radioId = `${fieldId}_${index}`;
                html += `
                    <div class="form-check">
                        <input class="form-check-input" 
                               type="radio" 
                               name="fields[${field.field_name}]" 
                               id="${radioId}" 
                               value="${this.escapeHtml(option)}"
                               ${isRequired ? 'required' : ''}>
                        <label class="form-check-label" for="${radioId}">
                            ${this.escapeHtml(option)}
                        </label>
                    </div>
                `;
            });
        }
        
        return html;
    },
    
    /**
     * Render checkbox field
     */
    renderCheckboxField: function(field, fieldId, isRequired) {
        let html = '';
        
        if (field.field_options) {
            const options = Array.isArray(field.field_options) 
                ? field.field_options 
                : JSON.parse(field.field_options);
            
            options.forEach((option, index) => {
                const checkboxId = `${fieldId}_${index}`;
                html += `
                    <div class="form-check">
                        <input class="form-check-input" 
                               type="checkbox" 
                               name="fields[${field.field_name}][]" 
                               id="${checkboxId}" 
                               value="${this.escapeHtml(option)}">
                        <label class="form-check-label" for="${checkboxId}">
                            ${this.escapeHtml(option)}
                        </label>
                    </div>
                `;
            });
        }
        
        return html;
    },
    
    /**
     * Render date field
     */
    renderDateField: function(field, fieldId, isRequired) {
        return `<input type="date" 
                       class="form-control" 
                       id="${fieldId}" 
                       name="fields[${field.field_name}]"
                       ${isRequired ? 'required' : ''}>`;
    },
    
    /**
     * Setup conditional logic
     */
    setupConditionalLogic: function() {
        // Will be implemented in task 17.3
        console.log('Conditional logic setup (to be implemented)');
    },
    
    /**
     * Setup validation
     */
    setupValidation: function() {
        // Will be implemented in task 17.4
        console.log('Validation setup (to be implemented)');
    },
    
    /**
     * Clear all fields
     */
    clearFields: function() {
        if (this.container) {
            this.container.innerHTML = '';
        }
        this.currentCategoryId = null;
        this.currentFields = [];
    },
    
    /**
     * Show loading state
     */
    showLoading: function() {
        if (this.container) {
            this.container.innerHTML = `
                <div class="text-center text-muted py-3">
                    <div class="spinner-border spinner-border-sm" role="status">
                        <span class="visually-hidden">Laden...</span>
                    </div>
                    <span class="ms-2">Velden laden...</span>
                </div>
            `;
        }
    },
    
    /**
     * Show error message
     */
    showError: function(message) {
        if (this.container) {
            this.container.innerHTML = `
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i>
                    ${this.escapeHtml(message)}
                </div>
            `;
        }
    },
    
    /**
     * Escape HTML
     */
    escapeHtml: function(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    DynamicFields.init();
});
