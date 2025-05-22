// Admin logout function
function adminLogout(e) {
    if (e) e.preventDefault();
    
    fetch('../php/auth.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=logout&admin=true'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.redirect) {
                window.location.href = data.redirect_url;
            }
        } else {
            console.error('Logout failed:', data.message);
        }
    })
    .catch(error => console.error('Error:', error));
}

// Check admin authentication status
function checkAdminAuth() {
    fetch('../php/check_admin_auth.php')
        .then(response => response.json())
        .then(data => {
            if (!data.isAdmin) {
                window.location.href = 'login.html';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            window.location.href = 'login.html';
        });
}

// Call checkAdminAuth when page loads
document.addEventListener('DOMContentLoaded', checkAdminAuth); 