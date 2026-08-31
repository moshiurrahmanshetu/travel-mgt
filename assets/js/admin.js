/**
 * Admin Panel JavaScript
 * Tour & Travel Booking Management System
 */

(function() {
    'use strict';

    // 1. Immediately apply saved sidebar state to prevent layout flickering
    try {
        const savedState = localStorage.getItem('travel_mgt_sidebar_collapsed');
        if (savedState === 'true' && window.innerWidth >= 992) {
            document.body.classList.add('sidebar-collapsed');
        }
    } catch (e) {
        console.warn('localStorage is not accessible:', e);
    }

    document.addEventListener('DOMContentLoaded', function() {
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const backdrop = document.querySelector('.sidebar-backdrop');
        const sidebar = document.getElementById('admin-sidebar');
        let tooltipInstances = [];

        // Function to initialize tooltips for collapsed sidebar icons
        function updateSidebarTooltips() {
            // Destroy existing tooltips
            tooltipInstances.forEach(t => {
                if (t && typeof t.dispose === 'function') {
                    t.dispose();
                }
            });
            tooltipInstances = [];

            if (typeof bootstrap === 'undefined' || !bootstrap.Tooltip) {
                return;
            }

            const isDesktop = window.innerWidth >= 992;
            const isCollapsed = document.body.classList.contains('sidebar-collapsed');

            if (isDesktop && isCollapsed) {
                const navLinks = document.querySelectorAll('.sidebar-nav-link');
                navLinks.forEach(link => {
                    const textEl = link.querySelector('.nav-link-text');
                    if (textEl) {
                        const title = textEl.textContent.trim();
                        const tooltip = new bootstrap.Tooltip(link, {
                            title: title,
                            placement: 'right',
                            trigger: 'hover',
                            container: 'body'
                        });
                        tooltipInstances.push(tooltip);
                    }
                });
            }
        }

        // Initialize tooltips on load
        updateSidebarTooltips();

        // 2. Toggle Sidebar logic
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function(e) {
                e.preventDefault();
                const isMobile = window.innerWidth < 992;

                if (isMobile) {
                    // Mobile Drawer
                    document.body.classList.toggle('sidebar-mobile-open');
                    if (backdrop) {
                        backdrop.classList.toggle('show');
                    }
                } else {
                    // Desktop Collapse
                    document.body.classList.toggle('sidebar-collapsed');
                    const isCollapsed = document.body.classList.contains('sidebar-collapsed');
                    try {
                        localStorage.setItem('travel_mgt_sidebar_collapsed', isCollapsed ? 'true' : 'false');
                    } catch (err) {
                        // ignore localStorage write errors
                    }
                    updateSidebarTooltips();
                }
            });
        }

        // 3. Mobile Backdrop click to close sidebar
        if (backdrop) {
            backdrop.addEventListener('click', function() {
                document.body.classList.remove('sidebar-mobile-open');
                backdrop.classList.remove('show');
            });
        }

        // Close mobile sidebar when clicking links on mobile
        if (sidebar) {
            sidebar.addEventListener('click', function(e) {
                if (window.innerWidth < 992 && e.target.closest('a.sidebar-nav-link')) {
                    document.body.classList.remove('sidebar-mobile-open');
                    if (backdrop) {
                        backdrop.classList.remove('show');
                    }
                }
            });
        }

        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 992) {
                document.body.classList.remove('sidebar-mobile-open');
                if (backdrop) {
                    backdrop.classList.remove('show');
                }
            }
            updateSidebarTooltips();
        });

        // 4. Password Toggle
        const togglePasswordButtons = document.querySelectorAll('.toggle-password');
        togglePasswordButtons.forEach(button => {
            button.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const input = document.getElementById(targetId);
                const icon = this.querySelector('i');

                if (input) {
                    if (input.type === 'password') {
                        input.type = 'text';
                        if (icon) {
                            icon.classList.remove('bi-eye');
                            icon.classList.add('bi-eye-slash');
                        }
                    } else {
                        input.type = 'password';
                        if (icon) {
                            icon.classList.remove('bi-eye-slash');
                            icon.classList.add('bi-eye');
                        }
                    }
                }
            });
        });

        // 5. Live Avatar Preview before upload
        const avatarInput = document.getElementById('avatar-input');
        const avatarPreview = document.getElementById('avatar-preview-img');
        const avatarFallback = document.getElementById('avatar-preview-fallback');

        if (avatarInput) {
            avatarInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    // Check size (2MB)
                    if (file.size > 2 * 1024 * 1024) {
                        alert('Selected image exceeds maximum allowed size of 2MB.');
                        this.value = '';
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function(evt) {
                        if (avatarPreview) {
                            avatarPreview.src = evt.target.result;
                            avatarPreview.classList.remove('d-none');
                        }
                        if (avatarFallback) {
                            avatarFallback.classList.add('d-none');
                        }
                    };
                    reader.readAsDataURL(file);
                }
            });
        }

        // 6. Auto-dismiss alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert-dismissible');
        alerts.forEach(alert => {
            setTimeout(() => {
                if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                    const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                    bsAlert.close();
                }
            }, 5000);
        });
    });
})();
