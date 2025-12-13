/**
 * KMS Gestion - Modern Lists JavaScript
 * Interactive features for list pages
 * Date: 2025-12-13
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // ═══════════════════════════════════════════════════════════════════════
    // Staggered Animation for Table Rows
    // ═══════════════════════════════════════════════════════════════════════
    const tableRows = document.querySelectorAll('.modern-table tbody tr');
    tableRows.forEach((row, index) => {
        row.style.opacity = '0';
        row.style.transform = 'translateX(-10px)';
        
        setTimeout(() => {
            row.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            row.style.opacity = '1';
            row.style.transform = 'translateX(0)';
        }, index * 30); // Stagger by 30ms
    });
    
    // ═══════════════════════════════════════════════════════════════════════
    // Search Input Auto-Focus
    // ═══════════════════════════════════════════════════════════════════════
    const searchInput = document.querySelector('.filter-card input[name="q"]');
    if (searchInput && !searchInput.value) {
        // Only auto-focus if search is empty and user didn't come from another field
        const urlParams = new URLSearchParams(window.location.search);
        if (!urlParams.toString()) {
            searchInput.focus();
        }
    }
    
    // ═══════════════════════════════════════════════════════════════════════
    // Confirm Delete Actions (if any delete buttons exist)
    // ═══════════════════════════════════════════════════════════════════════
    const deleteButtons = document.querySelectorAll('[data-confirm-delete]');
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            const message = this.dataset.confirmDelete || 'Êtes-vous sûr de vouloir supprimer cet élément ?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
    
    // ═══════════════════════════════════════════════════════════════════════
    // Highlight Selected Filter Values
    // ═══════════════════════════════════════════════════════════════════════
    const filterSelects = document.querySelectorAll('.filter-card select');
    filterSelects.forEach(select => {
        if (select.value && select.value !== '0' && select.value !== '') {
            select.classList.add('border-primary', 'fw-bold');
        }
        
        select.addEventListener('change', function() {
            if (this.value && this.value !== '0' && this.value !== '') {
                this.classList.add('border-primary', 'fw-bold');
            } else {
                this.classList.remove('border-primary', 'fw-bold');
            }
        });
    });
    
    const filterInputs = document.querySelectorAll('.filter-card input[type="text"]');
    filterInputs.forEach(input => {
        if (input.value.trim() !== '') {
            input.classList.add('border-primary', 'fw-semibold');
        }
    });
    
    // ═══════════════════════════════════════════════════════════════════════
    // Badge Hover Animation
    // ═══════════════════════════════════════════════════════════════════════
    const badges = document.querySelectorAll('.modern-badge');
    badges.forEach(badge => {
        badge.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.05) translateY(-2px)';
        });
        badge.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1) translateY(0)';
        });
    });
    
    // ═══════════════════════════════════════════════════════════════════════
    // Keyboard Shortcuts
    // ═══════════════════════════════════════════════════════════════════════
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + K: Focus search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const searchField = document.querySelector('.filter-card input[name="q"]');
            if (searchField) {
                searchField.focus();
                searchField.select();
            }
        }
        
        // Ctrl/Cmd + N: New item (if button exists)
        if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
            e.preventDefault();
            const newButton = document.querySelector('.btn-add-new');
            if (newButton) {
                newButton.click();
            }
        }
    });
    
    // ═══════════════════════════════════════════════════════════════════════
    // Row Click to View Details (Optional Enhancement)
    // ═══════════════════════════════════════════════════════════════════════
    const clickableRows = document.querySelectorAll('.modern-table tbody tr[data-href]');
    clickableRows.forEach(row => {
        row.style.cursor = 'pointer';
        row.addEventListener('click', function(e) {
            // Don't trigger if clicking on a button or link
            if (e.target.closest('a, button')) return;
            
            const href = this.dataset.href;
            if (href) {
                window.location.href = href;
            }
        });
    });
    
    // ═══════════════════════════════════════════════════════════════════════
    // Count Badge Animation
    // ═══════════════════════════════════════════════════════════════════════
    const countBadge = document.querySelector('.count-badge');
    if (countBadge) {
        const count = parseInt(countBadge.textContent);
        if (!isNaN(count) && count > 0) {
            let currentCount = 0;
            const duration = 800; // ms
            const increment = Math.ceil(count / (duration / 20));
            
            const counter = setInterval(() => {
                currentCount += increment;
                if (currentCount >= count) {
                    currentCount = count;
                    clearInterval(counter);
                    countBadge.style.transform = 'scale(1.1)';
                    setTimeout(() => {
                        countBadge.style.transform = 'scale(1)';
                    }, 200);
                }
                countBadge.textContent = currentCount;
            }, 20);
        }
    }
    
    // ═══════════════════════════════════════════════════════════════════════
    // Tooltip Initialization (if Bootstrap tooltips are used)
    // ═══════════════════════════════════════════════════════════════════════
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(
            document.querySelectorAll('[data-bs-toggle="tooltip"]')
        );
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
    
    // ═══════════════════════════════════════════════════════════════════════
    // Print Table Function
    // ═══════════════════════════════════════════════════════════════════════
    const printButton = document.querySelector('[data-print-table]');
    if (printButton) {
        printButton.addEventListener('click', function(e) {
            e.preventDefault();
            window.print();
        });
    }
    
    // ═══════════════════════════════════════════════════════════════════════
    // Table Sort (Simple client-side sorting)
    // ═══════════════════════════════════════════════════════════════════════
    const sortableHeaders = document.querySelectorAll('.modern-table thead th[data-sortable]');
    sortableHeaders.forEach(header => {
        header.style.cursor = 'pointer';
        header.innerHTML += ' <i class="bi bi-chevron-expand text-muted ms-1" style="font-size: 0.7rem;"></i>';
        
        header.addEventListener('click', function() {
            const table = this.closest('table');
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            const columnIndex = Array.from(this.parentElement.children).indexOf(this);
            const currentOrder = this.dataset.order || 'asc';
            const newOrder = currentOrder === 'asc' ? 'desc' : 'asc';
            
            rows.sort((a, b) => {
                const aValue = a.children[columnIndex].textContent.trim();
                const bValue = b.children[columnIndex].textContent.trim();
                
                // Try numeric comparison first
                const aNum = parseFloat(aValue.replace(/[^0-9.-]/g, ''));
                const bNum = parseFloat(bValue.replace(/[^0-9.-]/g, ''));
                
                if (!isNaN(aNum) && !isNaN(bNum)) {
                    return newOrder === 'asc' ? aNum - bNum : bNum - aNum;
                }
                
                // String comparison
                return newOrder === 'asc' 
                    ? aValue.localeCompare(bValue)
                    : bValue.localeCompare(aValue);
            });
            
            // Update DOM
            rows.forEach(row => tbody.appendChild(row));
            
            // Update order indicator
            this.dataset.order = newOrder;
            const icon = this.querySelector('i');
            icon.className = newOrder === 'asc' 
                ? 'bi bi-chevron-up text-primary ms-1'
                : 'bi bi-chevron-down text-primary ms-1';
            
            // Reset other headers
            sortableHeaders.forEach(h => {
                if (h !== this) {
                    h.dataset.order = 'asc';
                    const otherIcon = h.querySelector('i');
                    if (otherIcon) {
                        otherIcon.className = 'bi bi-chevron-expand text-muted ms-1';
                    }
                }
            });
        });
    });
    
    // ═══════════════════════════════════════════════════════════════════════
    // Lazy Load Images (if any product images)
    // ═══════════════════════════════════════════════════════════════════════
    if ('IntersectionObserver' in window) {
        const lazyImages = document.querySelectorAll('img[data-src]');
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        lazyImages.forEach(img => imageObserver.observe(img));
    }
    
    // ═══════════════════════════════════════════════════════════════════════
    // Auto-Dismiss Alerts
    // ═══════════════════════════════════════════════════════════════════════
    const alerts = document.querySelectorAll('.alert-modern');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-20px)';
            setTimeout(() => alert.remove(), 500);
        }, 5000); // Dismiss after 5 seconds
    });
    
    console.log('✨ KMS Modern Lists initialized');
});
