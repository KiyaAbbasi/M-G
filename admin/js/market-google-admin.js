jQuery(document).ready(function($) {
    'use strict';
    
    // تنظیمات عمومی
    var isDebug = (typeof window.location !== 'undefined' && window.location.hostname === 'localhost');  // تغییر از true به false
    
    function debugLog(message, data = null) {
        if (isDebug && console && console.log) {
            if (data) {
                console.log('[Market Admin] ' + message, data);
            } else {
                console.log('[Market Admin] ' + message);
            }
        }
    }
    
    debugLog('Admin script loaded');
    
    // بررسی وجود ajaxurl
    if (typeof ajaxurl === 'undefined') {
        console.error('ajaxurl is not defined');
        return;
    }
    
    // متغیر برای جلوگیری از bind مجدد
    var handlersInitialized = false;
    
    // فقط یک بار initialize کن
    if (handlersInitialized) {
        return;
    }
    
    // نمایش پیام‌ها
    function showNotification(message, type = 'info') {
        debugLog('Showing notification: ' + message + ' (' + type + ')');
        
        // حذف نوتیفیکیشن قبلی
        $('.market-notification').remove();
        
        var notificationClass = 'market-notification notification-' + type;
        var iconMap = {
            'success': '<i class="fas fa-check-circle"></i>',
            'error': '<i class="fas fa-exclamation-circle"></i>',
            'warning': '<i class="fas fa-exclamation-triangle"></i>',
            'info': '<i class="fas fa-info-circle"></i>'
        };
        
        var icon = iconMap[type] || '<i class="fas fa-info-circle"></i>';
        
        var notification = $('<div class="' + notificationClass + '">' +
            '<span class="notification-icon">' + icon + '</span>' +
            '<span class="notification-message">' + message + '</span>' +
            '<button class="notification-close">×</button>' +
        '</div>');
        
        $('body').append(notification);
        
        notification.addClass('show');
        
        // حذف خودکار بعد از 5 ثانیه
        setTimeout(function() {
            notification.removeClass('show');
            setTimeout(function() {
                notification.remove();
            }, 300);
        }, 5000);
        
        // دکمه بستن
        notification.find('.notification-close').on('click', function() {
            notification.removeClass('show');
            setTimeout(function() {
                notification.remove();
            }, 300);
        });
    }
    
    // تابع اصلی برای راه‌اندازی event handler ها - فقط یک بار
    function initializeEventHandlers() {
        if (handlersInitialized) {
            debugLog('Event handlers already initialized, skipping...');
            return;
        }
        
        debugLog('Initializing event handlers...');
        
        // جلوگیری از ارسال عادی فرم - اولویت اول
        $(document).off('submit', 'form[id*="product"], #product-form-modal, #product-form');
        $(document).on('submit', 'form[id*="product"], #product-form-modal, #product-form', function(e) {
            e.preventDefault();
            e.stopPropagation();
            debugLog('Form submission intercepted - preventing default');
            
            // بلافاصله جلوگیری از ارسال
            if (this.method && this.method.toLowerCase() === 'get') {
                debugLog('WARNING: Form had GET method, preventing submission');
                return false;
            }
            
            handleProductSave($(this));
            return false;
        });
        
        // جلوگیری اضافی برای دکمه‌های submit
        $(document).off('click', 'button[type="submit"], input[type="submit"]');
        $(document).on('click', 'button[type="submit"], input[type="submit"]', function(e) {
            var form = $(this).closest('form');
            if (form.attr('id') && (form.attr('id').includes('product') || form.attr('id') === 'product-form-modal')) {
                e.preventDefault();
                e.stopPropagation();
                debugLog('Submit button clicked - preventing default and handling via AJAX');
                handleProductSave(form);
                return false;
            }
        });

        // افزودن محصول - فقط event delegation
        $(document).off('click', '.add-product-btn');
        $(document).on('click', '.add-product-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            debugLog('Add product button clicked');
            
            // بررسی اینکه آیا modal از قبل باز است
            if ($('#product-modal').hasClass('show')) {
                debugLog('Modal already open, ignoring click');
                return;
            }
            
            resetForm();
            $('#modal-title').text('افزودن محصول جدید');
            $('#product-modal').addClass('show');
            debugLog('Modal opened for new product');
        });

        // ویرایش محصول
        $(document).off('click', '.edit-product');
        $(document).on('click', '.edit-product', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var productId = $(this).data('id');
            debugLog('Edit product clicked: ' + productId);
            
            // بررسی اینکه آیا modal از قبل باز است
            if ($('#product-modal').hasClass('show')) {
                debugLog('Modal already open, ignoring edit click');
                return;
            }
            
            $.post(ajaxurl, {
                action: 'get_product_for_edit',
                product_id: productId,
                nonce: window.marketAdminVars ? window.marketAdminVars.nonce : ''
            })
            .done(function(response) {
                if (response.success) {
                    var product = response.data;
                    $('#modal-title').text('ویرایش محصول');
                    $('#modal-product-id').val(product.id);
                    $('#modal-product-name').val(product.name);
                    $('#modal-product-type').val(product.type);
                    $('#modal-product-description').val(product.description);
                    $('#modal-original-price').val(formatPriceForInput(product.original_price));
                    
                    if (product.sale_price != product.original_price) {
                        $('#modal-sale-price').val(formatPriceForInput(product.sale_price));
                    } else {
                        $('#modal-sale-price').val('');
                    }
                    
                    $('#modal-sort-order').val(product.sort_order);
                    $('#modal-is-active').prop('checked', product.is_active == 1);
                    
                    if (product.image_url) {
                        $('#modal-product-image').val(product.image_url);
                        showImagePreview(product.image_url);
                    } else {
                        removeImage();
                    }
                    
                    $('#product-modal').addClass('show');
                    debugLog('Modal opened for edit product: ' + productId);
                }
            })
            .fail(function() {
                showNotification('خطا در دریافت اطلاعات محصول', 'error');
            });
        });

        // بستن modal
        $(document).off('click', '.modal-close, .btn-cancel, .modal-overlay');
        $(document).on('click', '.modal-close, .btn-cancel', function(e) {
            e.preventDefault();
            closeModal();
        });
        
        $(document).on('click', '.modal-overlay', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        // کلید ESC
        $(document).off('keydown.modal');
        $(document).on('keydown.modal', function(e) {
            if (e.keyCode === 27 && $('#product-modal').hasClass('show')) {
                closeModal();
            }
        });

        // سایر event handler ها...
        setupOtherHandlers();
        
        handlersInitialized = true;
        debugLog('Event handlers initialized successfully');
    }
    
    // سایر event handler ها
    function setupOtherHandlers() {
        // حذف محصول
        $(document).off('click', '.delete-product');
        $(document).on('click', '.delete-product', function() {
            var $this = $(this);
            var productId = $this.data('id');
            var productName = $this.closest('.product-item').find('.product-title').text();
            
            if ($this.prop('disabled')) return;
            
            var confirmMessage = 'آیا از حذف محصول "' + productName + '" مطمئن هستید؟\n\nاین عمل قابل برگشت نیست.';
            
            if (confirm(confirmMessage)) {
                var originalText = $this.html();
                $this.html('<i class="icon">⏳</i> در حال حذف...').prop('disabled', true);
                
                $.post(ajaxurl, {
                    action: 'delete_product',
                    product_id: productId,
                    nonce: window.marketAdminVars ? window.marketAdminVars.nonce : ''
                })
                .done(function(response) {
                    if (response.success) {
                        $this.closest('.product-item').fadeOut(300, function() {
                            $(this).remove();
                            if ($('.product-item').length === 0) {
                                refreshProducts();
                            }
                        });
                        showNotification('محصول با موفقیت حذف شد', 'success');
                    } else {
                        $this.html(originalText).prop('disabled', false);
                        showNotification(response.data || 'خطا در حذف محصول', 'error');
                    }
                })
                .fail(function() {
                    $this.html(originalText).prop('disabled', false);
                    showNotification('خطا در اتصال به سرور', 'error');
                });
            }
        });

        // تغییر وضعیت محصول
        $(document).off('click', '.toggle-status');
        $(document).on('click', '.toggle-status', function() {
            var productId = $(this).data('id');
            var currentStatus = $(this).data('status');
            var newStatus = currentStatus == 1 ? 0 : 1;
            
            var $btn = $(this);
            var originalText = $btn.html();
            $btn.html('<i class="icon">⏳</i>').prop('disabled', true);
            
            $.post(ajaxurl, {
                action: 'toggle_product_status',
                product_id: productId,
                status: newStatus,
                nonce: window.marketAdminVars ? window.marketAdminVars.nonce : ''
            })
            .done(function(response) {
                if (response.success) {
                    refreshProducts();
                    showNotification('وضعیت محصول تغییر کرد', 'success');
                } else {
                    $btn.html(originalText).prop('disabled', false);
                    showNotification('خطا در تغییر وضعیت', 'error');
                }
            })
            .fail(function() {
                $btn.html(originalText).prop('disabled', false);
                showNotification('خطا در اتصال به سرور', 'error');
            });
        });

        // بروزرسانی لیست
        $(document).off('click', '#refresh-products');
        $(document).on('click', '#refresh-products', function() {
            refreshProducts();
        });
        
        // مدیریت checkbox برای custom callbacks
        $(document).off('change', 'input[name="use_custom_callbacks"]');
        $(document).on('change', 'input[name="use_custom_callbacks"]', function() {
            if ($(this).is(':checked')) {
                $('#custom-callback-settings').slideDown();
                $('#default-callback-info').slideUp();
            } else {
                $('#custom-callback-settings').slideUp();
                $('#default-callback-info').slideDown();
            }
        });
        
        // تنظیم وضعیت اولیه
        $(document).ready(function() {
            var isCustomEnabled = $('input[name="use_custom_callbacks"]').is(':checked');
            if (isCustomEnabled) {
                $('#custom-callback-settings').show();
                $('#default-callback-info').hide();
            } else {
                $('#custom-callback-settings').hide();
                $('#default-callback-info').show();
            }
        });
        
        // مدیریت تب‌ها - فقط برای صفحات غیر از تنظیمات
        if (!$('body').hasClass('market-google-location_page_market-google-settings')) {
            var tabId = $(this).data('tab');
            
            // حذف کلاس active از همه تب‌ها
            $('.tab-button').removeClass('active');
            $('.tab-content').removeClass('active');
            
            // اضافه کردن کلاس active به تب انتخاب شده
            $(this).addClass('active');
            $('#' + tabId + '-tab').addClass('active');
        }
    }
    
    // تابع جداگانه برای مدیریت ذخیره محصول
    function handleProductSave($form) {
        debugLog('HandleProductSave called');
        
        var saveBtn = $form.find('.btn-save, button[type="submit"]');
        var originalText = saveBtn.html();
        
        // اعتبارسنجی فرم
        var name = $('#modal-product-name, #product-name').val().trim();
        // تابع تبدیل ارقام فارسی به انگلیسی
        function normalizeDigits(str) {
            if (!str) return '';
            return str.replace(/[۰-۹]/g, function(c) {
                return '0123456789'['۰۱۲۳۴۵۶۷۸۹'.indexOf(c)];
            }).replace(/[^0-9]/g, '');
        }
        
        // حذف کاما و فرمت‌های جداکننده فارسی و عربی
        var originalPriceRaw = $('#modal-original-price, #original-price').val() || '';
        var originalPrice = normalizeDigits(originalPriceRaw);
        
        if (!name) {
            showNotification('نام محصول الزامی است', 'error');
            return;
        }
        
        if (!originalPrice || parseInt(originalPrice) <= 0) {
            showNotification('قیمت اصلی باید بیشتر از صفر باشد', 'error');
            return;
        }
        
        saveBtn.html('<i class="icon">⏳</i> در حال ذخیره...').prop('disabled', true);
        
        // جمع‌آوری داده‌ها
        var salePriceRaw = $('#modal-sale-price, #sale-price').val() || '';
        var salePrice = normalizeDigits(salePriceRaw) || '';
        
        // بهبود دریافت nonce
        var nonce = '';
        if ($('#modal-nonce').length) {
            nonce = $('#modal-nonce').val();
        } else if (window.marketAdminVars && window.marketAdminVars.nonce) {
            nonce = window.marketAdminVars.nonce;
        } else {
            showNotification('خطا: nonce یافت نشد', 'error');
            saveBtn.html(originalText).prop('disabled', false);
            return;
        }
        
        var data = {
            action: 'save_product',
            nonce: nonce,
            product_id: $('#modal-product-id, #product-id').val() || '',
            name: name,
            subtitle: $('#modal-product-subtitle, #product-subtitle').val() || '',
            description: $('#modal-product-description, #product-description').val(),
            type: $('#modal-product-type, #product-type').val(),
            original_price: originalPrice,
            sale_price: salePrice,
            sort_order: $('#modal-sort-order, #sort-order').val() || '0',
            is_active: $('#modal-is-active, #is-active').is(':checked') ? 1 : 0,
            is_featured: $('#modal-is-featured, #is-featured').is(':checked') ? 1 : 0,
            image_url: $('#modal-product-image, #product-image-url').val() || ''
        };
        
        debugLog('Sending data:', data);
        
        // تعیین AJAX endpoint با استفاده از متغیر محلی‌سازی شده
        var ajaxUrl = (window.marketAdminVars && window.marketAdminVars.ajaxUrl) ? window.marketAdminVars.ajaxUrl : (window.ajaxurl || '/wp-admin/admin-ajax.php');
        debugLog('AJAX URL:', ajaxUrl);
        debugLog('Request Data for save_product:', data);
        
        $.post(ajaxUrl, data)
            .done(function(response) {
                saveBtn.html(originalText).prop('disabled', false);
                debugLog('Server response:', response);
                
                if (response.success) {
                    showNotification(response.data.message || 'محصول با موفقیت ذخیره شد', 'success');
                    
                    // بستن مودال اگر باز است
                    if ($('#product-modal').length && $('#product-modal').hasClass('show')) {
                        closeModal();
                    }
                    
                    // بروزرسانی لیست محصولات
                    refreshProducts();
                    
                    // ریست فرم standalone
                    if ($('#product-form').length && !$('#product-modal').hasClass('show')) {
                        resetForm();
                    }
                } else {
                    showNotification(response.data || 'خطا در ذخیره محصول', 'error');
                }
            })
            .fail(function(xhr, status, error) {
                saveBtn.html(originalText).prop('disabled', false);
                debugLog('AJAX Error:', {xhr: xhr, status: status, error: error});
                showNotification('خطا در ارتباط با سرور: ' + error, 'error');
                console.error('AJAX Error Details:', xhr.responseText);
            });
    }
    
    // توابع کمکی
    function refreshProducts() {
        debugLog('Refreshing products');
        
        if ($('.products-container').length) {
            $('.products-container').html('<div class="loading">⏳ در حال بارگذاری...</div>');
        }
        
        if ($('#products-list').length) {
            $('#products-list').html('<div class="loading">⏳ در حال بارگذاری...</div>');
        }
        
        $.post(ajaxurl, {
            action: 'get_products_for_settings',
            nonce: window.marketAdminVars ? window.marketAdminVars.nonce : ''
        })
        .done(function(response) {
            if (response.success) {
                $('#products-management').html(response.data.html);
                debugLog('Products refreshed successfully');
            } else {
                showNotification('خطا در بارگذاری محصولات', 'error');
            }
        })
        .fail(function() {
            showNotification('خطا در اتصال به سرور', 'error');
        });
    }
    
    function resetForm() {
        debugLog('Resetting form');
        
        if ($('#product-form-modal')[0]) {
            $('#product-form-modal')[0].reset();
        }
        
        if ($('#product-form')[0]) {
            $('#product-form')[0].reset();
        }
        
        $('#modal-product-id, #product-id').val('');
        $('#modal-is-active, #is-active').prop('checked', true);
        $('#modal-is-featured, #is-featured').prop('checked', false);
        
        removeImage();
    }
    
    function closeModal() {
        $('#product-modal').removeClass('show');
        setTimeout(function() {
            resetForm();
        }, 300);
    }
    
    function formatPriceForInput(price) {
        return price ? price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",") : '';
    }
    
    function showImagePreview(imageUrl) {
        if (imageUrl) {
            var preview = '<img src="' + imageUrl + '" alt="پیش‌نمایش" style="max-width: 100px; height: auto;">';
            $('#image-preview').html(preview);
            $('#remove-image').show();
        }
    }
    
    function removeImage() {
        $('#modal-product-image, #product-image-url').val('');
        $('#image-preview').html('<div class="placeholder"><i class="icon">📷</i><span>انتخاب تصویر</span></div>');
        $('#remove-image').hide();
    }
    
    // initial load of products in settings tab
    if ($('#products-management').length) {
        refreshProducts();
    }
    
    // فراخوانی event handlers
    initializeEventHandlers();
});