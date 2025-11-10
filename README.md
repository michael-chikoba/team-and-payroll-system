# Payroll System

A comprehensive payroll management system built with Laravel and Vue.js.

## Features

- **Employee Management**: Add, edit, and manage employee information
- **Attendance Tracking**: Clock in/out functionality with time tracking
- **Leave Management**: Apply for leaves with approval workflow
- **Payroll Processing**: Automated payroll calculation and processing
- **Payslip Generation**: Generate and distribute digital payslips
- **Reporting**: Comprehensive reports for attendance, payroll, and leaves
- **Role-based Access**: Three user roles (Admin, Manager, Employee)
- **Audit Logging**: Track all system activities

## Tech Stack

### Backend
- Laravel 10
- MySQL
- Sanctum for API authentication
- DomPDF for PDF generation

### Frontend
- Vue.js 3
- Pinia for state management
- Vue Router 4
- Tailwind CSS for styling
- Chart.js for data visualization

## Installation

### Prerequisites
- PHP 8.1+
- MySQL 8.0+
- Node.js 16+
- Composer

### Backend Setup
1. Clone the repository
2. Run `composer install`
3. Copy `.env.example` to `.env` and configure database
4. Run `php artisan key:generate`
5. Run `php artisan migrate --seed`
6. Run `php artisan serve`

### Frontend Setup
1. Navigate to frontend directory
2. Run `npm install`
3. Copy `.env.example` to `.env`
4. Run `npm run dev`

## Default Users

After seeding:
- **Admin**: admin@payroll.com / password
- **Manager**: manager@payroll.com / password  
- **Employee**: alice@payroll.com / password

## API Documentation

The API follows RESTful conventions and uses JSON:API standards. All endpoints require authentication except:
- `POST /api/login`
- `POST /api/register`
- `POST /api/forgot-password`
- `POST /api/reset-password`

## Development

### Running Tests
```bash
# Backend tests
php artisan test

# Frontend tests
npm run test