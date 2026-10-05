# MediCare - Part 1 (Laravel, no Node)

Marketplace + CRM foundation: public doctor search & booking, patient login, doctor dashboard, admin panel.

## Setup
1. Create a fresh Laravel project:
   composer create-project laravel/laravel medicare
2. Copy the folders from this zip (app, database, resources, routes) INTO that project. Say yes to overwrite
   (this replaces app/Models/User.php, routes/web.php, database/seeders/DatabaseSeeder.php).
3. Database: easiest is the default SQLite (nothing to set). For MySQL, edit .env (DB_CONNECTION=mysql, DB_DATABASE=medicare ...).
4. Run:
   php artisan migrate:fresh --seed
   php artisan serve
5. Open http://127.0.0.1:8000

## Demo logins (password: password)
- Admin:   admin@medicare.test  -> /admin/dashboard
- Doctor:  doctor1@medicare.test (doctor1..doctor6)
- Patient: use Sign up on the website

No npm, no Vite. Bootstrap 5, icons and the font load from CDN (internet needed in the browser).

## Online payment (Razorpay)

1. Create a Razorpay account, then Dashboard > Account & Settings > API Keys > Generate. Put `RAZORPAY_KEY_ID` and `RAZORPAY_KEY_SECRET` in `.env` (start with `rzp_test_` keys).
2. Dashboard > Webhooks > Add: URL `https://YOUR-DOMAIN/razorpay/webhook`, events `payment.captured` and `order.paid`, choose a secret and put it in `RAZORPAY_WEBHOOK_SECRET`. (The webhook confirms payments even if the patient closes the browser right after paying.)
3. Dashboard > Account & Settings > Payment Capture: keep it on **automatic**.
4. Run `php artisan migrate` and `php artisan config:clear`.
5. Keep the queue worker running (`php artisan queue:work`) and the scheduler (`php artisan schedule:work` or the cron). The worker sends refunds, the scheduler releases unpaid bookings.

How it behaves: with keys set, video visits are always prepaid and clinic visits can be prepaid by choice. The slot is held for `PAYMENT_WINDOW_MINUTES`; the doctor is told (and can confirm) only after the money is in. Cancelling or an expiry of a paid visit refunds the full amount automatically; if a refund keeps failing the reason is stored in `transactions.refund_error` and you can refund it from the Razorpay dashboard. Without keys nothing changes: fees are paid at the clinic.
