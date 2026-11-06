# AGENTS.md — Moloni for OpenCart 3

Guidance for AI coding agents (Claude Code, Cursor, Copilot, …) and new developers working in this plugin. This is the canonical context file; `CLAUDE.md` imports it.

## What this plugin is

**Moloni - Invoice solutions** (OCMOD code `moloni_invoice_solutions`, version in `install.xml`) is an OpenCart 3.0.x admin module that connects an OpenCart store to Moloni (moloni.pt) invoicing. It turns OpenCart orders into Moloni fiscal documents (invoices, invoice-receipts, simplified invoices, bills of lading, …), creates the customers and products those documents need, pushes product saves to Moloni, and can import products from Moloni into the catalog. It is a PHP module that talks to Moloni **only over the public API v1** (`https://api.moloni.pt/v1/`, set in `system/library/moloni/dependencies/connection.class.php`). It does not run alongside any Moloni platform or call internal services. Merchants install it from a `.zip` downloaded from plugins.moloni.com (see `README.md`). **Supported:** OpenCart 3.0.x on PHP 7.x. Keep the code PHP 7.0-safe.

## Responsibilities & boundaries

**Owns** (reacts to OpenCart **events** registered in `install()`, plus admin actions; no webhooks, no cron):
- **Document generation, manual:** the module's **Home** page lists orders with no Moloni document (`getIndexData()` → `ocdb->getOrdersAll()`, filtered by the `order_statuses` setting, max 100). You can generate one, generate several (`order_id` is a JSON array), or **discard** them (`action=delete`). All of these go through `invoice()` → `createDocumentFromOrder()`. The OCMOD also adds a per-order button to **Sales › Orders** (`invoiceButtonCheck`, event `admin/view/sale/order_list/before`).
- **Document generation, automatic:** event `catalog/model/checkout/order/addOrderHistory/after` → `catalog/controller/extension/module/moloni.php::eventCreateDocument`. When the new status is in the hard-coded list `[3, 5, 11, 15]`, it redirects the request back into the admin `extension/module/moloni/invoice&evento=moloni` route, using the `HTTP_REFERER` and the `user_token` taken from it. `invoice()` only generates the document when the `order_auto` setting is on, and returns JSON. This only works when the status change comes from the admin order page (the admin → catalog API call carries an admin referer). Storefront checkouts and payment callbacks never reach the admin route.
- **Product push:** events `admin/model/catalog/product/addProduct/after` and `.../editProduct/after` → `eventProductCheck`. It saves option `moloni_reference`s, and when `products_auto` = 1 it calls `eventProductHandler` → `products/insert|update`.
- **Product import (manual):** Settings › import → `importProducts()`. It pages `products/getModifiedSince` (50 per page) and creates or edits OpenCart products, categories (`products/getCategoryTree`) and images. Only simple products (`composition_type` 0) are created.
- **Admin UI:** the Moloni left-menu entry (`injectAdminMenuItem`, event `admin/view/common/column_left/before`), the Home / Documents / Settings pages (Twig), and the option-reference column on the product form (`optionsReferenceCheck` + OCMOD edit of `catalog/product_form.twig`).
- **Self-update:** `versionCheck()` polls `plugins.moloni.com/opencart3/release`. `?update=true` runs `githubUpdate()`, which **overwrites every `.php/.twig/.css` file of the install** from GitHub `master` and then clears `DIR_CACHE`.

**Must not:**
- **double-issue a document.** The `moloni_documents` row per order is the idempotency guard. `force` bypasses it on purpose ("Gerar novamente"), and discard writes a marker row.
- invent tax/exemption/document-type mappings. Those are fiscal-legal: taxes are matched to Moloni taxes by rate + SAF-T type, and exemption reasons come from settings.
- close a document whose total doesn't match the order. Documents are always inserted as draft (`status` 0) and only closed when `net_value` matches and there are no negative lines.
- hard-code a company or an endpoint, or commit credentials. Note that the API `client_id`/`client_secret` pair is already hard-coded in `start()`. Don't copy it anywhere else.
- issue credit notes. **Not supported, out of scope by design:** there is no refund → credit-note flow in this plugin.

**Moloni API surface used** (v1, `POST` form-encoded, `?access_token=` in the query): `grant` (password + refresh_token), `companies/getAll|getOne`, `documentSets/getAll`, per-type documents `<type>/insert|update|getOne|getPDFLink` (types: `invoices`, `invoiceReceipts`, `simplifiedInvoices`, `billsOfLading`, `deliveryNotes`, `purchaseOrder`, `internalDocuments`, `estimates`; the generic `documents/getOne|getPDFLink` is also used), `customers/getByVat|getBySearch|getByNumber|getNextNumber|insert|update`, `products/getByReference|insert|update|getModifiedSince|getCategoryTree`, `productCategories/getAll|insert`, `taxes/getAll`, `taxExemptions/getAll`, `paymentMethods/getAll|insert`, `deliveryMethods/getAll|insert`, `maturityDates/getAll`, `measurementUnits/getAll`, `countries/getAll`, `currencyExchange/getAll`, `economicActivityClassificationCodes/getAll`. Product images are downloaded from `www.moloni.pt/_imagens/` (not the API). Some wrappers in `system/library/moloni/classes/` (`alternate`, `suppliers`, `salesmen`, `subscription`, `account`, `customers::getLastNumber`) are present but not called.

