# SimpCommerce — Product Requirements Document

> **Version**: 1.0 | **Date**: 2026-06-11 | **Status**: Draft  
> **Author**: SimpCommerce Team  
> **Derived from**: `PROJECT_ANALYSIS.md`, `ARCHITECTURE.md`, `SPECIFICATION.md`, `API.md`

---

## 1. Executive Summary

**SimpCommerce** is an open-source, self-hosted commerce platform purpose-built for Myanmar's mid-market retailers operating 5–50 store locations with product catalogs ranging from 1,000 to 50,000 SKUs. The platform delivers a complete retail operating system — online storefront, customer self-service portal, inventory management, CRM, and reporting — through a modular monolith backend (Laravel 13) with independently deployable frontends (Nuxt 4 storefronts, Vue 3 dashboard).

### 1.1 Problem Statement

Mid-market retailers in Myanmar face three structural challenges:
1. **Fragmented systems** — POS, e-commerce, inventory, and accounting run on separate tools with no unified data model
2. **Cost-prohibitive SaaS** — International platforms (Shopify, Magento) are priced for Western markets and lack Myanmar-specific localization (MMK currency, Myanmar numerals, local address formats)
3. **Multi-store complexity** — Growing chains need centralized catalog management with per-store pricing, inventory, and order routing

### 1.2 Solution

SimpCommerce provides a **single codebase** that powers:
- **Customer-facing storefronts** (Nuxt SSR, one per store brand)
- **Staff administration dashboard** (Vue 3 SPA)
- **Shared backend API** with multi-tenant store isolation

### 1.3 Business Model

Self-hosted and open-source. Revenue model: optional professional services (custom deployments, support SLAs, training). The platform generates no per-transaction fees and requires no vendor lock-in.

---

## 2. Product Vision & Strategy

### 2.1 Vision Statement

> *To be the default commerce operating system for Myanmar's retail sector — free, self-owned, and fully localized.*

### 2.2 Strategic Pillars

| Pillar | Description |
|--------|-------------|
| **Localization First** | Full MMK currency, Myanmar numerals (၁၂၃၅), RTL-friendly layouts, Thanaka address formats, bilingual UI (English/Myanmar) |
| **Multi-Store by Design** | Every entity (product, category, order, customer, discount) is store-scoped from day one; no retrofit |
| **Self-Hosting Simplicity** | Runs on a single $20/month VPS with PostgreSQL; no Kubernetes, no microservices |
| **Extensible Architecture** | Modular monolith with clear domain boundaries; contributors can work on isolated modules |
| **Mobile-First Dashboard** | Staff perform POS operations, stock checks, and order management from tablets/phones |

### 2.3 Guiding Principles

1. **Offline-tolerant design** — Core POS flows must work during intermittent connectivity (future)
2. **Zero data egress** — All data stays on the retailer's own server
3. **Convention over configuration** — Sensible defaults; advanced settings are opt-in
4. **API-first** — Every feature exposed via REST API; UI is a consumer, not the system
5. **Test coverage mandate** — All API endpoints and service classes have automated tests

---

## 3. Target Users & Personas

### 3.1 User Roles

| Role | Population | Auth Method | Primary Interface |
|------|-----------|-------------|-------------------|
| **Root Admin** | 1–3 per organization | Sanctum token (24h) | Staff Dashboard |
| **Store Admin** | 1–2 per store location | Sanctum token (24h) | Staff Dashboard |
| **Staff** | 3–15 per store location | Sanctum token (24h) | Staff Dashboard |
| **Customer** | 500–50,000 per store | Sanctum token (7d) or Google OAuth | Storefront |
| **Guest** | Unlimited | None | Storefront |

### 3.2 Personas

#### Root Admin (e.g., U Aung, CTO of a 12-store electronics chain)
- **Needs**: Centralized user management, store creation, backup scheduling, audit trail access, full cross-store reporting
- **Pain Points**: Managing separate databases per store, inconsistent security policies
- **Success Metric**: <5 minutes to onboard a new store location

