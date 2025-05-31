# Quiz Application

## Introduction

This is a web-based quiz application using server-side rendered PHP (SSR) with MySQL (mysqli) and PHP sessions. Administrators can create and manage subjects, quizzes, and grading, while students can register, log in, take quizzes, and view their history and leaderboard—all without client-side REST APIs or JWT.

## Features

*   Server-side rendering (PHP) with mysqli and PHP sessions
*   Role-based access control:
    *   **Admin:**
        *   Manage subjects and quizzes (create, edit, delete)
        *   Manage questions within quizzes
        *   View and grade student quiz attempts
        *   View site-wide leaderboard
    *   **Student:**
        *   Register and log in
        *   Start and take quizzes
        *   View attempt history and scores
        *   View site-wide leaderboard
*   Automatic scoring for multiple-choice and true/false
*   Mobile-responsive UI with minimal JavaScript

## Prerequisites

*   PHP 8.0 or higher
*   MySQL database server
*   Composer for dependency management

## Setup

1.  **Clone the Repository:**
    ```sh
    git clone <repository-url>
    cd quiz-app
    ```
2.  **Configure Database:**
    Update `includes/db.php` with your MySQL credentials.
3.  **Install Dependencies:**
    ```sh
    composer install
    ```
4.  **Import Schema:**
    ```sh
    mysql -u your_db_user -p your_db_name < schema.sql
    ```

## Usage

1.  **Register:** Navigate to `/register.php` to create a student account.
2.  **Login:** Navigate to `/login.php` and enter your credentials.
3.  **Admin Dashboard:** After admin login, use `/admin/index.php` to manage subjects and quizzes.
4.  **Student Dashboard:** After student login, use `/student/index.php` to start quizzes and view past attempts.
5.  **Leaderboard:** Both roles can view leaderboard at `/student/leaderboard.php` or `/admin/index.php`.
6.  **Logout:** Click the Logout link or visit `/logout.php`.

## File Structure

*   `includes/` — Database and authentication middleware
*   `admin/` — Admin SSR pages (subjects, quizzes, attempts, grading)
*   `student/` — Student SSR pages (dashboard, attempt, leaderboard)
*   `login.php`, `register.php`, `logout.php` — Auth pages
*   `css/styles.css` — Stylesheet
*   `schema.sql` — Database schema and views

<!-- API endpoints and JS have been deprecated in this SSR version -->