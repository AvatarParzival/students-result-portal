# StudentHub - Student Management System

[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](https://opensource.org/licenses/MIT)  [![PHP](https://img.shields.io/badge/PHP-7.4+-blue)](https://www.php.net/)  [![MySQL](https://img.shields.io/badge/MySQL-5.7+-orange)](https://www.mysql.com/)  [![TailwindCSS](https://img.shields.io/badge/TailwindCSS-v3-blueviolet)](https://tailwindcss.com/)   


**StudentHub** is a web-based Student Management System that provides a centralized platform for managing student and academic information. It includes separate dashboards for students and administrators, enabling efficient management of student profiles, results, and subjects.


---

## Features

### Student Features
- **Student Registration:** Register with personal and academic details  
- **Student Login:** Secure login using roll number and password  
- **Dashboard:** Personalized dashboard displaying academic results and performance metrics  
- **Profile Management:** Update personal information and profile pictures  
- **Result Viewing:** Access semester-wise results with grade calculations  
- **Performance Analytics:** View average marks, highest/lowest scores, and overall grades  

### Admin Features
- **Admin Login:** Secure admin authentication  
- **Dashboard Overview:** Statistics on total students, courses, and results  
- **Student Management:** View, edit, and delete student records  
- **Result Management:** Upload and manage student results by subject  
- **Subject Management:** Add and remove subjects organized by semester  
- **Bulk Operations:** Select and delete multiple results at once  

---

## Technology Stack
- **Frontend:** HTML5, Tailwind CSS, JavaScript  
- **Backend:** PHP  
- **Database:** MySQL  
- **Icons:** Font Awesome  
- **Fonts:** Google Fonts (Poppins)  

---

## File Structure

    
      student_management_system/
      ├── index.html # Landing page
      ├── index.php # Redirect to landing page
      ├── backend/ # PHP backend files
      │ ├── admin_dashboard.php # Admin control panel
      │ ├── admin_login.php # Admin login page
      │ ├── db.php # Database connection
      │ ├── delete_result.php # Result deletion handler
      │ ├── edit_profile.php # Student profile editor
      │ ├── edit_student.php # Admin student editor
      │ ├── get_semester_subjects.php # API for semester subjects
      │ ├── get_student_details.php # API for student details
      │ ├── get_student_subjects.php # API for student subjects
      │ ├── login.php # Student login page
      │ ├── logout.php # Logout handler
      │ ├── register.php # Student registration page
      │ ├── student_dashboard.php # Student dashboard
      │ └── subject_management.php # Subject management page
      ├── uploads/ # Directory for profile pictures
      └── student_management.sql # Database schema



---

## Database Schema
Main tables:
- **students:** Stores student personal and academic information  
- **users:** Stores login credentials for students and admins  
- **results:** Stores academic results with subject marks and grades  
- **semester_subjects:** Contains subjects organized by semester  
- **student_subjects:** Junction table for student-subject relationships  
- **semesters:** Semester information  

---

## Installation

### Prerequisites
- Web server (Apache recommended)  
- PHP 7.4 or higher  
- MySQL 5.7 or higher  

### Steps
1. **Setup Database:**  
   - Create a MySQL database named `student_management`  
   - Import the `student_management.sql` file  

2. **Configure Database Connection:**  
   Update `backend/db.php` with your database credentials:
   ```php
   $servername = "localhost";
   $username = "your_username";
   $password = "your_password";
   $dbname = "student_management";

3. **Upload Files:**
    Place all files in your web server's directory (e.g., htdocs or www)

4. **Set Permissions:**
   Ensure the uploads directory has write permissions for file uploads

5. **Access the Application:**
   Open your browser and navigate to the project directory
---
## Quick Start
1. Clone this repository:

        git clone https://github.com/yourusername/studenthub.git

2. Navigate to the project directory:

        cd studenthub
    
3. Set up the database and update credentials in backend/db.php
4. Start your Apache server (e.g., XAMPP, WAMP, MAMP)
5. Open browser at

        http://localhost/studenthub


## Default Admin Account
  - Username: admin 
  - Password: 123456  
---

## Usage
### For Students

- Register a new account or login with existing credentials
- Access the personalized dashboard to view results
- Edit profile information as needed

### For Administrators

- Login using admin credentials
- Access the admin dashboard to manage students and results
- Use the subject management page to add/remove course subjects
- Upload student results by selecting students and subjects

---

## Security Features

- Password hashing using bcrypt

- Session-based authentication

- Input validation and sanitization

- SQL injection prevention using prepared statements

- File upload restrictions (type and size)

- Role-based access control

---
## Browser Compatibility
### Compatible with all modern browsers including:
- Chrome (recommended)
- Firefox
- Safari
- Edge

---

## Customization

- Modify Tailwind CSS classes in HTML files to change appearance
- Update the database schema in student_management.sql for additional features
- Customize the subject list by semester in the admin panel

---

## Troubleshooting

- Database Connection Error: Check credentials in db.php
- File Upload Issues: Verify uploads directory permissions
- Session Problems: Ensure cookies are enabled in the browser
- Page Not Found: Check if mod_rewrite is enabled (if using pretty URLs)
---
