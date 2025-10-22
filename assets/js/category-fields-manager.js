/**
 * Category Fields Manager
 * 
 * Handles the field management interface for category fields
 */

let currentCategoryId = null;
let currentFields = [];
let fieldManagementModal = null;
let sortable = null;

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    fieldManagementModal = new bootstrap.Modal(document.getElementById('fieldManagementModal'));
    
    // Setup form submission
    document.getElementById('fieldForm').addEventListener('submit', handleFieldFormSubmit);
    
    // Setup preview updates
    setupPreviewListeners();
});

/**
 * Open field management modal for a category
 */
function manageFields(categoryId, categoryName) {
    currentCategoryId = categoryId;
    
    // Set modal title
    document.getElementById('modalCategoryName').textContent = categoryName;
    
    // Reset form
    hideFieldForm();
    
    // Show modal
    fieldManagementModal.show();
    
    // Load fields
    loadCategoryFields(categoryId);
}

/**
 * Load fields for a category
 */
function loadCategoryFields(categoryId) {
    const container = document.getElementById('fieldsListContainer');
    container.innerHTML = `
        <div class="text-center text-muted py-4">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Laden...</span>
            </div>
            <p class="mt-2">Velden laden...</p>
        </div>
    `;
    
    fetch(`../api/get_category_fields.php?category_id=${categoryId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentFields = data.fields;
                renderFieldsList(data.fields);
            } else {
                showError('Fout bij laden velden: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error loading fields:', error);
            showError('Fout bij laden velden');
        });
}

/**
 * Render fields list
 */
function renderFieldsList(fields) {
    const container = document.getElementById('fieldsListContainer');
    
    if (fields.length === 0) {
        container.innerHTML = `
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                Nog geen velden geconfigureerd voor deze categorie.
            </div>
        `;
        return;
    }
    
    let html = '<div class="list-group" id="fieldsList">';
    
    fields.forEach(field => {
        const isActive = field.is_active == 1;
        const isRequired = field.is_required == 1;
        
        html += `
            <div class="list-group-item ${!isActive ? 'bg-light' : ''}" data-field-id="${field.field_id}" data-field-order="${field.field_order}">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center mb-1">
                            <i class="bi bi-grip-vertical text-muted me-2 drag-handle" style="cursor: move;"></i>
                            <h6 class="mb-0">${escapeHtml(field.field_label)}</h6>
                            ${isRequired ? '<span class="badge bg-danger ms-2">Verplicht</span>' : ''}
                            ${!isActive ? '<span class="badge bg-secondary ms-2">Inactief</span>' : ''}
                        </div>
                        <small class="text-muted">
                            <code>${escapeHtml(field.field_name)}</code> • 
                            ${getFieldTypeLabel(field.field_type)}
                        </small>
                    </div>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" onclick="editField(${field.field_id})" title="Bewerken">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-outline-danger" onclick="deleteField(${field.field_id}, '${escapeHtml(field.field_label)}')" title="Verwijderen">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    container.innerHTML = html;
    
    // Initialize drag & drop
    initializeSortable();
}

/**
 * Initialize Sortable.js for drag & drop
 */
function initializeSortable() {
    const fieldsList = document.getElementById('fieldsList');
    
    if (!fieldsList) return;
    
    // Destroy existing sortable instance if any
    if (sortable) {
        sortable.destroy();
    }
    
    sortable = new Sortable(fieldsList, {
        animation: 150,
        handle: '.drag-handle',
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        dragClass: 'sortable-drag',
        onEnd: function(evt) {
            // Get new order
            const items = fieldsList.querySelectorAll('.list-group-item');
            const newOrder = {};
            
            items.forEach((item, index) => {
                const fieldId = item.getAttribute('data-field-id');
                newOrder[fieldId] = index + 1;
            });
            
            // Save new order
            saveFieldOrder(newOrder);
        }
    });
}

/**
 * Save field order to server
 */
function saveFieldOrder(fieldOrder) {
    const formData = new FormData();
    const csrfInput = document.getElementById('global_csrf_token') || document.querySelector('input[name="csrf_token"]');
    if (!csrfInput) {
        showError('CSRF token not found');
        return;
    }
    formData.append('csrf_token', csrfInput.value);
    formData.append('field_order', JSON.stringify(fieldOrder));
    
    // Construct API URL - simpler approach
    const apiUrl = '../api/update_field_order.php';
    
    console.log('Saving field order to:', apiUrl);
    console.log('Field order:', fieldOrder);
    
    fetch(apiUrl, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.text();
    })
    .then(text => {
        console.log('Response text:', text);
        try {
            const data = JSON.parse(text);
            return data;
        } catch (e) {
            console.error('Failed to parse JSON:', text);
            throw new Error('Invalid JSON response from server');
        }
    })
    .then(data => {
        if (data.success) {
            showSuccess('Volgorde opgeslagen');
            // Update current fields array with new order
            currentFields.forEach(field => {
                if (fieldOrder[field.field_id]) {
                    field.field_order = fieldOrder[field.field_id];
                }
            });
        } else {
            showError('Fout bij opslaan volgorde: ' + data.error);
            // Reload fields to restore original order
            loadCategoryFields(currentCategoryId);
        }
    })
    .catch(error => {
        console.error('Error saving field order:', error);
        showError('Fout bij opslaan volgorde');
        // Reload fields to restore original order
        loadCategoryFields(currentCategoryId);
    });
}

