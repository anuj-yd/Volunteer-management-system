<?php
session_start();
date_default_timezone_set('Asia/Kolkata'); // Set to Indian time zone

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

// Current date and time in IST
$current_date = date('Y-m-d');
$current_time = date('H:i:s');

// Handle status filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'upcoming';

// Base query parts
$query_where = "WHERE admin_id = ? ";
$order_by = "ORDER BY event_date ASC, start_time ASC";

switch($status_filter) {
    case 'upcoming':
        $query_where .= "AND (event_date > ? OR (event_date = ? AND start_time > ?))";
        $params = [$current_date, $current_date, $current_time];
        break;
    case 'ongoing':
        $query_where .= "AND event_date = ? AND start_time <= ? AND end_time >= ?";
        $params = [$current_date, $current_time, $current_time];
        break;
    case 'completed':
        $query_where .= "AND (event_date < ? OR (event_date = ? AND end_time < ?))";
        $params = [$current_date, $current_date, $current_time];
        break;
    default:
        $query_where .= "AND (event_date > ? OR (event_date = ? AND start_time > ?))";
        $params = [$current_date, $current_date, $current_time];
}

$query = "SELECT * FROM events $query_where $order_by";
$stmt = $db->prepare($query);

// Bind parameters dynamically
$types = 's' . str_repeat('s', count($params));
$stmt->bind_param($types, $_SESSION['admin_id'], ...$params);
$stmt->execute();
$result = $stmt->get_result();
$events = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>VolunteerHub - Manage Events</title>
  <script src="https://cdn.tailwindcss.com"></script>
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
        </nav>
      </div>
    </aside>
    
    <!-- Main Content -->
    <main class="flex-1 p-6">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Manage Events</h2>
        <a href="add_event.php" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors">
          Add New Event
        </a>
      </div>
      
      <!-- Status Filter -->
      <div class="bg-white rounded-lg shadow-md p-4 mb-6">
        <div class="flex space-x-4">
          <a href="events.php?status=upcoming" class="px-4 py-2 rounded-lg <?php echo $status_filter === 'upcoming' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-800 hover:bg-gray-300'; ?> transition-colors">
            Upcoming
          </a>
          <a href="events.php?status=ongoing" class="px-4 py-2 rounded-lg <?php echo $status_filter === 'ongoing' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-800 hover:bg-gray-300'; ?> transition-colors">
            Ongoing
          </a>
          <a href="events.php?status=completed" class="px-4 py-2 rounded-lg <?php echo $status_filter === 'completed' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-800 hover:bg-gray-300'; ?> transition-colors">
            Completed
          </a>
        </div>
      </div>
      
      <!-- Events List -->
      <div class="space-y-4">
        <?php if (empty($events)): ?>
          <div class="bg-white rounded-lg shadow-md p-6 text-center">
            <p class="text-gray-500">No <?php echo $status_filter; ?> events found.</p>
          </div>
        <?php else: ?>
          <?php foreach ($events as $event): ?>
          <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-6">
              <div class="flex justify-between items-start">
                <div>
                  <h3 class="text-xl font-semibold text-indigo-700"><?php echo htmlspecialchars($event['event_name']); ?></h3>
                  <p class="text-gray-600 mt-1"><?php echo htmlspecialchars($event['location']); ?></p>
                </div>
                <div class="text-right">
                  <p class="font-medium"><?php echo date('M j, Y', strtotime($event['event_date'])); ?></p>
                  <p class="text-sm text-gray-500"><?php echo date('g:i A', strtotime($event['start_time'])) . ' - ' . date('g:i A', strtotime($event['end_time'])); ?></p>
                </div>
              </div>
              
              <p class="mt-3 text-gray-700"><?php echo htmlspecialchars($event['description']); ?></p>
              
              <div class="mt-4 flex justify-between items-center">
                <div>
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php 
                    $event_date = $event['event_date'];
                    $start_time = $event['start_time'];
                    $end_time = $event['end_time'];
                    
                    if ($event_date > $current_date || ($event_date == $current_date && $start_time > $current_time)) {
                        echo 'bg-blue-100 text-blue-800';
                    } elseif ($event_date == $current_date && $start_time <= $current_time && $end_time >= $current_time) {
                        echo 'bg-yellow-100 text-yellow-800';
                    } else {
                        echo 'bg-green-100 text-green-800';
                    }
                  ?>">
                    <?php 
                    if ($event_date > $current_date || ($event_date == $current_date && $start_time > $current_time)) {
                        echo "Upcoming";
                    } elseif ($event_date == $current_date && $start_time <= $current_time && $end_time >= $current_time) {
                        echo "Ongoing";
                    } else {
                        echo "Completed";
                    }
                    ?>
                  </span>
                  <?php if ($event['max_volunteers']): ?>
                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                      Max Volunteers: <?php echo $event['max_volunteers']; ?>
                    </span>
                  <?php endif; ?>
                </div>
                
                <div>
                  <a href="view_event.php?id=<?php echo $event['event_id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">View</a>
                  <a href="edit_event.php?id=<?php echo $event['event_id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">Edit</a>
                  <a href="delete_event.php?id=<?php echo $event['event_id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this event?')">Delete</a>
                </div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </main>
  </div>
</body>
</html>