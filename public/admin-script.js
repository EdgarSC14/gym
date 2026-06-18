// Admin Panel JavaScript - Consistente con el tema oscuro
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar todas las funcionalidades
    initMobileMenu();
    initModals();
    initTables();
    initForms();
    initNotifications();
    initCharts();
    initMediaUpload();
    initSearchAndFilter();
    initMediaPickers();
    // Mostrar/ocultar contraseña en el formulario de usuario
    const passwordInput = document.getElementById('password');
    const showPasswordCheckbox = document.getElementById('show-password');
    if (passwordInput && showPasswordCheckbox) {
        showPasswordCheckbox.addEventListener('change', function() {
            passwordInput.type = this.checked ? 'text' : 'password';
        });
    }
});

// Menú móvil
function initMobileMenu() {
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.admin-sidebar');
    
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
        
        // Cerrar menú al hacer clic fuera
        document.addEventListener('click', function(e) {
            if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        });
    }
}

// Modales
function initMediaPickers() {
    document.querySelectorAll('[data-media-select]').forEach(select => {
        const picker = select.closest('.admin-media-picker');
        const preview = picker ? picker.querySelector('[data-media-preview]') : null;
        if (!preview) return;

        const renderPreview = () => {
            const value = select.value.trim();
            const kind = select.getAttribute('data-media-kind') || 'image';
            const emptyLabel = select.getAttribute('data-empty-label') || 'Sin archivo';
            preview.innerHTML = '';
            preview.classList.toggle('video', kind === 'video');
            preview.classList.toggle('image', kind !== 'video');

            if (!value) {
                const empty = document.createElement('span');
                empty.textContent = emptyLabel;
                preview.appendChild(empty);
                return;
            }

            const src = value.startsWith('/') ? value : '/' + value;
            if (kind === 'video') {
                const video = document.createElement('video');
                video.src = src;
                video.muted = true;
                video.controls = true;
                video.preload = 'metadata';
                preview.appendChild(video);
                return;
            }

            const img = document.createElement('img');
            img.src = src;
            img.alt = 'Vista previa de ' + value.split('/').pop();
            img.loading = 'lazy';
            preview.appendChild(img);
        };

        select.addEventListener('change', renderPreview);
        renderPreview();
    });
}

function initModals() {
    const modalTriggers = document.querySelectorAll('[data-modal]');
    let activeModal = null;

    function openModal(modal) {
        if (!modal) return;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
        activeModal = modal;

        const focusTarget = modal.querySelector('input:not([type="hidden"]), select, textarea, button, a[href]');
        if (focusTarget) setTimeout(() => focusTarget.focus(), 30);
    }

    function closeModal(modal) {
        if (!modal) return;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        if (!document.querySelector('.modal.is-open')) {
            document.body.classList.remove('modal-open');
            activeModal = null;
        }
    }

    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            const modalId = this.getAttribute('data-modal');
            const modal = document.getElementById(modalId);
            if (modal) {
                e.preventDefault();
                openModal(modal);
            }
        });
    });

    document.querySelectorAll('[data-open-modal="true"]').forEach(openModal);

    document.addEventListener('click', function(e) {
        const closeButton = e.target.closest('.close, .close-modal, [data-close-modal]');
        if (closeButton) {
            const modal = closeButton.closest('.modal');
            if (modal && closeButton.tagName !== 'A') {
                e.preventDefault();
                closeModal(modal);
            }
            return;
        }

        if (e.target.classList && e.target.classList.contains('modal')) {
            closeModal(e.target);
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && activeModal) {
            closeModal(activeModal);
        }
    });
}

