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

// Function to sanitize input if not defined in db.php
if (!function_exists('sanitize')) {
    function sanitize($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
}

// Process application status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['application_id'])) {
    $application_id = intval($_POST['application_id']);
    $action = sanitize($_POST['action']);
    
    if ($action === 'accept' || $action === 'reject') {
        $status = ($action === 'accept') ? 'accepted' : 'rejected';
        
        // Verify that the job belongs to the employer
        if ($user_type === 'employer') {
            $stmt = $conn->prepare("
                UPDATE job_applications ja
                JOIN jobs j ON ja.job_id = j.id
                SET ja.status = :status
                WHERE ja.id = :application_id AND j.employer_id = :employer_id
            ");
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':application_id', $application_id);
            $stmt->bindParam(':employer_id', $user_id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $success = "Application has been " . $status . " successfully!";
            } else {
                $error = "Failed to update application status. Please try again.";
            }
        } else {
            $error = "You don't have permission to perform this action.";
        }
    }
}

// Get application details if viewing a specific application
$application = null;
if (isset($_GET['id'])) {
    $application_id = intval($_GET['id']);
    
    if ($user_type === 'employer') {
        // For employers, get applications for their jobs
        $stmt = $conn->prepare("
            SELECT ja.*, j.title as job_title, j.company_name, j.location as job_location, 
                   u.name as candidate_name, u.email as candidate_email,
                   cp.phone as candidate_phone, cp.location as candidate_location, 
                   cp.title as candidate_title, cp.skills as candidate_skills, 
                   cp.bio as candidate_bio, cp.resume as candidate_resume
            FROM job_applications ja
            JOIN jobs j ON ja.job_id = j.id
            JOIN users u ON ja.candidate_id = u.id
            LEFT JOIN candidate_profiles cp ON ja.candidate_id = cp.user_id
            WHERE ja.id = :application_id AND j.employer_id = :employer_id
        ");
        $stmt->bindParam(':application_id', $application_id);
        $stmt->bindParam(':employer_id', $user_id);
    } else {
        // For candidates, get their own applications
        $stmt = $conn->prepare("
            SELECT ja.*, j.title as job_title, j.company_name, j.location as job_location, 
                   u.name as employer_name, u.email as employer_email
            FROM job_applications ja
            JOIN jobs j ON ja.job_id = j.id
            JOIN users u ON j.employer_id = u.id
            WHERE ja.id = :application_id AND ja.candidate_id = :candidate_id
        ");
        $stmt->bindParam(':application_id', $application_id);
        $stmt->bindParam(':candidate_id', $user_id);
    }
    
    $stmt->execute();
    $application = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$application) {
        header("Location: dashboard.php");
        exit;
    }
}

// Get all applications
$applications = [];
if ($user_type === 'employer') {
    // For employers, get applications for their jobs
    $stmt = $conn->prepare("
        SELECT ja.id, ja.created_at, ja.status, j.title as job_title, 
               u.name as candidate_name, u.email as candidate_email
        FROM job_applications ja
        JOIN jobs j ON ja.job_id = j.id
        JOIN users u ON ja.candidate_id = u.id
        WHERE j.employer_id = :employer_id
        ORDER BY ja.created_at DESC
    ");
    $stmt->bindParam(':employer_id', $user_id);
} else {
    // For candidates, get their own applications
    $stmt = $conn->prepare("
        SELECT ja.id, ja.created_at, ja.status, j.title as job_title, 
               j.company_name, j.location as job_location
        FROM job_applications ja
        JOIN jobs j ON ja.job_id = j.id
        WHERE ja.candidate_id = :candidate_id
        ORDER BY ja.created_at DESC
    ");
    $stmt->bindParam(':candidate_id', $user_id);
}

$stmt->execute();
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Applications - HiringCafe</title>
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
        
        .applications-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .application-card {
            background-color: #ffffff;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
        }
        
        .application-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .application-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #111827;
        }
        
        .application-meta {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .application-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        .application-meta-item svg {
            width: 1.25rem;
            height: 1.25rem;
        }
        
        .application-status {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .status-accepted {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .status-rejected {
            background-color: #fee2e2;
            color: #b91c1c;
        }
        
        .application-date {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 1rem;
        }
        
        .application-detail {
            background-color: #ffffff;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .application-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 2rem;
        }
        
        .application-header-left {
            flex: 1;
        }
        
        .application-job-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #111827;
        }
        
        .application-company {
            font-size: 1rem;
            color: #4b5563;
            margin-bottom: 0.5rem;
        }
        
        .application-header-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        .application-header-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .application-header-meta-item svg {
            width: 1.25rem;
            height: 1.25rem;
        }
        
        .application-header-right {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 1rem;
        }
        
        .application-section {
            margin-bottom: 2rem;
        }
        
        .application-section:last-child {
            margin-bottom: 0;
        }
        
        .application-section-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #111827;
        }
        
        .application-bio {
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
        
        .application-actions {
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
        
        .btn-success {
            background-color: #10b981;
            color: white;
            border: none;
        }
        
        .btn-success:hover {
            background-color: #059669;
        }
        
        .btn-danger {
            background-color: #ef4444;
            color: white;
            border: none;
        }
        
        .btn-danger:hover {
            background-color: #dc2626;
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
        
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 4rem 2rem;
            text-align: center;
            background-color: #ffffff;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .empty-state-icon {
            width: 4rem;
            height: 4rem;
            color: #d1d5db;
            margin-bottom: 1.5rem;
        }
        
        .empty-state-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #111827;
        }
        
        .empty-state-description {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 1.5rem;
            max-width: 24rem;
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
        <?php if (isset($application)): ?>
            <!-- Application Detail View -->
            <div class="page-header">
                <h1 class="page-title">Application Details</h1>
                <p class="page-subtitle">
                    View detailed information about this job application.
                </p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="form-error" style="max-width: 800px; margin: 0 auto 1.5rem auto;"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (isset($success)): ?>
                <div class="form-success" style="max-width: 800px; margin: 0 auto 1.5rem auto;"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="application-detail">
                <div class="application-header">
                    <div class="application-header-left">
                        <h2 class="application-job-title"><?php echo htmlspecialchars($application['job_title']); ?></h2>
                        <div class="application-company"><?php echo htmlspecialchars($application['company_name']); ?></div>
                        <div class="application-header-meta">
                            <div class="application-header-meta-item">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                                <?php echo htmlspecialchars($application['job_location']); ?>
                            </div>
                            <div class="application-header-meta-item">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                                Applied on <?php echo date('M d, Y', strtotime($application['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                    <div class="application-header-right">
                        <div class="application-status <?php echo 'status-' . $application['status']; ?>">
                            <?php echo ucfirst($application['status']); ?>
                        </div>
                    </div>
                </div>
                
                <?php if ($user_type === 'employer'): ?>
                    <!-- Candidate Information (for employers) -->
                    <div class="application-section">
                        <h3 class="application-section-title">Candidate Information</h3>
                        <div class="application-meta">
                            <div class="application-meta-item">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                                <?php echo htmlspecialchars($application['candidate_name']); ?>
                            </div>
                            <div class="application-meta-item">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                    <polyline points="22,6 12,13 2,6"></polyline>
                                </svg>
                                <?php echo htmlspecialchars($application['candidate_email']); ?>
                            </div>
                            <?php if (isset($application['candidate_phone']) && !empty($application['candidate_phone'])): ?>
                                <div class="application-meta-item">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                    </svg>
                                    <?php echo htmlspecialchars($application['candidate_phone']); ?>
                                </div>
                            <?php endif; ?>
                            <?php if (isset($application['candidate_location']) && !empty($application['candidate_location'])): ?>
                                <div class="application-meta-item">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                        <circle cx="12" cy="10" r="3"></circle>
                                    </svg>
                                    <?php echo htmlspecialchars($application['candidate_location']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if (isset($application['candidate_title']) && !empty($application['candidate_title'])): ?>
                        <div class="application-section">
                            <h3 class="application-section-title">Professional Title</h3>
                            <div class="application-bio">
                                <?php echo htmlspecialchars($application['candidate_title']); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($application['candidate_bio']) && !empty($application['candidate_bio'])): ?>
                        <div class="application-section">
                            <h3 class="application-section-title">About Candidate</h3>
                            <div class="application-bio">
                                <?php echo nl2br(htmlspecialchars($application['candidate_bio'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($application['candidate_skills']) && !empty($application['candidate_skills'])): ?>
                        <div class="application-section">
                            <h3 class="application-section-title">Skills</h3>
                            <div class="skills-list">
                                <?php
                                $skills = explode(',', $application['candidate_skills']);
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
                    
                    <?php if (isset($application['candidate_resume']) && !empty($application['candidate_resume'])): ?>
                        <div class="application-section">
                            <h3 class="application-section-title">Resume</h3>
                            <a href="resume.php?file=<?php echo urlencode($application['candidate_resume']); ?>" target="_blank" class="resume-link">
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
                    
                    <?php if ($application['status'] === 'pending'): ?>
                        <div class="application-actions">
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to reject this application?')">Reject Application</button>
                            </form>
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                                <input type="hidden" name="action" value="accept">
                                <button type="submit" class="btn btn-success">Accept Application</button>
                            </form>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <!-- Employer Information (for candidates) -->
                    <div class="application-section">
                        <h3 class="application-section-title">Employer Information</h3>
                        <div class="application-meta">
                            <div class="application-meta-item">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                                <?php echo htmlspecialchars($application['employer_name']); ?>
                            </div>
                            <div class="application-meta-item">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                    <polyline points="22,6 12,13 2,6"></polyline>
                                </svg>
                                <?php echo htmlspecialchars($application['employer_email']); ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="application-actions">
                    <a href="applications.php" class="btn btn-outline">Back to Applications</a>
                </div>
            </div>
        <?php else: ?>
            <!-- Applications List View -->
            <div class="page-header">
                <h1 class="page-title">Job Applications</h1>
                <p class="page-subtitle">
                    <?php echo $user_type === 'employer' ? 'Manage applications for your job listings.' : 'Track your job applications.'; ?>
                </p>
            </div>
            
            <?php if (empty($applications)): ?>
                <div class="empty-state">
                    <svg xmlns="http://www.w3.org/2000/svg" class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                    </svg>
                    <h3 class="empty-state-title">No applications found</h3>
                    <p class="empty-state-description">
                        <?php echo $user_type === 'employer' ? 'You haven\'t received any job applications yet.' : 'You haven\'t applied to any jobs yet.'; ?>
                    </p>
                    <a href="<?php echo $user_type === 'employer' ? 'post-job.php' : 'index.php'; ?>" class="btn btn-primary">
                        <?php echo $user_type === 'employer' ? 'Post a Job' : 'Browse Jobs'; ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="applications-container">
                    <?php foreach ($applications as $app): ?>
                        <a href="applications.php?id=<?php echo $app['id']; ?>" class="application-card">
                            <h3 class="application-title"><?php echo htmlspecialchars($app['job_title']); ?></h3>
                            <div class="application-meta">
                                <?php if ($user_type === 'employer'): ?>
                                    <div class="application-meta-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="12" cy="7" r="4"></circle>
                                        </svg>
                                        <?php echo htmlspecialchars($app['candidate_name']); ?>
                                    </div>
                                    <div class="application-meta-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                            <polyline points="22,6 12,13 2,6"></polyline>
                                        </svg>
                                        <?php echo htmlspecialchars($app['candidate_email']); ?>
                                    </div>
                                <?php else: ?>
                                    <div class="application-meta-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                            <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                                            <line x1="12" y1="22.08" x2="12" y2="12"></line>
                                        </svg>
                                        <?php echo htmlspecialchars($app['company_name']); ?>
                                    </div>
                                    <div class="application-meta-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                            <circle cx="12" cy="10" r="3"></circle>
                                        </svg>
                                        <?php echo htmlspecialchars($app['job_location']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="application-status <?php echo 'status-' . $app['status']; ?>">
                                <?php echo ucfirst($app['status']); ?>
                            </div>
                            <div class="application-date">
                                Applied on <?php echo date('M d, Y', strtotime($app['created_at'])); ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
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
