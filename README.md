# 🎓 Dynamic Lecturer Content Management Profile

A modern, fully responsive academic portfolio website that allows lecturers to manage their professional profile, teaching experience, research projects, publications, supervision records, awards, appointments, and invited talks — all through a secure admin interface. Data is stored in **Supabase** (PostgreSQL) and the frontend is built with PHP, Bootstrap, and vanilla JavaScript.

[Website Live Demo](https://academicportfolio.kesug.com/index.php) 

## 🖼️ Website Screenshot
<img width="2879" height="1376" alt="image" src="https://github.com/user-attachments/assets/4eba0dd7-682c-490e-8cce-3084908ffd02" />
<img width="2879" height="1378" alt="image" src="https://github.com/user-attachments/assets/1f6b4495-0d31-4a5f-a005-e31494abf80a" />
<img width="2879" height="1367" alt="image" src="https://github.com/user-attachments/assets/02086ad4-ce8d-41fb-a48a-5bff42fdbc2f" />
<img width="2879" height="1373" alt="image" src="https://github.com/user-attachments/assets/0dd138e2-e824-459b-90bf-81428edf9b5d" />
<img width="2876" height="1374" alt="image" src="https://github.com/user-attachments/assets/653b9c35-d3cb-4b5b-93be-69620962a54a" />
<img width="2879" height="1377" alt="image" src="https://github.com/user-attachments/assets/a7887c79-a35a-4a17-93c0-626082ef42e8" />

## ✨ Features

- **Public Academic Portfolio** – Showcases your profile, research areas, teaching, publications, supervision, and other achievements.
- **Secure Admin Login** – Protected area to add/edit/delete all content.
- **Dynamic Content Management** – CRUD operations for:
  - Profile (bio, designation, institution, contact)
  - Teaching courses
  - Research projects
  - Publications (with DOI links)
  - Supervision (students, thesis titles, status)
  - Awards & recognition
  - Appointments
  - Invited talks
  - Research areas (tag cloud)
- **Image Upload** – Profile picture and hero background with cropping support (Cropper.js).
- **Responsive Design** – Works seamlessly on desktops, tablets, and mobile devices.
- **Filtering & Sorting** – Filter teaching by semester/institution, supervision by degree/status/program, publications by type/year/author, and more.
- **Smooth Scrolling & Active Navigation** – Section-based navigation with live highlighting.
- **Modern UI** – Custom CSS with gradient accents, card animations, and dark/light harmony.

## 🛠️ Tech Stack

- **Backend**: PHP 7.4+ (no framework, custom API calls)
- **Database**: Supabase (PostgreSQL)
- **Frontend**: HTML5, CSS3, JavaScript (ES6), Bootstrap 5, Bootstrap Icons
- **Additional Libraries**: Flatpickr (date picker), Cropper.js (image cropping)
- **Deployment**: Any PHP hosting (InfinityFree, Vercel via vercel-php, or traditional shared hosting)

## 📦 Installation & Setup

### Prerequisites
- PHP 7.4 or higher
- A Supabase account (free tier works)
- Web server (XAMPP for local development, or any online PHP host)

### Step 1: Clone the Repository
```bash
git clone https://github.com/LeoJ0515/Dynamic-Lecturer-Content-Management-Profile.git
cd Dynamic-Lecturer-Content-Management-Profile
```

### Step 2: Create the required tables in Supabase
```bash
Open your Supabase project → SQL Editor.

Run the SQL script from the file database.sql (included in this repository) to create all tables:
users, profile, teaching, research_projects, publications, supervision, awards, appointments, invited_talks, research_areas.

dont forget to insert your own email and password , is bottom there😊
and disable RLS
```

### Step 3: Get your Supabase API credentials
```bash
Go to Project Settings → API.
Copy the Project URL and the anon/public key.
```

### Step 4: Configure database.php & login.php
```bash
Open database.php and login.php replace the placeholders:
define('SUPABASE_URL', 'https://your-project.supabase.co');
define('SUPABASE_KEY', 'your-anon-key');

```

### Step 6: Run locally
```bash
Move the project folder to your web server's document root (e.g., C:\xampp\htdocs\LecturerProfile for XAMPP).
Start Apache.
Visit http://localhost/LecturerProfile/index.php
```


