# Nang Chicken Market

Nang Chicken Market is a PHP/MySQL online chicken market with separate customer and staff portals. Customers can browse chicken products, manage a cart, place orders, view receipts, and manage their account. Staff can manage inventory, sales records, and staff records through an enterprise portal.

## Features

### Customer portal

- Customer registration and email/password login
- Product browsing for chicken breast, wings, thighs, and drumsticks
- Shopping cart with quantity updates and item removal
- Checkout with Cash on Delivery or card payment selection
- Stock availability checks during checkout
- Order history with search, status, and date filters
- Receipt display after a successful checkout
- Profile and password management
- About and contact pages

### Staff portal

- Staff username/password login
- Inventory listing and stock price/quantity updates
- Sales search and filtering by status and date
- Staff assignment to pending receipts
- Pending/completed order status updates
- Staff directory search
- Add, edit, and delete staff records

## Technology

- PHP 8.2+
- MariaDB/MySQL
- PDO for database access
- HTML, CSS, and vanilla JavaScript
- Font Awesome and SweetAlert2 loaded from public CDNs

## Interface and themes

- Every customer, staff, authentication, and receipt page uses `css/style.css`.
- The shared stylesheet contains reusable design tokens, components, responsive layouts, and print styles.
- Light and dark modes are controlled by `js/theme.js`.
- The selected theme is remembered in the browser. When no preference has been saved, the website follows the device theme.
- The responsive glass-style interface uses the Nang red and green brand palette across customer and staff pages.
- `images/chicken_pattern_bg.png` provides a subtle shared background between the header and footer, while the home hero keeps `images/chicken_bg.png` as its main artwork.
- SweetAlert2 dialogs share the website's typography, colour tokens, surfaces, and dark mode.
- Mobile navigation, contextual back buttons, and active-page indicators make the site easier to move through on any screen size.

## Requirements

- PHP 8.2 or later with PDO and the MySQL driver enabled
- Apache or another web server capable of serving PHP
- MySQL or MariaDB
- A browser with JavaScript enabled
- Internet access for the Font Awesome and SweetAlert2 CDN assets used by the pages

XAMPP, WAMP, or a similar local PHP stack can be used for development.

## Local setup

1. Copy or clone this repository into the web root of your PHP server. For XAMPP, this is commonly `htdocs`.
2. Start Apache and MySQL/MariaDB.
3. Create a database named `chicken`.
4. Import [`chicken.sql`](chicken.sql) into the `chicken` database using phpMyAdmin or the MySQL command line.
5. Check the connection settings in [`config.php`](config.php). The current defaults are:
	- Host: `localhost`
	- Database: `chicken`
	- Username: `root`
	- Password: empty
6. Open the project through the web server, for example:

	```text
	http://localhost/Nang%20Chicken%20Market/
	```

The root page in [`index.php`](index.php) provides links to the customer and staff portals.

## Main routes

| Area | Entry point | Purpose |
| --- | --- | --- |
| Portal selector | [`index.php`](index.php) | Choose the customer or staff portal |
| Customer home | [`customer/home.php`](customer/home.php) | Browse available products |
| Customer login | [`customer/login.php`](customer/login.php) | Sign in as a customer |
| Customer registration | [`customer/register.php`](customer/register.php) | Create a customer account |
| Cart | [`customer/cart.php`](customer/cart.php) | Review and update cart items |
| Checkout | [`customer/checkout.php`](customer/checkout.php) | Submit an order and payment method |
| Orders | [`customer/orders.php`](customer/orders.php) | Review previous orders |
| Staff login | [`staff/enterprise_login.php`](staff/enterprise_login.php) | Sign in to the enterprise portal |
| Stock management | [`staff/enterprise_stock.php`](staff/enterprise_stock.php) | View and update inventory |
| Sales management | [`staff/enterprise_sales.php`](staff/enterprise_sales.php) | Search and manage sales |
| Staff directory | [`staff/enterprise_staff.php`](staff/enterprise_staff.php) | Manage staff records |

## Project structure

```text
.
├── index.php              # Customer/staff portal selector
├── config.php             # PDO database connection
├── chicken.sql            # Database schema and sample data
├── customer/              # Customer pages and shared layout
├── staff/                 # Staff portal pages and shared layout
├── css/style.css          # Shared light/dark design system for every page
├── js/theme.js            # Persistent light/dark theme controller
├── js/                    # Other client-side JavaScript
├── images/                # Product, branding, and page images
└── uploaded_img/          # Uploaded image storage directory
```

## Database

The supplied dump creates these tables:

- `customer` for customer accounts and contact details
- `staff` for staff accounts and staff information
- `stock` for product prices and available quantities
- `cart` for customer cart items
- `orders` for order line items and receipt numbers
- `payment` for payment records and payment methods

The checkout process uses Malaysia time (`Asia/Kuala_Lumpur`) and generates receipt numbers in the form `NC-XXXXXXXX`.

## Important development notes

- The current [`config.php`](config.php) uses a local `root` account with an empty password. Configure a dedicated database user and password before deploying beyond a local development environment.
- Customer and staff passwords in the supplied code and sample SQL are stored and compared as plain text. Password hashing should be implemented before production use.
- The contact form in [`customer/contact.php`](customer/contact.php) expects a `message` table, but that table is not present in the supplied [`chicken.sql`](chicken.sql). The contact form will need a matching table migration before it can store messages on a fresh database import.
- The SQL dump contains sample customer, staff, cart, order, and payment data. Replace or remove this data for a clean deployment.
- The application uses prepared statements for most database queries, but authentication, authorization, CSRF protection, validation, and error display should be reviewed before production deployment.

## License

No license file is currently included in the repository.
