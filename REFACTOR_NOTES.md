# Refactoring Notes

This version keeps the existing procedural PHP structure and storefront/admin design. It is a focused cleanup rather than a full rewrite.

## Changes made

- Added shared HTML escaping, CSRF-token and product-image upload helpers in `functions.php`.
- Set PDO to use exceptions, associative fetches and native prepared statements.
- Added CSRF checks to storefront and admin forms that change data, including cart updates, checkout, account forms, product/category changes and order-status updates.
- Changed product deletion and logout actions to POST requests.
- Added session-ID regeneration after successful customer and admin sign-in.
- Restricted admin sign-in to active admin accounts.
- Added server-side validation for product prices, stock, contact email and registration email/password.
- Added image upload checks for real image data, allowed image formats and a 5 MB size limit; generated safe filenames instead of using the original upload filename.
- Reworked checkout so product and variant stock are checked and reduced inside a database transaction. Order subtotal, shipping fee and total are saved separately, and order-item product/variant details are snapshotted.
- Preserved historical order items when a product is deleted and cleaned up associated image files.
- Corrected a malformed product-image tag in `products.php`.
- Escaped additional database-backed output in storefront and admin pages.

## Validation performed

- PHP syntax check (`php -l`) passed for all PHP files.
- The supplied SQL dump was reviewed. The product and variant stock totals in the dump are consistent for products that have variants.
- The supplied schema uses InnoDB and has the expected foreign-key behavior for products, product images, variants and order items.

## Not yet runtime-tested

A live checkout, login, upload or admin workflow test was not possible in this environment because the PHP runtime here does not have the `pdo_mysql` extension or a running MariaDB/MySQL server. Please test these flows locally in XAMPP before replacing your working project.

A sanitized public SQL dump is included in `database/guzel_ayakkabi_public.sql`. It retains the schema and catalog seed data but omits user accounts, password hashes, customer contact details, orders, invitations, and activity-log records.
