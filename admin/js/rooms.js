// Global variables
let roomTypes = [];
let rooms = [];
let currentRoomTypeId = null;
let currentRoomId = null;


// Document ready function
document.addEventListener('DOMContentLoaded', function() {
   // Initialize
   loadRoomTypes();
   loadRooms();
  
   // Event listeners for modals
   window.onclick = function(event) {
       if (event.target.classList.contains('modal')) {
           event.target.style.display = 'none';
       }
   };
  
   // Add event listener for room type selection
   const roomTypeSelect = document.getElementById('room-type-id');
   if (roomTypeSelect) {
       roomTypeSelect.addEventListener('change', handleRoomTypeSelection);
   }
});


// ===== Room Types Functions =====


// Fetch room types from the server
function loadRoomTypes() {
   fetch('api/room_types.php')
       .then(response => {
           if (!response.ok) {
               throw new Error('Network response was not ok');
           }
           return response.json();
       })
       .then(data => {
           roomTypes = data;
           populateRoomTypesTable();
           populateRoomTypeSelect();
       })
       .catch(error => {
           showNotification('Error loading room types: ' + error.message, 'error');
           console.error('Error loading room types:', error);
           document.querySelector('#room-types-table tbody').innerHTML =
               '<tr><td colspan="6">Error loading data. Please try again later.</td></tr>';
       });
}


// Populate room types table
function populateRoomTypesTable() {
   const tbody = document.querySelector('#room-types-table tbody');
  
   if (roomTypes.length === 0) {
       tbody.innerHTML = '<tr><td colspan="6">No room types found</td></tr>';
       return;
   }
  
   tbody.innerHTML = '';
   roomTypes.forEach(type => {
       tbody.innerHTML += `
           <tr>
               <td>${type.id}</td>
               <td>${type.name}</td>
               <td>${type.description || '-'}</td>
               <td>$${parseFloat(type.price_per_night).toFixed(2)}</td>
               <td>${type.capacity}</td>
               <td>
                   <button class="btn-icon" onclick="editRoomType(${type.id})">
                       <i class="fas fa-edit"></i>
                   </button>
                   <button class="btn-icon" onclick="confirmDeleteRoomType(${type.id})">
                       <i class="fas fa-trash-alt"></i>
                   </button>
               </td>
           </tr>
       `;
   });
}


// Populate room type select dropdown
function populateRoomTypeSelect() {
   const select = document.getElementById('room-type-id');
   if (!select) return;
  
   select.innerHTML = '<option value="">Select Room Type</option>';
  
   roomTypes.forEach(type => {
       select.innerHTML += `<option value="${type.id}">${type.name}</option>`;
   });
}


// Open room type modal for adding
function openRoomTypeModal() {
   document.getElementById('room-type-modal-title').textContent = 'Add Room Type';
   document.getElementById('room-type-form').reset();
   currentRoomTypeId = null;
   document.getElementById('room-type-modal').style.display = 'block';
}


// Open room type modal for editing
function editRoomType(id) {
   const roomType = roomTypes.find(type => type.id == id);
   if (!roomType) return;
  
   document.getElementById('room-type-modal-title').textContent = 'Edit Room Type';
   document.getElementById('room-type-name').value = roomType.name;
   document.getElementById('room-type-description').value = roomType.description || '';
   document.getElementById('room-type-price').value = roomType.price_per_night;
   document.getElementById('room-type-capacity').value = roomType.capacity;
  
   currentRoomTypeId = id;
   document.getElementById('room-type-modal').style.display = 'block';
}


// Save room type (create or update)
function saveRoomType(event) {
   event.preventDefault();
  
   const form = document.getElementById('room-type-form');
   const formData = new FormData(form);
  
   if (currentRoomTypeId) {
       // This is an edit operation - use the new dedicated update file
       fetch(`api/update_room_type.php?id=${currentRoomTypeId}`, {
           method: 'POST',
           body: formData
       })
       .then(response => {
           if (!response.ok) {
               throw new Error('Network response was not ok');
           }
           return response.json();
       })
       .then(data => {
           if (data.success) {
               showNotification(data.message || 'Room type updated successfully', 'success');
               closeModal('room-type-modal');
               loadRoomTypes();  // Refresh the data
           } else {
               showNotification(data.message || 'Error updating room type', 'error');
           }
       })
       .catch(error => {
           showNotification('Error: ' + error.message, 'error');
           console.error('Error updating room type:', error);
       });
   } else {
       // This is a create operation - use the original endpoint
       fetch('api/room_types.php', {
           method: 'POST',
           body: formData
       })
       .then(response => {
           if (!response.ok) {
               throw new Error('Network response was not ok');
           }
           return response.json();
       })
       .then(data => {
           if (data.success) {
               showNotification(data.message || 'Room type created successfully', 'success');
               closeModal('room-type-modal');
               loadRoomTypes();  // Refresh the data
           } else {
               showNotification(data.message || 'Error creating room type', 'error');
           }
       })
       .catch(error => {
           showNotification('Error: ' + error.message, 'error');
           console.error('Error creating room type:', error);
       });
   }
}


