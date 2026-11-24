# BlackStrike — Guns & Ammunition · v2.3

Inventory, point of sale, catalog by gun model, and printable compliance reports for a licensed firearms and ammunition dealer.
Built with plain **PHP 8 + MySQL/MariaDB** (no frameworks, no Composer) and vanilla HTML/CSS/JS, so it runs on a stock XAMPP install.

---

## ▶ Try it

| | What you get | How |
|---|---|---|
| **Live demo** | Click through every screen with sample data, right in your browser. Read-only: buttons that save show a *Demo mode* message. | **https://oticosven-byte.github.io/blackstrike/** — sign in with any username. |
| **Full app on GitHub** | The real PHP app running in GitHub Codespaces (PHP 8.3 + SQLite, no install). | On this repo click **Code → Codespaces → Create codespace on main**. Wait for the browser tab to open (or open the **Ports** tab → port **8080**), then run the installer. |
| **Full app on your PC** | The real app with MySQL. | Follow section 1 below (XAMPP). |

GitHub Pages only serves static files and can't run PHP or MySQL, so the live demo in `demo/` is a set of pages generated from the app with its sample data. The PHP app itself is everything else in this repository.

---

## 1. Install on XAMPP (Windows / macOS / Linux)

**Requirements:** XAMPP with PHP 8.0 or newer (any XAMPP from 2021 onward). Nothing else to install.

1. **Start the servers.** Open the **XAMPP Control Panel** and click **Start** next to **Apache** and **MySQL**. Both should turn green.
2. **Copy the project.** Unzip the package and copy the `blackstrike` folder into XAMPP's web root:
   - Windows: `C:\xampp\htdocs\blackstrike`
   - macOS: `/Applications/XAMPP/htdocs/blackstrike`
   - Linux: `/opt/lampp/htdocs/blackstrike`

   (Upgrading from an earlier version? Keep your existing folder and copy the new files over it, **except `config.php`**. The database name stays `jn_guns`, so your data is kept. New database columns and tables (customer accounts, shop photos, purchase requests) are added automatically the first time you open any page.)

   Check that `index.php` sits directly inside `htdocs\blackstrike\`, not in `htdocs\blackstrike\blackstrike\`.
3. **Run the installer.** In your browser, go to **http://localhost/blackstrike/install.php**
   - Enter the administrator's name, username and password.
   - Leave **Load sample data** ticked if you want demo products, serial-numbered firearms and 7 months of sales to explore. Untick it for a clean shop.
   - Click **Install now**. The installer creates the `jn_guns` database and all tables for you.
4. **Sign in.** Go to **http://localhost/blackstrike/** and sign in with the admin account you just created.
   With sample data you also get a staff account (`staff` / `Staff@1234`) and a customer account (`user` / `User@1234`) that already has a purchase history.
5. **Set up your shop.** Go to **Settings → Shop details** to change the store name or logo caption if needed, and enter your real address, dealer license number and expiry, TIN, and regulator. These appear on every receipt and official report.
6. **(Recommended)** Delete `install.php` once installation is done.

### If something goes wrong

| Problem | Fix |
|---|---|
| “Could not reach MySQL” | MySQL isn't running. Start it in the XAMPP Control Panel. If port 3306 is taken, change `port` in `config.php`. |
| “MySQL rejected the username/password” | You set a root password in XAMPP. Put it in `config.php` → `'password' => 'yourpassword'`. |
| 404 Not Found | The folder isn't directly under `htdocs`, or it's named differently. The URL must match the folder name. |
| Page shows raw PHP code | Apache isn't running, or you opened the file directly (`file:///…`). Always use `http://localhost/…`. |
| Fonts look plain | The headline fonts load from Google Fonts. Offline, the app falls back to system fonts and still works. |
| Need to reinstall | Delete `storage/installed.lock`, open `install.php` again and tick **Replace existing data**. |
| See detailed errors while developing | Set `'debug' => true` in `config.php`. Turn it off again afterwards. |

### Default accounts for all three roles
| Role | Username | Password | Lands on |
|---|---|---|---|
| Administrator | `admin` | `Admin@1234` | Dashboard |
| Staff | `staff` | `Staff@1234` | Dashboard |
| Customer | `user` | `User@1234` | My Account |

If the site is already installed, create or reset all three accounts at once: in phpMyAdmin, select the `jn_guns` database, open the **SQL** tab, paste the contents of `database/default_accounts.sql` and click **Go**. Change the passwords afterwards.

