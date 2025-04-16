<?php
session_start();
require_once 'db.php';

// Check if user is logged in and is an employer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'employer') {
    header("Location: login.php");
    exit;
}

// Get employer's company profile
$user_id = $_SESSION['user_id'];
$company = getCompanyProfile($conn, $user_id);

// If company profile doesn't exist, redirect to create company profile
if (!$company) {
    header("Location: company-profile.php?create=1");
    exit;
}

$success = '';
$error = '';

// Process job posting form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $requirements = sanitize($_POST['requirements']);
    $location = sanitize($_POST['location']);
    $salary = sanitize($_POST['salary']);
    $job_type = sanitize($_POST['job_type']);
    $category = sanitize($_POST['category']);
    
    // Validate input
    if (empty($title) || empty($description) || empty($requirements) || empty($location) || empty($job_type) || empty($category)) {
        $error = "Please fill in all required fields";
    } else {
        // Create job listing
        if (createJob($conn, $company['id'], $title, $description, $location, $salary, $job_type, $category, $requirements)) {
            $success = "Job posted successfully!";
        } else {
            $error = "Failed to post job. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post a Job - HiringCafe</title>
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
            <h1 class="page-title">Post a Job</h1>
            <p class="page-subtitle">Fill in the details below to create a new job listing.</p>
        </div>
        
        <div class="form-container">
            <?php if (!empty($error)): ?>
                <div class="form-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="form-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-section">
                    <h2 class="form-section-title">Job Details</h2>
                    
                    <div class="form-group full-width">
                        <label for="title" class="form-label">Job Title *</label>
                        <input type="text" id="title" name="title" class="form-input" placeholder="e.g. Senior Software Engineer" required value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="location" class="form-label">Location *</label>
                            <input type="text" id="location" name="location" class="form-input" placeholder="e.g. New York, NY" required value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="job_type" class="form-label">Job Type *</label>
                            <select id="job_type" name="job_type" class="form-select" required>
                                <option value="">Select Job Type</option>
                                <option value="Full-Time" <?php echo (isset($_POST['job_type']) && $_POST['job_type'] === 'Full-Time') ? 'selected' : ''; ?>>Full-Time</option>
                                <option value="Part-Time" <?php echo (isset($_POST['job_type']) && $_POST['job_type'] === 'Part-Time') ? 'selected' : ''; ?>>Part-Time</option>
                                <option value="Contract" <?php echo (isset($_POST['job_type']) && $_POST['job_type'] === 'Contract') ? 'selected' : ''; ?>>Contract</option>
                                <option value="Remote" <?php echo (isset($_POST['job_type']) && $_POST['job_type'] === 'Remote') ? 'selected' : ''; ?>>Remote</option>
                                <option value="Hybrid" <?php echo (isset($_POST['job_type']) && $_POST['job_type'] === 'Hybrid') ? 'selected' : ''; ?>>Hybrid</option>
                                <option value="Onsite" <?php echo (isset($_POST['job_type']) && $_POST['job_type'] === 'Onsite') ? 'selected' : ''; ?>>Onsite</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="category" class="form-label">Category *</label>
                            <select id="category" name="category" class="form-select" required>
                                <option value="">Select Category</option>
                                <option value="Technology" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Technology') ? 'selected' : ''; ?>>Technology</option>
                                <option value="Marketing" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Marketing') ? 'selected' : ''; ?>>Marketing</option>
                                <option value="Finance" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Finance') ? 'selected' : ''; ?>>Finance</option>
                                <option value="Healthcare" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Healthcare') ? 'selected' : ''; ?>>Healthcare</option>
                                <option value="Education" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Education') ? 'selected' : ''; ?>>Education</option>
                                <option value="Sales" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Sales') ? 'selected' : ''; ?>>Sales</option>
                                <option value="Design" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Design') ? 'selected' : ''; ?>>Design</option>
                                <option value="Human Resources" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Human Resources') ? 'selected' : ''; ?>>Human Resources</option>
                                <option value="Legal" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Legal') ? 'selected' : ''; ?>>Legal</option>
                                <option value="Customer Service" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Customer Service') ? 'selected' : ''; ?>>Customer Service</option>
                                <option value="Other" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="salary" class="form-label">Salary (Optional)</label>
                            <input type="text" id="salary" name="salary" class="form-input" placeholder="e.g. $80,000 - $100,000" value="<?php echo isset($_POST['salary']) ? htmlspecialchars($_POST['salary']) : ''; ?>">
                            <div class="form-hint">Leave blank if you prefer not to disclose</div>
                        </div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="description" class="form-label">Job Description *</label>
                        <textarea id="description" name="description" class="form-textarea" placeholder="Provide a detailed description of the job..." required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        <div class="form-hint">Include information about the role, responsibilities, and company culture</div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="requirements" class="form-label">Requirements *</label>
                        <textarea id="requirements" name="requirements" class="form-textarea" placeholder="List the qualifications and skills required for this position..." required><?php echo isset($_POST['requirements']) ? htmlspecialchars($_POST['requirements']) : ''; ?></textarea>
                        <div class="form-hint">Include education, experience, skills, and any other requirements</div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <a href="dashboard.php" class="btn btn-outline">Cancel</a>
                    <button type="submit" class="btn btn-primary">Post Job</button>
                </div>
            </form>
        </div>
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