// Confirm delete room type
function confirmDeleteRoomType(id) {
   if (confirm('Are you sure you want to delete this room type? This may affect rooms of this type.')) {
       deleteRoomType(id);
   }
}


// Delete room type using the new dedicated delete file
function deleteRoomType(id) {
   fetch(`api/delete_room_type.php?id=${id}`, {
       method: 'POST'
   })
   .then(response => {
       if (!response.ok) {
           throw new Error('Network response was not ok');
       }
       return response.json();
   })
   .then(data => {
       if (data.success) {
           showNotification(data.message || 'Room type deleted successfully', 'success');
           loadRoomTypes();  // Refresh the data
           loadRooms();      // Also refresh rooms as their display may depend on room types
       } else {
           showNotification(data.message || 'Error deleting room type', 'error');
       }
   })
   .catch(error => {
       showNotification('Error: ' + error.message, 'error');
       console.error('Error deleting room type:', error);
   });
}


// ===== Rooms Functions =====


// Fetch rooms from the server
function loadRooms() {
   fetch('api/rooms.php')
       .then(response => {
           if (!response.ok) {
               throw new Error('Network response was not ok');
           }
           return response.json();
       })
       .then(data => {
           rooms = data;
           populateRoomsTable();
       })
       .catch(error => {
           showNotification('Error loading rooms: ' + error.message, 'error');
           console.error('Error loading rooms:', error);
           document.querySelector('#rooms-table tbody').innerHTML =
               '<tr><td colspan="9">Error loading data. Please try again later.</td></tr>';
       });
}


// Populate rooms table
function populateRoomsTable() {
   const tbody = document.querySelector('#rooms-table tbody');
  
   if (rooms.length === 0) {
       tbody.innerHTML = '<tr><td colspan="9">No rooms found</td></tr>';
       return;
   }
  
   tbody.innerHTML = '';
   rooms.forEach(room => {
       const roomType = roomTypes.find(type => type.id == room.room_type_id) || { name: 'Unknown' };
      
       // Create status badge
       let statusBadge = `<span class="status">${room.status}</span>`;
       if (room.status === 'occupied') {
           statusBadge = `<span class="status checked-in">occupied</span>`;
       } else if (room.status === 'maintenance') {
           statusBadge = `<span class="status cancelled">maintenance</span>`;
       } else if (room.status === 'available') {
           statusBadge = `<span class="status reserved">available</span>`;
       }
      
       tbody.innerHTML += `
           <tr>
               <td>${room.room_id}</td>
               <td>${room.room_number}</td>
               <td>${roomType.name}</td>
               <td>${room.floor}</td>
               <td>${statusBadge}</td>
               <td>${room.notes || '-'}</td>
               <td>$${parseFloat(room.price_per_night).toFixed(2)}</td>
               <td>${room.capacity}</td>
               <td>
                   <button class="btn-icon" onclick="editRoom(${room.room_id})">
                       <i class="fas fa-edit"></i>
                   </button>
                   <button class="btn-icon" onclick="confirmDeleteRoom(${room.room_id})">
                       <i class="fas fa-trash-alt"></i>
                   </button>
               </td>
           </tr>
       `;
   });
}


// Open room modal for adding
function openRoomModal() {
   document.getElementById('room-modal-title').textContent = 'Add Room';
   document.getElementById('room-form').reset();
   document.getElementById('room-status').value = 'available';
   currentRoomId = null;
   document.getElementById('room-modal').style.display = 'block';
}


// Open room modal for editing
function editRoom(id) {
   const room = rooms.find(r => r.room_id == id);
   if (!room) return;
  
   document.getElementById('room-modal-title').textContent = 'Edit Room';
   document.getElementById('room-number').value = room.room_number;
   document.getElementById('room-type-id').value = room.room_type_id;
   document.getElementById('room-floor').value = room.floor;
   document.getElementById('room-status').value = room.status;
   document.getElementById('room-notes').value = room.notes || '';
   document.getElementById('room-price').value = room.price_per_night;
   document.getElementById('room-capacity').value = room.capacity;
  
   currentRoomId = id;
   document.getElementById('room-modal').style.display = 'block';
}


// Handle room type selection to auto-fill price and capacity
function handleRoomTypeSelection() {
   const typeId = document.getElementById('room-type-id').value;
   if (!typeId) return;
  
   const roomType = roomTypes.find(type => type.id == typeId);
   if (roomType) {
       document.getElementById('room-price').value = roomType.price_per_night;
       document.getElementById('room-capacity').value = roomType.capacity;
   }
}


