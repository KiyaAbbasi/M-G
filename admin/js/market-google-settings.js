jQuery(document).ready(function($) {
    'use strict';
    
    // دریافت تب فعال از URL
    var urlParams = new URLSearchParams(window.location.search);
    var currentTab = urlParams.get('tab') || 'general';
    
    // تنظیم تب فعال
    setActiveTab(currentTab);
    
    function setActiveTab(tab) {
        $('.tab-button, .tab-content').removeClass('active');
        $('.tab-button[data-tab="' + tab + '"]').addClass('active');
        $('#' + tab + '-tab').addClass('active');
        $('#active_tab').val(tab);
        
        // ذخیره در localStorage
        localStorage.setItem('marketGoogleSettingsActiveTab', tab);
        
        if (tab === 'products') {
            loadProductsInTab();
        }
    }
    
    // مدیریت کلیک تب‌ها
    $('.tab-button').click(function() {
        const tab = $(this).data('tab');
        setActiveTab(tab);
        
        // تغییر URL بدون reload
        var newUrl = new URL(window.location);
        newUrl.searchParams.set('tab', tab);
        window.history.replaceState({}, '', newUrl);
    });
    
    // مدیریت ارسال فرم - اطمینان از تنظیم صحیح تب
    $('form').on('submit', function() {
        var activeTab = $('.tab-button.active').data('tab') || currentTab;
        $('#active_tab').val(activeTab);
        
        // ذخیره تب فعال قبل از submit
        localStorage.setItem('marketGoogleSettingsActiveTab', activeTab);
    });
    
    // تابع لود محصولات در تب
    function loadProductsInTab() {
        if ($('#products-management .products-container').length > 0 || 
            $('#products-management .products-loaded').length > 0) {
            return;
        }
        
        $('#products-management').html('<div class="products-loading"><p>در حال بارگذاری محصولات...</p></div>');

        $.post(ajaxurl, {
            action: 'get_products_for_settings',
            nonce: window.marketAdminVars ? window.marketAdminVars.nonce : ''
        }, function(response) {
            if (response.success) {
                $('#products-management').html(response.data.html).addClass('products-loaded');
            } else {
                $('#products-management').html('<div class="error"><p>خطا در بارگذاری محصولات</p></div>');
            }
        }).fail(function() {
            $('#products-management').html('<div class="error"><p>خطا در اتصال به سرور</p></div>');
        });
    }
});