#### Store Admin (e.g., Daw Su, branch manager)
- **Needs**: Manage local catalog (add/retire products per store), update stock, manage local discounts, oversee staff
- **Pain Points**: Cannot see inventory across stores, manual stock reconciliation
- **Success Metric**: <2 minutes to adjust stock for 50 products

#### Cashier/Staff (e.g., Ko Min, retail associate)
- **Needs**: POS checkout, barcode scanning by SKU, customer lookup, cash session management
- **Pain Points**: Slow product search, complex checkout flow, training overhead
- **Success Metric**: <30 seconds per POS transaction

#### Customer (e.g., Ma Thiri, online shopper)
- **Needs**: Browse products, add to cart, COD checkout, track orders, manage addresses, wishlist
- **Pain Points**: Unclear shipping status, difficult return process, untrusted payment
- **Success Metric**: <2 minutes from cart to order confirmation

---

## 4. Functional Requirements

### 4.1 Multi-Store Management (v1)

**Priority**: P0 (foundational — required for all other features)

| ID | Requirement | Rationale |
|----|-------------|-----------|
| MS-01 | Create, read, update, delete stores via API (root only) | Centralized store lifecycle management |
| MS-02 | Each store has a unique slug used as `X-Store` header for scoping | Enables domain-based and header-based store isolation |
| MS-03 | Store entity includes: name, slug, domain (nullable), description, logo, phone, email, is_active toggle, freeform settings JSON | Covers branding, contact, and per-store configuration |
| MS-04 | `store_id` foreign key on: products, categories, brands, orders, customers, discounts, suppliers, cash_sessions, users | Ensures data isolation between stores |
| MS-05 | ResolveStore middleware reads X-Store header, resolves Store model, makes available as `app('current_store')` | Single source of truth for store context in requests |
| MS-06 | Storefront endpoints automatically filter catalog by resolved store | Customers only see products belonging to the target store |
| MS-07 | Default store (`main`) cannot be deleted | Prevents lockout from the system |
| MS-08 | Store-level settings support: currency display, theme config, shipping zones, tax rules (freeform JSON, validated by frontend) | Extensibility without schema changes |

### 4.2 Product Catalog (v1)

**Priority**: P0

| ID | Requirement | Rationale |
|----|-------------|-----------|
| PC-01 | Products have: name, slug (auto-generated from name), description, base_price, image, category_id (FK), brand_id (FK nullable), supplier_id (FK nullable), store_id (FK) | Complete product data model with key relationships |
| PC-02 | Product variants have: SKU (unique), size, color, image, price_adjustment (± from base_price), purchase_price, stock_quantity | Supports multi-SKU products (sizes, colors) |
| PC-03 | CRUD operations for products via dashboard API (admin write, staff read) | Full lifecycle management |
| PC-04 | CRUD operations for categories with parent_id self-reference for hierarchy | Multi-level category trees (e.g., Electronics > Phones > Smartphones) |
| PC-05 | CRUD operations for brands with logo upload | Brand-based product grouping and filtering |
| PC-06 | Image upload for products, variants, categories, and brand logos (multipart form) | Visual catalog management |
| PC-07 | CSV import with per-row validation, dispatched as background job | Bulk product onboarding for 1,000+ SKUs |
| PC-08 | CSV export with column headers | Offline catalog editing, supplier sharing |
| PC-09 | Barcode label generation (HTML) by product ID | Physical shelf labeling |
| PC-10 | Variant lookup by SKU via API | Barcode scanner integration at POS |
| PC-11 | Paginated product listing with search (name/SKU) and category filter | Efficient browsing in large catalogs |
| PC-12 | Storefront public endpoints: paginated products, product detail by slug, category list, brand list, store settings | Nuxt SSR storefront data source |

### 4.3 Inventory & Stock Management (v1)

**Priority**: P0

