/**
 * مدیریت تنظیمات محصولات برای ادمین
 * نسخه: 1.3.0
 */

// تنظیمات محصولات قابل ویرایش برای ادمین
window.MarketLocationProductSettings = {
    // پکیج‌ها (انتخاب یکی از آنها، بقیه را دیزیبل می‌کند)
    packages: [
        {
            id: 'all-maps',
            name: 'تمامی نقشه‌های آنلاین',
            original_price: 1234000,
            sale_price: 1109000,
            icon: '🗺️',
            type: 'package',
            special: true,
            description: 'شامل تمامی نقشه‌ها + کارت ویزیت',
            features: [
                'ثبت در گوگل مپ',
                'ثبت در نشان',
                'ثبت در بلد', 
                'ثبت در OpenStreetMap',
                'کارت ویزیت آنلاین',
                'پشتیبانی ۱ ساله'
            ],
            is_active: true,
            sort_order: 1
        },
        {
            id: 'vcard',
            name: 'کارت ویزیت آنلاین',
            original_price: 1234000,
            sale_price: 1109000,
            icon: '📇',
            type: 'package',
            special: false,
            description: 'کارت ویزیت دیجیتال حرفه‌ای',
            features: [
                'کارت ویزیت آنلاین',
                'QR Code اختصاصی',
                'لینک اشتراک‌گذاری',
                'آمار بازدید',
                'قابلیت ویرایش'
            ],
            is_active: true,
            sort_order: 2
        }
    ],
    
    // محصولات ویژه (می‌توان با هم ترکیب کرد)
    special_products: [
        {
            id: 'google-maps',
            name: 'نقشه گوگل‌مپ',
            original_price: 510000,
            sale_price: 459000,
            icon: 'G',
            type: 'special',
            description: 'ثبت در گوگل مپ',
            features: [
                'ثبت در Google Maps',
                'تأیید کسب‌وکار',
                'نمایش در جستجوی گوگل',
                'قابلیت دریافت نظرات'
            ],
            is_active: true,
            sort_order: 1
        },
        {
            id: 'neshan',
            name: 'نقشه نشان',
            original_price: 294000,
            sale_price: 264000,
            icon: 'ن',
            type: 'special',
            description: 'ثبت در نشان',
            features: [
                'ثبت در نقشه نشان',
                'نمایش اطلاعات کسب‌وکار',
                'قابلیت دریافت نظرات',
                'آمار بازدید'
            ],
            is_active: true,
            sort_order: 2
        },
        {
            id: 'balad',
            name: 'نقشه بلد',
            original_price: 283000,
            sale_price: 254000,
            icon: 'ب',
            type: 'special',
            description: 'ثبت در بلد',
            features: [
                'ثبت در نقشه بلد',
                'نمایش اطلاعات کامل',
                'قابلیت آپلود تصاویر',
                'دریافت نظرات کاربران'
            ],
            is_active: true,
            sort_order: 3
        },
        {
            id: 'openstreet',
            name: 'اپن‌استریت',
            original_price: 326000,
            sale_price: 293000,
            icon: 'O',
            type: 'special',
            description: 'ثبت در OpenStreetMap',
            features: [
                'ثبت در OpenStreetMap',
                'نقشه متن‌باز',
                'دسترسی جهانی',
                'قابلیت ویرایش اطلاعات'
            ],
            is_active: true,
            sort_order: 4
        }
    ],
    
    // تنظیمات عمومی
    settings: {
        tax_rate: 0.1, // 10% مالیات
        currency: 'تومان',
        enable_discount_display: true,
        allow_package_combination: false, // آیا پکیج‌ها قابل ترکیب باشند؟
        default_selection: 'all-maps' // انتخاب پیش‌فرض
    },
    
    // متدهای مدیریت
    methods: {
        /**
         * اضافه کردن محصول جدید
         */
        addProduct: function(productData, type = 'special') {
            const targetArray = type === 'package' ? 
                this.packages : this.special_products;
            
            // تولید ID یکتا
            if (!productData.id) {
                productData.id = 'product_' + Date.now();
            }
            
            // تنظیم مقادیر پیش‌فرض
            const defaultProduct = {
                is_active: true,
                sort_order: targetArray.length + 1,
                features: [],
                type: type
            };
            
            const newProduct = Object.assign(defaultProduct, productData);
            targetArray.push(newProduct);
            
            return newProduct;
        },
        
        /**
         * ویرایش محصول
         */
        updateProduct: function(productId, updateData) {
            const allProducts = [...this.packages, ...this.special_products];
            const productIndex = allProducts.findIndex(p => p.id === productId);
            
            if (productIndex !== -1) {
                Object.assign(allProducts[productIndex], updateData);
                return allProducts[productIndex];
            }
            
            return null;
        },
        
        /**
         * حذف محصول
         */
        deleteProduct: function(productId) {
            this.packages = this.packages.filter(p => p.id !== productId);
            this.special_products = this.special_products.filter(p => p.id !== productId);
        },
        
        /**
         * دریافت تمامی محصولات فعال
         */
        getActiveProducts: function() {
            const activePackages = this.packages.filter(p => p.is_active);
            const activeSpecials = this.special_products.filter(p => p.is_active);
            
            return {
                packages: activePackages.sort((a, b) => a.sort_order - b.sort_order),
                special_products: activeSpecials.sort((a, b) => a.sort_order - b.sort_order)
            };
        },
        
        /**
         * محاسبه درصد تخفیف
         */
        calculateDiscount: function(originalPrice, salePrice) {
            if (originalPrice <= salePrice) return 0;
            return Math.round(((originalPrice - salePrice) / originalPrice) * 100);
        },
        
        /**
         * ذخیره تنظیمات (برای ارسال به سرور)
         */
        saveSettings: function() {
            const settingsData = {
                packages: this.packages,
                special_products: this.special_products,
                settings: this.settings
            };
            
            // ذخیره محلی
            localStorage.setItem('market_location_product_settings', JSON.stringify(settingsData));
            
            // ارسال به سرور (اگر در دسترس باشد)
            if (typeof wp !== 'undefined' && wp.ajax) {
                wp.ajax.post('save_product_settings', {
                    nonce: window.marketLocationAdmin?.nonce,
                    settings_data: settingsData
                }).done(function(response) {
                    console.log('تنظیمات محصولات ذخیره شد:', response);
                }).fail(function(error) {
                    console.error('خطا در ذخیره تنظیمات:', error);
                });
            }
            
            return settingsData;
        },
        
        /**
         * بارگذاری تنظیمات
         */
        loadSettings: function() {
            const savedSettings = localStorage.getItem('market_location_product_settings');
            if (savedSettings) {
                try {
                    const data = JSON.parse(savedSettings);
                    if (data.packages) this.packages = data.packages;
                    if (data.special_products) this.special_products = data.special_products;
                    if (data.settings) this.settings = Object.assign(this.settings, data.settings);
                } catch (e) {
                    console.error('خطا در بارگذاری تنظیمات محصولات:', e);
                }
            }
        }
    }
};

// ایجاد کپی از متدها برای دسترسی آسان‌تر
Object.assign(window.MarketLocationProductSettings, window.MarketLocationProductSettings.methods);

// بارگذاری تنظیمات در شروع
window.MarketLocationProductSettings.loadSettings();

// نمونه استفاده:
// console.log('محصولات فعال:', window.MarketLocationProductSettings.getActiveProducts()); 