### Manual database import (alternative to the installer)
In phpMyAdmin (http://localhost/phpmyadmin), open **Import**, choose `database/schema.sql` and click **Go**.
This creates the tables and the three default accounts above. Then create an empty file called `storage/installed.lock`, sign in, and change the password straight away. The manual import does not include sample data.

---

## 2. What's included

| Area | Features |
|---|---|
| **Dashboard** | **Interactive 360° firearm viewer** in the hero: it rotates on its own, you can drag or swipe it (with momentum) or use the ←/→, pause and keyboard controls, and it shows the current best-selling firearm. Also live stock units and value, ammo health, month-to-date sales vs. last month, firearms on hand, 14-day sales chart, low-stock list, recent sales and activity, plus warnings when stock is low or the dealer license is close to expiry. |
| **Inventory** | **Card view** (the default) groups products into Firearms, Ammunition and Accessories. Each card shows the maker, caliber, model fit, stock meter, serials on hand, price and quick actions, and you can switch to a **Table** view. Search (press `/`), filters, sortable columns, CSV export, add/edit/archive products (admin), **receive stock** with supplier and DR/PO numbers, **stock adjustments** with reasons (admin), per-product stock card, and a **firearm serial register**. |
| **Sales (POS)** | Multi-item cart. Firearms are sold by exact serial number. Stock is checked live and enforced on the server so items can't be oversold. **License verification is required** for firearm and ammunition sales: license type, number, expiry (expired licenses are blocked) and a confirmation checkbox. Includes a printable receipt and void with reason (admin), which restores stock and serials. |
| **Customer shop** | The home page for customers and visitors. It has a product grid with photos, category tabs, caliber filter, sorting, search and an in-stock toggle. **Clicking any product** opens a large view with a photo gallery (click to enlarge full screen, click again to zoom, drag to pan), specs, compatible ammunition or firearms, and a **purchase request form**. The form collects buyer details, license, government ID, intended use, pickup date, payment preference and declarations. A cart lets customers request several items at once through the same form. |
| **Catalog** | Public page organized **by gun model**. Each model shows the firearm, compatible ammunition (matched by caliber) and fitted accessories. Filter by platform and caliber. Admins manage models under **Gun Models**. |
| **Reports** | Revenue by month, revenue by category, top products, and **6 official printable reports** (below). |
| **Settings** | Profile and password for every user. Shop details for admins. |
| **Users (admin)** | Create staff and admin accounts, change roles, reset passwords, deactivate accounts. You can't lock yourself out or remove the last admin. |
| **Activity log (admin)** | Every sign-in, sale, void, adjustment, report and setting change, with user and IP. |
| **About the Shop** | Public page with your story, hours, contact details, dealer license and buying requirements. |

### Online purchase requests: how they work
1. A signed-in customer opens a product in **Shop** and fills in the **Purchase form**, or adds several items to the cart and checks out. Visitors are asked to sign in or create an account first.
2. The request gets an **O-** number (for example `O-20261122-0001`) and appears under **My Orders** for the customer and under **Sales → Online requests** for staff. The dashboard shows how many are waiting and the next pickup date.
3. **Nothing is sold or reserved yet.** When the buyer arrives, staff open the request and click **Complete in POS**. The items and buyer details are pre-filled; staff choose the exact serial numbers, inspect the original license and ID, take payment and complete the sale. The request is then marked *Completed in store* and linked to the sale.
4. Staff can **decline** a request with a reason, and customers can **cancel** their own pending requests. Either way, the customer sees the outcome in My Orders.
5. Online requests are kept out of revenue figures and the Sales Register report. Only completed in-store sales count.

### Product photos
Admins can add photos under **Inventory → Edit product → Photos**: JPG, PNG, WEBP or GIF, up to 6 MB each, several at once. The first photo is the main one in the shop; use ✓ to make another photo the main one, or the bin icon to delete one. Photos are saved in `uploads/products/`. The built-in photos in `assets/images/` are matched to products by name. Products without a photo show an illustration instead.

If uploads fail on XAMPP, open `php.ini` (XAMPP Control Panel → Apache → Config) and check `file_uploads = On`, `upload_max_filesize = 8M` and `post_max_size = 40M`, then restart Apache.

### Printable official reports (Reports → Official printable reports)
Each report gets a **unique control number** (for example `BS-FAD-20261121-0001`) and is recorded in the **report register** so it can be reprinted later. Every report has your letterhead (name, address, dealer license no., TIN, regulator), a summary, the detailed table, a certification statement, and signature lines for *Prepared by / Checked by / Approved by*. Page numbers appear in Chrome and Edge.

| Code | Report | Purpose |
|---|---|---|
| INV | Inventory Status Report | Stock on hand and valuation by category; physical-count support |
| FAD | Firearms Acquisition & Disposition Record | Serial-level record: date acquired, source, date disposed, buyer, address, license, sale no.; opening and closing balances |
| AMS | Ammunition Sales Log | Every ammo sale with buyer, license, caliber, boxes and **total rounds** |
| SLR | Sales Register | All transactions, including voids, with payment-method totals |
| STM | Stock Movement Report | Audit trail of every stock change with running balance and user |
| RLS | Reorder & Low Stock Report | What to buy, with suggested order quantities |

To print: click **Generate & print**. The print dialog opens automatically. Choose **Save as PDF** to file a digital copy. Firearm, ammunition and stock-movement reports print in landscape. Each report can also be downloaded as **CSV** (opens in Excel).

> **Compliance note:** these reports are designed around common dealer record-keeping requirements (serial-level acquisition/disposition and buyer license details). Formats required by regulators change, so confirm the exact fields and submission format with your regulator, for example the PNP Firearms and Explosives Office, before relying on them for official submissions.

---

## 3. Roles

| Action | Customer | Staff | Admin |
|---|:-:|:-:|:-:|
| Browse catalog and About page | ✓ | ✓ | ✓ |
| **Shop**: large product photos, purchase request form, cart | ✓ | | |
| **My Orders**: purchase requests (cancel pending), purchase history, print own receipts, license status | ✓ | | |
| Edit own profile, contact details and password | ✓ | ✓ | ✓ |
| Keep own license details on file | ✓ | | |
| Dashboard, inventory, serial register | | ✓ | ✓ |
| Record sales (optionally linked to a customer account), print receipts | | ✓ | ✓ |
| Receive stock | | ✓ | ✓ |
| Generate and print reports, CSV export | | ✓ | ✓ |
| Add/edit/archive products, adjust stock, void sales | | | ✓ |
| Manage gun models, users (incl. customers), shop settings, activity log | | | ✓ |

**Customer accounts.** Customers can register themselves from the sign-in page (**Create an account**). An admin can turn this off under *Settings → Shop details → Customer self-registration*. Admins can also create customer accounts under **Users → Add customer**. At checkout, staff choose the customer's account in **New sale** to fill in their saved name, address and license. The sale then appears in that customer's purchase history. Staff must still inspect the original license and ID, so the verification box is never ticked automatically.

## 4. Project structure

```
blackstrike/
├── index.php            Front controller (index.php?page=…)
├── login.php / logout.php / register.php
├── install.php          One-time setup wizard
├── print.php            Printable receipts and official reports
├── export.php           CSV exports
├── config.php           Database connection settings
├── includes/            Bootstrap, DB layer, auth, helpers, layout, icons, schema, reports, shop (photos & requests)
├── pages/               One file per screen (dashboard, inventory, sale_new, catalog, reports, …)
├── assets/css/          styles.css (app) + print.css (reports/receipts)
├── assets/js/app.js     UI behaviour: menu, toasts, dialogs, sorting, search, POS, shop gallery/zoom, cart
├── assets/images/       Built-in product photos
├── uploads/products/    Photos uploaded by admins (scripts blocked by .htaccess)
├── database/schema.sql  MySQL schema for manual import (includes the 3 default accounts)
├── database/default_accounts.sql  Create/reset admin, staff and customer accounts
└── storage/             installed.lock (and the SQLite file if you use SQLite)
```

### Security built in
- Passwords hashed with `password_hash()` (bcrypt). Sign-in lockout after 5 failed attempts.
- Every form protected with a CSRF token. All output escaped. All queries use prepared statements.
- Session cookies are HttpOnly and SameSite=Lax, and the session ID is regenerated at sign-in.
- Role checks run on the server for every admin page and action.
- `.htaccess` files block browser access to `config.php`, `includes/`, `pages/`, `database/` and `storage/`.
- Stock changes and sales run in database transactions, with guarded updates that prevent overselling.
- CSV exports are protected against spreadsheet formula injection.

### Going live (beyond localhost)
Set a MySQL password and a dedicated database user, keep `debug` off, serve over HTTPS, delete `install.php`, and back up the `jn_guns` database regularly (phpMyAdmin → Export).
