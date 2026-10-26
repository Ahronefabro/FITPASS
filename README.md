# FitPass

A gym membership and attendance system with QR code check-in.

- **Web portal** (Laravel): the owner and staff side, including members, payments, check-ins, the QR scanner and reports.
- **Mobile app** (Flutter, in `mobile/`): the member side. Members sign up, show their QR code at the front desk, and see their membership and visit history.

Members who sign up in the app stay **pending** until staff confirm their payment on the staff **Members** page. Their QR code works after that.

---

## 1. What to install

| Tool | Version | Used for |
|---|---|---|
| [XAMPP](https://www.apachefriends.org/) | **PHP 8.2 or newer** | PHP and MySQL |
| [Composer](https://getcomposer.org/download/) | latest | Laravel packages |
| [Git](https://git-scm.com/) | latest | Getting the code |
| [Flutter](https://docs.flutter.dev/get-started/install/windows) | 3.x | The mobile app only |
| Google Chrome | latest | Running the app and the scanner |

Run `php -v` to check your PHP version. If it shows 8.0 or 8.1, install a newer XAMPP first, because the project will not install on older PHP.

---

## 2. Get the code

```powershell
git clone https://github.com/Ahronefabro/FITPASS.git
cd FITPASS
```

If you cloned it before, run `git pull` instead to get the latest version.

---

## 3. Set up the web portal (first time only)

1. Open the **XAMPP Control Panel** and click **Start** next to **MySQL**.
2. Open http://localhost/phpmyadmin, click **New**, and create a database named **`fitpass`**.
3. In a terminal inside the `FITPASS` folder, run:

```powershell
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
```

`.env.example` already uses the XAMPP defaults: user `root`, no password, database `fitpass`. If your MySQL is set up differently, edit the `DB_` lines in `.env`.

---

## 4. Run the web portal (every time)

With MySQL running in XAMPP, run:

```powershell
php artisan serve --host=0.0.0.0 --port=8000
```

Open **http://localhost:8000** and log in with one of these accounts:

| Portal | Username | Password |
|---|---|---|
| Owner | `admin` | `password` |
| Staff | `staff` | `password` |

Keep this terminal open while you use the app, because the app needs the server running.

---

## 5. Run the mobile app

Open a **second terminal**:

```powershell
cd mobile
flutter pub get
```

Then choose one of these:

**In Chrome (easiest)**
```powershell
flutter run -d chrome
```

**On the Android emulator**
```powershell
flutter run
```

**On a real Android phone**

The phone must be on the **same Wi-Fi** as your computer. Run `ipconfig` and copy your **IPv4 Address** (for example `192.168.1.10`), then:

```powershell
flutter run --dart-define=API_URL=http://192.168.1.10:8000
```

**To build an APK file to install on a phone:**
```powershell
flutter build apk --release --dart-define=API_URL=http://192.168.1.10:8000
```
The APK is saved to `mobile\build\app\outputs\flutter-apk\app-release.apk`. Each APK only works with the computer whose IP was used to build it, so everyone builds their own.

---

## 6. Try the whole flow

1. In the app, tap **Sign up** and create an account. The home screen shows **Waiting for payment**.
2. In the web portal, log in as **staff**, open **Members**, and click **Confirm payment** on the new sign-up.
3. In the app, log in and pull down on the home screen. Your QR code appears.
4. In the web portal, open the staff **Scanner** at **http://localhost:8000/staff/scanner** and show the QR code to the webcam. The member is checked in, and the visit appears in the app's **History** tab.

The scanner camera only works on **localhost** or **https://**. Open the scanner on the computer running the server, not through an IP address like `192.168.x.x`.

---

## Troubleshooting

| Problem | Fix |
|---|---|
| `vendor/autoload.php` not found | Run `composer install` |
| `Your php version does not satisfy...` | Install a XAMPP with PHP 8.2 or newer |
| `No application encryption key` | Run `php artisan key:generate` |
| `Unknown database 'fitpass'` | Create the `fitpass` database in phpMyAdmin |
| `No connection could be made` / page hangs | Start **MySQL** in XAMPP |
| Login says wrong username or password | Run `php artisan migrate --seed` to create the accounts |
| App says **Could not reach the FitPass server** | Make sure `php artisan serve --host=0.0.0.0 --port=8000` is running. On a phone, check that you used your own IP and are on the same Wi-Fi |
| Phone still cannot connect | Allow the server through Windows Firewall. In PowerShell **as Administrator**, run `New-NetFirewallRule -DisplayName "FitPass 8000" -Direction Inbound -Protocol TCP -LocalPort 8000 -Action Allow -Profile Private` |
| Scanner says the camera only works over https or localhost | Open the scanner at `http://localhost:8000/staff/scanner` |
| APK build fails asking for an NDK | Install it from Android Studio: **SDK Manager**, **SDK Tools**, **NDK (Side by side)** |

## Running the tests

The tests use a separate database. Create an empty database named **`fitpass_test`** in phpMyAdmin, then run:

```powershell
php artisan test
```
