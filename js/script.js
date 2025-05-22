document.addEventListener('DOMContentLoaded', function() {
    // Header scroll effect
    const header = document.querySelector('header');
    window.addEventListener('scroll', function() {
        if (window.scrollY > 100) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });

    // Mobile menu toggle
    const menuBtn = document.querySelector('.mobile-menu-btn');
    const mobileMenu = document.querySelector('nav ul');

    if (menuBtn) {
        menuBtn.addEventListener('click', function() {
            mobileMenu.classList.toggle('active');
        });
    }

    // Admin sidebar toggle
    const sidebarToggle = document.querySelector('.toggle-sidebar');
    const adminSidebar = document.querySelector('.admin-sidebar');

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            adminSidebar.classList.toggle('active');
        });
    }

    // Initialize datepickers for check-in/check-out fields
    const dateInputs = document.querySelectorAll('input[type="date"]');
    if (dateInputs.length) {
        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        dateInputs.forEach(input => {
            input.setAttribute('min', today);
        });
    }

    // Availability check validation
    const availForm = document.getElementById('availability-form');
    if (availForm) {
        availForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const checkIn = document.getElementById('check-in').value;
            const checkOut = document.getElementById('check-out').value;
            const guests = document.getElementById('guests').value;

            if (!checkIn || !checkOut || !guests) {
                alert('Please fill all fields');
                return;
            }

            const checkInDate = new Date(checkIn);
            const checkOutDate = new Date(checkOut);

            if (checkOutDate <= checkInDate) {
                alert('Check-out date must be after check-in date');
                return;
            }

            const roomsSection = document.getElementById('rooms');
            if (roomsSection) {
                roomsSection.scrollIntoView({behavior: 'smooth'});
            }

            filterRoomsAvailability(checkInDate, checkOutDate, guests);
        });
    }

    // Booking form validation
    const bookingForm = document.getElementById('booking-form');
    if (bookingForm) {
        bookingForm.addEventListener('submit', function(e) {
            const roomType = document.getElementById('room-type-booking').value;
            const checkIn  = document.getElementById('check-in').value;
            const checkOut = document.getElementById('check-out').value;

            if (!roomType || !checkIn || !checkOut) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    }

    // Admin login form validation
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;

            if (!username || !password) {
                alert('Please enter both username and password');
                return;
            }

            if (username === 'admin' && password === 'admin123') {
                window.location.href = 'dashboard.html';
            } else {
                alert('Invalid username or password');
            }
        });
    }

    // Function to filter rooms by availability
    function filterRoomsAvailability(checkIn, checkOut, guests) {
        console.log('Filtering rooms for:', {
            checkIn: checkIn.toISOString(),
            checkOut: checkOut.toISOString(),
            guests: guests
        });

        const roomCards = document.querySelectorAll('.room-card');
        roomCards.forEach(card => {
            card.style.display = 'block';
        });
    }

    // Admin dashboard charts (using Chart.js)
    const bookingsChart = document.getElementById('bookings-chart');
    if (bookingsChart) {
        const ctx = bookingsChart.getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['January', 'February', 'March', 'April', 'May', 'June'],
                datasets: [{
                    label: 'Bookings',
                    data: [12, 19, 15, 25, 22, 30],
                    backgroundColor: '#0A3622',
                    borderColor: '#0A3622',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    const occupancyChart = document.getElementById('occupancy-chart');
    if (occupancyChart) {
        const ctx = occupancyChart.getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Occupied', 'Available', 'Under Maintenance'],
                datasets: [{
                    label: 'Room Status',
                    data: [12, 8, 2],
                    backgroundColor: [
                        '#0A3622',
                        '#D4AF37',
                        '#999999'
                    ],
                    borderWidth: 1
                }]
            }
        });
    }

    const dateRangePicker = document.getElementById('date-range');
    if (dateRangePicker) {
        console.log('Date range picker initialized');
    }
});
