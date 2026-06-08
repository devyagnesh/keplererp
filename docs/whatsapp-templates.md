# WhatsApp message templates (Meta) — ManufactureERP

Use this document when creating templates in **Meta Business Suite** → **WhatsApp Manager** → **Message templates**.  
Template **names** and **BODY variable order** must match what the app sends (see `config/whatsapp.php` to rename defaults).

**API:** `POST https://graph.facebook.com/{WABA_GRAPH_VERSION}/{WABA_PHONE_NUMBER_ID}/messages`  
**App behaviour:** Sends `type: template` with `language.code` from `WHATSAPP_TEMPLATE_LOCALE` (default `en`) unless the job overrides (e.g. CLI `hello_world` uses `en_US`).  
**Body:** Variables are plain **TEXT** components in **fixed order** `{{1}}`, `{{2}}`, … as listed below.

---

## Quick test (Meta sample)

| Field | Value |
|--------|--------|
| Template name | `hello_world` |
| Language | `English (US)` → code `en_US` |
| Category | `UTILITY` (pre-supplied by Meta) |
| Body variables | **None** |

Use `php artisan whatsapp:test-send {phone}` to verify credentials (defaults to `hello_world` / `en_US`).

---

## Production templates (implemented in code)

Create each with category **UTILITY** (or **MARKETING** only if Meta policy allows for that use case). Use language **English** (`en`) unless you change `WHATSAPP_TEMPLATE_LOCALE` and align the app.

### 1. `po_approved`

| | |
|---|---|
| **When** | Purchase order status becomes **approved** (vendor receives alert). |
| **Recipient** | Vendor `phone` (normalised to E.164, default country `91`). |
| **Body variables** | **3** (all TEXT) |

**Suggested body copy (Meta editor):**

```text
Your purchase order {{1}} for total {{2}} INR has been approved. Expected delivery: {{3}}.
```

| Position | Meaning | Example |
|----------|---------|---------|
| `{{1}}` | PO number | `PO/2026-26/0001` |
| `{{2}}` | Total amount (string from DB) | `11800.00` |
| `{{3}}` | Expected delivery date | `2026-04-25` or `-` |

---

### 2. `grn_posted`

| | |
|---|---|
| **When** | Goods receipt (GRN) is **posted**. |
| **Recipient** | Users with role **Purchase Manager** or **Warehouse Manager** who have `whatsapp_number` set. |
| **Body variables** | **3** |

**Suggested body copy:**

```text
GRN {{1}} posted. Vendor: {{2}}. Lines: {{3}}.
```

| Position | Meaning | Example |
|----------|---------|---------|
| `{{1}}` | GRN number | `GRN-00042` |
| `{{2}}` | Vendor name | `Acme Supplies` |
| `{{3}}` | Short lines summary (max ~3 SKUs + ellipsis) | `SKU-01×10.0000, SKU-02×5.0000…` |

---

### 3. `invoice_sent`

| | |
|---|---|
| **When** | GST **invoice** is posted from a sales order. |
| **Recipient** | Customer `phone`. |
| **Body variables** | **3** |

**Suggested body copy:**

```text
Invoice {{1}} for {{2}} INR is issued. Due date: {{3}}. Thank you for your business.
```

| Position | Meaning | Example |
|----------|---------|---------|
| `{{1}}` | Invoice number | `INV/2026-26/0009` |
| `{{2}}` | Total amount | `25000.00` |
| `{{3}}` | Due date | `2026-05-20` |

---

### 4. `pr_rejected`

| | |
|---|---|
| **When** | Purchase **requisition** is **rejected** (with reason). |
| **Recipient** | Requester’s `whatsapp_number`. |
| **Body variables** | **2** |

**Suggested body copy:**

```text
Purchase requisition {{1}} was rejected. Reason: {{2}}
```

| Position | Meaning | Example |
|----------|---------|---------|
| `{{1}}` | PR number | `PR-00015` |
| `{{2}}` | Rejection reason (truncated in app to ~480 chars + `...`) | `Budget hold` |

---

### 5. `low_stock`

| | |
|---|---|
| **When** | Scheduled job `whatsapp:send-low-stock-alerts` (daily); per SKU below reorder, per Purchase Manager, once per recipient per item per **calendar day**. |
| **Recipient** | Users with role **Purchase Manager** or **Warehouse Manager** who have `whatsapp_number` set. |
| **Body variables** | **3** |

**Suggested body copy:**

```text
Low stock: {{1}}. Current qty: {{2}}. Reorder level: {{3}}. Please review and raise PR if needed.
```

| Position | Meaning | Example |
|----------|---------|---------|
| `{{1}}` | Item name | `Raw material X` |
| `{{2}}` | Current on-hand (aggregated) | `2.0000` |
| `{{3}}` | Reorder level from item master | `10.0000` |

