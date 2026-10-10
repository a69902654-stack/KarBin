# 🎥 سیستم هوشمند پایش نیروی کار

اپلیکیشن وب فارسی (راست‌چین) برای پایش ایمنی و بهره‌وری نیروی کار با استفاده از **بینایی ماشین** در مرورگر: تشخیص چهره، تشخیص وضعیت و فعالیت بدن، مدیریت مسیرها و نواحی کاری، و ثبت خودکار زمان کار — همراه با داشبورد مدیریتی و گزارش‌گیری.

> A Persian (RTL) web app for workforce monitoring using browser-based computer vision: face capture, pose/activity detection, work-zone management, automatic work-time logging, and an admin dashboard with reports.

---

## ✨ امکانات

- **📷 صفحهٔ دوربین زنده** — نمایش زنده از وب‌کم یا فایل ویدیو با کادر ردیابی روی سوژه
- **🙂 تشخیص چهره** — ذخیرهٔ نمونه چهرهٔ کارگران و شناسایی آن‌ها با `face-api.js`
- **🤸 تشخیص فعالیت** — تشخیص لحظه‌ای وضعیت بدن (راه رفتن، ایستادن، نشستن و…) با Pose Detection در مرورگر
- **🗺 مدیریت مسیرها** — تعریف نواحی/مسیرهای کاری روی نقشه و انتصاب کارگران به هر مسیر
- **⏱ ثبت زمان کار** — شروع و پایان خودکار جلسات کاری هر کارگر با محاسبهٔ مدت
- **📊 داشبورد و گزارش‌ها** — آمار کلی، گزارش فعالیت‌ها، لاگ رویدادهای دوربین با خروجی اکسل (CSV)، و گزارش زمان کار
- **🔐 پنل امن** — ورود ادمین با رمز هش‌شده (`bcrypt`) و کنترل دسترسی صفحات مدیریتی

## 🛠 فناوری‌ها

| لایه | موارد |
|------|-------|
| Backend | PHP 7.4+ ، MySQL/MariaDB ، PDO |
| Frontend | JavaScript خام ، Tailwind CSS ، Bootstrap 5 |
| بینایی ماشین | face-api.js ، TensorFlow.js (Pose Detection) |
| سایر | Font Awesome ، CDNs |

## 📁 ساختار پروژه

```
├── config.php              # اتصال دیتابیس (PDO)
├── index.php               # صفحهٔ دوربین و تشخیص فعالیت
├── kar_db.sql              # اسکیمای دیتابیس (+ داده اولیه)
├── add_work_logs.sql       # migration جدول work_logs (اجرا الزامی است)
├── public/                 # صفحات پنل مدیریت
│   ├── login.php           # ورود ادمین
│   ├── dashboard-new.php   # داشبورد اصلی
│   ├── workers-new.php     # مدیریت کارگران
│   ├── paths-new.php       # مدیریت مسیرها
│   ├── camera-new.php      # نمای دوربین
│   ├── activities.php      # گزارش فعالیت‌ها
│   ├── activity_logs.php   # لاگ رویدادها + خروجی اکسل
│   ├── work_reports.php    # گزارش زمان کار
│   └── face_recognition.php / save_face.php / compare_face.php
├── api/                    # endpoint های JSON
│   ├── workers.php         # CRUD کارگران
│   ├── paths.php           # فهرست مسیرها
│   ├── activities.php      # ثبت/خواندن فعالیت‌ها
│   └── lookup.php          # یافتن کارگرِ یک مسیر
├── assets/                 # CSS و ماژول‌های JS (تشخیص حرکت، دوربین)
└── uploads/                # تصاویر کارگران و نمونه‌های چهره
```

## 🚀 راه‌اندازی

**پیش‌نیاز:** PHP 7.4 یا بالاتر ، MySQL/MariaDB ، Apache (XAMPP / MAMP / WAMP)

1. پوشهٔ پروژه را در ریشهٔ وب‌سرور کپی کنید (مثلاً `htdocs/kar`)
2. در phpMyAdmin فایل `kar_db.sql` را import کنید
3. **فایل `add_work_logs.sql` را هم اجرا کنید** (برای فعال شدن ثبت و گزارش زمان کار الزامی است)
4. اطلاعات دیتابیس را در `config.php` تنظیم کنید:

```php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'kar_db');
define('DB_USER', 'root');
define('DB_PASS', '');
```

5. مرورگر را باز کنید: `http://localhost/kar/public/login.php`

**ورود پیش‌فرض:**

| نام کاربری | رمز عبور |
|------------|----------|
| `admin` | `admin123` |

> ⚠️ حتماً بعد از اولین ورود رمز را عوض کنید.

## 🗄 جداول دیتابیس

| جدول | نقش |
|------|-----|
| `admins` | حساب‌های ادمین |
| `workers` | اطلاعات کارگران |
| `paths` | مسیرها و نواحی کاری |
| `path_assignments` | انتصاب کارگران به مسیرها |
| `face_encodings` | نمونه‌های چهره و descriptor ها |
| `activity_logs` | فعالیت‌ها و رویدادهای تشخیص‌داده‌شده |
| `work_logs` | جلسات زمان کار (شروع/پایان/مدت) |

## 🔌 نمونهٔ استفاده از API

```bash
# ثبت یک فعالیت
curl -X POST http://localhost/kar/api/activities.php \
  -H "Content-Type: application/json" \
  -d '{"worker_id":1,"activity":"راه رفتن","confidence":88.5,"path_id":2}'

# خواندن آخرین فعالیت‌ها
curl "http://localhost/kar/api/activities.php?limit=10"
```

## 🚧 محدودیت‌ها و برنامه‌های بعدی

- پردازش تشخیص در **مرورگر** انجام می‌شود و کتابخانه‌ها از CDN بارگذاری می‌شوند؛ برای محیط آفلاین باید vendor شوند
- تشخیص چهره در نسخهٔ فعلی ساده است و برای دقت بالاتر نیاز به آموزش/بهینه‌سازی دارد
- برخی endpoint های JSON هنوز فاقد احراز هویت‌اند (در برنامهٔ توسعه)
- ذخیرهٔ تصاویر آپلودی باید محدودیت نوع و حجم بگیرد

## 📄 لایسنس

در حال حاضر لایسنسی تعیین نشده است.
