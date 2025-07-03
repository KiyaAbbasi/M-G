# افزونه Market Google Location Picker

یک افزونه کامل و پیشرفته برای وردپرس جهت ثبت و مدیریت لوکیشن‌های کسب و کار با قابلیت پرداخت آنلاین و نمایش در نقشه‌های مختلف.

## 🚀 ویژگی‌ها

### ✨ ویژگی‌های اصلی
- **ثبت لوکیشن تعاملی** با استفاده از OpenStreetMap
- **سیستم پرداخت آنلاین** با پشتیبانی از درگاه‌های بانک ملی و زرین‌پال
- **پنل مدیریت کامل** برای مشاهده و مدیریت لوکیشن‌ها
- **آمار و گزارش‌گیری پیشرفته** با نمودارهای تعاملی
- **چندین شورت‌کد مختلف** برای نمایش در صفحات
- **طراحی ریسپانسیو** و سازگار با موبایل
- **پشتیبانی کامل از زبان فارسی**

### 🎯 شورت‌کدهای موجود

#### 1. فرم ثبت لوکیشن
```shortcode
[market_location_form height="500" theme="default" payment_required="true"]
```

**پارامترها:**
- `height`: ارتفاع نقشه (پیش‌فرض: 500)
- `default_lat`: عرض جغرافیایی پیش‌فرض (پیش‌فرض: 35.6892)
- `default_lng`: طول جغرافیایی پیش‌فرض (پیش‌فرض: 51.3890)
- `theme`: پوسته نمایشی (default, modern, minimal)
- `payment_required`: آیا پرداخت اجباری است؟ (true/false)
- `max_locations_per_user`: حداکثر لوکیشن برای هر کاربر (پیش‌فرض: 5)

#### 2. لیست لوکیشن‌ها
```shortcode
[market_location_list limit="10" show_map="true" business_type="restaurant"]
```

**پارامترها:**
- `limit`: تعداد لوکیشن‌های نمایشی (پیش‌فرض: 10)
- `orderby`: مرتب‌سازی بر اساس (created_at, business_name)
- `order`: نحوه مرتب‌سازی (DESC, ASC)
- `show_map`: نمایش نقشه کوچک (true/false)
- `show_details`: نمایش جزئیات (true/false)
- `filter_by_user`: فیلتر بر اساس کاربر جاری (true/false)
- `business_type`: فیلتر بر اساس نوع کسب و کار
- `city`: فیلتر بر اساس شهر
- `province`: فیلتر بر اساس استان

#### 3. نقشه کامل لوکیشن‌ها
```shortcode
[market_location_map height="400" cluster_markers="true" show_filters="true"]
```

**پارامترها:**
- `height`: ارتفاع نقشه (پیش‌فرض: 400)
- `zoom`: زوم پیش‌فرض (پیش‌فرض: 10)
- `center_lat`: مرکز نقشه - عرض جغرافیایی
- `center_lng`: مرکز نقشه - طول جغرافیایی
- `show_search`: نمایش جستجو (true/false)
- `show_filters`: نمایش فیلترها (true/false)
- `cluster_markers`: گروه‌بندی نشانگرها (true/false)

#### 4. جستجوی لوکیشن‌ها
```shortcode
[market_location_search show_filters="true" ajax_search="true"]
```

#### 5. آمار عمومی
```shortcode
[market_location_stats show="total_locations,completed_payments" style="cards"]
```

**پارامترها:**
- `show`: آمارهای قابل نمایش (جدا شده با کاما)
- `style`: نحوه نمایش (cards, list, inline)

## 📋 نصب و راه‌اندازی

### پیش‌نیازها
- وردپرس 5.0 یا بالاتر
- PHP 7.4 یا بالاتر
- MySQL 5.6 یا بالاتر

### مراحل نصب
1. فایل‌های افزونه را در مسیر `/wp-content/plugins/market-google-location/` کپی کنید
2. از طریق پنل مدیریت وردپرس افزونه را فعال کنید
3. به منوی "لوکیشن‌های کسب و کار" بروید
4. تنظیمات را کامل کنید

### تنظیمات اولیه
1. **کلید API گوگل مپ:** (اختیاری - برای Geocoding)
2. **تنظیمات پرداخت:**
   - اطلاعات درگاه بانک ملی
   - اطلاعات درگاه زرین‌پال
   - مبلغ پرداخت پیش‌فرض
3. **تنظیمات نقشه:**
   - مختصات پیش‌فرض
   - زوم پیش‌فرض

## 🗃️ ساختار دیتابیس

افزونه جدول `wp_market_google_locations` را ایجاد می‌کند:

