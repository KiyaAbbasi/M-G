jQuery(document).ready(function($) {
    
    // اضافه کردن تابع showNotification مشابه سایر بخش‌ها
    function showNotification(message, type = 'info', duration = 5000) {
        // حذف آلرت‌های قبلی
        $('.market-notification').remove();
        
        var icons = {
            'success': '<i class="fas fa-check-circle"></i>',
            'error': '<i class="fas fa-exclamation-circle"></i>',
            'warning': '<i class="fas fa-exclamation-triangle"></i>',
            'info': '<i class="fas fa-info-circle"></i>'
        };
        
        var notificationClass = 'market-notification notification-' + type;
        var notification = $('<div class="' + notificationClass + '">' +
            '<span class="notification-icon">' + (icons[type] || icons['info']) + '</span>' +
            '<span class="notification-message">' + message + '</span>' +
            '<button class="notification-close">×</button>' +
        '</div>');
        
        $('body').append(notification);
        setTimeout(function() { notification.addClass('show'); }, 50);
        setTimeout(function() { 
            notification.removeClass('show');
            setTimeout(function() { notification.remove(); }, 300);
        }, duration);
        
        notification.find('.notification-close').on('click', function() {
            notification.removeClass('show');
            setTimeout(function() { notification.remove(); }, 300);
        });
    }
    
    // تغییر نحوه ارسال
    $('#sending-method').on('change', function() {
        console.log('Sending method changed!');
        var method = $(this).val();
        var container = $('#event-sms-container');
        var title = $('#event-fields-title');
        
        if (method) {
            container.show();
            
            // تغییر عنوان بخش رویدادها
            if (method === 'pattern') {
                title.html('<span class="title-gray">کدهای پترن</span> <span class="title-blue">سامانه پیامکی</span>');
                
                // نمایش فیلدهای پترن و مخفی کردن فیلدهای خط خدماتی
                $('.sms-pattern-field').addClass('active');
                $('.sms-service-field').removeClass('active');
                
                // تغییر نام فیلدهای فعال/غیرفعال
                $('.sms-toggle-switch input').each(function() {
                    var name = $(this).attr('name');
                    if (name) {
                    name = name.replace('service_events', 'pattern_events');
                    $(this).attr('name', name);
                    }
                });
            } else {
                title.html('<span class="title-gray">متن‌های پیامک</span> <span class="title-blue">رویدادی</span>');
                
                // نمایش فیلدهای خط خدماتی و مخفی کردن فیلدهای پترن
                $('.sms-service-field').addClass('active');
                $('.sms-pattern-field').removeClass('active');
                
                // تغییر نام فیلدهای فعال/غیرفعال
                $('.sms-toggle-switch input').each(function() {
                    var name = $(this).attr('name');
                    if (name) {
                    name = name.replace('pattern_events', 'service_events');
                    $(this).attr('name', name);
                    }
                });
            }
        } else {
            container.hide();
        }
    });
    
    // مدیریت تب‌های تست پیامک
    $('.sms-test-tab').on('click', function() {
        console.log('SMS test tab clicked!');
        var tab = $(this).data('tab');
        
        // فعال کردن تب
        $('.sms-test-tab').removeClass('active');
        $(this).addClass('active');
        
        // نمایش محتوای تب
        $('.sms-test-content').removeClass('active');
        $('#' + tab + '-test-tab').addClass('active');
    });
    
    // تست اتصال
    $('#test-connection-btn').on('click', function() {
        console.log('Test connection button clicked!');
        var button = $(this);
        var originalText = button.html();
        
        // بررسی پر بودن فیلدهای ضروری
        var provider = $('#sms-provider').val();
        var username = $('input[name="market_google_sms_settings[username]"]').val();
        var password = $('input[name="market_google_sms_settings[password]"]').val();
        var lineNumber = $('input[name="market_google_sms_settings[line_number]"]').val();
        
        if (!provider) {
            showNotification('لطفا ابتدا سامانه پیامکی را انتخاب کنید.', 'warning');
            return;
        }
        
        if (!username || !password) {
            showNotification('لطفا نام کاربری و رمز عبور را وارد کنید.', 'warning');
            return;
        }
        
        if (!lineNumber) {
            showNotification('لطفا شماره خط ارسال را وارد کنید.', 'warning');
            return;
        }
        
        button.html('<i class="fas fa-spinner fa-spin"></i> در حال تست...').prop('disabled', true);
        
        var data = {
            action: 'market_google_test_sms_connection',
            nonce: marketGoogleSmsSettings.nonce,
            provider: provider,
            username: username,
            password: password,
            api_key: $('input[name="market_google_sms_settings[api_key]"]').val(),
            line_number: lineNumber
        };
        
        $.post(marketGoogleSmsSettings.ajaxurl, data, function(response) {
            if (response.success) {
                showNotification('✅ اتصال موفقیت‌آمیز بود!\nموجودی: ' + response.data.sms_count + ' پیامک', 'success');
                location.reload();
            } else {
                // اگر مشکل در شماره خط است و قابلیت اصلاح خودکار داریم
                if (response.data && response.data.fix_available && response.data.suggested_number) {
                    showFixLineNumberDialog(response.data.suggested_number, provider, lineNumber);
                } else {
                    showNotification('❌ خطا در اتصال: ' + response.data.message, 'error');
                }
            }
        }).fail(function(xhr, status, error) {
            console.error('AJAX Error:', xhr.responseText);
            showNotification('❌ خطا در ارتباط با سرور: ' + error, 'error');
        }).always(function() {
            button.html(originalText).prop('disabled', false);
        });
    });
    
    // ارسال پیامک تست
    $('#send-test-sms-btn').on('click', function() {
        console.log('Test SMS button clicked!');
        var button = $(this);
        var originalText = button.html();
        var mobile = $('#test-mobile-number').val();
        var eventType = $('#test-sms-type').val();
        var sendingMethod = $('#sending-method').val();
        
        // اعتبارسنجی فیلدها
        if (!mobile) {
            showNotification('لطفا شماره موبایل را وارد کنید', 'warning');
            return;
        }
        
        if (!/^09\d{9}$/.test(mobile)) {
            showNotification('شماره موبایل باید 11 رقم و با 09 شروع شود', 'warning');
            return;
        }
        
        if (!eventType) {
            showNotification('لطفا نوع پیامک تست را انتخاب کنید', 'warning');
            return;
        }
        
        button.html('<i class="fas fa-spinner fa-spin"></i> در حال ارسال...').prop('disabled', true);
        
        var data = {
            action: 'market_google_send_test_sms',
            nonce: marketGoogleSmsSettings.nonce,
            mobile: mobile,
            event_type: eventType
        };
        
        $.post(marketGoogleSmsSettings.ajaxurl, data, function(response) {
            if (response.success) {
                showNotification('✅ پیامک با موفقیت ارسال شد', 'success');
            } else {
                var errorMessage = response.data && response.data.message ? response.data.message : 'خطای نامشخص';
                showNotification('❌ خطا در ارسال پیامک: ' + errorMessage, 'error');
            }
        }).fail(function(xhr, status, error) {
            console.error('AJAX Error:', xhr.responseText);
            showNotification('❌ خطا در ارتباط با سرور: ' + error, 'error');
        }).always(function() {
            button.html(originalText).prop('disabled', false);
        });
    });
    
    // ارسال پیامک پترن تست
    $('#send-test-pattern-btn').on('click', function() {
        console.log('Test pattern SMS button clicked!');
        var button = $(this);
        var originalText = button.html();
        var mobile = $('#test-pattern-mobile').val();
        var patternCode = $('#test-pattern-code').val();
        
        // اعتبارسنجی فیلدها
        if (!mobile) {
            showNotification('لطفا شماره موبایل را وارد کنید', 'warning');
            return;
        }
        
        if (!/^09\d{9}$/.test(mobile)) {
            showNotification('شماره موبایل باید 11 رقم و با 09 شروع شود', 'warning');
            return;
        }
        
        if (!patternCode) {
            showNotification('لطفا کد پترن را وارد کنید', 'warning');
            return;
        }
        
        button.html('<i class="fas fa-spinner fa-spin"></i> در حال ارسال...').prop('disabled', true);
        
        var data = {
            action: 'market_google_test_pattern_sms',
            nonce: marketGoogleSmsSettings.nonce,
            mobile: mobile,
            pattern_code: patternCode
        };
        
        $.post(marketGoogleSmsSettings.ajaxurl, data, function(response) {
            if (response.success) {
                showNotification('✅ پیامک پترن با موفقیت ارسال شد', 'success');
            } else {
                var errorMessage = response.data && response.data.message ? response.data.message : 'خطای نامشخص';
                showNotification('❌ خطا در ارسال پیامک پترن: ' + errorMessage, 'error');
            }
        }).fail(function(xhr, status, error) {
            console.error('AJAX Error:', xhr.responseText);
            showNotification('❌ خطا در ارتباط با سرور: ' + error, 'error');
        }).always(function() {
            button.html(originalText).prop('disabled', false);
        });
    });
    
    // کپی کردن کدهای کوتاه
    $('.sms-shortcode-code').on('click', function() {
        var text = $(this).text();
        navigator.clipboard.writeText(text).then(function() {
            showNotification('📋 کد کوتاه کپی شد: ' + text, 'success', 3000);
        }).catch(function() {
            // Fallback برای مرورگرهای قدیمی
            var textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            showNotification('📋 کد کوتاه کپی شد: ' + text, 'success', 3000);
        });
    });
    
    // ذخیره تنظیمات
    $('#market-google-sms-settings-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var submitButton = form.find('.sms-submit-button');
        var originalText = submitButton.val();
        
        submitButton.val('در حال ذخیره...').prop('disabled', true);
        
        var formData = form.serialize();
        formData += '&action=market_google_save_sms_settings';
        
        $.post(marketGoogleSmsSettings.ajaxurl, formData, function(response) {
                if (response.success) {
                showNotification('✅ تنظیمات با موفقیت ذخیره شد', 'success');
                        location.reload();
                } else {
                showNotification('❌ خطا در ذخیره تنظیمات: ' + response.message, 'error');
                }
        }).fail(function(xhr, status, error) {
                console.error('AJAX Error:', xhr.responseText);
                showNotification('❌ خطا در ارتباط با سرور: ' + error, 'error');
        }).always(function() {
            submitButton.val(originalText).prop('disabled', false);
        });
    });
    
    // نمایش دیالوگ اصلاح شماره خط
    function showFixLineNumberDialog(suggestedNumber, provider, currentNumber) {
        // حذف دیالوگ قبلی اگر وجود داشته باشد
        $('.fix-line-number-dialog').remove();
        
        var dialog = $('<div class="fix-line-number-dialog">' +
            '<div class="dialog-content">' +
                '<h3>اصلاح شماره خط</h3>' +
                '<p>شماره خط وارد شده برای سامانه پیامکی صحیح نیست.</p>' +
                '<p>شماره فعلی: <strong>' + currentNumber + '</strong></p>' +
                '<p>شماره پیشنهادی: <strong>' + suggestedNumber + '</strong></p>' +
                '<div class="dialog-buttons">' +
                    '<button class="fix-button">استفاده از شماره پیشنهادی</button>' +
                    '<button class="cancel-button">انصراف</button>' +
                '</div>' +
            '</div>' +
        '</div>');
        
        $('body').append(dialog);
        
        // نمایش دیالوگ
        setTimeout(function() {
            dialog.addClass('show');
        }, 50);
        
        // دکمه اصلاح
        dialog.find('.fix-button').on('click', function() {
            // اصلاح شماره خط
            $('input[name="market_google_sms_settings[line_number]"]').val(suggestedNumber);
            
            // بستن دیالوگ
            dialog.removeClass('show');
            setTimeout(function() {
                dialog.remove();
            }, 300);
            
            // نمایش پیام
            showNotification('✅ شماره خط اصلاح شد.', 'success');
        });
        
        // دکمه انصراف
        dialog.find('.cancel-button').on('click', function() {
            dialog.removeClass('show');
            setTimeout(function() {
                dialog.remove();
            }, 300);
        });
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
        let converted = convertPersianToEnglish(value);
        return converted.replace(/[^0-9]/g, '');
    }

    // اعمال روی فیلدهای شماره موبایل
    $(document).on('input', '#test-mobile-number, #test-pattern-mobile', function() {
        const $field = $(this);
        const value = $field.val();
        const filteredValue = filterAndConvertNumbers(value);
        
        if (value !== filteredValue) {
            $field.val(filteredValue);
        }
    });
});