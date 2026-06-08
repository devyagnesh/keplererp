# KeplerERP — Learn the Full System (A to Z)

> **Who is this for?** Anyone who will use or support this software — owners, HR, purchase, sales, warehouse, accounts — even if you are not technical.  
> **How to read it:** Like a teacher explaining one chapter at a time. Use the **story of Kepler Tools** as your mental model.

---

## Table of contents

1. [What is this software?](#1-what-is-this-software)
2. [The big picture — one story](#2-the-big-picture--one-story)
3. [How you log in and what you see](#3-how-you-log-in-and-what-you-see)
4. [Company setup — rules of your business](#4-company-setup--rules-of-your-business)
5. [Users and roles — who can click what](#5-users-and-roles--who-can-click-what)
6. [Master data — people and things you trade](#6-master-data--people-and-things-you-trade)
7. [Inventory — where stock lives](#7-inventory--where-stock-lives)
8. [Purchase — buying from suppliers](#8-purchase--buying-from-suppliers)
9. [Sales — selling to customers](#9-sales--selling-to-customers)
10. [Production — making finished goods](#10-production--making-finished-goods)
11. [Finance — money, GST, and accounts](#11-finance--money-gst-and-accounts)
12. [HR — people, attendance, salary](#12-hr--people-attendance-salary)
13. [Reports and PDFs](#13-reports-and-pdfs)
14. [WhatsApp alerts](#14-whatsapp-alerts)
15. [Vendor portal](#15-vendor-portal)
16. [Batch and serial numbers](#16-batch-and-serial-numbers)
17. [End-to-end day in the life](#17-end-to-end-day-in-the-life)
18. [Quick dictionary](#18-quick-dictionary)

---

## 1. What is this software?

**KeplerERP** is an **ERP** (Enterprise Resource Planning) system for a **manufacturing company in India**.

Think of it as **one notebook for the whole factory and office**:

- What you **buy** (raw materials from vendors)
- What you **store** (warehouses, batches, expiry)
- What you **make** (production / BOM)
- What you **sell** (quotations, orders, invoices)
- What you **owe and collect** (payments, GST)
- Who you **employ** (HR, payroll)

Everything is connected. Example: when goods arrive from a supplier, **stock goes up** and **accounts know you owe money**. When you dispatch to a customer, **stock goes down** and **invoice raises your receivable**.

---

## 2. The big picture — one story

Imagine **Kepler Tools Pvt. Ltd.** in Pune. They make hand tools.

| Real-world thing | In the software |
|------------------|-----------------|
| Supplier / raw material seller | **Vendor** (e.g. `V-1001` — Steel Suppliers Pune) |
| Buyer of your products | **Customer** (e.g. `C-2001` — Mumbai Hardware Mart) |
| Product you sell | **Item** / SKU (e.g. `HAM-500` — Claw Hammer 500g) |
| Steel rods you buy | **Item** / SKU (e.g. `STL-ROD-12` — Steel Rod 12mm) |
| Godown / store | **Warehouse** (e.g. `WH-MAIN` — Main Plant Store) |
| “We need 100 rods” request | **Purchase Requisition (PR)** |
| Official order to vendor | **Purchase Order (PO)** |
| Goods actually received | **Goods Receipt Note (GRN)** |
| Price quote to customer | **Sales Quotation** |
| Customer’s confirmed order | **Sales Order (SO)** |
| Tax invoice | **Invoice** |
| Recipe to build a hammer | **Bill of Materials (BOM)** |
| Factory job to make 50 hammers | **Work Order** |
| Salary run for March | **Payroll Run** |

### Flow diagram (simplified)

```
BUY SIDE                          SELL SIDE
────────                          ─────────
Vendor                            Customer
  ↓                                 ↓
PR (internal need)                Enquiry (optional)
  ↓                                 ↓
PO (order to vendor)              Quotation (price offer)
  ↓                                 ↓
GRN (stock IN)                    Sales Order (stock RESERVED)
  ↓                                 ↓
Pay vendor                        Dispatch (stock OUT)
                                    ↓
                                  Invoice (bill customer)
                                    ↓
                                  Customer payment
```

**Production** sits in the middle: BOM says what raw materials go into one hammer; Work Order consumes steel and puts finished hammers into stock.

---

## 3. How you log in and what you see

### Login

- Open your site (e.g. `https://keplererp.test/login`).
- Enter **email** and **password**.
- After login you land on the first screen your role is allowed to see (not everyone sees the same menu).

**Default demo admin** (from seed data):

| Field | Value |
|-------|--------|
| Email | `admin@gmail.com` |
| Password | `password` |
| Role | Super Admin (full access) |

### The menu (sidebar)

The left menu only shows modules you have **permission** for. If you do not see “Purchase orders”, your role is not allowed — that is normal, not a bug.

### Dashboard

Only **Super Admin** and **Admin** see the main dashboard (counts, low stock, etc.). Other roles go straight to their work area (e.g. HR Manager → Employees).

---

## 4. Company setup — rules of your business

**Menu:** Company setup (from user menu or `/admin/company`)

This is filled **once** (then updated when law or process changes). Almost every document uses this data.

### Important fields (plain English)

| Field | Example | What it means |
|-------|---------|----------------|
| Company name | Kepler Tools Pvt. Ltd. | Name on invoices and PDFs |
| GSTIN | 27AABCK1234M1Z5 | Your GST registration (15 characters) |
| PAN | AABCK1234M | Income tax ID |
| State code | `27` | Maharashtra — used to decide CGST+SGST vs IGST |
| Address | Plot 12, MIDC, Pune | Registered address |
| Invoice prefix | `INV/` | Invoice numbers look like `INV/2026-27/00042` |
| PO prefix | `PO/` | Purchase orders like `PO/2026-27/00015` |
| PO approval threshold | `100000` | POs above ₹1,00,000 need **finance approval** before vendor is told |
| WhatsApp enabled | Yes/No | Turn automated WhatsApp messages on or off |
| e-Invoice enabled | Yes/No | Generate IRN on tax invoices (when configured) |
| Bank name / account / IFSC | HDFC… | Printed on invoices; used for receipts |

**Why state code matters (simple):**

- Customer in **same state** as you → GST splits into **CGST + SGST** (half each).
- Customer in **another state** → **IGST** only.

Example: Kepler Tools (Maharashtra `27`) sells to a shop in Gujarat `24` → IGST on the invoice.

---

## 5. Users and roles — who can click what

A **user** is a login account (email + password). Each user has **one role**.

### Roles in this system

| Role | In simple words |
|------|------------------|
| **Super Admin** | Owner of the system — can do everything |
| **Admin** | Same as Super Admin for daily use |
| **Purchase Manager** | Vendors, PR, PO, GRN, purchase reports |
| **Sales Manager** | Customers, quotes, orders, invoices, dispatch |
| **Warehouse Manager** | Stock, adjust, transfer, GRN, dispatch from warehouse |
| **Accountant** | Vouchers, payments, GSTR, P&L, balance sheet |
| **HR Manager** | Employees, attendance, payroll rules, payroll runs |
| **Production Supervisor** | BOM, work orders, production updates |
| **Staff** | Mostly **read-only reports** (sales + inventory) |
| **Employee** | **Only own payslips** (no admin menu) |

### User form fields

| Field | Example | Purpose |
|-------|---------|---------|
| Name | Priya Patil | Display name |
| Email | priya@keplertools.com | Login |
| Phone | 9876543210 | Contact (10-digit Indian mobile) |
| WhatsApp | 9876543210 | Gets alerts (PO approved, salary credited, etc.) |
| Role | Purchase Manager | **One role only** |
| Active | Yes | Inactive users cannot log in |

---

## 6. Master data — people and things you trade

Master data is created **before** daily transactions. Bad master data = wrong GST, wrong stock, wrong payments.

### 6.1 Vendors (suppliers)

**Menu:** Vendors

| Field | Example | Meaning |
|-------|---------|---------|
| Vendor code | `V-1001` | Your internal ID |
| Name | Steel Suppliers Pune | Company name |
| GSTIN | 27AAAAA0000A1Z5 | Vendor’s GST (for purchase GST) |
| State code | `27` | For GST calculation |
| Phone / email | … | Contact |
| Status | `pending_approval` → `active` | New vendors must be **approved** before use |
| Portal enabled | Yes | Vendor can log in to accept POs and upload invoices |
| Payment terms | 30 days | “Pay within 30 days” — used on PO |
| Credit limit | 500000 | Optional control |

**Statuses:** `pending_approval` → **Approve** → `active`. You can **block** a bad vendor.

### 6.2 Customers

**Menu:** Customers

| Field | Example | Meaning |
|-------|---------|---------|
| Customer code | `C-2001` | Internal ID |
| Name | Mumbai Hardware Mart | Shop / company name |
| GSTIN | 24BBBBB1111B1Z5 | For B2B invoices |
| State code | `24` | Gujarat → IGST from Maharashtra seller |
| Credit limit | 200000 | Max outstanding allowed |
| Credit used | 45000 | System tracks how much is already due |
| Price list | Retail Mumbai | Optional special prices |
| Status | `active` / `blocked` | Blocked customers cannot get new orders |

**Addresses:** A customer can have **shipping addresses** (different cities). The default shipping address drives **place of supply** on invoices.

### 6.3 Items (products & materials)

**Menu:** Items (under Inventory area)

| Field | Example | Meaning |
|-------|---------|---------|
| SKU | `HAM-500` | Stock keeping unit — unique code |
| Name | Claw Hammer 500g | What humans read |
| UOM | `PCS` | Unit: pieces, KG, etc. |
| HSN code | 8205 | Government code for GST returns |
| GST rate | 18 | 18% GST on this item |
| Item type | RAW_MATERIAL / FINISHED | Helps reporting |
| Reorder level | 50 | When stock falls below 50, low-stock alert can fire |
| Batch tracked | Yes/No | Must enter batch on GRN/dispatch |
| Serial tracked | Yes/No | Each unit has unique serial; qty always 1 |
| Active | Yes | Inactive items hidden from new lines |

**Display in dropdowns:** `Claw Hammer 500g (HAM-500)` — name first, SKU in brackets.

### 6.4 Warehouses

| Field | Example | Meaning |
|-------|---------|---------|
| Code | `WH-MAIN` | Short code |
| Name | Main Plant Store | Godown name |
| City | Pune | Location label |
| Active | Yes | Cannot use inactive warehouse on new documents |

### 6.5 Price lists (optional)

**Menu:** Price lists

For customers who do not pay the “default” price. You define a list (e.g. “Dealer West”) and attach **item + unit price**. Customer master links to that list.

---

## 7. Inventory — where stock lives

**Stock** = how many units of an item are in a warehouse **right now**.

### 7.1 Stock balances

**Menu:** Inventory → balances (or stock balances)

Shows:

| Column | Example | Meaning |
|--------|---------|---------|
| Warehouse | WH-MAIN | Where |
| Item | Steel Rod 12mm (STL-ROD-12) | What |
| Qty | 450.0000 | On-hand quantity |

Stock **increases** on GRN, production completion, positive adjustment.  
Stock **decreases** on dispatch, production consumption, transfer out, GRN return, negative adjustment.

### 7.2 Stock adjustment

**Menu:** Adjust stock

Use when physical count does not match system (damage, found extra, correction).

| Field | Meaning |
|-------|---------|
| Warehouse | Which godown |
| Item | What product |
| Qty +/- | +10 adds ten pieces; -2 removes two |
| Batch / serial | Required if item is batch/serial tracked |
| Reason | Note for audit |

### 7.3 Stock transfer

Move stock **from warehouse A to warehouse B** (same item, same company).

### 7.4 Batch traceability

**Menu:** Batch traceability (needs report permission)

For batch items you see:

- **FEFO** — which batches expire first (First Expiry, First Out)
- **Expiry alerts** — expired or expiring within 30 days (configurable)
- **History** — every in/out movement with batch/serial

---

## 8. Purchase — buying from suppliers

### 8.1 Purchase Requisition (PR) — internal request

**Who:** Purchase team (or any role with `purchase.pr.create`)  
**Purpose:** “We need these items” — **not yet** a legal order to vendor.

| Field | Example | Meaning |
|-------|---------|---------|
| PR number | `PR-0007` | Auto-generated |
| Required date | 2026-06-15 | When material is needed |
| Warehouse | WH-MAIN | Where stock should land |
| Notes | For June production | Free text |
| Lines: Item | STL-ROD-12 | What to buy |
| Lines: Qty | 500 | How many |

**Workflow:**

1. **Draft** — you create and edit.
2. **Submit** — sends for approval (`pending_approval`).
3. **Approved** or **Rejected** — approver decides; requester may get WhatsApp.
4. **Convert to PO** — creates a Purchase Order linked to this PR.

You can add **up to 30 lines** (use **Add line**). Empty lines are ignored.

### 8.2 Purchase Order (PO) — order to vendor

| Field | Example | Meaning |
|-------|---------|---------|
| PO number | `PO/2026-27/00015` | Official order number |
| Vendor | V-1001 Steel Suppliers | Who will supply |
| Warehouse | WH-MAIN | Delivery destination |
| Expected delivery | 2026-06-20 | Planning date |
| Payment terms | 30 days | From vendor master or manual |
| Lines: Item, Qty, Unit cost | STL-ROD-12, 500, ₹85 | Before GST |
| Subtotal / CGST / SGST / Total | … | Auto GST from vendor state vs company |

**Statuses (life of a PO):**

| Status | Meaning |
|--------|---------|
| `draft` | Being prepared |
| `pending_finance` | Total above company threshold — finance must approve |
| `approved` | Ready to send to vendor |
| `sent` | Sent to vendor (email/WhatsApp) |
| `accepted` / `rejected` | Vendor responded on portal |

**Rules you should know:**

- Person who **created** PO cannot **approve** it (four-eyes control).
- If PO total > **PO approval threshold** (e.g. ₹1,00,000), it goes to **pending_finance** first.
- **Finance approve** needs Accountant role (`finance.payment.approve`).
- **Mark sent** tells vendor the order is official.

### 8.3 GRN — Goods Receipt Note (stock in)

**When:** Material physically arrives.

| Field | Example | Meaning |
|-------|---------|---------|
| GRN number | `GRN-00023` | Receipt document |
| PO | PO/2026-27/00015 | Usually linked to PO |
| Vendor | V-1001 | Who delivered |
| Warehouse | WH-MAIN | Where you put stock |
| Lines: Accepted qty | 500 | What you actually received (can be less than PO) |
| Batch no | BATCH-2026-06-A | **Required** if item is batch-tracked |
| Serial no | SN-000891 | **Required** if serial-tracked (qty = 1) |
| Expiry date | 2027-05-31 | **Required** for batch items in this system |

**On Post:**

1. Stock **increases**.
2. **Vendor payable** created (you owe money).
3. **Accounting entry** (inventory debit, creditor credit — automatic).
4. WhatsApp may notify purchase/warehouse managers.

### 8.4 GRN return

Return material **back to vendor** (wrong item, damage). Reduces stock and adjusts payable.

### 8.5 Vendor payable & 3-way match

After GRN, finance sees **you owe** the vendor (open payable).

Vendor uploads **tax invoice PDF** on **vendor portal**. System compares:

- PO amount  
- GRN / payable amount  
- Invoice amount  

Result: **matched** or **variance** (someone investigates).

---

## 9. Sales — selling to customers

### 9.1 Sales enquiry (optional)

A **lead**: someone asked price/availability. Not yet a formal quote.

| Field | Example |
|-------|---------|
| Contact name | Raj from Mumbai Hardware |
| Phone | 9123456789 |
| Status | open |

### 9.2 Sales quotation

Formal **price offer**.

| Field | Example | Meaning |
|-------|---------|---------|
| Quote number | `QT-00012` | Reference |
| Customer | C-2001 | Who |
| Valid until | 2026-06-30 | After this date quote can **expire** (nightly job) |
| Lines | HAM-500, 100 pcs, ₹250 | Item, qty, price |
| Status | draft → sent → accepted | Workflow |

**Convert to Sales Order** when customer says yes.

### 9.3 Sales order (SO)

| Field | Example | Meaning |
|-------|---------|---------|
| Order number | `SO-00045` | Customer’s order ref |
| Customer | C-2001 | Buyer |
| Warehouse | WH-MAIN | From which godown you will ship |
| Lines | HAM-500 × 100 | Products and qty |
| Status | confirmed → processing → dispatched | See below |

**What happens on create:**

- System **reserves** stock (so another order cannot steal the same pieces).

**Statuses:**

| Status | Meaning |
|--------|---------|
| `confirmed` | Order booked; stock reserved |
| `processing` | Pick/pack; you can enter courier name & tracking |
| `dispatched` | Stock deducted; dispatch challan created |

**Dispatch (important):**

- Warehouse picks items.
- For **batch** items: choose batch number.
- For **serial** items: choose serial number (one per line).
- Stock goes **out**.
- Customer can get dispatch details.

### 9.4 Invoice (tax invoice)

Created from sales order when ready to bill.

| Field | Example | Meaning |
|-------|---------|---------|
| Invoice number | `INV/2026-27/00088` | Legal invoice no. |
| GST breakdown | CGST/SGST or IGST | From customer state |
| Status | posted → partially_paid → paid | Payment tracking |
| Due date | 2026-07-15 | For overdue WhatsApp |
| IRN / ACK | (if e-invoice on) | Government e-invoice reference |

**On post:** Accounting entry + optional WhatsApp to customer with PDF link.

### 9.5 Credit note

When customer returns goods or you reduce bill after invoice. Adjusts revenue and tax.

### 9.6 Customer payment (receipt)

**Menu:** Finance → Payments

Record money **received** from customer against invoice (UTR, date, amount). Invoice becomes **partially paid** or **paid**.

---

## 10. Production — making finished goods

### 10.1 Bill of Materials (BOM)

**Recipe** for one finished item.

Example — **1× Claw Hammer (HAM-500)** needs:

| Component | Qty per hammer |
|-----------|----------------|
| STL-ROD-12 | 0.5 KG |
| HND-WOOD | 1 PCS |
| PIN-CLR | 2 PCS |

BOM has **version** and **active** flag — only active BOM is used for new work orders.

### 10.2 Work order

**Job:** “Make 100 hammers in WH-MAIN.”

| Field | Example | Meaning |
|-------|---------|---------|
| WO number | `WO-0009` | Job ID |
| Item to produce | HAM-500 | Finished good |
| Qty planned | 100 | Target |
| Warehouse | WH-MAIN | Where finished goods will be stored |
| BOM | v1 | Which recipe |
| Sales order link | SO-00045 | Optional — tied to customer order |
| Status | planned → in_progress → completed | |

**When started (`in_progress`):**

- Raw materials from BOM are **consumed** from stock (reservation/issue).

**When completed:**

- Finished quantity (e.g. 98 hammers if 2 scrapped) **received into stock**.
- WhatsApp may notify production staff.

---

## 11. Finance — money, GST, and accounts

### 11.1 Automatic accounting (you don’t click this — system does)

| Event | Simple accounting effect |
|-------|---------------------------|
| GRN posted | Inventory up, you owe vendor (creditor) |
| Sales invoice posted | Customer owes you (debtor), revenue + GST |
| Payroll processed | Salary expense, bank payment, PF/ESI/PT liabilities |
| Vendor payment | Creditor down, bank down |
| Customer receipt | Bank up, debtor down |

### 11.2 Journal voucher (manual)

**Menu:** Finance → Vouchers

For adjustments not covered by normal screens (e.g. bank charges, corrections).

| Field | Meaning |
|-------|---------|
| Voucher no | `JV-0003` |
| Lines | Account code, debit, credit |
| Status | draft → **posted** when debits = credits |

### 11.3 Payments screen

- **Pay vendor** — select payable, amount, UTR.
- **Receive from customer** — select invoice, amount, UTR.

### 11.4 Chart of accounts

List of ledger accounts (`BANK-MAIN`, `SALARY-EXP`, `PF-PAYABLE`, etc.). Used by reports and vouchers.

### 11.5 GST reports

| Report | Purpose |
|--------|---------|
| GSTR-1 | Outward sales for a month (export CSV/JSON/PDF) |
| GSTR-3B | Summary tax liability |
| GST period lock | Lock a month so nobody backdates invoices |

### 11.6 Financial reports

- **Profit & Loss** — income vs expenses for a date range  
- **Balance sheet** — assets vs liabilities on a date  
- **Vendor statement** — what you owe one vendor  

---

## 12. HR — people, attendance, salary

### 12.1 Employee master

| Field | Example | Meaning |
|-------|---------|---------|
| Emp code | `EMP-014` | HR ID |
| Name | Amit Kumar | |
| Department | PUR | Purchase |
| Designation | MGR | Manager |
| Join date | 2024-04-01 | |
| Basic salary | 25000 | Monthly base for payroll |
| PF number / UAN | … | Provident fund |
| ESI number | … | If applicable |
| Bank account / IFSC | … | Salary transfer |
| PF opted in | Yes | Affects PF deduction |
| User link | (optional) | Login for payslip portal |
| Allowances | HRA ₹5000, Conveyance ₹2000 | From allowance types |

**Allowance types** (HR configures): HRA, Conveyance, custom earning names.

**Payroll rules** (HR configures): PF %, ESI %, professional tax — company-wide statutory settings.

### 12.2 Attendance

Daily mark: **present**, **absent**, **half day**. Used in payroll (LOP = loss of pay days).

### 12.3 Leave

Employee applies leave → HR **approves** or **rejects** → WhatsApp to employee on approval.

### 12.4 Payroll run

**Menu:** Payroll → create run for **year + month** (e.g. March 2026).

**Process payroll** (one button for whole company):

1. For each **active employee**, calculate:
   - Basic (minus LOP)
   - Allowances (HRA, etc.)
   - Gross
   - Deductions: PF, ESI, PT, TDS
   - **Net pay**
2. Post accounting.
3. Generate **payslip PDF** per employee.
4. WhatsApp **salary credited** with net amount (+ payslip link if configured).

**Employee role** users open **My payslips** only — no admin menu.

---

## 13. Reports and PDFs

**Menu:** Reports (permission-based sections)

| You see | If you have permission |
|---------|-------------------------|
| Sales stats | `reports.sales` |
| Purchase stats | `reports.purchase` |
| Inventory / stock ledger PDF | `reports.inventory` |
| GSTR, P&L, balance sheet | `finance.reports.view` |
| Employee count | `hr.employee.manage` |

Most list pages have **PDF download** on documents (PO, GRN, invoice, payslip, dispatch challan).

---

## 14. WhatsApp alerts

Turned on in **Company → WhatsApp enabled** + API keys in server `.env`.

| When | Who gets message |
|------|------------------|
| PR approved / rejected | Person who requested |
| PO needs finance / approved / rejected | PO creator |
| PO approved / sent | Vendor |
| GRN posted | Purchase & warehouse managers |
| Invoice posted | Customer |
| Payment received | Customer |
| Payment sent to vendor | Vendor |
| Invoice overdue | Customer (scheduled daily) |
| Low stock | Purchase/warehouse (scheduled daily) |
| Leave approved | Employee |
| Production started / completed | Production staff |
| Salary credited | Employee |
| License expiring | Admins (scheduled) |

Templates must exist in Meta Business Manager — see `docs/whatsapp-templates.md`.

---

## 15. Vendor portal

**URL:** `/vendor/login` (separate from staff login)

Vendor logs in with **email + portal password** (set by admin when enabling portal).

| Action | What vendor does |
|--------|------------------|
| Accept PO | Confirms they will supply |
| Reject PO | Declines order |
| Update delivery | In transit / delivered / delayed |
| Upload invoice | PDF against open payable |

Admin must set vendor **active**, **portal enabled**, and send credentials (email).

---

## 16. Batch and serial numbers

| Type | Real-life example | Rule in software |
|------|-------------------|------------------|
| **None** | Screws in a bag — no tracking | No batch/serial fields |
| **Batch** | Paint drums with lot `B-2026-01` and expiry | Batch + expiry on GRN; pick batch on dispatch (FEFO) |
| **Serial** | Machines each with unique serial | One serial = quantity 1 only |

**Why it matters:** Recall expired paint, trace which customer got serial `SN-9912`, comply with audits.

---

## 17. End-to-end day in the life

**Morning — Purchase**

1. Production supervisor says steel is low.  
2. Purchase executive creates **PR** for 500× `STL-ROD-12`.  
3. Manager **approves** PR.  
4. Purchase converts to **PO** to `V-1001` for ₹85/piece + GST.  
5. PO total ₹50,000 — below threshold → straight to **approved** → **sent**.  
6. Vendor **accepts** on portal.

**Afternoon — Warehouse**

7. Truck arrives. Warehouse creates **GRN** for 500 rods, batch `B-JUN-01`, expiry next year.  
8. Stock shows 500 more rods in `WH-MAIN`.

**Sales**

9. Sales manager creates **quotation** for 100 hammers @ ₹250.  
10. Customer confirms → **Sales order** `SO-00045`. Stock reserved.  
11. Warehouse **dispatches** 100 hammers (batch/serial if tracked).  
12. Accounts posts **invoice** → customer owes ₹29,500 + GST (example).  
13. Customer pays → **receipt** recorded.

**Production**

14. Work order **WO-0009** for 100 hammers → consumes rods/handles → **completes** → hammers in stock.

**Month end — HR**

15. HR marks attendance.  
16. HR runs **payroll** for the month → payslips + WhatsApp to employees.

**Month end — Finance**

17. Accountant exports **GSTR-1**, pays vendor via **payments**, locks GST period.

That is the full circle.

---

## 18. Quick dictionary

| Term | Meaning |
|------|---------|
| SKU | Product code |
| UOM | Unit of measure (PCS, KG) |
| HSN | Harmonized System Nomenclature for GST |
| PR | Purchase Requisition |
| PO | Purchase Order |
| GRN | Goods Receipt Note |
| SO | Sales Order |
| BOM | Bill of Materials |
| WO | Work Order |
| GSTIN | GST identification number |
| CGST / SGST / IGST | Types of GST |
| IRN | e-Invoice reference number |
| UTR | Bank transaction reference |
| LOP | Loss of pay (unpaid leave days) |
| PF / ESI / PT | Salary statutory deductions |
| FEFO | First expiry, first out |
| 3-way match | PO vs GRN vs vendor invoice |
| Payable | Money you owe vendor |
| Receivable | Money customer owes you |

---

## Related documents in this project

| File | Use for |
|------|---------|
| `docs/PROJECT_OVERVIEW.md` | Demo script with sample clicks |
| `docs/whatsapp-templates.md` | Setting up WhatsApp in Meta |
| `AGENTS.md` | Developer technical rules |

---

## Tips for learning the live system

1. Log in as **Super Admin** (`admin@gmail.com` / `password` after seed).  
2. Set **Company** first.  
3. Create one **vendor**, one **customer**, two **items** (raw + finished), one **warehouse**.  
4. Walk the **purchase chain** once with small quantities.  
5. Walk the **sales chain** once.  
6. Create a simple **BOM** and **work order**.  
7. Add one **employee** and a test **payroll run** in draft month.  
8. Log in as other roles (create test users) to see **menu differences**.

When something is blocked, check: **role permission**, **document status** (draft vs approved), **vendor/customer active**, and **stock available**.

---

*Document version: aligned with KeplerERP codebase (Laravel 13). Field names and statuses match the application; your live `.env` and seed data may use different demo emails.*
