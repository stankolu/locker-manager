# Locker Manager — Lycée Ermesinde

A web-based locker management system for managing student locker assignments, locks, rooms, and houses at Lycée Ermesinde.

## Features

- **Dashboard** — Overview of all stats (students, lockers, assignments)
- **Students** — Manage students with class and house assignments
- **Lockers** — Full CRUD with verified status, room assignment, building, and status tracking
- **Locks** — Manage physical locks with combinations
- **Classes** — Organize classes by house and level
- **Houses** — Color-coded house system
- **Rooms** — Track physical room locations
- **Assignments** — Year-based locker-to-student assignments with lock pairing
- **Map** — Visual locker map (requires `locker_map_layout.json`)
- **Import** — Bulk import from Excel/CSV files
- **Export** — PDF export of locker lists with all details
- **School Years** — Multi-year support with active year selection

## Requirements

- PHP 8.0+
- MySQL 8.0+
- PHP Extensions: pdo_mysql, mbstring, xml, gd, curl, zip
- Composer

## Installation

```bash
# Clone the repository
git clone https://github.com/stankolu/locker-manager.git
cd locker-manager

# Run the setup script
bash setup.sh

# Or manually:
# 1. Install PHP dependencies
composer install --no-dev

# 2. Create the database
mysql -u root < schema.sql

# 3. Configure database credentials
# Edit includes/config.php with your MySQL credentials

# 4. Start the development server
php -S 0.0.0.0:8080
```

## Configuration

Edit `includes/config.php` to set your database credentials:

```php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'locker_manager');
define('DB_USER', 'your_user');
define('DB_PASS', 'your_password');
```

## Project Structure

```
locker-manager/
├── assets/
│   ├── css/style.css
│   ├── js/app.js
│   └── img/
├── includes/
│   ├── config.php       # Database & app configuration
│   ├── db.php           # PDO database class
│   ├── helpers.php      # Utility functions
│   ├── header.php       # HTML header template
│   └── footer.php       # HTML footer template
├── index.php            # Dashboard
├── students.php         # Student management
├── lockers.php          # Locker management
├── locks.php            # Lock management
├── classes.php          # Class management
├── houses.php           # House management
├── rooms.php            # Room management
├── assignments.php      # Assignment management
├── map.php              # Visual locker map
├── import.php           # Data import
├── export.php           # PDF export
├── school_years.php     # School year management
├── schema.sql           # Database schema
├── setup.sh             # Automated setup script
├── composer.json        # PHP dependencies
└── README.md
```

## License

Internal use only — Lycée Ermesinde.
