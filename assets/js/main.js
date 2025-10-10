/**
 * ICT Ticketportaal - Main JavaScript
 * Handles form validation, AJAX operations, file uploads, and UI interactions
 */

// ===================================
// 1. Document Ready & Initialization
// ===================================
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    initFormValidation();
    initFileUpload();
    initConfirmDialogs();
    initAjaxStatusUpdates();
    initTooltips();
    initSidebarToggle();
    
    console.log('ICT Ticketportaal initialized');
});

// ===================================
// 2. Form Validation
// ===================================
function initFormValidation() {
    // Get all forms with needs-validation class
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
    
    // Real-time validation for specific fields
    initEmailValidation();
    initPasswordValidation();
}

function initEmailValidation() {
    const emailInputs = document.querySelectorAll('input[type="email"]');
    
    emailInputs.forEach(input => {
        input.addEventListener('blur', function() {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (this.value && !emailRegex.test(this.value)) {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
            } else if (this.value) {
                this.classList.add('is-valid');
                this.classList.remove('is-invalid');
            }
        });
    });
}

function initPasswordValidation() {
    const passwordInputs = document.querySelectorAll('input[type="password"][data-validate="true"]');
    
    passwordInputs.forEach(input => {
        input.addEventListener('input', function() {
            const password = this.value;
            const minLength = 8;
            const hasLetter = /[a-zA-Z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            
            const isValid = password.length >= minLength && hasLetter && hasNumber;
            
            if (isValid) {
                this.classList.add('is-valid');
                this.classList.remove('is-invalid');
            } else if (password.length > 0) {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
            }
        });
    });
}

// ===================================
// 3. File Upload Preview
// ===================================
function initFileUpload() {
    const fileInputs = document.querySelectorAll('input[type="file"]');
    
    fileInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            handleFileSelect(e, this);
        });
    });
}

function handleFileSelect(event, input) {
    const files = event.target.files;
    const previewContainer = document.getElementById('filePreview') || createPreviewContainer(input);
    
    // Clear previous preview
    previewContainer.innerHTML = '';
    
    if (files.length === 0) {
        previewContainer.style.display = 'none';
        return;
    }
    
    previewContainer.style.display = 'block';
    
    Array.from(files).forEach((file, index) => {
        const fileItem = createFilePreviewItem(file, index);
        previewContainer.appendChild(fileItem);
    });
    
    // Validate file size and type
    validateFiles(files, input);
}

function createPreviewContainer(input) {
    const container = document.createElement('div');
    container.id = 'filePreview';
    container.className = 'file-preview mt-3';
    input.parentNode.appendChild(container);
    return container;
}

function createFilePreviewItem(file, index) {
    const item = document.createElement('div');
    item.className = 'file-preview-item';
    
    const icon = getFileIcon(file.type);
    const size = formatFileSize(file.size);
    
    item.innerHTML = `
        <i class="bi ${icon} fs-4 me-3 text-primary"></i>
        <div class="flex-grow-1">
            <div class="fw-bold">${escapeHtml(file.name)}</div>
            <small class="text-muted">${size}</small>
        </div>
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeFile(${index})">
            <i class="bi bi-x-lg"></i>
        </button>
    `;
    
    return item;
}

function getFileIcon(mimeType) {
    if (mimeType.startsWith('image/')) return 'bi-file-image';
    if (mimeType.includes('pdf')) return 'bi-file-pdf';
    if (mimeType.includes('word')) return 'bi-file-word';
    if (mimeType.includes('excel') || mimeType.includes('spreadsheet')) return 'bi-file-excel';
    if (mimeType.includes('zip') || mimeType.includes('compressed')) return 'bi-file-zip';
    return 'bi-file-earmark';
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

function validateFiles(files, input) {
    const maxSize = 10 * 1024 * 1024; // 10MB
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 
                          'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                          'text/plain'];
    
    let hasError = false;
    
    Array.from(files).forEach(file => {
        if (file.size > maxSize) {
            showAlert('Bestand te groot: ' + file.name + ' (max 10MB)', 'danger');
            hasError = true;
        }
        
        if (!allowedTypes.includes(file.type) && !file.type.startsWith('image/')) {
            showAlert('Bestandstype niet toegestaan: ' + file.name, 'danger');
            hasError = true;
        }
    });
    
    if (hasError) {
        input.value = '';
        const previewContainer = document.getElementById('filePreview');
        if (previewContainer) {
            previewContainer.style.display = 'none';
        }
    }
}

function removeFile(index) {
    // This would need to be implemented with a proper file management system
    console.log('Remove file at index:', index);
}

// ===================================
// 4. AJAX Status Updates
// ===================================
function initAjaxStatusUpdates() {
    // Status update buttons
    const statusButtons = document.querySelectorAll('[data-action="update-status"]');
    
    statusButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const ticketId = this.dataset.ticketId;
            const newStatus = this.dataset.status;
            updateTicketStatus(ticketId, newStatus);
        });
    });
    
    // Assignment dropdowns
    const assignmentSelects = document.querySelectorAll('[data-action="assign-ticket"]');
    
    assignmentSelects.forEach(select => {
        select.addEventListener('change', function() {
            const ticketId = this.dataset.ticketId;
            const agentId = this.value;
            assignTicket(ticketId, agentId);
        });
    });
}