| ID | Requirement | Rationale |
|----|-------------|-----------|
| IN-01 | Stock quantity is an absolute value on `product_variants.stock_quantity` | Clear inventory picture per variant |
| IN-02 | PATCH `/variants/{id}/stock` for manual absolute stock adjustment | Physical stock count reconciliation |
| IN-03 | Every stock change creates a StockMovement record with: product_variant_id, quantity_change (±), reason enum (Sale, Purchase, Adjustment, Return), reference_type, reference_id, user_id | Full audit trail for inventory |
| IN-04 | Stock movements list endpoint with date range and reason filters (admin only) | Inventory audit and discrepancy investigation |
| IN-05 | Atomic row-lock stock decrement at checkout | Prevents overselling during concurrent purchases |
| IN-06 | Stock validation before adding to cart and before checkout | Prevents customers from ordering unavailable items |
| IN-07 | Idempotent cancel guard: double-cancel does not double-restock | Data integrity during edge cases |
| IN-08 | Stock auto-restore on order cancellation (reason=Return) and manual return processing | Accurate inventory after refunds |

### 4.4 Order Management (v1: online — v2: POS)

**Priority**: P0 (online), P1 (POS)

| ID | Requirement | Rationale |
|----|-------------|-----------|
| OR-01 | Orders have: order_number (auto-generated ORD-YYYYMMDD-XXXX), total_amount, source enum (pos/online), status enum, notes | Core order data |
| OR-02 | Order number generation: sequential per date with DB-level locking, format `ORD-{YYYYMMDD}-{XXXX}` | Unique, human-readable, thread-safe |
| OR-03 | Order statuses: Pending, Processing, Completed, Shipped, Delivered, Cancelled, Refunded | Covers POS and online lifecycles |
| OR-04 | Source-specific status transitions enforced: POS (pending→completed→cancelled→refunded), Online (processing→shipped→delivered, processing→cancelled) | Prevents invalid state changes |
| OR-05 | Order items capture: product_variant_id, quantity, unit_price, subtotal (frozen at order time) | Price snapshot ensures historical accuracy |
| OR-06 | Staff can create POS orders via API: select customer (optional), items with variant+quantity, payment method, amount received, discount (optional) | In-store sale workflow |
| OR-07 | Staff can update order status (PATCH) with transition validation (admin only) | Order fulfillment pipeline |
| OR-08 | Admin can process partial returns: specify items to return, restocks automatically | Item-level return management |
| OR-09 | Paginated order listing with search (order_number, customer name) and status filter | Order lookup and processing queue |
| OR-10 | Full order detail includes: items (with variant → product), payment, invoice, shipment | Single view for order processing |

### 4.5 Invoice Management (v1)

**Priority**: P0

| ID | Requirement | Rationale |
|----|-------------|-----------|
| IV-01 | Invoice auto-generated on order creation | Every sale has a corresponding financial document |
| IV-02 | Invoice number auto-generated: `INV-{YYYYMMDD}-{XXXX}`, sequential per date with DB locking | Legal compliance, sequential audit trail |
| IV-03 | Invoice statuses: Draft, Issued, Paid, Cancelled, Overdue | Tracks payment lifecycle |
| IV-04 | Invoice includes: issued_date, due_date (30 days from issue), notes, terms | Complete financial document |
| IV-05 | Invoice PDF download (barryvdh/laravel-dompdf) | Printable customer copy |
| IV-06 | Thermal receipt format for POS | Compact POS printer output |
| IV-07 | Print-ready HTML view | Browser-based printing |
| IV-08 | Paginated invoice listing with search and status filter | Accounts receivable management |

### 4.6 Customer Management (v1)

**Priority**: P0

| ID | Requirement | Rationale |
|----|-------------|-----------|
| CM-01 | Customer registration with: name, email, password (min 8 chars, uppercase+lowercase+digit) | Secure self-service sign-up |
| CM-02 | Customer login returns Sanctum token (7-day expiry) with customer object | API-based auth for mobile apps and SPA |
| CM-03 | Google OAuth login via Socialite: GET redirect URL → consent → callback creates/links customer by email | Social login convenience, passwordless option |
| CM-04 | OAuth customers (password=null) cannot use password login | Security boundary for OAuth-only accounts |
| CM-05 | Customer profile: view own details, update name/email/password | Self-service profile management |
| CM-06 | Customer CRUD in staff dashboard with paginated list and search | Walk-in customer creation, CRM lookup |
| CM-07 | Customer order history sub-resource in staff dashboard | Support queries, return processing |
| CM-08 | Address book: create/read/update/delete addresses with shipping/billing type, set as default | Checkout address selection |
| CM-09 | Address fields: name, phone, street, city, state, postal_code, is_default | Complete shipping address |

