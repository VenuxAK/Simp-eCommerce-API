# SimpCommerce User Manual

## Overview

SimpCommerce is a Point of Sale system designed for home-use clothing stores. It supports **English** and **Burmese (မြန်မာ)** languages, works on desktop and mobile, and includes dark mode.

### Quick Start

```bash
# Terminal 1 — Start the backend API
cd api
php artisan serve
# → http://localhost:8000

# Terminal 2 — Start the frontend
cd frontend
bun run dev
# → http://localhost:5173
```

**Default login credentials:**

| Role | Email | Password |
|------|-------|----------|
| Admin | `admin@simppos.test` | `password` |
| Staff | `staff@simppos.test` | `password` |

---

## 1. Dashboard

The dashboard shows a summary of today's business:

- **Today's Sales** — total revenue from completed orders
- **Today's Orders** — number of orders placed today
- **Total Products/Variants** — inventory overview
- **Low Stock Items** — variants with stock ≤ 5 (amber alert)
- **Sales Chart** — interactive bar chart with 7 days / 30 days / Month toggle
- **Recent Orders** — last 10 orders, click to view details
- **Backups** — create and download database backups
- **Active Cash Session** — shows if register is open (see Cash Management)

---

## 2. POS (Point of Sale)

The main checkout screen for ringing up customers.

### Adding Items

1. **Browse products** — products appear as cards with photos and prices
2. **Filter** — use the search bar (or press `Ctrl+K` / `/` to focus search) to find by **name**, **SKU**, or **category**
3. **Category filter** — dropdown to show only one category
4. **Click a product card** → a dialog opens showing all variants (size/color) with photos and stock levels
5. **Click a variant** → added to the cart on the right

### Barcode Scanning

- **Point a barcode scanner** at the label and scan
- The scanner types the SKU rapidly + presses Enter
- SimpCommerce detects this (fast keystrokes) and auto-adds the item to cart
- A green pulse indicator shows in the search bar during active scanning
- If the SKU is not found, a "SKU not found" toast appears

### Cart Management

- **+ / −** buttons adjust quantity (stops at available stock)
- **X** removes item from cart
- Each cart item shows the variant thumbnail photo
- **Customer** — optional: click to search and assign a customer (earns loyalty points)

### Discounts

- Select a discount from the dropdown below the cart
- Discounts can apply to: **All Items**, **Category**, or **Product**
- The discounted total is shown in real-time (subtotal → discount → total)

### Checkout

1. Select **payment method**: Cash or Bank Transfer
2. Enter **amount received** (for cash, enter what the customer paid)
3. **Change** is calculated automatically
4. Click **Complete Sale**
5. Order is created, stock is deducted, invoice is auto-generated

### After Sale

- Go to **Invoices** → click the invoice → **Print**, **Receipt** (thermal), or **PDF**

---

## 3. Products

### Product List

- **List/Grid toggle** — switch between table and card views
- **Search** — by product name, SKU, or category
- **Category filter** — show products from one category
- **Import CSV** — bulk import products from a CSV file
- **Export CSV** — download all products as a CSV file
- **Pagination** — navigates through pages of products
- Click the edit icon (✏️) to modify a product

### Creating a Product

1. Click **New Product**
2. Fill in:
   - **Name** — product name
   - **Category** — select from existing categories
   - **Supplier** — optional, select a supplier (see Suppliers)
   - **Base Price** — selling price in Kyats
   - **Image** — upload a product photo (jpg/png/webp, max 2MB)
   - **Description** — optional notes
3. Add **Variants** — each size/color combination:
   - **SKU / Barcode** — unique identifier (this is what barcode scanners read)
   - **Size** (S, M, L, XL, etc.)
   - **Color** (Black, White, Red, etc.)
   - **Price Adj.** — if this variant costs more or less than base price
   - **Cost** — purchase price (for profit tracking)
   - **Stock** — how many you have
   - **Variant Image** — optional photo of this specific color/size
4. Click **Save**

### Editing a Product

- All fields are editable
- Adding/removing variants preserves existing order history (variants with orders are protected from deletion)
- You cannot delete a product that has order history

### Barcode Labels

- On the product edit page, you can print **barcode labels** for all variants
- Labels include: barcode (Code 39), SKU, product name, size/color, price
- Designed for standard label sheets

---

