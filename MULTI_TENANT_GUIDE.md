# Multi-Tenant System Guide

## Overview
The FurnHub system has been converted to a multi-tenant architecture where each business owner has their own isolated data for business settings, payment settings, categories, and products.

## What Changed

### Database Changes
All main tables now include an `owner_id` column to isolate data per business owner:

1. **system_settings** - Business information (name, colors, contact info)
   - Added: `owner_id INT(11)` with index
   - Each owner has their own business settings

2. **payment_settings** - Payment account details
   - Added: `owner_id INT(11)` with index
   - Each owner configures their own payment accounts

3. **categories** - Product categories
   - Added: `owner_id INT(11)` with index
   - Categories are specific to each owner

4. **products** - Product inventory
   - Added: `owner_id INT(11)` with index
   - Products belong to individual owners

### SQL Migration Script
Run this to add `owner_id` columns to existing tables:
```sql
-- File: database/add_owner_id_columns.sql

-- Add owner_id to system_settings
ALTER TABLE system_settings ADD COLUMN owner_id INT(11) NULL AFTER id;
ALTER TABLE system_settings ADD INDEX idx_owner_id (owner_id);

-- Add owner_id to payment_settings
ALTER TABLE payment_settings ADD COLUMN owner_id INT(11) NULL AFTER id;
ALTER TABLE payment_settings ADD INDEX idx_owner_id (owner_id);

-- Add owner_id to categories
ALTER TABLE categories ADD COLUMN owner_id INT(11) NULL AFTER category_id;
ALTER TABLE categories ADD INDEX idx_owner_id (owner_id);

-- Add owner_id to products
ALTER TABLE products ADD COLUMN owner_id INT(11) NULL AFTER product_id;
ALTER TABLE products ADD INDEX idx_owner_id (owner_id);
```

## Updated Files

### Owner Dashboard Pages
All owner pages now filter data by `owner_id = $_SESSION['user_id']`:

1. **owner/settings.php** - Business settings (isolated)
2. **owner/payment_settings.php** - Payment accounts (isolated)
3. **owner/categories.php** - Category management (isolated)
4. **owner/products.php** - Product listing (isolated)
5. **owner/add_product.php** - Product creation (auto-assigns owner_id)
6. **owner/edit_product.php** - Product editing (owner validation)
7. **owner/delete_product.php** - Product deletion (owner validation)
8. **owner/inventory.php** - Inventory management (isolated)
9. **owner/update_stock.php** - Stock updates (owner validation)
10. **owner/dashboard.php** - Statistics (calculated per owner)
11. **owner/setup_wizard.php** - Setup status (checks owner data)

### Update Handlers
1. **owner/update_settings.php** - Saves settings with owner_id
2. **owner/update_payment_settings.php** - Saves payment info with owner_id

## How It Works

### New Owner Registration Flow
1. Owner registers via `register.php` (selects "Business Owner" role)
2. On first login, automatically redirected to `setup_wizard.php`
3. Setup wizard checks if owner has:
   - Business information (system_settings WHERE owner_id = X)
   - Payment settings (payment_settings WHERE owner_id = X)
   - Categories (categories WHERE owner_id = X)
   - Products (products WHERE owner_id = X)
4. Owner must complete all 4 steps before accessing dashboard
5. Dashboard enforces validation - redirects back if incomplete

### Data Isolation
Every query now filters by `owner_id`:
```php
$owner_id = $_SESSION['user_id'];

// Example: Get categories for this owner only
$query = "SELECT * FROM categories WHERE owner_id = :owner_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':owner_id', $owner_id);
$stmt->execute();
```

### Automatic Column Creation
Pages automatically create `owner_id` columns if they don't exist:
```php
try {
    $columnCheck = $db->query("SHOW COLUMNS FROM products LIKE 'owner_id'");
    if ($columnCheck->rowCount() == 0) {
        $db->exec("ALTER TABLE products ADD COLUMN owner_id INT(11) NULL AFTER product_id");
        $db->exec("ALTER TABLE products ADD INDEX idx_owner_id (owner_id)");
    }
} catch (Exception $e) {
    // Column might already exist
}
```

## Benefits

### For Business Owners
- **Complete Control**: Each owner manages only their own data
- **Privacy**: Cannot see other owners' products, categories, or settings
- **Customization**: Own business name, colors, payment accounts
- **Independence**: No interference between different businesses

