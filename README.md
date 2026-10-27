# University Advising System (CSE370)

A web app for university course advising, built with PHP and MySQL for
**CSE370: Database Systems** at BRAC University (Spring 2025).

It covers the path from applicant to enrolled student: people apply online, admins review and
admit them, students pick course sections during advising, and faculty approve advising, advise
students and enter grades.

## Features by role

| Role | What they can do |
| --- | --- |
| **Applicant** | Register, submit an application with SSC/HSC grades, parent details and a supporting document (PDF, DOCX, JPG or PNG, up to 5 MB), and follow the application status |
| **Student** | Dashboard with personal details, advising approval, the course catalogue grouped into Core, General Education and Elective, and a weekly class schedule; advising panel to add or drop sections |
| **Faculty** | Approve students for advising, search for any student and add or drop sections for them, and enter grades |
| **Admin** | Review applicants (confirm payment, admit, wait-list or reject) and open their documents, add or remove faculty, add or remove courses, open sections and assign faculty to them |

Anyone can browse and search the course catalogue.

Adding a section checks that:

- a seat is free (the seat is taken in the same statement that checks it, so two students
  cannot both get the last one),
- the student has completed the prerequisites, either listed as completed or passed here
  with a grade,
- it does not overlap any class the student is taking now, and
- the student is not already enrolled in the course.

Dropping a section gives its seat back. A course that already has a grade cannot be dropped.

### Application status

| Status | Meaning | Set by |
| --- | --- | --- |
| 0 | Registered, no application yet | Registration |
| 1 | Application submitted, awaiting payment | Submitting the form |
| 2 | Payment confirmed | Admin |
| 3 | Accepted: a student account is created with the same email and password | Admin |
| 4 | Rejected: the applicant may apply again | Admin |
| 5 | Wait list | Admin |

## Security

- Every query uses prepared statements.
- Passwords are stored with `password_hash()`. Accounts from an older database that still hold
  plain-text passwords are accepted once at login and upgraded to a hash.
- `auth.php` checks the role on every page, so students cannot open faculty or admin pages, and
  add/drop requests can only change the logged-in student's own courses.
- Everything shown on a page is HTML-escaped.
- Uploads are checked by their real content type, not just the file name, and are saved under
  random names in `documents/`. That folder is closed to the web by `documents/.htaccess`;
  admins open uploads through `document.php`.
- The session cookie is `HttpOnly` and `SameSite=Lax`, and the session ID changes at login.

## Database

MySQL/MariaDB schema in `advising.sql` (12 tables), with sample data:

| Table | Holds |
| --- | --- |
| `users` | Accounts for students, faculty and admins (role 1, 2, 3) |
| `new`, `applications` | Applicant accounts with their status, and their submitted applications |
| `student` | Credits, CGPA, completed and current courses, advising approval |
| `faculty` | Faculty initials and courses offered |
| `course`, `section`, `room`, `slots` | Course catalogue with prerequisites, sections with seats, and class times and rooms |
| `enrollment` | Which student is in which section, with grades |
| `role`, `admin` | Role and admin reference tables |

Schedules come from joining `enrollment` with `room`.

## Built with

- PHP 8 (mysqli with prepared statements, `password_hash`, sessions, `finfo` for uploads)
- MySQL / MariaDB
- HTML and CSS

## Run it locally

1. Install [XAMPP](https://www.apachefriends.org/) (Apache, PHP, MariaDB).
2. Copy this folder into `xampp/htdocs/advising`.
3. In phpMyAdmin, create a database named `advising` and import `advising.sql`.
4. Open <http://localhost/advising/home.php>.

`DBconnect.php` uses XAMPP's defaults (`localhost`, `root`, no password, database `advising`).
To use other settings, set the `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS` or `DB_NAME`
environment variables.

If you imported an earlier version of `advising.sql`, import it again: the `new` table gained a
`status` column, and the password columns were widened to hold hashes.

Sample accounts (all use the password `password`):

| Role | Email | Notes |
| --- | --- | --- |
| Admin | `tania.aziz@gmail.com` | |
| Faculty | `sadia.akter@gmail.com` | |
| Student | `demo.student@example.com` | Has completed courses, so courses with prerequisites can be added |
| Student | `farhana.ahmed@gmail.com` | No completed courses yet; advising not approved |
| Applicant | `sultana.akter@gmail.com` | Registered, application not submitted |
| Applicant | `tanzim.hasan@gmail.com` | Application submitted, awaiting payment |

All sample people and accounts are made up.

## Limitations

This is a course project, not a production system:

- Forms rely on `SameSite` cookies against cross-site requests; there are no CSRF tokens.
- The session cookie is not marked `Secure`, because XAMPP serves plain HTTP. Turn that on
  when serving over HTTPS.
- `documents/.htaccess` protects uploads on Apache only. Other web servers need an equivalent
  rule.