## 4. Categories

Simple category management for organizing products.

- **Create** — enter a name and optional description
- **Edit** — rename or change description
- **Delete** — only if no products are assigned

---

## 5. Customers

Track customer information and purchase history.

- **Search** — by name, email, or phone
- **Click a customer** → view their profile and order history
- **Delete** — removes customer record (does not affect order history)
- **Loyalty Points** — customers earn 1 point per 10 Ks spent, automatically awarded on sale

---

## 6. Suppliers

Manage your product suppliers/vendors.

- **Create** — name, contact person, phone, email, address, notes
- **Edit/Delete** — suppliers with linked products cannot be deleted
- Products can be assigned to a supplier during creation/editing
- The product form includes a **Supplier** dropdown

---

## 7. Sales (Order History)

View all completed, cancelled, and refunded orders.

- **Filter** — by date range and status
- **Pagination** — navigates through pages of orders
- **Click an order** → view full details including items, payment, and invoice
- **Refund** — for completed orders, click **Return** to open the return panel:
  - Check which items to return (checkbox per item)
  - Stock is automatically restored
  - A stock movement is logged
  - Order and invoice status updated to refunded

---

## 8. Invoices

Auto-generated for every completed order.

- **Filter** — by date range and status
- **Pagination** — navigates through pages of invoices
- **Click an invoice** → view full invoice with items
- **Actions:**
  - **Print** — browser print (use with receipt paper for thermal)
  - **Receipt** — thermal-optimized receipt view (58mm width, monospace font)
  - **PDF** — download as a formatted PDF

---

## 9. Discounts

Create and manage promotional discounts.

### Creating a Discount

1. Go to **Discounts** → **Create**
2. Fill in:
   - **Name** — e.g., "Summer Sale"
   - **Type** — Percentage (%) or Fixed Amount (Ks)
   - **Value** — the discount amount
   - **Applies To** — who gets the discount:
     - *All Items* — entire order
     - *Category* — only items in a specific category (pick from list)
     - *Product* — only a specific product (pick from list)
   - **Dates** — optional start/end dates
   - **Active** — toggle on/off
3. Click **Save**

### Using a Discount in POS

1. Add items to cart
2. Select the discount from the dropdown below the cart
3. The subtotal, discount amount, and total are shown in real-time
4. Complete the sale — the discount is applied automatically

---

## 10. Stock History

Track every stock change across all variants.

- **Filter** — by date range and reason (sale, adjustment, cancel, refund)
- **Pagination** — navigates through pages of movements
- Each movement shows: date, product name, variant, quantity change (+ or -), and reason badge
- Stock is logged automatically on:
  - Sale completion (deduction)
  - Order cancel/refund (restoration)
  - Manual stock adjustment

---

## 11. Cash Management

Manage daily cash register sessions.

### Opening the Register

1. Go to **Cash** → **Open Register**
2. Enter the **opening balance** (cash in the drawer at start of day)
3. Click **Open** — a green banner confirms the register is open

### Closing the Register

1. Count the cash in the drawer
2. Click **Close Register**
3. Enter the **closing balance** (actual cash counted)
4. SimpCommerce calculates:
   - **Expected balance** = opening balance + cash orders during session
   - **Difference** = closing balance − expected balance
5. A non-zero difference means over/under in the drawer
6. A success toast shows the difference amount

### Session History

View all past sessions with:
- Date, user who opened/closed
- Opening/closing balances
- Difference (green for positive, red for negative)

---

## 12. Reports

### Sales Report

- **Date range** — select from/to dates
- Shows: total sales, order count, average order value, items sold
- **Daily breakdown** — table with per-day totals

### Best Sellers

- Shows top 20 products by quantity sold in the selected date range
- Columns: rank, product name, variant, quantity sold, revenue

### Payment Methods

- **Cash vs Transfer** breakdown
- Shows: payment method, number of orders, total amount

---

## 13. User Management (Admin only)

Manage staff accounts.

- **Create User** — name, email, password, role (Admin or Staff)
- **Edit User** — update info, reset password, change role
- **Delete User** — cannot delete yourself or users with order history
- **Search** — filter users by name or email
- Current user is marked with a **(you)** label

### Role Differences

