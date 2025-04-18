<?php
session_start();

// Database connection - using the same database as login.php
$db = new mysqli('localhost', 'root', '', 'volunteer_management_system');

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$message = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $db->real_escape_string(trim($_POST['email']));

    // Check if email exists in admins table (not users table)
    $sql = "SELECT * FROM admins WHERE email = '$email'";
    $result = $db->query($sql);

    if ($result->num_rows == 1) {
        $admin = $result->fetch_assoc();
        
        // In a real application, you would:
        // 1. Generate a password reset token
        // 2. Send an email with a reset link
        // 3. Store the token in the database with an expiration
        
        // For this example, we'll just send the username (not password for security)
        $to = $email;
        $subject = "Your VolunteerHub Account Recovery";
        $message_body = "Hello,\n\nYour username is: " . $admin['username'] . "\n\n";
        $message_body .= "Please use this username to login at: http://yourdomain.com/login.php\n\n";
        $message_body .= "If you didn't request this, please ignore this email.\n";
        $headers = "From: noreply@volunteerhub.com";
        
        if (mail($to, $subject, $message_body, $headers)) {
            $message = "An email with your account details has been sent to your email address.";
        } else {
            $error = "Failed to send email. Please try again later.";
        }
    } else {
        $error = "No admin account found with that email address.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - VolunteerHub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .bg-gradient {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="flex min-h-screen">
        <!-- Left side - Image/Info -->
        <div class="hidden lg:flex lg:w-1/2 bg-gradient items-center justify-center">
            <div class="max-w-md text-white p-8">
                <h1 class="text-4xl font-bold mb-6">VolunteerHub Admin</h1>
                <p class="text-xl mb-8">Manage your volunteer organization efficiently and effectively.</p>
                <div class="space-y-4">
                    <div class="flex items-center">
                        <i class="fas fa-users text-2xl mr-4"></i>
                        <span>Manage volunteers and events</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-chart-bar text-2xl mr-4"></i>
                        <span>Track volunteer hours and impact</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-file-certificate text-2xl mr-4"></i>
                        <span>Generate certificates automatically</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right side - Forgot Password Form -->
        <div class="w-full lg:w-1/2 flex items-center justify-center p-8">
            <div class="max-w-md w-full">
                <div class="text-center mb-10">
                    <h2 class="text-3xl font-bold text-gray-800">Forgot Password</h2>
                    <p class="text-gray-600 mt-2">Enter your email to recover your admin account</p>
                </div>
                
                <?php if ($message): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded" role="alert">
                        <p class="font-medium">Success</p>
                        <p><?= htmlspecialchars($message) ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded" role="alert">
                        <p class="font-medium">Error</p>
                        <p><?= htmlspecialchars($error) ?></p>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" class="space-y-6">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-gray-400"></i>
                            </div>
                            <input type="email" id="email" name="email" class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150">
                        Send Recovery Email
                    </button>
                    
                    <div class="text-center mt-4">
                        <p class="text-sm text-gray-600">
                            Remember your password?
                            <a href="login.php" class="font-medium text-indigo-600 hover:text-indigo-500">Login here</a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>