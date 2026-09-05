<?php
require 'db_connect.php';
header('Content-Type: application/json');

$doc_type = $_POST['doc_type'] ?? '';

// Simple file upload check (ensure 'uploads' folder exists in your PHP directory)
if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    
    $fileName = basename($_FILES['document']['name']);
    $targetPath = $uploadDir . time() . '_' . $fileName;

    if (move_uploaded_file($_FILES['document']['tmp_name'], $targetPath)) {
        echo json_encode(["status" => "success", "message" => "Document uploaded for verification!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to move uploaded file."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "No file uploaded or upload error."]);
}
?>