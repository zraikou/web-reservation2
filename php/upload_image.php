
<?php
require_once 'config.php';
require_once 'auth.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Authentication check
$auth = new Auth();
if (!$auth->isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if file was uploaded
if (isset($_FILES['room_image']) && $_FILES['room_image']['error'] === UPLOAD_ERR_OK) {
    // Get room type ID from POST
    $roomTypeId = isset($_POST['room_type_id']) ? (int)$_POST['room_type_id'] : 0;
    $isPrimary = isset($_POST['is_primary']) && $_POST['is_primary'] === '1';
    
    if ($roomTypeId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid room type']);
        exit();
    }
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
    $fileType = finfo_file($fileInfo, $_FILES['room_image']['tmp_name']);
    finfo_close($fileInfo);
    
    if (!in_array($fileType, $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPEG, JPG, and PNG are allowed.']);
        exit();
    }
    
    // Create upload directory if it doesn't exist
    $uploadDir = '../uploads/rooms/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($_FILES['room_image']['name'], PATHINFO_EXTENSION);
    $filename = 'room_' . $roomTypeId . '_' . uniqid() . '.' . $extension;
    $filePath = $uploadDir . $filename;
    $relativeFilePath = 'uploads/rooms/' . $filename;
    
    // Move the uploaded file
    if (move_uploaded_file($_FILES['room_image']['tmp_name'], $filePath)) {
        // Connect to database
        $conn = connectDB();
        
        // If this is the primary image, update any existing primary images
        if ($isPrimary) {
            $stmt = $conn->prepare("UPDATE room_images SET is_primary = 0 WHERE room_type_id = ?");
            $stmt->bind_param("i", $roomTypeId);
            $stmt->execute();
        }
        
        // Insert image record into database
        $stmt = $conn->prepare("INSERT INTO room_images (room_type_id, image_path, is_primary) VALUES (?, ?, ?)");
        $stmt->bind_param("isi", $roomTypeId, $relativeFilePath, $isPrimary);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true, 
                'message' => 'Image uploaded successfully',
                'image_id' => $conn->insert_id,
                'image_path' => $relativeFilePath
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save image record: ' . $conn->error]);
        }
        
        $conn->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No image file uploaded']);
}
?>
