BUDGETTRACKER (PHP + MySQL) - XAMPP SETUP

1. Copy this whole folder (BudgetTracker_PHP) into C:\xampp\htdocs\
   (folder name must have NO trailing space)
2. Open XAMPP Control Panel -> Start Apache and MySQL.
3. Open http://localhost/phpmyadmin -> Import -> choose database.sql -> Go.
4. Open http://localhost/BudgetTracker_PHP/ -> Register an account -> Login.

ADMIN ACCOUNT (created automatically on first page load):
   Username: admin     Password: Admin@12345   (change it in Settings > Change Password)
   Log in as admin -> Settings -> "Admin: Database Export" -> Export SQL Database.
   Only admins see that section; normal users do not.

If it still says "Database connection failed":
 - MySQL must be green/running in XAMPP.
 - Edit config/database.php: DB_PORT (3306 default; use 3307 if XAMPP shows that port),
   DB_USER = root, DB_PASS = blank by default.
 - Make sure database.sql was imported (database "budget_tracker" exists).

prototype/ contains the old localStorage-only HTML version (not connected to MySQL).
