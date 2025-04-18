<?php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Database connection
$db = new mysqli('localhost', 'root', '', 'volunteer_management_system');

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $event_name = $db->real_escape_string($_POST['event_name']);
    $description = $db->real_escape_string($_POST['description']);
    $location = $db->real_escape_string($_POST['location']);
    $event_date = $db->real_escape_string($_POST['event_date']);
    $start_time = $db->real_escape_string($_POST['start_time']);
    $end_time = $db->real_escape_string($_POST['end_time']);
    $max_volunteers = (int)$_POST['max_volunteers'];
    $status = $db->real_escape_string($_POST['status']);
    $skills = $_POST['skills'];
    $admin_id = $_SESSION['admin_id'];
    
    // Start transaction
    $db->begin_transaction();
    
    try {
        // Insert event
        $stmt = $db->prepare("INSERT INTO events 
                            (event_name, description, location, event_date, start_time, end_time, 
                             max_volunteers, status, admin_id) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param("ssssssisi", 
            $event_name, 
            $description, 
            $location, 
            $event_date, 
            $start_time, 
            $end_time, 
            $max_volunteers, 
            $status,
            $admin_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Error creating event: " . $db->error);
        }
        
        $event_id = $db->insert_id;
        
        // Insert event skills
        if (!empty($skills)) {
            $skill_stmt = $db->prepare("INSERT INTO event_skills (event_id, skill_id) VALUES (?, ?)");
            
            foreach ($skills as $skill_id) {
                $skill_id = (int)$skill_id;
                $skill_stmt->bind_param("ii", $event_id, $skill_id);
                if (!$skill_stmt->execute()) {
                    throw new Exception("Error adding skills: " . $db->error);
                }
            }
        }
        
        // Commit transaction
        $db->commit();
        
        $_SESSION['success_message'] = "Event created successfully!";
        header("Location: events.php");
        exit();
    } catch (Exception $e) {
        $db->rollback();
        $error = $e->getMessage();
    }
}

// Get all skills for the form
$skills = $db->query("SELECT * FROM skills ORDER BY skill_name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>VolunteerHub - Add Event</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>
<body class="bg-gray-50 font-sans">
  <!-- Header -->
  <header class="bg-gradient-to-r from-indigo-700 to-indigo-500 text-white p-4 sticky top-0 z-50 shadow-md">
    <div class="max-w-7xl mx-auto flex justify-between items-center">
      <h1 class="text-xl font-bold flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>
        VolunteerHub Admin
      </h1>
      
      <div class="flex items-center space-x-4">
        <span class="hidden md:inline"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
        <a href="logout.php" class="bg-white text-indigo-600 px-3 py-1 rounded-lg text-sm hover:bg-indigo-100 transition-colors">
          Logout
        </a>
      </div>
    </div>
  </header>

  <!-- Sidebar and Main Content -->
  <div class="flex">
    <!-- Sidebar -->
    <aside class="bg-white w-64 min-h-screen shadow-md hidden md:block">
      <div class="p-4">
        <div class="text-center py-4 border-b border-gray-200">
          <h2 class="text-lg font-semibold text-indigo-700"><?php echo htmlspecialchars($_SESSION['organization']); ?></h2>
          <p class="text-sm text-gray-500">Admin Dashboard</p>
        </div>
        
        <nav class="mt-6">
          <a href="dashboard.php" class="block py-2 px-4 text-gray-600 hover:bg-indigo-50 hover:text-indigo-700 rounded-lg transition-colors">Dashboard</a>
          <a href="volunteers.php" class="block py-2 px-4 mt-2 text-gray-600 hover:bg-indigo-50 hover:text-indigo-700 rounded-lg transition-colors">Volunteers</a>
          <a href="events.php" class="block py-2 px-4 mt-2 bg-indigo-50 text-indigo-700 rounded-lg font-medium">Events</a>
          <a href="certificates.php" class="block py-2 px-4 mt-2 text-gray-600 hover:bg-indigo-50 hover:text-indigo-700 rounded-lg transition-colors">Certificates</a>
          <a href="reports.php" class="block py-2 px-4 mt-2 text-gray-600 hover:bg-indigo-50 hover:text-indigo-700 rounded-lg transition-colors">Reports</a>
        </nav>
      </div>
    </aside>
    
    <!-- Main Content -->
    <main class="flex-1 p-6">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Add New Event</h2>
        <a href="events.php" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
          Back to Events
        </a>
      </div>
      
      <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
          <?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>
      
      <form method="POST" class="bg-white rounded-lg shadow-md p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label for="event_name" class="block text-sm font-medium text-gray-700 mb-1">Event Name *</label>
            <input type="text" id="event_name" name="event_name" required 
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
          </div>
          
          <div>
            <label for="location" class="block text-sm font-medium text-gray-700 mb-1">Location *</label>
            <input type="text" id="location" name="location" required 
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
          </div>
          
          <div>
            <label for="event_date" class="block text-sm font-medium text-gray-700 mb-1">Date *</label>
            <input type="date" id="event_date" name="event_date" required 
                   min="<?php echo date('Y-m-d'); ?>"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
          </div>
          
          <div>
            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
            <select id="status" name="status" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
              <option value="upcoming">Upcoming</option>
              <option value="ongoing">Ongoing</option>
              <option value="completed">Completed</option>
            </select>
          </div>
          
          <div>
            <label for="start_time" class="block text-sm font-medium text-gray-700 mb-1">Start Time *</label>
            <input type="time" id="start_time" name="start_time" required 
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
          </div>
          
          <div>
            <label for="end_time" class="block text-sm font-medium text-gray-700 mb-1">End Time *</label>
            <input type="time" id="end_time" name="end_time" required 
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
          </div>
          
          <div>
            <label for="max_volunteers" class="block text-sm font-medium text-gray-700 mb-1">Max Volunteers *</label>
            <input type="number" id="max_volunteers" name="max_volunteers" min="1" required
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
          </div>
        </div>
        
        <div class="mt-6">
          <label for="skills" class="block text-sm font-medium text-gray-700 mb-1">Required Skills *</label>
          <select id="skills" name="skills[]" multiple required
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all select2">
              <?php while ($skill = $skills->fetch_assoc()): ?>
                  <option value="<?php echo $skill['skill_id']; ?>"><?php echo htmlspecialchars($skill['skill_name']); ?></option>
              <?php endwhile; ?>
          </select>
        </div>
        
        <div class="mt-6">
          <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description *</label>
          <textarea id="description" name="description" rows="4" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all"></textarea>
        </div>
        
        <div class="mt-8 flex justify-end">
          <button type="submit" class="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition-colors font-medium">
            Create Event
          </button>
        </div>
      </form>
    </main>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script>
    $(document).ready(function() {
        $('.select2').select2({
            placeholder: "Select required skills",
            allowClear: true
        });
    });
  </script>
</body>
</html>