# University Advising System (CSE370)

A web app for university course advising, built with PHP and MySQL as a group project for
**CSE370: Database Systems** at BRAC University (Group 6, Lab Section 12, Spring 2025).

It covers the path from applicant to enrolled student: people apply online, admins admit them,
students pick course sections during advising, and faculty approve advising and enter grades.

## Features by role

| Role | What they can do |
| --- | --- |
| **Applicant** | Register, then submit an application with SSC/HSC grades, parent details and a supporting document; check application status |
| **Student** | Dashboard with personal details, the course catalogue grouped into Core, General Education and Elective, and their class schedule; advising panel to add or drop course sections, with live seat counts and a check against enrolling in the same course twice |
| **Faculty** | Approve students for advising, advise students, and enter or update grades |
| **Admin** | Admit applicants (creates their student account), add or remove faculty, add or remove courses and sections, and assign faculty to sections |

Anyone can browse and search the course catalogue.

## Database

MySQL/MariaDB schema in `advising.sql` (12 tables), with sample data:

| Table | Holds |
| --- | --- |
| `users` | Accounts for students, faculty and admins (role 1, 2, 3) |
| `new`, `applications` | Applicant accounts and their submitted applications |
| `student` | Credits, CGPA, completed and current courses, advising approval |
| `faculty` | Faculty initials and courses offered |
| `course`, `section`, `room`, `slots` | Course catalogue, sections with seats, and class times and rooms |
| `enrollment` | Which student is in which section, with grades |
| `role`, `admin` | Role and admin reference tables |

Adding a section decrements its seat count and dropping it increments it again; schedules come
from joining `enrollment`, `course` and `room`.

## Built with

- PHP 8 (mysqli, prepared statements for most queries, sessions)
- MySQL / MariaDB
- HTML and CSS

## Run it locally

1. Install [XAMPP](https://www.apachefriends.org/) (Apache, PHP, MariaDB).
2. Copy this folder into `xampp/htdocs/advising`.
3. In phpMyAdmin, create a database named `advising` and import `advising.sql`.
4. Create an empty `documents` folder next to `apply.php` for application uploads.
5. Open <http://localhost/advising/home.php>.

`DBconnect.php` uses XAMPP's defaults (`root`, no password); change it if your setup differs.

Sample accounts (all use the password `password`):

| Role | Email |
| --- | --- |
| Admin | `tania.aziz@gmail.com` |
| Faculty | `sadia.akter@gmail.com` |
| Student | `farhana.ahmed@gmail.com` |
| Applicant | `tanzim.hasan@gmail.com` |

All sample people and accounts are made up.

## Known limitations

This is a course project and has not been hardened for real use:

- `login.php` and `register.php` build SQL from form input instead of using prepared
  statements, so they are open to SQL injection.
- Passwords are stored in plain text.
- Pages check that someone is logged in, but not their role, so any logged-in user can open the
  admin and faculty pages.
- Uploaded documents are not checked for type or size.
- `home.php`, `faculty.php` and `register.php` link to Bootstrap and CSS files (`css/`, `js/`,
  `main.css`) that are not in this repository, so those pages appear unstyled.
- A few links point to pages that were never built: `courses.php`, `announce.php`, `enroll.php`
  and `wait_list.php`.
