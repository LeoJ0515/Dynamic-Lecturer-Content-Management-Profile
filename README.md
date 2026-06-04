# 🎓 Dynamic Lecturer Content Management Profile

A modern, fully responsive academic portfolio website that allows lecturers to manage their professional profile, teaching experience, research projects, publications, supervision records, awards, appointments, and invited talks — all through a secure admin interface. Data is stored in **Supabase** (PostgreSQL) and the frontend is built with PHP, Bootstrap, and vanilla JavaScript.

![Portfolio Screenshot](screenshot.png)

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