### 4.7 E-Commerce (v1)

**Priority**: P0

| ID | Requirement | Rationale |
|----|-------------|-----------|
| EC-01 | Server-side shopping cart: add variant with quantity, update quantity, remove item, clear cart | Cart persists across devices, prevents stock conflicts |
| EC-02 | Cart is scoped to authenticated customer | Multi-user isolation |
| EC-03 | Unit price = product.base_price + variant.price_adjustment | Transparent pricing from base + variant |
| EC-04 | COC checkout via POST `/checkout`: validates cart non-empty, re-checks stock with row lock, creates Order (online/processing) | Transactional checkout with inventory integrity |
| EC-05 | Checkout creates: Order + OrderItems + StockMovements + Invoice + Shipment + cart clear — all in one DB transaction | Atomic order creation |
| EC-06 | Checkout validation endpoint (GET `/checkout/validate`): returns stock warnings before committing | Pre-checkout confidence |
| EC-07 | Idempotency key middleware on checkout | Prevents duplicate orders from network retries |
| EC-08 | Customer order history: paginated list, order detail with items, shipment, invoice, cancel button for processing orders | Self-service order management |
| EC-09 | Order cancellation (processing only): restocks items, cancels invoice, idempotent guard | Customer-initiated cancellation |
| EC-10 | Wishlist: toggle add/remove, list items, clear all, remove by ID | Save-for-later shopping |
| EC-11 | Shipment tracking: linked to address, method enum (Standard, Express), tracking_number, tracking_url, shipped_at, delivered_at | Order fulfillment visibility |

### 4.8 Discount & Promotion (v1)

**Priority**: P1

| ID | Requirement | Rationale |
|----|-------------|-----------|
| DI-01 | Discount types: percentage (%) or fixed amount | Common retail discount models |
| DI-02 | Discount scope: applies to all products, specific category, or specific product | Targeted promotions |
| DI-03 | Discount time window: starts_at, ends_at, is_active toggle | Scheduled and manual promotions |
| DI-04 | CRUD operations (admin write, staff read) | Managed by store/admin |
| DI-05 | Active discounts endpoint for POS | Quick lookup during checkout |
| DI-06 | Discounts are store-scoped | Per-store pricing strategies |

### 4.9 Cash Management (v2 — POS launch)

**Priority**: P1

| ID | Requirement | Rationale |
|----|-------------|-----------|
| CA-01 | Open cash session with opening_balance | Shift-start cash count |
| CA-02 | Close cash session with closing_balance, auto-calculated difference vs expected | Shift-end reconciliation |
| CA-03 | Expected balance = opening_balance + (cash sales) − (cash refunds) | Automated discrepancy detection |
| CA-04 | Session history with date filtering | Audit and reporting |
| CA-05 | Active session endpoint: returns current open session or null | Prevents concurrent sessions |
| CA-06 | Sessions are store-scoped and user-attributed | Multi-store, multi-staff tracking |

### 4.10 Reporting & Analytics (v1)

**Priority**: P0

| ID | Requirement | Rationale |
|----|-------------|-----------|
| RP-01 | Dashboard summary: today's sales total, order count, low-stock products, recent orders (last 5) | At-a-glance store health |
| RP-02 | Sales report: date range filter, daily breakdown (total orders, total revenue) | Period-over-period performance |
| RP-03 | Best-sellers report: top products by total quantity sold | Inventory planning and promotion targeting |
| RP-04 | Payment methods report: sales breakdown by payment type (cash vs transfer) | Payment preference tracking |
| RP-05 | All reports scoped to store context | Per-store performance comparison |

### 4.11 User & Access Management (v1)

**Priority**: P0

