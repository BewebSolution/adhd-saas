/**
 * Mobile Enhancements for ADHD Task Manager
 * Migliora l'esperienza su dispositivi mobili
 */

(function() {
    'use strict';

    // Detect if mobile
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    const isTablet = /iPad|Android/i.test(navigator.userAgent) && !(/Mobile/i.test(navigator.userAgent));

    /**
     * Initialize mobile enhancements
     */
    function initMobileEnhancements() {
        if (window.innerWidth <= 768 || isMobile) {
            // IMPORTANT: Add toggled class to hide sidebar on mobile by default
            closeSidebarMobile();

            // Add mobile-specific event listeners
            initMobileSidebar();
            initMobileTables();
            initMobileFilters();
            initTouchOptimizations();
            initMobileFAB();
        }

        // Handle orientation changes
        window.addEventListener('orientationchange', handleOrientationChange);
        window.addEventListener('resize', handleResize);
    }

    /**
     * Close sidebar on mobile by default
     */
    function closeSidebarMobile() {
        const sidebar = document.getElementById('accordionSidebar');
        if (sidebar && window.innerWidth <= 768) {
            // On mobile, toggled = hidden (opposite of desktop)
            sidebar.classList.add('toggled');
            // Ensure no inline styles interfere
            sidebar.style.removeProperty('width');
            sidebar.style.removeProperty('overflow-x');
        }
    }

    /**
     * Initialize mobile sidebar behavior
     */
    function initMobileSidebar() {
        const sidebarToggle = document.getElementById('sidebarToggleTop');
        const sidebar = document.getElementById('accordionSidebar');
        const contentWrapper = document.getElementById('content-wrapper');

        if (sidebarToggle && sidebar) {
            // Remove any existing click handlers
            const newToggle = sidebarToggle.cloneNode(true);
            sidebarToggle.parentNode.replaceChild(newToggle, sidebarToggle);

            // Add our mobile-specific handler
            newToggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                // Toggle sidebar (remember: on mobile, toggled = hidden)
                sidebar.classList.toggle('toggled');

                // Add overlay when sidebar is VISIBLE (not toggled)
                if (!sidebar.classList.contains('toggled')) {
                    createOverlay();
                } else {
                    removeOverlay();
                }
            });

            // Close sidebar when clicking outside
            document.addEventListener('click', function(e) {
                if (!sidebar.contains(e.target) &&
                    !sidebarToggle.contains(e.target) &&
                    !sidebar.classList.contains('toggled')) {
                    sidebar.classList.add('toggled');
                    removeOverlay();
                }
            });

            // Close sidebar when selecting a menu item
            const navLinks = sidebar.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                        setTimeout(() => {
                            sidebar.classList.add('toggled');
                            removeOverlay();
                        }, 300);
                    }
                });
            });
        }
    }

    /**
     * Create overlay for sidebar
     */
    function createOverlay() {
        if (!document.getElementById('sidebar-overlay')) {
            const overlay = document.createElement('div');
            overlay.id = 'sidebar-overlay';
            overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 9998;
                transition: opacity 0.3s;
            `;
            document.body.appendChild(overlay);

            overlay.addEventListener('click', function() {
                const sidebar = document.getElementById('accordionSidebar');
                if (sidebar) {
                    sidebar.classList.add('toggled');
                }
                removeOverlay();
            });
        }
    }

    /**
     * Remove overlay
     */
    function removeOverlay() {
        const overlay = document.getElementById('sidebar-overlay');
        if (overlay) {
            overlay.remove();
        }
    }

    /**
     * Convert tables to cards on mobile
     */
    function initMobileTables() {
        if (window.innerWidth <= 768) {
            const tables = document.querySelectorAll('.table-responsive table');

            tables.forEach(table => {
                // Skip if already converted
                if (table.dataset.mobileConverted === 'true') return;

                // Add swipe hint
                const hint = document.createElement('div');
                hint.className = 'swipe-hint text-muted small mb-2';
                hint.innerHTML = '<i class="fas fa-hand-point-left"></i> Scorri per vedere tutto';
                table.parentElement.insertBefore(hint, table.parentElement.firstChild);

                // Mark as converted
                table.dataset.mobileConverted = 'true';
            });

            // Convert task table to cards if on tasks page
            convertTaskTableToCards();
        }
    }

    /**
     * Convert task table to mobile-friendly cards
     */
    function convertTaskTableToCards() {
        const taskTable = document.querySelector('.table-responsive table');
        if (!taskTable || !window.location.pathname.includes('/tasks')) return;

        const tbody = taskTable.querySelector('tbody');
        if (!tbody) return;

        const rows = tbody.querySelectorAll('tr');
        const cardContainer = document.createElement('div');
        cardContainer.className = 'task-cards-container d-block d-md-none';

        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length < 2) return;

            const card = document.createElement('div');
            card.className = 'card mb-3 shadow-sm';

            const titleCell = cells[2]; // Title column
            const statusCell = cells[4]; // Status column
            const priorityCell = cells[5]; // Priority column
            const actionsCell = cells[cells.length - 1]; // Actions column

            card.innerHTML = `
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="card-title mb-1">${titleCell ? titleCell.innerHTML : ''}</h6>
                        ${statusCell ? statusCell.innerHTML : ''}
                    </div>
                    <div class="mb-2">
                        ${priorityCell ? priorityCell.innerHTML : ''}
                    </div>
                    <div class="btn-group btn-group-sm w-100">
                        ${actionsCell ? actionsCell.innerHTML : ''}
                    </div>
                </div>
            `;

            cardContainer.appendChild(card);
        });

        // Insert cards and hide table on mobile
        if (cardContainer.children.length > 0) {
            taskTable.parentElement.parentElement.appendChild(cardContainer);
            taskTable.parentElement.classList.add('d-none', 'd-md-block');
        }
    }

    /**
     * Initialize collapsible filters on mobile
     */
    function initMobileFilters() {
        const filterCards = document.querySelectorAll('.card:has(form[method="GET"])');

        filterCards.forEach(card => {
            if (window.innerWidth <= 768) {
                const cardBody = card.querySelector('.card-body');
                if (!cardBody) return;

                // Create toggle button
                const toggleBtn = document.createElement('button');
                toggleBtn.className = 'btn btn-outline-primary w-100 mb-3 d-md-none';
                toggleBtn.innerHTML = '<i class="fas fa-filter"></i> Mostra Filtri';

                // Wrap original content
                const content = document.createElement('div');
                content.className = 'filter-content collapse';
                content.innerHTML = cardBody.innerHTML;

                // Replace content
                cardBody.innerHTML = '';
                cardBody.appendChild(toggleBtn);
                cardBody.appendChild(content);

                // Add toggle functionality
                toggleBtn.addEventListener('click', function() {
                    content.classList.toggle('show');
                    toggleBtn.innerHTML = content.classList.contains('show')
                        ? '<i class="fas fa-times"></i> Nascondi Filtri'
                        : '<i class="fas fa-filter"></i> Mostra Filtri';
                });
            }
        });
    }

    /**
     * Initialize touch optimizations
     */
    function initTouchOptimizations() {
        // Prevent 300ms delay on touch devices
        if ('ontouchstart' in window) {
            document.addEventListener('touchstart', function() {}, {passive: true});
        }

        // Add touch feedback
        const buttons = document.querySelectorAll('.btn, .nav-link');
        buttons.forEach(btn => {
            btn.addEventListener('touchstart', function() {
                this.style.opacity = '0.7';
            }, {passive: true});

            btn.addEventListener('touchend', function() {
                this.style.opacity = '1';
            }, {passive: true});
        });

        // Improve scroll performance
        const scrollElements = document.querySelectorAll('.table-responsive, .sidebar');
        scrollElements.forEach(el => {
            el.style.webkitOverflowScrolling = 'touch';
        });
    }

    /**
     * Initialize Floating Action Button for mobile
     */
    function initMobileFAB() {
        if (window.innerWidth <= 768 && !document.getElementById('mobile-fab')) {
            const fab = document.createElement('div');
            fab.id = 'mobile-fab';
            fab.className = 'fab-container d-md-none';
            fab.innerHTML = `
                <div class="fab-menu" id="fabMenu">
                    <div class="fab-menu-item">
                        <a href="${BASE_URL}/tasks/create" class="btn btn-success btn-sm shadow">
                            <i class="fas fa-plus"></i> Nuovo Task
                        </a>
                    </div>
                    <div class="fab-menu-item">
                        <button onclick="PomodoroADHD.quickStart()" class="btn btn-danger btn-sm shadow">
                            🍅 Pomodoro
                        </button>
                    </div>
                    <div class="fab-menu-item">
                        <button onclick="getSmartFocus()" class="btn btn-info btn-sm shadow">
                            <i class="fas fa-brain"></i> Smart Focus
                        </button>
                    </div>
                </div>
                <button class="fab-button shadow" id="fabButton">
                    <i class="fas fa-plus"></i>
                </button>
            `;

            document.body.appendChild(fab);

            // FAB menu toggle
            const fabButton = document.getElementById('fabButton');
            const fabMenu = document.getElementById('fabMenu');

            fabButton.addEventListener('click', function() {
                fabMenu.classList.toggle('show');
                this.innerHTML = fabMenu.classList.contains('show')
                    ? '<i class="fas fa-times"></i>'
                    : '<i class="fas fa-plus"></i>';
            });

            // Close menu when clicking outside
            document.addEventListener('click', function(e) {
                if (!fab.contains(e.target)) {
                    fabMenu.classList.remove('show');
                    fabButton.innerHTML = '<i class="fas fa-plus"></i>';
                }
            });
        }
    }

    /**
     * Handle orientation change
     */
    function handleOrientationChange() {
        // Adjust layout after orientation change
        setTimeout(() => {
            initMobileTables();
            adjustForLandscape();
        }, 300);
    }

    /**
     * Handle window resize
     */
    let resizeTimer;
    function handleResize() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            if (window.innerWidth > 768) {
                // Remove mobile enhancements on desktop
                removeOverlay();
                const sidebar = document.getElementById('accordionSidebar');
                if (sidebar) {
                    sidebar.classList.remove('toggled');
                }
            } else {
                // Reapply mobile enhancements
                initMobileEnhancements();
            }
        }, 250);
    }

    /**
     * Adjust for landscape orientation
     */
    function adjustForLandscape() {
        if (window.orientation === 90 || window.orientation === -90) {
            document.body.classList.add('landscape-mode');
        } else {
            document.body.classList.remove('landscape-mode');
        }
    }

    /**
     * Improve form inputs for mobile
     */
    function improveFormInputs() {
        // Set input types for better mobile keyboards
        const dateInputs = document.querySelectorAll('input[name*="date"], input[name*="due"]');
        dateInputs.forEach(input => {
            if (!input.type || input.type === 'text') {
                input.type = 'date';
            }
        });

        const timeInputs = document.querySelectorAll('input[name*="time"], input[name*="hour"]');
        timeInputs.forEach(input => {
            if (!input.type || input.type === 'text') {
                input.type = 'time';
            }
        });

        const emailInputs = document.querySelectorAll('input[name*="email"], input[name*="mail"]');
        emailInputs.forEach(input => {
            if (!input.type || input.type === 'text') {
                input.type = 'email';
            }
        });

        const numberInputs = document.querySelectorAll('input[name*="hour"], input[name*="minute"], input[name*="estimated"]');
        numberInputs.forEach(input => {
            if (!input.type || input.type === 'text') {
                input.type = 'number';
                input.inputMode = 'numeric';
            }
        });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMobileEnhancements);
    } else {
        initMobileEnhancements();
    }

    // Also improve form inputs
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', improveFormInputs);
    } else {
        improveFormInputs();
    }

})();