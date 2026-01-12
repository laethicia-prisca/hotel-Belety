// ===================================
// Fonctions utilitaires générales
// ===================================

// Confirmation de suppression
function confirmDelete(message = 'Êtes-vous sûr de vouloir supprimer cet élément ?') {
    return confirm(message);
}

// Gestion des modals
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = 'auto';
    }
}

// Fermer le modal en cliquant à l'extérieur
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        closeModal(e.target.id);
    }
});

// Gestion des alertes auto-disparaissantes
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
});

// Validation de formulaire côté client
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.style.borderColor = 'var(--danger-color)';
            isValid = false;
        } else {
            field.style.borderColor = 'var(--border-color)';
        }
    });
    
    return isValid;
}

// Réinitialiser les bordures d'erreur
document.addEventListener('input', function(e) {
    if (e.target.hasAttribute('required')) {
        e.target.style.borderColor = 'var(--border-color)';
    }
});

// Formatage automatique des numéros de téléphone
function formatPhoneNumber(input) {
    let value = input.value.replace(/\D/g, '');
    if (value.length > 10) value = value.substr(0, 10);
    
    if (value.length > 6) {
        input.value = value.substr(0, 2) + ' ' + value.substr(2, 2) + ' ' + 
                      value.substr(4, 2) + ' ' + value.substr(6, 2) + ' ' + value.substr(8);
    } else if (value.length > 4) {
        input.value = value.substr(0, 2) + ' ' + value.substr(2, 2) + ' ' + value.substr(4);
    } else if (value.length > 2) {
        input.value = value.substr(0, 2) + ' ' + value.substr(2);
    } else {
        input.value = value;
    }
}

// Calcul automatique du prix total
function calculateTotalPrice() {
    const checkIn = document.getElementById('check_in_date');
    const checkOut = document.getElementById('check_out_date');
    const roomSelect = document.getElementById('room_id');
    const totalPriceField = document.getElementById('total_price');
    
    if (!checkIn || !checkOut || !roomSelect || !totalPriceField) return;
    
    if (checkIn.value && checkOut.value && roomSelect.value) {
        const date1 = new Date(checkIn.value);
        const date2 = new Date(checkOut.value);
        const nights = Math.ceil((date2 - date1) / (1000 * 60 * 60 * 24));
        
        if (nights > 0) {
            const pricePerNight = parseFloat(roomSelect.options[roomSelect.selectedIndex].dataset.price || 0);
            const totalPrice = nights * pricePerNight;
            totalPriceField.value = totalPrice.toFixed(2);
        }
    }
}

// Recherche en temps réel dans les tables
function searchTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    
    if (!input || !table) return;
    
    input.addEventListener('keyup', function() {
        const filter = this.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
}

// Filtrage des réservations par statut
function filterByStatus(statusSelectId, tableId) {
    const select = document.getElementById(statusSelectId);
    const table = document.getElementById(tableId);
    
    if (!select || !table) return;
    
    select.addEventListener('change', function() {
        const status = this.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            if (status === '' || status === 'all') {
                row.style.display = '';
            } else {
                const statusCell = row.querySelector('.badge');
                if (statusCell && statusCell.textContent.toLowerCase().includes(status)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        });
    });
}

// Validation des dates (check-out après check-in)
function validateDates() {
    const checkIn = document.getElementById('check_in_date');
    const checkOut = document.getElementById('check_out_date');
    
    if (!checkIn || !checkOut) return;
    
    checkIn.addEventListener('change', function() {
        checkOut.min = this.value;
        if (checkOut.value && checkOut.value <= this.value) {
            checkOut.value = '';
        }
    });
}

// Prévisualisation d'image
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (!input.files || !input.files[0] || !preview) return;
    
    const reader = new FileReader();
    reader.onload = function(e) {
        preview.src = e.target.result;
        preview.style.display = 'block';
    };
    reader.readAsDataURL(input.files[0]);
}

