<?php
session_start();
require_once 'db.php';

// Check if job ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$job_id = $_GET['id'];
$job = getJobById($conn, $job_id);

// If job not found, redirect to index
if (!$job) {
    header("Location: index.php");
    exit;
}

// Check if user has already applied for this job
$has_applied = false;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id FROM applications WHERE user_id = :user_id AND job_id = :job_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':job_id', $job_id);
    $stmt->execute();
    $has_applied = $stmt->rowCount() > 0;
}

// Process job application
$application_success = false;
$application_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'candidate') {
    // Check if resume is uploaded
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
                // Save application to database
                if (applyForJob($conn, $user_id, $job_id, $upload_path)) {
                    $application_success = true;
                    $has_applied = true;
                } else {
                    $application_error = "Failed to submit application. Please try again.";
                }
            } else {
                $application_error = "Failed to upload resume. Please try again.";
            }
        } else {
            $application_error = "Invalid file type. Please upload a PDF or Word document.";
        }
    } else {
        $application_error = "Please upload your resume.";
    }
}

// Get similar jobs
$stmt = $conn->prepare("SELECT j.*, c.name as company_name, c.logo as company_logo 
                       FROM jobs j 
                       JOIN companies c ON j.company_id = c.id 
                       WHERE j.category = :category AND j.id != :job_id 
                       ORDER BY j.created_at DESC 
                       LIMIT 3");
$stmt->bindParam(':category', $job['category']);
$stmt->bindParam(':job_id', $job_id);
$stmt->execute();
$similar_jobs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($job['title']); ?> - HiringCafe</title>
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
        
        .menu-icon {
            padding: 0.5rem;
            border-radius: 0.375rem;
            transition: background-color 0.2s;
            cursor: pointer;
        }
        
        .menu-icon:hover {
            background-color: #f3f4f6;
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
        
        .job-header {
            margin-bottom: 2rem;
        }
        
        .job-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #111827;
        }
        
        .job-company {
            font-size: 1.125rem;
            color: #4b5563;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .company-logo {
            width: 3rem;
            height: 3rem;
            border-radius: 0.375rem;
            background-color: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .company-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .job-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .job-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        .job-meta-item svg {
            width: 1.25rem;
            height: 1.25rem;
        }
        
        .job-actions {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
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
        
        .btn svg {
            width: 1.25rem;
            height: 1.25rem;
            margin-right: 0.5rem;
        }
        
        .job-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        
        @media (max-width: 768px) {
            .job-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .job-content {
            background-color: #ffffff;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 2rem;
        }
        
        .job-section {
            margin-bottom: 2rem;
        }
        
        .job-section:last-child {
            margin-bottom: 0;
        }
        
        .job-section-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #111827;
        }
        
        .job-description {
            font-size: 0.875rem;
            color: #4b5563;
            line-height: 1.7;
        }
        
        .job-description p {
            margin-bottom: 1rem;
        }
        
        .job-description ul,
        .job-description ol {
            margin-bottom: 1rem;
            padding-left: 1.5rem;
        }
        
        .job-description li {
            margin-bottom: 0.5rem;
        }
        
        .job-sidebar {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
        
        .sidebar-card {
            background-color: #ffffff;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
        }
        
        .sidebar-card-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #111827;
        }
        
        .company-info {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        
        .company-logo-lg {
            width: 5rem;
            height: 5rem;
            border-radius: 0.5rem;
            background-color: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            overflow: hidden;
        }
        
        .company-logo-lg img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .company-name {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #111827;
        }
        
        .company-details {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 1.5rem;
        }
        
        .company-actions {
            width: 100%;
        }
        
        .similar-jobs-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .similar-job-item {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            border-radius: 0.375rem;
            transition: background-color 0.2s;
        }
        
        .similar-job-item:hover {
            background-color: #f3f4f6;
        }
        
        .similar-job-logo {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 0.375rem;
            background-color: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .similar-job-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .similar-job-info {
            flex: 1;
        }
        
        .similar-job-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.25rem;
        }
        
        .similar-job-company {
            font-size: 0.75rem;
            color: #6b7280;
            margin-bottom: 0.5rem;
        }
        
        .similar-job-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            font-size: 0.75rem;
            color: #6b7280;
        }
        
        .application-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
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
        
        .form-error {
            color: #ef4444;
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }
        
        .form-success {
            color: #10b981;
            font-size: 0.875rem;
            margin-top: 0.5rem;
            padding: 0.75rem;
            background-color: #ecfdf5;
            border-radius: 0.375rem;
            text-align: center;
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
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="user-menu">
                        <a href="dashboard.php" class="user-avatar">
                            <?php echo substr($_SESSION['name'], 0, 1); ?>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="auth-buttons">
                        <a href="login.php" class="btn btn-outline">Log in</a>
                        <a href="register.php" class="btn btn-primary">Sign up</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>
    
    <!-- Main Content -->
    <main class="main-container">
        <div class="job-header">
            <h1 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h1>
            <div class="job-company">
                <div class="company-logo">
                    <?php if (!empty($job['company_logo'])): ?>
                        <img src="<?php echo htmlspecialchars($job['company_logo']); ?>" alt="<?php echo htmlspecialchars($job['company_name']); ?>">
                    <?php else: ?>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                        </svg>
                    <?php endif; ?>
                </div>
                <?php echo htmlspecialchars($job['company_name']); ?>
            </div>
            
            <div class="job-meta">
                <div class="job-meta-item">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                        <circle cx="12" cy="10" r="3"></circle>
                    </svg>
                    <?php echo htmlspecialchars($job['location']); ?>
                </div>
                <div class="job-meta-item">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                    </svg>
                    <?php echo htmlspecialchars($job['job_type']); ?>
                </div>
                <?php if (!empty($job['salary'])): ?>
                    <div class="job-meta-item">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                        <?php echo htmlspecialchars($job['salary']); ?>
                    </div>
                <?php endif; ?>
                <div class="job-meta-item">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    Posted <?php 
                    $date = new DateTime($job['created_at']);
                    $now = new DateTime();
                    $interval = $date->diff($now);
                    
                    if ($interval->days == 0) {
                        echo 'Today';
                    } elseif ($interval->days == 1) {
                        echo 'Yesterday';
                    } else {
                        echo $interval->days . ' days ago';
                    }
                    ?>
                </div>
            </div>
            
            <div class="job-actions">
                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'candidate'): ?>
                    <?php if ($has_applied): ?>
                        <button class="btn btn-outline" disabled>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                            Applied
                        </button>
                    <?php else: ?>
                        <a href="#apply-section" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10 9 9 9 8 9"></polyline>
                            </svg>
                            Apply Now
                        </a>
                    <?php endif; ?>
                <?php elseif (!isset($_SESSION['user_id'])): ?>
                    <a href="login.php?redirect=job.php?id=<?php echo $job_id; ?>" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                            <polyline points="10 17 15 12 10 7"></polyline>
                            <line x1="15" y1="12" x2="3" y2="12"></line>
                        </svg>
                        Login to Apply
                    </a>
                <?php endif; ?>
                
                <button class="btn btn-outline">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path>
                    </svg>
                    Save Job
                </button>
                
                <button class="btn btn-outline">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"></path>
                        <polyline points="16 6 12 2 8 6"></polyline>
                        <line x1="12" y1="2" x2="12" y2="15"></line>
                    </svg>
                    Share
                </button>
            </div>
        </div>
        
        <div class="job-grid">
            <div class="job-content">
                <div class="job-section">
                    <h2 class="job-section-title">Job Description</h2>
                    <div class="job-description">
                        <?php echo nl2br(htmlspecialchars($job['description'])); ?>
                    </div>
                </div>
                
                <div class="job-section">
                    <h2 class="job-section-title">Requirements</h2>
                    <div class="job-description">
                        <?php echo nl2br(htmlspecialchars($job['requirements'])); ?>
                    </div>
                </div>
                
                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'candidate' && !$has_applied): ?>
                    <div class="job-section" id="apply-section">
                        <h2 class="job-section-title">Apply for this Job</h2>
                        
                        <?php if ($application_success): ?>
                            <div class="form-success">
                                Your application has been submitted successfully! We'll notify you when there's an update.
                            </div>
                        <?php else: ?>
                            <?php if (!empty($application_error)): ?>
                                <div class="form-error"><?php echo $application_error; ?></div>
                            <?php endif; ?>
                            
                            <form class="application-form" method="POST" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label for="resume" class="form-label">Upload Resume (PDF or Word)</label>
                                    <input type="file" id="resume" name="resume" class="form-input" accept=".pdf,.doc,.docx" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="cover_letter" class="form-label">Cover Letter (Optional)</label>
                                    <textarea id="cover_letter" name="cover_letter" class="form-input" rows="5" placeholder="Tell us why you're a good fit for this position..."></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Submit Application</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="job-sidebar">
                <div class="sidebar-card company-info">
                    <div class="company-logo-lg">
                        <?php if (!empty($job['company_logo'])): ?>
                            <img src="<?php echo htmlspecialchars($job['company_logo']); ?>" alt="<?php echo htmlspecialchars($job['company_name']); ?>">
                        <?php else: ?>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                            </svg>
                        <?php endif; ?>
                    </div>
                    <h3 class="company-name"><?php echo htmlspecialchars($job['company_name']); ?></h3>
                    <p class="company-details">
                        <?php echo !empty($job['company_description']) ? substr(htmlspecialchars($job['company_description']), 0, 150) . '...' : 'No company description available.'; ?>
                    </p>
                    <div class="company-actions">
                        <a href="#" class="btn btn-outline btn-block">View Company Profile</a>
                    </div>
                </div>
                
                <div class="sidebar-card">
                    <h3 class="sidebar-card-title">Similar Jobs</h3>
                    
                    <div class="similar-jobs-list">
                        <?php if (empty($similar_jobs)): ?>
                            <p style="font-size: 0.875rem; color: #6b7280;">No similar jobs found.</p>
                        <?php else: ?>
                            <?php foreach ($similar_jobs as $similar_job): ?>
                                <a href="job.php?id=<?php echo $similar_job['id']; ?>" class="similar-job-item">
                                    <div class="similar-job-logo">
                                        <?php if (!empty($similar_job['company_logo'])): ?>
                                            <img src="<?php echo htmlspecialchars($similar_job['company_logo']); ?>" alt="<?php echo htmlspecialchars($similar_job['company_name']); ?>">
                                        <?php else: ?>
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                                <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                                            </svg>
                                        <?php endif; ?>
                                    </div>
                                    <div class="similar-job-info">
                                        <h4 class="similar-job-title"><?php echo htmlspecialchars($similar_job['title']); ?></h4>
                                        <div class="similar-job-company"><?php echo htmlspecialchars($similar_job['company_name']); ?></div>
                                        <div class="similar-job-meta">
                                            <span><?php echo htmlspecialchars($similar_job['location']); ?></span>
                                            <span>•</span>
                                            <span><?php echo htmlspecialchars($similar_job['job_type']); ?></span>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
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
