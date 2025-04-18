<?php
session_start();
date_default_timezone_set('Asia/Kolkata');

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
    $volunteer_id = intval($_POST['volunteer_id']);
    $event_id = intval($_POST['event_id']);
    $certificate_type = $_POST['certificate_type'];
    
    // Get volunteer and event details
    $volunteer = $db->query("SELECT * FROM volunteers WHERE volunteer_id = $volunteer_id")->fetch_assoc();
    $event = $db->query("SELECT * FROM events WHERE event_id = $event_id AND admin_id = {$_SESSION['admin_id']}")->fetch_assoc();
    
    if (!$volunteer || !$event) {
        $_SESSION['error_message'] = "Invalid volunteer or event selected";
        header('Location: certificates.php');
        exit();
    }
    
    // Generate certificate content (HTML template)
    $certificate_content = "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Certificate of {$certificate_type}</title>
            <style>
                body { 
                    font-family: 'Montserrat', Arial, sans-serif; 
                    text-align: center; 
                    padding: 50px;
                    background-color: #f9f9f9;
                    color: #333;
                }
                .certificate { 
                    border: 15px solid #1a365d; 
                    padding: 50px; 
                    max-width: 800px; 
                    margin: 0 auto;
                    background-color: #fff;
                    background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZmZmZmZmIj48L3JlY3Q+PGNpcmNsZSBjeD0iNTAiIGN5PSI1MCIgcj0iNDAiIHN0cm9rZT0iIzFhMzY1ZCIgc3Ryb2tlLXdpZHRoPSIwLjUiIHN0cm9rZS1vcGFjaXR5PSIwLjEiIGZpbGw9Im5vbmUiPjwvY2lyY2xlPjwvc3ZnPg==');
                    background-repeat: repeat;
                    position: relative;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                }
                .certificate::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMDAiIGhlaWdodD0iMjAwIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSJub25lIj48L3JlY3Q+PHBhdGggZD0iTTAgMCBMIDIwMCAyMDAiIHN0cm9rZT0iIzFhMzY1ZCIgc3Ryb2tlLXdpZHRoPSIwLjUiIHN0cm9rZS1vcGFjaXR5PSIwLjA1Ij48L3BhdGg+PHBhdGggZD0iTTIwMCAwIEwgMCAyMDAiIHN0cm9rZT0iIzFhMzY1ZCIgc3Ryb2tlLXdpZHRoPSIwLjUiIHN0cm9rZS1vcGFjaXR5PSIwLjA1Ij48L3BhdGg+PC9zdmc+');
                    opacity: 0.1;
                    z-index: 0;
                }
                .certificate-content {
                    position: relative;
                    z-index: 1;
                }
                .logo {
                    width: 120px;
                    height: auto;
                    margin-bottom: 20px;
                }
                .certificate-title { 
                    color: #1a365d; 
                    font-size: 36px; 
                    margin-bottom: 30px;
                    font-weight: 700;
                    text-transform: uppercase;
                    letter-spacing: 2px;
                    border-bottom: 2px solid #1a365d;
                    padding-bottom: 10px;
                    display: inline-block;
                }
                .recipient { 
                    color: #2c5282; 
                    font-size: 28px; 
                    margin-bottom: 40px;
                    font-weight: 600;
                }
                .description {
                    font-size: 18px; 
                    line-height: 1.6;
                    margin-bottom: 30px;
                }
                .event-name {
                    font-size: 22px;
                    font-weight: 600;
                    color: #2c5282;
                    margin: 15px 0;
                }
                .signature { 
                    margin-top: 60px;
                    display: flex;
                    justify-content: space-around;
                }
                .signature-item {
                    text-align: center;
                }
                .signature-line {
                    width: 200px;
                    border-bottom: 2px solid #1a365d;
                    margin: 0 auto 10px;
                }
                .date { 
                    margin-top: 40px;
                    font-style: italic;
                    color: #666;
                }
                .certificate-id {
                    position: absolute;
                    bottom: 20px;
                    right: 20px;
                    font-size: 12px;
                    color: #666;
                }
                .seal {
                    position: absolute;
                    bottom: 40px;
                    left: 40px;
                    width: 100px;
                    height: 100px;
                    background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIj48Y2lyY2xlIGN4PSI1MCIgY3k9IjUwIiByPSI0NSIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjMWEzNjVkIiBzdHJva2Utd2lkdGg9IjIiPjwvY2lyY2xlPjxjaXJjbGUgY3g9IjUwIiBjeT0iNTAiIHI9IjM1IiBmaWxsPSJub25lIiBzdHJva2U9IiMxYTM2NWQiIHN0cm9rZS13aWR0aD0iMiI+PC9jaXJjbGU+PHRleHQgeD0iNTAiIHk9IjUwIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBhbGlnbm1lbnQtYmFzZWxpbmU9Im1pZGRsZSIgZmlsbD0iIzFhMzY1ZCIgZm9udC1zaXplPSIxMCIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXdlaWdodD0iYm9sZCI+T0ZGSUNJQUw8L3RleHQ+PHRleHQgeD0iNTAiIHk9IjY1IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBhbGlnbm1lbnQtYmFzZWxpbmU9Im1pZGRsZSIgZmlsbD0iIzFhMzY1ZCIgZm9udC1zaXplPSIxMCIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXdlaWdodD0iYm9sZCI+U0VBTDwvdGV4dD48L3N2Zz4=');
                    background-repeat: no-repeat;
                    opacity: 0.7;
                }
            </style>
            <link href='https://fonts.googleapis.com/css?family=Montserrat:400,500,600,700' rel='stylesheet'>
        </head>
        <body>
            <div class='certificate'>
                <div class='certificate-content'>
                    <div class='certificate-title'>Certificate of {$certificate_type}</div>
                    <p class='description'>This is to certify that</p>
                    <p class='recipient'>{$volunteer['first_name']} {$volunteer['last_name']}</p>
                    <p class='description'>has successfully participated and contributed to</p>
                    <p class='event-name'>{$event['event_name']}</p>
                    <p class='description'>held on {$event['event_date']} at {$event['location']}</p>
                    
                    <div class='signature'>
                        <div class='signature-item'>
                            <div class='signature-line'></div>
                            <p>{$_SESSION['full_name']}</p>
                            <p>Event Coordinator</p>
                        </div>
                        <div class='signature-item'>
                            <div class='signature-line'></div>
                            <p>{$_SESSION['organization']}</p>
                            <p>Organization</p>
                        </div>
                    </div>
                    
                    <div class='date'>
                        <p>Issued on: " . date('F j, Y') . "</p>
                    </div>
                    
                    <div class='seal'></div>
                    <div class='certificate-id'>Certificate ID: VMS-" . time() . "-$volunteer_id-$event_id</div>
                </div>
            </div>
        </body>
        </html>
    ";
    
    // Insert certificate into database
    $stmt = $db->prepare("INSERT INTO certificates (volunteer_id, event_id, certificate_type, certificate_content) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $volunteer_id, $event_id, $certificate_type, $certificate_content);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Certificate generated successfully!";
    } else {
        $_SESSION['error_message'] = "Error generating certificate: " . $db->error;
    }
    
    header('Location: certificates.php');
    exit();
}

