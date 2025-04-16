<?php
session_start();
require_once 'db.php';

// Check if user is logged in and is an employer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'employer') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$company = getCompanyProfile($conn, $user_id);
$is_create_mode = isset($_GET['create']) && $_GET['create'] == 1;
$is_edit_mode = isset($_GET['edit']) && $_GET['edit'] == 1;

$success = '';
$error = '';

// Process company profile form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $industry = sanitize($_POST['industry']);
    $size = sanitize($_POST['size']);
    $founded_year = sanitize($_POST['founded_year']);
    $website = sanitize($_POST['website']);
    
    // Validate input
    if (empty($name) || empty($description) || empty($industry) || empty($size)) {
        $error = "Please fill in all required fields";
    } else {
        // Handle logo upload
        $logo_path = '';
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_type = $_FILES['logo']['type'];
            
            if (in_array($file_type, $allowed_types)) {
                $file_name = time() . '_' . $_FILES['logo']['name'];
                $upload_dir = 'uploads/logos/';
                
                // Create directory if it doesn't exist
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $upload_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
                    $logo_path = $upload_path;
                } else {
                    $error = "Failed to upload logo. Please try again.";
                }
            } else {
                $error = "Invalid file type. Please upload a JPEG, PNG, or GIF image.";
            }
        }
        
        if (empty($error)) {
            if ($company) {
                // Update existing company profile
                $stmt = $conn->prepare("UPDATE companies SET name = :name, description = :description, industry = :industry, size = :size, founded_year = :founded_year, website = :website" . (!empty($logo_path) ? ", logo = :logo" : "") . " WHERE id = :id");
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':industry', $industry);
                $stmt->bindParam(':size', $size);
                $stmt->bindParam(':founded_year', $founded_year);
                $stmt->bindParam(':website', $website);
                $stmt->bindParam(':id', $company['id']);
                
                if (!empty($logo_path)) {
                    $stmt->bindParam(':logo', $logo_path);
                }
                
                if ($stmt->execute()) {
                    $success = "Company profile updated successfully!";
                    $company = getCompanyProfile($conn, $user_id);
                } else {
                    $error = "Failed to update company profile. Please try again.";
                }
            } else {
                // Create new company profile
                if (createCompanyProfile($conn, $user_id, $name, $description, $industry, $size, $founded_year, $website, $logo_path)) {
                    $success = "Company profile created successfully!";
                    $company = getCompanyProfile($conn, $user_id);
                    $is_create_mode = false;
                } else {
                    $error = "Failed to create company profile. Please try again.";
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
    <title>Company Profile - HiringCafe</title>
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
        
        .company-profile {
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
        
        .profile-logo {
            width: 6rem;
            height: 6rem;
            border-radius: 0.5rem;
            background-color: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .profile-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
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
        
        .profile-description {
            font-size: 0.875rem;
            color: #4b5563;
            line-height: 1.7;
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
        
        .form-select {
            padding: 0.75rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            transition: all 0.2s;
            background-color: #ffffff;
            cursor: pointer;
        }
        
        .form-select:focus {
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
            <h1 class="page-title"><?php echo $is_create_mode ? 'Create Company Profile' : ($is_edit_mode ? 'Edit Company Profile' : 'Company Profile'); ?></h1>
            <p class="page-subtitle">
                <?php if ($is_create_mode): ?>
                    Complete your company profile to start posting jobs.
                <?php elseif ($is_edit_mode): ?>
                    Update your company information.
                <?php else: ?>
                    View and manage your company profile.
                <?php endif; ?>
            </p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="form-error" style="max-width: 800px; margin: 0 auto 1.5rem auto;"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="form-success" style="max-width: 800px; margin: 0 auto 1.5rem auto;"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($is_create_mode || $is_edit_mode || !$company): ?>
            <!-- Company Profile Form -->
            <div class="form-container">
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-section">
                        <h2 class="form-section-title">Company Information</h2>
                        
                        <div class="form-group full-width">
                            <label for="name" class="form-label">Company Name *</label>
                            <input type="text" id="name" name="name" class="form-input" placeholder="Enter your company name" required value="<?php echo isset($company['name']) ? htmlspecialchars($company['name']) : (isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''); ?>">
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="industry" class="form-label">Industry *</label>
                                <select id="industry" name="industry" class="form-select" required>
                                    <option value="">Select Industry</option>
                                    <option value="Technology" <?php echo (isset($company['industry']) && $company['industry'] === 'Technology') || (isset($_POST['industry']) && $_POST['industry'] === 'Technology') ? 'selected' : ''; ?>>Technology</option>
                                    <option value="Healthcare" <?php echo (isset($company['industry']) && $company['industry'] === 'Healthcare') || (isset($_POST['industry']) && $_POST['industry'] === 'Healthcare') ? 'selected' : ''; ?>>Healthcare</option>
                                    <option value="Finance" <?php echo (isset($company['industry']) && $company['industry'] === 'Finance') || (isset($_POST['industry']) && $_POST['industry'] === 'Finance') ? 'selected' : ''; ?>>Finance</option>
                                    <option value="Education" <?php echo (isset($company['industry']) && $company['industry'] === 'Education') || (isset($_POST['industry']) && $_POST['industry'] === 'Education') ? 'selected' : ''; ?>>Education</option>
                                    <option value="Retail" <?php echo (isset($company['industry']) && $company['industry'] === 'Retail') || (isset($_POST['industry']) && $_POST['industry'] === 'Retail') ? 'selected' : ''; ?>>Retail</option>
                                    <option value="Manufacturing" <?php echo (isset($company['industry']) && $company['industry'] === 'Manufacturing') || (isset($_POST['industry']) && $_POST['industry'] === 'Manufacturing') ? 'selected' : ''; ?>>Manufacturing</option>
                                    <option value="Marketing" <?php echo (isset($company['industry']) && $company['industry'] === 'Marketing') || (isset($_POST['industry']) && $_POST['industry'] === 'Marketing') ? 'selected' : ''; ?>>Marketing</option>
                                    <option value="Hospitality" <?php echo (isset($company['industry']) && $company['industry'] === 'Hospitality') || (isset($_POST['industry']) && $_POST['industry'] === 'Hospitality') ? 'selected' : ''; ?>>Hospitality</option>
                                    <option value="Other" <?php echo (isset($company['industry']) && $company['industry'] === 'Other') || (isset($_POST['industry']) && $_POST['industry'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="size" class="form-label">Company Size *</label>
                                <select id="size" name="size" class="form-select" required>
                                    <option value="">Select Size</option>
                                    <option value="1-10" <?php echo (isset($company['size']) && $company['size'] === '1-10') || (isset($_POST['size']) && $_POST['size'] === '1-10') ? 'selected' : ''; ?>>1-10 employees</option>
                                    <option value="11-50" <?php echo (isset($company['size']) && $company['size'] === '11-50') || (isset($_POST['size']) && $_POST['size'] === '11-50') ? 'selected' : ''; ?>>11-50 employees</option>
                                    <option value="51-200" <?php echo (isset($company['size']) && $company['size'] === '51-200') || (isset($_POST['size']) && $_POST['size'] === '51-200') ? 'selected' : ''; ?>>51-200 employees</option>
                                    <option value="201-500" <?php echo (isset($company['size']) && $company['size'] === '201-500') || (isset($_POST['size']) && $_POST['size'] === '201-500') ? 'selected' : ''; ?>>201-500 employees</option>
                                    <option value="501-1000" <?php echo (isset($company['size']) && $company['size'] === '501-1000') || (isset($_POST['size']) && $_POST['size'] === '501-1000') ? 'selected' : ''; ?>>501-1000 employees</option>
                                    <option value="1001+" <?php echo (isset($company['size']) && $company['size'] === '1001+') || (isset($_POST['size']) && $_POST['size'] === '1001+') ? 'selected' : ''; ?>>1001+ employees</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="founded_year" class="form-label">Founded Year</label>
                                <input type="number" id="founded_year" name="founded_year" class="form-input" placeholder="e.g. 2010" min="1900" max="<?php echo date('Y'); ?>" value="<?php echo isset($company['founded_year']) ? htmlspecialchars($company['founded_year']) : (isset($_POST['founded_year']) ? htmlspecialchars($_POST['founded_year']) : ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="website" class="form-label">Website</label>
                                <input type="url" id="website" name="website" class="form-input" placeholder="e.g. https://example.com" value="<?php echo isset($company['website']) ? htmlspecialchars($company['website']) : (isset($_POST['website']) ? htmlspecialchars($_POST['website']) : ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="description" class="form-label">Company Description *</label>
                            <textarea id="description" name="description" class="form-textarea" placeholder="Tell us about your company..." required><?php echo isset($company['description']) ? htmlspecialchars($company['description']) : (isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''); ?></textarea>
                            <div class="form-hint">Include information about your company culture, mission, and values</div>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="logo" class="form-label">Company Logo</label>
                            <input type="file" id="logo" name="logo" class="form-input" accept="image/jpeg,image/png,image/gif">
                            <div class="form-hint">Recommended size: 400x400 pixels (JPEG, PNG, or GIF)</div>
                            <?php if (isset($company['logo']) && !empty($company['logo'])): ?>
                                <div style="margin-top: 1rem;">
                                    <p style="font-size: 0.875rem; margin-bottom: 0.5rem;">Current logo:</p>
                                    <img src="<?php echo htmlspecialchars($company['logo']); ?>" alt="Company Logo" style="max-width: 100px; max-height: 100px; border-radius: 0.375rem;">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <a href="<?php echo $is_create_mode ? 'dashboard.php' : 'company-profile.php'; ?>" class="btn btn-outline">Cancel</a>
                        <button type="submit" class="btn btn-primary"><?php echo $is_create_mode ? 'Create Profile' : 'Save Changes'; ?></button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <!-- Company Profile View -->
            <div class="company-profile">
                <div class="profile-header">
                    <div class="profile-logo">
                        <?php if (!empty($company['logo'])): ?>
                            <img src="<?php echo htmlspecialchars($company['logo']); ?>" alt="<?php echo htmlspecialchars($company['name']); ?>">
                        <?php else: ?>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="48" height="48">
                                <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                            </svg>
                        <?php endif; ?>
                    </div>
                    
                    <div class="profile-info">
                        <h2 class="profile-name"><?php echo htmlspecialchars($company['name']); ?></h2>
                        <div class="profile-meta">
                            <div class="profile-meta-item">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                                </svg>
                                <?php echo htmlspecialchars($company['industry']); ?>
                            </div>
                            
                            <div class="profile-meta-item">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                </svg>
                                <?php echo htmlspecialchars($company['size']); ?> employees
                            </div>
                            
                            <?php if (!empty($company['founded_year'])): ?>
                                <div class="profile-meta-item">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                        <line x1="16" y1="2" x2="16" y2="6"></line>
                                        <line x1="8" y1="2" x2="8" y2="6"></line>
                                        <line x1="3" y1="10" x2="21" y2="10"></line>
                                    </svg>
                                    Founded in <?php echo htmlspecialchars($company['founded_year']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($company['website'])): ?>
                                <div class="profile-meta-item">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="2" y1="12" x2="22" y2="12"></line>
                                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                                    </svg>
                                    <a href="<?php echo htmlspecialchars($company['website']); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars(preg_replace('#^https?://#', '', $company['website'])); ?></a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="profile-actions">
                    <a href="company-profile.php?edit=1" class="btn btn-outline">Edit Profile</a>
                    <a href="post-job.php" class="btn btn-primary">Post a Job</a>
                </div>
                
                <div class="profile-section">
                    <h3 class="profile-section-title">About</h3>
                    <div class="profile-description">
                        <?php echo nl2br(htmlspecialchars($company['description'])); ?>
                    </div>
                </div>
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
