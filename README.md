# HELIOS Fashion Landing Page

**Tagline:** Timeless Elegance • Wear Your Light

HELIOS is a responsive fashion landing page for men's and women's clothing, built with HTML, CSS, JavaScript, PHP, and MySQL.

## Features
- Premium brown, cream, and gold design
- Responsive layout with sticky navigation
- Men's and women's product categories
- Product images, prices, and descriptions
- WhatsApp-based ordering and customer contact
- Admin login and product management (planned)
- Add, edit, preview, and delete products (planned)

## Tech Stack
- HTML5, CSS3, JavaScript
- PHP
- MySQL
- XAMPP

## Project Structure

```text
helios/
├── index.php
├── README.md
├── admin/
│   ├── login.php
│   ├── dashboard.php
│   ├── add-product.php
│   ├── edit-product.php
│   ├── preview-product.php
│   ├── delete-product.php
│   └── logout.php
├── config/
│   └── db.php
├── assets/
│   ├── css/
│   │   ├── style.css
│   │   └── admin.css
│   ├── js/
│   │   └── script.js
│   └── images/
│       ├── products/
│       ├── hero/
│       └── about/
└── includes/
    ├── header.php
    └── footer.php
```

*Note: Some admin files and features are planned and may not have been created yet.*

## Local Setup
1. Install XAMPP and start Apache and MySQL.
2. Place the project in `C:\xampp\htdocs\helios\`.
3. Create a MySQL database named `helios_db`.
4. Create the `products` and `admins` tables.
5. Configure `config/db.php` with your database credentials.
6. Open `http://localhost/helios/` in your browser.

## Project Status
Currently in development. The database connection and initial landing page structure are set up. Product management and admin functionality are upcoming.

## Contact
- **Email:** contracthelios@gmail.com
- **Instagram:** [HELIOS Instagram](https://www.instagram.com/mdmusaaddekshahriar/)
- **Facebook:** [HELIOS Facebook Page](https://www.facebook.com/profile.php?id=61595611768284)

## License
All rights reserved. Contact the project owner for permission to reuse this project.