| ID | Requirement | Rationale |
|----|-------------|-----------|
| UA-01 | Staff login with email+password, returns Sanctum token (24h) and user object | Dashboard authentication |
| UA-02 | Three staff roles: Root (super admin), StoreAdmin (store-level admin), Staff (read+POS) | Principle of least privilege |
| UA-03 | Role-based middleware on all admin endpoints (Root > StoreAdmin > Staff) | Server-side enforcement |
| UA-04 | User CRUD for root only: create/update/delete staff users, assign roles | Centralized user management |
| UA-05 | Profile self-update for all staff: name, email, password | Self-service profile management |
| UA-06 | Password policy: min 8 characters, must contain uppercase, lowercase, and digit | Basic security baseline |
| UA-07 | Token revocation on logout | Session termination |
| UA-08 | CachedTokenAuth middleware for performance | Reduced DB load on every authenticated request |

### 4.12 Supplier Management (v1)

**Priority**: P1

| ID | Requirement | Rationale |
|----|-------------|-----------|
| SU-01 | Supplier CRUD with: name, contact_person, phone, email, address, notes | Vendor database |
| SU-02 | Products reference supplier via nullable FK | Product-to-supplier traceability |
| SU-03 | Cannot delete supplier if referenced by products | Referential integrity |
| SU-04 | Paginated listing | Scalable supplier management |
| SU-05 | Store-scoped suppliers | Per-store supplier relationships |

### 4.13 System Operations (v1)

**Priority**: P0

| ID | Requirement | Rationale |
|----|-------------|-----------|
| SY-01 | Database backup: driver-aware (pg_dump for PostgreSQL, mysqldump for MySQL, file copy for SQLite) | Disaster recovery |
| SY-02 | Backup create dispatches CreateBackupJob to queue | Non-blocking backup creation |
| SY-03 | Backup list: enumerates backup files by prefix | Backup inventory |
| SY-04 | Backup download: filename sanitized via basename() to prevent path traversal | Secure download |
| SY-05 | Backup operations restricted to root role | Security boundary |
| SY-06 | Audit logging: all model creation, updates, and deletions logged with user_id, action, old/new values, IP | Compliance and troubleshooting |
| SY-07 | Audit log endpoint: paginated with action filter (root only) | Incident investigation |

---

## 5. Non-Functional Requirements

### 5.1 Performance

| ID | Requirement | Target |
|----|-------------|--------|
| NF-P01 | API response time (p95) for storefront product listing | <500ms for 50 products/page |
| NF-P02 | API response time (p95) for product detail | <300ms |
| NF-P03 | Checkout transaction completion time | <3 seconds |
| NF-P04 | CSV import throughput | 1,000 products/minute |
| NF-P05 | Concurrent users supported per store | 100 simultaneous customers, 20 simultaneous staff |
| NF-P06 | Database query optimization: N+1 prevention via eager loading | Zero N+1 queries in production endpoints |

### 5.2 Security

| ID | Requirement | Target |
|----|-------------|--------|
| NF-S01 | All API endpoints over HTTPS | Mandatory |
| NF-S02 | Token-based authentication with expiry (24h staff, 7d customer) | Enforced at middleware |
| NF-S03 | Rate limiting: auth endpoints 10/min, general API 60/min, checkout 10/min | Abuse prevention |
| NF-S04 | Input validation on all endpoints via FormRequest classes | Injection prevention |
| NF-S05 | Password hashing via Laravel's bcrypt with automatic rehashing | Password storage security |
| NF-S06 | Backup download path traversal prevention | Filesystem security |
| NF-S07 | CORS configuration per environment | Cross-origin security |
| NF-S08 | No secrets or keys in codebase; all credentials via `.env` | Secret management |

### 5.3 Reliability & Availability

| ID | Requirement | Target |
|----|-------------|--------|
| NF-R01 | Checkout idempotency: duplicate requests do not create duplicate orders | 100% guarantee |
| NF-R02 | Stock operations are atomic: concurrent decrements never oversell | Row-level locking |
| NF-R03 | Failed jobs (import, backup) logged to failed_jobs table with retry capability | Queue resilience |
| NF-R04 | Database backups runnable without application downtime | Non-blocking |

### 5.4 Maintainability

