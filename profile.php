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

// If user is an employer, redirect to company profile
if ($user_type === 'employer') {
    header("Location: company-profile.php");
    exit;
}

// Function to get user profile if not defined in db.php
if (!function_exists('getUserProfile')) {
    function getUserProfile($conn, $user_id) {
        $stmt = $conn->prepare("SELECT u.id, u.name, u.email, u.user_type, cp.phone, cp.location, cp.title, cp.skills, cp.bio, cp.resume 
                               FROM users u 
                               LEFT JOIN candidate_profiles cp ON u.id = cp.user_id 
                               WHERE u.id = :user_id");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Function to sanitize input if not defined in db.php
if (!function_exists('sanitize')) {
    function sanitize($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
}

// Function to validate email if not defined in db.php
if (!function_exists('validateEmail')) {
    function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
}

$user = getUserProfile($conn, $user_id);
$is_edit_mode = isset($_GET['edit']) && $_GET['edit'] == 1;

$success = '';
$error = '';

// Process profile update form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $location = sanitize($_POST['location']);
    $title = sanitize($_POST['title']);
    $skills = sanitize($_POST['skills']);
    $bio = sanitize($_POST['bio']);
    
    // Validate input
    if (empty($name) || empty($email)) {
        $error = "Name and email are required fields";
    } elseif (!validateEmail($email)) {
        $error = "Please enter a valid email address";
    } else {
        // Check if email exists for another user
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email AND id != :user_id");
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $error = "Email already exists. Please use a different email.";
        } else {
            // Handle resume upload
            $resume_path = isset($user['resume']) ? $user['resume'] : '';
            if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                $file_type = $_FILES['resume']['type'];
                
                if (in_array($file_type, $allowed_types)) {
                    $file_name = time() . '_' . $_FILES['resume']['name'];
                    $upload_dir = 'uploads/resumes/';
                    
                    // Create directory if it doesn't exist
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $upload_path = $upload_dir . $file_name;
                    
                    if (move_uploaded_file($_FILES['resume']['tmp_name'], $upload_path)) {
                        $resume_path = $upload_path;
                    } else {
                        $error = "Failed to upload resume. Please try again.";
                    }
                } else {
                    $error = "Invalid file type. Please upload a PDF or Word document.";
                }
            }
            
            if (empty($error)) {
                try {
                    // Start transaction
                    $conn->beginTransaction();
                    
                    // Update user profile
                    $stmt = $conn->prepare("UPDATE users SET name = :name, email = :email WHERE id = :user_id");
                    $stmt->bindParam(':name', $name);
                    $stmt->bindParam(':email', $email);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                    
                    // Check if candidate profile exists
                    $stmt = $conn->prepare("SELECT * FROM candidate_profiles WHERE user_id = :user_id");
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                    
                    if ($stmt->rowCount() > 0) {
                        // Update existing profile
                        $sql = "UPDATE candidate_profiles SET phone = :phone, location = :location, title = :title, skills = :skills, bio = :bio";
                        
                        if (!empty($resume_path)) {
                            $sql .= ", resume = :resume";
                        }
                        
                        $sql .= " WHERE user_id = :user_id";
                        
                        $stmt = $conn->prepare($sql);
                        $stmt->bindParam(':phone', $phone);
                        $stmt->bindParam(':location', $location);
                        $stmt->bindParam(':title', $title);
                        $stmt->bindParam(':skills', $skills);
                        $stmt->bindParam(':bio', $bio);
                        $stmt->bindParam(':user_id', $user_id);
                        
                        if (!empty($resume_path)) {
                            $stmt->bindParam(':resume', $resume_path);
                        }
                    } else {
                        // Create new profile
                        $stmt = $conn->prepare("INSERT INTO candidate_profiles (user_id, phone, location, title, skills, bio, resume) VALUES (:user_id, :phone, :location, :title, :skills, :bio, :resume)");
                        $stmt->bindParam(':user_id', $user_id);
                        $stmt->bindParam(':phone', $phone);
                        $stmt->bindParam(':location', $location);
                        $stmt->bindParam(':title', $title);
                        $stmt->bindParam(':skills', $skills);
                        $stmt->bindParam(':bio', $bio);
                        $stmt->bindParam(':resume', $resume_path);
                    }
                    
                    $stmt->execute();
                    
                    // Commit transaction
                    $conn->commit();
                    
                    $success = "Profile updated successfully!";
                    $_SESSION['name'] = $name;
                    $_SESSION['email'] = $email;
                    $user = getUserProfile($conn, $user_id);
                    $is_edit_mode = false;
                } catch (PDOException $e) {
                    // Rollback transaction on error
                    $conn->rollBack();
                    $error = "Database error: " . $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - HiringCafe</title>
    <link rel="icon" href="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCI+PHBhdGggZmlsbD0iI0ZGNjZCRiIgZD0iTTEyIDJDNi40NzcgMiAyIDYuNDc3IDIgMTJzNC40NzcgMTAgMTAgMTAgMTAtNC40NzcgMTAtMTBTMTcuNTIzIDIgMTIgMnptMCAxOGMtNC40MTggMC04LTMuNTgyLTgtOHMzLjU4Mi04IDgtOCA4IDMuNTgyIDggOC0zLjU4MiA4LTggOHptLTEtMTNIOXYyaDJ2LTJ6bTYgMGgtMnYyaDJ2LTJ6bS03IDRsLTMuNSAzLjVMOCAxNmw0LTQgNC40IDQuNEwxOCAxNWwtNC01eiIvPjwvc3ZnPg==">
    <style>
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f9fafb;
            color: #111827;
            line-height: 1.5;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        a {
            text-decoration: none;
            color: inherit;
        }
        
        /* Header Styles */
        header {
            background-color: #ffffff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
            font-weight: 700;
            font-size: 1.5rem;
            color: #111827;
        }
        
        .logo-icon {
            width: 36px;
            height: 36px;
            background-color: #FF66BF;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.5rem;
        }
        
        .logo-icon svg {
            width: 24px;
            height: 24px;
            fill: white;
        }
        
        .user-nav {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            position: relative;
        }
        
        .user-avatar {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            background-color: #FF66BF;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
        }
        
        /* Main Content Styles */
        .main-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 2rem 1rem;
            flex: 1;
        }
        
        .page-header {
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #111827;
        }
        
        .page-subtitle {
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        .profile-container {
            background-color: #ffffff;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .profile-avatar {
            width: 6rem;
            height: 6rem;
            border-radius: 50%;
            background-color: #FF66BF;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 2rem;
        }
        
        .profile-info {
            flex: 1;
        }
        
        .profile-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #111827;
        }
        
        .profile-title {
            font-size: 1rem;
            color: #4b5563;
            margin-bottom: 0.5rem;
        }
        
        .profile-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        .profile-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .profile-meta-item svg {
            width: 1.25rem;
            height: 1.25rem;
        }
        
        .profile-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .profile-section {
            margin-bottom: 2rem;
        }
        
        .profile-section:last-child {
            margin-bottom: 0;
        }
        
        .profile-section-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #111827;
        }
        
        .profile-bio {
            font-size: 0.875rem;
            color: #4b5563;
            line-height: 1.7;
        }
        
        .skills-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .skill-tag {
            background-color: #f3f4f6;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            color: #4b5563;
        }
        
        .resume-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background-color: #f3f4f6;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            color: #4b5563;
            margin-top: 1rem;
        }
        
        .resume-link:hover {
            background-color: #e5e7eb;
        }
        
        .resume-link svg {
            width: 1.25rem;
            height: 1.25rem;
        }
        
        .form-container {
            background-color: #ffffff;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .form-section {
            margin-bottom: 2rem;
        }
        
        .form-section:last-child {
            margin-bottom: 0;
        }
        
        .form-section-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #111827;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-label {
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
        }
        
        .form-input {
            padding: 0.75rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #FF66BF;
            box-shadow: 0 0 0 3px rgba(255, 102, 191, 0.1);
        }
        
        .form-textarea {
            padding: 0.75rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            transition: all 0.2s;
            resize: vertical;
            min-height: 150px;
        }
        
        .form-textarea:focus {
            outline: none;
            border-color: #FF66BF;
            box-shadow: 0 0 0 3px rgba(255, 102, 191, 0.1);
        }
        
        .form-hint {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 0.25rem;
        }
        
        .form-error {
            color: #ef4444;
            font-size: 0.875rem;
            margin-top: 0.5rem;
            padding: 0.75rem;
            background-color: #fee2e2;
            border-radius: 0.375rem;
        }
        
        .form-success {
            color: #10b981;
            font-size: 0.875rem;
            margin-top: 0.5rem;
            padding: 0.75rem;
            background-color: #ecfdf5;
            border-radius: 0.375rem;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border-radius: 0.375rem;
            font-weight: 500;
            font-size: 0.875rem;
            transition: all 0.2s;
            cursor: pointer;
        }
        
        .btn-primary {
            background-color: #FF66BF;
            color: white;
            border: none;
        }
        
        .btn-primary:hover {
            background-color: #ff4db3;
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid #e5e7eb;
            color: #4b5563;
        }
        
        .btn-outline:hover {
            background-color: #f9fafb;
            border-color: #d1d5db;
        }
        
        /* Footer Styles */
        footer {
            background-color: #ffffff;
            border-top: 1px solid #e5e7eb;
            padding: 1.5rem 0;
            margin-top: auto;
        }
        
        .footer-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .footer-logo {
            display: flex;
            align-items: center;
            font-weight: 700;
            font-size: 1.25rem;
            color: #111827;
        }
        
        .footer-logo-icon {
            width: 2rem;
            height: 2rem;
            background-color: #FF66BF;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.5rem;
        }
        
        .footer-logo-icon svg {
            width: 1.25rem;
            height: 1.25rem;
            fill: white;
        }
        
        .copyright {
            font-size: 0.875rem;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="header-container">
            <a href="index.php" class="logo">
                <div class="logo-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-5-9h10v2H7z"/>
                    </svg>
                </div>
                HiringCafe
            </a>
            
            <div class="user-nav">
                <div class="user-menu">
                    <a href="dashboard.php" class="user-avatar">
                        <?php echo substr($_SESSION['name'], 0, 1); ?>
                    </a>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Main Content -->
    <main class="main-container">
        <div class="page-header">
            <h1 class="page-title"><?php echo $is_edit_mode ? 'Edit Profile' : 'Profile'; ?></h1>
            <p class="page-subtitle">
                <?php echo $is_edit_mode ? 'Update your personal information and resume.' : 'View and manage your profile information.'; ?>
            </p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="form-error" style="max-width: 800px; margin: 0 auto 1.5rem auto;"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="form-success" style="max-width: 800px; margin: 0 auto 1.5rem auto;"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($is_edit_mode): ?>
            <!-- Edit Profile Form -->
            <div class="form-container">
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-section">
                        <h2 class="form-section-title">Personal Information</h2>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="name" class="form-label">Full Name *</label>
                                <input type="text" id="name" name="name" class="form-input" placeholder="Enter your full name" required value="<?php echo isset($user['name']) ? htmlspecialchars($user['name']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" id="email" name="email" class="form-input" placeholder="Enter your email" required value="<?php echo isset($user['email']) ? htmlspecialchars($user['email']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" id="phone" name="phone" class="form-input" placeholder="Enter your phone number" value="<?php echo isset($user['phone']) ? htmlspecialchars($user['phone']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="location" class="form-label">Location</label>
                                <input type="text" id="location" name="location" class="form-input" placeholder="e.g. New York, NY" value="<?php echo isset($user['location']) ? htmlspecialchars($user['location']) : ''; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h2 class="form-section-title">Professional Information</h2>
                        
                        <div class="form-group">
                            <label for="title" class="form-label">Professional Title</label>
                            <input type="text" id="title" name="title" class="form-input" placeholder="e.g. Senior Software Engineer" value="<?php echo isset($user['title']) ? htmlspecialchars($user['title']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="skills" class="form-label">Skills</label>
                            <input type="text" id="skills" name="skills" class="form-input" placeholder="e.g. JavaScript, React, Node.js" value="<?php echo isset($user['skills']) ? htmlspecialchars($user['skills']) : ''; ?>">
                            <div class="form-hint">Separate skills with commas</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="bio" class="form-label">Bio</label>
                            <textarea id="bio" name="bio" class="form-textarea" placeholder="Tell us about yourself..."><?php echo isset($user['bio']) ? htmlspecialchars($user['bio']) : ''; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="resume" class="form-label">Resume</label>
                            <input type="file" id="resume" name="resume" class="form-input" accept=".pdf,.doc,.docx">
                            <div class="form-hint">Upload your resume (PDF or Word document)</div>
                            <?php if (isset($user['resume']) && !empty($user['resume'])): ?>
                                <div style="margin-top: 1rem;">
                                    <p style="font-size: 0.875rem; margin-bottom: 0.5rem;">Current resume: <a href="<?php echo htmlspecialchars($user['resume']); ?>" target="_blank" style="color: #FF66BF;"><?php echo basename($user['resume']); ?></a></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <a href="profile.php" class="btn btn-outline">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <!-- View Profile -->
            <div class="profile-container">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?php echo substr($_SESSION['name'], 0, 1); ?>
                    </div>
                    
                    <div class="profile-info">
                        <h2 class="profile-name"><?php echo htmlspecialchars($user['name']); ?></h2>
                        <?php if (isset($user['title']) && !empty($user['title'])): ?>
                            <div class="profile-title"><?php echo htmlspecialchars($user['title']); ?></div>
                        <?php endif; ?>
                        <div class="profile-meta">
                            <div class="profile-meta-item">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                    <polyline points="22,6 12,13 2,6"></polyline>
                                </svg>
                                <?php echo htmlspecialchars($user['email']); ?>
                            </div>
                            
                            <?php if (isset($user['phone']) && !empty($user['phone'])): ?>
                                <div class="profile-meta-item">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                    </svg>
                                    <?php echo htmlspecialchars($user['phone']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (isset($user['location']) && !empty($user['location'])): ?>
                                <div class="profile-meta-item">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                        <circle cx="12" cy="10" r="3"></circle>
                                    </svg>
                                    <?php echo htmlspecialchars($user['location']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="profile-actions">
                    <a href="profile.php?edit=1" class="btn btn-outline">Edit Profile</a>
                </div>
                
                <?php if (isset($user['bio']) && !empty($user['bio'])): ?>
                    <div class="profile-section">
                        <h3 class="profile-section-title">About</h3>
                        <div class="profile-bio">
                            <?php echo nl2br(htmlspecialchars($user['bio'])); ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($user['skills']) && !empty($user['skills'])): ?>
                    <div class="profile-section">
                        <h3 class="profile-section-title">Skills</h3>
                        <div class="skills-list">
                            <?php
                            $skills = explode(',', $user['skills']);
                            foreach ($skills as $skill) {
                                $skill = trim($skill);
                                if (!empty($skill)) {
                                    echo '<span class="skill-tag">' . htmlspecialchars($skill) . '</span>';
                                }
                            }
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($user['resume']) && !empty($user['resume'])): ?>
                    <div class="profile-section">
                        <h3 class="profile-section-title">Resume</h3>
                        <a href="<?php echo htmlspecialchars($user['resume']); ?>" target="_blank" class="resume-link">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10 9 9 9 8 9"></polyline>
                            </svg>
                            View Resume
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>
    
    <!-- Footer -->
    <footer>
        <div class="footer-container">
            <a href="index.php" class="footer-logo">
                <div class="footer-logo-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-5-9h10v2H7z"/>
                    </svg>
                </div>
                HiringCafe
            </a>
            
            <div class="copyright">
                &copy; <?php echo date('Y'); ?> HiringCafe. All rights reserved.
            </div>
        </div>
    </footer>
</body>
</html>