// Tablas interactivas
function initTables() {
    const tables = document.querySelectorAll('.admin-table table');
    
    tables.forEach(table => {
        // Ordenamiento
        const headers = table.querySelectorAll('th[data-sort]');
        headers.forEach(header => {
            header.addEventListener('click', function() {
                const column = this.getAttribute('data-sort');
                const tbody = table.querySelector('tbody');
                const rows = Array.from(tbody.querySelectorAll('tr'));
                
                rows.sort((a, b) => {
                    const aValue = a.querySelector(`td[data-${column}]`).getAttribute(`data-${column}`);
                    const bValue = b.querySelector(`td[data-${column}]`).getAttribute(`data-${column}`);
                    
                    if (this.classList.contains('sort-desc')) {
                        return aValue.localeCompare(bValue);
                    } else {
                        return bValue.localeCompare(aValue);
                    }
                });
                
                this.classList.toggle('sort-desc');
                tbody.innerHTML = '';
                rows.forEach(row => tbody.appendChild(row));
            });
        });
        
        // Búsqueda en tabla
        const tableCard = table.closest('.admin-table');
        const searchInput = tableCard ? tableCard.querySelector('.table-search') : null;
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const rows = table.querySelectorAll('tbody tr');
                
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            });
        }
    });
}

// Inicialización de formularios
function initForms() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        // Validación en tiempo real (excluyendo file inputs)
        const inputs = form.querySelectorAll('input:not([type="file"]), select, textarea');
        inputs.forEach(input => {
            // Validar al perder el foco
            input.addEventListener('blur', function() {
                validateField(this);
                updateFieldStyles(this);
            });
            
            // Validar al escribir (para campos de texto)
            if (input.type !== 'password') {
                input.addEventListener('input', function() {
                    // Solo limpiar error si el campo está válido
                    if (this.value.trim() !== '' && this.value === this.value.trim()) {
                        clearFieldError(this);
                    }
                    updateFieldStyles(this);
                });
            }
            
            // Validar al cambiar (para selects)
            if (input.tagName === 'SELECT') {
                input.addEventListener('change', function() {
                    validateField(this);
                    updateFieldStyles(this);
                });
            }
            
            // Prevenir espacios al inicio en tiempo real
            input.addEventListener('keydown', function(e) {
                if (e.key === ' ' && this.selectionStart === 0) {
                    e.preventDefault();
                }
                // Para email, prevenir espacios en cualquier posición
                if (this.type === 'email' && e.key === ' ') {
                    e.preventDefault();
                }
            });
            
            // Limpiar espacios al pegar
            input.addEventListener('paste', function(e) {
                setTimeout(() => {
                    this.value = this.value.trim();
                    validateField(this);
                    updateFieldStyles(this);
                }, 10);
            });
            
            // Inicializar estilos
            updateFieldStyles(input);
        });
        
        // Envío de formulario
        form.addEventListener('submit', function(e) {
            // Validar todos los campos antes de enviar (excluyendo file inputs)
            const fields = this.querySelectorAll('input:not([type="file"]), select, textarea');
            let isValid = true;
            
            fields.forEach(field => {
                if (!validateField(field)) {
                    isValid = false;
                    // Enfocar el primer campo con error
                    if (isValid === false) {
                        field.focus();
                        isValid = false;
                    }
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showNotification('Por favor, corrige los errores en el formulario', 'error');
                
                // Hacer scroll al primer error
                const firstError = this.querySelector('.field-error');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
    });
}

// Función para actualizar estilos de campos
function updateFieldStyles(field) {
    const value = field.value.trim();
    const isRequired = field.hasAttribute('required');
    const hasError = field.classList.contains('error');
    
    // Remover estilos previos
    field.style.borderColor = '';
    field.style.boxShadow = '';
    field.style.backgroundColor = '';
    
    // Si hay error, mantener estilo de error
    if (hasError) {
        return;
    }
    
    // Si es campo requerido y está vacío, mantener estilo por defecto
    if (isRequired && !value) {
        return;
    }
    
    // Si es campo opcional y está vacío, mantener estilo por defecto
    if (!isRequired && !value) {
        return;
    }
    
    // Validación especial para email: no mostrar verde si solo tiene espacios
    if (field.type === 'email' && field.value.trim() === '') {
        return;
    }
    
    // Validación especial para password: mínimo 8 caracteres y no vacío
    if (field.id === 'password') {
        if (value.length >= 8) {
            field.style.borderColor = '#28a745';
            field.style.boxShadow = '0 0 5px rgba(40, 167, 69, 0.3)';
        }
        return;
    }
    
    // Si tiene contenido válido, aplicar estilo verde
    if (value && validateField(field)) {
        field.style.borderColor = '#28a745';
        field.style.boxShadow = '0 0 5px rgba(40, 167, 69, 0.3)';
    }
}

// Validación de campos
function validateField(field) {
    const value = field.value.trim();
    const type = field.type;
    const required = field.hasAttribute('required');
    const id = field.id;
    
    clearFieldError(field);
    
    // Validación de campos obligatorios
    if (required && !value) {
        showFieldError(field, 'Este campo es obligatorio');
        return false;
    }
    
    // Validación de espacios en blanco para todos los campos
    if (value && value.trim() === '') {
        showFieldError(field, 'Este campo no puede contener solo espacios en blanco');
        return false;
    }
    
    // Validación de espacios al inicio y final
    if (value && (value !== value.trim())) {
        showFieldError(field, 'Este campo no puede comenzar o terminar con espacios en blanco');
        return false;
    }
    
    if (value) {
        switch (type) {
            case 'email':
                // Validación adicional para email: no puede contener solo espacios
                if (value.trim() === '') {
                    showFieldError(field, 'El email no puede contener solo espacios en blanco');
                    return false;
                }
                // Validación para espacios al inicio o final
                if (value !== value.trim()) {
                    showFieldError(field, 'El email no puede comenzar o terminar con espacios en blanco');
                    return false;
                }
                if (!isValidEmail(value)) {
                    showFieldError(field, 'Email inválido');
                    return false;
                }
                break;
            case 'number':
                if (isNaN(value) || (!field.hasAttribute('data-allow-negative') && value < 0)) {
                    showFieldError(field, 'Número inválido');
                    return false;
                }
                break;
            case 'url':
                if (!isValidUrl(value)) {
                    showFieldError(field, 'URL inválida');
                    return false;
                }
                break;
        }
        
        // Validaciones personalizadas para el formulario de usuario
        if (id === 'username') {
            if (value.length < 3) {
                showFieldError(field, 'El nombre de usuario debe tener al menos 3 caracteres');
                return false;
            }
            if (!/^[a-zA-Z0-9_]+$/.test(value)) {
                showFieldError(field, 'El nombre de usuario solo puede contener letras, números y guiones bajos');
                return false;
            }
        }
        
        if (id === 'first_name' || id === 'last_name') {
            if (/\d/.test(value)) {
                showFieldError(field, 'No puede contener números');
                return false;
            }
            if (value.length < 2) {
                showFieldError(field, 'Debe tener al menos 2 caracteres');
                return false;
            }
        }
        
        if (id === 'phone') {
            if (/[^\d\s\-\+]/.test(value)) {
                showFieldError(field, 'El teléfono no puede contener letras');
                return false;
            }
            if (value.replace(/[\s\-+]/g, '').length < 10) {
                showFieldError(field, 'El teléfono debe tener al menos 10 dígitos');
                return false;
            }
            if (value.replace(/[\s\-+]/g, '').length > 10) {
                showFieldError(field, 'El teléfono no puede tener más de 10 dígitos');
                return false;
            }
        }
        
        if (id === 'password') {
            // Solo validar longitud si el campo no está vacío (para edición)
            if (value.length > 0 && value.length < 8) {
                showFieldError(field, 'La contraseña debe tener al menos 8 caracteres');
                return false;
            }
        }
        
        if (id === 'address') {
            if (value.length < 10) {
                showFieldError(field, 'La dirección debe tener al menos 10 caracteres');
                return false;
            }
        }
    }
    
    return true;
}

// Validación de formulario completo
function validateForm(form) {
    const fields = form.querySelectorAll('input, select, textarea');
    let isValid = true;
    
    fields.forEach(field => {
        if (!validateField(field)) {
            isValid = false;
        }
    });
    
    return isValid;
}

// Mostrar error de campo
function showFieldError(field, message) {
    field.classList.add('error');
    
    let errorDiv = field.parentElement.querySelector('.field-error');
    if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        field.parentElement.appendChild(errorDiv);
    }
    
    errorDiv.textContent = message;
    errorDiv.style.color = '#dc3545';
    errorDiv.style.fontSize = '1.2rem';
    errorDiv.style.marginTop = '5px';
}

// Limpiar error de campo
function clearFieldError(field) {
    field.classList.remove('error');
    const errorDiv = field.parentElement.querySelector('.field-error');
    if (errorDiv) {
        errorDiv.remove();
    }
}

// Notificaciones
function initNotifications() {
    // Crear contenedor de notificaciones
    if (!document.querySelector('.notifications-container')) {
        const container = document.createElement('div');
        container.className = 'notifications-container';
        container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            max-width: 400px;
        `;
        document.body.appendChild(container);
    }
}

function showNotification(message, type = 'info') {
    const container = document.querySelector('.notifications-container');
    const notification = document.createElement('div');
    
    notification.className = `notification notification-${type}`;
    notification.style.cssText = `
        background: var(--snd-bg-color);
        color: var(--text-color);
        padding: 15px 20px;
        border-radius: var(--border-radius);
        margin-bottom: 10px;
        border: 2px solid var(--main-color);
        box-shadow: 0 0 15px rgba(255, 30, 0, 0.3);
        transform: translateX(100%);
        transition: var(--transition);
        position: relative;
    `;
    
    notification.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <span>${message}</span>
            <button class="close-notification" style="background: none; border: none; color: var(--main-color); font-size: 1.8rem; cursor: pointer;">×</button>
        </div>
    `;
    
    container.appendChild(notification);
    
    // Animación de entrada
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Cerrar notificación
    const closeBtn = notification.querySelector('.close-notification');
    closeBtn.addEventListener('click', () => {
        closeNotification(notification);
    });
    
    // Auto-cerrar después de 5 segundos
    setTimeout(() => {
        closeNotification(notification);
    }, 5000);
}

function closeNotification(notification) {
    notification.style.transform = 'translateX(100%)';
    setTimeout(() => {
        notification.remove();
    }, 300);
}

// Gráficos (Chart.js)
function initCharts() {
    const chartContainers = document.querySelectorAll('.chart-container');
    
    chartContainers.forEach(container => {
        const canvas = container.querySelector('canvas');
        if (canvas) {
            const ctx = canvas.getContext('2d');
            const chartType = container.getAttribute('data-chart-type');
            
            if (chartType === 'revenue') {
                createRevenueChart(ctx);
            } else if (chartType === 'sales') {
                createSalesChart(ctx);
            }
        }
    });
}

function createRevenueChart(ctx) {
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
            datasets: [{
                label: 'Ingresos Mensuales',
                data: [12000, 19000, 15000, 25000, 22000, 30000],
                borderColor: '#FF1E00',
                backgroundColor: 'rgba(255, 30, 0, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    labels: {
                        color: '#FFF'
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(255, 30, 0, 0.1)'
                    },
                    ticks: {
                        color: '#FFF'
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(255, 30, 0, 0.1)'
                    },
                    ticks: {
                        color: '#FFF'
                    }
                }
            }
        }
    });
}

