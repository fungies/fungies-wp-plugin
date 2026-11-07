=== Fungies for WooCommerce ===
Contributors: fungies
Tags: woocommerce, payments, checkout, digital products
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
WC requires at least: 6.0
WC tested up to: 9.0
Stable tag: 2.4.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connect your WooCommerce store to Fungies.io — sync products, accept payments through Fungies hosted checkout, and keep orders perfectly in sync.

== Description ==

**Fungies for WooCommerce** lets you sell digital products through your WooCommerce store while [Fungies.io](https://fungies.io) handles payments, taxes, and compliance as your **Merchant of Record**.

You keep full control of your storefront. Fungies takes care of the hard parts — payment processing, tax collection, invoicing, and regulatory compliance — so you can focus on your products.

= How It Works =

1. **Connect** — Paste your Fungies API keys in WooCommerce → Settings → Fungies
2. **Sync** — Your Fungies products are automatically imported into WooCommerce
3. **Sell** — Customers browse your store and check out via Fungies hosted checkout
4. **Get Paid** — Fungies processes the payment and sends a webhook to complete the WooCommerce order
5. **Stay in Sync** — Orders, refunds, and subscriptions are kept up to date automatically

= Features =

* **Two-Way Product Sync** — Pull OneTimePayment products from Fungies into WooCommerce AND push WooCommerce products to Fungies as OneTimePayment offers (name, description, price, featured image)
* **Auto-Sync on Product Save** — Editing a WooCommerce product automatically updates the matching offer in Fungies
* **Currency Validation** — Detects your Fungies workspace currency and warns if it differs from your WooCommerce store currency
* **Multi-Item Hosted Checkout** — Carts with multiple products create a Fungies Checkout Element so all line items appear on the hosted checkout page
* **Detailed Sync Panel** — Clear summary under "Sync Now" showing pull/push counts and per-product errors
* **Duplicate Protection** — Products pushed from WooCommerce to Fungies will not be re-imported as duplicates on the next pull
* **Hosted Checkout** — Customers are redirected to a secure Fungies checkout page to complete payment, then returned to your WooCommerce thank-you page
* **Real-Time Order Sync** — Webhooks keep WooCommerce orders in sync with Fungies payments, including completions, failures, and refunds
* **Subscription Support** — Handles subscription creation, renewal, and cancellation events from Fungies
* **Sandbox / Staging Mode** — Test the full flow with staging API keys and Stripe test cards before going live
* **Secure Webhooks** — All incoming webhooks are verified with HMAC-SHA256 signatures and protected against duplicate processing
* **WooCommerce Blocks Compatible** — Works with both the classic checkout and the new WooCommerce block-based checkout
* **HPOS Compatible** — Fully compatible with WooCommerce High-Performance Order Storage (custom order tables)
* **Opt-In Debug Logging** — Toggle verbose request/response logging in WooCommerce → Status → Logs (source: `fungies`). Errors and warnings are always logged; verbose dumps are off by default to keep log files small and avoid storing third-party API payloads unless you're troubleshooting.
* **Dashboard Widget** — See your sync status at a glance from the WordPress dashboard

= Why Fungies? =

Fungies acts as your Merchant of Record, which means:

* **No payment gateway setup** — Fungies handles Stripe, PayPal, and more
* **Automatic tax collection** — Sales tax, VAT, and GST handled globally
* **Invoicing & compliance** — Professional invoices generated for every transaction
* **Fraud protection** — Built-in fraud detection and chargeback handling

= Use Cases =

* Sell software, ebooks, courses, or any digital product
* Add a WooCommerce storefront to your existing Fungies catalog
* Let Fungies handle payments and taxes while you manage the shopping experience

= Third-Party Service: Fungies.io =

This plugin connects your WooCommerce store to the **Fungies.io** platform, an external third-party service operated by Fungies Inc.

**What data is sent to Fungies:**

* During **product sync**, the plugin sends your API keys to the Fungies API to retrieve your product catalog.
* During **checkout**, customers are redirected to the Fungies hosted checkout page. Their email address and country code are passed as URL parameters.
* During **webhook processing**, Fungies sends order and payment data (order ID, payment status, amounts, subscription details) to your WordPress site.

**Service endpoints used:**

* Production API: `https://api.fungies.io/v0`
* Staging API: `https://api.stage.fungies.net/v0`
* Hosted checkout: `https://{your-store}.app.fungies.io`

**A Fungies account is required** to use this plugin. You can sign up at [fungies.io](https://fungies.io).

**Legal documents:**

* [General Terms of Use](https://help.fungies.io/legal/general-terms-of-use)
* [SaaS Terms of Use](https://help.fungies.io/legal/saas-terms-of-use)
* [Privacy Policy](https://help.fungies.io/legal/privacy-policy)
* [Cookies and Tracking](https://help.fungies.io/legal/cookies-and-tracking)

== Product Sync ==

Every sync runs two phases on the same triggers:

* **Pull (Fungies to WooCommerce)** — `GET /v0/offers/list`, then for each offer create or update a matching WooCommerce product (name, description, image, price, currency).
* **Push (WooCommerce to Fungies)** — for each published WooCommerce product, build a OneTimePayment product and offer body, then either `PATCH` an existing one or `POST` a new one.

= Triggers =

* **Sync Now button** in WooCommerce → Settings → Fungies
* **WP-Cron**, hourly
* **`woocommerce_update_product` / `woocommerce_new_product`** hooks (push only, debounced 5 seconds)

= Loop and duplicate prevention =

* `_fungies_offer_id` on a WooCommerce product marks it as Fungies-originated → skipped during push.
* `_fungies_pushed_offer_id` on a WooCommerce product marks it as already-pushed → skipped during pull.
* A runtime `is_pulling` flag prevents WooCommerce update hooks from firing while the pull writes to the database.
* A 5-second per-product transient lock debounces rapid-fire saves.
* If a `PATCH` returns 404 "Product not found" (e.g. after switching from staging to production keys), stale IDs are cleared and the product is recreated in the current workspace.

= Currency handling =

The plugin auto-detects the Fungies workspace currency. If your WooCommerce currency does not match, the push phase errors out with a clear message — products are never pushed at the wrong currency.

= Meta keys reference =

* `_fungies_offer_id` — Fungies offer ID this WooCommerce product mirrors (set on pull).
* `_fungies_currency` — currency the offer was priced in (set on pull).
* `_fungies_checkout_url` — pre-built single-offer hosted checkout URL (set on pull).
* `_fungies_pushed_product_id` — Fungies product ID for a WC-originated product (set on push).
* `_fungies_pushed_offer_id` — Fungies offer ID for a WC-originated product (set on push).
* `_fungies_pushed_at` — timestamp of last successful push.

== Checkout URL Generation ==

When a customer clicks **Place Order**, the plugin produces a different Fungies hosted checkout URL depending on how many distinct offers are in the cart.

= Step 1 — Collect offer IDs =

For each cart line item, the builder resolves a Fungies offer ID by checking, in order:

1. `_fungies_offer_id` (product was pulled **from** Fungies).
2. `_fungies_pushed_offer_id` (product was pushed **to** Fungies from WooCommerce).

Each offer ID is collected once, with the cart quantity added up per offer. A Fungies checkout charges one quantity (`fngs-quantity`) for every offer it holds, so:

* every offer has the same quantity N: the checkout opens at quantity N;
* quantities differ (e.g. 2× A + 1× B): checkout is refused before the redirect with a message asking the customer to use one quantity per product or separate orders. The plugin never guesses a price.

Items without either meta key are logged and skipped.

= Step 2 — Build the URL =

**Single offer** (1 distinct offer ID) — no API call is needed. The plugin redirects directly to:

`<store_url>/checkout/<offer_id>?fngs-customer-email=...&fngs-customer-country=...&fngs-quantity=N`

**Multiple offers** (2 or more distinct offer IDs) — the plugin calls `POST /v0/elements/checkout/create` with each offer ID once, then redirects to:

`<store_url>/checkout-element/<element_id>?fngs-customer-email=...&fngs-customer-country=...&fngs-quantity=N`

`fngs-quantity` is left out when N is 1.

The element ID is also stored on the WooCommerce order as `_fungies_checkout_element_id` for traceability. When the customer lands on the hosted checkout, Fungies promotes the element into a checkout session and the URL becomes `…/checkout-element/<element_id>/checkout/<session_id>` — every cart product is visible in the order summary.

= Why two URL shapes? =

The single-offer URL is stateless — no API call, no rate-limit cost, no extra latency. The multi-offer flow has to use the Checkout Element endpoint because Fungies' single-offer URL only carries one offer ID. Before v2.1.6, multi-item carts would silently lose every line item except the first.

== Installation ==

= Automatic Installation =

1. Go to **WordPress Admin → Plugins → Add New**
2. Search for **"Fungies for WooCommerce"**
3. Click **Install Now**, then **Activate**

= Manual Installation =

1. Download the plugin `.zip` file
2. Go to **WordPress Admin → Plugins → Add New → Upload Plugin**
3. Upload the zip file and click **Install Now**
4. Click **Activate**

= Setup =

After activation:

1. Go to **WooCommerce → Settings → Fungies**
2. Enter your Fungies API keys (Public Key, Secret Key, Webhook Secret)
3. Enter your published Fungies Store URL
4. Configure the webhook endpoint in your [Fungies Dashboard](https://app.fungies.io) → Developers → Webhooks
5. Configure the post-purchase redirect URL in Fungies Dashboard → Settings → Store → Checkout tab
6. Click **Sync Now** to import your products

For detailed setup instructions, see the [full documentation](https://help.fungies.io).

== Frequently Asked Questions ==

= Does the Fungies store need to be published? =

Yes. The hosted checkout URL only works when your Fungies store is published. Go to the Fungies Dashboard and make sure your store is not in draft mode.

= Which Fungies products are synced? =

Only **OneTimePayment** products and their offers are synced into WooCommerce. Other product types (Digital Downloads, Subscriptions, Game Keys, etc.) are not imported. Product names and descriptions from Fungies are used for the WooCommerce product listings.

= Can I use this alongside other WooCommerce payment gateways? =

Yes. Fungies registers as a standard WooCommerce payment gateway. Customers can choose it at checkout alongside any other enabled gateways.

= How often do products sync automatically? =

Every hour via WordPress Cron. You can also trigger a manual sync anytime from the Fungies settings page.

= How do I test without processing real payments? =

Enable **Sandbox Mode** in the plugin settings, use staging keys from [app.stage.fungies.net](https://app.stage.fungies.net), and pay with [Stripe test cards](https://docs.stripe.com/testing?testing-method=card-numbers).

= Why don't customers get redirected back after payment? =

You need to configure the **Instant Redirect URL** in Fungies Dashboard → Settings → Store → Checkout tab. Use the Post-Purchase Redirect URL shown on the plugin settings page and add the Order ID and User Email URL parameters.

= Do I need separate API keys for sandbox and production? =

Yes. Production and staging environments in Fungies are completely separate. API keys, products, and webhooks are independent — staging keys will not work against the production API, and vice versa.

= What webhook events should I enable? =

Enable these events in your Fungies webhook configuration: `payment_success`, `payment_failed`, `payment_refunded`, `subscription_created`, `subscription_interval`, and `subscription_cancelled`.

= What happens if the amount paid does not match the order? =

On `payment_success` the plugin compares what Fungies charged with the order's items after discounts. The charge is `data.order.value` plus `data.order.tax` (the value is net of tax), in the order currency, so a paid order passes whether the offer's price includes tax or not. Shipping, fees and WooCommerce's own tax are added by WooCommerce and are not charged through Fungies. If the payment is short, in another currency, or carries no amount, the order is put **on hold** with an order note instead of being marked paid, and `_fungies_amount_check` records why. Review it in the Fungies dashboard before fulfilling.

= What order metadata is stored? =

When a payment succeeds, the plugin stores the Fungies order ID, order number, payment ID, payment type, subscription ID (if applicable), invoice URL, processing fee, and tax amount on the WooCommerce order.

= Does it work with WooCommerce Blocks checkout? =

Yes. The plugin is fully compatible with both the classic WooCommerce checkout and the new block-based cart and checkout experience.

== Screenshots ==

1. Fungies appearing as a payment option in the WooCommerce Payments settings.
2. Customer-facing Fungies checkout step launched from WooCommerce.
3. Fungies checkout completing the purchase with Merchant-of-Record handling.
4. Fungies for WooCommerce settings tab inside WooCommerce admin.
5. Fungies dashboard showing products and orders synced from WooCommerce.
6. Synced products list inside the Fungies dashboard.
7. WooCommerce order detail view linked to its Fungies order and payment.

== Changelog ==

= 2.4.5 =
* Fix: A cart with quantity > 1 now sends each Fungies offer ID once and passes the quantity as `fngs-quantity=N`, so 3× one product is charged for 3. Before, the plugin sent one offer ID per unit: the API answered 404 on the repeated ID, and once the API collapses repeated IDs the buyer would have been charged for 1.
* Fix: A cart whose products have different quantities (e.g. 2× A + 1× B) is refused at checkout with a clear message, because a Fungies checkout charges one quantity for every product.
* Safety: `payment_success` now checks the charge (`data.order.value` plus `data.order.tax`) and its currency against the order's items after discounts (shipping, fees and WooCommerce's own tax excluded). A shortfall, another currency or a missing amount puts the order on hold for review instead of marking it paid.
* Fix: A renewal order's total, and a refund that carries no amount, now use the charge including tax. Before, both used the amount net of tax. A refund without an amount never exceeds what the order still holds.
* Dev: `tests/test-checkout-plan.php` (plain PHP, no WordPress) covers both rules.

= 2.4.4 =
* Fix: Customer email was not pre-filled on the Fungies hosted checkout page when redirecting from WooCommerce. Root cause: `Fungies_Checkout_URL_Builder::build()` was sending `fngs-user-email` as the prefill query parameter, but per the official Fungies docs the prefill parameter is `fngs-customer-email`. `fngs-user-email` is the *outbound* system parameter Fungies appends to the post-purchase Instant Redirect URL — not the inbound prefill param. Mixing the two meant the WC billing email was passed but ignored by Fungies, so customers had to retype their email at checkout. The plugin now sends `fngs-customer-email` for prefill while still reading `fngs-user-email` from the return URL (where Fungies appends it) — both names are now used in their correct directions. No data migration needed; existing orders are unaffected.
* Docs: Corrected the URL examples in the readme to match.

= 2.4.3 =
* Lint: Replaced the three `phpcs:ignore` annotations added in 2.4.2 with proper `phpcs:disable` / `phpcs:enable` blocks. The slow-query sniff fires on the `'meta_key'` / `'meta_value'` / `'meta_query'` array-key string tokens *inside* the `wc_get_orders()` / `get_posts()` literal, not on the outer call line, so the single-line `ignore` form never reached them. Affects `Fungies_Order_Sync::find_order_by_meta()`, `Fungies_Return_Resolver::by_meta()`, and `Fungies_Product_Sync::push_to_fungies()`.
* No runtime behaviour changes.

= 2.4.2 =
* Security/correctness: Replaced two SQL queries in `Fungies_Workspace_Meta::get_all_pushed_offer_ids()` and `Fungies_Product_Sync::cleanup_pushed_duplicates()` that interpolated class constants directly into the SQL string with fully prepared statements using `$wpdb->prepare()` + `$wpdb->esc_like()`. Behaviour is unchanged — values were already trusted constants, but the new form is what WordPress.org Plugin Check requires and what static analyzers can verify.
* i18n: Added the missing `/* translators: ... */` comments above every gettext call that uses placeholders so translators see what each `%s` / `%d` refers to. Reworked `'Connected to %s API! (%s)'` into ordered placeholders `'Connected to %1$s API! (%2$s)'` per WP i18n guidelines.
* Lint: Added narrow `phpcs:ignore` annotations with inline rationale on the handful of justified direct-DB / slow-query / nonce-check warnings (e.g. the `wc-api=fungies_return` redirect handler reads `$_GET` for read-only redirect logic and is paired with an HMAC-verified webhook; the direct postmeta lookups are single-row indexed reads). No new ignores were added blindly.
* No runtime behaviour changes.

= 2.4.1 =
* Compliance: Added the `Requires Plugins: woocommerce` header introduced in WordPress 6.5. WordPress now refuses to activate the plugin unless WooCommerce is installed and active, and the WooCommerce dependency is shown in the Plugins screen.
* i18n: Renamed the plugin text domain from `fungies-wp` to `fungies-for-woocommerce` (matching the plugin slug) across all 97 gettext calls and the plugin header. Required for the WordPress.org translation platform to pick up strings for community translation. Any custom `.po` / `.mo` files keyed off the old `fungies-wp` domain must be re-generated.
* No runtime behaviour changes.

= 2.4.0 =
* Security: Hardened the Fungies Store URL setting — the field is now an `<input type="url">` and saves through `esc_url_raw`, plus the runtime read site re-validates with `esc_url_raw` + `wp_http_validate_url` and rejects anything other than `http`/`https` schemes before building a customer redirect.
* Security: Added an HTTPS host allowlist (`fungies.io`, `fungies.net`) before sideloading product images via `media_sideload_image`, mitigating SSRF risk from third-party image URLs. Extensible via the `fungies_image_host_allowlist` filter.
* Privacy: Verbose API request/response logging is now opt-in via a new **WooCommerce → Settings → Fungies → Debug Logging** checkbox. Errors and warnings are still logged unconditionally so genuine failures remain diagnosable; verbose dumps are off by default to keep log files small and avoid storing third-party API payloads unless troubleshooting.
* i18n: Wrapped two previously-untranslated admin AJAX error strings in `__()` with translator comments and numbered placeholders.
* Cleanup: Removed `console.log` / `console.error` calls from the front-end Blocks checkout JS so the customer's browser console stays clean.
* Docs: Inline rationale comment added above the webhook REST route's `permission_callback => '__return_true'` explaining the HMAC-SHA256 signature verification, timing-safe comparison, and idempotency replay protection that act as the real auth.
* Docs: Older changelog entries (2.1.x, 2.0.x, 1.x) moved to `changelog.txt` per WP.org guidelines, keeping the readme focused on the current and previous major.
* Build: `.gitattributes` now `export-ignore`s `README.md`, `build.ps1`, and `fungies-*.zip` so the shipped plugin zip contains only runtime files.
* Readme: Removed obsolete `Donate link` and the `Screenshots` section (no screenshot assets shipped).

= 2.3.1 =
* Feature: Coupons are now pushed to Fungies **the moment they are saved** in WooCommerce, mirroring how products already work. Hook `save_post_shop_coupon` runs `Fungies_Coupon_Sync::on_coupon_saved`, which creates or updates the corresponding Fungies discount immediately — no need to wait for the hourly cron or click "Sync Now". Skipped silently for autosaves and revisions, debounced via a 5-second transient lock per coupon.
* Feature: Deleting a coupon (`before_delete_post`) now clears its workspace-scoped `_fungies_pushed_discount_id__<hash>` post meta, so re-creating a coupon with the same code creates a fresh Fungies discount instead of trying to update a stale ID.

= 2.3.0 =
* Feature: WooCommerce coupon codes applied at checkout are now forwarded to the Fungies hosted checkout via the `fngs-discount-code` query parameter, so the Fungies-side total automatically matches the WooCommerce-side total after discount. The first coupon code on the order is forwarded as-is — it is expected to match a Fungies discount code already synced via "Sync Now". No action needed on the Fungies dashboard side as long as the coupon was synced.

= 2.2.3 =
* Fix: `PATCH /v0/discounts/:id/update` rejected every coupon update with `id: Required` because the Fungies update schema demands `id` in the request body in addition to the URL path. The API client now injects the discount UUID into the body automatically.
* Fix: Coupon diff now correctly normalizes server-side amount storage. Fungies stores fixed-amount discounts in currency minor units (e.g. `1` USD becomes `"100"` in responses), so the previous diff always reported "different" and triggered an update on every sync. The mapper now multiplies fixed amounts by `wc_get_price_decimals()` before comparing, and converts `validUntil` from milliseconds to seconds before comparing.

= 2.2.2 =
* Fix: Coupon sync no longer aborts when the Fungies `GET /v0/discounts/list` endpoint returns 500 because of pre-existing rows with negative `validFrom` Dates (a known server-side timezone bug). The plugin now falls back to a row-by-row walk that skips broken pages, and primarily relies on the local `_fungies_pushed_discount_id` post meta to decide between create and update.
* Fix: If an UPDATE call returns "not found" (e.g., the Fungies discount was archived/deleted manually), the plugin now clears the stale local mapping and creates a fresh discount instead of erroring.
* Note for existing installs: any duplicate `percent10` / `fixed1` rows already in your Fungies workspace from a v2.2.0 install must be archived manually in the Fungies dashboard — the plugin can no longer see them through the broken LIST page.

= 2.2.1 =
* Fix: Fungies API rejects `validFrom: 0` with "Number must be greater than or equal to 0" even though the spec lists 0 as valid. The coupon now sends the WooCommerce coupon's actual `date_created` timestamp (or current time as fallback) for `validFrom`, which is also more semantically correct.
* Fix: `purchaseLimit` was always sent — its create-schema enum forbids `null`, so coupons without a usage limit failed validation. The field is now only included when the WooCommerce coupon has a usage limit set.
* Fix: When `GET /v0/discounts/list` fails on the first page, the coupon sync now returns the API error instead of silently treating the remote index as empty (which could create duplicates on a transient outage).

= 2.2.0 =
* Feature: WooCommerce coupons are now synced to Fungies on every "Sync Now" run. Each coupon (`percent`, `fixed_cart`, `fixed_product`) is created or updated as a Fungies discount with the same code, amount, amount type, expiration date, and usage limit. The Sync panel reports a third "Coupons → Fungies" line with created / updated / error counts. Mapping is workspace-scoped (sandbox vs production) so toggling Sandbox Mode does not orphan the link, and re-running Sync skips coupons that are already in sync.

For changelog entries from earlier releases (2.1.x, 2.0.x, 1.x), see `changelog.txt` in the plugin root.

== Upgrade Notice ==

= 2.4.5 =
Carts with quantity > 1 are charged for the full quantity; mixed-quantity carts are refused; a short payment puts the order on hold instead of marking it paid. Update before your Fungies store charges repeated offers once.

= 2.4.4 =
Fixes the customer email prefill on the Fungies hosted checkout page (was sending the wrong query parameter name). Recommended update.

= 2.4.3 =
Plugin Check follow-up: fixes the scope of three slow-query phpcs:ignore annotations from 2.4.2 (single-line ignores didn't reach the array-key tokens). No runtime changes.

= 2.4.2 =
WordPress.org Plugin Check pass: prepared SQL, translator comments, ordered placeholders, and justified phpcs:ignore annotations on direct DB reads. No runtime changes.

= 2.4.1 =
WP.org compliance: declares WooCommerce as a `Requires Plugins` dependency and renames the text domain to match the plugin slug. Recommended.

= 2.4.0 =
Security hardening (URL validation, image SSRF allowlist), opt-in debug logging, i18n fixes, and cleaner front-end JS. Recommended update.

= 2.3.1 =
Coupons are now pushed to Fungies the moment they are saved in WooCommerce, instead of waiting for the hourly cron or a manual sync. Recommended update.

= 2.3.0 =
WooCommerce coupon codes applied at checkout are now forwarded to the Fungies hosted checkout, so totals stay in sync after discount.

= 2.2.0 =
Adds WooCommerce → Fungies coupon sync. Run "Sync Now" once after updating to push your existing coupons.