## Architecture map

| Path | What it is |
| --- | --- |
| `install.xml` | OCMOD: name/version/author, adds the Moloni button to `sale/order_list.twig` and the `moloni_reference` option column to `catalog/product_form.twig` |
| `admin/controller/extension/module/moloni.php` | **Everything else.** Pages (`index`, `settings`, `documents`, `invoice`, `importProducts`), `install()`/`uninstall()` (tables + event registration), event handlers, order → document mapping (`createDocumentFromOrder`, `moloniCustomerHandler`, `moloniProductHandler`, `moloniShippingHandler`), `tool*` helpers (taxes, prices, discounts, zip codes, categories, payment/delivery methods), self-update |
| `catalog/controller/extension/module/moloni.php` | `eventCreateDocument`: the catalog-side order-history event that redirects to the admin `invoice` route |
| `admin/model/extension/module/moloni/install.php` | Creates `moloni` (tokens), `moloni_settings`, `moloni_documents` and the `product_option_value.moloni_reference` column. Drops the first two on uninstall |
| `admin/model/extension/module/moloni/ocdb.php` | All raw SQL: tokens, per-company/per-store settings (OC cache), the documents ledger, order lists, tax rules/geo-zones, store locations |
| `system/library/moloni.php` | `moloni` façade loaded via `$this->load->library('moloni')`. It lazy-loads `moloni/classes/<name>.class.php` by property name, and `documents('<type>')` sets the endpoint prefix |
| `system/library/moloni/dependencies/connection.class.php` | API client: base URL, `curl()`, login/refresh, token expiry, image download |
| `system/library/moloni/dependencies/errors.class.php` | Error collector + translation of API validation codes into pt-PT messages |
| `system/library/moloni/dependencies/debug.class.php` | Per-request API call log, shown when the `debug_console` setting is on |
| `system/library/moloni/classes/` | One thin wrapper per Moloni resource (`products`, `customers`, `documents`, `taxes`, `categories`, …) |
| `admin/view/template/extension/module/moloni/` | Twig views: `login`, `companies`, `home`, `documents`, `settings`, `store_list`, `errors`, `debug`, `update` |
| `admin/view/stylesheet/moloni/moloni-sheet.css` | Module CSS |
| `admin/language/{en-gb,pt-pt}/extension/module/moloni.php` | Language strings |

The repo mirrors the OpenCart root layout (its `.gitignore` excludes the core files), so it overlays a store's root directly. No Composer, no npm build (`.nvmrc` exists but there is no `package.json`), and no git submodules.

## Running locally

1. **Store:** a local OpenCart 3.0.x on PHP 7.x + MySQL. The repo has no Docker or dev setup. Either copy or symlink `admin/`, `catalog/` and `system/` over the store root, or install the release `.zip` through **Extensions › Installer**.
2. **Extensions › Modifications → Refresh**, so `install.xml` is applied to the order list and the product form. Repeat this after any change to `install.xml`.
3. **Extensions › Extensions › Modules → Moloni → Install.** This creates the tables and registers the events. Give the admin user group access/modify permission on `extension/module/moloni`.
4. Open **Moloni** in the left menu, log in with a Moloni account and pick the company. Then fill **Settings** per store: document set, document type, status, measure unit, maturity date, exemption reasons, order statuses, and so on. Moloni credentials and settings are configured only in the module's admin screens and stored in the store DB. They are never committed.

## Commands

| Command | Where | What it does |
| --- | --- | --- |
| `php -l <file>` | root | Syntax check (the only static check available) |
| Extensions › Modifications › Refresh | OpenCart admin | Re-apply `install.xml` |
| Modules › Moloni › Uninstall + Install | OpenCart admin | Re-register events / recreate tables (needed after changing `install()`) |

## Verification

**There is no automated test or lint gate.** The repo has no `phpunit`, `phpcs` or `phpstan` config, no CI workflow and no Composer. To verify a change:

1. `php -l` every touched PHP file. Keep syntax PHP 7.0-safe: no typed properties, `match`, nullsafe `?->`, constructor promotion or `readonly`. The code uses `array()`/`[]` and `?:` only.
2. **Exercise the behaviour in a local OpenCart 3 store:** generate a document from the Home list, from the order-list button, and through an admin status change with `order_auto` on. Also check discard, the product save → Moloni product, the product import and the settings save. Use a Moloni test company and keep documents as **drafts** (`document_status` = draft) unless closing them is the point of the test.
3. Turn on `debug_console` in Settings to see every API request and response on the page.

