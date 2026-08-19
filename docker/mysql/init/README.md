# Docker database seed policy

`10-web-traicay.sql` is a sanitized demo catalog dump. It may contain only
schema, migration state, categories, products, product images, coupons, and
coupon images.

Never export these tables into a tracked seed file:

- users, authentication tokens, password history, or sessions;
- orders, carts, addresses, contacts, notifications, or audit logs;
- any table containing customer names, email addresses, phone numbers,
  shipping details, OAuth identifiers, IP addresses, or password hashes.

Use synthetic seeders for demo accounts. Keep all real credentials and
customer data outside Git.