/**
 * Show field form for create or edit
 */
function showFieldForm(action, fieldData = null) {
    document.getElementById('fieldFormPlaceholder').style.display = 'none';
    document.getElementById('fieldFormContainer').style.display = 'block';
    
    const form = document.getElementById('fieldForm');
    form.reset();
    
    document.getElementById('formAction').value = action;
    document.getElementById('formCategoryId').value = currentCategoryId;
    
    if (action === 'create') {
        document.getElementById('formTitle').textContent = 'Veld Toevoegen';
        document.getElementById('formFieldId').value = '';
    } else if (action === 'update' && fieldData) {
        document.getElementById('formTitle').textContent = 'Veld Bewerken';
        document.getElementById('formFieldId').value = fieldData.field_id;
        document.getElementById('fieldName').value = fieldData.field_name;
        document.getElementById('fieldLabel').value = fieldData.field_label;
        document.getElementById('fieldType').value = fieldData.field_type;
        document.getElementById('fieldPlaceholder').value = fieldData.placeholder || '';
        document.getElementById('fieldHelpText').value = fieldData.help_text || '';
        document.getElementById('fieldRequired').checked = fieldData.is_required == 1;
        
        // Handle field options
        if (fieldData.field_options) {
            const options = Array.isArray(fieldData.field_options) 
                ? fieldData.field_options 
                : JSON.parse(fieldData.field_options);
            document.getElementById('fieldOptions').value = options.join('\n');
        }
        
        handleFieldTypeChange();
    }
    
    // Update preview
    updatePreview();
}

/**
 * Hide field form
 */
function hideFieldForm() {
    document.getElementById('fieldFormContainer').style.display = 'none';
    document.getElementById('fieldFormPlaceholder').style.display = 'block';
}

/**
 * Edit field
 */
function editField(fieldId) {
    const field = currentFields.find(f => f.field_id == fieldId);
    if (field) {
        showFieldForm('update', field);
    }
}

/**
 * Delete field
 */
