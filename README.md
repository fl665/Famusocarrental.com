# Famusocarrental.com
Famuso Car Rental Services is a locally-owned automotive service provider based in Ndola, Zambia, strategically positioned in the heart of the Copperbelt Province's commercial and industrial hub. Operating as a dual-service enterprise, the company offers both car rental solutions and vehicle sales to serve business travelers, tourists, mining industry personnel, and local residents in Zambia's second-largest city. With its proximity to Simon Mwansa Kapwepwe International Airport and deep understanding of regional market dynamics, Famuso competes against international car rental chains by providing personalized service, competitive pricing (in a market averaging $80-100 USD daily rates), and flexible solutions tailored to local needs. The company's dual-revenue model optimizes fleet utilization while their community-focused approach, primarily marketed through social media platforms, emphasizes direct customer relationships and responsive service delivery in the competitive Zambian automotive services sector.
FAMUSO CAR RENTAL SYSTEM
=========================

Developed by: [Flemings Allan Kalimina, Obby and Mwachande Mbindo]
Technology Stack: PHP, MySQL, Tailwind CSS, Font Awesome
Last Updated: [12:56 PM]

DESCRIPTION
-----------
The Famuso Car Rental System is a web-based application designed to manage the operations of a car rental service. It supports multiple user roles including Admins, Agents, and Customers. The system allows customers to browse, book, and manage car rentals, while admins and agents can manage cars, bookings, and users.

FEATURES
--------
✓ Secure user authentication (Login/Register)  
✓ Role-based access control: Admin, Agent, Customer  
✓ Admin Dashboard for managing users, bookings, cars, and analytics  
✓ Agent Dashboard for viewing and managing assigned bookings  
✓ Customer Dashboard for booking cars and viewing booking status  
✓ Responsive layout with Tailwind CSS  
✓ Booking status tracking (pending, approved, rejected, completed)  
✓ Booking cost calculation  
✓ Logout and session handling  
✓ Real-time action icons and UI interactivity  

FOLDER STRUCTURE
----------------
/includes/         - Contains database connection and shared functions  
/dashboard/        - Contains dashboards for admin, agent, and customer  
/cars/             - Manage and display car listings  
/bookings/         - Manage bookings (view, approve, reject, delete)  
/assets/           - Images, CSS, and icon assets  
index.php          - Home/Landing page  
login.php          - Login form  
register.php       - Registration form  
logout.php         - Logout script  
README.txt         - System overview  
.sql               - SQL schema to import database  

USER ROLES
----------
1. ADMIN
   - Full access to the system
   - Can manage users, cars, and bookings
   - Access to analytics and system settings

2. AGENT
   - Can view and manage bookings assigned to them
   - Limited access to car listings
   - Cannot manage users or system settings

3. CUSTOMER
   - Can browse cars and create bookings
   - Can view and cancel personal bookings
   - No access to admin features

DATABASE STRUCTURE
------------------
- users
  (id, full_name, email, phone, password, role, created_at)

- cars
  (id, car_name, brand, type, model_year, status, price_per_day, image)

- bookings
  (id, user_id, car_id, booking_date, return_date, total_cost, status)

INSTALLATION & SETUP
--------------------
1. Clone or download the project into your XAMPP/htdocs directory.
2. Start Apache and MySQL from XAMPP control panel.
3. Import the SQL schema:
   - Open phpMyAdmin
   - Create a database (e.g., `car_rental_system`)
   - Import the provided SQL file into the database

4. Update database config in `includes/db.php`:
   ```php
   $conn = new mysqli("localhost", "root", "", "car_rental_system");
