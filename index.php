<?php
session_start();
require_once 'db.php';

// Get jobs with optional filters
$filters = [];
if (isset($_GET['keyword'])) $filters['keyword'] = sanitize($_GET['keyword']);
if (isset($_GET['location'])) $filters['location'] = sanitize($_GET['location']);
if (isset($_GET['job_type'])) $filters['job_type'] = sanitize($_GET['job_type']);
if (isset($_GET['category'])) $filters['category'] = sanitize($_GET['category']);

$jobs = getJobs($conn, $filters);

// Get filter options
$stmt = $conn->query("SELECT DISTINCT job_type FROM jobs");
$job_types = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $conn->query("SELECT DISTINCT category FROM jobs");
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $conn->query("SELECT DISTINCT location FROM jobs");
$locations = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HiringCafe - Find Your Dream Job</title>
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
        
        .search-container {
            flex: 1;
            max-width: 600px;
            margin: 0 2rem;
            position: relative;
        }
        
        .search-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 1px solid #e5e7eb;
            border-radius: 9999px;
            font-size: 0.875rem;
            background-color: #f9fafb;
            transition: all 0.2s;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #FF66BF;
            background-color: #ffffff;
            box-shadow: 0 0 0 3px rgba(255, 102, 191, 0.1);
        }
        
        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
        }
        
        .user-nav {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .location-selector {
            display: flex;
            align-items: center;
            padding: 0.5rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .location-selector:hover {
            background-color: #f3f4f6;
        }
        
        .location-info {
            margin-left: 0.5rem;
            text-align: left;
        }
        
        .location-name {
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        
        .location-details {
            color: #6b7280;
            font-size: 0.75rem;
        }
        
        .auth-buttons {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .login-btn {
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            font-size: 0.875rem;
            transition: background-color 0.2s;
        }
        
        .login-btn:hover {
            background-color: #f3f4f6;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .menu-icon {
            padding: 0.5rem;
            border-radius: 0.375rem;
            transition: background-color 0.2s;
        }
        
        .menu-icon:hover {
            background-color: #f3f4f6;
        }
        
        .user-avatar {
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            background-color: #FF66BF;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        /* Filter Bar Styles */
        .filter-bar {
            background-color: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            padding: 0.75rem 0;
            overflow-x: auto;
            white-space: nowrap;
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        
        .filter-bar::-webkit-scrollbar {
            display: none;
        }
        
        .filter-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 1rem;
            display: flex;
            gap: 0.5rem;
        }
        
        .filter-btn {
            padding: 0.5rem 0.75rem;
            border: 1px solid #e5e7eb;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            background-color: #ffffff;
            color: #4b5563;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }
        
        .filter-btn:hover {
            background-color: #f9fafb;
            border-color: #d1d5db;
        }
        
        .filter-btn.active {
            background-color: #FF66BF;
            color: white;
            border-color: #FF66BF;
        }
        
        /* Main Content Styles */
        .main-container {
            max-width: 1280px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: #111827;
        }
        
        .jobs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .job-card {
            background-color: #ffffff;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .job-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .job-header {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .company-logo {
            width: 3rem;
            height: 3rem;
            border-radius: 0.375rem;
            background-color: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            overflow: hidden;
        }
        
        .company-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .job-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.25rem;
        }
        
        .company-name {
            font-size: 0.875rem;
            color: #4b5563;
            margin-bottom: 0.5rem;
        }
        
        .job-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .job-tag {
            padding: 0.25rem 0.5rem;
            background-color: #f3f4f6;
            border-radius: 9999px;
            font-size: 0.75rem;
            color: #4b5563;
            white-space: nowrap;
        }
        
        .job-description {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 1rem;
            flex-grow: 1;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .job-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
            font-size: 0.75rem;
            color: #6b7280;
        }
        
        .job-location {
            display: flex;
            align-items: center;
        }
        
        .job-location svg {
            margin-right: 0.25rem;
        }
        
        .job-date {
            font-size: 0.75rem;
            color: #6b7280;
        }
        
        /* Featured Section Styles */
        .featured-section {
            margin-bottom: 3rem;
        }
        
        .featured-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .view-all {
            font-size: 0.875rem;
            color: #FF66BF;
            font-weight: 500;
        }
        
        .view-all:hover {
            text-decoration: underline;
        }
        
        /* Categories Section Styles */
        .categories-section {
            margin-bottom: 3rem;
        }
        
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1rem;
        }
        
        .category-card {
            background-color: #ffffff;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
            text-align: center;
        }
        
        .category-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .category-icon {
            width: 3rem;
            height: 3rem;
            background-color: #f3f4f6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }
        
        .category-name {
            font-size: 1rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.5rem;
        }
        
        .category-count {
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        /* Newsletter Section Styles */
        .newsletter-section {
            background-color: #ffffff;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-bottom: 3rem;
            text-align: center;
        }
        
        .newsletter-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #111827;
        }
        
        .newsletter-description {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 1.5rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .newsletter-form {
            display: flex;
            max-width: 500px;
            margin: 0 auto;
        }
        
        .newsletter-input {
            flex: 1;
            padding: 0.75rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem 0 0 0.375rem;
            font-size: 0.875rem;
        }
        
        .newsletter-input:focus {
            outline: none;
            border-color: #FF66BF;
            box-shadow: 0 0 0 3px rgba(255, 102, 191, 0.1);
        }
        
        .newsletter-btn {
            padding: 0.75rem 1.5rem;
            background-color: #FF66BF;
            color: white;
            font-weight: 500;
            font-size: 0.875rem;
            border: none;
            border-radius: 0 0.375rem 0.375rem 0;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .newsletter-btn:hover {
            background-color: #ff4db3;
        }
        
        /* Footer Styles */
        footer {
            background-color: #ffffff;
            border-top: 1px solid #e5e7eb;
            padding: 3rem 0;
        }
        
        .footer-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
        }
        
        .footer-column h3 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #111827;
        }
        
        .footer-links {
            list-style: none;
        }
        
        .footer-links li {
            margin-bottom: 0.5rem;
        }
        
        .footer-links a {
            font-size: 0.875rem;
            color: #6b7280;
            transition: color 0.2s;
        }
        
        .footer-links a:hover {
            color: #FF66BF;
        }
        
        .footer-bottom {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e5e7eb;
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
        
        .social-links {
            display: flex;
            gap: 1rem;
        }
        
        .social-link {
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            background-color: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s;
        }
        
        .social-link:hover {
            background-color: #e5e7eb;
        }
        
        .social-link svg {
            width: 1.25rem;
            height: 1.25rem;
            fill: #4b5563;
        }
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .header-container {
                flex-wrap: wrap;
            }
            
            .search-container {
                order: 3;
                margin: 1rem 0 0;
                max-width: 100%;
            }
            
            .jobs-grid {
                grid-template-columns: 1fr;
            }
            
            .newsletter-form {
                flex-direction: column;
            }
            
            .newsletter-input {
                border-radius: 0.375rem;
                margin-bottom: 0.5rem;
            }
            
            .newsletter-btn {
                border-radius: 0.375rem;
            }
            
            .footer-bottom {
                flex-direction: column;
                align-items: flex-start;
            }
        }
        
        /* Utility Classes */
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
        
        .text-primary {
            color: #FF66BF;
        }
        
        .hidden {
            display: none;
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
            
            <div class="search-container">
                <form action="index.php" method="GET">
                    <div class="search-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </div>
                    <input type="text" name="keyword" class="search-input" placeholder="Search for jobs, companies, or keywords" value="<?php echo isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : ''; ?>">
                </form>
            </div>
            
            <div class="user-nav">
                <div class="location-selector">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                        <circle cx="12" cy="10" r="3"></circle>
                    </svg>
                    <div class="location-info">
                        <div class="location-name">
                            United States
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </div>
                        <div class="location-details">Remote · Hybrid · Onsite · All Environments</div>
                    </div>
                </div>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="user-menu">
                        <a href="#" class="menu-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="3" y1="12" x2="21" y2="12"></line>
                                <line x1="3" y1="6" x2="21" y2="6"></line>
                                <line x1="3" y1="18" x2="21" y2="18"></line>
                            </svg>
                        </a>
                        <a href="dashboard.php" class="user-avatar">
                            <?php echo substr($_SESSION['name'], 0, 1); ?>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="auth-buttons">
                        <a href="login.php" class="login-btn">Log in</a>
                        <a href="register.php" class="btn btn-primary">Sign up</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>
    
    <!-- Filter Bar -->
    <div class="filter-bar">
        <div class="filter-container">
            <button class="filter-btn">Departments</button>
            <button class="filter-btn">Salary</button>
            <button class="filter-btn">Commitment</button>
            <button class="filter-btn">Experience</button>
            <button class="filter-btn">Job Titles & Keywords</button>
            <button class="filter-btn">Education</button>
            <button class="filter-btn">Licenses & Certifications</button>
            <button class="filter-btn">Security Clearance</button>
            <button class="filter-btn">Languages</button>
            <button class="filter-btn">Shifts & Schedules</button>
            <button class="filter-btn">Travel Requirement</button>
            <button class="filter-btn">Benefits & Perks</button>
            <button class="filter-btn">Encouraged to Apply</button>
            <button class="filter-btn">Company</button>
            <button class="filter-btn">Industry</button>
            <button class="filter-btn">Stage & Funding</button>
            <button class="filter-btn">Size</button>
            <button class="filter-btn">Founding Year</button>
        </div>
    </div>
    
    <!-- Main Content -->
    <main class="main-container">
        <!-- Featured Jobs Section -->
        <section class="featured-section">
            <div class="featured-header">
                <h2 class="section-title">Featured Jobs</h2>
                <a href="#" class="view-all">View all</a>
            </div>
            
            <div class="jobs-grid">
                <?php 
                $featured_count = 0;
                foreach ($jobs as $job): 
                    if ($featured_count >= 6) break;
                    $featured_count++;
                ?>
                <a href="job.php?id=<?php echo $job['id']; ?>" class="job-card">
                    <div class="job-header">
                        <div class="company-logo">
                            <?php if (!empty($job['company_logo'])): ?>
                                <img src="<?php echo htmlspecialchars($job['company_logo']); ?>" alt="<?php echo htmlspecialchars($job['company_name']); ?>">
                            <?php else: ?>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                                </svg>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h3 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h3>
                            <p class="company-name"><?php echo htmlspecialchars($job['company_name']); ?></p>
                        </div>
                    </div>
                    
                    <div class="job-meta">
                        <span class="job-tag"><?php echo htmlspecialchars($job['job_type']); ?></span>
                        <span class="job-tag"><?php echo htmlspecialchars($job['category']); ?></span>
                        <?php if (!empty($job['salary'])): ?>
                            <span class="job-tag"><?php echo htmlspecialchars($job['salary']); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <p class="job-description">
                        <?php echo htmlspecialchars(substr($job['description'], 0, 150)) . '...'; ?>
                    </p>
                    
                    <div class="job-footer">
                        <div class="job-location">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            <?php echo htmlspecialchars($job['location']); ?>
                        </div>
                        <div class="job-date">
                            <?php 
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
                </a>
                <?php endforeach; ?>
                
                <?php if ($featured_count === 0): ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 2rem;">
                    <p>No jobs found. Please try different search criteria.</p>
                </div>
                <?php endif; ?>
            </div>
        </section>
        
        <!-- Categories Section -->
        <section class="categories-section">
            <h2 class="section-title">Browse by Category</h2>
            
            <div class="categories-grid">
                <div class="category-card">
                    <div class="category-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                            <line x1="8" y1="21" x2="16" y2="21"></line>
                            <line x1="12" y1="17" x2="12" y2="21"></line>
                        </svg>
                    </div>
                    <h3 class="category-name">Technology</h3>
                    <p class="category-count">1,234 jobs</p>
                </div>
                
                <div class="category-card">
                    <div class="category-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
                            <line x1="7" y1="7" x2="7.01" y2="7"></line>
                        </svg>
                    </div>
                    <h3 class="category-name">Marketing</h3>
                    <p class="category-count">876 jobs</p>
                </div>
                
                <div class="category-card">
                    <div class="category-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                    </div>
                    <h3 class="category-name">Finance</h3>
                    <p class="category-count">543 jobs</p>
                </div>
                
                <div class="category-card">
                    <div class="category-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <h3 class="category-name">Human Resources</h3>
                    <p class="category-count">321 jobs</p>
                </div>
                
                <div class="category-card">
                    <div class="category-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                    </div>
                    <h3 class="category-name">Legal</h3>
                    <p class="category-count">198 jobs</p>
                </div>
                
                <div class="category-card">
                    <div class="category-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                    </div>
                    <h3 class="category-name">Real Estate</h3>
                    <p class="category-count">156 jobs</p>
                </div>
                
                <div class="category-card">
                    <div class="category-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                        </svg>
                    </div>
                    <h3 class="category-name">Education</h3>
                    <p class="category-count">432 jobs</p>
                </div>
                
                <div class="category-card">
                    <div class="category-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                    </div>
                    <h3 class="category-name">Healthcare</h3>
                    <p class="category-count">765 jobs</p>
                </div>
            </div>
        </section>
        
        <!-- Newsletter Section -->
        <section class="newsletter-section">
            <h2 class="newsletter-title">Get Job Alerts</h2>
            <p class="newsletter-description">
                Stay updated with the latest job opportunities. We'll send you notifications when new jobs matching your preferences are posted.
            </p>
            <form class="newsletter-form">
                <input type="email" class="newsletter-input" placeholder="Enter your email address" required>
                <button type="submit" class="newsletter-btn">Subscribe</button>
            </form>
        </section>
    </main>
    
    <!-- Footer -->
    <footer>
        <div class="footer-container">
            <div class="footer-grid">
                <div class="footer-column">
                    <h3>For Job Seekers</h3>
                    <ul class="footer-links">
                        <li><a href="#">Browse Jobs</a></li>
                        <li><a href="#">Browse Companies</a></li>
                        <li><a href="#">Salary Calculator</a></li>
                        <li><a href="#">Career Advice</a></li>
                        <li><a href="#">Resume Builder</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h3>For Employers</h3>
                    <ul class="footer-links">
                        <li><a href="#">Post a Job</a></li>
                        <li><a href="#">Pricing</a></li>
                        <li><a href="#">Employer Resources</a></li>
                        <li><a href="#">Talent Solutions</a></li>
                        <li><a href="#">Hiring Insights</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h3>HiringCafe</h3>
                    <ul class="footer-links">
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Work at HiringCafe</a></li>
                        <li><a href="#">Blog</a></li>
                        <li><a href="#">Press</a></li>
                        <li><a href="#">Contact Us</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h3>Resources</h3>
                    <ul class="footer-links">
                        <li><a href="#">Help Center</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms of Service</a></li>
                        <li><a href="#">Cookie Policy</a></li>
                        <li><a href="#">Accessibility</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
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
                
                <div class="social-links">
                    <a href="#" class="social-link">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                            <path d="M22.675 0h-21.35c-.732 0-1.325.593-1.325 1.325v21.351c0 .731.593 1.324 1.325 1.324h11.495v-9.294h-3.128v-3.622h3.128v-2.671c0-3.1 1.893-4.788 4.659-4.788 1.325 0 2.463.099 2.795.143v3.24l-1.918.001c-1.504 0-1.795.715-1.795 1.763v2.313h3.587l-.467 3.622h-3.12v9.293h6.116c.73 0 1.323-.593 1.323-1.325v-21.35c0-.732-.593-1.325-1.325-1.325z"/>
                        </svg>
                    </a>
                    <a href="#" class="social-link">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                            <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                        </svg>
                    </a>
                    <a href="#" class="social-link">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                            <path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678c-3.405 0-6.162 2.76-6.162 6.162 0 3.405 2.76 6.162 6.162 6.162 3.405 0 6.162-2.76 6.162-6.162 0-3.405-2.76-6.162-6.162-6.162zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44-.795 0-1.44-.646-1.44-1.44 0-.794.646-1.439 1.44-1.439.793-.001 1.44.645 1.44 1.439z"/>
                        </svg>
                    </a>
                    <a href="#" class="social-link">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                            <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </footer>
    
    <script>
        // JavaScript for filter buttons
        document.addEventListener('DOMContentLoaded', function() {
            const filterButtons = document.querySelectorAll('.filter-btn');
            
            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    this.classList.toggle('active');
                });
            });
        });
        
        // Function to format date
        function formatDate(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffTime = Math.abs(now - date);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            if (diffDays === 0) {
                return 'Today';
            } else if (diffDays === 1) {
                return 'Yesterday';
            } else {
                return diffDays + ' days ago';
            }
        }
    </script>
</body>
</html>