// Save room (create or update)
function saveRoom(event) {
   event.preventDefault();
  
   const formData = new FormData(document.getElementById('room-form'));
   const roomNumber = document.getElementById('room-number').value;
   
   // Check if room number already exists (for new rooms only)
   if (!currentRoomId && isDuplicateRoomNumber(roomNumber)) {
       showNotification('Room number already exists. Please use a different room number.', 'error');
       return;
   }
  
   if (currentRoomId) {
       // Use the dedicated update_room.php file
       fetch(`api/update_room.php?id=${currentRoomId}`, {
           method: 'POST',
           body: formData
       })
       .then(response => {
           // First handle the response status
           if (response.status === 409) {
               return response.json().then(data => {
                   throw new Error('Room number already exists');
               });
           } else if (!response.ok) {
               return response.json().then(data => {
                   if (data && data.message) {
                       throw new Error(data.message);
                   } else {
                       throw new Error('Failed to update room');
                   }
               }).catch(err => {
                   throw new Error('Network response was not ok');
               });
           }
           return response.json();
       })
       .then(data => {
           if (data.success) {
               showNotification(data.message || 'Room updated successfully', 'success');
               closeModal('room-modal');
               loadRooms();  // Refresh the data
           } else {
               showNotification(data.message || 'Error updating room', 'error');
           }
       })
       .catch(error => {
           // Provide a more specific error message for duplicate room numbers
           if (error.message.includes('already exists')) {
               showNotification('Room number already exists. Please use a different room number.', 'error');
           } else {
               showNotification('Error: ' + error.message, 'error');
           }
           console.error('Error updating room:', error);
       });
   } else {
       // Create operation uses the original endpoint
       fetch('api/rooms.php', {
           method: 'POST',
           body: formData
       })
       .then(response => {
           // First handle the response status
           if (response.status === 409) {
               return response.json().then(data => {
                   throw new Error('Room number already exists');
               });
           } else if (!response.ok) {
               return response.json().then(data => {
                   if (data && data.message) {
                       throw new Error(data.message);
                   } else {
                       throw new Error('Failed to create room');
                   }
               }).catch(err => {
                   throw new Error('Network response was not ok');
               });
           }
           return response.json();
       })
       .then(data => {
           if (data.success) {
               showNotification(data.message || 'Room created successfully', 'success');
               closeModal('room-modal');
               loadRooms();  // Refresh the data
           } else {
               showNotification(data.message || 'Error creating room', 'error');
           }
       })
       .catch(error => {
           // Provide a more specific error message for duplicate room numbers
           if (error.message.includes('already exists')) {
               showNotification('Room number already exists. Please use a different room number.', 'error');
           } else {
               showNotification('Error: ' + error.message, 'error');
           }
           console.error('Error creating room:', error);
       });
   }
}


// Check if room number already exists in the rooms array
function isDuplicateRoomNumber(roomNumber) {
   return rooms.some(room => room.room_number === roomNumber);
}


// Confirm delete room
function confirmDeleteRoom(id) {
   if (confirm('Are you sure you want to delete this room?')) {
       deleteRoom(id);
   }
}


// Delete room using the dedicated delete file
function deleteRoom(id) {
   fetch(`api/delete_room.php?id=${id}`, {
       method: 'POST'
   })
   .then(response => {
       if (!response.ok) {
           throw new Error('Network response was not ok');
       }
       return response.json();
   })
   .then(data => {
       if (data.success) {
           showNotification(data.message || 'Room deleted successfully', 'success');
           loadRooms();  // Refresh the data
       } else {
           showNotification(data.message || 'Error deleting room', 'error');
       }
   })
   .catch(error => {
       showNotification('Error: ' + error.message, 'error');
       console.error('Error deleting room:', error);
   });
}


// ===== Utility Functions =====


// Close modal
function closeModal(modalId) {
   document.getElementById(modalId).style.display = 'none';
}


// Show notification
function showNotification(message, type) {
   const notification = document.createElement('div');
   notification.className = `notification ${type}`;
   notification.textContent = message;
  
   document.body.appendChild(notification);
  
   // Trigger animation
   setTimeout(() => {
       notification.classList.add('show');
   }, 10);
  
   // Remove after 3 seconds
   setTimeout(() => {
       notification.classList.remove('show');
       setTimeout(() => {
           document.body.removeChild(notification);
       }, 300);
   }, 3000);
}


// Toggle sidebar
document.addEventListener('DOMContentLoaded', function() {
   const toggleButton = document.querySelector('.toggle-sidebar');
   if (toggleButton) {
       toggleButton.addEventListener('click', function() {
           document.querySelector('.admin-wrap').classList.toggle('sidebar-collapsed');
       });
   }
});