## Conventions

- **OpenCart 3 MVC + events.** Routes are `extension/module/moloni/<method>`. Models load via `modelsRequired` → `$this->install` / `$this->ocdb`. Host hooks are `model_setting_event->addEvent(...)` in `install()` (and the matching `deleteEventByCode` in `uninstall()`), and template changes go into `install.xml` OCMOD operations. Don't edit core files.
- **Every admin page calls `start()` first.** It loads the library and language, restores and refreshes tokens, saves a posted settings form, and loads settings. `allowed()` then routes to `login` / `companies` when there is no session or company.
- **Settings** live in `moloni_settings`, keyed by `(company_id, store_id, label)`. Read them via `$this->settings['<label>']` with an `isset` guard. Only `order_statuses` is JSON-decoded, and the rest are strings.
- **API calls** go through `$this->moloni-><resource>-><method>()` → `connection->curl('<resource>/<method>', $values)`. Wrapper classes inject `company_id`, and failures land in `$this->moloni->errors` (not exceptions). Check `errors->getError('all')` before inserting.
- **Merchant-facing UI uses OpenCart 3 admin conventions** (Bootstrap 3 / Font Awesome 4 markup, as the existing Twig views render). Match what `admin/view/template/extension/module/moloni/` already does.
- **User-facing strings are pt-PT.** Many are inline in the controller, others are in the language files.
- **Branch flow:** cut feature branches from `devel` and open PRs into `devel`. A release is a merge of `devel` → `master`. `master` is what ships to merchants (see the self-update gotcha).
- **Release bump:** bump `<version>` in `install.xml` with each release. The controller's `$version` (`v1.03`) is a separate, stale value compared by `versionCheck()`. Don't treat it as the plugin version.
- **Idempotency + fiscal correctness are load-bearing.** Document creation must not double-issue, and tax/document-type/exemption mappings are fiscal-legal.

## Gotchas

Curated, verified conventions and traps. This is the default-trusted source — every agent reads it.

- **Events are registered only in `install()`.** `patch()` is empty and there is no upgrade path. A new or renamed event needs an uninstall/reinstall in every store, and **uninstall drops `moloni` and `moloni_settings`** (tokens and all settings). It keeps `moloni_documents` and the `moloni_reference` column.
- **The auto-invoice status list is hard-coded** in `catalog/controller/extension/module/moloni.php` (`[3, 5, 11, 15]`, the default ids for Shipped/Complete/Refunded/Processed). This is a **known legacy bug**: the list ignores the `order_statuses` setting, and **11 = Refunded triggers an invoice**.
- **`ocdb` builds SQL by string concatenation without escaping** (settings values, tokens, labels, `setDocumentInserted`). A quote in a setting value breaks the query. Use `$this->db->escape()` / `(int)` in anything you add or touch.
- **The `order_since` filter is a no-op.** `getOrdersAll()` only applies it when `is_array($order_since)`, but the setting is stored as a string.
- **The ledger row is only written after the main document is inserted.** With `shipping_document` on, a bill of lading is inserted first. If the main insert then fails, no row is written and a retry issues another bill of lading.
- **Discard writes a fake ledger row** (`invoice_id = '10'`). Anything that reads `moloni_documents.invoice_id` as a real Moloni id (the Documents page calls `getOne` on it) has to expect it.
- **Product reference** = prefix + (sku ?: model ?: product_id) + option suffix. Spaces become `_` unless `replace_white_space` is off, and the result is **truncated to 28 chars**. The order flow and `eventProductHandler` must build it the same way, or the products drift apart.
- **Shipping/fees lines use fixed references `Portes` / `Taxa`.** They are created once (with the taxes of that first order) and never updated. Negative totals are skipped and force the document to stay a draft (`hasNegative`).
- **Settings are cached in the OpenCart cache** (`moloni_settings<company><store>`) and invalidated only by the plugin's own writes. A manual DB edit needs a cache clear.
- **Tokens:** a single-row table. The access token is treated as valid for 50 minutes, and the refresh is refused 5 days after expiry, so the merchant has to log in again.
- **Self-update overwrites the install from GitHub `master`** (`githubUpdate()`). It targets `$git_user`/`$git_repo`/`$git_branch` = `moloni`/`opencart3`/`master`. That GitHub repo redirects to this one, so the updater pulls **this repo's `master`**. Whatever is merged to `master` ships to every merchant who clicks "update".
- **`eventProductHandler` `exit`s on a failed save** (`print_r` + `exit`), which aborts the admin product save response.
- **No submodules** (`.gitmodules` absent).

> Recent, **unverified** findings live in `.claude/journal/` (one dated file per finding). They are promoted into this section by manual curation once re-verified against the code — treat them as leads, not yet rules.