| ID | Requirement | Target |
|----|-------------|--------|
| NF-M01 | Modular monolith: 14 domain modules with clear boundaries | Isolated change impact |
| NF-M02 | Service layer pattern: business logic in dedicated service classes, not controllers | Testable, reusable logic |
| NF-M03 | Repository pattern: data access isolated from business logic | Swappable data sources |
| NF-M04 | Shared traits for cross-cutting concerns (ApiResponse, StoreScope, QueryFilter) | DRY principle |
| NF-M05 | All domain string values backed by PHP enums | Type safety, refactoring safety |
| NF-M06 | Code style enforced via Laravel Pint | Consistent codebase |
| NF-M07 | Test coverage for all API endpoints and service classes | Regression prevention |

### 5.5 Compatibility

| ID | Requirement | Target |
|----|-------------|--------|
| NF-C01 | PHP 8.4+ | Required runtime |
| NF-C02 | PostgreSQL 16+ (production), SQLite in-memory (testing) | Supported databases |
| NF-C03 | RESTful JSON API consumed by Vue 3 SPA and Nuxt 4 SSR frontends | Client compatibility |
| NF-C04 | Sanctum token auth compatible with browser-based and mobile clients | Client diversity |

---

## 6. Localization & Accessibility (v1)

### 6.1 Myanmar Localization

| ID | Requirement | Details |
|----|-------------|---------|
| LZ-01 | Dual-language UI: English and Myanmar (Burmese) | All labels, error messages, validation feedback |
| LZ-02 | MMK currency display with Myanmar numeral option | Format: ၁၂,၃၄၅ ကျပ် (12345 Kyats) |
| LZ-03 | Myanmar address format support | Street, Ward, Township, City, State/Region |
| LZ-04 | RTL-friendly layout for Myanmar text | Proper text alignment for Burmese content |
| LZ-05 | Server-side error messages in both languages | API returns locale-appropriate messages based on Accept-Language header |
| LZ-06 | Date/time format preference: Buddhist calendar option (၁၃၈၈ ခုနှစ်) | Cultural calendar support |

### 6.2 Accessibility (v2)

| ID | Requirement |
|----|-------------|
| AC-01 | WCAG 2.1 Level AA compliance for customer storefront |
| AC-02 | Keyboard-navigable POS interface |
| AC-03 | Screen reader support for critical flows (checkout, login) |
| AC-04 | Color contrast ratios meet minimum 4.5:1 for text |

---

## 7. Technical Architecture Requirements

### 7.1 Backend Stack

| Component | Technology | Version |
|-----------|-----------|---------|
| Runtime | PHP | 8.4+ |
| Framework | Laravel | 13 |
| Database | PostgreSQL | 16+ |
| Auth | Laravel Sanctum + Socialite (Google OAuth) | 4.x / 5.x |
| PDF | barryvdh/laravel-dompdf | Latest stable |
| Queue | Database driver (jobs table) | — |
| Testing | PHPUnit | 12 |
| Code Style | Laravel Pint | 1.x |
| Monitoring | Laravel Nightwatch | 1.x |

### 7.2 Frontend Stack (Separate Repositories)

| Component | Technology | Purpose |
|-----------|-----------|---------|
| Staff Dashboard | Vue 3 + TypeScript + Vite | Administrative SPA |
| UI Library | Shadcn/vue + Tailwind CSS v4 | Component system |
| State Management | Pinia | Dashboard state |
| Charts | Chart.js + vue-chartjs | Reports visualization |
| i18n | vue-i18n | EN/MY translations |
| Storefronts | Nuxt 4 SSR (one repo per store) | Public-facing e-commerce |

### 7.3 API Design Principles

| Principle | Implementation |
|-----------|---------------|
| RESTful resource naming | `/products`, `/orders/{id}`, `/storefront/products` |
| Consistent response envelope | `{ data: ... }` for single resources, `{ data: [...], meta: {...} }` for paginated |
| Status codes | 200 (success), 201 (created), 401 (unauthenticated), 403 (forbidden), 404 (not found), 422 (validation), 429 (rate limited), 500 (server error) |
| Header-based store scoping | `X-Store: {slug}` on all requests |
| Bearer token auth | `Authorization: Bearer {token}` |
| Rate limiting | Per-endpoint group: auth=10/min, api=60/min, checkout=10/min |