```sql
CREATE TABLE wp_market_google_locations (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    user_id bigint(20) NOT NULL,
    latitude decimal(10,8) NOT NULL,
    longitude decimal(11,8) NOT NULL,
    business_name varchar(255) NOT NULL,
    business_type varchar(100) DEFAULT '',
    business_phone varchar(20) DEFAULT '',
    business_hours varchar(100) DEFAULT '',
    province varchar(50) DEFAULT '',
    city varchar(50) DEFAULT '',
    address text DEFAULT '',
    website varchar(255) DEFAULT '',
    selected_maps text DEFAULT '',
    payment_method varchar(20) DEFAULT 'bmi',
    payment_status varchar(20) DEFAULT 'pending',
    payment_amount decimal(10,0) DEFAULT 0,
    payment_transaction_id varchar(100) DEFAULT '',
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);
```

## 🔧 تنظیمات و سفارشی‌سازی

### هوک‌ها و فیلترها

#### فیلترها
```php
// تغییر مبلغ پرداخت
add_filter('market_google_payment_amount', function($amount, $location_data) {
    return 15000; // 15,000 تومان
}, 10, 2);

// تغییر پیام موفقیت
add_filter('market_google_success_message', function($message, $location_id) {
    return 'پرداخت شما با موفقیت انجام شد!';
}, 10, 2);

// اضافه کردن فیلدهای سفارشی
add_filter('market_google_custom_fields', function($fields) {
    $fields['instagram'] = array(
        'label' => 'اینستاگرام',
        'type' => 'url',
        'required' => false
    );
    return $fields;
});
```

#### اکشن‌ها
```php
// بعد از ثبت موفق لوکیشن
add_action('market_google_location_saved', function($location_id, $location_data) {
    // ارسال ایمیل اطلاع‌رسانی
    wp_mail('admin@site.com', 'لوکیشن جدید ثبت شد', '...');
}, 10, 2);

// بعد از پرداخت موفق
add_action('market_google_payment_completed', function($location_id, $transaction_id) {
    // فعال‌سازی خودکار لوکیشن
    update_location_status($location_id, 'active');
}, 10, 2);
```

### سفارشی‌سازی CSS
```css
/* تغییر رنگ‌های افزونه */
:root {
    --market-primary-color: #2563eb;
    --market-secondary-color: #64748b;
    --market-success-color: #10b981;
    --market-error-color: #ef4444;
}

/* سفارشی‌سازی فرم */
.market-location-form {
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

لیست شورت کد‌ها
[market_location_form] - فرم ثبت مکان
[market_location_list] - لیست مکان‌ها
[market_location_map] - نمایش نقشه
[market_location_search] - جستجوی مکان
[market_location_stats] - آمار
[market_payment_result] - نتیجه پرداخت

## 📊 پنل مدیریت

### صفحات موجود
1. **مدیریت لوکیشن‌ها:** نمایش، ویرایش و حذف لوکیشن‌ها
2. **تنظیمات:** تنظیمات کلی افزونه
3. **آمار و گزارش‌ها:** نمودارها و آمار تفصیلی

### ویژگی‌های پنل آمار
- کارت‌های آمار کلی
- نمودار ثبت‌نام‌ها در 30 روز گذشته
- جدول برترین شهرها
- جدول برترین انواع کسب و کار
- فیلترهای پیشرفته
- ویجت آمار در داشبورد وردپرس

## 🔌 ادغام با سایر افزونه‌ها

### Gravity Forms
افزونه از ادغام با Gravity Forms پشتیبانی می‌کند:

```php
// اضافه کردن فیلد نقشه به فرم
add_action('gform_field_standard_settings', function($position, $form_id) {
    if ($position == 50) {
        echo '<li class="map_location_setting field_setting">';
        echo '<label>فیلد انتخاب لوکیشن</label>';
        echo '</li>';
    }
}, 10, 2);
```

### WooCommerce
```php
// اضافه کردن لوکیشن به محصولات
add_action('woocommerce_product_options_general_product_data', function() {
    echo '<div class="product-location-field">';
    echo '[market_location_form height="300" theme="minimal"]';
    echo '</div>';
});
```

## 🌐 چندزبانه

افزونه از سیستم ترجمه وردپرس پشتیبانی می‌کند. فایل‌های ترجمه در مسیر `/languages/` قرار دارند.

### اضافه کردن زبان جدید
1. فایل POT را از مسیر `/languages/` دانلود کنید
2. با استفاده از Poedit ترجمه کنید
3. فایل‌های `.po` و `.mo` را در همان مسیر قرار دهید

## 🔒 امنیت

### تأیید صحت درخواست‌ها
```php
// تمام فرم‌ها از nonce استفاده می‌کنند
wp_verify_nonce($_POST['nonce'], 'market_google_nonce');

