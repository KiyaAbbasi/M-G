// ابزارهای عمومی برای پروژه مارکت گوگل

// نمایش نوتیفیکیشن
function showNotification(message, type = 'info', duration = 5000) {
    // پیاده‌سازی ساده و قابل استفاده در همه بخش‌ها
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = message;
    document.body.appendChild(notification);
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, duration);
}

// تبدیل اعداد فارسی به انگلیسی
function convertPersianToEnglish(str) {
    if (!str) return '';
    const persianNumbers = [/۰/g, /۱/g, /۲/g, /۳/g, /۴/g, /۵/g, /۶/g, /۷/g, /۸/g, /۹/g];
    for (let i = 0; i < 10; i++) {
        str = str.replace(persianNumbers[i], i);
    }
    return str;
}

// فیلتر و تبدیل اعداد فارسی به انگلیسی (فقط اعداد)
function filterAndConvertNumbers(value) {
    if (!value) return '';
    let result = convertPersianToEnglish(value);
    return result.replace(/[^0-9]/g, '');
}

// اکسپورت برای استفاده در سایر فایل‌ها
window.showNotification = showNotification;
window.convertPersianToEnglish = convertPersianToEnglish;
window.filterAndConvertNumbers = filterAndConvertNumbers; 