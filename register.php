<?php
session_start();

// Database connection
$db = new mysqli('localhost', 'root', '', 'volunteer_management_system');

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$errors = [];
$success = false;

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $full_name = trim($_POST['full_name']);
    $organization = trim($_POST['organization']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // Validate form data
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }
    
    if (empty($organization)) {
        $errors[] = "Organization name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (strlen($username) < 5) {
        $errors[] = "Username must be at least 5 characters";
    }
    
    // Check if username already exists
    $stmt = $db->prepare("SELECT admin_id FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $errors[] = "Username already exists. Please choose another one.";
    }
    
    // Check if email already exists
    $stmt = $db->prepare("SELECT admin_id FROM admins WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $errors[] = "Email already registered. Please use another email or login.";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    // If no errors, insert new admin
    if (empty($errors)) {
        // In a production environment, you would hash the password
        // $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("INSERT INTO admins (full_name, organization, email, username, password, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssss", $full_name, $organization, $email, $username, $password);
        
        if ($stmt->execute()) {
            $success = true;
            
            // Auto login after registration
            $admin_id = $db->insert_id;
            $_SESSION['admin_id'] = $admin_id;
            $_SESSION['username'] = $username;
            $_SESSION['full_name'] = $full_name;
            $_SESSION['organization'] = $organization;
            
            // Redirect to dashboard after successful registration
            header("Location: dashboard.php");
            exit();
        } else {
            $errors[] = "Registration failed: " . $db->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>VolunteerHub - Admin Registration</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .register-bg {
      background-image: url('https://i.pinimg.com/474x/76/62/a8/7662a815beaedb54d122dc4b8c24f064.jpg');
      background-size: cover;
      background-position: center;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    @keyframes slideIn {
      from { transform: translateX(-20px); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }
    @keyframes float {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-10px); }
    }
    .animate-fade-in {
      animation: fadeIn 0.8s ease-out forwards;
    }
    .animate-slide-in {
      animation: slideIn 0.5s ease-out forwards;
    }
    .animate-float {
      animation: float 6s ease-in-out infinite;
    }
    .progress-step {
      transition: all 0.3s ease;
    }
    .form-input-focus {
      transition: all 0.3s ease;
    }
    .form-input-focus:focus {
      transform: translateY(-2px);
      box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.2);
    }
  </style>
</head>
<body class="bg-indigo-50 min-h-screen flex items-center justify-center p-4">
  <div class="grid grid-cols-1 md:grid-cols-2 bg-white rounded-xl shadow-2xl overflow-hidden max-w-5xl w-full animate-fade-in">
    <div class="register-bg hidden md:block relative">
      <div class="absolute inset-0 bg-indigo-900 bg-opacity-70 flex flex-col justify-center items-center text-white p-12">
        <h2 class="text-3xl font-bold mb-6">Join Our Community</h2>
        <p class="text-lg mb-8 text-center">Register as an admin to manage volunteers and events for your organization.</p>
        <div class="grid grid-cols-2 gap-4">
          <img src="https://i.pinimg.com/474x/9b/be/d9/9bbed92bbd9d1e7c75c6cb474e1b1335.jpg" alt="Volunteer work" class="w-full h-32 object-cover rounded-lg shadow-lg animate-float" style="animation-delay: 0s;">
          <img src="https://i.pinimg.com/736x/33/f8/c8/33f8c8a942bf445ee669ce9506ee1fd4.jpg" alt="Volunteer portrait" class="w-full h-32 object-cover rounded-lg shadow-lg animate-float" style="animation-delay: 1.5s;">
          <img src="https://i.pinimg.com/474x/d2/5e/6e/d25e6eac73427212e857488080ec5267.jpg" alt="Community service" class="w-full h-32 object-cover rounded-lg shadow-lg animate-float" style="animation-delay: 3s;">
          <img src="https://i.pinimg.com/736x/c7/c7/75/c7c775b4b8e76bcaa58c7c444dfa9dd1.jpg" alt="Volunteer group" class="w-full h-32 object-cover rounded-lg shadow-lg animate-float" style="animation-delay: 4.5s;">
        </div>
        <p class="mt-8 text-sm italic">"The best way to find yourself is to lose yourself in the service of others."</p>
      </div>
    </div>
    
    <div class="p-8 md:p-12 overflow-y-auto max-h-screen">
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-indigo-700 flex items-center justify-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
          VolunteerHub
        </h1>
        <p class="text-gray-600 mt-2">Admin Registration</p>
      </div>
      
      <!-- Progress Steps -->
      <div class="flex justify-between mb-8">
        <div class="flex flex-col items-center">
          <div id="step1-indicator" class="w-10 h-10 rounded-full bg-indigo-600 text-white flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
          </div>
          <span class="text-xs mt-1 text-indigo-600 font-medium">Account</span>
        </div>
        <div class="flex-1 flex items-center justify-center">
          <div class="h-1 w-full bg-gray-200">
            <div id="progress-bar-1" class="h-1 bg-indigo-600 w-0 transition-all duration-500"></div>
          </div>
        </div>
        <div class="flex flex-col items-center">
          <div id="step2-indicator" class="w-10 h-10 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
          </div>
          <span class="text-xs mt-1 text-gray-500">Organization</span>
        </div>
        <div class="flex-1 flex items-center justify-center">
          <div class="h-1 w-full bg-gray-200">
            <div id="progress-bar-2" class="h-1 bg-indigo-600 w-0 transition-all duration-500"></div>
          </div>
        </div>
        <div class="flex flex-col items-center">
          <div id="step3-indicator" class="w-10 h-10 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
          </div>
          <span class="text-xs mt-1 text-gray-500">Security</span>
        </div>
      </div>
      
      <?php if (!empty($errors)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-4 animate-fade-in">
          <ul class="list-disc pl-5">
            <?php foreach ($errors as $error): ?>
              <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
      
      <?php if ($success): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded mb-4 animate-fade-in">
          Registration successful! Redirecting to dashboard...
        </div>
      <?php endif; ?>
      
      <form method="POST" id="registration-form" class="space-y-4">
        <!-- Step 1: Account Information -->
        <div id="step1" class="animate-slide-in">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
              <input type="text" id="full_name" name="full_name" required 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all form-input-focus"
                    value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
            </div>
            
            <div>
              <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
              <input type="email" id="email" name="email" required 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all form-input-focus"
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
          </div>
          
          <div class="mt-4">
            <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
            <input type="text" id="username" name="username" required 
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all form-input-focus"
                  value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            <p class="text-xs text-gray-500 mt-1">Username must be at least 5 characters</p>
          </div>
          
          <div class="mt-6 flex justify-end">
            <button type="button" id="next-to-step2" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition-all duration-300 transform hover:-translate-y-1 hover:shadow-lg">
              Next
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1 inline" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
              </svg>
            </button>
          </div>
        </div>
        
        <!-- Step 2: Organization Information -->
        <div id="step2" class="hidden">
          <div>
            <label for="organization" class="block text-sm font-medium text-gray-700 mb-1">Organization Name</label>
            <input type="text" id="organization" name="organization" required 
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all form-input-focus"
                  value="<?php echo isset($_POST['organization']) ? htmlspecialchars($_POST['organization']) : ''; ?>">
          </div>
          
          <div class="mt-4">
            <label for="organization_type" class="block text-sm font-medium text-gray-700 mb-1">Organization Type</label>
            <select id="organization_type" name="organization_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all form-input-focus">
              <option value="ngo">Non-Governmental Organization (NGO)</option>
              <option value="nonprofit">Non-Profit Organization</option>
              <option value="educational">Educational Institution</option>
              <option value="community">Community Group</option>
              <option value="corporate">Corporate Social Responsibility</option>
              <option value="other">Other</option>
            </select>
          </div>
          
          <div class="mt-6 flex justify-between">
            <button type="button" id="back-to-step1" class="bg-gray-200 text-gray-800 px-6 py-2 rounded-lg hover:bg-gray-300 transition-all duration-300">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1 inline" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
              </svg>
              Back
            </button>
            <button type="button" id="next-to-step3" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition-all duration-300 transform hover:-translate-y-1 hover:shadow-lg">
              Next
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1 inline" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
              </svg>
            </button>
          </div>
        </div>
        
        <!-- Step 3: Security Information -->
        <div id="step3" class="hidden">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
              <div class="relative">
                <input type="password" id="password" name="password" required 
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all form-input-focus">
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                  <button type="button" id="toggle-password" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" id="eye-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                  </button>
                </div>
              </div>
              <p class="text-xs text-gray-500 mt-1">Password must be at least 8 characters</p>
            </div>
            
            <div>
              <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
              <div class="relative">
                <input type="password" id="confirm_password" name="confirm_password" required 
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all form-input-focus">
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                  <button type="button" id="toggle-confirm-password" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" id="confirm-eye-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
          
          <div class="mt-4">
            <div class="flex items-center">
              <input id="terms" name="terms" type="checkbox" required class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
              <label for="terms" class="ml-2 block text-sm text-gray-700">
                I agree to the <a href="#" class="text-indigo-600 hover:text-indigo-500">Terms of Service</a> and <a href="#" class="text-indigo-600 hover:text-indigo-500">Privacy Policy</a>
              </label>
            </div>
          </div>
          
          <div class="mt-6 flex justify-between">
            <button type="button" id="back-to-step2" class="bg-gray-200 text-gray-800 px-6 py-2 rounded-lg hover:bg-gray-300 transition-all duration-300">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1 inline" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
              </svg>
              Back
            </button>
            <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition-all duration-300 transform hover:-translate-y-1 hover:shadow-lg flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z" />
              </svg>
              Complete Registration
            </button>
          </div>
        </div>
      </form>
      
      <div class="mt-6 text-center text-sm text-gray-500">
        Already have an account? 
        <a href="login.php" class="text-indigo-600 hover:text-indigo-800 font-medium transition-colors">Sign In</a>
      </div>
      
      <div class="mt-4 text-center text-sm text-gray-500">
        <a href="index.html" class="text-indigo-600 hover:text-indigo-800 flex items-center justify-center transition-colors">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
          </svg>
          Back to Homepage
        </a>
      </div>
    </div>
  </div>
  
  <script>
    // Multi-step form navigation
    const step1 = document.getElementById('step1');
    const step2 = document.getElementById('step2');
    const step3 = document.getElementById('step3');
    
    const step1Indicator = document.getElementById('step1-indicator');
    const step2Indicator = document.getElementById('step2-indicator');
    const step3Indicator = document.getElementById('step3-indicator');
    
    const progressBar1 = document.getElementById('progress-bar-1');
    const progressBar2 = document.getElementById('progress-bar-2');
    
    const nextToStep2 = document.getElementById('next-to-step2');
    const backToStep1 = document.getElementById('back-to-step1');
    const nextToStep3 = document.getElementById('next-to-step3');
    const backToStep2 = document.getElementById('back-to-step2');
    
    // Step 1 to Step 2
    nextToStep2.addEventListener('click', function() {
      // Validate step 1 fields
      const fullName = document.getElementById('full_name').value;
      const email = document.getElementById('email').value;
      const username = document.getElementById('username').value;
      
      if (!fullName || !email || !username) {
        alert('Please fill in all fields before proceeding.');
        return;
      }
      
      if (username.length < 5) {
        alert('Username must be at least 5 characters.');
        return;
      }
      
      // Hide step 1, show step 2
      step1.classList.add('hidden');
      step2.classList.remove('hidden');
      step2.classList.add('animate-slide-in');
      
      // Update progress indicators
      step2Indicator.classList.remove('bg-gray-200', 'text-gray-500');
      step2Indicator.classList.add('bg-indigo-600', 'text-white');
      progressBar1.style.width = '100%';
    });
    
    // Step 2 to Step 1
    backToStep1.addEventListener('click', function() {
      // Hide step 2, show step 1
      step2.classList.add('hidden');
      step1.classList.remove('hidden');
      
      // Update progress indicators
      step2Indicator.classList.add('bg-gray-200', 'text-gray-500');
      step2Indicator.classList.remove('bg-indigo-600', 'text-white');
      progressBar1.style.width = '0%';
    });
    
    // Step 2 to Step 3
    nextToStep3.addEventListener('click', function() {
      // Validate step 2 fields
      const organization = document.getElementById('organization').value;
      
      if (!organization) {
        alert('Please fill in all fields before proceeding.');
        return;
      }
      
      // Hide step 2, show step 3
      step2.classList.add('hidden');
      step3.classList.remove('hidden');
      step3.classList.add('animate-slide-in');
      
      // Update progress indicators
      step3Indicator.classList.remove('bg-gray-200', 'text-gray-500');
      step3Indicator.classList.add('bg-indigo-600', 'text-white');
      progressBar2.style.width = '100%';
    });
    
    // Step 3 to Step 2
    backToStep2.addEventListener('click', function() {
      // Hide step 3, show step 2
      step3.classList.add('hidden');
      step2.classList.remove('hidden');
      
      // Update progress indicators
      step3Indicator.classList.add('bg-gray-200', 'text-gray-500');
      step3Indicator.classList.remove('bg-indigo-600', 'text-white');
      progressBar2.style.width = '0%';
    });
    
    // Password visibility toggle
    const togglePassword = document.getElementById('toggle-password');
    const passwordInput = document.getElementById('password');
    const toggleConfirmPassword = document.getElementById('toggle-confirm-password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    
    togglePassword.addEventListener('click', function() {
      const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
      passwordInput.setAttribute('type', type);
    });
    
    toggleConfirmPassword.addEventListener('click', function() {
      const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
      confirmPasswordInput.setAttribute('type', type);
    });
    
    // Form validation
    const form = document.getElementById('registration-form');
    form.addEventListener('submit', function(e) {
      const password = document.getElementById('password').value;
      const confirmPassword = document.getElementById('confirm_password').value;
      const terms = document.getElementById('terms').checked;
      
      if (password !== confirmPassword) {
        e.preventDefault();
        alert('Passwords do not match!');
      }
      
      if (!terms) {
        e.preventDefault();
        alert('You must agree to the Terms of Service and Privacy Policy.');
      }
    });
  </script>
</body>
</html>