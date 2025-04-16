<?php
session_start();
require_once 'db.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
$success = '';

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $user_type = sanitize($_POST['user_type']);
    
    // Validate input
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password) || empty($user_type)) {
        $error = "Please fill in all fields";
    } elseif (!validateEmail($email)) {
        $error = "Please enter a valid email address";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif ($user_type !== 'candidate' && $user_type !== 'employer') {
        $error = "Invalid user type";
    } elseif (userExists($conn, $email)) {
        $error = "Email already exists. Please use a different email or login";
    } else {
        // Register user
        if (registerUser($conn, $name, $email, $password, $user_type)) {
            $success = "Registration successful! You can now login.";
            
            // Auto login after registration
            $user = loginUser($conn, $email, $password);
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['user_type'] = $user['user_type'];
                
                // Redirect to dashboard
                header("Location: dashboard.php");
                exit;
            }
        } else {
            $error = "Registration failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - HiringCafe</title>
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
        
        /* Main Content Styles */
        .main-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 2rem 1rem;
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .auth-container {
            background-color: #ffffff;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            width: 100%;
            max-width: 500px;
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .auth-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #111827;
        }
        
        .auth-subtitle {
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        .auth-form {
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
        
        .user-type-selector {
            display: flex;
            gap: 1rem;
            margin-top: 0.5rem;
        }
        
        .user-type-option {
            flex: 1;
            padding: 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .user-type-option:hover {
            background-color: #f9fafb;
            border-color: #d1d5db;
        }
        
        .user-type-option.active {
            background-color: #f0f9ff;
            border-color: #FF66BF;
            box-shadow: 0 0 0 3px rgba(255, 102, 191, 0.1);
        }
        
        .user-type-option svg {
            width: 2rem;
            height: 2rem;
            margin: 0 auto 0.5rem;
        }
        
        .user-type-option h3 {
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .user-type-option p {
            font-size: 0.75rem;
            color: #6b7280;
        }
        
        .auth-btn {
            padding: 0.75rem 1rem;
            background-color: #FF66BF;
            color: white;
            font-weight: 500;
            font-size: 0.875rem;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: background-color 0.2s;
            text-align: center;
        }
        
        .auth-btn:hover {
            background-color: #ff4db3;
        }
        
        .auth-divider {
            display: flex;
            align-items: center;
            margin: 1.5rem 0;
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .auth-divider::before,
        .auth-divider::after {
            content: "";
            flex: 1;
            border-top: 1px solid #e5e7eb;
        }
        
        .auth-divider span {
            padding: 0 0.5rem;
        }
        
        .social-auth {
            display: flex;
            gap: 1rem;
        }
        
        .social-btn {
            flex: 1;
            padding: 0.75rem 1rem;
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: #4b5563;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .social-btn:hover {
            background-color: #f9fafb;
            border-color: #d1d5db;
        }
        
        .social-btn svg {
            width: 1.25rem;
            height: 1.25rem;
        }
        
        .auth-footer {
            text-align: center;
            margin-top: 2rem;
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        .auth-footer a {
            color: #FF66BF;
            font-weight: 500;
        }
        
        .auth-footer a:hover {
            text-decoration: underline;
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
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .auth-container {
                padding: 1.5rem;
            }
            
            .user-type-selector {
                flex-direction: column;
            }
            
            .social-auth {
                flex-direction: column;
            }
            
            .footer-container {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
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
        </div>
    </header>
    
    <!-- Main Content -->
    <main class="main-container">
        <div class="auth-container">
            <div class="auth-header">
                <h1 class="auth-title">Create an account</h1>
                <p class="auth-subtitle">Join HiringCafe to find your dream job or hire top talent.</p>
            </div>
            
            <form class="auth-form" method="POST" action="">
                <?php if (!empty($error)): ?>
                    <div class="form-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="form-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="name" class="form-label">Full Name</label>
                    <input type="text" id="name" name="name" class="form-input" placeholder="Enter your full name" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" class="form-input" placeholder="Enter your email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-input" placeholder="Create a password" required>
                    <small style="color: #6b7280; font-size: 0.75rem;">Password must be at least 8 characters long</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-input" placeholder="Confirm your password" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">I am a</label>
                    <div class="user-type-selector">
                        <div class="user-type-option" data-value="candidate">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            <h3>Job Seeker</h3>
                            <p>I want to find a job</p>
                        </div>
                        <div class="user-type-option" data-value="employer">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                            </svg>
                            <h3>Employer</h3>
                            <p>I want to post jobs</p>
                        </div>
                    </div>
                    <input type="hidden" id="user_type" name="user_type" value="<?php echo isset($_POST['user_type']) ? htmlspecialchars($_POST['user_type']) : 'candidate'; ?>">
                </div>
                
                <button type="submit" class="auth-btn">Create account</button>
                
                <div class="auth-divider">
                    <span>or</span>
                </div>
                
                <div class="social-auth">
                    <button type="button" class="social-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        Google
                    </button>
                    <button type="button" class="social-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                            <path fill="#1877F2" d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                        Facebook
                    </button>
                </div>
            </form>
            
            <div class="auth-footer">
                Already have an account? <a href="login.php">Log in</a>
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
        // JavaScript for user type selection
        document.addEventListener('DOMContentLoaded', function() {
            const userTypeOptions = document.querySelectorAll('.user-type-option');
            const userTypeInput = document.getElementById('user_type');
            
            // Set initial active state based on hidden input value
            userTypeOptions.forEach(option => {
                if (option.dataset.value === userTypeInput.value) {
                    option.classList.add('active');
                }
            });
            
            userTypeOptions.forEach(option => {
                option.addEventListener('click', function() {
                    // Remove active class from all options
                    userTypeOptions.forEach(opt => opt.classList.remove('active'));
                    
                    // Add active class to clicked option
                    this.classList.add('active');
                    
                    // Update hidden input value
                    userTypeInput.value = this.dataset.value;
                });
            });
            
            // Form validation
            const form = document.querySelector('.auth-form');
            const nameInput = document.getElementById('name');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            
            form.addEventListener('submit', function(event) {
                let isValid = true;
                
                // Validate name
                if (!nameInput.value.trim()) {
                    isValid = false;
                    showError(nameInput, 'Name is required');
                } else {
                    clearError(nameInput);
                }
                
                // Validate email
                if (!emailInput.value.trim()) {
                    isValid = false;
                    showError(emailInput, 'Email is required');
                } else if (!isValidEmail(emailInput.value.trim())) {
                    isValid = false;
                    showError(emailInput, 'Please enter a valid email address');
                } else {
                    clearError(emailInput);
                }
                
                // Validate password
                if (!passwordInput.value.trim()) {
                    isValid = false;
                    showError(passwordInput, 'Password is required');
                } else if (passwordInput.value.length < 8) {
                    isValid = false;
                    showError(passwordInput, 'Password must be at least 8 characters long');
                } else {
                    clearError(passwordInput);
                }
                
                // Validate confirm password
                if (!confirmPasswordInput.value.trim()) {
                    isValid = false;
                    showError(confirmPasswordInput, 'Please confirm your password');
                } else if (confirmPasswordInput.value !== passwordInput.value) {
                    isValid = false;
                    showError(confirmPasswordInput, 'Passwords do not match');
                } else {
                    clearError(confirmPasswordInput);
                }
                
                if (!isValid) {
                    event.preventDefault();
                }
            });
            
            // Helper functions
            function showError(input, message) {
                const formGroup = input.closest('.form-group');
                let errorElement = formGroup.querySelector('.form-error');
                
                if (!errorElement) {
                    errorElement = document.createElement('div');
                    errorElement.className = 'form-error';
                    formGroup.appendChild(errorElement);
                }
                
                errorElement.textContent = message;
                input.style.borderColor = '#ef4444';
            }
            
            function clearError(input) {
                const formGroup = input.closest('.form-group');
                const errorElement = formGroup.querySelector('.form-error');
                
                if (errorElement) {
                    errorElement.remove();
                }
                
                input.style.borderColor = '#e5e7eb';
            }
            
            function isValidEmail(email) {
                const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
                return re.test(email);
            }
        });
    </script>
</body>
</html>