// Impression
function printSection(sectionId) {
    const section = document.getElementById(sectionId);
    if (!section) return;
    
    const printWindow = window.open('', '', 'height=600,width=800');
    printWindow.document.write('<html><head><title>Impression</title>');
    printWindow.document.write('<link rel="stylesheet" href="css/style.css">');
    printWindow.document.write('</head><body>');
    printWindow.document.write(section.innerHTML);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.print();
}

// Export table to CSV
function exportTableToCSV(tableId, filename = 'export.csv') {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const rowData = [];
        cols.forEach(col => {
            rowData.push('"' + col.textContent.trim().replace(/"/g, '""') + '"');
        });
        csv.push(rowData.join(','));
    });
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename;
    link.click();
}

// Statistiques en temps réel (Dashboard)
function updateDashboardStats() {
    // Cette fonction peut être étendue pour faire des requêtes AJAX
    // pour mettre à jour les statistiques en temps réel
    console.log('Mise à jour des statistiques...');
}

// Animation des chiffres (compteur)
function animateValue(elementId, start, end, duration) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    const range = end - start;
    const increment = range / (duration / 16);
    let current = start;
    
    const timer = setInterval(() => {
        current += increment;
        if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
            current = end;
            clearInterval(timer);
        }
        element.textContent = Math.round(current);
    }, 16);
}

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    // Validation des dates
    validateDates();
    
    // Date minimum pour les réservations (aujourd'hui)
    const today = new Date().toISOString().split('T')[0];
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        if (!input.min) {
            input.min = today;
        }
    });
    
    // Calcul automatique du prix total
    const checkInInput = document.getElementById('check_in_date');
    const checkOutInput = document.getElementById('check_out_date');
    const roomSelectInput = document.getElementById('room_id');
    
    if (checkInInput && checkOutInput && roomSelectInput) {
        checkInInput.addEventListener('change', calculateTotalPrice);
        checkOutInput.addEventListener('change', calculateTotalPrice);
        roomSelectInput.addEventListener('change', calculateTotalPrice);
    }
    
    // Animation des statistiques au chargement
    const statValues = document.querySelectorAll('.stat-details h3');
    statValues.forEach(stat => {
        const endValue = parseInt(stat.textContent);
        if (!isNaN(endValue)) {
            stat.textContent = '0';
            animateValue(stat.id || 'stat-' + Math.random(), 0, endValue, 1000);
        }
    });
});

// Gestion du toggle de la sidebar (pour mobile)
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        sidebar.classList.toggle('active');
    }
}

// AJAX pour mise à jour sans rechargement
function updateStatus(id, type, newStatus) {
    fetch(`php/update_status.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            id: id,
            type: type,
            status: newStatus
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erreur lors de la mise à jour');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur de connexion');
    });
}

// Vérification de la disponibilité d'une chambre
function checkRoomAvailability(roomId, checkIn, checkOut) {
    return fetch(`php/check_availability.php?room_id=${roomId}&check_in=${checkIn}&check_out=${checkOut}`)
        .then(response => response.json())
        .then(data => data.available)
        .catch(error => {
            console.error('Erreur:', error);
            return false;
        });
}

// Tooltip simple
function showTooltip(element, message) {
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.textContent = message;
    tooltip.style.position = 'absolute';
    tooltip.style.background = '#333';
    tooltip.style.color = '#fff';
    tooltip.style.padding = '5px 10px';
    tooltip.style.borderRadius = '4px';
    tooltip.style.fontSize = '12px';
    tooltip.style.zIndex = '9999';
    
    document.body.appendChild(tooltip);
    
    const rect = element.getBoundingClientRect();
    tooltip.style.top = (rect.top - tooltip.offsetHeight - 5) + 'px';
    tooltip.style.left = (rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2)) + 'px';
    
    setTimeout(() => {
        tooltip.remove();
    }, 2000);
}