// پاک‌سازی ورودی‌ها
$business_name = sanitize_text_field($_POST['business_name']);
$latitude = floatval($_POST['latitude']);
```

### سطح دسترسی
- **مدیریت لوکیشن‌ها:** `manage_options`
- **مشاهده آمار:** `manage_options`
- **ثبت لوکیشن:** `read` (کاربران عادی)

## 🐛 رفع مشکلات

### مشکلات متداول

#### نقشه نمایش داده نمی‌شود
- بررسی کنید که اتصال اینترنت برقرار باشد
- CDN Leaflet در دسترس باشد
- خطاهای کنسول مرورگر را بررسی کنید

#### پرداخت کار نمی‌کند
- اطلاعات درگاه پرداخت را بررسی کنید
- SSL سایت فعال باشد
- URL بازگشت درست تنظیم شده باشد

#### آمار نمایش داده نمی‌شود
- Chart.js بارگذاری شده باشد
- جدول دیتابیس موجود باشد
- سطح دسترسی کاربر کافی باشد

### فعال‌سازی حالت دیباگ
```php
// در wp-config.php
define('MARKET_GOOGLE_DEBUG', true);

// در functions.php
add_action('wp_footer', function() {
    if (defined('MARKET_GOOGLE_DEBUG') && MARKET_GOOGLE_DEBUG) {
        echo '<div id="market-debug-info"></div>';
    }
});
```

## 📝 تغییرات نسخه‌ها

### نسخه 1.0.0
- انتشار اولیه افزونه
- ثبت لوکیشن با OpenStreetMap
- سیستم پرداخت بانک ملی و زرین‌پال
- پنل مدیریت کامل

### نسخه 1.1.0 (در دست توسعه)
- اضافه شدن کلاس شورت‌کدها
- سیستم آمار و گزارش‌گیری
- ویجت داشبورد
- بهبود رابط کاربری

### نسخه 1.2.0
- اضافه شدن فرم چند مرحله‌ای جدید
- سیستم استان/شهر ایران
- ساعات کاری هوشمند
- درگاه بانک ملی
- داشبورد آنالیتیکس
- ردیابی مراحل فرم
- کپی مختصات
- فیلتر و جستجوی پیشرفته

## 🤝 مشارکت

برای مشارکت در توسعه این افزونه:

1. Repository را Fork کنید
2. برنچ جدید ایجاد کنید (`git checkout -b feature/AmazingFeature`)
3. تغییرات را Commit کنید (`git commit -m 'Add some AmazingFeature'`)
4. برنچ را Push کنید (`git push origin feature/AmazingFeature`)
5. Pull Request ایجاد کنید

## 📞 پشتیبانی

- **وب‌سایت:** [https://marketgoogle.com](https://marketgoogle.com)
- **ایمیل:** support@marketgoogle.com
- **مستندات:** [https://docs.marketgoogle.com](https://docs.marketgoogle.com)

## 📄 مجوز

این افزونه تحت مجوز GPL-2.0+ منتشر شده است.

---

# Market Google Location Picker Plugin

A comprehensive WordPress plugin for business location registration and management with online payment and multi-map display capabilities.

## 🚀 Features

### ✨ Core Features
- **Interactive Location Registration** using OpenStreetMap
- **Online Payment System** supporting BMI and ZarinPal gateways
- **Complete Admin Panel** for viewing and managing locations
- **Advanced Analytics & Reporting** with interactive charts
- **Multiple Shortcodes** for different display purposes
- **Responsive Design** and mobile-friendly
- **Full Persian Language Support**

### 🎯 Available Shortcodes

#### 1. Location Registration Form
```shortcode
[market_location_form height="500" theme="default" payment_required="true"]
```

#### 2. Locations List
```shortcode
[market_location_list limit="10" show_map="true" business_type="restaurant"]
```

#### 3. Full Locations Map
```shortcode
[market_location_map height="400" cluster_markers="true" show_filters="true"]
```

#### 4. Location Search
```shortcode
[market_location_search show_filters="true" ajax_search="true"]
```

#### 5. Public Statistics
```shortcode
[market_location_stats show="total_locations,completed_payments" style="cards"]
```

## 📋 Installation

### Requirements
- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

### Installation Steps
1. Copy plugin files to `/wp-content/plugins/market-google-location/`
2. Activate the plugin through WordPress admin panel
3. Go to "Business Locations" menu
4. Complete the settings

## 🔧 Customization

### Hooks and Filters

```php
// Change payment amount
add_filter('market_google_payment_amount', function($amount, $location_data) {
    return 15000; // 15,000 Toman
}, 10, 2);

// After successful location save
add_action('market_google_location_saved', function($location_id, $location_data) {
    // Send notification email
    wp_mail('admin@site.com', 'New location registered', '...');
}, 10, 2);
```

## 📞 Support

- **Website:** [https://marketgoogle.com](https://marketgoogle.com)
- **Email:** support@marketgoogle.com
- **Documentation:** [https://docs.marketgoogle.com](https://docs.marketgoogle.com)

## 📄 License

This plugin is released under the GPL-2.0+ license. 