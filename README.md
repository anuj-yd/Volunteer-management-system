📋 Volunteer Management System
A web-based platform designed for NGOs and organizations to efficiently manage volunteers, schedule events, track attendance, and generate certificates automatically.

🛠️ Technologies Used
Frontend: HTML, Tailwind CSS, JavaScript (DOM)

Backend: PHP

Database: MySQL

Additional Tools: XAMPP (for local server setup)

🌟 Features
✅ Admin Dashboard – View total volunteers, upcoming events, and completed activities.
✅ Volunteer Management – Add, edit, or remove volunteers with skill-based filtering.
✅ Event Scheduling – Create events, assign volunteers, and track attendance.
✅ Automated Certificates – Generate certificates instantly after event completion.
✅ Responsive Design – Works on desktop and mobile devices.

🚀 Installation & Setup
Prerequisites
XAMPP/WAMP (for PHP & MySQL)

Web Browser (Chrome, Firefox, Edge)

Steps
Clone the repository

sh
git clone https://github.com/yourusername/volunteer-management-system.git
Set up the database

Import the SQL file (database/volunteer_management.sql) into phpMyAdmin.

Configure database connection

Update config/db.php with your MySQL credentials.

Run the project

Move the project folder to htdocs (for XAMPP) or www (for WAMP).

Access via http://localhost/volunteer-management-system.

📂 Project Structure
volunteer-management-system/  
├── assets/            # CSS, JS, and images  
│   ├── css/           # Tailwind CSS  
│   ├── js/            # JavaScript (DOM)  
│   └── images/        # Logos, certificates, etc.  
├── config/            # Database config  
│   └── db.php  
├── database/          # SQL files  
│   └── volunteer_management.sql  
├── includes/          # PHP functions & helpers  
│   └── functions.php  
├── admin/             # Admin panel  
│   ├── dashboard.php  
│   ├── volunteers.php  
│   └── events.php  
├── certificates/      # Auto-generated certificates  
└── index.php          # Login page  


🤝 Contribution
Feel free to fork, improve, or suggest changes via Pull Requests.