function createSalesChart(ctx) {
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Productos', 'Servicios', 'Suscripciones'],
            datasets: [{
                data: [45, 30, 25],
                backgroundColor: ['#FF1E00', '#FFD700', '#28a745'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    labels: {
                        color: '#FFF'
                    }
                }
            }
        }
    });
}

// Subida de medios
function initMediaUpload() {
    const dropZones = document.querySelectorAll('.drop-zone');
    
    dropZones.forEach(zone => {
        const input = zone.querySelector('input[type="file"]');
        const preview = zone.querySelector('.file-preview');
        
        // Drag and drop
        zone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });
        
        zone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });
        
        zone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleFileUpload(files[0], preview, input);
            }
        });
        
        // Click para seleccionar archivo
        zone.addEventListener('click', function() {
            input.click();
        });
        
        // Cambio de archivo
        input.addEventListener('change', function() {
            if (this.files.length > 0) {
                handleFileUpload(this.files[0], preview, input);
            }
        });
    });
}

function handleFileUpload(file, preview, input) {
    // Validar tipo de archivo
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'video/webm'];
    
    if (!allowedTypes.includes(file.type)) {
        showNotification('Tipo de archivo no permitido', 'error');
        return;
    }
    
    // Validar tamaño (5MB máximo)
    if (file.size > 5 * 1024 * 1024) {
        showNotification('El archivo es demasiado grande (máximo 5MB)', 'error');
        return;
    }
    
    // Mostrar preview
    if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" style="max-width: 100%; max-height: 200px; border-radius: var(--border-radius);">`;
        };
        reader.readAsDataURL(file);
    } else {
        preview.innerHTML = `<video controls style="max-width: 100%; max-height: 200px; border-radius: var(--border-radius);"><source src="${URL.createObjectURL(file)}" type="${file.type}"></video>`;
    }
    
    showNotification('Archivo cargado exitosamente', 'success');
}