---

## 8. Release Plan & Roadmap

### 8.1 Version 1.0 — "Online Retail" (MVP)

**Scope**: E-commerce storefront + customer portal + staff dashboard (admin/catalog/inventory/reporting)

| Feature Area | Status | Notes |
|-------------|--------|-------|
| Multi-store infrastructure | Complete | 14 modules, 8 store-scoped tables |
| Product catalog (CRUD + CSV + images) | Complete | Includes brands, categories with hierarchy |
| Inventory & stock movements | Complete | Atomic stock, audit trail |
| Category hierarchy | Complete | Self-referencing parent_id |
| Brand management | Complete | Logo upload, store scoped |
| Supplier management | Complete | Store scoped |
| Customer auth + profile + addresses | Complete | Sanctum + Google OAuth |
| Server-side cart | Complete | Stock-validated, per-customer |
| COD checkout | Complete | Transactional, idempotent |
| Customer order history + cancellation | Complete | Processing-only cancel with restock |
| Wishlist | Complete | Toggle-based |
| Shipments | Complete | Address-linked, status tracking |
| Invoices (PDF/receipt/print) | Complete | Sequential numbering |
| Dashboard + reports | Complete | Sales, best-sellers, payment methods |
| User management + RBAC | Complete | 3 roles, middleware-enforced |
| Audit logging | Complete | All model changes tracked |
| Backup system | Complete | Driver-aware |
| Myanmar localization (server-side) | Partial | EN/MY error translations exist; full localization pending |
| Rate limiting + idempotency | Complete | Checkout safety |
| Tests | Complete | 147 tests passing |

### 8.2 Version 1.1 — "Mobile POS" (Next Release)

| Feature | Priority | Dependencies |
|---------|----------|--------------|
| POS order creation flow in dashboard | P0 | Cash sessions |
| Barcode scanner integration (SKU lookup) | P0 | Variant SKU endpoint (exists) |
| Cash session management (open/close/reconcile) | P0 | Cash module (backend exists) |
| Thermal receipt printing | P1 | Invoice module (backend exists) |
| Offline-capable POS mode | P2 | Service worker, local storage sync |

### 8.3 Version 1.2 — "Payment Gateways" (Next Quarter)

| Feature | Priority | Dependencies |
|---------|----------|--------------|
| KBZ Pay integration | P0 | Partner API access, sandbox environment |
| Wave Money integration | P0 | Partner API access, sandbox environment |
| Payment webhook handling | P0 | Order status automation |
| Refund processing via gateways | P1 | Gateway refund APIs |

### 8.4 Version 2.0 — "Enterprise" (Future)

| Feature | Priority | Dependencies |
|---------|----------|--------------|
| Plain products (without variants) | P0 | Catalog module extension |
| Advanced discount rules (tiered, coupon codes) | P1 | Promotion module extension |
| Multi-currency support | P1 | Store settings extension |
| Email notifications (order confirmation, shipping update) | P1 | Mail service integration |
| SMS notifications (Myanmar carriers) | P2 | SMS gateway integration |
| Inventory forecasting | P2 | Historical data analysis |
| Customer loyalty program | P2 | Points tracking (field exists) |
| API versioning strategy | P2 | Breaking change management |

---

## 9. Assumptions, Risks & Open Questions

### 9.1 Assumptions

| ID | Assumption | Impact if Wrong |
|----|-----------|-----------------|
| AS-01 | Retailers have reliable internet for cloud-hosted storefronts | POS must support offline mode |
| AS-02 | PostgreSQL 16+ is available on target hosting environments | MySQL compatibility needed |
| AS-03 | Staff have basic computer literacy (web browser, form input) | Training material requirements increase |
| AS-04 | COD remains dominant payment method in Myanmar through 2026 | Payment gateway integration can be deferred |
| AS-05 | Google OAuth is acceptable for customer social login | Alternative providers needed |
| AS-06 | 147 tests provide sufficient coverage for MVP launch | Additional edge case testing needed |

### 9.2 Risks

