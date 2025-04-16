<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Check if file parameter is provided
if (!isset($_GET['file']) || empty($_GET['file'])) {
    header("HTTP/1.0 404 Not Found");
    echo "File not found";
    exit;
}

$file_path = sanitize($_GET['file']);

// Security check: Make sure the file is in the uploads directory
if (strpos($file_path, 'uploads/') !== 0) {
    header("HTTP/1.0 403 Forbidden");
    echo "Access denied";
    exit;
}

// Check if the file exists
if (!file_exists($file_path)) {
    header("HTTP/1.0 404 Not Found");
    echo "File not found";
    exit;
}

// For employers, check if they have access to this resume
if ($user_type === 'employer') {
    // Check if the resume belongs to a candidate who applied to one of their jobs
    $stmt = $conn->prepare("
        SELECT cp.resume 
        FROM candidate_profiles cp
        JOIN job_applications ja ON cp.user_id = ja.candidate_id
        JOIN jobs j ON ja.job_id = j.id
        WHERE j.employer_id = :employer_id AND cp.resume = :resume
    ");
    $stmt->bindParam(':employer_id', $user_id);
    $stmt->bindParam(':resume', $file_path);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        // Also check if the employer is viewing their own company document
        $stmt = $conn->prepare("
            SELECT document 
            FROM company_profiles
            WHERE user_id = :employer_id AND document = :document
        ");
        $stmt->bindParam(':employer_id', $user_id);
        $stmt->bindParam(':document', $file_path);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            header("HTTP/1.0 403 Forbidden");
            echo "Access denied";
            exit;
        }
    }
} else {
    // For candidates, check if they are accessing their own resume
    $stmt = $conn->prepare("
        SELECT resume 
        FROM candidate_profiles
        WHERE user_id = :user_id AND resume = :resume
    ");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':resume', $file_path);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        header("HTTP/1.0 403 Forbidden");
        echo "Access denied";
        exit;
    }
}

// Get file info
$file_info = pathinfo($file_path);
$file_name = $file_info['basename'];
$file_ext = strtolower($file_info['extension']);

// Set appropriate content type
switch ($file_ext) {
    case 'pdf':
        $content_type = 'application/pdf';
        break;
    case 'doc':
        $content_type = 'application/msword';
        break;
    case 'docx':
        $content_type = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        break;
    default:
        $content_type = 'application/octet-stream';
}

// Output the file
header('Content-Type: ' . $content_type);
header('Content-Disposition: inline; filename="' . $file_name . '"');
header('Content-Length: ' . filesize($file_path));
readfile($file_path);
exit;

// Function to sanitize input if not defined in db.php
if (!function_exists('sanitize')) {
    function sanitize($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
}
?>
