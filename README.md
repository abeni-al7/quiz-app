# Quiz Application

## Introduction

This is a web-based quiz application that allows administrators to create and manage quizzes, and students to take quizzes and view their results. The application features user authentication, role-based access control, and a leaderboard.

## Features

*   **User Authentication:** Secure login and registration for students and administrators using JWT.
*   **Role-Based Access:**
    *   **Admin:**
        *   Manage subjects (create, edit, delete).
        *   Manage quizzes (create, edit, delete).
        *   Manage questions within quizzes (create, edit, delete). Supports multiple-choice and true/false questions.
        *   View and grade student quiz attempts.
        *   View a site-wide leaderboard.
    *   **Student:**
        *   Register and log in.
        *   View available quizzes filtered by subject.
        *   Take quizzes.
        *   View their own quiz attempt history and scores.
        *   View a site-wide leaderboard.
*   **Dynamic Quiz Taking:** Students can navigate through questions, submit answers, and receive immediate feedback on auto-graded questions.
*   **Leaderboard:** Displays top-scoring students.

## Prerequisites

*   A web server (e.g., Apache, Nginx) with PHP support.
*   PHP version 8.0 or higher.
*   MySQL database server.
*   [Composer](https://getcomposer.org/) for managing PHP dependencies.

## Setup

1.  **Clone the Repository:**
    ```sh
    git clone <repository-url>
    cd quiz-app
    ```
2.  **Configure Web Server:**
    Configure your web server (Apache, Nginx, etc.) to serve the project from its root directory.

3.  **Create Database:**
    Create a MySQL database. The default database name used in the application is `quiz_app`.

4.  **Database Credentials:**
    Update the database connection details (host, database name, username, password) in the `includes/db.php` file:
    ```php
    $host = 'your_db_host';
    $db   = 'your_db_name';
    $user = 'your_db_user';
    $pass = 'your_db_password';
    ```
5.  **Install Dependencies:**
    Run Composer to install the required PHP libraries:
    ```sh
    composer install
    ```
6.  **Import Database Schema:**
    Import the database schema from `schema.sql` into your MySQL database. This will create the necessary tables and views.
    ```sh
    mysql -u your_db_user -p your_db_name < schema.sql
    ```

## Usage

1.  **Access the Application:**
    Open your web browser and navigate to the application's URL.
    *   To register a new account, go to `register.html`.
    *   To log in, go to `login.html`.

2.  **Admin Dashboard:**
    After an admin logs in, they will be redirected to `admin/index.html`. From here, they can manage subjects, quizzes, questions, view attempts, and the leaderboard.

3.  **Student Dashboard:**
    After a student logs in, they will be redirected to `student/index.html`. From here, they can start new quizzes, view their past attempts, and see the leaderboard.

    *   **Taking a Quiz:** Students can select a subject and then a quiz to start an attempt via `student/attempt.html`.
    *   **Viewing Leaderboard:** Students can view the leaderboard at `student/leaderboard.html`.

## API Endpoints

The application uses a set of PHP scripts in the `/api/` directory to handle data operations:
*   `api/auth/login.php`: Handles user login.
*   `api/auth/register.php`: Handles new user registration.
*   `api/subjects.php`: Manages subjects (CRUD).
*   `api/quizzes.php`: Manages quizzes (CRUD).
*   `api/questions.php`: Manages questions for quizzes (CRUD).
*   `api/student_quizzes.php`: Manages student quiz attempts (starting, submitting, viewing).
*   `api/answers.php`: Handles submission and retrieval of student answers, and grading by admins.
*   `api/leaderboard.php`: Retrieves leaderboard data.
*   `api/admin/attempts.php`: Retrieves all student attempts for admin view.

All API endpoints require JWT authentication, passed via the `Authorization: Bearer <token>` header.
The JWT generation and validation logic can be found in `includes/jwt.php`.
Authentication middleware is in `includes/auth_middleware.php`.