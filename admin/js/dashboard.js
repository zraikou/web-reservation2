// Setup auto-refresh for dashboard data
function setupAutoRefresh() {
    // Default refresh interval: 30 seconds (30000 ms)
    const refreshInterval = 30000;
    
    // Store the interval ID so it can be cleared if needed
    window.dashboardRefreshInterval = setInterval(() => {
        console.log('Auto-refreshing dashboard data...');
        
        // Refresh only the data, not the UI indicators
        Promise.all([
            fetchDashboardData(false),
            fetchRecentBookings(false)
        ])
        .then(() => {
            console.log('Dashboard auto-refresh completed');
            // Update last refresh time if the element exists
            const lastRefreshElem = document.querySelector('#last-refresh-time');
            if (lastRefreshElem) {
                const now = new Date();
                lastRefreshElem.textContent = `Last updated: ${now.toLocaleTimeString()}`;
            }
        })
        .catch(error => {
            console.error('Auto-refresh failed:', error);
            // Don't show visible errors for auto-refresh failures
        });
    }, refreshInterval);
    
    // Add a timestamp display for last refresh if it doesn't exist
    const dashboardHeader = document.querySelector('.admin-content-header');
    if (dashboardHeader && !document.querySelector('#last-refresh-time')) {
        const refreshInfo = document.createElement('div');
        refreshInfo.id = 'refresh-info';
        refreshInfo.innerHTML = `<span id="last-refresh-time">Last updated: ${new Date().toLocaleTimeString()}</span>`;
        dashboardHeader.appendChild(refreshInfo);
    }
}document.addEventListener('DOMContentLoaded', function() {
    // Toggle sidebar
    const toggleBtn = document.querySelector('.toggle-sidebar');
    const adminWrap = document.querySelector('.admin-wrap');
    
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            adminWrap.classList.toggle('sidebar-collapsed');
        });
    }
    
    // Initialize the dashboard
    initializeDashboard();
    
    // Set up auto-refresh for dashboard data (every 30 seconds)
    setupAutoRefresh();
    
    // Add event listeners for action buttons
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('action-btn')) {
            const action = e.target.innerText.toLowerCase().replace(' ', '-');
            const row = e.target.closest('tr');
            const bookingId = row.querySelector('td:first-child').innerText.replace('#', '');
            
            handleBookingAction(bookingId, action);
        }
    });

    // Add manual refresh button listener if it exists
    const refreshBtn = document.querySelector('#refresh-data');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function(e) {
            e.preventDefault();
            initializeDashboard(true);
        });
    }
});

// Initialize dashboard with all required data
function initializeDashboard(showLoadingIndicators = false) {
    if (showLoadingIndicators) {
        showLoading('.admin-cards', 'Loading dashboard data...');
        showLoading('.admin-table tbody', 'Loading bookings...');
    }
    
    // Load all dashboard components with Promise.all for parallel fetching
    Promise.all([
        fetchDashboardData(),
        fetchRecentBookings()
    ])
    .then(results => {
        console.log('All dashboard data loaded successfully');
        hideLoading('.admin-cards');
        hideLoading('.admin-table tbody');
    })
    .catch(error => {
        console.error('Error initializing dashboard:', error);
        hideLoading('.admin-cards');
        hideLoading('.admin-table tbody');
    });
}

// Show loading indicator in specified container
function showLoading(selector, message = 'Loading...') {
    const container = document.querySelector(selector);
    if (container) {
        // Store original content
        container.dataset.originalContent = container.innerHTML;
        
        // For cards container
        if (selector === '.admin-cards') {
            container.innerHTML = `
                <div class="admin-card loading">
                    <div class="card-content">
                        <div class="loading-spinner"></div>
                        <p>${message}</p>
                    </div>
                </div>`;
        } 
        // For table body
        else if (selector === '.admin-table tbody') {
            container.innerHTML = `<tr><td colspan="7"><div class="loading-spinner"></div> ${message}</td></tr>`;
        }
    }
}

// Hide loading indicator and restore or replace content
function hideLoading(selector) {
    const container = document.querySelector(selector);
    if (container && container.dataset.originalContent) {
        container.innerHTML = container.dataset.originalContent;
        delete container.dataset.originalContent;
    }
}