// Búsqueda y filtros
function initSearchAndFilter() {
    const searchInputs = document.querySelectorAll('.search-input');
    const filterButtons = document.querySelectorAll('.filter-btn');
    
    // Búsqueda en tiempo real
    searchInputs.forEach(input => {
        input.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const targetSelector = this.getAttribute('data-target');
            const items = document.querySelectorAll(targetSelector);
            
            items.forEach(item => {
                const text = item.textContent.toLowerCase();
                item.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    });
    
    // Filtros
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const filter = this.getAttribute('data-filter');
            const targetSelector = this.getAttribute('data-target');
            const items = document.querySelectorAll(targetSelector);
            
            // Remover clase activa de todos los botones
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            items.forEach(item => {
                if (filter === 'all' || item.getAttribute('data-category') === filter) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
}

// Utilidades
function isValidEmail(email) {
    // Primero verificar que no esté vacío o solo contenga espacios
    if (!email || email.trim() === '') {
        return false;
    }
    
    // Verificar que no contenga espacios al inicio o final
    if (email !== email.trim()) {
        return false;
    }
    
    // Verificar que no contenga espacios en el medio
    if (email.includes(' ')) {
        return false;
    }
    
    // Validación de formato de email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function isValidUrl(url) {
    try {
        new URL(url);
        return true;
    } catch {
        return false;
    }
}

// Confirmación de eliminación
function confirmDelete(message = '¿Estás seguro de que quieres eliminar este elemento?') {
    return confirm(message);
}

// Copiar al portapapeles
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showNotification('Copiado al portapapeles', 'success');
    }).catch(() => {
        showNotification('Error al copiar', 'error');
    });
}

// Exportar datos
function exportData(format = 'csv') {
    const table = document.querySelector('.admin-table table');
    if (!table) return;
    
    const rows = Array.from(table.querySelectorAll('tr'));
    let csvContent = '';
    
    rows.forEach(row => {
        const cells = Array.from(row.querySelectorAll('th, td'));
        const rowData = cells.map(cell => `"${cell.textContent.trim()}"`).join(',');
        csvContent += rowData + '\n';
    });
    
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `export_${new Date().toISOString().split('T')[0]}.csv`;
    a.click();
    window.URL.revokeObjectURL(url);
    
    showNotification('Datos exportados exitosamente', 'success');
}

// Inicializar actualizaciones en tiempo real
// initRealTimeUpdates(); 