function deleteField(fieldId, fieldLabel) {
    if (!confirm(`Weet je zeker dat je het veld "${fieldLabel}" wilt verwijderen?`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
    formData.append('action', 'delete');
    formData.append('field_id', fieldId);
    
    // Disable the delete button to prevent double-clicks
    const deleteButton = event.target.closest('button');
    if (deleteButton) {
        deleteButton.disabled = true;
        deleteButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    }
    
    fetch('../api/manage_category_field.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Don't close modal - just reload the fields list
            showSuccess(data.message);
            
            // Reload fields for this category
            loadCategoryFields(currentCategoryId);
            
            // Update the count badge on the main page
            updateCategoryFieldCount(currentCategoryId);
        } else {
            showError(data.error);
            // Re-enable button on error
            if (deleteButton) {
                deleteButton.disabled = false;
                deleteButton.innerHTML = '<i class="bi bi-trash"></i>';
            }
        }
    })
    .catch(error => {
        console.error('Error deleting field:', error);
        showError('Fout bij verwijderen veld');
        // Re-enable button on error
        if (deleteButton) {
            deleteButton.disabled = false;
            deleteButton.innerHTML = '<i class="bi bi-trash"></i>';
        }
    });
}

/**
 * Update category field count badge
 */
function updateCategoryFieldCount(categoryId) {
    fetch(`../api/get_category_fields.php?category_id=${categoryId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const badge = document.querySelector(`[data-category-id="${categoryId}"] .field-count-badge`);
                if (badge) {
                    badge.textContent = data.fields.length;
                }
            }
        })
        .catch(error => {
            console.error('Error updating count:', error);
        });
}

/**
 * Handle field form submission
 */
function handleFieldFormSubmit(e) {
    e.preventDefault();
    
    console.log('Form submitted');
    
    const formData = new FormData(e.target);
    
    // Handle field options
    const fieldType = formData.get('field_type');
    if (['select', 'radio', 'checkbox'].includes(fieldType)) {
        const optionsText = document.getElementById('fieldOptions').value;
        const options = optionsText.split('\n')
            .map(opt => opt.trim())
            .filter(opt => opt.length > 0);
        formData.append('field_options', JSON.stringify(options));
    }
    
    console.log('Sending request to API...');
    
    fetch('../api/manage_category_field.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response received:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        
        if (data.success) {
            console.log('Success! Closing modal and reloading...');
            
            // Close modal first
            fieldManagementModal.hide();
            
            // Show success message
            showSuccess(data.message);
            
            // Reload the page to update field counts
            setTimeout(() => {
                console.log('Reloading page now...');
                window.location.reload();
            }, 1000);
        } else {
            console.error('API returned error:', data.error);
            showError(data.error);
        }
    })
    .catch(error => {
        console.error('Error saving field:', error);
        showError('Fout bij opslaan veld');
    });
}

/**
 * Handle field type change
 */
function handleFieldTypeChange() {
    const fieldType = document.getElementById('fieldType').value;
    const optionsContainer = document.getElementById('optionsContainer');
    
    if (['select', 'radio', 'checkbox'].includes(fieldType)) {
        optionsContainer.style.display = 'block';
    } else {
        optionsContainer.style.display = 'none';
    }
}

/**
 * Get field type label
 */
function getFieldTypeLabel(type) {
    const labels = {
        'text': 'Tekst',
        'textarea': 'Tekst (lang)',
        'select': 'Dropdown',
        'radio': 'Radio',
        'checkbox': 'Checkbox',
        'date': 'Datum',
        'number': 'Nummer',
        'email': 'Email',
        'tel': 'Telefoon'
    };
    return labels[type] || type;
}

/**
 * Show success message
 */
function showSuccess(message) {
    showAlert('success', message);
}

/**
 * Show error message
 */
function showError(message) {
    showAlert('danger', message);
}

/**
 * Show alert message
 */
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
    alertDiv.style.zIndex = '9999';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

/**
 * Setup preview listeners
 */
function setupPreviewListeners() {
    // Listen to form field changes
    const fieldsToWatch = ['fieldLabel', 'fieldType', 'fieldPlaceholder', 'fieldHelpText', 'fieldRequired', 'fieldOptions'];
    
    fieldsToWatch.forEach(fieldId => {
        const element = document.getElementById(fieldId);
        if (element) {
            element.addEventListener('input', updatePreview);
            element.addEventListener('change', updatePreview);
        }
    });
}

/**
 * Update field preview
 */
function updatePreview() {
    const label = document.getElementById('fieldLabel').value || 'Veld Label';
    const type = document.getElementById('fieldType').value;
    const placeholder = document.getElementById('fieldPlaceholder').value;
    const helpText = document.getElementById('fieldHelpText').value;
    const isRequired = document.getElementById('fieldRequired').checked;
    const optionsText = document.getElementById('fieldOptions').value;
    
    if (!type) {
        document.getElementById('fieldPreview').innerHTML = '<p class="text-muted small mb-0">Selecteer een veld type...</p>';
        return;
    }
    
    let previewHtml = '<div class="mb-3">';
    
    // Label
    previewHtml += `<label class="form-label">${escapeHtml(label)}`;
    if (isRequired) {
        previewHtml += ' <span class="text-danger">*</span>';
    }
    previewHtml += '</label>';
    
    // Field based on type
    switch (type) {
        case 'text':
        case 'email':
        case 'tel':
        case 'number':
            previewHtml += `<input type="${type}" class="form-control" placeholder="${escapeHtml(placeholder)}" ${isRequired ? 'required' : ''}>`;
            break;
            
        case 'textarea':
            previewHtml += `<textarea class="form-control" rows="3" placeholder="${escapeHtml(placeholder)}" ${isRequired ? 'required' : ''}></textarea>`;
            break;
            
        case 'select':
            previewHtml += '<select class="form-select" ' + (isRequired ? 'required' : '') + '>';
            previewHtml += '<option value="">-- Selecteer --</option>';
            if (optionsText) {
                const options = optionsText.split('\n').filter(opt => opt.trim());
                options.forEach(opt => {
                    previewHtml += `<option value="${escapeHtml(opt.trim())}">${escapeHtml(opt.trim())}</option>`;
                });
            }
            previewHtml += '</select>';
            break;
            
        case 'radio':
            if (optionsText) {
                const options = optionsText.split('\n').filter(opt => opt.trim());
                options.forEach((opt, index) => {
                    const optValue = opt.trim();
                    previewHtml += `
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="preview_radio" id="preview_radio_${index}" value="${escapeHtml(optValue)}" ${isRequired ? 'required' : ''}>
                            <label class="form-check-label" for="preview_radio_${index}">
                                ${escapeHtml(optValue)}
                            </label>
                        </div>
                    `;
                });
            } else {
                previewHtml += '<p class="text-muted small">Voeg opties toe om preview te zien</p>';
            }
            break;
            
        case 'checkbox':
            if (optionsText) {
                const options = optionsText.split('\n').filter(opt => opt.trim());
                options.forEach((opt, index) => {
                    const optValue = opt.trim();
                    previewHtml += `
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="preview_checkbox_${index}" value="${escapeHtml(optValue)}">
                            <label class="form-check-label" for="preview_checkbox_${index}">
                                ${escapeHtml(optValue)}
                            </label>
                        </div>
                    `;
                });
            } else {
                previewHtml += '<p class="text-muted small">Voeg opties toe om preview te zien</p>';
            }
            break;
            
        case 'date':
            previewHtml += `<input type="date" class="form-control" ${isRequired ? 'required' : ''}>`;
            break;
    }
    
    // Help text
    if (helpText) {
        previewHtml += `<small class="form-text text-muted">${escapeHtml(helpText)}</small>`;
    }
    
    previewHtml += '</div>';
    
    document.getElementById('fieldPreview').innerHTML = previewHtml;
}

/**
 * Escape HTML
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Preview fields for a category
 */
function previewFields(categoryId) {
    if (!categoryId) {
        alert('Selecteer eerst een categorie');
        return;
    }
    
    const url = `category_fields_preview.php?category_id=${categoryId}`;
    window.open(url, '_blank', 'width=900,height=700,scrollbars=yes,resizable=yes');
}