---

### 6. `po_dispatch`

| | |
|---|---|
| **When** | Purchase order is **marked sent** to the vendor (`Mark sent` action). |
| **Recipient** | Vendor `phone`. |
| **Body variables** | **2** |

**Suggested body copy:**

```text
Purchase order {{1}} has been marked as sent to you. Expected delivery: {{2}}.
```

---

### 7. `po_staff_update`

| | |
|---|---|
| **When** | PO creator is notified: **pending finance**, **final approved**, or **rejected**. |
| **Recipient** | User who created the PO (`whatsapp_number`). |
| **Body variables** | **3** |

**Suggested body copy (Meta editor):**

```text
Purchase order update — {{1}}

Current status: {{2}}

{{3}}

Please log in to ManufactureERP to review this purchase order and take any required action (approve, reject, or follow up with finance/vendor).

— ManufactureERP
```

| Position | Meaning | Example |
|----------|---------|---------|
| `{{1}}` | PO number | `PO-00042` |
| `{{2}}` | Status headline | `Pending finance approval` / `Fully approved` / `Rejected` |
| `{{3}}` | Detail (amount, reason, or next step) | `Total 50000.00 INR — awaiting finance sign-off.` |

---

### 8. `pr_approved`

| | |
|---|---|
| **When** | Purchase **requisition** is **approved**. |
| **Recipient** | Requester’s `whatsapp_number`. |
| **Body variables** | **2** |

**Suggested body copy:**

```text
Purchase requisition {{1}} is approved. {{2}}
```

---

### 9. `payment_receipt` / `payment_sent` / `payment_overdue`

| Template | When | Recipient | Variables |
|----------|------|-----------|-------------|
| `payment_receipt` | Customer receipt posted against invoice | Customer `phone` | **3** — invoice no, amount, balance |
| `payment_sent` | Vendor payment recorded | Vendor `phone` | **3** — reference, amount, payable ref |
| `payment_overdue` | Scheduled overdue invoice alert | Customer `phone` | **3** — invoice no, days overdue, amount due |

---

### 10. `leave_approved`

| | |
|---|---|
| **When** | Leave application approved. |
| **Recipient** | Employee `whatsapp` or `phone`. |
| **Body variables** | **4** — start date, end date, type, approver name |

---

### 11. `license_expiry`

| | |
|---|---|
| **When** | Daily scheduler — AMC/license nearing expiry. |
| **Recipient** | Super Admin / Admin `whatsapp_number`. |
| **Body variables** | **2** — days label, renewal URL |

---

### 12. `prod_started` / `prod_complete`

| Template | When | Recipient | Variables |
|----------|------|-----------|-------------|
| `prod_started` | Work order → `in_progress` | Staff with `production.log` or `production.order.create` | **3** — WO no, SKU, planned qty |
| `prod_complete` | Work order → `completed` | Same as above | **3** — WO no, SKU, actual qty |

---

### 13. `salary_credited`

| | |
|---|---|
| **When** | Payroll run processed. |
| **Recipient** | Employee `whatsapp` or `phone`. |
| **Body variables** | **4** — name, period `YYYY-MM`, net salary, payslip signed URL |

---

### Vendor portal credentials (email — not WhatsApp)

When a vendor portal password is issued, the app sends **`VendorPortalCredentialsMail`** to the vendor’s **email** (portal URL + temporary password). A vendor **email** is required when enabling the portal or resetting the password. Do not create a Meta template for this flow.

---

## Renaming templates in Meta

If Meta assigns different internal names (e.g. `po_approved_v2`), update **`config/whatsapp.php`** under `templates` so values match Meta’s **exact** template names. No code change is required beyond config if variable **count and order** stay the same.

---

## Checklist before going live

1. Submit each template for Meta **approval** (UTILITY approves faster when content is transactional).
2. Set `.env`: `WABA_PHONE_NUMBER_ID`, `WABA_ACCESS_TOKEN`, `WABA_GRAPH_VERSION`, `WHATSAPP_DRIVER=cloud`.
3. Enable **WhatsApp notifications** on **Company setup** in the ERP.
4. Ensure **vendors/customers** have correct **`phone`**; staff have **`whatsapp_number`** on their user profile for internal alerts.
5. Run `php artisan whatsapp:test-send` with a **test number** added in Meta **App roles / WhatsApp test numbers** while the app is in development.

---

## Reference: JSON shape the app sends (Cloud API)

Body parameters are sent as `template.components[]` with `type: body` and `parameters: [{ type: text, text: "..." }, ...]` in the same order as the table columns above. Templates with **zero** variables (e.g. `hello_world`) omit the `components` key.
