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
$user = getUserProfile($conn, $user_id);

// Get user-specific data
if ($user_type === 'candidate') {
    // Get candidate's applications
    $applications = getUserApplications($conn, $user_id);
    
    // Get recommended jobs
    $stmt = $conn->query("SELECT j.*, c.name as company_name, c.logo as company_logo 
                         FROM jobs j 
                         JOIN companies c ON j.company_id = c.id 
                         WHERE j.is_active = 1 
                         ORDER BY j.created_at DESC 
                         LIMIT 5");
    $recommended_jobs = $stmt->fetchAll();
} else {
    // Get employer's company profile
    $company = getCompanyProfile($conn, $user_id);
    
    if ($company) {
        // Get company's job listings
        $jobs = getCompanyJobs($conn, $company['id']);
        
        // Get recent applications for company's jobs
        $stmt = $conn->prepare("SELECT a.*, j.title as job_title, u.name as applicant_name 
                               FROM applications a 
                               JOIN jobs j ON a.job_id = j.id 
                               JOIN users u ON a.user_id = u.id 
                               WHERE j.company_id = :company_id 
                               ORDER BY a.applied_at DESC 
                               LIMIT 10");
        $stmt->bindParam(':company_id', $company['id']);
        $stmt->execute();
        $recent_applications = $stmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - HiringCafe</title>
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
        
        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 0.5rem;
            background-color: #ffffff;
            border-radius: 0.375rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            width: 200px;
            z-index: 10;
            display: none;
        }
        
        .dropdown-menu.active {
            display: block;
        }
        
        .dropdown-menu-item {
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            color: #4b5563;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: background-color 0.2s;
        }
        
        .dropdown-menu-item:hover {
            background-color: #f3f4f6;
        }
        
        .dropdown-menu-item svg {
            width: 1rem;
            height: 1rem;
        }
        
        .dropdown-divider {
            height: 1px;
            background-color: #e5e7eb;
            margin: 0.25rem 0;
        }
        
        /* Main Content Styles */
        .main-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 2rem 1rem;
            flex: 1;
        }
        
        .dashboard-header {
            margin-bottom: 2rem;
        }
        
        .dashboard-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #111827;
        }
        
        .dashboard-subtitle {
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 2rem;
        }
        
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .dashboard-main {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
        
        .dashboard-sidebar {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
        
        .dashboard-card {
            background-color: #ffffff;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
        }
        
        .dashboard-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .dashboard-card-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #111827;
        }
        
        .dashboard-card-action {
            font-size: 0.875rem;
            color: #FF66BF;
            font-weight: 500;
        }
        
        .dashboard-card-action:hover {
            text-decoration: underline;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .stat-card {
            background-color: #ffffff;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
        }
        
        .stat-title {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 0.5rem;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 0.5rem;
        }
        
        .stat-change {
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .stat-change.positive {
            color: #10b981;
        }
        
        .stat-change.negative {
            color: #ef4444;
        }
        
        .job-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .job-item {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            border-radius: 0.375rem;
            transition: background-color 0.2s;
        }
        
        .job-item:hover {
            background-color: #f3f4f6;
        }
        
        .job-logo {
            width: 3rem;
            height: 3rem;
            border-radius: 0.375rem;
            background-color: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .job-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .job-info {
            flex: 1;
        }
        
        .job-title {
            font-size: 1rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.25rem;
        }
        
        .job-company {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 0.5rem;
        }
        
        .job-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            font-size: 0.75rem;
            color: #6b7280;
        }
        
        .job-meta-item {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .job-meta-item svg {
            width: 0.875rem;
            height: 0.875rem;
        }
        
        .application-list {
            display: flex;
            flex-direction: column;
        }
        
        .application-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .application-item:last-child {
            border-bottom: none;
        }
        
        .application-info {
            flex: 1;
        }
        
        .application-job {
            font-size: 0.875rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.25rem;
        }
        
        .application-company {
            font-size: 0.75rem;
            color: #6b7280;
        }
        
        .application-date {
            font-size: 0.75rem;
            color: #6b7280;
        }
        
        .application-status {
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .application-status.pending {
            background-color: #fef3c7;
            color: #d97706;
        }
        
        .application-status.reviewed {
            background-color: #e0f2fe;
            color: #0284c7;
        }
        
        .application-status.shortlisted {
            background-color: #dcfce7;
            color: #16a34a;
        }
        
        .application-status.rejected {
            background-color: #fee2e2;
            color: #dc2626;
        }
        
        .application-status.hired {
            background-color: #f3e8ff;
            color: #7e22ce;
        }
        
        .profile-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        
        .profile-avatar {
            width: 5rem;
            height: 5rem;
            border-radius: 50%;
            background-color: #FF66BF;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .profile-name {
            font-size: 1.125rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.25rem;
        }
        
        .profile-email {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 1rem;
        }
        
        .profile-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1rem;
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
        
        .btn-block {
            width: 100%;
        }
        
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            text-align: center;
        }
        
        .empty-state-icon {
            width: 4rem;
            height: 4rem;
            margin-bottom: 1rem;
            color: #9ca3af;
        }
        
        .empty-state-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.5rem;
        }
        
        .empty-state-description {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 1.5rem;
            max-width: 300px;
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
                    <div class="user-avatar" id="userMenuToggle">
                        <?php echo substr($_SESSION['name'], 0, 1); ?>
                    </div>
                    <div class="dropdown-menu" id="userMenu">
                        <div class="dropdown-menu-item">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            <span>Profile</span>
                        </div>
                        <div class="dropdown-menu-item">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                                <path d="M12 7A4 4 0 1 0 8 3a4 4 0 0 0 4 4z"></path>
                                <line x1="12" y1="11" x2="12" y2="17"></line>
                                <line x1="9" y1="14" x2="15" y2="14"></line>
                            </svg>
                            <span>Account Settings</span>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a href="logout.php" class="dropdown-menu-item">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                <polyline points="16 17 21 12 16 7"></polyline>
                                <line x1="21" y1="12" x2="9" y2="12"></line>
                            </svg>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Main Content -->
    <main class="main-container">
        <div class="dashboard-header">
            <h1 class="dashboard-title">Dashboard</h1>
            <p class="dashboard-subtitle">Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>!</p>
        </div>
        
        <div class="dashboard-grid">
            <div class="dashboard-main">
                <?php if ($user_type === 'candidate'): ?>
                    <!-- Candidate Dashboard -->
                    <div class="dashboard-card">
                        <div class="dashboard-card-header">
                            <h2 class="dashboard-card-title">Your Applications</h2>
                            <a href="#" class="dashboard-card-action">View all</a>
                        </div>
                        
                        <div class="application-list">
                            <?php if (empty($applications)): ?>
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                            <polyline points="14 2 14 8 20 8"></polyline>
                                            <line x1="16" y1="13" x2="8" y2="13"></line>
                                            <line x1="16" y1="17" x2="8" y2="17"></line>
                                            <polyline points="10 9 9 9 8 9"></polyline>
                                        </svg>
                                    </div>
                                    <h3 class="empty-state-title">No applications yet</h3>
                                    <p class="empty-state-description">Start applying for jobs to see your applications here.</p>
                                    <a href="index.php" class="btn btn-primary">Browse Jobs</a>
                                </div>
                            <?php else: ?>
                                <?php foreach ($applications as $application): ?>
                                    <div class="application-item">
                                        <div class="application-info">
                                            <div class="application-job"><?php echo htmlspecialchars($application['job_title']); ?></div>
                                            <div class="application-company"><?php echo htmlspecialchars($application['company_name']); ?></div>
                                        </div>
                                        <div class="application-date">
                                            <?php 
                                            $date = new DateTime($application['applied_at']);
                                            echo $date->format('M d, Y');
                                            ?>
                                        </div>
                                        <div class="application-status <?php echo $application['status']; ?>">
                                            <?php echo ucfirst($application['status']); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="dashboard-card">
                        <div class="dashboard-card-header">
                            <h2 class="dashboard-card-title">Recommended Jobs</h2>
                            <a href="index.php" class="dashboard-card-action">View all</a>
                        </div>
                        
                        <div class="job-list">
                            <?php if (empty($recommended_jobs)): ?>
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                                        </svg>
                                    </div>
                                    <h3 class="empty-state-title">No jobs available</h3>
                                    <p class="empty-state-description">Check back later for new job opportunities.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($recommended_jobs as $job): ?>
                                    <a href="job.php?id=<?php echo $job['id']; ?>" class="job-item">
                                        <div class="job-logo">
                                            <?php if (!empty($job['company_logo'])): ?>
                                                <img src="<?php echo htmlspecialchars($job['company_logo']); ?>" alt="<?php echo htmlspecialchars($job['company_name']); ?>">
                                            <?php else: ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                                                </svg>
                                            <?php endif; ?>
                                        </div>
                                        <div class="job-info">
                                            <h3 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h3>
                                            <div class="job-company"><?php echo htmlspecialchars($job['company_name']); ?></div>
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
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Employer Dashboard -->
                    <div class="dashboard-card">
                        <div class="dashboard-card-header">
                            <h2 class="dashboard-card-title">Overview</h2>
                        </div>
                        
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-title">Active Jobs</div>
                                <div class="stat-value"><?php echo isset($jobs) ? count($jobs) : 0; ?></div>
                                <div class="stat-change positive">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
                                        <polyline points="18 15 12 9 6 15"></polyline>
                                    </svg>
                                    12% from last month
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-title">Total Applications</div>
                                <div class="stat-value"><?php echo isset($recent_applications) ? count($recent_applications) : 0; ?></div>
                                <div class="stat-change positive">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
                                        <polyline points="18 15 12 9 6 15"></polyline>
                                    </svg>
                                    8% from last month
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-title">Profile Views</div>
                                <div class="stat-value">142</div>
                                <div class="stat-change negative">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
                                        <polyline points="6 9 12 15 18 9"></polyline>
                                    </svg>
                                    3% from last month
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="dashboard-card">
                        <div class="dashboard-card-header">
                            <h2 class="dashboard-card-title">Your Job Listings</h2>
                            <a href="post-job.php" class="dashboard-card-action">Post a Job</a>
                        </div>
                        
                        <div class="job-list">
                            <?php if (empty($jobs)): ?>
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                                        </svg>
                                    </div>
                                    <h3 class="empty-state-title">No jobs posted yet</h3>
                                    <p class="empty-state-description">Start posting jobs to attract top talent.</p>
                                    <a href="post-job.php" class="btn btn-primary">Post a Job</a>
                                </div>
                            <?php else: ?>
                                <?php foreach ($jobs as $job): ?>
                                    <a href="job.php?id=<?php echo $job['id']; ?>" class="job-item">
                                        <div class="job-logo">
                                            <?php if (!empty($company['logo'])): ?>
                                                <img src="<?php echo htmlspecialchars($company['logo']); ?>" alt="<?php echo htmlspecialchars($company['name']); ?>">
                                            <?php else: ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                                                </svg>
                                            <?php endif; ?>
                                        </div>
                                        <div class="job-info">
                                            <h3 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h3>
                                            <div class="job-company"><?php echo isset($company['name']) ? htmlspecialchars($company['name']) : ''; ?></div>
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
                                                <div class="job-meta-item">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <circle cx="12" cy="12" r="10"></circle>
                                                        <polyline points="12 6 12 12 16 14"></polyline>
                                                    </svg>
                                                    <?php 
                                                    $date = new DateTime($job['created_at']);
                                                    echo $date->format('M d, Y');
                                                    ?>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="dashboard-card">
                        <div class="dashboard-card-header">
                            <h2 class="dashboard-card-title">Recent Applications</h2>
                            <a href="#" class="dashboard-card-action">View all</a>
                        </div>
                        
                        <div class="application-list">
                            <?php if (empty($recent_applications)): ?>
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                            <polyline points="14 2 14 8 20 8"></polyline>
                                            <line x1="16" y1="13" x2="8" y2="13"></line>
                                            <line x1="16" y1="17" x2="8" y2="17"></line>
                                            <polyline points="10 9 9 9 8 9"></polyline>
                                        </svg>
                                    </div>
                                    <h3 class="empty-state-title">No applications yet</h3>
                                    <p class="empty-state-description">Applications will appear here when candidates apply to your jobs.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($recent_applications as $application): ?>
                                    <div class="application-item">
                                        <div class="application-info">
                                            <div class="application-job"><?php echo htmlspecialchars($application['job_title']); ?></div>
                                            <div class="application-company"><?php echo htmlspecialchars($application['applicant_name']); ?></div>
                                        </div>
                                        <div class="application-date">
                                            <?php 
                                            $date = new DateTime($application['applied_at']);
                                            echo $date->format('M d, Y');
                                            ?>
                                        </div>
                                        <div class="application-status <?php echo $application['status']; ?>">
                                            <?php echo ucfirst($application['status']); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="dashboard-sidebar">
                <div class="dashboard-card profile-card">
                    <div class="profile-avatar">
                        <?php echo substr($_SESSION['name'], 0, 1); ?>
                    </div>
                    <h3 class="profile-name"><?php echo htmlspecialchars($_SESSION['name']); ?></h3>
                    <p class="profile-email"><?php echo htmlspecialchars($_SESSION['email']); ?></p>
                    
                    <?php if ($user_type === 'candidate'): ?>
                        <a href="profile.php" class="btn btn-outline btn-block">View Profile</a>
                        <div class="profile-actions">
                            <a href="resume.php" class="btn btn-outline">Upload Resume</a>
                            <a href="profile.php?edit=1" class="btn btn-outline">Edit Profile</a>
                        </div>
                    <?php else: ?>
                        <?php if (isset($company) && $company): ?>
                            <p class="profile-email"><?php echo htmlspecialchars($company['name']); ?></p>
                            <a href="company-profile.php" class="btn btn-outline btn-block">View Company Profile</a>
                        <?php else: ?>
                            <a href="company-profile.php?create=1" class="btn btn-primary btn-block">Create Company Profile</a>
                        <?php endif; ?>
                        <div class="profile-actions">
                            <a href="post-job.php" class="btn btn-primary">Post a Job</a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <h2 class="dashboard-card-title">Quick Links</h2>
                    </div>
                    
                    <ul style="list-style: none; padding: 0;">
                        <?php if ($user_type === 'candidate'): ?>
                            <li style="margin-bottom: 0.5rem;">
                                <a href="index.php" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; border-radius: 0.375rem; transition: background-color 0.2s;">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                                    </svg>
                                    Browse Jobs
                                </a>
                            </li>
                            <li style="margin-bottom: 0.5rem;">
                                <a href="#" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; border-radius: 0.375rem; transition: background-color 0.2s;">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                                        <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path>
                                    </svg>
                                    Saved Jobs
                                </a>
                            </li>
                            <li style="margin-bottom: 0.5rem;">
                                <a href="#" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; border-radius: 0.375rem; transition: background-color 0.2s;">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                        <polyline points="14 2 14 8 20 8"></polyline>
                                        <line x1="16" y1="13" x2="8" y2="13"></line>
                                        <line x1="16" y1="17" x2="8" y2="17"></line>
                                        <polyline points="10 9 9 9 8 9"></polyline>
                                    </svg>
                                    Applications
                                </a>
                            </li>
                        <?php else: ?>
                            <li style="margin-bottom: 0.5rem;">
                                <a href="post-job.php" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; border-radius: 0.375rem; transition: background-color 0.2s;">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                                        <line x1="12" y1="5" x2="12" y2="19"></line>
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                    </svg>
                                    Post a Job
                                </a>
                            </li>
                            <li style="margin-bottom: 0.5rem;">
                                <a href="#" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; border-radius: 0.375rem; transition: background-color 0.2s;">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                                    </svg>
                                    Manage Jobs
                                </a>
                            </li>
                            <li style="margin-bottom: 0.5rem;">
                                <a href="#" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; border-radius: 0.375rem; transition: background-color 0.2s;">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="9" cy="7" r="4"></circle>
                                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                    </svg>
                                    Candidates
                                </a>
                            </li>
                        <?php endif; ?>
                        <li style="margin-bottom: 0.5rem;">
                            <a href="#" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; border-radius: 0.375rem; transition: background-color 0.2s;">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                                    <circle cx="12" cy="12" r="3"></circle>
                                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                                </svg>
                                Settings
                            </a>
                        </li>
                        <li>
                            <a href="logout.php" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; border-radius: 0.375rem; transition: background-color 0.2s;">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                    <polyline points="16 17 21 12 16 7"></polyline>
                                    <line x1="21" y1="12" x2="9" y2="12"></line>
                                </svg>
                                Logout
                            </a>
                        </li>
                    </ul>
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
    
    <script>
        // JavaScript for user menu dropdown
        document.addEventListener('DOMContentLoaded', function() {
            const userMenuToggle = document.getElementById('userMenuToggle');
            const userMenu = document.getElementById('userMenu');
            
            userMenuToggle.addEventListener('click', function() {
                userMenu.classList.toggle('active');
            });
            
            // Close the menu when clicking outside
            document.addEventListener('click', function(event) {
                if (!userMenuToggle.contains(event.target) && !userMenu.contains(event.target)) {
                    userMenu.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>