function updateTicketStatus(ticketId, newStatus) {
    if (!confirm('Weet u zeker dat u de status wilt wijzigen?')) {
        return;
    }
    
    showLoading();
    
    fetch('/api/tickets.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'update_status',
            ticket_id: ticketId,
            status: newStatus
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            showAlert('Status succesvol bijgewerkt', 'success');
            // Refresh the page or update UI
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert('Fout bij bijwerken status: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        hideLoading();
        showAlert('Er is een fout opgetreden', 'danger');
        console.error('Error:', error);
    });
}

function assignTicket(ticketId, agentId) {
    showLoading();
    
    fetch('/api/tickets.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'assign_ticket',
            ticket_id: ticketId,
            agent_id: agentId
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            showAlert('Ticket succesvol toegewezen', 'success');
        } else {
            showAlert('Fout bij toewijzen ticket: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        hideLoading();
        showAlert('Er is een fout opgetreden', 'danger');
        console.error('Error:', error);
    });
}

// ===================================
// 5. Confirmation Dialogs
// ===================================
function initConfirmDialogs() {
    // Delete actions
    const deleteButtons = document.querySelectorAll('[data-action="delete"]');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const message = this.dataset.confirmMessage || 'Weet u zeker dat u dit wilt verwijderen?';
            
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    });
    
    // Deactivate actions
    const deactivateButtons = document.querySelectorAll('[data-action="deactivate"]');
    
    deactivateButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Weet u zeker dat u dit wilt deactiveren?')) {
                e.preventDefault();
                return false;
            }
        });
    });
}

// ===================================
// 6. UI Helper Functions
// ===================================
function showAlert(message, type = 'info') {
    const alertContainer = document.getElementById('alertContainer') || createAlertContainer();
    
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.role = 'alert';
    alert.innerHTML = `
        ${escapeHtml(message)}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    alertContainer.appendChild(alert);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        alert.classList.remove('show');
        setTimeout(() => alert.remove(), 150);
    }, 5000);
}

function createAlertContainer() {
    const container = document.createElement('div');
    container.id = 'alertContainer';
    container.className = 'position-fixed top-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}

function showLoading() {
    let overlay = document.getElementById('loadingOverlay');
    
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'loadingOverlay';
        overlay.className = 'loading-overlay';
        overlay.innerHTML = `
            <div class="spinner-border text-light spinner-border-lg" role="status">
                <span class="visually-hidden">Laden...</span>
            </div>
        `;
        document.body.appendChild(overlay);
    }
    
    overlay.style.display = 'flex';
}

function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

function initTooltips() {
    // Initialize Bootstrap tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

function initSidebarToggle() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });
    }
}

// ===================================
// 7. Utility Functions
// ===================================
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// ===================================
// 8. Table Sorting & Filtering
// ===================================
function initTableSorting() {
    const sortableHeaders = document.querySelectorAll('th[data-sortable="true"]');
    
    sortableHeaders.forEach(header => {
        header.style.cursor = 'pointer';
        header.addEventListener('click', function() {
            const table = this.closest('table');
            const columnIndex = Array.from(this.parentNode.children).indexOf(this);
            sortTable(table, columnIndex);
        });
    });
}

function sortTable(table, columnIndex) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    const sortedRows = rows.sort((a, b) => {
        const aValue = a.children[columnIndex].textContent.trim();
        const bValue = b.children[columnIndex].textContent.trim();
        
        return aValue.localeCompare(bValue, 'nl', { numeric: true });
    });
    
    // Clear and re-append sorted rows
    tbody.innerHTML = '';
    sortedRows.forEach(row => tbody.appendChild(row));
}

// ===================================
// 9. Search & Filter
// ===================================
function initSearch() {
    const searchInput = document.getElementById('searchInput');
    
    if (searchInput) {
        searchInput.addEventListener('input', debounce(function() {
            const searchTerm = this.value.toLowerCase();
            filterTable(searchTerm);
        }, 300));
    }
}

function filterTable(searchTerm) {
    const table = document.querySelector('table tbody');
    if (!table) return;
    
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
}

// ===================================
// 10. Export Functions for Global Use
// ===================================
window.ticketPortal = {
    showAlert,
    showLoading,
    hideLoading,
    updateTicketStatus,
    assignTicket,
    escapeHtml
};
