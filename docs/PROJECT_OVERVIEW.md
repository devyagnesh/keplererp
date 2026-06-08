# KeplerERP — Complete Project Overview & Client Demo Guide

> **On-premise Manufacturing ERP** built with Laravel 13, designed for Indian manufacturing companies.
> Covers the full business cycle: Procurement → Production → Sales → Finance → HR.

---

## Table of Contents

1. [System Architecture](#1-system-architecture)
2. [Authentication & User Roles](#2-authentication--user-roles)
3. [Company Setup](#3-company-setup)
4. [Master Data — Vendors, Customers, Items, Warehouses](#4-master-data)
5. [Purchase Module](#5-purchase-module)
6. [Inventory Module](#6-inventory-module)
7. [Sales Module](#7-sales-module)
8. [Production Module](#8-production-module)
9. [Finance Module](#9-finance-module)
10. [HR Module](#10-hr-module)
11. [Reports](#11-reports)
12. [WhatsApp Notifications](#12-whatsapp-notifications)
13. [End-to-End Business Flow Example](#13-end-to-end-business-flow-example)
14. [Permission Reference](#14-permission-reference)

---

## 1. System Architecture

```
Browser ──► Laravel (routes/web.php)
               │
               ├── Auth Middleware (Spatie Roles & Permissions)
               ├── Controllers (app/Http/Controllers/Admin/*)
               ├── Services   (app/Services/*)        ← Business logic
               ├── Models     (app/Models/*)           ← Eloquent ORM
               └── Blade Views (resources/views/admin/*)
```

**Key technologies:**
| Layer | Technology |
|---|---|
| Backend | PHP 8.4 + Laravel 13 |
| Frontend | Bootstrap 5 + Vanilla JS |
| Database | MySQL (via Eloquent ORM) |
| Permissions | Spatie Laravel Permission |
| GST Calculation | `GstCalculationService` (CGST/SGST/IGST split) |
| Notifications | WhatsApp API (`WhatsAppNotificationService`) |
| Document IDs | Auto-sequential `SO-0001`, `PO-0001`, `INV-0001` |

---

## 2. Authentication & User Roles

### How Login Works

1. User visits `/login` → sees the login form (col-md-4, centred).
2. Enters **Email + Password** → rate-limited to 5 attempts/minute.
3. On success → redirected to `/admin` which checks their **highest permission** and lands them on the correct first page.

**Sample login credentials (demo):**

| Role | Email | Password |
|---|---|---|
| Super Admin | `admin@keplertools.com` | `secret` |
| Purchase Manager | `purchase@keplertools.com` | `secret` |
| Sales Executive | `sales@keplertools.com` | `secret` |
| HR Manager | `hr@keplertools.com` | `secret` |
| Finance Officer | `finance@keplertools.com` | `secret` |

### Role-Based Redirect Logic

After login, the system auto-redirects based on what the user can see:

```
company.view       → Company Settings page
vendors.view       → Vendors list
customers.view     → Customers list
inventory.view     → Warehouses list
purchase.pr.create → Purchase Requisitions
sales.order.create → Sales Orders
production.bom.create → Bill of Materials
finance.voucher.create → Finance Vouchers
hr.employee.manage → Employees
```

### User Fields

| Field | Example | Purpose |
|---|---|---|
| Name | Rahul Sharma | Display name |
| Email | rahul@keplertools.com | Login credential |
| Phone | 9876543210 | Contact |
| WhatsApp Number | 9876543210 | Receives PO/approval alerts |
| Warehouse | WH-MAIN | Restricts user to a specific warehouse |
| Employee Link | EMP-001 | Connects to HR employee record |

---

## 3. Company Setup

**Path:** `/admin/company` (requires `company.view` permission)

This is the **global settings** page. Everything downstream inherits from here.

### Sample Company Data

| Field | Example Value |
|---|---|
| Company Name | Kepler Tools Pvt. Ltd. |
| Legal Name | Kepler Tools Private Limited |
| GSTIN | 27AABCK1234M1Z5 |
| PAN | AABCK1234M |
| Address | Plot 12, MIDC, Pune, Maharashtra |
| State Code | 27 (Maharashtra) |
| Currency | INR |
| Invoice Prefix | INV- |
| PO Prefix | PO- |
| Default Tax Type | CGST+SGST (intra-state) |
| PO Approval Threshold | ₹1,00,000 |
| WhatsApp Enabled | Yes |

> **What `po_approval_threshold` does:** If a Purchase Order total exceeds ₹1,00,000, it goes to `pending_finance` status and requires a Finance Officer to approve it separately — even after the Purchase Manager approves it.

---

## 4. Master Data

### 4.1 Vendors

**Path:** `/admin/vendors`

Vendors are suppliers from whom the company buys raw materials.

**Lifecycle statuses:** `pending_approval` → `active` → `blocked`

| Field | Example |
|---|---|
| Vendor Code | VEN-0001 |
| Name | Apex Steel Suppliers |
| GSTIN | 27AAPCS1234B1Z3 |
| State Code | 27 |
| Payment Terms | 30 days |
| WhatsApp | 9811223344 |
| Status | active |

**Demo scenario:** When you click **Block** on a vendor, their status changes to `blocked` and no new POs can be raised against them.

---

### 4.2 Customers

**Path:** `/admin/customers`

Customers are buyers to whom the company sells finished goods.

| Field | Example |
|---|---|
| Name | Bharat Auto Parts Ltd. |
| GSTIN | 07AABCB9876K1ZP |
| State Code | 07 (Delhi — triggers IGST) |
| Payment Terms | 45 days |
| Credit Limit | ₹5,00,000 |
| Status | active |

> **GST Logic:** If the customer's state code (07 Delhi) ≠ company state code (27 Maharashtra), the system automatically applies **IGST** instead of CGST+SGST.

---

### 4.3 Items (Products/Materials)

**Path:** `/admin/items`

Items are both raw materials and finished goods.

| Field | Example |
|---|---|
| SKU | SKU-BOLT-M10 |
| Name | M10 Steel Bolt |
| Unit | PCS |
| GST Rate | 18% |
| Cost Price | ₹12.00 |
| Sale Price | ₹20.00 |
| Is Active | Yes |

---

### 4.4 Warehouses

**Path:** `/admin/warehouses`

| Field | Example |
|---|---|
| Code | WH-MAIN |
| Name | Main Production Store |
| Location | Pune Plant |
| Is Active | Yes |

Multiple warehouses are supported. Stock is tracked **per item per warehouse**.

---

## 5. Purchase Module

The purchase module follows a strict 4-step flow:

```
Purchase Requisition (PR)
        ↓
Purchase Order (PO)  ←── optional: linked to PR
        ↓
[Finance Approval if PO > threshold]
        ↓
Goods Receipt Note (GRN)  ←── posts stock into warehouse
```

---

### 5.1 Purchase Requisition (PR)

**Path:** `/admin/purchase/requisitions`

A PR is an internal request to buy something. Raised by a department and approved by a manager.

**Statuses:** `draft` → `submitted` → `approved` → `rejected` → `converted`

**Demo — Creating a PR:**

> 1. Click **New Requisition**
> 2. Select **Warehouse:** WH-MAIN
> 3. Add line: Item = `M10 Steel Bolt`, Qty = 500
> 4. Add line: Item = `M8 Hex Nut`, Qty = 1000
> 5. Click **Save** → status becomes `draft`
> 6. Click **Submit** → status becomes `submitted`
> 7. A manager clicks **Approve** → status becomes `approved`

**What happens when Approved:**
- PR is now available to be linked when creating a Purchase Order.

---

### 5.2 Purchase Order (PO)

**Path:** `/admin/purchase/orders`

**Statuses:** `draft` → `approved` → `pending_finance` → `sent` → (GRN created)

**Demo — Creating a PO:**

> 1. Click **New Purchase Order**
> 2. **Link PR:** Select `PR-0001` (auto-fills items)
> 3. **Vendor:** Apex Steel Suppliers (VEN-0001)
> 4. **Warehouse:** WH-MAIN
> 5. **Order Date:** 2025-05-10
> 6. **Expected Delivery:** 2025-05-20
> 7. **Payment Terms:** 30 days
> 8. Line 1: M10 Steel Bolt × 500 @ ₹12 = ₹6,000 + 18% GST
> 9. Line 2: M8 Hex Nut × 1000 @ ₹5 = ₹5,000 + 18% GST
> 10. **Total = ₹13,570** (after GST)
> 11. Save → PO-0001 created with status `draft`

**GST breakdown example (intra-state):**

| Item | Taxable | CGST 9% | SGST 9% | Line Total |
|---|---|---|---|---|
| M10 Steel Bolt 500 pcs | ₹6,000 | ₹540 | ₹540 | ₹7,080 |
| M8 Hex Nut 1000 pcs | ₹5,000 | ₹450 | ₹450 | ₹5,900 |
| **Total** | **₹11,000** | **₹990** | **₹990** | **₹12,980** |

**Approval flow:**

- Another user (not the creator — **four-eyes policy**) clicks **Approve**
- If total ≤ ₹1,00,000 → status = `approved` directly
- If total > ₹1,00,000 → status = `pending_finance`; Finance Officer must also **Finance Approve**
- After approval → **Mark Sent** sends WhatsApp alert to vendor
- PR-0001 status automatically becomes `converted`

---

### 5.3 Goods Receipt Note (GRN)

**Path:** `/admin/purchase/grns`

A GRN records physical receipt of goods from the vendor and **posts stock into the warehouse**.

**Demo — Creating a GRN:**

> 1. Click **New GRN**
> 2. **Link PO:** PO-0001
> 3. **Vendor:** Apex Steel Suppliers
> 4. **Warehouse:** WH-MAIN
> 5. **Received At:** 2025-05-19
> 6. Enter quantities actually received:
>    - M10 Steel Bolt: 500 pcs ✓
>    - M8 Hex Nut: 980 pcs (short delivery of 20)
> 7. Save → GRN-0001 created and **stock balances updated**

**Result in Inventory Balances:**

| Item | Warehouse | Qty On Hand |
|---|---|---|
| M10 Steel Bolt | WH-MAIN | 500 |
| M8 Hex Nut | WH-MAIN | 980 |

---

## 6. Inventory Module

**Paths:**
- `/admin/warehouses` — manage warehouses
- `/admin/items` — manage items
- `/admin/inventory/balances` — view current stock
- `/admin/inventory/adjust` — manual stock adjustment
- `/admin/inventory/transfer` — transfer between warehouses

### Stock Adjustment (Demo)

> **Scenario:** Physical count found 10 extra bolts not recorded.
> 1. Go to **Inventory → Adjust Stock**
> 2. Select **Warehouse:** WH-MAIN, **Item:** M10 Steel Bolt
> 3. **Adjustment Type:** Increase
> 4. **Quantity:** 10
> 5. **Notes:** Physical count surplus
> 6. Submit → balance becomes 510

### Stock Transfer (Demo)

> **Scenario:** Move 100 bolts from Main Store to Secondary Store.
> 1. Go to **Inventory → Transfer Stock**
> 2. **From Warehouse:** WH-MAIN
> 3. **To Warehouse:** WH-SECONDARY
> 4. **Item:** M10 Steel Bolt, **Qty:** 100
> 5. Submit → WH-MAIN: 410, WH-SECONDARY: 100

### Stock Reservation

When a **Sales Order is confirmed**, the system automatically **reserves stock** (reduces available qty) so the same stock cannot be sold twice. Stock is only physically deducted when the order is **Dispatched**.

---

## 7. Sales Module

```
Sales Quotation (optional)
        ↓
Sales Order  ←── stock reserved on creation
        ↓
Dispatch     ←── stock physically deducted
        ↓
Invoice      ←── accounting entry posted
```

### 7.1 Sales Quotation

**Path:** `/admin/sales/quotations`

A price quote sent to a customer before they commit to buying.

**Demo:**

> 1. Click **New Quotation**
> 2. **Customer:** Bharat Auto Parts Ltd. (Delhi — IGST applies)
> 3. Add Line: Item = `Finished Gear Assembly`, Qty = 50, Price = ₹800
> 4. System calculates IGST 18% = ₹7,200
> 5. **Total = ₹47,200**
> 6. Save → SQ-0001 created

---

### 7.2 Sales Order

**Path:** `/admin/sales/orders`

**Statuses:** `confirmed` → `dispatched` → (invoiced)

**Demo — Creating a Sales Order:**

> 1. Click **New Sales Order**
> 2. **Customer:** Bharat Auto Parts Ltd.
> 3. **Warehouse:** WH-MAIN (stock will be reserved from here)
> 4. **Order Date:** 2025-05-12
> 5. **Expected Dispatch:** 2025-05-18
> 6. **Payment Terms:** 45 days
> 7. Line: `Finished Gear Assembly` × 50 @ ₹800
>
> **GST (inter-state customer — Delhi):**
>
> | Taxable | IGST 18% | Total |
> |---|---|---|
> | ₹40,000 | ₹7,200 | ₹47,200 |
>
> 8. Save → SO-0001 created, status = `confirmed`
> 9. **Stock reserved:** 50 units of Finished Gear Assembly

**Dispatching:**

> - Click **Dispatch** on SO-0001
> - System deducts 50 units from WH-MAIN inventory
> - `dispatched_at` timestamp recorded
> - Status → `dispatched`

**Generating Invoice:**

> - Click **Invoice** on SO-0001
> - INV-0001 is created and posted
> - Accounting journal entry automatically created:
>   - Dr: Accounts Receivable ₹47,200
>   - Cr: Sales Revenue ₹40,000
>   - Cr: IGST Payable ₹7,200

---

## 8. Production Module

```
Bill of Materials (BOM)  ←── recipe for a finished good
        ↓
Production Work Order  ←── plan to manufacture N units
        ↓
Log Actual Output      ←── update actual_qty produced
```

### 8.1 Bill of Materials (BOM)

**Path:** `/admin/production/boms`

A BOM defines what raw materials are needed to produce one unit of a finished good.

**Demo — BOM for "Finished Gear Assembly":**

| Component | Qty Per Unit |
|---|---|
| M10 Steel Bolt | 4 pcs |
| M8 Hex Nut | 8 pcs |
| Gear Disc (raw) | 1 pcs |
| Lubricant Oil | 0.05 litres |

> If you create a BOM version 2 later (design change), the old version is kept for traceability. Only the active version is used for new work orders.

---

### 8.2 Production Work Order

**Path:** `/admin/production/work-orders`

**Statuses:** `draft` → `in_progress` → `completed`

**Demo:**

> 1. Click **New Work Order**
> 2. **Finished Item:** Finished Gear Assembly
> 3. **BOM:** BOM v1 (active)
> 4. **Planned Qty:** 100 units
> 5. **Warehouse:** WH-MAIN
> 6. **Planned Start:** 2025-05-14, **Planned End:** 2025-05-16
> 7. Save → WO-0001 created
>
> **Raw material requirement auto-calculated:**
> - M10 Steel Bolt: 400 pcs
> - M8 Hex Nut: 800 pcs
> - Gear Disc (raw): 100 pcs
>
> 8. When production completes, update **Actual Qty:** 98 units (2 scrapped)
> 9. Status → `completed`

---

## 9. Finance Module

### 9.1 Journal Vouchers

**Path:** `/admin/finance/vouchers`

Manual double-entry accounting entries.

**Statuses:** `draft` → `posted`

**Demo — recording a bank payment:**

> 1. Click **New Voucher**
> 2. **Voucher Date:** 2025-05-15
> 3. **Narration:** Payment to Apex Steel Suppliers for PO-0001
> 4. Lines:
>    - Dr: Accounts Payable — ₹12,980
>    - Cr: Bank Account — ₹12,980
> 5. Save → JV-0001 (draft)
> 6. Click **Post** → status becomes `posted`, entries locked

**Validation:** The system enforces that **debits = credits** before posting.

---

### 9.2 Auto-Generated Accounting Entries

The system auto-creates journal entries for:

| Event | Debit | Credit |
|---|---|---|
| GRN posted | Stock / Inventory | Goods Receipt Clearing |
| Sales Invoice posted | Accounts Receivable | Sales Revenue + Tax |
| Payroll processed | Salaries Expense | Payroll Payable |

---

## 10. HR Module

### 10.1 Employees

**Path:** `/admin/hr/employees`

| Field | Example |
|---|---|
| Employee Code | EMP-0001 |
| Name | Rohit Verma |
| Department | Production |
| Designation | Line Operator |
| Join Date | 2023-01-15 |
| Linked User | rohit@keplertools.com |

---

### 10.2 Attendance

**Path:** `/admin/hr/attendance`

Mark daily attendance for employees.

**Demo:**

> - Select Date: 2025-05-10
> - Employee: Rohit Verma → Present ✓
> - Employee: Priya Singh → Absent ✗
> - Employee: Amitesh Kumar → Half Day
> - Save → records stored for payroll calculation

---

### 10.3 Payroll

**Path:** `/admin/hr/payroll-runs`

**Statuses:** `pending` → `processed`

**Demo — Running May 2025 Payroll:**

> 1. Click **New Payroll Run**
> 2. **Period:** May 2025
> 3. Save → status = `pending`
> 4. Click **Process** → system calculates salary based on:
>    - Days present in attendance records
>    - Employee salary configuration
> 5. Status → `processed`, `processed_at` timestamp recorded
> 6. Accounting entry auto-posted:
>    - Dr: Salaries Expense
>    - Cr: Payroll Payable

---

## 11. Reports

**Path:** `/admin/reports`

The reports dashboard aggregates data across all modules. Access is controlled by individual permissions:

| Report | Permission | What It Shows |
|---|---|---|
| Sales Report | `reports.sales` | Orders, invoices, revenue by period |
| Purchase Report | `reports.purchase` | POs, GRNs, vendor spend |
| Inventory Report | `reports.inventory` | Stock levels, movements, slow-moving items |
| Finance Report | `reports.finance` | Journal entries, P&L summary |
| HR Report | `hr.employee.manage` | Headcount, attendance summary |

---

## 12. WhatsApp Notifications

The system sends automatic WhatsApp messages to users and vendors at key events:

| Trigger | Recipient | Message |
|---|---|---|
| PO approved | PO Creator | "Your PO-0001 has been approved." |
| PO requires finance approval | PO Creator | "PO-0001 is pending finance approval." |
| PO rejected | PO Creator | "PO-0001 rejected. Reason: Budget exceeded." |
| PO marked sent | Vendor (via vendor WhatsApp) | "PO-0001 dispatched to vendor." |

> WhatsApp notifications can be toggled per company via **Company Settings → WhatsApp Enabled**.

---

## 13. End-to-End Business Flow Example

> **Scenario:** Kepler Tools receives an order for 50 Gear Assemblies from Bharat Auto Parts.

### Step-by-Step

```
1. SALES
   └── Create Sales Order SO-0001
       Customer: Bharat Auto Parts (Delhi)
       Item: Finished Gear Assembly × 50 @ ₹800
       Total: ₹47,200 (IGST 18%)
       → Stock reserved: 50 units

2. PRODUCTION (if stock insufficient)
   └── Check Inventory → only 10 units available
   └── Create Work Order WO-0001
       Planned: 50 units of Finished Gear Assembly
       BOM: 200 bolts, 400 nuts, 50 gear discs needed

3. PURCHASE (raw materials short)
   └── Create PR-0001: 200 bolts, 400 nuts
   └── PR approved by manager
   └── Create PO-0001 from PR-0001
       Vendor: Apex Steel Suppliers
       Total: ₹8,260 (under ₹1L threshold → direct approval)
   └── WhatsApp sent to vendor
   └── Vendor delivers → GRN-0001 posted
       → Stock updated: +200 bolts, +400 nuts

4. PRODUCTION COMPLETE
   └── WO-0001 updated: Actual Qty = 50
   └── Finished Gear Assembly stock: 60 units (10 old + 50 new)

5. DISPATCH
   └── Click Dispatch on SO-0001
   └── 50 units deducted from WH-MAIN
   └── Remaining stock: 10 units

6. INVOICE
   └── Click Invoice on SO-0001
   └── INV-0001 created and posted
   └── Journal entry:
       Dr Accounts Receivable ₹47,200
       Cr Sales Revenue       ₹40,000
       Cr IGST Payable         ₹7,200

7. PAYMENT (Finance)
   └── Create Journal Voucher JV-0001
       Dr Accounts Receivable ₹47,200
       Cr Bank Account        ₹47,200
   └── Post voucher → transaction complete ✓
```

---

## 14. Permission Reference

| Permission | What It Unlocks |
|---|---|
| `company.view` / `company.edit` | View/edit company settings |
| `users.view` / `users.create` / `users.edit` / `users.delete` | User management |
| `vendors.view` / `vendors.create` / `vendors.edit` / `vendors.delete` / `vendors.approve` | Vendor management |
| `customers.view` / `customers.create` / `customers.edit` / `customers.delete` | Customer management |
| `inventory.view` | View warehouses, items, stock balances |
| `inventory.adjust` | Create/edit warehouses, items; adjust stock |
| `inventory.transfer` | Transfer stock between warehouses |
| `purchase.pr.create` | Raise, submit, approve/reject PRs |
| `purchase.po.create` | Create POs |
| `purchase.po.approve` | Approve POs |
| `purchase.po.finance_approve` | Finance-approve high-value POs |
| `sales.quotation.create` | Create quotations |
| `sales.order.create` | Create sales orders |
| `sales.dispatch` | Dispatch confirmed orders |
| `sales.invoice.create` | Generate invoices from dispatched orders |
| `production.bom.create` | Create Bill of Materials |
| `production.order.create` | Create Work Orders |
| `production.log` | Update actual output on Work Orders |
| `finance.voucher.create` | Create journal vouchers |
| `finance.reports.view` | View finance reports |
| `hr.employee.manage` | Manage employees |
| `hr.attendance.mark` | Record attendance |
| `hr.payroll.run` | Run payroll |
| `reports.sales` / `reports.purchase` / `reports.inventory` / `reports.finance` | View respective reports |

---

*Generated: May 2026 — KeplerERP v1.0*
