/**
 * اسکریپت عمومی افزونه Market Google Location
 * نسخه: 1.3.0 - با پیگیری پیشرفت و مدیریت داینامیک محصولات
 */
(function($) {
    'use strict';
    
    // متغیرهای سراسری
    let currentStep = 1;
    let totalSteps = 4;
    let formData = {};
    let map = null;
    let marker = null;
    let sessionId = generateSessionId();
    let autoSaveTimer = null;
    let selectedPackages = [];
    let progressTracking = true; // فعال‌سازی پیگیری پیشرفت

    // تنظیمات محصولات داینامیک - بدون مقادیر پیش‌فرض، بارگذاری از دیتابیس
    let productSettings = {
        packages: [],
        special_products: []
    };

    // بررسی VPN و اتصال
    let vpnDetected = false;

    // داده‌های استان و شهر ایران
    const iranProvinces = {
        'آذربایجان شرقی': ['تبریز', 'مراغه', 'میانه', 'شبستر', 'مرند', 'اهر', 'بناب', 'سراب', 'کلیبر', 'هریس', 'ورزقان', 'چاراویماق', 'هشترود', 'آذرشهر', 'جلفا'],
        'آذربایجان غربی': ['ارومیه', 'خوی', 'مهاباد', 'سلماس', 'بوکان', 'میاندوآب', 'نقده', 'پیرانشهر', 'سردشت', 'تکاب', 'چالدران', 'شاهین‌دژ', 'قره‌ضیاءالدین', 'ماکو'],
        'اردبیل': ['اردبیل', 'پارس‌آباد', 'خلخال', 'مشگین‌شهر', 'گرمی', 'بیله‌سوار', 'کوثر', 'نمین', 'نیر', 'سرعین'],
        'اصفهان': ['اصفهان', 'کاشان', 'نجف‌آباد', 'خمینی‌شهر', 'شاهین‌شهر', 'فولادشهر', 'لنجان', 'مبارکه', 'نطنز', 'اردستان', 'گلپایگان', 'خوانسار', 'تیران و کرون', 'فریدن', 'چادگان', 'بویین و میاندشت', 'دهاقان', 'فریدونشهر', 'فلاورجان', 'شهرضا', 'دولت‌آباد', 'سمیرم'],
        'البرز': ['کرج', 'نظرآباد', 'طالقان', 'ساوجبلاغ', 'هشتگرد', 'فردیس', 'اشتهارد', 'چهارباغ', 'کوهسار', 'محمدشهر', 'مهرشهر'],
        'ایلام': ['ایلام', 'دهلران', 'آبدانان', 'مهران', 'دره‌شهر', 'چوار', 'ایوان', 'بدره', 'ملکشاهی', 'لومار'],
        'بوشهر': ['بوشهر', 'برازجان', 'خرمشهر', 'کنگان', 'گناوه', 'دیلم', 'جم', 'عسلویه', 'تنگستان', 'دیر'],
        'تهران': ['تهران', 'ورامین', 'اسلامشهر', 'رباط کریم', 'شهریار', 'قدس', 'ملارد', 'فیروزکوه', 'دماوند', 'پردیس', 'بهارستان', 'چهاردانگه', 'شمیرانات', 'پاکدشت', 'قرچک', 'ری'],
        'چهارمحال و بختیاری': ['شهرکرد', 'بروجن', 'فارسان', 'لردگان', 'اردل', 'سامان', 'کوهرنگ', 'کیان', 'بازفت', 'گندمان'],
        'خراسان جنوبی': ['بیرجند', 'قائن', 'فردوس', 'طبس', 'نهبندان', 'درمیان', 'سرایان', 'سربیشه', 'خوسف', 'بشرویه'],
        'خراسان رضوی': ['مشهد', 'نیشابور', 'سبزوار', 'تربت حیدریه', 'قوچان', 'کاشمر', 'گناباد', 'تربت جام', 'خواف', 'طوس', 'بردسکن', 'چناران', 'درگز', 'کلات', 'مه ولات', 'رشتخوار', 'سرخس', 'فریمان', 'فیض‌آباد'],
        'خراسان شمالی': ['بجنورد', 'اسفراین', 'شیروان', 'فاروج', 'آشخانه', 'گرمه', 'مانه و سملقان', 'جاجرم', 'راز و جرگلان'],
        'خوزستان': ['اهواز', 'آبادان', 'خرمشهر', 'دزفول', 'مسجدسلیمان', 'شوشتر', 'بهبهان', 'ماهشهر', 'ایذه', 'رامهرمز', 'شوش', 'اندیمشک', 'لالی', 'گتوند', 'کارون', 'حمیدیه', 'هندیجان', 'باوی', 'هویزه'],
        'زنجان': ['زنجان', 'ابهر', 'خدابنده', 'طارم', 'ماهنشان', 'سلطانیه', 'ایجرود', 'خرمدره'],
        'سمنان': ['سمنان', 'شاهرود', 'دامغان', 'گرمسار', 'بسطام', 'مهدی‌شهر', 'میامی', 'سرخه', 'آرادان'],
        'سیستان و بلوچستان': ['زاهدان', 'زابل', 'چابهار', 'ایرانشهر', 'خاش', 'سراوان', 'نیک‌شهر', 'کنارک', 'میرجاوه', 'دلگان', 'قصرقند', 'راسک', 'سرباز', 'هامون', 'فنوج', 'نصرت‌آباد', 'زهک'],
        'فارس': ['شیراز', 'مرودشت', 'کازرون', 'فسا', 'داراب', 'جهرم', 'لار', 'آباده', 'فیروزآباد', 'لامرد', 'اقلید', 'نی‌ریز', 'استهبان', 'قیر و کارزین', 'مهر', 'خرم‌بید', 'گراش', 'ممسنی', 'سپیدان', 'نورآباد', 'پاسارگاد', 'رستم', 'خنج', 'بوانات', 'زرین‌دشت', 'فراشبند', 'کوه‌چنار', 'بیضا'],
        'قزوین': ['قزوین', 'البرز', 'تاکستان', 'آوج', 'بوئین‌زهرا', 'آبیک', 'محمودآباد نمونه'],
        'قم': ['قم'],
        'کردستان': ['سنندج', 'مریوان', 'بانه', 'سقز', 'قروه', 'بیجار', 'کامیاران', 'دیواندره', 'دهگلان', 'سروآباد'],
        'کرمان': ['کرمان', 'رفسنجان', 'جیرفت', 'بم', 'سیرجان', 'شهربابک', 'زرند', 'کهنوج', 'بردسیر', 'راور', 'انار', 'بافت', 'رودبار جنوب', 'عنبرآباد', 'قلعه گنج', 'نرماشیر', 'فهرج', 'منوجان', 'ریگان', 'رابر'],
        'کرمانشاه': ['کرمانشاه', 'اسلام‌آباد غرب', 'پاوه', 'جوانرود', 'سنقر', 'کنگاور', 'صحنه', 'هرسین', 'گیلان غرب', 'روانسر', 'ثلاث باباجانی', 'دالاهو', 'سومار', 'قصر شیرین'],
        'کهگیلویه و بویراحمد': ['یاسوج', 'گچساران', 'دوگنبدان', 'دهدشت', 'لیکک', 'چیتاب', 'باشت', 'مارگون', 'لنده'],
        'گلستان': ['گرگان', 'گنبد کاووس', 'آق‌قلا', 'علی‌آباد کتول', 'مینودشت', 'کردکوی', 'بندر ترکمن', 'کلاله', 'آزادشهر', 'رامیان', 'مراوه‌تپه', 'بندرگز', 'گالیکش', 'گمیش‌تپه'],
        'گیلان': ['رشت', 'انزلی', 'لاهیجان', 'آستارا', 'رودسر', 'فومن', 'صومعه‌سرا', 'طالش', 'لنگرود', 'آستانه اشرفیه', 'ماسال', 'شفت', 'رودبار', 'املش', 'بندر انزلی', 'سیاهکل'],
        'لرستان': ['خرم‌آباد', 'بروجرد', 'دورود', 'کوهدشت', 'الیگودرز', 'نورآباد', 'پل‌دختر', 'ازنا', 'اشترینان', 'چگنی', 'دلفان', 'دوره', 'رومشکان', 'سلسله'],
        'مازندران': ['ساری', 'بابل', 'آمل', 'قائم‌شهر', 'بابلسر', 'گرگان', 'نوشهر', 'چالوس', 'تنکابن', 'رامسر', 'نکا', 'جویبار', 'نور', 'فریدونکنار', 'کلاردشت', 'محمودآباد', 'سوادکوه', 'کلارآباد', 'عباس‌آباد', 'کیاسر', 'سلمان‌شهر'],
        'مرکزی': ['اراک', 'ساوه', 'خمین', 'محلات', 'دلیجان', 'تفرش', 'آشتیان', 'کمیجان', 'شازند', 'فراهان', 'زرندیه'],
        'هرمزگان': ['بندرعباس', 'بندر لنگه', 'میناب', 'قشم', 'کیش', 'پارسیان', 'جاسک', 'رودان', 'خمیر', 'بستک', 'حاجی‌آباد', 'ابوموسی', 'بندر خمیر', 'سیریک'],
        'همدان': ['همدان', 'ملایر', 'نهاوند', 'تویسرکان', 'کبودرآهنگ', 'اسدآباد', 'بهار', 'رزن', 'فامنین'],
        'یزد': ['یزد', 'اردکان', 'میبد', 'ابرکوه', 'بافق', 'مهریز', 'تفت', 'اشکذر', 'خاتم', 'بهاباد', 'زارچ', 'شاهدیه', 'حمیدیا']
    };

    // راه‌اندازی اولیه
    $(document).ready(function() {
        // جلوگیری از تداخل event handlers
        $(document).off('click.marketGoogle');
        $(document).off('submit.marketGoogle');
        
        // بررسی اگر کاربر از درگاه پرداخت برگشته
        const urlParams = new URLSearchParams(window.location.search);
        const paymentResult = urlParams.get('payment');
        const paymentError = urlParams.get('error');
        
        // اگر کاربر از درگاه برگشته (موفق یا ناموفق)، localStorage را پاک کن
        if (paymentResult || paymentError) {
            localStorage.removeItem('market_location_form_data');
            formData = {};
            selectedPackages = [];
            sessionId = generateSessionId();
            console.log('🔄 Payment return detected - localStorage cleared and new session created');
            
            // پاک کردن URL parameters
            if (window.history.replaceState) {
                const cleanUrl = window.location.pathname;
                window.history.replaceState({}, document.title, cleanUrl);
            }
        }
        
        // پاک کردن localStorage در شروع جلسه جدید
        if (window.location.pathname === '/path-to-form/') {
            localStorage.removeItem('market_location_form_data');
            console.log('✅ localStorage cleared at form start');
        }
        
        initializeForm();
        setupEventListeners();
        loadFormFromStorage();
        startAutoSave();
        createNotificationContainer();
        setup24HourFormat();
        
        // بارگذاری محصولات از API داینامیک
        loadDynamicProducts();
    });


    
    /**
     * نمایش هشدار VPN
     */
    function showVPNWarning() {
        const warningHTML = `
            <div class="vpn-warning-modal" style="
                position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
                background: rgba(0,0,0,0.8); z-index: 10000; display: flex; 
                align-items: center; justify-content: center; font-family: Tahoma;">
                <div style="
                    background: white; padding: 30px; border-radius: 15px; 
                    max-width: 500px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
                    <div style="font-size: 48px; margin-bottom: 20px;">🚫</div>
                    <h2 style="color: #e74c3c; margin: 0 0 15px;">هشدار VPN</h2>
                    <p style="margin: 15px 0; line-height: 1.6; color: #666;">
                        به نظر می‌رسد از VPN استفاده می‌کنید. برای دسترسی به درگاه‌های پرداخت ایرانی:
                    </p>
                    <ul style="text-align: right; margin: 20px 0; color: #666;">
                        <li>VPN خود را خاموش کنید</li>
                        <li>فیلترشکن را غیرفعال کنید</li>
                        <li>از اتصال مستقیم اینترنت استفاده کنید</li>
                    </ul>
                    <div style="margin-top: 25px;">
                        <button onclick="$('.vpn-warning-modal').remove()" style="
                            background: #3498db; color: white; border: none; 
                            padding: 12px 25px; border-radius: 8px; cursor: pointer;
                            font-size: 16px; margin: 0 10px;">متوجه شدم</button>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(warningHTML);
    }
    
    /**
     * نمایش هشدار مشکل شبکه
     */
    function showNetworkWarning() {
        showNotification('⚠️ مشکل در اتصال به اینترنت تشخیص داده شد. لطفاً اتصال خود را بررسی کنید.', 'warning', 8000);
    }
    
    /**
     * نمایش خطای اتصال WordPress
     */
    function showWordPressConnectionError() {
        showNotification('❌ خطا در اتصال به سرور. لطفاً صفحه را رفرش کنید.', 'error', 10000);
    }

    /**
     * راه‌اندازی اولیه فرم
     */
    function initializeForm() {
        console.log('🚀 Form initialization started');
        
        // بررسی اگر در صفحه فرم هستیم
        if (window.location.pathname.includes('/path-to-form/')) {
            // پاک کردن localStorage در شروع session جدید
            localStorage.removeItem('market_location_form_data');
            formData = {};
            selectedPackages = [];
            sessionId = generateSessionId();
            console.log('✅ New form session - localStorage cleared');
        } else {
            // بارگذاری داده‌های ذخیره شده فقط اگر در همان session هستیم
            const savedSessionId = localStorage.getItem('market_location_session_id');
            if (savedSessionId === sessionId) {
                loadFormFromStorage();
            } else {
                // Session جدید - پاک کردن داده‌های قدیمی
                localStorage.removeItem('market_location_form_data');
                formData = {};
                selectedPackages = [];
            }
        }
        
        // ذخیره session ID جدید
        localStorage.setItem('market_location_session_id', sessionId);
        
        // نمایش مرحله اول
        showStep(1);
        
        // راه‌اندازی dropdown استان/شهر
        initializeLocationDropdowns();
        
        // راه‌اندازی ساعات کاری
        initializeWorkingHours();
        
        // شروع پیگیری پیشرفت
        if (progressTracking) {
            startProgressTracking();
        }
    }

    /**
     * تنظیم event listener ها
     */
    function setupEventListeners() {
        // دکمه‌های بعدی و قبلی با namespace
        $(document).on('click.marketGoogle', '.btn-next', nextStep);
        $(document).on('click.marketGoogle', '.btn-prev', prevStep);
        
        // پیگیری تغییرات فیلدها برای ذخیره خودکار
        $(document).on('input.marketGoogle change.marketGoogle', '.form-input, .form-select', function() {
            clearTimeout(autoSaveTimer);
            autoSaveTimer = setTimeout(saveFormData, 1000);
            
            // پیگیری پیشرفت در مرحله اول
            if (currentStep === 1) {
                trackUserProgress();
            }
        });
        
        // submit فرم با namespace
        $(document).on('submit.marketGoogle', '#market-location-form', handleFormSubmit);
        
        // انتخاب محصول داینامیک با namespace
        $(document).on('click.marketGoogle', '.package-item', selectDynamicPackage);
        
        // ساعات کاری
        // حذف شده: event listener برای دکمه‌های preset ساعت کاری
        
        // اضافه کردن event listener برای محدود کردن ورودی و validation
        let lastNumericWarningTime = 0;
        
        $(document).on('input.marketGoogle', '#full_name', function() {
            const $field = $(this);
            let value = $field.val();
            
            // بررسی وجود عدد (انگلیسی و فارسی)
            if (/[\d۰-۹]/.test(value)) {
                // حذف اعداد از مقدار (انگلیسی و فارسی)
                const filteredValue = value.replace(/[\d۰-۹]/g, '');
                $field.val(filteredValue);
                
                // نمایش اخطار فقط یک بار در هر 3 ثانیه
                const currentTime = Date.now();
                if (currentTime - lastNumericWarningTime > 3000) {
                    showNotification('در فیلد نام و نام خانوادگی نمی‌توانید عدد وارد کنید.', 'error', 5000);
                    lastNumericWarningTime = currentTime;
                }
                
                $field.addClass('error').removeClass('success');
                return;
            }
            
            // فیلتر کردن کاراکترهای غیرمجاز (فقط حروف فارسی، انگلیسی و فاصله)
            const filteredValue = value.replace(/[^\u0600-\u06FF\u0041-\u005A\u0061-\u007A\s]/g, '');
            if (value !== filteredValue) {
                $field.val(filteredValue);
            }
            
            // validation و تغییر رنگ
            validateFieldAndUpdateColor($field, /^[\u0600-\u06FF\u0041-\u005A\u0061-\u007A\s]+$/);
        });
        
        $(document).on('input.marketGoogle', '#phone', function() {
            const $field = $(this);
            const value = $field.val();
            
            // تبدیل اعداد فارسی به انگلیسی و فیلتر
            const filteredValue = filterAndConvertNumbers(value);
            if (value !== filteredValue) {
                $field.val(filteredValue);
            }
            
            // validation و تغییر رنگ
            validateFieldAndUpdateColor($field, /^09\d{9}$/);
        });
        
        $(document).on('input.marketGoogle', '#business_phone', function() {
            const $field = $(this);
            const value = $field.val();
            
            // تبدیل اعداد فارسی به انگلیسی و فیلتر
            const filteredValue = filterAndConvertNumbers(value);
            if (value !== filteredValue) {
                $field.val(filteredValue);
            }
            
            // validation انعطاف‌پذیر - حداقل 3 رقم
            validateFieldAndUpdateColor($field, /^\d{3,}$/);
        });
        
        $(document).on('input.marketGoogle', '#business_name', function() {
            const $field = $(this);
            validateFieldAndUpdateColor($field, /.{2,}/);
        });
        
        // اضافه کردن validation برای فیلد وب‌سایت
        $(document).on('input.marketGoogle', '#website', function() {
            const $field = $(this);
            const value = $field.val().trim();
            
            if (!value) {
                $field.removeClass('error success');
            } else {
                $field.removeClass('error').addClass('success');
            }
        });
        
        // اضافه کردن validation برای فیلد آدرس دقیق
        $(document).on('input.marketGoogle', '#exact_address', function() {
            const $field = $(this);
            const value = $field.val().trim();
            
            if (!value) {
                $field.removeClass('error success');
            } else if (value.length >= 10) { // حداقل ۱۰ کاراکتر برای آدرس دقیق
                $field.removeClass('error').addClass('success');
            } else {
                $field.removeClass('success').addClass('error');
            }
            
            // ذخیره در formData
            formData.exact_address = value;
            saveFormData();
        });
        
        $(document).on('change.marketGoogle', '#province', function() {
            const $field = $(this);
            const selectedProvince = $field.val();
            
            if (selectedProvince && selectedProvince !== 'انتخاب کنید' && selectedProvince !== '') {
                $field.removeClass('error').addClass('success');
                // به‌روزرسانی شهرها
                updateCityOptions(selectedProvince);
                // ریست کردن شهر
                $('#city').val('').removeClass('error success');
            } else {
                $field.removeClass('success').addClass('error');
                $('#city').empty().append('<option value="">انتخاب کنید</option>').removeClass('error success');
            }
            
            // ذخیره در formData
            formData.province = selectedProvince;
            saveFormData();
        });
        
        $(document).on('change.marketGoogle', '#city', function() {
            const $field = $(this);
            const selectedCity = $field.val();
            
            if (selectedCity && selectedCity !== 'انتخاب کنید' && selectedCity !== '') {
                $field.removeClass('error').addClass('success');
            } else {
                $field.removeClass('success');
                // فقط اگر استان انتخاب شده باشد، شهر را قرمز کن
                const province = $('#province').val();
                if (province && province !== 'انتخاب کنید' && province !== '') {
                    $field.addClass('error');
                }
            }
            
            // ذخیره در formData
            formData.city = selectedCity;
            saveFormData();
        });
    }

    /**
     * اعتبارسنجی فیلد و تغییر رنگ
     */
    function validateFieldAndUpdateColor($field, regex) {
        const value = $field.val().trim();
        
        if (!value) {
            // فیلد خالی - حالت عادی
            $field.removeClass('error success');
        } else if (regex.test(value)) {
            // فیلد صحیح - سبز
            $field.removeClass('error').addClass('success');
        } else {
            // فیلد نادرست - قرمز
            $field.removeClass('success').addClass('error');
        }
    }

    /**
     * شروع پیگیری پیشرفت کاربر
     */
    function startProgressTracking() {
        // پیگیری ورود کاربر
        trackUserProgress('page_enter');
        
        // پیگیری خروج کاربر
        $(window).on('beforeunload', function() {
            trackUserProgress('page_exit');
        });
    }

    /**
     * پیگیری پیشرفت کاربر
     */
    function trackUserProgress(action = 'field_change') {
        const fullName = $('#full_name').val();
        const phone = $('#phone').val();
        
        // فقط اگر نام یا شماره پر شده باشد
        if (fullName || phone) {
            const progressData = {
                session_id: sessionId,
                step: currentStep,
                action: action,
                full_name: fullName,
                phone: phone,
                completed_fields: getCurrentStepCompletedFields(),
                form_data: formData,
                timestamp: new Date().toISOString(),
                user_agent: navigator.userAgent,
                page_url: window.location.href
            };
            
            // ذخیره در localStorage
            localStorage.setItem('user_progress_' + sessionId, JSON.stringify(progressData));
            
            // ارسال به سرور (اگر در دسترس باشد)
            if (typeof marketLocationVars !== 'undefined' && marketLocationVars.ajaxUrl !== '#demo-mode') {
                $.ajax({
                    url: marketLocationVars.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'track_user_progress',
                        nonce: marketLocationVars.nonce,
                        progress_data: progressData
                    }
                });
            }
            
            console.log('📊 پیشرفت کاربر ذخیره شد:', progressData);
        }
    }

    /**
     * راه‌اندازی محصولات داینامیک
     */
    function initializeDynamicProducts() {
        const $container = $('.packages-container');
        $container.empty();
        
        // مرتب‌سازی محصولات بر اساس sort_order
        const allProducts = [];
        
        if (productSettings.packages) {
            allProducts.push(...productSettings.packages);
        }
        if (productSettings.special_products) {
            allProducts.push(...productSettings.special_products);
        }
        if (productSettings.normal_products) {
            allProducts.push(...productSettings.normal_products);
        }
        if (productSettings.featured_products) {
            allProducts.push(...productSettings.featured_products);
        }
        
        // مرتب‌سازی بر اساس sort_order
        allProducts.sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0));
        
        // اضافه کردن همه محصولات
        allProducts.forEach(product => {
            $container.append(createProductHTML(product));
        });
    }

    /**
     * ایجاد HTML محصول
     */
    function createProductHTML(product) {
        const hasDiscount = product.sale_price && product.sale_price < product.original_price;
        const finalPrice = product.sale_price || product.original_price;
        const discountPercent = hasDiscount ? 
            Math.round(((product.original_price - product.sale_price) / product.original_price) * 100) : 0;
        
        let specialClass = '';
        
        if (product.type === 'package') {
            // پکیج ویژه با رنگ سبز
            specialClass = 'special';
        } else if (product.type === 'featured') {
            // محصولات برجسته با رنگ نارنجی
            specialClass = 'vcard';
        }
        
        // تعیین آیکون یا تصویر
        const hasImage = product.image_url && product.image_url.trim() !== '';
        const productIcon = product.icon || '🏪';
        const iconOrImage = hasImage ? 
            `<img class="package-image" src="${product.image_url}" alt="${product.name}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
             <div class="package-icon" style="display: none;">${productIcon}</div>` :
            `<div class="package-icon">${productIcon}</div>`;
        
        return `
            <div class="package-item ${specialClass}" 
                 data-package="${product.id}" 
                 data-price="${finalPrice}"
                 data-type="${product.type}">
                <div class="package-info">
                    ${iconOrImage}
                    <div>
                        <div class="package-title">${product.name}</div>
                        ${product.subtitle ? `<div class="package-subtitle">${product.subtitle}</div>` : ''}
                        <div class="package-description">${product.description}</div>
                    </div>
                </div>
                <div class="package-price-container">
                    ${hasDiscount ? `
                        <div class="package-original-price">${Math.round(product.original_price / 1000).toLocaleString('fa-IR')} هزار تومان</div>
                        <div class="package-discount">%${discountPercent} تخفیف</div>
                    ` : ''}
                    <div class="package-price">${Math.round(finalPrice / 1000).toLocaleString('fa-IR')} هزار تومان</div>
                </div>
            </div>
        `;
    }

    /**
     * انتخاب محصول داینامیک
     */
    function selectDynamicPackage() {
        const $item = $(this);
        const pkgId = $item.data('package');
        const pkgType = $item.data('type');
        
        // اگر آیتم غیرفعال باشد، کاری نکن
        if ($item.hasClass('disabled')) return;

        if (pkgType === 'package') {
            // منطق پکیج ویژه
            if ($item.hasClass('selected')) {
                // لغو انتخاب پکیج ویژه
                $('.package-item').removeClass('selected disabled');
                selectedPackages = [];
            } else {
                // انتخاب پکیج ویژه و غیرفعال کردن محصولات معمولی
                $('.package-item[data-type="normal"]').removeClass('selected').addClass('disabled');
                $('.package-item[data-type="package"], .package-item[data-type="featured"]').removeClass('disabled');
                $item.addClass('selected');
                // حذف محصولات معمولی از لیست انتخاب شده و اضافه کردن پکیج
                selectedPackages = selectedPackages.filter(id => {
                    const itemType = $(`.package-item[data-package="${id}"]`).data('type');
                    return itemType !== 'normal';
                });
                selectedPackages.push(pkgId);
            }
        } else {
            // منطق محصولات معمولی و برجسته
            if ($item.hasClass('selected')) {
                // لغو انتخاب محصول
                $item.removeClass('selected');
                selectedPackages = selectedPackages.filter(id => id !== pkgId);
                
                // اگر پکیج ویژه انتخاب شده، آن را هم لغو کن
                $('.package-item[data-type="package"]').removeClass('selected');
                selectedPackages = selectedPackages.filter(id => {
                    const itemType = $(`.package-item[data-package="${id}"]`).data('type');
                    return itemType !== 'package';
                });
                
                // فعال کردن مجدد همه محصولات
                $('.package-item').removeClass('disabled');
            } else {
                // انتخاب محصول
                $item.addClass('selected');
                selectedPackages.push(pkgId);
                
                // بررسی اینکه آیا همه 4 محصول معمولی انتخاب شدند
                const normalSelected = selectedPackages.filter(id => {
                    const itemType = $(`.package-item[data-package="${id}"]`).data('type');
                    return itemType === 'normal';
                });
                
                if (normalSelected.length === 4) {
                    // فعال کردن خودکار پکیج ویژه
                    const packageItem = $('.package-item[data-type="package"]');
                    if (packageItem.length > 0) {
                        const packageId = packageItem.data('package');
                        
                        // حذف محصولات معمولی و اضافه کردن پکیج
                        $('.package-item[data-type="normal"]').removeClass('selected').addClass('disabled');
                        packageItem.addClass('selected');
                        
                        selectedPackages = selectedPackages.filter(id => {
                            const itemType = $(`.package-item[data-package="${id}"]`).data('type');
                            return itemType !== 'normal';
                        });
                        selectedPackages.push(packageId);
                        
                        showNotification('با انتخاب همه نقشه‌ها، پکیج ویژه فعال شد و هزینه کمتری پرداخت خواهید کرد.');
                    }
                }
            }
        }
        
        updateTotalPrice();
        trackUserProgress('product_selection');
    }

    /**
     * اعمال منطق انتخاب پکیج
     */
    function applyPackageSelection(packageId) {
        // وقتی یک پکیج ویژه انتخاب می‌شود، بقیه کارت‌ها را غیرفعال و کسل‌شده نمایش بده
        $('.package-item').not(`[data-package="${packageId}"]`).addClass('disabled').removeClass('selected');
    }

    /**
     * نمایش مرحله خاص
     */
    function showStep(step) {
        currentStep = step;
        
        // مخفی کردن همه مراحل
        $('.form-step').removeClass('active');
        
        // نمایش مرحله جاری
        $(`.form-step[data-step="${step}"]`).addClass('active');
        
        // به‌روزرسانی نقطه‌های step indicator
        updateStepIndicator();
        
        // راه‌اندازی نقشه در مرحله 2
        if (step === 2 && !map) {
            setTimeout(initializeMap, 100);
        }
        
        // به‌روزرسانی خلاصه در مرحله 4
        if (step === 4) {
            updateSummary();
        }
        
        // پیگیری مرحله
        trackUserProgress('step_change');
    }

    /**
     * به‌روزرسانی نقطه‌های ایندیکیتور
     */
    function updateStepIndicator() {
        $('.step-dot').removeClass('active');
        for (let i = 1; i <= currentStep; i++) {
            $(`#dot-${i}`).addClass('active');
        }
    }

    /**
     * رفتن به مرحله بعدی
     */
    function nextStep() {
        if (validateCurrentStep()) {
            if (currentStep < totalSteps) {
                saveCurrentStepData();
                showStep(currentStep + 1);
            } else {
                // اگر در آخرین مرحله هستیم، فرم را ارسال کن
                saveCurrentStepData();
                $('#market-location-form').submit();
            }
        }
    }

    /**
     * رفتن به مرحله قبلی
     */
    function prevStep() {
        if (currentStep > 1) {
            // پاک کردن داده‌های مرحله فعلی قبل از برگشت
            clearCurrentStepData();
            showStep(currentStep - 1);
        }
    }

    /**
     * پاک کردن داده‌های مرحله فعلی
     */
    function clearCurrentStepData() {
        const $currentForm = $(`.form-step[data-step="${currentStep}"]`);
        
        // پاک کردن فیلدهای فرم
        $currentForm.find('.form-input, .form-select').each(function() {
            const $field = $(this);
            $field.val('').removeClass('error success');
        });
        
        // پاک کردن انتخاب محصولات در مرحله 3
        if (currentStep === 3) {
            $('.package-item').removeClass('selected disabled');
            selectedPackages = [];
            updateTotalPrice();
        }
        
        // پاک کردن نقشه در مرحله 2
        if (currentStep === 2) {
            if (map && marker) {
                map.removeLayer(marker);
                marker = null;
            }
            formData.latitude = null;
            formData.longitude = null;
        }
        
        // پاک کردن داده‌های مربوط به مرحله فعلی از formData
        switch (currentStep) {
            case 1:
                delete formData.full_name;
                delete formData.phone;
                break;
            case 2:
                delete formData.business_name;
                delete formData.business_phone;
                delete formData.province;
                delete formData.city;
                delete formData.latitude;
                delete formData.longitude;
                delete formData.manual_address;
                delete formData.website;
                delete formData.working_hours;
                break;
            case 3:
                delete formData.selected_packages;
                selectedPackages = [];
                break;
        }
        
        // به‌روزرسانی localStorage
        localStorage.setItem('market_location_form_data', JSON.stringify(formData));
    }

    /**
     * اعتبارسنجی مرحله جاری
     */
    function validateCurrentStep() {
        const $currentForm = $(`.form-step[data-step="${currentStep}"]`);
        let isValid = true;
        const errors = [];

        // بررسی فیلدهای اجباری
        $currentForm.find('.form-input[required], .form-select[required]').each(function() {
            const $field = $(this);
            const value = $field.val().trim();
            
            if (!value) {
                $field.addClass('error');
                const label = $field.closest('.form-group').find('label').text().replace('*', '').trim();
                errors.push(`فیلد "${label}" الزامی است.`);
                isValid = false;
            } else {
                $field.removeClass('error');
            }
        });

        // اعتبارسنجی خاص هر مرحله
        switch (currentStep) {
            case 1:
                // فقط اگر فیلدهای اجباری پر باشند، validation خاص انجام شود
                if ($('#full_name').val().trim() && $('#phone').val().trim()) {
                    const personalValidation = validatePersonalInfo();
                    if (!personalValidation) {
                        isValid = false;
                        return false;
                    }
                }
                break;
            case 2:
                const locationValidation = validateLocation();
                if (!locationValidation) {
                    isValid = false;
                    return false;
                }
                break;
            case 3:
                // بررسی انتخاب محصول
                if (selectedPackages.length === 0) {
                    errors.push('لطفاً حداقل یک محصول انتخاب کنید.');
                    isValid = false;
                    // نمایش پیام خطا روی container محصولات
                    $('#packages-container').addClass('error');
                } else {
                    $('#packages-container').removeClass('error');
                }
                break;
            case 4:
                // بررسی کامل بودن تمام اطلاعات
                if (selectedPackages.length === 0) {
                    errors.push('لطفاً حداقل یک محصول انتخاب کنید.');
                    isValid = false;
                }
                
                if (!formData.full_name || !formData.phone) {
                    errors.push('اطلاعات شخصی کامل نیست.');
                    isValid = false;
                }
                
                if (!formData.business_name || !formData.latitude || !formData.longitude) {
                    errors.push('اطلاعات کسب و کار یا موقعیت کامل نیست.');
                    isValid = false;
                }
                
                if (!formData.province || !formData.city) {
                    errors.push('استان و شهر انتخاب نشده است.');
                    isValid = false;
                }
                break;
        }

        // نمایش خطاها
        if (errors.length > 0) {
            showNotification(errors.join('<br>'), 'error');
            isValid = false;
        }

        return isValid;
    }

    /**
     * اعتبارسنجی اطلاعات شخصی
     */
    function validatePersonalInfo() {
        let isValid = true;
        const errors = [];
        
        // بررسی نام و نام خانوادگی - حروف فارسی و انگلیسی
        const fullName = $('#full_name').val().trim();
        const nameRegex = /^[\u0600-\u06FF\u0041-\u005A\u0061-\u007A\s]+$/;
        
        if (fullName && !nameRegex.test(fullName)) {
            $('#full_name').addClass('error').removeClass('success');
            errors.push('• نام و نام خانوادگی فقط باید شامل حروف فارسی و انگلیسی باشد');
            isValid = false;
        } else if (fullName) {
            $('#full_name').removeClass('error').addClass('success');
        }
        
        // بررسی شماره موبایل
        const phone = $('#phone').val().trim();
        const phoneRegex = /^09\d{9}$/;
        
        if (phone && !phoneRegex.test(phone)) {
            $('#phone').addClass('error').removeClass('success');
            errors.push('• فرمت شماره همراه اشتباه است. شماره همراه باید ۱۱ رقم و به صورت 09121111111 باشد');
            isValid = false;
        } else if (phone) {
            $('#phone').removeClass('error').addClass('success');
        }
        
        // نمایش خطاها با فرمت لیستی
        if (!isValid) {
            const errorMessage = errors.join('\n');
            showNotification(errorMessage, 'error', 5000);
        }

        return isValid;
    }

    /**
     * اعتبارسنجی انتخاب محصولات
     */
    function validatePackageSelection() {
        if (selectedPackages.length === 0) {
            // حذف نوتیفیکیشن‌های قبلی
            $('.notification').remove();
            showNotification('لطفاً یکی از محصولات را انتخاب کنید.', 'error');
            return false;
        }
        return true;
    }

    /**
     * اعتبارسنجی موقعیت
     */
    function validateLocation() {
        let isValid = true;
        const errors = [];
        
        // بررسی نام کسب و کار
        const businessName = $('#business_name').val().trim();
        if (!businessName) {
            $('#business_name').addClass('error').removeClass('success');
            errors.push('• نام کسب و کار الزامی است');
            isValid = false;
        } else if (businessName.length >= 2) {
            $('#business_name').removeClass('error').addClass('success');
        }
        
        // بررسی شماره تماس کسب و کار
        const businessPhone = $('#business_phone').val().trim();
        const businessPhoneRegex = /^\d{3,}$/;
        if (!businessPhone) {
            $('#business_phone').addClass('error').removeClass('success');
            errors.push('• شماره تماس کسب و کار الزامی است');
            isValid = false;
        } else if (businessPhoneRegex.test(businessPhone)) {
            $('#business_phone').removeClass('error').addClass('success');
        } else {
            $('#business_phone').addClass('error').removeClass('success');
            errors.push('• شماره تماس کسب و کار باید حداقل ۳ رقم باشد');
            isValid = false;
        }
        
        // بررسی انتخاب موقعیت روی نقشه
        if (!formData.latitude || !formData.longitude) {
            errors.push('• لطفاً موقعیت خود را روی نقشه انتخاب کنید');
            isValid = false;
            
            // اضافه کردن کلاس error به map-container
            const mapContainer = $('.map-container');
            if (mapContainer.length) {
                mapContainer.addClass('error').removeClass('success');
                
                // حذف کلاس error بعد از 3 ثانیه
                setTimeout(() => {
                    mapContainer.removeClass('error');
                }, 3000);
            }
            
            // اسکرول به نقشه
            $('html, body').animate({
                scrollTop: mapContainer.offset().top - 100
            }, 500);
        } else {
            // اگر موقعیت انتخاب شده، کلاس success اضافه کن
            const mapContainer = $('.map-container');
            if (mapContainer.length) {
                mapContainer.addClass('success').removeClass('error');
            }
        }
        
        // منطق اصلاح شده استان و شهر
        const province = $('#province').val();
        const city = $('#city').val();
        const isProvinceSelected = province && province !== 'انتخاب کنید' && province.trim() !== '';
        const isCitySelected = city && city !== 'انتخاب کنید' && city.trim() !== '';
        
        // اگر هیچ کدام انتخاب نشده، خطای استان نمایش بده
        if (!isProvinceSelected) {
            $('#province').addClass('error').removeClass('success');
            $('#city').removeClass('error success');
            errors.push('• انتخاب استان الزامی است');
            isValid = false;
        } else {
            // استان انتخاب شده، بررسی شهر
            $('#province').removeClass('error').addClass('success');
            
            if (!isCitySelected) {
                $('#city').addClass('error').removeClass('success');
                errors.push('• انتخاب شهر الزامی است');
                isValid = false;
            } else {
                $('#city').removeClass('error').addClass('success');
            }
        }
        
        // نمایش خطاها با فرمت لیستی
        if (!isValid) {
            const errorMessage = errors.join('\n');
            showNotification(errorMessage, 'error', 5000);
        }

        return isValid;
    }

    /**
     * به‌روزرسانی قیمت کل
     */
    function updateTotalPrice() {
        let subtotal = 0;
        
        selectedPackages.forEach(packageId => {
            const product = [...productSettings.packages, ...productSettings.special_products]
                .find(p => p.id === packageId);
            if (product) {
                // استفاده از قیمت ویژه اگر موجود باشد، وگرنه قیمت اصلی
                const finalPrice = product.sale_price || product.original_price;
                subtotal += finalPrice;
            }
        });
        
        // محاسبه مالیات 10%
        const taxAmount = Math.round(subtotal * 0.1);
        const totalPrice = subtotal + taxAmount;
        
        // نمایش اعداد با فرمت فارسی (هزار تومان)
        const formatPrice = (price) => {
            return Math.round(price / 1000).toLocaleString('fa-IR') + ' هزار تومان';
        };
        
        $('#subtotal-price').text(formatPrice(subtotal));
        $('#tax-amount').text(formatPrice(taxAmount));
        $('#total-price').text(formatPrice(totalPrice));
        $('#selected_packages').val(JSON.stringify(selectedPackages));
    }

    /**
     * تولید session ID
     */
    function generateSessionId() {
        return 'sess_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    /**
     * دریافت فیلدهای تکمیل شده در مرحله فعلی
     */
    function getCurrentStepCompletedFields() {
        const $currentForm = $(`.form-step[data-step="${currentStep}"]`);
        const completedFields = [];
        
        $currentForm.find('.form-input, .form-select').each(function() {
            const $field = $(this);
            if ($field.val() && $field.val().trim()) {
                completedFields.push($field.attr('name'));
            }
        });
        
        return completedFields;
    }

    // توابع کمکی باقی‌مانده...
    
    /**
     * اضافه کردن تابع به‌روزرسانی شهرها
     */
    function updateCityOptions(selectedProvince) {
        const $citySelect = $('#city');
        $citySelect.empty().append('<option value="">انتخاب کنید</option>');
        
        if (selectedProvince && iranProvinces[selectedProvince]) {
            iranProvinces[selectedProvince].forEach(city => {
                $citySelect.append(`<option value="${city}">${city}</option>`);
            });
        }
    }

    /**
     * راه‌اندازی dropdown های استان/شهر
     */
    function initializeLocationDropdowns() {
        // پر کردن dropdown استان
        const $provinceSelect = $('#province');
        $provinceSelect.empty().append('<option value="">انتخاب کنید</option>');
        
        Object.keys(iranProvinces).forEach(province => {
            $provinceSelect.append(`<option value="${province}">${province}</option>`);
        });
        
        // راه‌اندازی select2 برای dropdown ها
        if ($.fn.select2) {
            $('.searchable-select').select2({
                placeholder: 'انتخاب کنید...',
                allowClear: true,
                language: {
                    noResults: function() { return "نتیجه‌ای یافت نشد"; },
                    searching: function() { return "در حال جستجو..."; }
                },
                dir: 'rtl'
            });
        }
        
        // تغییر استان
        $provinceSelect.on('change', function() {
            const selectedProvince = $(this).val();
            updateCityOptions(selectedProvince);
            
            if ($.fn.select2) {
                $('#city').trigger('change.select2');
            }
        });
    }

    /**
     * راه‌اندازی ساعات کاری
     */
    function initializeWorkingHours() {
        // مقدار پیش‌فرض برای فیلد ساعت کاری
        if (!$('#working_hours_text').val()) {
            $('#working_hours_text').val('24 ساعته');
        }
        $('#working_hours').val($('#working_hours_text').val());
        
        // به‌روزرسانی فیلد مخفی هنگام تغییر فیلد متنی
        $('#working_hours_text').on('input', function() {
            $('#working_hours').val($(this).val());
        });
    }

    /**
     * دریافت ساعات کاری
     */
    function getWorkingHours() {
        return $('#working_hours_text').val() || '24 ساعته';
    }

    /**
     * ذخیره اطلاعات مرحله جاری
     */
    function saveCurrentStepData() {
        const $currentForm = $(`.form-step[data-step="${currentStep}"]`);
        
        $currentForm.find('.form-input, .form-select').each(function() {
            const $field = $(this);
            const name = $field.attr('name');
            const value = $field.val();
            
            if (name) {
                formData[name] = value;
            }
        });
        
        formData.working_hours = getWorkingHours();
        formData.selected_packages = selectedPackages;
        formData.session_id = sessionId; // اضافه کردن session ID
        formData.timestamp = Date.now(); // اضافه کردن timestamp
        
        localStorage.setItem('market_location_form_data', JSON.stringify(formData));
        localStorage.setItem('market_location_session_id', sessionId);
    }

    /**
     * ذخیره کل اطلاعات فرم
     */
    function saveFormData() {
        saveCurrentStepData();
        trackUserProgress('auto_save');
    }

    /**
     * بارگذاری اطلاعات فرم از localStorage
     */
    function loadFormFromStorage() {
        const savedData = localStorage.getItem('market_location_form_data');
        if (savedData) {
            try {
                formData = JSON.parse(savedData);
                populateFormFields();
            } catch (e) {
                console.error('Error loading form data:', e);
            }
        }
    }

    /**
     * پر کردن فیلدهای فرم با اطلاعات ذخیره شده
     */
    function populateFormFields() {
        // پر کردن فیلدهای معمولی
        for (const [name, value] of Object.entries(formData)) {
            const $field = $(`[name="${name}"]`);
            if ($field.length && value) {
                $field.val(value);
                // اعمال کلاس success برای فیلدهای پر شده
                if (value.toString().trim() !== '') {
                    $field.removeClass('error').addClass('success');
                }
            }
        }
        
        // بازیابی محصولات انتخابی
        if (formData.selected_packages && Array.isArray(formData.selected_packages)) {
            selectedPackages = [...formData.selected_packages];
            selectedPackages.forEach(packageId => {
                $(`.package-item[data-package="${packageId}"]`).addClass('selected');
            });
            updateTotalPrice();
        }
        
        // بازیابی موقعیت نقشه
        if (formData.latitude && formData.longitude) {
            setTimeout(() => {
                if (map) {
                    const lat = parseFloat(formData.latitude);
                    const lng = parseFloat(formData.longitude);
                    
                    // تنظیم مرکز نقشه
                    map.setView([lat, lng], 15);
                    
                    // اضافه کردن marker
                    if (marker) {
                        map.removeLayer(marker);
                    }
                    marker = L.marker([lat, lng], { draggable: true }).addTo(map);
                    
                    // به‌روزرسانی نمایش موقعیت
                    updateCoordinates(lat, lng);
                    if (formData.auto_address) {
                        updateLocationDisplay(formData.auto_address);
                    } else {
                        reverseGeocode(lat, lng);
                    }
                    
                    // اضافه کردن event listener برای marker
                    marker.on('dragend', function(e) {
                        const position = e.target.getLatLng();
                        updateCoordinates(position.lat, position.lng);
                        reverseGeocode(position.lat, position.lng);
                    });
                }
            }, 500);
        }
        
        // بازیابی استان و شهر
        if (formData.province) {
            $('#province').val(formData.province).removeClass('error').addClass('success');
            // بارگذاری شهرهای استان انتخابی
            updateCityOptions(formData.province);
            
            if (formData.city) {
                setTimeout(() => {
                    $('#city').val(formData.city).removeClass('error').addClass('success');
                }, 100);
            }
        }
    }

    /**
     * شروع auto-save
     */
    function startAutoSave() {
        setInterval(saveFormData, 30000);
    }

    /**
     * راه‌اندازی نقشه
     */
    function initializeMap() {
        $('#map').html('<div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #64748b;">در حال بارگذاری نقشه...</div>');
        
        try {
            const defaultLat = 35.6892;
            const defaultLng = 51.3890;
            
            map = L.map('map').setView([defaultLat, defaultLng], 13);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);
            
            // حذف marker پیش‌فرض - فقط نقشه نمایش داده شود
            // marker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(map);
            
            // حذف تنظیمات پیش‌فرض
            // updateCoordinates(defaultLat, defaultLng);
            // updateLocationDisplay('تهران، خیابان ولیعصر');
            
            // نمایش پیام راهنما
            updateLocationDisplay('روی نقشه کلیک کنید تا موقعیت خود را انتخاب کنید');
            
            map.on('click', function(e) {
                if (marker) {
                    map.removeLayer(marker);
                }
                
                marker = L.marker(e.latlng, { draggable: true }).addTo(map);
                
                // اضافه کردن کلاس success به map-container
                $('.map-container').addClass('success').removeClass('error');
                
                updateCoordinates(e.latlng.lat, e.latlng.lng);
                reverseGeocode(e.latlng.lat, e.latlng.lng);
                
                marker.on('dragend', function(e) {
                    const position = e.target.getLatLng();
                    updateCoordinates(position.lat, position.lng);
                    reverseGeocode(position.lat, position.lng);
                });
            });
            
            setTimeout(() => { map.invalidateSize(); }, 100);
            
        } catch (error) {
            console.error('Error initializing map:', error);
            $('#map').html('<div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #ef4444;">خطا در بارگذاری نقشه</div>');
        }
    }

    /**
     * به‌روزرسانی مختصات
     */
    function updateCoordinates(lat, lng) {
        const preciseLatitude = parseFloat(lat.toFixed(6));
        const preciseLongitude = parseFloat(lng.toFixed(6));
        
        formData.latitude = preciseLatitude;
        formData.longitude = preciseLongitude;
        
        // اضافه کردن حاشیه سبز ملایم به نقشه وقتی موقعیت انتخاب شد
        if (map && map.getContainer) {
            const mapContainer = map.getContainer();
            $(mapContainer).css({
                'border': '2px solid #28a745',
                'border-radius': '8px',
                'box-shadow': '0 0 5px rgba(40, 167, 69, 0.2)'
            });
        }
        
        $('#coordinates').text(`${preciseLatitude}, ${preciseLongitude}`);
        $('#latitude').val(preciseLatitude);
        $('#longitude').val(preciseLongitude);
        
        saveFormData();
        trackUserProgress('location_change');
    }

    /**
     * به‌روزرسانی نمایش موقعیت
     */
    function updateLocationDisplay(address) {
        $('#selected-location').text(address);
    }

    /**
     * تبدیل مختصات به آدرس
     */
    function reverseGeocode(lat, lng) {
        $.ajax({
            url: `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&accept-language=fa`,
            type: 'GET',
            success: function(data) {
                if (data && data.display_name) {
                    const filteredAddress = filterAddress(data.display_name);
                    updateLocationDisplay(filteredAddress);
                    formData.auto_address = filteredAddress;
                } else {
                    updateLocationDisplay('آدرس یافت نشد');
                }
            },
            error: function() {
                updateLocationDisplay('خطا در دریافت آدرس');
            }
        });
    }
    
    /**
     * فیلتر آدرس برای نمایش بهتر
     */
    function filterAddress(fullAddress) {
        let parts = fullAddress.split(',').map(part => part.trim());
        let filteredParts = [];
        
        for (let part of parts) {
            if (!part.match(/\d{5}-?\d+/) && 
                !part.includes('ایران') && 
                !part.includes('استان') &&
                !part.includes('شهرستان') &&
                !part.includes('بخش مرکزی')) {
                filteredParts.push(part);
            }
        }
        
        if (filteredParts.length >= 3) {
            let addressComponents = [];
            let cityPart = filteredParts.find(part => 
                part.includes('تهران') || part.includes('اصفهان') || 
                part.includes('شیراز') || part.includes('کرج') ||
                part.includes('مشهد') || part.includes('تبریز') ||
                part.includes('شهر') || part.length <= 20
            );
            
            if (cityPart) {
                addressComponents.push(cityPart);
                filteredParts = filteredParts.filter(p => p !== cityPart);
            }
            
            let districtPart = filteredParts.find(part => 
                part.includes('منطقه') || part.includes('ناحیه') ||
                part.includes('محله') || (part.length <= 25 && !part.includes('خیابان'))
            );
            
            if (districtPart) {
                addressComponents.push(districtPart);
                filteredParts = filteredParts.filter(p => p !== districtPart);
            }
            
            let streetParts = filteredParts.filter(part => 
                part.includes('خیابان') || part.includes('کوچه') ||
                part.includes('بلوار') || part.includes('میدان')
            );
            
            if (streetParts.length > 0) {
                streetParts.sort((a, b) => {
                    if (a.includes('بلوار') || a.includes('میدان')) return -1;
                    if (b.includes('بلوار') || b.includes('میدان')) return 1;
                    if (a.includes('خیابان') && b.includes('کوچه')) return -1;
                    if (a.includes('کوچه') && b.includes('خیابان')) return 1;
                    return 0;
                });
                
                addressComponents = addressComponents.concat(streetParts.slice(0, 2));
            }
            
            if (addressComponents.length < 3 && filteredParts.length > 0) {
                let remainingParts = filteredParts.filter(part => 
                    !streetParts.includes(part) && 
                    part !== districtPart && 
                    part !== cityPart
                );
                addressComponents = addressComponents.concat(remainingParts.slice(0, 2));
            }
            
            return addressComponents.join('، ').substring(0, 100) + 
                   (addressComponents.join('، ').length > 100 ? '...' : '');
        }
        
        return filteredParts.slice(0, 3).join('، ') || 'آدرس نامشخص';
    }

    /**
     * به‌روزرسانی خلاصه اطلاعات
     */
    function updateSummary() {
        // Diagnostic log for updateSummary
        console.log('[updateSummary] called. currentStep:', currentStep, 'selectedPackages:', selectedPackages, 'productSettings:', productSettings);
        const fullname = $('#full_name').val() || 'ثبت نشده';
        const phone = $('#phone').val() || 'ثبت نشده';
        
        let personalInfo = `
            <div class="summary-item">
                <span class="summary-item-label">نام و نام خانوادگی:</span>
                <span class="summary-item-value">${fullname}</span>
            </div>
            <div class="summary-item">
                <span class="summary-item-label">تلفن همراه:</span>
                <span class="summary-item-value">${phone}</span>
            </div>
        `;
        
        const businessName = $('#business_name').val() || 'ثبت نشده';
        const businessPhone = $('#business_phone').val() || 'ثبت نشده';
        const website = $('#website').val() || 'ثبت نشده';
        const manualAddress = $('#manual_address').val() || 'ثبت نشده';
        const coordinates = $('#coordinates').text() || 'ثبت نشده';

        // Build business info including location summary
        let businessInfo = `
            <div class="summary-item">
                <span class="summary-item-label">نام کسب‌وکار:</span>
                <span class="summary-item-value">${businessName}</span>
            </div>
            <div class="summary-item">
                <span class="summary-item-label">تلفن کسب‌وکار:</span>
                <span class="summary-item-value">${businessPhone}</span>
            </div>
            <div class="summary-item">
                <span class="summary-item-label">آدرس وب‌سایت:</span>
                <span class="summary-item-value">${website}</span>
            </div>
            <div class="summary-item">
                <span class="summary-item-label">آدرس دقیق:</span>
                <span class="summary-item-value">${manualAddress}</span>
            </div>
            <div class="summary-item">
                <span class="summary-item-label">مختصات:</span>
                <span class="summary-item-value">${coordinates}</span>
            </div>
        `;
        
        const workingHours = getWorkingHours();
        const formattedHours = formatWorkingHours(workingHours);
        
        businessInfo += `
            <div class="summary-item">
                <span class="summary-item-label">ساعات کاری:</span>
                <span class="summary-item-value">${formattedHours}</span>
            </div>
        `;
        
        $('#summary-personal').html(personalInfo);
        $('#summary-business').html(businessInfo);
        
        // Render selected products summary from DOM
        const $packagesList = $('#summary-packages-list');
        $packagesList.empty();
        const $selectedItems = $('.package-item.selected');
        let subtotal = 0;
        if ($selectedItems.length > 0) {
            $selectedItems.each(function() {
                const $el = $(this);
                const name = $el.find('.package-title').text().trim();
                const price = parseInt($el.attr('data-price')) || 0;
                subtotal += price;
                const formatPrice = (price) => {
                    return price.toLocaleString('fa-IR') + ' تومان';
                };
                
                $packagesList.append(`
                    <div class="summary-package-item">
                        <span class="package-name">${name}</span>
                        <span class="package-price">${formatPrice(price)}</span>
                    </div>
                `);
            });
        } else {
            $packagesList.html('<p style="color: #6b7280;">محصولی انتخاب نشده</p>');
        }
        // Calculate and show totals
        const taxAmount = Math.round(subtotal * 0.1);
        const totalPrice = subtotal + taxAmount;
        
        // نمایش با فرمت 889.000 تومان (مرحله آخر)
        $('#subtotal-price').text(subtotal.toLocaleString('fa-IR') + ' تومان');
        $('#tax-amount').text(taxAmount.toLocaleString('fa-IR') + ' تومان');
        $('#total-price').text(totalPrice.toLocaleString('fa-IR') + ' تومان');
    }

    /**
     * فرمت کردن ساعات کاری برای نمایش
     */
    function formatWorkingHours(hours) {
        // برای سیستم ساده شده، فقط متن را برگردان
        return hours || '24 ساعته';
    }

    /**
     * submit فرم نهایی
     */
    function handleFormSubmit(e) {
        e.preventDefault();
        
        console.log('DEBUG: Form submit started');
        
        // حل مشکل فیلد website قبل از validation
        const websiteField = document.getElementById('website');
        if (websiteField) {
            // حذف موقت required attribute
            const wasRequired = websiteField.hasAttribute('required');
            websiteField.removeAttribute('required');
            
            // تنظیم مقدار پیشفرض اگر خالی باشد
            if (!websiteField.value || websiteField.value.trim() === '') {
                websiteField.value = 'https://';
            }
            
            // اطمینان از اینکه مقدار معتبر URL باشد
            if (!websiteField.value.startsWith('http://') && !websiteField.value.startsWith('https://')) {
                websiteField.value = 'https://' + websiteField.value;
            }
            
            console.log('DEBUG: Website field fixed:', websiteField.value);
        }
        
        // بررسی اعتبار فرم
        if (!form.checkValidity()) {
            console.log('DEBUG: Form validation failed');
            
            // نمایش پیام خطا برای کاربر
            const firstInvalidField = form.querySelector(':invalid');
            if (firstInvalidField) {
                console.log('DEBUG: First invalid field:', firstInvalidField.name, firstInvalidField.validationMessage);
                firstInvalidField.focus();
                alert('لطفاً فیلدهای مورد نیاز را پر کنید: ' + firstInvalidField.validationMessage);
            }
            return;
        }
        
        // بررسی packages انتخاب شده
        const selectedPackages = getSelectedPackages();
        console.log('DEBUG: Selected packages:', selectedPackages);
        
        if (selectedPackages.length === 0) {
            alert('لطفاً حداقل یک پکیج انتخاب کنید.');
            return;
        }
        
        // دریافت تمام داده‌های فرم
        const formData = new FormData(form);
        
        // اضافه کردن packages انتخاب شده
        selectedPackages.forEach((pkg, index) => {
            formData.append(`packages[${index}][name]`, pkg.name);
            formData.append(`packages[${index}][price]`, pkg.price);
        });
        
        // محاسبه مبلغ کل
        const totalAmount = selectedPackages.reduce((total, pkg) => total + pkg.price, 0);
        formData.append('total_amount', totalAmount);
        
        // اضافه کردن action
        formData.append('action', 'market_google_submit_location');
        formData.append('nonce', marketGoogleAjax.nonce);
        
        console.log('DEBUG: FormData contents:');
        for (let [key, value] of formData.entries()) {
            console.log(`${key}: ${value}`);
        }
        
        // اعمال تبدیل اعداد فارسی
        const phone = formData.get('phone');
        const businessPhone = formData.get('business_phone');
        if (phone) {
            formData.set('phone', convertPersianToEnglish(phone));
            console.log('DEBUG: Phone converted:', phone, '->', convertPersianToEnglish(phone));
        }
        if (businessPhone) {
            formData.set('business_phone', convertPersianToEnglish(businessPhone));
            console.log('DEBUG: Business phone converted:', businessPhone, '->', convertPersianToEnglish(businessPhone));
        }
        
        // غیرفعال کردن دکمه submit برای جلوگیری از ارسال مجدد
        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'در حال پردازش...';
        }
        
        console.log('DEBUG: Sending AJAX request to:', marketGoogleAjax.ajax_url);
        
        // ارسال درخواست AJAX
        jQuery.ajax({
            url: marketGoogleAjax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            timeout: 30000, // 30 second timeout
            beforeSend: function(xhr) {
                console.log('DEBUG: AJAX request starting...');
            },
            success: function(response) {
                console.log('DEBUG: Server response received:', response);
                
                try {
                    // اگر response string است، parse کن
                    if (typeof response === 'string') {
                        response = JSON.parse(response);
                    }
                    
                    if (response.success) {
                        console.log('DEBUG: Request successful');
                        if (response.data && response.data.redirect_url) {
                            console.log('DEBUG: Redirecting to payment gateway:', response.data.redirect_url);
                            window.location.href = response.data.redirect_url;
                        } else {
                            alert('فرم با موفقیت ارسال شد!');
                            form.reset();
                        }
                    } else {
                        console.error('DEBUG: Server returned error:', response.data);
                        let errorMessage = 'خطای نامشخص رخ داده است.';
                        if (response.data && response.data.message) {
                            errorMessage = response.data.message;
                        }
                        alert('خطا: ' + errorMessage);
                    }
                } catch (parseError) {
                    console.error('DEBUG: Error parsing response:', parseError, response);
                    alert('خطا در پردازش پاسخ سرور');
                }
            },
            error: function(xhr, status, error) {
                console.error('DEBUG: AJAX error occurred:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    responseText: xhr.responseText,
                    error: error,
                    readyState: xhr.readyState
                });
                
                let errorMessage = 'خطا در ارتباط با سرور';
                if (xhr.status === 400) {
                    errorMessage = 'درخواست نامعتبر (400)';
                } else if (xhr.status === 500) {
                    errorMessage = 'خطای داخلی سرور (500)';
                } else if (xhr.status === 0) {
                    errorMessage = 'عدم دسترسی به سرور';
                }
                
                alert(errorMessage + ': ' + error);
            },
            complete: function() {
                // فعال کردن مجدد دکمه submit
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = 'پرداخت و ثبت نهایی';
                }
                console.log('DEBUG: AJAX request completed');
            }
        });
    }
    

    
    /**
     * مدیریت خطاهای اتصال
     */
    function handleConnectionError(error, $submitBtn, originalText) {
        console.error('🚨 خطای اتصال:', error);
        
        let errorMessage = error.message;
        let showVPNAlert = false;
        
        switch (error.type) {
            case 'gateway_unavailable':
                errorMessage = '🚫 درگاه‌های پرداخت در دسترس نیستند';
                showVPNAlert = true;
                break;
                
            case 'network_error':
                errorMessage = '🌐 مشکل در اتصال اینترنت';
                showVPNAlert = true;
                break;
                
            case 'timeout':
                errorMessage = '⏱️ زمان اتصال تمام شد';
                break;
                
            case 'demo_mode':
                errorMessage = '⚠️ سیستم در حالت دمو';
                break;
                
            default:
                errorMessage = '❌ ' + errorMessage;
        }
        
        showNotification(errorMessage, 'error', 10000);
        
        if (showVPNAlert && !vpnDetected) {
            setTimeout(() => {
                showVPNWarning();
            }, 2000);
        }
        
        $submitBtn.text(originalText).prop('disabled', false);
    }
    
    /**
     * ادامه فرآیند ارسال فرم پس از تست موفق
     */
    function proceedWithFormSubmission($submitBtn, originalText) {
        $submitBtn.text('در حال پردازش...');
        
        console.log('✅ Starting form data collection');
        
        saveCurrentStepData();
        trackUserProgress('form_submit');
        
        console.log('📡 AJAX URL:', marketLocationVars.ajaxUrl);
        console.log('🔑 Nonce:', marketLocationVars.nonce);
        
        // جمع‌آوری تمام داده‌های فرم از localStorage
        const allFormData = JSON.parse(localStorage.getItem('market_location_form_data') || '{}');
        console.log('💾 Loaded form data from localStorage:', allFormData);
        
        // ساخت FormData object
        const formData = new FormData();
        formData.append('action', 'submit_location_form');
        formData.append('nonce', marketLocationVars.nonce);
        
        console.log('📦 Added basic form data');
        
        // اضافه کردن تمام داده‌های ذخیره شده
        Object.keys(allFormData).forEach(key => {
            if (allFormData[key] !== null && allFormData[key] !== undefined) {
                if (typeof allFormData[key] === 'object') {
                    formData.append(key, JSON.stringify(allFormData[key]));
                    console.log(`📄 Added ${key}:`, JSON.stringify(allFormData[key]));
                } else {
                    formData.append(key, allFormData[key]);
                    console.log(`📄 Added ${key}:`, allFormData[key]);
                }
            }
        });
        
        // اضافه کردن محصولات انتخابی
        if (selectedPackages && selectedPackages.length > 0) {
            formData.append('selected_packages', JSON.stringify(selectedPackages));
            console.log(`📦 Added selected packages:`, selectedPackages);
        } else {
            console.error('❌ NO PACKAGES SELECTED!');
            $submitBtn.text(originalText).prop('disabled', false);
            showNotification('لطفاً حداقل یک محصول انتخاب کنید.', 'error');
            return;
        }

        // اضافه کردن داده‌های فرم فعلی
        $('#market-location-form').find('input, select, textarea').each(function() {
            const $field = $(this);
            const name = $field.attr('name');
            if (name && name !== 'action' && name !== 'nonce') {
                if ($field.attr('type') === 'checkbox') {
                    const value = $field.is(':checked') ? '1' : '0';
                    formData.append(name, value);
                    console.log(`☑️ Added checkbox ${name}:`, value);
                } else {
                    const value = $field.val() || '';
                    formData.append(name, value);
                    console.log(`📝 Added field ${name}:`, value);
                }
            }
        });
        
        console.log('🚀 Sending AJAX request to:', marketLocationVars.ajaxUrl);
        
        // AJAX درخواست اصلی
        $.ajax({
            url: marketLocationVars.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            timeout: 90000, // 90 ثانیه timeout برای پردازش پرداخت
            beforeSend: function(xhr, settings) {
                console.log('📡 AJAX beforeSend - URL:', settings.url);
                console.log('📡 AJAX beforeSend - Type:', settings.type);
            },
            success: function(response) {
                console.log('✅ AJAX Success - Full response:', response);
                
                if (response.success) {
                    console.log('✅ Server returned success');
                    
                    if (response.data && response.data.redirect_url) {
                    console.log('🔗 Redirect URL:', response.data.redirect_url);
                    showNotification('✅ در حال هدایت به درگاه پرداخت...', 'success');
                    
                    // پاک کردن localStorage قبل از هدایت
                    localStorage.removeItem('market_location_form_data');
                    console.log('✅ localStorage cleared before payment redirect');
                    
                    // نمایش اطلاعات درگاه استفاده شده
                    if (response.data.gateway_used) {
                        const gatewayName = response.data.gateway_used === 'zarinpal' ? 'زرین‌پال' : 'بانک ملی';
                        setTimeout(() => {
                            showNotification(`🏦 درگاه ${gatewayName} انتخاب شد`, 'info', 3000);
                        }, 1000);
                    }
                    
                    // هدایت به درگاه پرداخت
                    setTimeout(function() {
                        console.log('🚀 Redirecting to:', response.data.redirect_url);
                        window.location.href = response.data.redirect_url;
                    }, 2000);
                    } else {
                        console.log('⚠️ No redirect URL in response');
                        showNotification('فرم با موفقیت ثبت شد!', 'success');
                        localStorage.removeItem('market_location_form_data');
                        $submitBtn.text(originalText).prop('disabled', false);
                    }
                } else {
                    console.error('❌ Server returned error:', response);
                    const errorMessage = response.data || response.message || 'خطایی رخ داد. لطفاً دوباره تلاش کنید.';
                    showNotification(errorMessage, 'error');
                    $submitBtn.text(originalText).prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ AJAX Error Details:');
                console.error('Status:', status);
                console.error('Error:', error);
                console.error('Response Text:', xhr.responseText);
                console.error('Status Code:', xhr.status);
                console.error('Ready State:', xhr.readyState);
                
                let errorMessage = 'خطا در اتصال به سرور.';
                
                if (status === 'timeout') {
                    errorMessage = '⏱️ زمان پردازش تمام شد. لطفاً دوباره تلاش کنید.';
                } else if (xhr.status === 0) {
                    errorMessage = '🌐 خطا در اتصال اینترنت. VPN خود را خاموش کنید.';
                    setTimeout(() => {
                        if (!vpnDetected) showVPNWarning();
                    }, 2000);
                } else if (xhr.responseText) {
                    try {
                        const errorData = JSON.parse(xhr.responseText);
                        errorMessage = errorData.message || errorMessage;
                        console.error('Parsed error data:', errorData);
                    } catch(e) {
                        console.error('Failed to parse error response:', e);
                        if (xhr.status === 403) {
                            errorMessage = '🔒 دسترسی غیرمجاز. صفحه را رفرش کنید.';
                        } else if (xhr.status === 404) {
                            errorMessage = '❓ سرویس مورد نظر یافت نشد.';
                        } else if (xhr.status === 500) {
                            errorMessage = '🔥 خطای داخلی سرور.';
                        }
                    }
                }
                
                showNotification(errorMessage, 'error');
                $submitBtn.text(originalText).prop('disabled', false);
            },
            complete: function(xhr, status) {
                console.log('🏁 AJAX Complete - Status:', status);
                console.log('🏁 AJAX Complete - XHR Status:', xhr.status);
            }
        });
    }

    /**
     * ایجاد container برای notification ها
     */
    function createNotificationContainer() {
        if (!$('.notification-container').length) {
            $('body').append('<div class="notification-container"></div>');
        }
    }

    /**
     * تنظیم فرمت 24 ساعته برای input های time
     */
    function setup24HourFormat() {
        $('input[type="time"]').each(function() {
            this.setAttribute('step', '3600');
        });
    }

    /**
     * نمایش notification مدرن
     */
    function showNotification(message, type = 'info', duration = 5000) {
        // حذف نوتیفیکیشن‌های قبلی برای جلوگیری از نمایش چندگانه
        $('.notification').remove();
        
        const icons = { 
            success: '<i class="fas fa-check-circle"></i>', 
            error: '<i class="fas fa-exclamation-circle"></i>', 
            warning: '<i class="fas fa-exclamation-triangle"></i>', 
            info: '<i class="fas fa-info-circle"></i>' 
        };
        
        // تبدیل \n به <br> برای نمایش صحیح
        const formattedMessage = message.replace(/\n/g, '<br>');
        
        const notificationId = 'notification_' + Date.now();
        const notification = $(`
            <div class="notification ${type}" id="${notificationId}">
                <div class="notification-icon">${icons[type] || '<i class="fas fa-info-circle"></i>'}</div>
                <div class="notification-content">${formattedMessage}</div>
                <button class="notification-close" onclick="closeNotification('${notificationId}')">&times;</button>
            </div>
        `);
        
        $('.notification-container').append(notification);
        
        setTimeout(() => { notification.addClass('show'); }, 50);
        setTimeout(() => { closeNotification(notificationId); }, duration);
        
        return notificationId;
    }

    /**
     * بستن notification
     */
    function closeNotification(notificationId) {
        const $notification = $('#' + notificationId);
        if ($notification.length) {
            $notification.addClass('hide');
            setTimeout(() => { $notification.remove(); }, 400);
        }
    }

    // متغیرهای سراسری
    window.closeNotification = closeNotification;
    window.showNotification = showNotification;
    
    // اضافه کردن iranProvinces به عنوان متغیر global برای تست
    window.iranProvinces = iranProvinces;
    
    /**
     * ریست کامل فرم
     */
    function resetForm() {
        // پاک کردن تمام فیلدها
        $('#market-location-form')[0].reset();
        
        // پاک کردن انتخاب محصولات
        $('.package-item').removeClass('selected disabled');
        selectedPackages = [];
        
        // پاک کردن نقشه
        if (map && marker) {
            map.removeLayer(marker);
            marker = null;
        }
        
        // پاک کردن formData
        formData = {};
        
        // پاک کردن localStorage
        localStorage.removeItem('market_location_form_data');
        
        // برگشت به مرحله اول
        showStep(1);
        
        // پاک کردن کلاس‌های خطا
        $('.form-input, .form-select').removeClass('error success');
        $('#packages-container').removeClass('error');
        
        // به‌روزرسانی قیمت
        updateTotalPrice();
    }
    

    
    /**
     * بارگذاری محصولات از API
     */
    function loadDynamicProducts() {
        // بارگذاری تنظیمات محصولات از فایل خارجی (اگر در دسترس باشد)
        if (typeof window.MarketLocationProductSettings !== 'undefined') {
            const externalSettings = window.MarketLocationProductSettings.getActiveProducts();
            productSettings.packages = externalSettings.packages;
            productSettings.special_products = externalSettings.special_products;
            console.log('✅ تنظیمات محصولات از فایل خارجی بارگذاری شد');
            initializeDynamicProducts();
            return;
        }
        
        // بارگذاری از API داینامیک
        if (typeof marketLocationVars !== 'undefined' && marketLocationVars.ajaxUrl !== '#demo-mode') {
        $.ajax({
            url: marketLocationVars.ajaxUrl,
            type: 'POST',
            data: {
                    action: 'get_active_products',
                nonce: marketLocationVars.nonce
            },
            success: function(response) {
                    if (response.success) {
                        // تبدیل داده‌های دریافتی به فرمت مناسب
                        productSettings.packages = response.data.packages.map(convertDatabaseProduct);
                        productSettings.special_products = response.data.special_products.map(convertDatabaseProduct);
                        
                        console.log('✅ محصولات از دیتابیس بارگذاری شد:', productSettings);
                        initializeDynamicProducts();
                } else {
                        console.warn('خطا در بارگذاری محصولات از دیتابیس:', response.data);
                        console.log('🔄 استفاده از محصولات پیش‌فرض...');
                        useDefaultProducts();
                }
            },
            error: function() {
                    console.warn('خطا در اتصال به API برای محصولات');
                    console.log('🔄 استفاده از محصولات پیش‌فرض...');
                    useDefaultProducts();
                }
            });
        } else {
            // حالت دمو - استفاده از محصولات پیش‌فرض
            console.log('🔧 حالت دمو - استفاده از محصولات پیش‌فرض');
            useDefaultProducts();
        }
    }
    
    /**
     * تبدیل محصول دیتابیس به فرمت فرانت‌اند
     */
    function convertDatabaseProduct(dbProduct) {
        return {
            id: dbProduct.id,
            name: dbProduct.name,
            subtitle: dbProduct.subtitle || '',
            description: dbProduct.description,
            icon: dbProduct.icon,
            image_url: dbProduct.image_url || '',
            type: dbProduct.type,
            original_price: parseInt(dbProduct.original_price),
            sale_price: parseInt(dbProduct.sale_price),
            sort_order: parseInt(dbProduct.sort_order || 0)
        };
    }
    
    /**
     * نمایش پیام عدم وجود محصول
     */
    function showNoProductsMessage() {
        const container = $('#packages-container');
        container.html(`
            <div class="no-products-message" style="text-align: center; padding: 60px 20px; background: #f9f9f9; border-radius: 12px; border: 2px dashed #ddd;">
                <div style="font-size: 48px; margin-bottom: 16px; opacity: 0.6;">📦</div>
                <h3 style="color: #666; margin: 0 0 8px;">هیچ محصولی یافت نشد</h3>
                <p style="color: #999; margin: 0 0 20px;">در حال حاضر محصولی برای انتخاب موجود نیست.</p>
                <p style="color: #e74c3c; font-size: 14px; margin: 0;">
                    <strong>نکته:</strong> ادمین باید ابتدا محصولات را در پنل ادمین تعریف کند.
                </p>
            </div>
        `);
        console.log('✅ پیام عدم وجود محصول نمایش داده شد');
    }

    /**
     * نمایش پیام حالت دمو
     */
    function showDemoModeMessage() {
        const container = $('#packages-container');
        container.html(`
            <div class="demo-mode-message" style="text-align: center; padding: 60px 20px; background: #fff3cd; border-radius: 12px; border: 2px dashed #ffc107;">
                <div style="font-size: 48px; margin-bottom: 16px;">🔧</div>
                <h3 style="color: #856404; margin: 0 0 8px;">حالت دمو</h3>
                <p style="color: #856404; margin: 0;">سیستم در حالت دمو قرار دارد.</p>
            </div>
        `);
        console.log('⚠️ فرم در حالت دمو است');
    }

    /**
     * استفاده از محصولات پیش‌فرض - غیرفعال شده
     */
    function useDefaultProducts() {
        console.log('🔄 استفاده از محصولات پیش‌فرض با subtitle...');
        
        // محصولات با subtitle
        productSettings = {
            packages: [
                {
                    id: 'all-maps',
                    name: 'تمامی نقشه‌های آنلاین',
                    subtitle: 'پکیج کامل و اقتصادی',
                    original_price: 1397000,
                    sale_price: 889000,
                    icon: '🗺️',
                    image_url: '',
                    type: 'package',
                    special: true,
                    is_featured: 1,
                    description: 'شامل تمامی نقشه‌ها - پکیج ویژه با ۳۶٪ تخفیف'
                }
            ],
            special_products: [
                {
                    id: 'google-maps',
                    name: 'نقشه گوگل‌مپ',
                    subtitle: 'محبوب‌ترین نقشه جهان',
                    original_price: 510000,
                    sale_price: 459000,
                    icon: 'G',
                    image_url: '',
                    type: 'normal',
                    special: false,
                    is_featured: 0,
                    description: 'ثبت در گوگل مپ'
                },
                {
                    id: 'openstreet',
                    name: 'اپن‌استریت',
                    subtitle: 'نقشه متن‌باز جهانی',
                    original_price: 326000,
                    sale_price: 293000,
                    icon: 'O',
                    image_url: '',
                    type: 'normal',
                    special: false,
                    is_featured: 0,
                    description: 'ثبت در OpenStreetMap'
                },
                {
                    id: 'neshan',
                    name: 'نقشه نشان',
                    subtitle: 'نقشه محلی ایران',
                    original_price: 294000,
                    sale_price: 264000,
                    icon: 'ن',
                    image_url: '',
                    type: 'normal',
                    special: false,
                    is_featured: 0,
                    description: 'ثبت در نشان'
                },
                {
                    id: 'balad',
                    name: 'نقشه بلد',
                    subtitle: 'نقشه و ترافیک هوشمند',
                    original_price: 283000,
                    sale_price: 254000,
                    icon: 'ب',
                    image_url: '',
                    type: 'normal',
                    special: false,
                    is_featured: 0,
                    description: 'ثبت در بلد'
                },
                {
                    id: 'business-card',
                    name: 'کارت ویزیت آنلاین',
                    subtitle: 'کارت ویزیت دیجیتال حرفه‌ای',
                    original_price: 1234000,
                    sale_price: 1109000,
                    icon: '💼',
                    image_url: '',
                    type: 'featured',
                    special: false,
                    is_featured: 1,
                    description: 'کارت ویزیت دیجیتال و سایت اختصاصی'
                }
            ]
        };
        
        console.log('✅ محصولات پیش‌فرض با subtitle بارگذاری شد');
        initializeDynamicProducts();
    }

    /**
     * پیش‌نمایش محصول
     */
    function updatePreview() {
        const name = $('#product-name').val() || 'نام محصول';
        const description = $('#product-description').val() || '';
        const icon = $('#product-icon').val() || '🏪';
        const imageUrl = $('#product-image-url').val() || '';
        const type = $('#product-type').val();
        const originalPrice = parseInt($('#original-price').val()) || 0;
        const salePrice = parseInt($('#sale-price').val()) || 0;
        const isActive = $('#is-active').is(':checked');
        const isFeatured = $('#is-featured').is(':checked');
        
        const discountPercent = originalPrice > salePrice ? 
            Math.round(((originalPrice - salePrice) / originalPrice) * 100) : 0;
        
        let specialClass = '';
        
        if (type === 'package') {
            if (isFeatured) {
                specialClass = 'special';
            } else {
                specialClass = 'vcard';
            }
        }
        
        // تعیین آیکون یا تصویر
        const hasImage = imageUrl && imageUrl.trim() !== '';
        const iconOrImage = hasImage ? 
            `<img class="package-image" src="${imageUrl}" alt="${name}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
             <div class="package-icon" style="display: none;">${icon}</div>` :
            `<div class="package-icon">${icon}</div>`;
        
        const previewHtml = `
            <div class="package-item ${specialClass} ${isActive ? '' : 'disabled'}">
                <div class="package-info">
                    ${iconOrImage}
                    <div>
                        <div class="package-title">${name}</div>
                        <div class="package-subtitle">زیرعنوان نمونه</div>
                        <div class="package-description">${description}</div>
                    </div>
                </div>
                <div class="package-price-container">
                    ${discountPercent > 0 ? `
                        <div class="package-original-price">${formatPrice(originalPrice)} تومان</div>
                        <div class="package-discount">${discountPercent}% تخفیف</div>
                    ` : ''}
                    <div class="package-price">${formatPrice(salePrice)} تومان</div>
                </div>
            </div>
        `;
        
        $('#product-preview').html(previewHtml);
    }
    

    /**
     * تبدیل اعداد فارسی به انگلیسی
     */
    function convertPersianToEnglish(str) {
        const persianNumbers = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        const englishNumbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        
        let result = str;
        for (let i = 0; i < 10; i++) {
            result = result.replace(new RegExp(persianNumbers[i], 'g'), englishNumbers[i]);
        }
        return result;
    }
    
    /**
     * فیلتر و تبدیل خودکار اعداد فارسی
     */
    function filterAndConvertNumbers(value) {
        // تبدیل اعداد فارسی به انگلیسی
        let converted = convertPersianToEnglish(value);
        // فقط اعداد انگلیسی را نگه داشتن
        return converted.replace(/[^0-9]/g, '');
    }

    /**
     * Debug helper function
     */
    function debugFormSubmission() {
        console.group('🔍 Form Debug Information');
        console.log('📋 Form Data:', formData);
        console.log('🎯 Selected Packages:', selectedPackages);
        console.log('💰 Total Amount:', calculateTotalAmount());
        console.log('📍 Current Step:', currentStep);
        console.log('🌍 AJAX URL:', marketLocationAjax.ajaxurl);
        console.log('🔐 Nonce:', marketLocationAjax.nonce);
        console.groupEnd();
        
        return {
            formComplete: Object.keys(formData).length > 0,
            packagesSelected: selectedPackages.length > 0,
            totalAmount: calculateTotalAmount(),
            step: currentStep
        };
    }

    // Make debug function globally accessible
    window.debugMarketForm = debugFormSubmission;

    // حل مشکل website field validation
    jQuery(document).ready(function($) {
        // حل مشکل فیلد website - حذف تمام validation ها
        function fixWebsiteField() {
            const websiteField = document.getElementById('website');
            if (websiteField) {
                // حذف required attribute
                websiteField.removeAttribute('required');
                // حذف pattern attribute
                websiteField.removeAttribute('pattern');
                // تغییر type به text برای حذف URL validation
                websiteField.type = 'text';
                
                console.log('Website field validation removed');
            }
        }
        
        // اجرای تابع در ابتدا
        fixWebsiteField();
        
        // اجرای مجدد در صورت تغییر DOM
        setTimeout(fixWebsiteField, 1000);
        
        // اضافه کردن novalidate به فرم برای جلوگیری از HTML5 validation
        const form = document.getElementById('market-location-form');
        if (form) {
            form.setAttribute('novalidate', 'true');
            console.log('Form novalidate attribute added');
        }
    });

})(jQuery);