### For System Administrators
- **Scalability**: Support multiple businesses on one installation
- **Security**: Data isolation prevents cross-owner access
- **Flexibility**: Each business can have different setups

## Testing Checklist

### New Owner Registration
- [ ] Register new owner account via register.php
- [ ] Verify redirect to setup_wizard.php
- [ ] Complete business information (Step 1)
- [ ] Complete payment settings (Step 2)
- [ ] Create at least one category (Step 3)
- [ ] Create at least one product (Step 4)
- [ ] Verify dashboard access after completion
- [ ] Confirm cannot access dashboard until all steps complete

### Data Isolation
- [ ] Register two different owner accounts
- [ ] Owner A creates categories and products
- [ ] Owner B creates different categories and products
- [ ] Login as Owner A - verify only sees their own data
- [ ] Login as Owner B - verify only sees their own data
- [ ] Confirm no cross-contamination

### Settings Management
- [ ] Each owner can change business name independently
- [ ] Each owner has own payment account details
- [ ] Each owner has own color scheme and branding
- [ ] Changes by Owner A don't affect Owner B

### Product Management
- [ ] Owner can only edit their own products
- [ ] Owner can only delete their own products
- [ ] Owner can only view their own categories
- [ ] Attempting to access other owner's product IDs fails

## Migration for Existing Data

If you have existing data before multi-tenant conversion:

### Option 1: Assign to First Owner
```sql
-- Get first owner user_id
SELECT user_id FROM users WHERE role = 'owner' LIMIT 1;

-- Assign all existing data to that owner (replace X with actual user_id)
UPDATE system_settings SET owner_id = X WHERE owner_id IS NULL;
UPDATE payment_settings SET owner_id = X WHERE owner_id IS NULL;
UPDATE categories SET owner_id = X WHERE owner_id IS NULL;
UPDATE products SET owner_id = X WHERE owner_id IS NULL;
```

### Option 2: Create Test Owner
```sql
-- Create new test owner
INSERT INTO users (full_name, email, password, role) 
VALUES ('Test Owner', 'owner@furnhub.com', '$2y$10$...', 'owner');

-- Get the new user_id and assign data (replace X)
UPDATE system_settings SET owner_id = X WHERE owner_id IS NULL;
UPDATE payment_settings SET owner_id = X WHERE owner_id IS NULL;
UPDATE categories SET owner_id = X WHERE owner_id IS NULL;
UPDATE products SET owner_id = X WHERE owner_id IS NULL;
```

## Important Notes

### Customer Orders
- Customers can still purchase from any owner's products
- Order system tracks which products belong to which owner via product_id
- Dashboard statistics calculate revenue based on owner's products

### Admin Access
- Admin users still see all data across all owners
- Admin dashboard queries don't filter by owner_id
- Admin can manage system-wide settings

### Riders
- Riders are assigned to deliveries regardless of owner
- Rider dashboard shows all assigned deliveries
- No rider isolation by owner (shared delivery workforce)

## Security Considerations

1. **Always validate owner_id** in queries:
   ```php
   WHERE product_id = :id AND owner_id = :owner_id
   ```

2. **Never trust URL parameters** alone - always check ownership

3. **Use prepared statements** with owner_id binding

4. **Validate permissions** before allowing edits/deletes

## Future Enhancements

Possible additions to the multi-tenant system:

1. **Subdomain Support**: Each owner gets their own subdomain
2. **Custom Domains**: Owners can use their own domain names
3. **Owner Plans**: Different subscription tiers with feature limits
4. **Multi-Store**: One owner manages multiple store locations
5. **Franchise Mode**: Parent owner with sub-owners
6. **Revenue Sharing**: Platform takes commission per sale
7. **Owner Analytics**: Detailed business intelligence dashboard
8. **API Access**: Each owner gets API keys for integrations

## Support

If you encounter issues with the multi-tenant system:

1. Check database columns exist: `SHOW COLUMNS FROM products;`
2. Verify owner_id is set: `SELECT owner_id FROM products LIMIT 10;`
3. Check session: `var_dump($_SESSION['user_id']);`
4. Review error logs in `php_errors.log`

## Conclusion

The FurnHub system now supports multiple independent business owners on a single installation. Each owner has complete control over their own business data, settings, and products while maintaining data privacy and security.