| Permission | Admin | Staff |
|------------|-------|-------|
| Use POS | ✅ | ✅ |
| Manage products/customers | ✅ | ✅ |
| View reports | ✅ | ✅ |
| Manage discounts | ✅ | ✅ |
| Manage users | ✅ | ❌ |
| View audit log | ✅ | ❌ |
| Access user management page | ✅ | ❌ |

---

## 14. Profile

Update your own account information.

- Change **name** and **email**
- Change **password** (leave blank to keep current)
- Your role badge is displayed (Admin or Staff)

---

## 15. Audit Log (Admin only)

View a chronological log of all important actions in the system.

- **Filter** — by action type (created, updated, deleted)
- **Pagination** — navigates through pages
- Each entry shows: date, user, action, model type, and model ID

---

## 16. Database Backup

Create and download backups of your SQLite database.

- **On the Dashboard** — click **Backup Now** to create a backup
- Backups are stored with timestamps (e.g., `backup-2026-05-21-145537.sqlite`)
- Click the **download icon** next to any backup to save it
- Only the 5 most recent backups are shown on the dashboard

---

## 17. Keyboard Shortcuts

| Shortcut | Action |
|----------|--------|
| `Ctrl+K` | Focus POS search bar |
| `/` | Focus POS search bar (from anywhere except text inputs) |
| Barcode scan | Auto-detected → adds item to cart |

---

## 18. Dark Mode

Click the **moon/sun icon** in the header to toggle between light and dark themes. Your preference is saved automatically to localStorage.

---

## 19. Language

Click the language toggle in the header to switch between **English** and **Burmese (မြန်မာ)**. Your preference is saved automatically to localStorage.

Validation error messages from the API are also translated to your selected language.

---

## 20. Pagination

All list pages (Products, Customers, Sales, Invoices, Discounts, Suppliers, Stock, Users, Audit Log) use server-side pagination.

- Navigate with **prev/next** buttons and **page number** buttons
- Shows "X–Y of Z" summary (e.g., "21–40 of 156")
- Filters and search work together with pagination

---

## 21. Common Tasks

### How do I sell a product?

1. Go to **POS** → find the product → click it → choose size/color → add to cart
2. (Optional) assign a customer → select payment method → enter amount → **Complete Sale**

### How do I add a new product with variants?

1. Go to **Products** → **New Product**
2. Fill in name, category, base price
3. Add variants: enter SKU, size, color, stock for each
4. **Save**

### How do I print a receipt?

1. Complete a sale
2. Go to **Invoices** → click the invoice
3. Click **Receipt** (thermal-optimized) or **Print** (browser print) or **PDF** (download)

### How do I refund an order?

1. Go to **Sales** → click the completed order
2. Click **Return**
3. Check the items to return → click **Refund**
4. Stock is restored automatically

### How do I apply a discount?

1. Create the discount under **Discounts**
2. In **POS**, select the discount from the cart dropdown
3. Complete the sale — only eligible items get discounted

### How do I open/close the cash register?

1. Go to **Cash** → **Open Register** → enter starting balance
2. At end of day → **Close Register** → enter counted cash
3. Review the difference

### How do I backup the database?

1. Go to **Dashboard**
2. Click **Backup Now**
3. Click the download icon next to any backup to save it

### How do I import/export products?

1. Go to **Products**
2. Click **Export** to download a CSV of all products
3. Click **Import** to upload a CSV file (format: category, name, sku, size, color, base_price, etc.)

### How do I print barcode labels?

1. Go to **Products** → click edit on a product
2. The product page has a **Labels** link/button
3. Opens a printable page with barcode labels for all variants

---

## 22. Tips & Troubleshooting

- **Barcode not scanning?** Make sure the SKU in the system exactly matches the barcode label. Barcode scanners are plug-and-play (USB), no drivers needed.
- **Missing products in POS?** Check the category filter is set to "All Categories".
- **Can't delete a product?** It has existing order history. Set stock to 0 instead to disable it.
- **Image not showing?** Images are served from `http://localhost:8000/storage/...`. Make sure the Laravel server is running.
- **PDF not downloading?** Make sure you're logged in. The PDF endpoint requires authentication.
- **Backup download fails?** Make sure you are logged in with a valid token.
- **Chart shows "No sales data"?** Select a different date range — if there are no orders in the selected period, the chart will be empty.
- **Pagination resets after filter?** Applying a filter always returns to page 1.
