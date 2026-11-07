```
    ███████╗██╗   ██╗███╗   ██╗ ██████╗ ██╗███████╗███████╗
    ██╔════╝██║   ██║████╗  ██║██╔════╝ ██║██╔════╝██╔════╝
    █████╗  ██║   ██║██╔██╗ ██║██║  ███╗██║█████╗  ███████╗
    ██╔══╝  ██║   ██║██║╚██╗██║██║   ██║██║██╔══╝  ╚════██║
    ██║     ╚██████╔╝██║ ╚████║╚██████╔╝██║███████╗███████║
    ╚═╝      ╚═════╝ ╚═╝  ╚═══╝ ╚═════╝ ╚═╝╚══════╝╚══════╝
              ╔═╗╔═╗╦═╗  ╦ ╦╔═╗╔═╗╔═╗╔═╗╔╦╗╔╦╗╔═╗╦═╗╔═╗╔═╗
              ╠╣ ║ ║╠╦╝  ║║║║ ║║ ║║  ║ ║║║║║║║║╣ ╠╦╝║  ║╣
              ╚  ╚═╝╩╚═  ╚╩╝╚═╝╚═╝╚═╝╚═╝╩ ╩╩ ╩╚═╝╩╚═╚═╝╚═╝
```

# Fungies for WooCommerce

