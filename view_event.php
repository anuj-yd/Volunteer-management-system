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

// Get event details
$event_id = $_GET['id'];
$stmt = $db->prepare("SELECT * FROM events WHERE event_id = ? AND admin_id = ?");
$stmt->bind_param("ii", $event_id, $_SESSION['admin_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: events.php');
    exit();
}

$event = $result->fetch_assoc();

// Get required skills for this event
$required_skills = [];
$skills_stmt = $db->prepare("
    SELECT s.skill_id, s.skill_name 
    FROM event_skills es 
    JOIN skills s ON es.skill_id = s.skill_id 
    WHERE es.event_id = ?
");
$skills_stmt->bind_param("i", $event_id);
$skills_stmt->execute();
$skills_result = $skills_stmt->get_result();
while ($skill = $skills_result->fetch_assoc()) {
    $required_skills[] = $skill;
}

// Handle volunteer enrollment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['volunteer_id'])) {
    $volunteer_id = $_POST['volunteer_id'];
    
    // Check if volunteer is already enrolled
    $check_stmt = $db->prepare("SELECT * FROM event_volunteers WHERE event_id = ? AND volunteer_id = ?");
    $check_stmt->bind_param("ii", $event_id, $volunteer_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows === 0) {
        // Check if event has reached max volunteers
        if ($event['max_volunteers'] > 0) {
            $count_stmt = $db->prepare("SELECT COUNT(*) as count FROM event_volunteers WHERE event_id = ?");
            $count_stmt->bind_param("i", $event_id);
            $count_stmt->execute();
            $count_result = $count_stmt->get_result()->fetch_assoc();
            
            if ($count_result['count'] >= $event['max_volunteers']) {
                $error_message = "This event has reached its maximum volunteer capacity.";
            }
        }
        
        if (!isset($error_message)) {
            // Check if volunteer has required skills (only if event has required skills)
            $has_required_skills = true;
            
            if (!empty($required_skills)) {
                $skills_required = array_column($required_skills, 'skill_id');
                
                $volunteer_skills_stmt = $db->prepare("
                    SELECT vs.skill_id 
                    FROM volunteer_skills vs 
                    WHERE vs.volunteer_id = ?
                ");
                $volunteer_skills_stmt->bind_param("i", $volunteer_id);
                $volunteer_skills_stmt->execute();
                $volunteer_skills_result = $volunteer_skills_stmt->get_result();
                $volunteer_skills = [];
                
                while ($skill = $volunteer_skills_result->fetch_assoc()) {
                    $volunteer_skills[] = $skill['skill_id'];
                }
                
                // Check if volunteer has at least one required skill
                $common_skills = array_intersect($skills_required, $volunteer_skills);
                if (empty($common_skills)) {
                    $has_required_skills = false;
                    $error_message = "This volunteer doesn't have the required skills for this event.";
                }
            }
            
            if ($has_required_skills) {
                // Enroll volunteer in event_volunteers
                $enroll_stmt = $db->prepare("
                    INSERT INTO event_volunteers (event_id, volunteer_id, enrollment_date) 
                    VALUES (?, ?, NOW())
                ");
                $enroll_stmt->bind_param("ii", $event_id, $volunteer_id);
                
                // Also add to volunteer_participation with default values
                $participation_stmt = $db->prepare("
                    INSERT INTO volunteer_participation (volunteer_id, event_id, hours_worked, certificate_generated)
                    VALUES (?, ?, 0, FALSE)
                ");
                $participation_stmt->bind_param("ii", $volunteer_id, $event_id);
                
                if ($enroll_stmt->execute() && $participation_stmt->execute()) {
                    $success_message = "Volunteer enrolled successfully!";
                } else {
                    $error_message = "Failed to enroll volunteer: " . $db->error;
                }
            }
        }
    } else {
        $error_message = "This volunteer is already enrolled in the event.";
    }
}

// Get volunteers for this event
$volunteers = [];
$volunteer_count = 0;

$volunteer_result = $db->query("
    SELECT v.* 
    FROM volunteers v
    JOIN event_volunteers ev ON v.volunteer_id = ev.volunteer_id
    WHERE ev.event_id = $event_id
");

if ($volunteer_result) {
    $volunteers = $volunteer_result;
    $volunteer_count = $volunteer_result->num_rows;
}

// Get all available volunteers (not already enrolled in this event) with matching skills
if (!empty($required_skills)) {
    // If event has required skills, only show volunteers with at least one matching skill
    $required_skill_ids = array_column($required_skills, 'skill_id');
    $skill_ids_placeholder = implode(',', array_fill(0, count($required_skill_ids), '?'));
    
    $available_query = "
        SELECT v.*, 
               GROUP_CONCAT(s.skill_name SEPARATOR ', ') as skills,
               GROUP_CONCAT(s.skill_id) as skill_ids
        FROM volunteers v
        JOIN volunteer_skills vs ON v.volunteer_id = vs.volunteer_id
        JOIN skills s ON vs.skill_id = s.skill_id
        WHERE v.volunteer_id NOT IN (
            SELECT volunteer_id FROM event_volunteers WHERE event_id = $event_id
        )
        AND v.status = 'active'
        AND vs.skill_id IN ($skill_ids_placeholder)
        GROUP BY v.volunteer_id
    ";
    
    $available_stmt = $db->prepare($available_query);
    $available_stmt->bind_param(str_repeat('i', count($required_skill_ids)), ...$required_skill_ids);
    $available_stmt->execute();
    $available_result = $available_stmt->get_result();
} else {
    // If no required skills, show all active volunteers not already enrolled
    $available_query = "
        SELECT v.*, 
               GROUP_CONCAT(s.skill_name SEPARATOR ', ') as skills,
               GROUP_CONCAT(s.skill_id) as skill_ids
        FROM volunteers v
        LEFT JOIN volunteer_skills vs ON v.volunteer_id = vs.volunteer_id
        LEFT JOIN skills s ON vs.skill_id = s.skill_id
        WHERE v.volunteer_id NOT IN (
            SELECT volunteer_id FROM event_volunteers WHERE event_id = $event_id
        )
        AND v.status = 'active'
        GROUP BY v.volunteer_id
    ";
    $available_result = $db->query($available_query);
}

if ($available_result) {
    $available_volunteers = $available_result;
}

// Get current date and time for status calculation
$current_date = date('Y-m-d');
$current_time = date('H:i:s');

// Calculate event status dynamically
$event_status = '';
if ($event['event_date'] > $current_date) {
    $event_status = 'upcoming';
} elseif ($event['event_date'] == $current_date) {
    if ($current_time < $event['start_time']) {
        $event_status = 'upcoming';
    } elseif ($current_time >= $event['start_time'] && $current_time <= $event['end_time']) {
        $event_status = 'ongoing';
    } else {
        $event_status = 'completed';
    }
} else {
    $event_status = 'completed';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>View Event | VolunteerHub</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js" defer></script>
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <style>
    .select2-container {
        width: 100% !important;
    }
    .select2-container--default .select2-selection--single {
        height: 42px;
        padding: 6px;
        border-color: #d1d5db;
        border-radius: 0.5rem;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 42px;
    }
    .event-progress {
        transition: width 1s ease-in-out;
    }
    .volunteer-card {
        transition: all 0.3s ease;
    }
    .volunteer-card:hover {
        transform: translateY(-5px);
    }
  </style>
</head>
<body class="bg-gray-50 min-h-screen">
  <!-- Header -->
  <header class="bg-gradient-to-r from-blue-700 to-blue-500 text-white p-4 sticky top-0 z-50 shadow-md">
    <div class="max-w-7xl mx-auto flex justify-between items-center">
      <h1 class="text-xl font-bold flex items-center">
        <i class="fas fa-calendar-alt mr-2"></i>
        VolunteerHub Admin
      </h1>
      
      <div class="flex items-center space-x-4">
        <span class="hidden md:inline"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
        <a href="logout.php" class="bg-white text-blue-600 px-3 py-1 rounded-lg text-sm hover:bg-blue-50 transition-colors">
          <i class="fas fa-sign-out-alt mr-1"></i> Logout
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
          <h2 class="text-lg font-semibold text-blue-700"><?php echo htmlspecialchars($_SESSION['organization']); ?></h2>
          <p class="text-sm text-gray-500">Admin Dashboard</p>
        </div>
        
        <nav class="mt-6">
          <a href="dashboard.php" class="flex items-center py-2 px-4 text-gray-600 hover:bg-blue-50 hover:text-blue-700 rounded-lg transition-colors">
            <i class="fas fa-tachometer-alt w-5 mr-2"></i> Dashboard
          </a>
          <a href="volunteers.php" class="flex items-center py-2 px-4 mt-2 text-gray-600 hover:bg-blue-50 hover:text-blue-700 rounded-lg transition-colors">
            <i class="fas fa-users w-5 mr-2"></i> Volunteers
          </a>
          <a href="events.php" class="flex items-center py-2 px-4 mt-2 bg-blue-50 text-blue-700 rounded-lg font-medium">
            <i class="fas fa-calendar-alt w-5 mr-2"></i> Events
          </a>
          <a href="certificates.php" class="flex items-center py-2 px-4 mt-2 text-gray-600 hover:bg-blue-50 hover:text-blue-700 rounded-lg transition-colors">
            <i class="fas fa-certificate w-5 mr-2"></i> Certificates
          </a>
          <a href="reports.php" class="flex items-center py-2 px-4 mt-2 text-gray-600 hover:bg-blue-50 hover:text-blue-700 rounded-lg transition-colors">
            <i class="fas fa-chart-bar w-5 mr-2"></i> Reports
          </a>
        </nav>
      </div>
    </aside>
    
    <!-- Mobile Sidebar Toggle -->
    <div class="md:hidden fixed bottom-4 right-4 z-40">
      <button id="sidebar-toggle" class="bg-blue-600 text-white p-3 rounded-full shadow-lg">
        <i class="fas fa-bars"></i>
      </button>
    </div>
    
    <!-- Mobile Sidebar -->
    <div id="mobile-sidebar" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-40 hidden md:hidden">
      <div class="absolute left-0 top-0 bottom-0 w-64 bg-white shadow-lg transform transition-transform duration-300 ease-in-out">
        <div class="p-4">
          <button id="close-sidebar" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700">
            <i class="fas fa-times"></i>
          </button>
          
          <div class="text-center py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-blue-700"><?php echo htmlspecialchars($_SESSION['organization']); ?></h2>
            <p class="text-sm text-gray-500">Admin Dashboard</p>
          </div>
          
          <nav class="mt-6">
            <a href="dashboard.php" class="flex items-center py-2 px-4 text-gray-600 hover:bg-blue-50 hover:text-blue-700 rounded-lg transition-colors">
              <i class="fas fa-tachometer-alt w-5 mr-2"></i> Dashboard
            </a>
            <a href="volunteers.php" class="flex items-center py-2 px-4 mt-2 text-gray-600 hover:bg-blue-50 hover:text-blue-700 rounded-lg transition-colors">
              <i class="fas fa-users w-5 mr-2"></i> Volunteers
            </a>
            <a href="events.php" class="flex items-center py-2 px-4 mt-2 bg-blue-50 text-blue-700 rounded-lg font-medium">
              <i class="fas fa-calendar-alt w-5 mr-2"></i> Events
            </a>
            <a href="certificates.php" class="flex items-center py-2 px-4 mt-2 text-gray-600 hover:bg-blue-50 hover:text-blue-700 rounded-lg transition-colors">
              <i class="fas fa-certificate w-5 mr-2"></i> Certificates
            </a>
            <a href="reports.php" class="flex items-center py-2 px-4 mt-2 text-gray-600 hover:bg-blue-50 hover:text-blue-700 rounded-lg transition-colors">
              <i class="fas fa-chart-bar w-5 mr-2"></i> Reports
            </a>
          </nav>
        </div>
      </div>
    </div>
    
    <!-- Main Content -->
    <main class="flex-1 p-6">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800 flex items-center">
          <i class="fas fa-calendar-check text-blue-600 mr-2"></i>
          Event Details
        </h2>
        <div class="flex space-x-2">
          <a href="edit_event.php?id=<?php echo $event['event_id']; ?>" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center">
            <i class="fas fa-edit mr-1"></i> Edit Event
          </a>
          <a href="events.php" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors flex items-center">
            <i class="fas fa-arrow-left mr-1"></i> Back
          </a>
        </div>
      </div>
      
      <!-- Success/Error messages -->
      <?php if (isset($success_message)): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-md flex items-center">
          <i class="fas fa-check-circle mr-2"></i>
          <p><?php echo htmlspecialchars($success_message); ?></p>
        </div>
      <?php endif; ?>
      
      <?php if (isset($error_message)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-md flex items-center">
          <i class="fas fa-exclamation-circle mr-2"></i>
          <p><?php echo htmlspecialchars($error_message); ?></p>
        </div>
      <?php endif; ?>
      
      <!-- Event Details Card -->
      <div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
        <div class="bg-blue-600 text-white p-4">
          <h3 class="text-xl font-semibold"><?php echo htmlspecialchars($event['event_name']); ?></h3>
        </div>
        
        <div class="p-6">
          <div class="flex flex-col md:flex-row md:justify-between">
            <div class="mb-6 md:mb-0">
              <div class="flex items-center mb-4">
                <div class="bg-blue-100 p-2 rounded-full mr-3">
                  <i class="fas fa-map-marker-alt text-blue-600"></i>
                </div>
                <div>
                  <p class="text-sm text-gray-500">Location</p>
                  <p class="font-medium"><?php echo htmlspecialchars($event['location']); ?></p>
                </div>
              </div>
              
              <div class="flex items-center mb-4">
                <div class="bg-blue-100 p-2 rounded-full mr-3">
                  <i class="fas fa-calendar-day text-blue-600"></i>
                </div>
                <div>
                  <p class="text-sm text-gray-500">Date</p>
                  <p class="font-medium"><?php echo date('F j, Y', strtotime($event['event_date'])); ?></p>
                </div>
              </div>
              
              <div class="flex items-center mb-4">
                <div class="bg-blue-100 p-2 rounded-full mr-3">
                  <i class="fas fa-clock text-blue-600"></i>
                </div>
                <div>
                  <p class="text-sm text-gray-500">Time</p>
                  <p class="font-medium"><?php echo date('g:i A', strtotime($event['start_time'])) . ' - ' . date('g:i A', strtotime($event['end_time'])); ?></p>
                </div>
              </div>
              
              <div class="flex items-center">
                <div class="bg-blue-100 p-2 rounded-full mr-3">
                  <i class="fas fa-user-check text-blue-600"></i>
                </div>
                <div>
                  <p class="text-sm text-gray-500">Status</p>
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php 
                    echo $event_status === 'upcoming' ? 'bg-blue-100 text-blue-800' : 
                         ($event_status === 'ongoing' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'); 
                  ?>">
                    <?php echo ucfirst($event_status); ?>
                  </span>
                </div>
              </div>
            </div>
            
            <div class="md:text-right">
              <div class="bg-gray-100 p-4 rounded-lg">
                <div class="text-lg font-semibold mb-2">Volunteers Registered</div>
                <div class="text-4xl font-bold text-blue-600 mb-2">
                  <?php echo $volunteer_count; ?>
                  <?php if ($event['max_volunteers']): ?>
                    <span class="text-lg text-gray-500">/ <?php echo $event['max_volunteers']; ?></span>
                  <?php endif; ?>
                </div>
                
                <?php if ($event['max_volunteers']): ?>
                  <div class="w-full bg-gray-200 rounded-full h-2.5 mb-2">
                    <div class="bg-blue-600 h-2.5 rounded-full event-progress" style="width: <?php echo min(100, ($volunteer_count / $event['max_volunteers']) * 100); ?>%"></div>
                  </div>
                  <p class="text-sm text-gray-500">
                    <?php echo $event['max_volunteers'] - $volunteer_count; ?> spots remaining
                  </p>
                <?php endif; ?>
              </div>
              
              <?php if ($event_status !== 'completed'): ?>
                <button onclick="document.getElementById('enroll-modal').classList.remove('hidden')" 
                        class="mt-4 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors w-full flex items-center justify-center">
                  <i class="fas fa-user-plus mr-1"></i> Enroll Volunteer
                </button>
              <?php endif; ?>
            </div>
          </div>
          
          <div class="mt-8">
            <h4 class="text-lg font-semibold text-gray-800 mb-3 flex items-center">
              <i class="fas fa-info-circle text-blue-600 mr-2"></i> Description
            </h4>
            <div class="bg-gray-50 p-4 rounded-lg">
              <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
            </div>
          </div>
          
          <?php if (!empty($required_skills)): ?>
            <div class="mt-6">
              <h4 class="text-lg font-semibold text-gray-800 mb-3 flex items-center">
                <i class="fas fa-tools text-blue-600 mr-2"></i> Required Skills
              </h4>
              <div class="flex flex-wrap gap-2">
                <?php foreach ($required_skills as $skill): ?>
                  <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                    <i class="fas fa-check-circle mr-1"></i> <?php echo htmlspecialchars($skill['skill_name']); ?>
                  </span>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
          
          <!-- Event Timeline -->
          <div class="mt-8">
            <h4 class="text-lg font-semibold text-gray-800 mb-3 flex items-center">
              <i class="fas fa-hourglass-half text-blue-600 mr-2"></i> Event Timeline
            </h4>
            <div class="relative">
              <!-- Timeline line -->
              <div class="absolute h-full w-0.5 bg-blue-200 left-5 top-0"></div>
              
              <!-- Timeline items -->
              <div class="ml-12 relative pb-8">
                <div class="absolute -left-7 mt-1.5 w-3 h-3 rounded-full border-2 border-blue-600 bg-white"></div>
                <div class="text-sm">
                  <p class="font-medium text-blue-600">Created</p>
                  <p class="text-gray-500">Event was created and scheduled</p>
                </div>
              </div>
              
              <div class="ml-12 relative pb-8">
                <div class="absolute -left-7 mt-1.5 w-3 h-3 rounded-full border-2 <?php echo $event_status === 'upcoming' ? 'border-gray-400 bg-white' : 'border-blue-600 bg-blue-600'; ?>"></div>
                <div class="text-sm">
                  <p class="font-medium <?php echo $event_status === 'upcoming' ? 'text-gray-500' : 'text-blue-600'; ?>">Event Start</p>
                  <p class="text-gray-500"><?php echo date('F j, Y - g:i A', strtotime($event['event_date'] . ' ' . $event['start_time'])); ?></p>
                </div>
              </div>
              
              <div class="ml-12 relative">
                <div class="absolute -left-7 mt-1.5 w-3 h-3 rounded-full border-2 <?php echo $event_status === 'completed' ? 'border-blue-600 bg-blue-600' : 'border-gray-400 bg-white'; ?>"></div>
                <div class="text-sm">
                  <p class="font-medium <?php echo $event_status === 'completed' ? 'text-blue-600' : 'text-gray-500'; ?>">Event End</p>
                  <p class="text-gray-500"><?php echo date('F j, Y - g:i A', strtotime($event['event_date'] . ' ' . $event['end_time'])); ?></p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Volunteers List -->
      <div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
        <div class="bg-blue-600 text-white p-4 flex justify-between items-center">
          <h3 class="text-xl font-semibold flex items-center">
            <i class="fas fa-users mr-2"></i> Registered Volunteers
          </h3>
          <span class="bg-white text-blue-600 px-2 py-1 rounded-full text-sm font-medium">
            <?php echo $volunteer_count; ?> volunteers
          </span>
        </div>
        
        <div class="p-6">
          <?php if ($volunteer_count === 0): ?>
            <div class="text-center py-8">
              <div class="text-gray-400 mb-4">
                <i class="fas fa-user-slash text-5xl"></i>
              </div>
              <p class="text-gray-500">No volunteers have registered for this event yet.</p>
              <?php if ($event_status !== 'completed'): ?>
                <button onclick="document.getElementById('enroll-modal').classList.remove('hidden')" 
                        class="mt-4 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                  <i class="fas fa-user-plus mr-1"></i> Enroll Volunteer
                </button>
              <?php endif; ?>
            </div>
          <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <?php while ($volunteer = $volunteers->fetch_assoc()): ?>
                <?php
                // Get volunteer skills
                $skills_stmt = $db->prepare("SELECT s.skill_name FROM volunteer_skills vs JOIN skills s ON vs.skill_id = s.skill_id WHERE vs.volunteer_id = ?");
                $skills_stmt->bind_param("i", $volunteer['volunteer_id']);
                $skills_stmt->execute();
                $skills_result = $skills_stmt->get_result();
                $volunteer_skills = [];
                while ($skill = $skills_result->fetch_assoc()) {
                    $volunteer_skills[] = $skill['skill_name'];
                }
                ?>
                <div class="volunteer-card bg-white border rounded-lg shadow-sm hover:shadow-md transition-all overflow-hidden">
                  <div class="p-4 border-b">
                    <div class="flex items-center">
                      <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center">
                        <span class="text-blue-600 font-medium"><?php echo substr($volunteer['first_name'], 0, 1) . substr($volunteer['last_name'], 0, 1); ?></span>
                      </div>
                      <div class="ml-3">
                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($volunteer['first_name'] . ' ' . $volunteer['last_name']); ?></div>
                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($volunteer['email']); ?></div>
                      </div>
                    </div>
                  </div>
                  <div class="p-4">
                    <div class="mb-2">
                      <div class="text-xs text-gray-500">Phone</div>
                      <div class="text-sm"><?php echo htmlspecialchars($volunteer['phone']); ?></div>
                    </div>
                    <div class="mb-2">
                      <div class="text-xs text-gray-500">Skills</div>
                      <div class="flex flex-wrap gap-1 mt-1">
                        <?php foreach ($volunteer_skills as $skill): ?>
                          <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                            <?php echo htmlspecialchars($skill); ?>
                          </span>
                        <?php endforeach; ?>
                      </div>
                    </div>
                    <div class="mt-3 flex justify-between items-center">
                      <a href="view_volunteer.php?id=<?php echo $volunteer['volunteer_id']; ?>" class="text-blue-600 hover:text-blue-800 text-sm">
                        <i class="fas fa-eye mr-1"></i> View Profile
                      </a>
                      <form method="POST" action="remove_volunteer.php" onsubmit="return confirm('Are you sure you want to remove this volunteer from the event?');">
                        <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                        <input type="hidden" name="volunteer_id" value="<?php echo $volunteer['volunteer_id']; ?>">
                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm">
                          <i class="fas fa-user-minus mr-1"></i> Remove
                        </button>
                      </form>
                    </div>
                  </div>
                </div>
              <?php endwhile; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </main>
  </div>

  <!-- Enroll Volunteer Modal -->
  <div id="enroll-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
    <div class="relative mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
      <div class="flex justify-between items-center mb-4 border-b pb-3">
        <h3 class="text-lg font-semibold text-gray-800 flex items-center">
          <i class="fas fa-user-plus text-blue-600 mr-2"></i> Enroll Volunteer
        </h3>
        <button onclick="document.getElementById('enroll-modal').classList.add('hidden')" class="text-gray-500 hover:text-gray-700">
          <i class="fas fa-times"></i>
        </button>
      </div>
      
      <?php if (isset($available_error)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
          <p><?php echo htmlspecialchars($available_error); ?></p>
        </div>
      <?php endif; ?>
      
      <?php if ($available_volunteers && $available_volunteers->num_rows > 0): ?>
        <form method="POST">
          <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
          <div class="mb-4">
            <label class="block text-gray-700 text-sm font-medium mb-2" for="volunteer_id">
              <i class="fas fa-user mr-1"></i> Select Volunteer
            </label>
            <select name="volunteer_id" id="volunteer_id" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
              <?php while ($volunteer = $available_volunteers->fetch_assoc()): ?>
                <option value="<?php echo $volunteer['volunteer_id']; ?>">
                  <?php echo htmlspecialchars($volunteer['first_name'] . ' ' . $volunteer['last_name'] . ' (' . $volunteer['email'] . ')'); ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>
          <?php if (!empty($required_skills)): ?>
            <div class="mb-4 text-sm text-gray-600 bg-blue-50 p-3 rounded-lg">
              <p class="font-medium text-blue-700 mb-1">Required skills for this event:</p>
              <div class="flex flex-wrap gap-1 mt-1">
                <?php foreach ($required_skills as $skill): ?>
                  <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                    <?php echo htmlspecialchars($skill['skill_name']); ?>
                  </span>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
          <div class="flex justify-end space-x-3 mt-6">
            <button type="button" onclick="document.getElementById('enroll-modal').classList.add('hidden')" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 transition-colors">
              Cancel
            </button>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors flex items-center">
              <i class="fas fa-user-plus mr-1"></i> Enroll
            </button>
          </div>
        </form>
      <?php else: ?>
        <div class="text-center py-6">
          <div class="text-gray-400 mb-4">
            <i class="fas fa-user-slash text-5xl"></i>
          </div>
          <p class="text-gray-600 mb-4">
            <?php if (!empty($required_skills)): ?>
              No available volunteers with the required skills.
            <?php else: ?>
              No available volunteers to enroll.
            <?php endif; ?>
          </p>
          <button onclick="document.getElementById('enroll-modal').classList.add('hidden')" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 transition-colors">
            Close
          </button>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
    $(document).ready(function() {
      // Initialize Select2
      $('#volunteer_id').select2({
        placeholder: "Select a volunteer",
        dropdownParent: $('#enroll-modal')
      });
      
      // Mobile sidebar toggle
      $('#sidebar-toggle').click(function() {
        $('#mobile-sidebar').removeClass('hidden');
        $('#mobile-sidebar > div').removeClass('-translate-x-full');
      });
      
      $('#close-sidebar').click(function() {
        $('#mobile-sidebar').addClass('hidden');
        $('#mobile-sidebar > div').addClass('-translate-x-full');
      });
      
      // Close modal when clicking outside
      $('#enroll-modal').click(function(e) {
        if (e.target === this) {
          $(this).addClass('hidden');
        }
      });
    });
  </script>
</body>
</html>