// Fetch dashboard summary data with better error handling
function fetchDashboardData(showErrors = true) {
    return new Promise((resolve, reject) => {
        fetch('api/dashboard_summary.php', {
            method: 'GET',
            headers: {
                'Cache-Control': 'no-cache',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Server returned ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Dashboard data received:', data);
            if (data.status === 'success') {
                updateDashboardCards(data);
                resolve(data);
            } else {
                const error = new Error(data.message || 'Unknown error loading dashboard data');
                console.error(error);
                if (showErrors) {
                    displayErrorCard('.admin-cards', 'Error Loading Dashboard Data', data.message || 'Please check your connection and refresh.');
                }
                reject(error);
            }
        })
        .catch(error => {
            console.error('Error fetching dashboard data:', error);
            if (showErrors) {
                displayErrorCard('.admin-cards', 'Error Loading Dashboard Data', 'Please check your connection and refresh.');
            }
            reject(error);
        });
    });
}

// Display error message in card format
function displayErrorCard(selector, title, message) {
    const container = document.querySelector(selector);
    if (container) {
        // For cards container
        if (selector === '.admin-cards') {
            container.innerHTML = `
                <div class="admin-card error">
                    <div class="card-content">
                        <h3>${title}</h3>
                        <p>${message}</p>
                        <button class="retry-btn" onclick="initializeDashboard(true)">Retry</button>
                    </div>
                </div>`;
        } 
        // For table
        else if (selector.includes('.admin-table')) {
            const tableBody = document.querySelector('.admin-table tbody');
            if (tableBody) {
                tableBody.innerHTML = `<tr><td colspan="7">
                    <div class="error-message">
                        <p>${message}</p>
                        <button class="retry-btn" onclick="loadRecentBookings()">Retry</button>
                    </div>
                </td></tr>`;
            }
        }
    }
}

// Update dashboard cards with data
function updateDashboardCards(data) {
    console.log('Updating dashboard cards with data:', data);
    
    // Update available rooms
    const availableRoomsCard = document.querySelector('.admin-card:nth-child(1) p');
    if (availableRoomsCard) {
        availableRoomsCard.textContent = `${data.available_rooms} / ${data.total_rooms}`;
    } else {
        console.error('Could not find available rooms card element');
    }
    
    // Update today's check-ins
    const checkInsCard = document.querySelector('.admin-card:nth-child(2) p');
    if (checkInsCard) {
        checkInsCard.textContent = data.todays_checkins;
    } else {
        console.error('Could not find check-ins card element');
    }

    // If there are additional dashboard metrics, update them here
    if (data.revenue && document.querySelector('.admin-card:nth-child(3) p')) {
        document.querySelector('.admin-card:nth-child(3) p').textContent = data.revenue;
    }
    
    if (data.occupancy_rate && document.querySelector('.admin-card:nth-child(4) p')) {
        document.querySelector('.admin-card:nth-child(4) p').textContent = data.occupancy_rate + '%';
    }
}

// Load recent bookings for dashboard with better error handling
function fetchRecentBookings(showErrors = true) {
    return new Promise((resolve, reject) => {
        fetch('api/get_bookings.php?limit=5&page=1&sort=id&order=desc', {
            method: 'GET',
            headers: {
                'Cache-Control': 'no-cache',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Server returned ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                updateRecentBookingsTable(data.bookings);
                resolve(data);
            } else {
                const error = new Error(data.message || 'Unknown error loading bookings');
                console.error('Error loading bookings:', error);
                if (showErrors) {
                    displayErrorCard('.admin-table', 'Error Loading Bookings', data.message || 'Unable to load recent bookings.');
                }
                reject(error);
            }
        })
        .catch(error => {
            console.error('Error fetching bookings:', error);
            if (showErrors) {
                displayErrorCard('.admin-table', 'Error Loading Bookings', 'Failed to load recent bookings. Please try again.');
            }
            reject(error);
        });
    });
}

// Update bookings table with data
function updateRecentBookingsTable(bookings) {
    const tableBody = document.querySelector('.admin-table tbody');
    if (!tableBody) return;
    
    // Clear existing rows
    tableBody.innerHTML = '';
    
    if (bookings.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="7" class="no-data">No recent bookings found.</td></tr>`;
        return;
    }
    
    // Add booking rows
    bookings.forEach(booking => {
        const row = document.createElement('tr');
        row.dataset.bookingId = booking.id;
        
        // Create appropriate action buttons based on status
        let actionButtons = '';
        switch(booking.status) {
            case 'confirmed':
                actionButtons = `<button class="action-btn">Check In</button>
                                <button class="action-btn delete">Cancel</button>`;
                break;
            case 'checked_in':
                actionButtons = `<button class="action-btn">Check Out</button>`;
                break;
            case 'pending':
                actionButtons = `<button class="action-btn">Confirm</button>
                                <button class="action-btn delete">Cancel</button>`;
                break;
            case 'checked_out':
            case 'cancelled':
                actionButtons = ''; // No actions for completed bookings
                break;
            default:
                actionButtons = '';
        }
        
        // Format status display
        const statusClass = booking.status.replace('_', '-');
        const statusDisplay = booking.status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
        
        row.innerHTML = `
            <td>#${booking.id}</td>
            <td>${booking.guest_name || 'N/A'}</td>
            <td>${booking.room || 'N/A'}</td>
            <td>${booking.check_in || 'N/A'}</td>
            <td>${booking.check_out || 'N/A'}</td>
            <td><span class="status ${statusClass}">${statusDisplay}</span></td>
            <td class="actions-cell">${actionButtons}</td>
        `;
        
        tableBody.appendChild(row);
    });
}

// Handle booking actions (check in, check out, cancel) with improved feedback
function handleBookingAction(bookingId, action) {
    if (!confirm(`Are you sure you want to ${action.replace('-', ' ')} booking #${bookingId}?`)) {
        return;
    }
    
    // Find the row with the matching booking ID
    const row = document.querySelector(`tr[data-booking-id="${bookingId}"]`) || 
                document.querySelector(`.admin-table tbody tr td:first-child:contains('#${bookingId}')`).closest('tr');
    
    if (!row) {
        console.error(`Row for booking #${bookingId} not found`);
        return;
    }
    
    // Display loading status
    const actionCell = row.querySelector('td:last-child');
    const originalContent = actionCell.innerHTML;
    actionCell.innerHTML = '<div class="loading-spinner small"></div> <span>Processing...</span>';
    
    fetch(`api/update_booking_status.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            id: bookingId,
            action: action
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`Server returned ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.status === 'success') {
            // Show success indicator temporarily
            actionCell.innerHTML = '<span class="success-indicator">✓ Success</span>';
            
            // Refresh data after successful action
            setTimeout(() => {
                initializeDashboard();
                // Optional: Show toast notification instead of alert
                showToastNotification(`Booking #${bookingId} has been updated successfully.`, 'success');
            }, 1000);
        } else {
            console.error('Error updating booking:', data.message);
            actionCell.innerHTML = originalContent;
            showToastNotification(`Error: ${data.message}`, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        actionCell.innerHTML = originalContent;
        showToastNotification('An error occurred while processing your request.', 'error');
    });
}

// Show toast notification
function showToastNotification(message, type = 'info') {
    // Create toast container if it doesn't exist
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container';
        document.body.appendChild(toastContainer);
    }
    
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <div class="toast-content">
            <span>${message}</span>
            <button class="toast-close">&times;</button>
        </div>
    `;
    
    // Add close functionality
    toast.querySelector('.toast-close').addEventListener('click', function() {
        toast.classList.add('toast-hiding');
        setTimeout(() => toast.remove(), 300);
    });
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (toast && toast.parentNode) {
            toast.classList.add('toast-hiding');
            setTimeout(() => toast.remove(), 300);
        }
    }, 5000);
    
    // Add to container
    toastContainer.appendChild(toast);
    
    // Trigger animation
    setTimeout(() => toast.classList.add('toast-visible'), 10);
}

// Add real-time search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('#booking-search');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(function(e) {
            const searchTerm = e.target.value.trim();
            if (searchTerm.length >= 2) {
                searchBookings(searchTerm);
            } else if (searchTerm.length === 0) {
                // Reset to default view
                loadRecentBookings();
            }
        }, 500));
    }
});

// Search bookings with AJAX
function searchBookings(searchTerm) {
    const tableBody = document.querySelector('.admin-table tbody');
    if (!tableBody) return;
    
    showLoading('.admin-table tbody', 'Searching...');
    
    fetch(`api/get_bookings.php?search=${encodeURIComponent(searchTerm)}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`Server returned ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.status === 'success') {
            updateRecentBookingsTable(data.bookings);
            
            // Add search results indicator
            const resultsInfo = document.createElement('div');
            resultsInfo.className = 'search-results-info';
            resultsInfo.textContent = `Found ${data.bookings.length} result(s) for "${searchTerm}"`;
            
            const tableContainer = document.querySelector('.admin-table').closest('.admin-content-box');
            const existingInfo = tableContainer.querySelector('.search-results-info');
            
            if (existingInfo) {
                existingInfo.replaceWith(resultsInfo);
            } else {
                tableContainer.insertBefore(resultsInfo, tableContainer.querySelector('.admin-table'));
            }
        } else {
            console.error('Error searching bookings:', data.message);
            displayErrorCard('.admin-table', 'Search Error', data.message || 'Unable to complete search.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        displayErrorCard('.admin-table', 'Search Error', 'Failed to search bookings. Please try again.');
    });
}

// Utility function for debouncing
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