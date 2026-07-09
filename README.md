# Sistem Kampus (Campus Information System)

This project is a Campus Information System built with a Laravel (PHP) backend and a React (JavaScript) frontend.

## Project Structure

- `backend/`: Contains the Laravel API for the system.
- `frontend/`: Contains the React application for the user interface.

## Getting Started

### Backend Setup (Laravel)

1.  Navigate to the `backend` directory:
    ```bash
    cd backend
    ```
2.  Install Composer dependencies:
    ```bash
    composer install
    ```
3.  Copy the `.env.example` file and create your `.env` file:
    ```bash
    cp .env.example .env
    ```
4.  Generate an application key:
    ```bash
    php artisan key:generate
    ```
5.  Configure your database in the `.env` file.
6.  Run database migrations:
    ```bash
    php artisan migrate
    ```
7.  Seed the database (optional, for initial data):
    ```bash
    php artisan db:seed
    ```
8.  Start the Laravel development server:
    ```bash
    php artisan serve
    ```

### Frontend Setup (React)

1.  Navigate to the `frontend` directory:
    ```bash
    cd frontend
    ```
2.  Install Node.js dependencies:
    ```bash
    npm install
    ```
3.  Start the React development server:
    ```bash
    npm run dev
    ```

## Demo Credentials

For testing purposes, the following demo accounts are seeded in the database:

| Role      | Email                  | Password      |
| --------- | ---------------------- | ------------- |
| Admin     | `admin@wdu.ac.id`      | `password123` |
| Dosen     | `dosen1@wdu.ac.id`     | `password123` |
| Mahasiswa | `mahasiswa1@wdu.ac.id` | `password123` |

## Features

- **Authentication & Authorization:** User login with role-based access control (Admin, Dosen, Mahasiswa).
- **Dynamic Dashboards:** Separate dashboards for Admin, Dosen, and Mahasiswa roles with relevant information.
- **Student Payment Management:** (Admin/Dosen) View, add, and manage student payments.
- **Interactive UI:** Clickable buttons with hover effects for better user experience.

## Screenshots

### Login Page

![Login Page](".\frontend\src\assets\Login.png")
_The login page for authenticating users._

### Admin Dashboard

![Admin Dashboard]("./frontend/src/assets/tampilan%20panel%20admin.png")
_Dashboard for administrators with student payment management._

## Recent Changes & Improvements

- **Backend Reversion:** Removed incorrect Blade-based dashboard implementation.
- **Frontend Refactor:** Implemented React components for dynamic dashboards, including `Dashboard.jsx`, `StudentProfileCard.jsx`, `PaymentTable.jsx`, `AddPaymentForm.jsx`.
- **API Integration:** Established communication between React components and Laravel API for data fetching and submission.
- **Enhanced Styling:** Added a new `.btn-blue` CSS class for consistent button styling and hover effects across dashboards.
- **Improved Routing:** Corrected root route in `App.jsx` to ensure proper login redirection for unauthenticated users.

---

© 2026 Sistem Kampus. All rights reserved.
