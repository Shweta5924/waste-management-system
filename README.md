# Waste Segregation Monitoring System

## Prerequisites
- PHP installed (v8.0 or higher)
- MySQL Database (via XAMPP, WAMP, or standalone) enabled and running

## Quick Start (Windows)
1. **Start MySQL**: Open XAMPP Control Panel and start **MySQL**.
2. **Initialize Database**: 
   - Open a terminal in this folder.
   - Run: `php setup_db.php`
   - This creates the `waste_db` database and default users.
3. **Start Application**:
   - Run: `php -S localhost:8000`
4. **Access in Browser**:
   - Open [http://localhost:8000](http://localhost:8000)

## Default Credentials
**Admin:**
- Email: `admin@waste.com`
- Password: `password`

**Supervisor:**
- Email: `super1@waste.com`
- Password: `password`

## Project Structure
- `config/`: Database connection settings
- `assets/`: CSS and JS files
- `admin/`: Admin dashboard files
- `staff/`: Staff entry forms
- `index.php`: Main landing page
