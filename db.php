<?php
// Database connection parameters
$host = "localhost";
$dbname = "dbg0vlzkbncsla";
$username = "uklz9ew3hrop3";
$password = "zyrbspyjlzjb";

// Create connection with error handling
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // Log error instead of displaying to users
    error_log("Connection failed: " . $e->getMessage());
    // Return a user-friendly message
    die("We're experiencing technical difficulties. Please try again later.");
}

// Function to sanitize input data
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Function to check if user exists
function userExists($conn, $email) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    return $stmt->rowCount() > 0;
}

// Function to register a new user
function registerUser($conn, $name, $email, $password, $user_type) {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, user_type, created_at) VALUES (:name, :email, :password, :user_type, NOW())");
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $hashed_password);
    $stmt->bindParam(':user_type', $user_type);
    return $stmt->execute();
}

// Function to authenticate user
function loginUser($conn, $email, $password) {
    $stmt = $conn->prepare("SELECT id, name, email, password, user_type FROM users WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch();
        if (password_verify($password, $user['password'])) {
            // Remove password from session data
            unset($user['password']);
            return $user;
        }
    }
    return false;
}

// Function to get all job listings with optional filters
function getJobs($conn, $filters = []) {
    $sql = "SELECT j.*, c.name as company_name, c.logo as company_logo 
            FROM jobs j 
            JOIN companies c ON j.company_id = c.id 
            WHERE 1=1";
    
    $params = [];
    
    if (!empty($filters['keyword'])) {
        $sql .= " AND (j.title LIKE :keyword OR j.description LIKE :keyword)";
        $params[':keyword'] = '%' . $filters['keyword'] . '%';
    }
    
    if (!empty($filters['location'])) {
        $sql .= " AND j.location LIKE :location";
        $params[':location'] = '%' . $filters['location'] . '%';
    }
    
    if (!empty($filters['job_type'])) {
        $sql .= " AND j.job_type = :job_type";
        $params[':job_type'] = $filters['job_type'];
    }
    
    if (!empty($filters['category'])) {
        $sql .= " AND j.category = :category";
        $params[':category'] = $filters['category'];
    }
    
    $sql .= " ORDER BY j.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    return $stmt->fetchAll();
}

// Function to get job details by ID
function getJobById($conn, $job_id) {
    $stmt = $conn->prepare("SELECT j.*, c.name as company_name, c.logo as company_logo, c.description as company_description 
                           FROM jobs j 
                           JOIN companies c ON j.company_id = c.id 
                           WHERE j.id = :job_id");
    $stmt->bindParam(':job_id', $job_id);
    $stmt->execute();
    return $stmt->fetch();
}

// Function to apply for a job
function applyForJob($conn, $user_id, $job_id, $resume_path) {
    $stmt = $conn->prepare("INSERT INTO applications (user_id, job_id, resume, status, applied_at) 
                           VALUES (:user_id, :job_id, :resume, 'pending', NOW())");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':job_id', $job_id);
    $stmt->bindParam(':resume', $resume_path);
    return $stmt->execute();
}

// Function to get user applications
function getUserApplications($conn, $user_id) {
    $stmt = $conn->prepare("SELECT a.*, j.title as job_title, c.name as company_name 
                           FROM applications a 
                           JOIN jobs j ON a.job_id = j.id 
                           JOIN companies c ON j.company_id = c.id 
                           WHERE a.user_id = :user_id 
                           ORDER BY a.applied_at DESC");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Function to get company job listings
function getCompanyJobs($conn, $company_id) {
    $stmt = $conn->prepare("SELECT * FROM jobs WHERE company_id = :company_id ORDER BY created_at DESC");
    $stmt->bindParam(':company_id', $company_id);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Function to get job applications for a company
function getJobApplications($conn, $job_id) {
    $stmt = $conn->prepare("SELECT a.*, u.name as applicant_name, u.email as applicant_email 
                           FROM applications a 
                           JOIN users u ON a.user_id = u.id 
                           WHERE a.job_id = :job_id 
                           ORDER BY a.applied_at DESC");
    $stmt->bindParam(':job_id', $job_id);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Function to update application status
function updateApplicationStatus($conn, $application_id, $status) {
    $stmt = $conn->prepare("UPDATE applications SET status = :status WHERE id = :id");
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':id', $application_id);
    return $stmt->execute();
}

// Function to create a new job listing
function createJob($conn, $company_id, $title, $description, $location, $salary, $job_type, $category, $requirements) {
    $stmt = $conn->prepare("INSERT INTO jobs (company_id, title, description, location, salary, job_type, category, requirements, created_at) 
                           VALUES (:company_id, :title, :description, :location, :salary, :job_type, :category, :requirements, NOW())");
    $stmt->bindParam(':company_id', $company_id);
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':location', $location);
    $stmt->bindParam(':salary', $salary);
    $stmt->bindParam(':job_type', $job_type);
    $stmt->bindParam(':category', $category);
    $stmt->bindParam(':requirements', $requirements);
    return $stmt->execute();
}

// Function to update user profile
function updateUserProfile($conn, $user_id, $name, $email, $bio, $skills, $experience, $education) {
    $stmt = $conn->prepare("UPDATE users SET name = :name, email = :email, bio = :bio, skills = :skills, experience = :experience, education = :education WHERE id = :id");
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':bio', $bio);
    $stmt->bindParam(':skills', $skills);
    $stmt->bindParam(':experience', $experience);
    $stmt->bindParam(':education', $education);
    $stmt->bindParam(':id', $user_id);
    return $stmt->execute();
}

// Function to get user profile
function getUserProfile($conn, $user_id) {
    $stmt = $conn->prepare("SELECT id, name, email, bio, skills, experience, education, user_type, created_at FROM users WHERE id = :id");
    $stmt->bindParam(':id', $user_id);
    $stmt->execute();
    return $stmt->fetch();
}

// Function to create company profile
function createCompanyProfile($conn, $user_id, $name, $description, $industry, $size, $founded_year, $website, $logo) {
    $stmt = $conn->prepare("INSERT INTO companies (user_id, name, description, industry, size, founded_year, website, logo, created_at) 
                           VALUES (:user_id, :name, :description, :industry, :size, :founded_year, :website, :logo, NOW())");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':industry', $industry);
    $stmt->bindParam(':size', $size);
    $stmt->bindParam(':founded_year', $founded_year);
    $stmt->bindParam(':website', $website);
    $stmt->bindParam(':logo', $logo);
    return $stmt->execute();
}

// Function to get company profile
function getCompanyProfile($conn, $user_id) {
    $stmt = $conn->prepare("SELECT * FROM companies WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    return $stmt->fetch();
}
?>