> Connect your WooCommerce store to [Fungies.io](https://fungies.io) — sync products, accept payments through Fungies hosted checkout, and keep orders perfectly in sync.

---

## Overview

```
  ┌─────────────────────┐         ┌─────────────────────┐
  │   WooCommerce Store │         │    Fungies.io        │
  │                     │         │                      │
  │  ┌───────────────┐  │  Sync   │  ┌────────────────┐ │
  │  │   Products    │◄─┼─────────┼──│   Products     │ │
  │  └───────────────┘  │         │  └────────────────┘ │
  │                     │         │                      │
  │  ┌───────────────┐  │  Push   │  ┌────────────────┐ │
  │  │   Coupons     │──┼─────────┼─►│   Discounts    │ │
  │  └───────────────┘  │         │  └────────────────┘ │
  │                     │         │                      │
  │  ┌───────────────┐  │ Webhook │  ┌────────────────┐ │
  │  │    Orders     │◄─┼─────────┼──│   Payments     │ │
  │  └───────────────┘  │         │  └────────────────┘ │
  │                     │         │                      │
  │  ┌───────────────┐  │Redirect │  ┌────────────────┐ │
  │  │   Customer    │──┼─────────┼─►│ Hosted Chkout  │ │
  │  └───────────────┘  │  +code  │  └────────────────┘ │
  └─────────────────────┘         └─────────────────────┘
```

Fungies acts as your **Merchant of Record** — handling payments, taxes, and compliance. WooCommerce is your **storefront**. This plugin bridges them seamlessly.

---

## Features

| Feature | Description |
|---------|-------------|
| API Key Management | Production + Staging keys in WC Settings |
| Sandbox / Staging Mode | Routes to `api.stage.fungies.net` |
| Two-Way Product Sync | Pull Fungies offers → WC products **and** push WC products → Fungies OneTimePayment offers |
| Auto-Sync on Save | Editing a WC product automatically updates the matching Fungies offer |
| One-Way Coupon Sync | Push WC coupons → Fungies discounts (`percent`, `fixed_cart`, `fixed_product`) with matching code, amount, expiry, usage limit |
| Instant Coupon Push on Save | `save_post_shop_coupon` hook creates / updates the Fungies discount the moment you save the coupon — no waiting for cron |
| Discount Code Forwarding | WC coupon applied at checkout is appended to the Fungies hosted checkout URL via `fngs-discount-code`, so totals stay consistent |
| Currency Validation | Auto-detects Fungies workspace currency; warns on mismatch |
| Multi-Item Checkout | Multiple cart items create a Fungies Checkout Element so all line items appear on the hosted checkout page |
| Detailed Sync Panel | Pull/push summaries + collapsible error list under the "Sync Now" button |
| Duplicate Protection | Products pushed to Fungies are never re-imported as duplicates on the next pull |
| Hosted Checkout | Redirect to Fungies checkout page |
| Webhook Integration | Real-time order sync |
| Order Creation | Auto from payment webhooks |
| Refund Handling | Auto status updates |
| Subscription Support | Create / Renew / Cancel |
| Post-Purchase Redirect | Returns customer to WC order page |
| WooCommerce Logging | Full audit trail |

---

## How It Works

1. **Configure** — Paste your Fungies API keys and Store URL in WooCommerce → Settings → Fungies
2. **Sync Products** — Click "Sync Now" or let the hourly cron pull OneTimePayment products automatically
3. **Customer Shops** — Customers browse your WC store and proceed to checkout
4. **Fungies Checkout** — Customer is redirected to Fungies hosted checkout to pay
5. **Payment Complete** — Fungies redirects customer back to your WooCommerce thank-you page
6. **Webhook Sync** — Fungies sends `payment_success` → plugin completes the WC order

---

## Requirements

| Requirement | Version |
|-------------|---------|
| WordPress | ≥ 5.8 |
| WooCommerce | ≥ 6.0 |
| PHP | ≥ 7.4 |
| Fungies Account | [Sign up](https://fungies.io) |

---

## Installation

### Manual Upload

1. Download the plugin `.zip` from the [latest release](https://github.com/dukenukemall/fungies-wp-plugin/releases)
2. Go to **WordPress Admin → Plugins → Add New → Upload Plugin**
3. Upload the zip and click **Activate**

### From Source

```bash
cd wp-content/plugins/
git clone https://github.com/dukenukemall/fungies-wp-plugin.git
```

Then activate via **WordPress Admin → Plugins**.

---

## Configuration

### Step 1: Choose Your Environment

| Environment | API URL | Dashboard |
|---|---|---|
| **Production** | `https://api.fungies.io/v0` | [app.fungies.io](https://app.fungies.io) |
| **Sandbox** | `https://api.stage.fungies.net/v0` | [app.stage.fungies.net](https://app.stage.fungies.net) |

> **Important:** Production and staging are **completely separate**. API keys, products, and webhooks are all independent. Staging keys will NOT work against the production API, and vice versa.

### Step 2: Get Your API Keys

1. Log in to your Fungies Dashboard (production or staging)
2. Go to **Developers → API Keys**
3. Click **Generate API Key**
4. Copy your **Public Key** (`pub_...`) and **Secret Key** (`sec_...`)
5. Copy your **Webhook Secret** from **Developers → Webhooks**

### Step 3: Plugin Settings

Navigate to **WooCommerce → Settings → Fungies** and fill in:

| Setting | Value |
|---------|-------|
| Sandbox Mode | Check if using staging |
| Public Key | Your `pub_...` key |
| Secret Key | Your `sec_...` key |
| Webhook Secret | From Fungies webhook settings |
| **Fungies Store URL** | Your store URL (see Step 4) |

### Step 4: Match Store Currencies

> **Your WooCommerce store currency must match the currency set in your Fungies workspace.**

1. Check your Fungies currency in **Fungies Dashboard → Settings → General** (under "Currency")
2. Check your WooCommerce currency in **WooCommerce → Settings → General** (under "Currency options")
3. Make sure both are set to the same currency (e.g. both USD, both EUR)

If the currencies don't match, product prices will display incorrectly and checkout totals may be wrong.

### Step 5: Publish Your Fungies Store & Get the Store URL

> **Your Fungies store must be published for the hosted checkout to work.**

1. In the Fungies Dashboard, click **"Go to Store"** in the top menu
2. Make sure the store is **published** (not draft)
3. Copy the store URL — it looks like:
   - **Production:** `https://yourname.app.fungies.io`
   - **Staging:** `https://yourname.stage.fungies.net`
4. Paste it into the **Fungies Store URL** field in WooCommerce → Settings → Fungies
5. Click **Save Changes**

### Step 6: Configure Webhook in Fungies

1. Go to **Fungies Dashboard → Developers → Webhooks**
2. Add a new endpoint with the **Webhook URL** shown on the plugin settings page
   (e.g. `https://yoursite.com/wp-json/fungies/v1/webhook`)
3. Select the events: `payment_success`, `payment_failed`, `payment_refunded`, `subscription_created`, `subscription_interval`, `subscription_cancelled`

### Step 7: Configure Post-Purchase Redirect URL in Fungies

After a customer completes payment on Fungies, they need to be redirected back to your WooCommerce store. This is configured **store-wide** in the Fungies Dashboard.

1. Go to **Fungies Dashboard → Settings → Store → Checkout tab**
2. Scroll down to **"Success redirection settings"**
3. In the **Instant Redirect URL** field, paste the **Post-Purchase Redirect URL** shown on the plugin settings page:
   ```
   https://yoursite.com/?wc-api=fungies_return
   ```
4. In **URL Parameters**, add these system parameters from the dropdown:
   - **Order id** (appears as `fngs-order-id` in the URL)
   - **User email** (appears as `fngs-user-email` in the URL)

   > **Note:** The dropdown list shows human-readable names (e.g. "Order id", "User email"). Once selected, they are automatically converted to the correct URL parameters (`fngs-order-id`, `fngs-user-email`). The names in the dropdown may differ from the final parameter names -- this is expected.

5. The final redirect URL will look like:
   ```
   https://yoursite.com/?wc-api=fungies_return&fngs-order-id={fngs-order-id}&fngs-user-email={fngs-user-email}
   ```
6. Click **Save**

> **How it works:** After payment, Fungies redirects the customer to this URL with the Fungies order ID. The plugin looks up the matching WooCommerce order and sends the customer to the WooCommerce "Order Received" thank-you page.

See the [Fungies redirect documentation](https://help.fungies.io/for-saas-developers/redirecting-after-purchase) for more details on available system parameters.

### Step 8: Test the Full Flow

1. Enable **Sandbox Mode** and use staging keys
2. Click **Sync Now** to pull products
3. Add a product to cart and proceed to checkout
4. Select **Fungies Checkout** and place order
5. You should be redirected to the Fungies hosted checkout page
6. Pay using [Stripe test cards](https://docs.stripe.com/testing?testing-method=card-numbers)
7. After payment, you should be redirected back to the WooCommerce thank-you page
8. Check **WooCommerce → Orders** — the order should be marked as completed
9. Check logs at **WooCommerce → Status → Logs → `fungies-*`**

> When ready to go live, uncheck Sandbox Mode, switch to production keys and store URL, and Save Changes.

---

## Product Sync (Two-Way)

Every sync runs **two phases**: a **pull** (Fungies → WooCommerce) followed by a **push** (WooCommerce → Fungies). Both phases run on the same triggers:

| Trigger | When |
|---|---|
| **Sync Now button** | WooCommerce → Settings → Fungies |
| **WP-Cron** | Every hour |
| **Product save hook** | `woocommerce_update_product` / `woocommerce_new_product` (push only, debounced 5 s) |

### Pull — Fungies → WooCommerce

```
Fungies                                WooCommerce
───────                                ───────────
GET /v0/offers/list  ──────────►  for each offer:
                                    ├─ skip if already pushed from WC*
                                    ├─ find matching WC product by _fungies_offer_id
                                    ├─ create or update WC product:
                                    │    name, description, regular_price,
                                    │    featured image, _fungies_currency,
                                    │    _fungies_offer_id, _fungies_checkout_url
                                    └─ remove duplicates (one-time cleanup)
```

\* Offers whose ID is in any WC product's `_fungies_pushed_offer_id` meta are **skipped** to prevent re-importing products that originated in WC.

### Push — WooCommerce → Fungies

```
WooCommerce                                  Fungies
───────────                                  ───────
for each published WC product:
  ├─ skip if has _fungies_offer_id (originated in Fungies)
  ├─ assert WC currency == Fungies workspace currency  ──► error if mismatch
  ├─ build product body (name, description, cover, type=OneTimePayment, status=ACTIVE)
  ├─ build offer body (name, currency, price in major units)
  │
  ├─ if _fungies_pushed_product_id + _fungies_pushed_offer_id exist:
  │     PATCH /v0/products/{id}/update
  │     PATCH /v0/offers/{id}/update
  │     └─ on 404 "not found" ► clear stale IDs, fall through to create
  │
  └─ otherwise:
        POST /v0/products/create  ──► store _fungies_pushed_product_id
        POST /v0/offers/create    ──► store _fungies_pushed_offer_id
                                  ──► store _fungies_pushed_at
```

### Loop & duplicate prevention

| Mechanism | Purpose |
|---|---|
| `_fungies_offer_id` on WC product | Marks WC products that **came from Fungies** — skipped during push |
| `_fungies_pushed_offer_id` on WC product | Marks WC products that have been **pushed to Fungies** — skipped during pull |
| `Fungies_Product_Sync::$is_pulling` flag | Prevents `woocommerce_update_product` hook from firing while the pull phase writes to WC |
| `fungies_push_lock_<id>` 5 s transient | De-bounces rapid-fire WC product updates |
| `cleanup_pushed_duplicates()` SQL pass | Removes duplicates that existed before v2.1.5 |
| 404 → recreate fallback | Recovers from stale IDs when API keys point to a different Fungies workspace (v2.1.7) |

### Meta keys reference

| Meta key | Set during | Purpose |
|---|---|---|
| `_fungies_offer_id` | Pull | The Fungies offer ID this WC product mirrors |
| `_fungies_currency` | Pull | Currency the offer was priced in |
| `_fungies_checkout_url` | Pull | Pre-built single-offer hosted checkout URL |
| `_fungies_pushed_product_id` | Push | Fungies product ID for a WC-originated product |
| `_fungies_pushed_offer_id` | Push | Fungies offer ID for a WC-originated product |
| `_fungies_pushed_at` | Push | Timestamp of last successful push |

---

## Coupon Sync (One-Way: WC → Fungies)

WooCommerce coupons are mirrored as Fungies **discounts** so any code a customer applies in WC is recognized at the Fungies hosted checkout. The sync is intentionally one-way (WC is the source of truth).

### Triggers

| Trigger | When |
|---|---|
| **`save_post_shop_coupon` hook** | The instant you save a coupon in `Marketing → Coupons` (debounced 5 s via transient lock) |
| **Sync Now button** | Catches anything that didn't propagate (e.g. transient API outage during save) |
| **WP-Cron** | Hourly safety net (`fungies_product_sync_cron`) — runs the full Sync Now pass |
| **`before_delete_post`** | Clears the workspace-scoped Fungies ID meta so re-creating a coupon with the same code creates a fresh Fungies discount |

### Field mapping

| WooCommerce coupon | Fungies discount | Notes |
|---|---|---|
| `code` | `discountCode`, `name` | Forwarded as-is, case-preserved |
| `discount_type = percent` | `amountType = percentage` | `amount` sent as the percent value (e.g. `10`) |
| `discount_type = fixed_cart` / `fixed_product` | `amountType = fixed` | `amount` sent in major currency units (e.g. `1` USD) |
| `amount` | `amount` | Server stores fixed amounts in minor units (`1` USD → `"100"`) — diff is currency-aware |
| `date_expires` | `validUntil` | Sent as a Unix-seconds timestamp |
| `usage_limit` | `purchaseLimit` | Omitted when no limit is set on the WC coupon |
| `date_created` | `validFrom` | Always sent as a positive timestamp |
| WC store currency (`get_woocommerce_currency()`) | `currency` | Required by the Fungies API |
| `post_status = publish` | `status = active` | Anything else (`draft`, `pending`, `private`) → `inactive` |

Unsupported coupon types (anything other than `percent`, `fixed_cart`, `fixed_product`) are skipped with a log entry — they wouldn't have a meaningful Fungies counterpart.

### Local mapping

The pushed Fungies discount UUID is stored on the WC coupon post as a **workspace-scoped** post meta key:

```
_fungies_pushed_discount_id__<sha256(secret_key)[0..11]>
```

This means toggling Sandbox ↔ Production never mixes IDs across workspaces. Each environment maintains its own mapping cleanly.

### Sync algorithm (per coupon)

```
build payload from WC coupon
discount_id = local post meta (workspace-scoped)

if discount_id:
    diff payload vs. last-known remote shape (currency-aware for fixed amounts)
    if no diff → skip (status: unchanged)
    PATCH /v0/discounts/{id}/update with payload + id in body
    on 404 / "not found" → clear local meta, fall through to create
    on success → status: updated
else:
    POST /v0/discounts/create
    store returned UUID in workspace-scoped meta
    status: created
```

### Checkout-side: forwarding the applied code

When the customer applies a coupon at the WC checkout and is redirected to Fungies, the URL builder appends the first applied coupon code:

```
…/checkout/<offer-id>?fngs-user-email=…&fngs-customer-country=…&fngs-discount-code=<code>
```

Fungies looks up the discount by `discountCode` and applies it on the hosted checkout, so the post-discount total matches what the customer saw on WC. Combined with the instant `save_post_shop_coupon` push, the Fungies side is always in sync by the time the customer reaches checkout.

### Resilience

- Tolerates transient `GET /v0/discounts/list` 500s by walking pages one at a time and primarily relying on local post-meta mapping.
- Diff comparison normalizes server-side amount storage (Fungies stores fixed amounts in currency minor units) and unit drift (`validUntil` is returned in milliseconds, sent in seconds) so the plugin doesn't trigger pointless `updated` reports.

---

## Checkout Flow

```
  Customer          WooCommerce           Fungies             WooCommerce
  ────────          ───────────           ───────             ───────────
     │                   │                   │                     │
     │  1. Place Order   │                   │                     │
     │──────────────────►│                   │                     │
     │                   │                   │                     │
     │                   │ 2. Create pending  │                     │
     │                   │    order           │                     │
     │                   │                   │                     │
     │  3. Redirect to Fungies checkout      │                     │
     │◄──────────────────│                   │                     │
     │──────────────────────────────────────►│                     │
     │                   │                   │                     │
     │  4. Pay on Fungies│                   │                     │
     │──────────────────────────────────────►│                     │
     │                   │                   │                     │
     │                   │                   │ 5. Webhook           │
     │                   │                   │ payment_success      │
     │                   │                   │────────────────────►│
     │                   │                   │                     │
     │  6. Redirect back │                   │  Order completed     │
     │◄─────────────────────────────────────│                     │
     │──────────────────────────────────────────────────────────►│
     │                   │                   │                     │
     │  7. Thank-you page│                   │                     │
     │◄─────────────────────────────────────────────────────────│
```

---

## Checkout URL Generation

When a customer clicks **Place Order**, `Fungies_Payment_Gateway::process_payment()` delegates to `Fungies_Checkout_URL_Builder::build($order)`, which produces a different URL shape depending on **how many distinct offers** are in the cart.

### Step 1 — Collect offer IDs

For each line item the builder resolves a Fungies offer ID by checking, in order:

1. `_fungies_offer_id` (product was pulled **from** Fungies)
2. `_fungies_pushed_offer_id` (product was pushed **to** Fungies from WC)

Each offer ID is collected once, with quantities added up per offer (since 2.4.5; before, each unit was one more copy of the id). A Fungies checkout charges one `fngs-quantity` for every offer it holds, so when every offer has the same quantity N the URL carries `&fngs-quantity=N` (left out for N = 1), and when quantities differ (2× A + 1× B) `build()` returns a `WP_Error` and `process_payment()` refuses the order with a notice. Items without either meta key are logged and skipped.

### Step 2 — Build the URL

#### Single offer (1 distinct offer ID, any quantity)

No API call is needed — Fungies exposes a deterministic single-offer hosted checkout URL:

```
<store_url>/checkout/<offer_id>
  ?fngs-user-email=<billing_email>
  &fngs-customer-country=<billing_country>
  &fngs-discount-code=<applied_coupon_code>   ← appended only when a WC coupon is applied (v2.3.0+)
```

Example:

```
https://azzeki.com/checkout/d8ef9d66-7d5f-4c30-88d6-4ff8fd612b88
  ?fngs-user-email=keviolf%40gmail.com
  &fngs-customer-country=PL
  &fngs-discount-code=percent10
```

#### Multiple offers (2+ distinct offer IDs)

The builder calls the Fungies Checkout Element API to bundle all offers into a single hosted checkout session:

```
POST /v0/elements/checkout/create
{
  "name": "WC Order #22071",
  "offersIds": [
    "076b348f-804f-4c1f-bce3-5541d410992a",   ← Fungies-originated product
    "d8ef9d66-7d5f-4c30-88d6-4ff8fd612b88"    ← WC-originated, pushed product
  ]
}
```

The response's `data.checkoutElement.id` is stored on the WC order as `_fungies_checkout_element_id` and used to build:

```
<store_url>/checkout-element/<element_id>
  ?fngs-user-email=<billing_email>
  &fngs-customer-country=<billing_country>
  &fngs-discount-code=<applied_coupon_code>   ← appended only when a WC coupon is applied (v2.3.0+)
```

Example:

```
https://azzeki.com/checkout-element/e3e1e78d-c31a-41f9-a058-b1ad7c9acbb9
  ?fngs-user-email=keviolf%40gmail.com
  &fngs-customer-country=PL
  &fngs-discount-code=percent10
```

When the customer lands there, Fungies promotes the element into a checkout session and the URL becomes `…/checkout-element/<element_id>/checkout/<session_id>` — both products are visible in the order summary on the hosted page.

### Decision tree

```
                 Cart items
                     │
                     ▼
          collect offer IDs from
        _fungies_offer_id  →  _fungies_pushed_offer_id
                     │
            ┌────────┴────────┐
            │                 │
       0 offers           1+ offers
            │                 │
            ▼          ┌──────┴──────┐
   fall back to:       │             │
   _fungies_checkout_url   1 offer       2+ offers
   on any cart item        │             │
            │              ▼             ▼
            │     /checkout/{id}   POST /elements/checkout/create
            │                            │
            ▼                            ▼
   if still none →           /checkout-element/{element_id}
   wc order received URL              │
   (logged as warning)                │
                              store element_id on order
                              for traceability
```

### Why two URL shapes?

The single-offer URL is **stateless** — no API call, no rate-limit cost, no extra latency. The multi-offer flow has to use the Checkout Element endpoint because Fungies' single-offer URL only carries one offer ID. v2.1.6 introduced this split; before that, multi-item carts would silently lose every line item except the first.

---

## Webhook Events

| Fungies Event | WooCommerce Action |
|---|---|
| `payment_success` | Complete WC order, store Fungies metadata |
| `payment_failed` | Update order status to `failed` |
| `payment_refunded` | Create WC refund, set status to `refunded` |
| `subscription_created` | Store subscription ID in order meta |
| `subscription_interval` | Create renewal order |
| `subscription_cancelled` | Update subscription status in meta |

### Webhook Security

All incoming webhooks are verified using **HMAC-SHA256** signature validation. Duplicate events are filtered via idempotency key tracking.

---

## Order Metadata

When a payment succeeds, the following metadata is stored on the WC order:

| Meta Key | Description |
|---|---|
| `_fungies_order_id` | Fungies order UUID |
| `_fungies_order_number` | Fungies order number |
| `_fungies_payment_id` | Payment UUID |
| `_fungies_payment_type` | `one_time`, `subscription_initial`, etc. |
| `_fungies_subscription_id` | Subscription UUID (if applicable) |
| `_fungies_invoice_url` | Invoice PDF link |
| `_fungies_fee` | Fungies processing fee |
| `_fungies_tax` | Tax amount |

---

## FAQ

**Q: Does the Fungies store need to be published?**
A: Yes. The hosted checkout URL only works when your Fungies store is published. Go to the Fungies Dashboard and make sure your store is not in draft mode.

**Q: Why don't customers get redirected back after payment?**
A: You need to configure the **Instant Redirect URL** in Fungies Dashboard → Settings → Store → Checkout tab. See [Step 7](#step-7-configure-post-purchase-redirect-url-in-fungies) above.

**Q: Can I use this alongside other WC payment gateways?**
A: Yes. Fungies registers as a standard WC payment gateway. Customers can choose it at checkout alongside any other enabled gateways.

**Q: Which Fungies products are synced?**
A: Only **OneTimePayment** products and their offers are synced. Other product types (Digital Downloads, Subscriptions, Game Keys, etc.) are not imported. The product name and description from Fungies are used for the WooCommerce product listing.

**Q: How often do products sync automatically?**
A: Every hour via WP Cron. You can also trigger a manual sync anytime from the settings page.

**Q: What about coupons?**
A: WooCommerce coupons (`percent`, `fixed_cart`, `fixed_product`) are pushed to Fungies as discounts. Direction is one-way: WC → Fungies. The push is **instant** on every coupon save (no waiting for cron) and the same code path runs on Sync Now and the hourly cron as a safety net.

**Q: How does the discount actually get applied at the Fungies checkout?**
A: When the customer applies a coupon at the WC checkout, the redirect URL to Fungies is appended with `&fngs-discount-code=<code>`. Fungies looks up the discount by code (already synced via the plugin) and applies it on the hosted checkout, so the totals match across both checkouts.

**Q: How do I test without processing real payments?**
A: Enable **Sandbox Mode**, use staging keys from [app.stage.fungies.net](https://app.stage.fungies.net), and pay with [Stripe test cards](https://docs.stripe.com/testing?testing-method=card-numbers).

---

## Changelog

### 2.3.1
- **Added:** Instant coupon push on save via the `save_post_shop_coupon` hook. New / edited WC coupons reach Fungies within ~500 ms instead of waiting for the next Sync Now or hourly cron.
- **Added:** `before_delete_post` hook clears the workspace-scoped `_fungies_pushed_discount_id__<hash>` meta when a coupon is deleted, so re-creating one with the same code creates a fresh Fungies discount instead of trying to update a stale ID.

### 2.3.0
- **Added:** Applied WC coupon code is forwarded to the Fungies hosted checkout via `&fngs-discount-code=<code>`. Fungies auto-applies the matching synced discount, so the total customers see on Fungies matches what they saw on WC.

### 2.2.3
- **Fixed:** `PATCH /v0/discounts/{id}/update` was rejected with `id: Required` because the Fungies update schema demands `id` in the request body in addition to the URL path. The API client now injects the discount UUID into the body.
- **Fixed:** Coupon diff now normalizes server-side amount storage (Fungies stores fixed amounts in currency minor units — e.g. `1` USD becomes `"100"`) and converts `validUntil` from milliseconds to seconds. No more spurious "updated" reports on every sync.

### 2.2.2
- **Fixed:** Coupon sync no longer aborts when `GET /v0/discounts/list` returns 500 because of pre-existing rows with negative `validFrom` Dates. The plugin now falls back to a row-by-row walk that skips broken pages and primarily relies on the local `_fungies_pushed_discount_id` post meta to decide between create and update.
- **Fixed:** If `update_discount` returns "not found" (e.g., the Fungies discount was archived/deleted manually), the plugin clears the stale local mapping and creates a fresh discount instead of erroring.

### 2.2.1
- **Fixed:** `validFrom` now uses the WC coupon's actual `date_created` Unix timestamp instead of `0`. The Fungies API runtime validator rejected `0` despite the spec listing it as valid, and the server-side `setTimeToStartOfPeriod` call could produce a negative Date in non-UTC server timezones.
- **Fixed:** `purchaseLimit` is now omitted from the create payload when no usage limit is set on the WC coupon (the create schema does not accept `null`).
- **Fixed:** When `GET /v0/discounts/list` fails on the first page, the coupon sync now returns the API error instead of silently treating the remote index as empty.

### 2.2.0
- **Added:** WooCommerce → Fungies coupon sync. Each WC coupon (`percent`, `fixed_cart`, `fixed_product`) is mirrored as a Fungies discount with the same code, amount, amount type, expiration date, and usage limit. Runs on every Sync Now and the hourly cron.
- **Added:** "Coupons → Fungies" row in the Sync Now result panel, with created / updated / error counts and an inline collapsible error list.
- **Added:** Mapping is workspace-scoped (`_fungies_pushed_discount_id__<workspace-hash>` post meta), so toggling Sandbox Mode does not orphan the link.
- **Added:** Three new Fungies API endpoints in the client: `GET /v0/discounts/list`, `POST /v0/discounts/create`, `PATCH /v0/discounts/{id}/update`.

### 2.1.7
- **Fixed:** Push to Fungies now recovers from "Product not found" errors (e.g. after switching from staging to production API keys). Stale Fungies product/offer IDs are cleared and the product is recreated in the current workspace.
- **Fixed:** When a pushed offer is missing in the current workspace but the product still exists, the offer is recreated under the existing product instead of erroring out.
- **Refactor:** Extracted product/offer body builders into `Fungies_Product_Body` to keep the push class focused.

### 2.1.6
- **Fixed:** Multi-item carts now create a Fungies Checkout Element so **all** line items appear on the hosted checkout page (previously only the first item was sent).
- **Fixed:** Products pushed from WC to Fungies are now resolved at checkout — they were silently skipped before.
- **Added:** New `POST /v0/elements/checkout/create` integration in the API client.
- **Added:** WC order metadata `_fungies_checkout_element_id` for traceability of multi-item checkouts.
- **Refactor:** Extracted hosted checkout URL building into `Fungies_Checkout_URL_Builder`.

### 2.1.5
- **Fixed:** Duplicate WC products created when pulling offers we previously pushed from WooCommerce.
- **Added:** One-time cleanup that removes existing duplicates on the next sync.
- **Added:** Pull phase now skips offers that already have `_fungies_pushed_offer_id`.

### 2.1.4
- **Fixed:** HTTP 400 "Invalid input" on Fungies product/offer PATCH — the `id` field is now sent in the request body in addition to the URL path.
- **Improved:** Verbose request body logging in the API client.

### 2.1.3
- **Fixed:** HTTP 500 on `POST /v0/products/create` by sending the required `status: ACTIVE` field.
- **Fixed:** Offer prices are now sent in major currency units (no more accidental ×100).

### 2.1.2
- **Fixed:** Plugin zip path separators (forward slashes) for Linux-based WP hosts.
- **Fixed:** Versioned top-level folder inside the zip to avoid "destination folder already exists" on upload.

### 2.1.1
- **Fixed:** Fatal error on plugin install caused by Windows-style path separators in the zip.

### 2.1.0
- **Added:** Two-way product sync — push WC products to Fungies as OneTimePayment offers (name, description, price, featured image).
- **Added:** Auto-push to Fungies when a WC product is saved/updated.
- **Added:** Currency auto-detection and mismatch validation.
- **Added:** Detailed sync result panel under "Sync Now".
- **Added:** Loop guard so editing a Fungies-imported product in WC does not push back to Fungies.

### 2.0.3
- Security: webhook handler now rejects requests when the webhook secret is not configured.
- Security: escape and sanitize currency code in storefront price output.
- Security: use `esc_html(esc_url())` for non-href URL display in admin settings.

For older releases, see [`readme.txt`](readme.txt).

---

## License

GPLv2 or later. See [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html).
