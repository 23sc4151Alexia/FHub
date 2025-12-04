# FurnHub - Furniture Shop Ordering System

A comprehensive furniture shop ordering system with separate dashboards for Admin, Owner, Rider, and Customer roles.

## Features

### Admin Dashboard
- View overall statistics and analytics
- Manage all orders (view, edit, assign riders)
- Manage products (add, edit, delete)
- Manage users (customers, riders, owners, admins)
- Manage categories
- View reports

### Owner Dashboard
- Sales reports and analytics
- Revenue tracking (daily, monthly, total)
- Inventory management
- Low stock alerts
- Top selling products
- Order overview

### Rider Dashboard
- View assigned deliveries
- Update delivery status
- Track completed deliveries
- View earnings
- Customer contact information
- Delivery history

### Customer Interface
- Browse products by category
- Search and filter products
- Shopping cart functionality
- Place orders with delivery information
- Track order status
- View order history

## Installation

1. **Database Setup**
   - Create a MySQL database named `furnhub_db`
   - Import the schema from `database/schema.sql`
   - The schema includes sample data and default users

2. **Configuration**
   - Update database credentials in `config/database.php`
   - Adjust BASE_URL in `config/config.php` if needed

3. **Access the System**
   - Place the project in your XAMPP htdocs folder
   - Access via: `http://localhost/furnhub/`

## Default Login Credentials

- **Admin**: admin / admin123
- **Owner**: owner / admin123
- **Rider**: rider1 / admin123
- **Customer**: Register a new account

## System Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache Web Server (XAMPP recommended)
- Modern web browser

## Project Structure

```
furnhub/
├── admin/              # Admin dashboard pages
├── owner/              # Owner dashboard pages
├── rider/              # Rider dashboard pages
├── customer/           # Customer interface pages
├── auth/               # Authentication handlers
├── config/             # Configuration files
├── database/           # Database schema
├── includes/           # Shared components (headers, sidebars)
├── assets/             # CSS, JS, images
├── index.php           # Landing page
├── login.php           # Login page
└── register.php        # Registration page
```

## Key Features by Role

### Admin
- Complete system control
- User management across all roles
- Product and category management
- Order management and rider assignment
- System-wide reports

### Owner
- Business analytics and insights
- Sales reports with date filtering
- Inventory monitoring
- Revenue tracking
- Top products analysis

### Rider
- Delivery management
- Status updates (mark as delivered)
- Customer contact access
- Earnings tracking
- Delivery history

### Customer
- Product browsing and search
- Shopping cart
- Order placement
- Order tracking
- Order history

## Technologies Used

- **Backend**: PHP 7.4+
- **Database**: MySQL with PDO
- **Frontend**: HTML5, CSS3, JavaScript
- **Session Management**: PHP Sessions
- **Security**: Password hashing, input sanitization, prepared statements

## Security Features

- Password hashing using PHP's `password_hash()`
- SQL injection prevention via prepared statements
- XSS protection through input sanitization
- Role-based access control
- Session-based authentication

## Future Enhancements

- Email notifications for orders
- SMS notifications for riders
- Payment gateway integration
- Real-time order tracking
- Product reviews and ratings
- Advanced analytics dashboard
- Mobile app integration
- Multi-language support

## Support

For issues or questions, please refer to the documentation or contact the development team.

## License

This project is proprietary software for FurnHub.
