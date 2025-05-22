/**
 * White Lotus Hotel - Room Display Functions
 * This file handles fetching and displaying room data from the database
 */

// Global variable to store login status
let isLoggedIn = false;

// Function to load room types from the database and update the room cards
function loadRoomTypes() {
    fetch('php/get_room_types.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.rooms && data.rooms.length > 0) {
                displayRoomCards(data.rooms);
            } else {
                console.error('No room data received');
                document.querySelector('.room-grid').innerHTML = '<p class="no-rooms">No rooms available at the moment. Please check back later.</p>';
            }
        })
        .catch(error => {
            console.error('Error fetching room data:', error);
            document.querySelector('.room-grid').innerHTML = '<p class="no-rooms">Failed to load room information. Please refresh the page or try again later.</p>';
        });
}

// Function to display room cards based on data from the database
function displayRoomCards(rooms) {
    const roomGrid = document.querySelector('.room-grid');
    
    // Clear existing room cards
    roomGrid.innerHTML = '';
    
    // Create and append room cards for each room from the database
    rooms.forEach(room => {
        const roomCard = document.createElement('div');
        roomCard.className = 'room-card';
        
        // Format price with commas for thousands
        const formattedPrice = parseFloat(room.price_per_night).toLocaleString('en-US', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        });
        
        // Build room card HTML using only database fields
        roomCard.innerHTML = `
            <div class="room-info">
                <h3 class="room-name">${room.name}</h3>
                <p class="room-description">${room.description}</p>
                <div class="room-features">
                    <span class="feature"><i class="fas fa-user"></i> ${room.capacity} Guests</span>
                </div>
                <div class="room-price">
                    <div class="price">$${formattedPrice}<span>/night</span></div>
                </div>
            </div>
        `;
        
        roomGrid.appendChild(roomCard);
    });
}

// Modified function to handle booking attempts with specific room ID
function checkLoginForBooking(roomId) {
    fetch('php/check_auth.php')
        .then(response => response.json())
        .then(data => {
            if (data.isLoggedIn) {
                // If logged in, redirect to booking page with room ID parameter
                window.location.href = `booking.html?room_id=${roomId}`;
            } else {
                // Show login prompt modal
                const modal = document.createElement('div');
                modal.className = 'login-modal';
                modal.innerHTML = `
                    <div class="modal-content">
                        <span class="close-modal">&times;</span>
                        <i class="fas fa-lock"></i>
                        <h3>Login Required</h3>
                        <p>Please login or create an account to book your stay.</p>
                        <div class="modal-buttons">
                            <a href="login.html" class="btn">Login</a>
                            <a href="register.html" class="btn btn-outline">Register</a>
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);
                
                // Close modal functionality
                const closeBtn = modal.querySelector('.close-modal');
                closeBtn.onclick = function() {
                    modal.remove();
                }
                
                // Close modal when clicking outside
                window.onclick = function(event) {
                    if (event.target === modal) {
                        modal.remove();
                    }
                }
            }
        })
        .catch(error => console.error('Error:', error));
}

// On page load, check login status and then load room cards
window.addEventListener('DOMContentLoaded', function() {
    fetch('php/check_auth.php')
        .then(response => response.json())
        .then(data => {
            isLoggedIn = data.isLoggedIn;
            loadRoomTypes();
        });
});