// Get volunteers and events for dropdowns
$volunteers = $db->query("SELECT * FROM volunteers ORDER BY first_name, last_name");
$events = $db->query("SELECT * FROM events WHERE admin_id = {$_SESSION['admin_id']} ORDER BY event_date DESC");

// Check if regenerating for specific volunteer and event
$regenerate_volunteer_id = isset($_GET['volunteer_id']) ? intval($_GET['volunteer_id']) : 0;
$regenerate_event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;

$db->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Certificate | VolunteerHub</title>
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
        .certificate-preview {
            transition: all 0.3s ease;
        }
        .certificate-preview:hover {
            transform: scale(1.02);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-gradient-to-r from-blue-700 to-blue-500 text-white p-4 sticky top-0 z-50 shadow-md">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold flex items-center">
                <i class="fas fa-certificate mr-2"></i>
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

    <!-- Main Content -->
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
                    <a href="events.php" class="flex items-center py-2 px-4 mt-2 text-gray-600 hover:bg-blue-50 hover:text-blue-700 rounded-lg transition-colors">
                        <i class="fas fa-calendar-alt w-5 mr-2"></i> Events
                    </a>
                    <a href="certificates.php" class="flex items-center py-2 px-4 mt-2 bg-blue-50 text-blue-700 rounded-lg font-medium">
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
                        <a href="events.php" class="flex items-center py-2 px-4 mt-2 text-gray-600 hover:bg-blue-50 hover:text-blue-700 rounded-lg transition-colors">
                            <i class="fas fa-calendar-alt w-5 mr-2"></i> Events
                        </a>
                        <a href="certificates.php" class="flex items-center py-2 px-4 mt-2 bg-blue-50 text-blue-700 rounded-lg font-medium">
                            <i class="fas fa-certificate w-5 mr-2"></i> Certificates
                        </a>
                        <a href="reports.php" class="flex items-center py-2 px-4 mt-2 text-gray-600 hover:bg-blue-50 hover:text-blue-700 rounded-lg transition-colors">
                            <i class="fas fa-chart-bar w-5 mr-2"></i> Reports
                        </a>
                    </nav>
                </div>
            </div>
        </div>
        
        <main class="flex-1 p-6">
            <div class="max-w-4xl mx-auto">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 flex items-center">
                        <i class="fas fa-certificate text-blue-600 mr-2"></i>
                        Generate Certificate
                    </h2>
                </div>
                
                <!-- Certificate Generator Card -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-6">
                        <form method="POST" id="certificate-form">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="volunteer_id" class="block text-gray-700 font-medium mb-2">
                                        <i class="fas fa-user mr-1"></i> Select Volunteer
                                    </label>
                                    <select id="volunteer_id" name="volunteer_id" class="volunteer-select w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                        <option value="">Select a volunteer</option>
                                        <?php while ($volunteer = $volunteers->fetch_assoc()): ?>
                                            <option value="<?php echo $volunteer['volunteer_id']; ?>" <?php echo $volunteer['volunteer_id'] == $regenerate_volunteer_id ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($volunteer['first_name'] . ' ' . $volunteer['last_name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                
                                <div>
                                    <label for="event_id" class="block text-gray-700 font-medium mb-2">
                                        <i class="fas fa-calendar-alt mr-1"></i> Select Event
                                    </label>
                                    <select id="event_id" name="event_id" class="event-select w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                        <option value="">Select an event</option>
                                        <?php while ($event = $events->fetch_assoc()): ?>
                                            <option value="<?php echo $event['event_id']; ?>" <?php echo $event['event_id'] == $regenerate_event_id ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($event['event_name'] . ' (' . date('M j, Y', strtotime($event['event_date'])) . ')'); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mt-6">
                                <label for="certificate_type" class="block text-gray-700 font-medium mb-2">
                                    <i class="fas fa-award mr-1"></i> Certificate Type
                                </label>
                                <select id="certificate_type" name="certificate_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                    <option value="">Select a type</option>
                                    <option value="Participation">Participation</option>
                                    <option value="Appreciation">Appreciation</option>
                                    <option value="Achievement">Achievement</option>
                                    <option value="Excellence">Excellence</option>
                                    <option value="Recognition">Recognition</option>
                                </select>
                            </div>
                            
                            <!-- Certificate Preview -->
                            <div class="mt-8 border rounded-lg p-4 bg-gray-50 hidden" id="certificate-preview-container">
                                <h3 class="text-lg font-semibold text-gray-700 mb-4">Certificate Preview</h3>
                                <div class="certificate-preview bg-white border-4 border-blue-700 p-8 rounded-lg shadow-lg text-center">
                                    <div class="text-2xl font-bold text-blue-700 uppercase mb-4" id="preview-title">Certificate of <span id="preview-type">Participation</span></div>
                                    <p class="text-gray-600 mb-2">This is to certify that</p>
                                    <p class="text-xl font-semibold text-blue-600 mb-4" id="preview-name">Volunteer Name</p>
                                    <p class="text-gray-600 mb-2">has successfully participated in</p>
                                    <p class="text-lg font-medium text-blue-600 mb-4" id="preview-event">Event Name</p>
                                    <p class="text-gray-600 mb-2">held on <span id="preview-date">Event Date</span></p>
                                    <div class="mt-8 text-sm text-gray-500">
                                        <p>Issued by <?php echo htmlspecialchars($_SESSION['organization']); ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex justify-end space-x-4 mt-8">
                                <a href="certificates.php" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors flex items-center">
                                    <i class="fas fa-arrow-left mr-1"></i> Back
                                </a>
                                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors flex items-center">
                                    <i class="fas fa-certificate mr-1"></i> Generate Certificate
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Certificate Templates -->
                <div class="mt-8">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Certificate Templates</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow cursor-pointer template-card" data-type="Participation">
                            <div class="h-40 bg-gradient-to-r from-blue-500 to-blue-600 p-4 flex items-center justify-center">
                                <div class="text-white text-center">
                                    <i class="fas fa-certificate text-4xl mb-2"></i>
                                    <h4 class="font-semibold">Participation</h4>
                                </div>
                            </div>
                            <div class="p-4">
                                <p class="text-sm text-gray-600">For volunteers who participated in an event</p>
                            </div>
                        </div>
                        
                        <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow cursor-pointer template-card" data-type="Appreciation">
                            <div class="h-40 bg-gradient-to-r from-green-500 to-green-600 p-4 flex items-center justify-center">
                                <div class="text-white text-center">
                                    <i class="fas fa-award text-4xl mb-2"></i>
                                    <h4 class="font-semibold">Appreciation</h4>
                                </div>
                            </div>
                            <div class="p-4">
                                <p class="text-sm text-gray-600">To show appreciation for volunteer contributions</p>
                            </div>
                        </div>
                        
                        <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow cursor-pointer template-card" data-type="Achievement">
                            <div class="h-40 bg-gradient-to-r from-purple-500 to-purple-600 p-4 flex items-center justify-center">
                                <div class="text-white text-center">
                                    <i class="fas fa-trophy text-4xl mb-2"></i>
                                    <h4 class="font-semibold">Achievement</h4>
                                </div>
                            </div>
                            <div class="p-4">
                                <p class="text-sm text-gray-600">For volunteers who achieved specific goals</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('.volunteer-select, .event-select').select2({
                placeholder: "Select an option",
                allowClear: true
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
            
            // Update certificate preview when form fields change
            $('#volunteer_id, #event_id, #certificate_type').change(function() {
                updateCertificatePreview();
            });
            
            // Template card selection
            $('.template-card').click(function() {
                const type = $(this).data('type');
                $('#certificate_type').val(type).trigger('change');
                
                // Highlight selected template
                $('.template-card').removeClass('ring-2 ring-blue-500');
                $(this).addClass('ring-2 ring-blue-500');
            });
            
            // Function to update certificate preview
            function updateCertificatePreview() {
                const volunteerId = $('#volunteer_id').val();
                const eventId = $('#event_id').val();
                const certificateType = $('#certificate_type').val();
                
                if (volunteerId && eventId && certificateType) {
                    // Show preview container
                    $('#certificate-preview-container').removeClass('hidden');
                    
                    // Update preview content
                    $('#preview-type').text(certificateType);
                    $('#preview-name').text($('#volunteer_id option:selected').text());
                    $('#preview-event').text($('#event_id option:selected').text().split('(')[0].trim());
                    $('#preview-date').text($('#event_id option:selected').text().match(/$$(.*?)$$/)[1]);
                }
            }
            
            // Form validation
            $('#certificate-form').submit(function(e) {
                const volunteerId = $('#volunteer_id').val();
                const eventId = $('#event_id').val();
                const certificateType = $('#certificate_type').val();
                
                if (!volunteerId || !eventId || !certificateType) {
                    e.preventDefault();
                    alert('Please fill in all required fields');
                    return false;
                }
                
                return true;
            });
        });
    </script>
</body>
</html>