| ID | Risk | Likelihood | Impact | Mitigation |
|----|------|-----------|--------|------------|
| RK-01 | **Regulatory uncertainty**: Myanmar financial/data protection regulations may impose requirements not currently addressed | Medium | High | Conduct legal review before production launch; design for audit trail extensibility |
| RK-02 | **Self-hosting complexity**: Mid-market retailers may lack technical expertise to self-host | High | Medium | Provide Docker-based deployment, managed hosting partnership option |
| RK-03 | **Payment gateway API changes**: KBZ Pay / Wave Money may change APIs during integration | Medium | Medium | Abstract payment interface; integration-specific adapters |
| RK-04 | **Category hierarchy depth**: Unbounded nesting could cause performance issues on storefront | Low | Medium | Limit to 3 levels; cache category trees |
| RK-05 | **Stock consistency under load**: Concurrent checkout may expose race conditions beyond row-locking | Low | High | Load testing before launch; queue-based stock reservation |
| RK-06 | **OAuth dependency**: Google OAuth outage blocks customer login for OAuth-only users | Low | Medium | Offer email/password as fallback; consider password setup flow for OAuth users |
| RK-07 | **Myanmar localization gaps**: Font rendering, numeral conversion, calendar conversion may have edge cases | Medium | Medium | User testing with native speakers; iterative improvements |

### 9.3 Open Questions

| ID | Question | Status | Owner |
|----|----------|--------|-------|
| OQ-01 | What are the specific Myanmar financial/tax reporting requirements? | Unanswered | Legal review needed |
| OQ-02 | Which third-party integrations are required (email service, SMS gateway, shipping calculators, accounting exports)? | Unanswered | Product decision needed |
| OQ-03 | What is the target hosting environment (bare metal, VPS, Docker) and expected infrastructure cost? | Unanswered | Infrastructure decision needed |
| OQ-04 | Should the platform support MySQL/MariaDB in addition to PostgreSQL? | Unanswered | Engineering decision pending |
| OQ-05 | What is the browser support matrix for the dashboard and storefront? | Unanswered | Product decision needed |
| OQ-06 | Is there a need for a mobile app (React Native/Flutter) or is responsive web sufficient? | Unanswered | Product decision needed |
| OQ-07 | What level of uptime SLA is expected for production deployments? | Unanswered | Business decision needed |

---

## 10. Glossary

| Term | Definition |
|------|-----------|
| **COD** | Cash on Delivery — payment collected when goods are delivered |
| **MMK** | Myanmar Kyat — official currency of Myanmar |
| **POS** | Point of Sale — in-store checkout system |
| **SKU** | Stock Keeping Unit — unique identifier for a product variant |
| **Sanctum** | Laravel's lightweight API token authentication package |
| **Socialite** | Laravel's OAuth authentication package |
| **RBAC** | Role-Based Access Control — permission system based on user roles |
| **Nuxt SSR** | Nuxt.js Server-Side Rendering — generates HTML on the server for SEO and performance |
| **SPA** | Single Page Application — client-side rendered web app (dashboard) |
| **Modular Monolith** | Single deployable application with strict domain module boundaries |
| **Store Scoping** | Filtering data by store_id to isolate multi-tenant data |
| **Idempotency** | Property where multiple identical requests produce the same result as a single request |
| **Row Lock** | Database mechanism preventing concurrent modifications to the same row |
| **Shadcn/vue** | Vue component library built on Radix Vue primitives with Tailwind styling |
| **Pint** | Laravel's PHP code style fixer (wrapper around PHP-CS-Fixer) |
| **Nightwatch** | Laravel's application monitoring and observability platform |

---

## 11. Document Control

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2026-06-11 | SimpCommerce Team | Initial PRD derived from codebase analysis |
| — | — | — | Awaiting review and sign-off |

**Review Cadence**: This PRD should be reviewed at each major version boundary (v1.0, v1.1, v2.0) and updated as open questions are resolved.

**Approval Signatures**:

| Role | Name | Date | Signature |
|------|------|------|-----------|
| Product Owner | — | — | — |
| Technical Lead | — | — | — |
| Stakeholder | — | — | — |
