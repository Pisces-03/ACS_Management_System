<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Cold Storage Management System')</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <!-- Custom CSS -->
    <link href="{{ asset('css/main.css') }}" rel="stylesheet">

    <style>
        /* Prevent horizontal scrolling */
        html, body {
            overflow-x: hidden !important;
            max-width: 100vw !important;
        }
        
        /* Prevent notification icon from moving when clicked */
        #notificationDropdown {
            position: relative !important;
            transform: none !important;
            transition: none !important;
        }
        
        #notificationDropdown:focus,
        #notificationDropdown:active,
        #notificationDropdown:hover {
            transform: none !important;
            position: relative !important;
            outline: none !important;
            box-shadow: none !important;
        }
        
        #notificationDropdown .bi-bell {
            position: relative !important;
            transform: none !important;
            transition: none !important;
        }
        
        .nav-item.dropdown.show #notificationDropdown {
            transform: none !important;
            position: relative !important;
        }
        
        /* Fix dropdown positioning and layout */
        .notification-dropdown {
            position: absolute !important;
            right: 0 !important;
            left: auto !important;
            transform: none !important;
            margin-top: 0.5rem !important;
            display: none !important;
        }
        
        /* Ensure dropdown is visible when show class is present */
        .dropdown.show .notification-dropdown,
        .notification-dropdown.show {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        
        /* Hide dropdown when not showing */
        .notification-dropdown:not(.show) {
            display: none !important;
        }
        
        /* Fix text wrapping in notifications */
        .notification-dropdown .dropdown-item {
            white-space: normal !important;
            word-wrap: break-word !important;
            overflow-wrap: break-word !important;
            padding: 0.75rem 1rem !important;
        }
        
        .notification-dropdown .dropdown-item strong {
            display: block;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        
        .notification-dropdown .dropdown-item .small {
            display: block;
            word-wrap: break-word;
            overflow-wrap: break-word;
            margin-top: 0.25rem;
        }
        
        @media (max-width: 576px) {
            .notification-dropdown {
                right: 10px !important;
                left: 10px !important;
                max-width: calc(100vw - 20px) !important;
                min-width: calc(100vw - 20px) !important;
            }
        }
    </style>

    @stack('styles')
    @stack('head')
    
    <!-- * Global approval functions - always available to prevent "not defined" errors -->
    <script>
    // * Queue for pending approval calls
    window._pendingApprovalCalls = window._pendingApprovalCalls || [];
    
    // * Stub function for openApprovalModal - will be overridden by view-specific implementations
    if (typeof window.openApprovalModal === 'undefined') {
        window.openApprovalModal = function(requestId, actionType) {
            // * Queue the call for when the real implementation is available
            window._pendingApprovalCalls.push({requestId: requestId, action: actionType});
            // * Try again after a short delay
            setTimeout(function() {
                if (typeof window.openApprovalModal === 'function' && window.openApprovalModal.toString().indexOf('Queue the call') === -1) {
                    window.openApprovalModal(requestId, actionType);
                }
            }, 100);
        };
    }
    
    // * Define approval functions globally to prevent "not defined" errors
    // * These will be overridden by view-specific implementations if needed
    if (typeof window.approveRequest === 'undefined') {
        window.approveRequest = function(requestId) {
            if (typeof window.openApprovalModal === 'function') {
                window.openApprovalModal(requestId, 'approve');
            } else {
                window._pendingApprovalCalls.push({requestId: requestId, action: 'approve'});
            }
        };
    }
    
    if (typeof window.rejectRequest === 'undefined') {
        window.rejectRequest = function(requestId) {
            if (typeof window.openApprovalModal === 'function') {
                window.openApprovalModal(requestId, 'reject');
            } else {
                window._pendingApprovalCalls.push({requestId: requestId, action: 'reject'});
            }
        };
    }
    </script>
</head>
<body class="d-flex flex-column min-vh-100">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="{{ route('dashboard') }}">
                <i class="bi bi-snow2"></i> ACS Management
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                @auth
                <ul class="navbar-nav navbar-nav-centered">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}" {{ request()->routeIs('dashboard') ? 'aria-current="page"' : '' }}>
                            <i class="bi bi-speedometer2"></i> <span class="nav-link-text">Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('attendance.*') ? 'active' : '' }}" href="{{ route('attendance.index') }}" {{ request()->routeIs('attendance.*') ? 'aria-current="page"' : '' }}>
                            <i class="bi bi-clock"></i> <span class="nav-link-text">Attendance</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('tasks.*') ? 'active' : '' }}" href="{{ route('tasks.index') }}" {{ request()->routeIs('tasks.*') ? 'aria-current="page"' : '' }}>
                            <i class="bi bi-list-task"></i> <span class="nav-link-text">Tasks</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('maintenance.*') ? 'active' : '' }}" href="{{ route('maintenance.index') }}" {{ request()->routeIs('maintenance.*') ? 'aria-current="page"' : '' }}>
                            <i class="bi bi-tools"></i> <span class="nav-link-text">Maintenance</span>
                        </a>
                    </li>
                    @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('preventive-maintenance.*') ? 'active' : '' }}" href="{{ route('preventive-maintenance.index') }}" {{ request()->routeIs('preventive-maintenance.*') ? 'aria-current="page"' : '' }}>
                            <i class="bi bi-calendar-check"></i> <span class="nav-link-text">Preventive</span>
                        </a>
                    </li>
                    @endif
                    @if(auth()->user()->role === 'employee')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('material-requests.*') ? 'active' : '' }}" href="{{ route('material-requests.index') }}" {{ request()->routeIs('material-requests.*') ? 'aria-current="page"' : '' }}>
                            <i class="bi bi-box-seam"></i> <span class="nav-link-text">Materials</span>
                        </a>
                    </li>
                    @endif
                    @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('pending-approvals.*') ? 'active' : '' }}" href="{{ route('pending-approvals.index') }}" {{ request()->routeIs('pending-approvals.*') ? 'aria-current="page"' : '' }}>
                            <i class="bi bi-clipboard-check"></i> <span class="nav-link-text">Pending Approvals</span>
                        </a>
                    </li>
                    @endif
                    @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('employees.*') ? 'active' : '' }}" href="{{ route('employees.index') }}" {{ request()->routeIs('employees.*') ? 'aria-current="page"' : '' }}>
                            <i class="bi bi-people"></i> <span class="nav-link-text">Employees</span>
                        </a>
                    </li>
                    @endif
                    @if(auth()->user()->role !== 'employee')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('reports.*') && !request()->routeIs('reports.customer-feedback') && !request()->routeIs('feedback.*') ? 'active' : '' }}" href="{{ route('reports.index') }}" {{ request()->routeIs('reports.*') && !request()->routeIs('reports.customer-feedback') && !request()->routeIs('feedback.*') ? 'aria-current="page"' : '' }}>
                            <i class="bi bi-graph-up"></i> <span class="nav-link-text">Reports</span>
                        </a>
                    </li>
                    @endif
                    @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('reports.customer-feedback') ? 'active' : '' }}" href="{{ route('reports.customer-feedback') }}" {{ request()->routeIs('reports.customer-feedback') ? 'aria-current="page"' : '' }}>
                            <i class="bi bi-emoji-smile"></i> <span class="nav-link-text">Customer Evaluations</span>
                        </a>
                    </li>
                    @endif
                    @if(auth()->user()->role === 'employee')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('feedback.*') ? 'active' : '' }}" href="{{ route('feedback.my') }}" {{ request()->routeIs('feedback.*') ? 'aria-current="page"' : '' }}>
                            <i class="bi bi-emoji-smile"></i> <span class="nav-link-text">Customer Evaluation</span>
                        </a>
                    </li>
                    @endif
                    @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('audit-logs.*') ? 'active' : '' }}" href="{{ route('audit-logs.index') }}" {{ request()->routeIs('audit-logs.*') ? 'aria-current="page"' : '' }}>
                            <i class="bi bi-clipboard-data"></i> <span class="nav-link-text">Audit Logs</span>
                        </a>
                    </li>
                    @endif
                </ul>

                <ul class="navbar-nav">
                    <!-- Notifications Dropdown -->
                    <li class="nav-item dropdown me-2">
                        <a class="nav-link position-relative" href="#" id="notificationDropdown" role="button" aria-expanded="false" style="outline: none; border: none; box-shadow: none !important; cursor: pointer;">
                            <i class="bi bi-bell" style="display: inline-block; position: relative;"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notificationBadge" style="display: none;">
                                0
                            </span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end notification-dropdown" style="min-width: 320px; max-width: min(320px, calc(100vw - 20px)); max-height: 450px; display: flex; flex-direction: column; padding: 0;">
                            <h6 class="dropdown-header mb-0 px-3 py-2">Notifications</h6>
                            <hr class="dropdown-divider my-0">
                            <div id="notificationList" style="flex: 1; overflow-y: auto; min-height: 0; max-height: 350px;">
                                <ul class="list-unstyled mb-0">
                                <li><span class="dropdown-item text-muted">Loading notifications...</span></li>
                                </ul>
                            </div>
                            <hr class="dropdown-divider my-0" style="flex-shrink: 0;">
                            <a class="dropdown-item text-center small py-2" href="#" onclick="markAllNotificationsRead(); return false;" style="flex-shrink: 0;">Mark all as read</a>
                        </div>
                    </li>
                    
                    <!-- User Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> {{ auth()->user()->full_name }}
                            <span class="badge bg-light text-primary">{{ auth()->user()->branch_name }}</span>
                            <span class="badge bg-secondary">{{ ucfirst(auth()->user()->role) }}</span>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('profile') }}"><i class="bi bi-person"></i> Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="dropdown-item">
                                        <i class="bi bi-box-arrow-right"></i> Logout
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Alerts -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show m-0" role="alert">
        <i class="bi bi-check-circle"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show m-0" role="alert">
        <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <!-- Main Content -->
    <main class="py-4 flex-grow-1">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-light text-center py-3 mt-auto">
        <div class="container">
            <p class="mb-0 text-muted">
                &copy; {{ date('Y') }} Asia Cold Storage Corporation. All rights reserved.
            </p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Custom JS -->
    <script src="{{ asset('js/utils.js') }}"></script>

    <!-- Session Management -->
    <script>
        let sessionWarningShown = false;
        let idleTimer;
        let warningTimer;
        
        // Session timeout settings (in minutes)
        const SESSION_TIMEOUT = 120; // 2 hours
        const WARNING_TIME = 110; // 1 hour 50 minutes
        
        function resetIdleTimer() {
            clearTimeout(idleTimer);
            clearTimeout(warningTimer);
            sessionWarningShown = false;
            
            // Show warning before session expires
            warningTimer = setTimeout(showSessionWarning, WARNING_TIME * 60 * 1000);
            
            // Auto logout when session expires
            idleTimer = setTimeout(logoutUser, SESSION_TIMEOUT * 60 * 1000);
        }
        
        function showSessionWarning() {
            if (sessionWarningShown) return;
            sessionWarningShown = true;
            
            Swal.fire({
                title: 'Session Expiring',
                text: 'Your session will expire in 10 minutes due to inactivity. Click "Stay Logged In" to continue.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Stay Logged In',
                cancelButtonText: 'Logout Now',
                allowOutsideClick: false,
                timer: 10 * 60 * 1000, // 10 minutes
                timerProgressBar: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Reset the timer
                    resetIdleTimer();
                } else {
                    logoutUser();
                }
            });
        }
        
        function logoutUser() {
            Swal.fire({
                title: 'Session Expired',
                text: 'You have been logged out due to inactivity.',
                icon: 'info',
                confirmButtonText: 'OK'
            }).then(() => {
                // Submit logout form
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("logout") }}';
                
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                
                form.appendChild(csrfInput);
                document.body.appendChild(form);
                form.submit();
            });
        }
        
        // Track user activity
        document.addEventListener('DOMContentLoaded', function() {
            resetIdleTimer();
            
            // Reset timer on user activity
            ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'].forEach(event => {
                document.addEventListener(event, resetIdleTimer, true);
            });
            
            // Load notifications on page load
            loadNotifications();
            
            // Refresh notifications every 30 seconds
            setInterval(loadNotifications, 30000);
        });
        
        // Notification functionality
        function loadNotifications() {
            fetch('{{ route("notifications.unread") }}', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
                .then(response => {
                    // Check if response is OK and is JSON
                    if (!response.ok) {
                        return response.text().then(text => {
                            // Try to parse as JSON first
                            try {
                                return JSON.parse(text);
                            } catch (e) {
                                // If not JSON, return error object
                                throw new Error('Server error: ' + response.status);
                            }
                        });
                    }
                    // Check content type before parsing
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        return response.json();
                    } else {
                        // Not JSON, return text as error
                        return response.text().then(text => {
                            throw new Error('Invalid response format');
                        });
                    }
                })
                .then(data => {
                    // Only update if we have valid data
                    if (data && typeof data === 'object') {
                        updateNotificationBadge(data.count || 0);
                        updateNotificationList(data.notifications || []);
                    }
                })
                .catch(error => {
                    console.error('Error loading notifications:', error);
                    // Silently fail - don't show errors to user for notification loading
                    // Just set count to 0 to avoid UI issues
                    updateNotificationBadge(0);
                    updateNotificationList([]);
                });
        }
        
        function updateNotificationBadge(count) {
            const badge = document.getElementById('notificationBadge');
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.style.display = 'block';
            } else {
                badge.style.display = 'none';
            }
        }
        
        function updateNotificationList(notifications) {
            const list = document.getElementById('notificationList');
            
            if (notifications.length === 0) {
                list.innerHTML = '<ul class="list-unstyled mb-0"><li><span class="dropdown-item text-muted">No notifications</span></li></ul>';
                return;
            }
            
            let html = '<ul class="list-unstyled mb-0">';
            notifications.forEach(notification => {
                const timeAgo = new Date(notification.created_at).toLocaleString();
                const isRead = notification.is_read || false;
                
                // Style based on read status and priority
                let itemClass = 'dropdown-item py-2';
                let titleClass = 'small';
                let readIndicator = '';
                
                if (isRead) {
                    itemClass += ' bg-light text-muted';
                    titleClass += ' text-muted';
                    readIndicator = '<i class="bi bi-check-circle-fill text-success" style="width: 18px; display: inline-block; text-align: center;"></i>';
                } else {
                    // Unread notifications
                    if (notification.priority === 'high' || notification.priority === 'urgent') {
                        titleClass += ' text-danger fw-bold';
                    } else if (notification.priority === 'medium') {
                        titleClass += ' text-warning';
                    } else {
                        titleClass += ' text-dark';
                    }
                    readIndicator = '<span class="badge bg-primary rounded-pill" style="font-size: 0.5rem; min-width: 18px; display: inline-block; text-align: center;">NEW</span>';
                }
                
                // * Determine navigation URL based on notification type
                // * Default to dashboard for notifications without specific URLs (since notifications.index returns JSON)
                let notificationUrl = '{{ route("dashboard") }}';
                if (notification.type === 'maintenance_findings_submitted' && notification.data && notification.data.maintenance_log_id) {
                    notificationUrl = `/maintenance/${notification.data.maintenance_log_id}`;
                } else if (notification.type === 'task_assigned' && notification.data && notification.data.task_id) {
                    notificationUrl = `/tasks/${notification.data.task_id}`;
                } else if (notification.type === 'approval_needed' && notification.data && notification.data.request_id) {
                    // * Material request approval needed (type: approval_needed)
                    notificationUrl = `/material-requests/${notification.data.request_id}`;
                } else if (notification.type === 'material_request_approval_needed' && notification.data && notification.data.request_id) {
                    notificationUrl = `/material-requests/${notification.data.request_id}`;
                } else if (notification.type === 'material_request_status_changed' && notification.data && notification.data.request_id) {
                    notificationUrl = `/material-requests/${notification.data.request_id}`;
                } else if (notification.type === 'branch_change_request' && notification.data && notification.data.employee_id) {
                    notificationUrl = `/employees/${notification.data.employee_id}`;
                } else if (notification.data && notification.data.action_url) {
                    // * Use action_url from notification data if available
                    notificationUrl = notification.data.action_url;
                }
                
                html += `
                    <li>
                        <a class="${itemClass}" href="${notificationUrl}" onclick="event.preventDefault(); markNotificationRead(${notification.id}, '${notificationUrl}');" style="display: block; width: 100%;">
                            <div class="d-flex align-items-start" style="width: 100%;">
                                <span style="width: 24px; flex-shrink: 0; margin-right: 8px; display: flex; align-items: flex-start; padding-top: 2px;">${readIndicator}</span>
                                <div class="flex-grow-1" style="min-width: 0; flex: 1;">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px;">
                                        <strong class="${titleClass}" style="word-wrap: break-word; overflow-wrap: break-word; flex: 1; min-width: 0;">${notification.title}</strong>
                                        <small class="text-muted" style="white-space: nowrap; flex-shrink: 0; font-size: 0.75rem;">${timeAgo}</small>
                                    </div>
                                    <div class="small ${isRead ? 'text-muted' : 'text-secondary'} mt-1" style="word-wrap: break-word; overflow-wrap: break-word; width: 100%;">${notification.message}</div>
                                </div>
                            </div>
                        </a>
                    </li>
                `;
            });
            html += '</ul>';
            
            list.innerHTML = html;
        }
        
        function markNotificationRead(notificationId, redirectUrl = null) {
            // * Close dropdown before redirecting
            const dropdownParent = document.getElementById('notificationDropdown')?.closest('.dropdown');
            const dropdownMenu = document.getElementById('notificationDropdown')?.nextElementSibling;
            if (dropdownParent && dropdownMenu) {
                dropdownParent.classList.remove('show');
                dropdownMenu.classList.remove('show');
                dropdownMenu.style.display = 'none';
                document.getElementById('notificationDropdown')?.setAttribute('aria-expanded', 'false');
                document.body.style.overflow = '';
            }
            
            // * Mark as read and redirect
            fetch(`/notifications/${notificationId}/read`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                    // * Navigate to the relevant page if URL is provided
                    if (redirectUrl && redirectUrl !== '#') {
                        window.location.href = redirectUrl;
                } else {
                    // * Fallback to dashboard if no specific URL
                    window.location.href = '{{ route("dashboard") }}';
                }
            })
            .catch(error => {
                console.error('Error marking notification as read:', error);
                // * Still navigate even if marking as read fails
                if (redirectUrl && redirectUrl !== '#') {
                    window.location.href = redirectUrl;
                } else {
                    // * Fallback to dashboard
                    window.location.href = '{{ route("dashboard") }}';
                }
            });
        }
        
        function markAllNotificationsRead() {
            fetch('/notifications/mark-all-read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Refresh the notification list to update read status
                    loadNotifications();
                    // Keep dropdown open - don't close it
                    
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'All notifications marked as read',
                        showConfirmButton: false,
                        timer: 3000
                    });
                }
            })
            .catch(error => {
                console.error('Error marking all notifications as read:', error);
            });
        }
        
        // * Handle notification dropdown behavior
        document.addEventListener('DOMContentLoaded', function() {
            const notificationDropdown = document.getElementById('notificationDropdown');
            const dropdownParent = notificationDropdown ? notificationDropdown.closest('.dropdown') : null;
            const dropdownMenu = notificationDropdown ? notificationDropdown.nextElementSibling : null;
            
            if (!notificationDropdown || !dropdownParent || !dropdownMenu) {
                return;
            }
            
            // Initialize dropdown state - ensure it starts closed
            dropdownParent.classList.remove('show');
            dropdownMenu.classList.remove('show');
            dropdownMenu.style.display = 'none';
            notificationDropdown.setAttribute('aria-expanded', 'false');
            
            // Function to check if dropdown is open
            function isOpen() {
                const hasShowClass = dropdownParent.classList.contains('show') && dropdownMenu.classList.contains('show');
                const computedDisplay = window.getComputedStyle(dropdownMenu).display;
                const isVisible = computedDisplay !== 'none' && computedDisplay !== '';
                return hasShowClass && isVisible;
            }
            
            // Function to open dropdown
            function openDropdown() {
                dropdownParent.classList.add('show');
                dropdownMenu.classList.add('show');
                notificationDropdown.setAttribute('aria-expanded', 'true');
                dropdownMenu.style.display = 'flex';
                dropdownMenu.style.visibility = 'visible';
                dropdownMenu.style.opacity = '1';
                    if (window.innerWidth <= 767) {
                        document.body.style.overflow = 'hidden';
                    }
            }
            
            // Function to close dropdown
            function closeDropdown() {
                dropdownParent.classList.remove('show');
                dropdownMenu.classList.remove('show');
                notificationDropdown.setAttribute('aria-expanded', 'false');
                dropdownMenu.style.display = 'none';
                dropdownMenu.style.visibility = 'hidden';
                dropdownMenu.style.opacity = '0';
                document.body.style.overflow = '';
            }
            
            // Toggle dropdown on bell icon click
            notificationDropdown.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                if (isOpen()) {
                    closeDropdown();
                } else {
                    openDropdown();
                    // Load notifications when opening
                    if (typeof loadNotifications === 'function') {
                        loadNotifications();
                    }
                }
            }, true);
            
            // Prevent closing when clicking inside dropdown menu
            dropdownMenu.addEventListener('click', function(e) {
                e.stopPropagation();
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (isOpen() && 
                    !notificationDropdown.contains(e.target) && 
                    !dropdownMenu.contains(e.target)) {
                    closeDropdown();
                }
            });
            
            // Disable any Bootstrap dropdown instance that might exist
            try {
                const existingDropdown = bootstrap.Dropdown.getInstance(notificationDropdown);
                if (existingDropdown) {
                    existingDropdown.dispose();
                }
            } catch (e) {
                // Ignore errors
            }
            
            // Make sure no data-bs-toggle attribute exists
            if (notificationDropdown.hasAttribute('data-bs-toggle')) {
                notificationDropdown.removeAttribute('data-bs-toggle');
            }
        });
    </script>

    @stack('scripts')
</body>
</html>
