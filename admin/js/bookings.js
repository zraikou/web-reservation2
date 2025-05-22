// Script to handle the bookings table functionality
document.addEventListener('DOMContentLoaded', function() {
    // Global variables
    let currentPage = 1;
    let totalPages = 1;
    const entriesPerPage = 10;
    let currentFilters = {};
    
    // Initialize the page
    initializeBookingsPage();
    
    // Event listeners for filtering
    document.getElementById('apply-filters').addEventListener('click', applyFilters);
    
    // Setup modal events
    setupModalEvents();
    
    /**
     * Initialize the bookings page
     */
    function initializeBookingsPage() {
        loadBookings();
        
        // Mobile sidebar toggle
        const toggleSidebar = document.querySelector('.toggle-sidebar');
        if (toggleSidebar) {
            toggleSidebar.addEventListener('click', function() {
                document.querySelector('.admin-wrap').classList.toggle('sidebar-collapsed');
            });
        }
    }
    
    /**
     * Load bookings with current filters and pagination
     */
    function loadBookings() {
        // Show loading state
        const tableBody = document.querySelector('#bookings-table tbody');
        tableBody.innerHTML = '<tr><td colspan="9" class="text-center">Loading...</td></tr>';
        
        // Build query parameters
        let params = new URLSearchParams();
        params.append('page', currentPage);
        params.append('limit', entriesPerPage);
        
        // Add filters if present
        if (currentFilters.dateFrom) params.append('date_from', currentFilters.dateFrom);
        if (currentFilters.dateTo) params.append('date_to', currentFilters.dateTo);
        if (currentFilters.status) params.append('status', currentFilters.status);
        if (currentFilters.roomType) params.append('room_type', currentFilters.roomType);
        if (currentFilters.search) params.append('search', currentFilters.search);
        
        // Fetch data from server
        fetch(`api/get_bookings.php?${params.toString()}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    // Log each booking's ID and status to the console
                    data.bookings.forEach(b => console.log("Booking", b.id, "has status →", JSON.stringify(b.status)));
                    displayBookings(data.bookings);
                    updatePagination(data.page, data.total_pages, data.total);
                } else {
                    showError('Failed to load bookings');
                }
            })
            .catch(error => {
                console.error('Error fetching bookings:', error);
                showError('Error loading bookings. Please try again.');
            });
    }
    
    /**
     * Display bookings in the table
     */
    function displayBookings(bookings) {
        const tableBody = document.querySelector('#bookings-table tbody');
        tableBody.innerHTML = '';
        
        if (bookings.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="9" class="text-center">No bookings found</td></tr>';
            return;
        }
        
        bookings.forEach(booking => {
            const row = document.createElement('tr');
            
            // Format the status with appropriate class
            let statusClass = '';
            switch(booking.status) {
                case 'pending':
                case 'confirmed':
                    statusClass = 'status status-reserved';
                    break;
                case 'checked_in':
                    statusClass = 'status status-checked-in';
                    break;
                case 'checked_out':
                    statusClass = 'status status-checked-out';
                    break;
                case 'cancelled':
                    statusClass = 'status status-cancelled';
                    break;
            }
            
            // Format dates
            const checkInDate = new Date(booking.check_in);
            const checkOutDate = new Date(booking.check_out);
            
            const formattedCheckIn = checkInDate.toLocaleDateString();
            const formattedCheckOut = checkOutDate.toLocaleDateString();
            
            row.innerHTML = `
                <td>${booking.id}</td>
                <td>${booking.guest_name}</td>
                <td>${booking.contact}</td>
                <td>${booking.room}</td>
                <td>${formattedCheckIn}</td>
                <td>${formattedCheckOut}</td>
                <td>${booking.guests}</td>
                <td><span class="${statusClass}">${formatStatus(booking.status)}</span></td>
                <td>${generateActionButtons(booking)}</td>
            `;
            
            tableBody.appendChild(row);
        });
        
        // Add event listeners to action buttons
        addActionButtonListeners();
    }
    
    /**
     * Format the status for display
     */
    function formatStatus(status) {
        // Convert snake_case to Title Case
        return status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    }
    
    /**
     * Generate action buttons based on booking status
     */
    function generateActionButtons(booking) {
        let buttons = '';
        
        // Different actions based on status
        switch(booking.status) {
            case 'pending':
                buttons += `<button class="action-btn confirm-btn" data-id="${booking.id}">Confirm</button>`;
                buttons += `<button class="action-btn cancel-btn delete" data-id="${booking.id}">Cancel</button>`;
                break;
            case 'confirmed':
                buttons += `<button class="action-btn check-in-btn" data-id="${booking.id}">Check In</button>`;
                buttons += `<button class="action-btn cancel-btn delete" data-id="${booking.id}">Cancel</button>`;
                break;
            case 'checked_in':
                buttons += `<button class="action-btn check-out-btn" data-id="${booking.id}">Check Out</button>`;
                break;
            case 'checked_out':
                // No actions for checked out
                break;
            case 'cancelled':
                // No actions for cancelled
                break;
        }
        
        // Delete button for all statuses
        buttons += `<button class="action-btn delete-btn delete" data-id="${booking.id}">Delete</button>`;
        
        return buttons;
    }
    
    /**
     * Add event listeners to action buttons
     */
    function addActionButtonListeners() {
        // Confirm button (for pending status)
        document.querySelectorAll('.confirm-btn').forEach(button => {
            button.addEventListener('click', function() {
                const reservationId = this.getAttribute('data-id');
                showConfirmationModal(
                    'Confirm Reservation',
                    'Are you sure you want to confirm this reservation?',
                    () => updateReservationStatus(reservationId, 'confirmed')
                );
            });
        });
        
        // Check In button
        document.querySelectorAll('.check-in-btn').forEach(button => {
            button.addEventListener('click', function() {
                const reservationId = this.getAttribute('data-id');
                showConfirmationModal(
                    'Confirm Check In',
                    'Are you sure you want to check in this guest?',
                    () => updateReservationStatus(reservationId, 'checked_in')
                );
            });
        });
        
        // Check Out button
        document.querySelectorAll('.check-out-btn').forEach(button => {
            button.addEventListener('click', function() {
                const reservationId = this.getAttribute('data-id');
                showConfirmationModal(
                    'Confirm Check Out',
                    'Are you sure you want to check out this guest?',
                    () => updateReservationStatus(reservationId, 'checked_out')
                );
            });
        });
        
        // Cancel button
        document.querySelectorAll('.cancel-btn').forEach(button => {
            button.addEventListener('click', function() {
                const reservationId = this.getAttribute('data-id');
                showConfirmationModal(
                    'Confirm Cancellation',
                    'Are you sure you want to cancel this reservation?',
                    () => updateReservationStatus(reservationId, 'cancelled')
                );
            });
        });
        
        // Delete button
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const reservationId = this.getAttribute('data-id');
                showConfirmationModal(
                    'Confirm Delete',
                    'Are you sure you want to delete this reservation? This action cannot be undone.',
                    () => deleteReservation(reservationId)
                );
            });
        });
    }
    
    /**
     * Update reservation status
     */
    function updateReservationStatus(reservationId, status) {
        fetch('api/update_reservation_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                reservation_id: reservationId,
                status: status
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showNotification('Status updated successfully', 'success');
                loadBookings(); // Reload bookings
            } else {
                showNotification('Failed to update status: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error updating status:', error);
            showNotification('Error updating status', 'error');
        });
    }
    
    /**
     * Delete reservation
     */
    function deleteReservation(reservationId) {
        fetch('api/delete_reservation.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                reservation_id: reservationId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showNotification('Reservation deleted successfully', 'success');
                loadBookings(); // Reload bookings
            } else {
                showNotification('Failed to delete reservation: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error deleting reservation:', error);
            showNotification('Error deleting reservation', 'error');
        });
    }
    
    /**
     * Setup modal events
     */
    function setupModalEvents() {
        const modal = document.getElementById('confirmation-modal');
        const cancelBtn = document.getElementById('modal-cancel');
        
        // Close modal on cancel
        cancelBtn.addEventListener('click', function() {
            modal.style.display = 'none';
        });
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    }
    
    /**
     * Show confirmation modal
     */
    function showConfirmationModal(title, message, confirmCallback) {
        const modal = document.getElementById('confirmation-modal');
        const modalTitle = document.getElementById('modal-title');
        const modalMessage = document.getElementById('modal-message');
        const confirmBtn = document.getElementById('modal-confirm');
        
        modalTitle.textContent = title;
        modalMessage.textContent = message;
        
        // Remove previous event listener to avoid duplicates
        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
        
        // Add new event listener
        newConfirmBtn.addEventListener('click', function() {
            confirmCallback();
            modal.style.display = 'none';
        });
        
        // Show modal
        modal.style.display = 'block';
    }
    
    /**
     * Apply filters from form fields
     */
    function applyFilters() {
        const dateFrom = document.getElementById('date-from').value;
        const dateTo = document.getElementById('date-to').value;
        const status = document.getElementById('status-filter').value;
        const roomType = document.getElementById('room-type-filter').value;
        const search = document.getElementById('search').value;
        
        // Store current filters
        currentFilters = {
            dateFrom: dateFrom,
            dateTo: dateTo,
            status: status,
            roomType: roomType,
            search: search
        };
        
        // Reset to first page and load bookings
        currentPage = 1;
        loadBookings();
    }
    
    /**
     * Update pagination controls
     */
    function updatePagination(page, totalPages, totalEntries) {
        const paginationContainer = document.getElementById('pagination');
        const entriesInfo = document.getElementById('entries-info');
        
        // Update current page and total pages
        currentPage = page;
        totalPages = totalPages;
        
        // Update entries info
        const start = (page - 1) * entriesPerPage + 1;
        const end = Math.min(page * entriesPerPage, totalEntries);
        entriesInfo.textContent = `Showing ${start} to ${end} of ${totalEntries} entries`;
        
        // Clear pagination
        paginationContainer.innerHTML = '';
        
        // Previous button
        const prevButton = document.createElement('button');
        prevButton.textContent = 'Previous';
        prevButton.classList.add('btn', 'btn-sm');
        prevButton.disabled = page <= 1;
        prevButton.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                loadBookings();
            }
        });
        paginationContainer.appendChild(prevButton);
        
        // Page buttons
        const maxButtons = 5;
        const startPage = Math.max(1, page - Math.floor(maxButtons / 2));
        const endPage = Math.min(totalPages, startPage + maxButtons - 1);
        
        for (let i = startPage; i <= endPage; i++) {
            const pageButton = document.createElement('button');
            pageButton.textContent = i;
            pageButton.classList.add('btn', 'btn-sm');
            if (i === page) {
                pageButton.classList.add('active');
            }
            pageButton.addEventListener('click', () => {
                currentPage = i;
                loadBookings();
            });
            paginationContainer.appendChild(pageButton);
        }
        
        // Next button
        const nextButton = document.createElement('button');
        nextButton.textContent = 'Next';
        nextButton.classList.add('btn', 'btn-sm');
        nextButton.disabled = page >= totalPages;
        nextButton.addEventListener('click', () => {
            if (currentPage < totalPages) {
                currentPage++;
                loadBookings();
            }
        });
        paginationContainer.appendChild(nextButton);
    }
    
    /**
     * Show notification
     */
    function showNotification(message, type = 'info') {
        // Create notification element if it doesn't exist
        let notification = document.getElementById('notification');
        if (!notification) {
            notification = document.createElement('div');
            notification.id = 'notification';
            notification.style.position = 'fixed';
            notification.style.top = '20px';
            notification.style.right = '20px';
            notification.style.padding = '10px 20px';
            notification.style.borderRadius = '5px';
            notification.style.zIndex = '1001';
            notification.style.maxWidth = '300px';
            notification.style.transition = 'opacity 0.3s ease-in-out';
            document.body.appendChild(notification);
        }
        
        // Set type-specific styles
        if (type === 'success') {
            notification.style.backgroundColor = '#4CAF50';
            notification.style.color = 'white';
        } else if (type === 'error') {
            notification.style.backgroundColor = '#F44336';
            notification.style.color = 'white';
        } else {
            notification.style.backgroundColor = '#2196F3';
            notification.style.color = 'white';
        }
        
        // Set message and show
        notification.textContent = message;
        notification.style.opacity = '1';
        
        // Hide after 3 seconds
        setTimeout(() => {
            notification.style.opacity = '0';
        }, 3000);
    }
    
    /**
     * Show error in the table
     */
    function showError(message) {
        const tableBody = document.querySelector('#bookings-table tbody');
        tableBody.innerHTML = `<tr><td colspan="9" class="text-center text-danger">${message}</td></tr>`;
    }
});