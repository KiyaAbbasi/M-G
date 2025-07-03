jQuery(document).ready(function($) {
    let updateInterval;
    let timeInterval;
    const REFRESH_INTERVAL = 5000; // هر 5 ثانیه
    
    function startAutoUpdate() {
        updateInterval = setInterval(() => {
            refreshDashboard();
            if (window.triggerAllFilters) window.triggerAllFilters();
        }, REFRESH_INTERVAL);
        $('.live-indicator').addClass('active');
    }

    function stopAutoUpdate() {
        clearInterval(updateInterval);
        $('.live-indicator').removeClass('active');
    }

    function updateDateTime(format = 'dddd - dd / mm / yyyy - HH:ii:ss') {
        const datetimeDisplay = document.querySelector('.datetime-display span');
        if (datetimeDisplay && typeof window.jdate === 'function') {
            const now = new Date();
            let output = '';
            if (format === 'dd') {
                output = window.jdate('d', now.getTime());
            } else if (format === 'mm') {
                output = window.jdate('m', now.getTime());
            } else if (format === 'yyyy') {
                output = window.jdate('Y', now.getTime());
            } else if (format === 'dddd - dd / mm / yyyy - HH:ii:ss') {
                // نمایش کامل به سبک حرفه‌ای
                const dayName = window.jdate('l', now.getTime());
                const day = window.jdate('d', now.getTime());
                const month = window.jdate('m', now.getTime());
                const year = window.jdate('Y', now.getTime());
                const hour = window.jdate('H', now.getTime());
                const minute = window.jdate('i', now.getTime());
                const second = window.jdate('s', now.getTime());
                output = `${dayName} - ${day} / ${month} / ${year} - ساعت ${hour}:${minute}:${second}`;
            } else {
                output = window.jdate(format, now.getTime());
            }
            datetimeDisplay.textContent = output;
        }
    }

    window.refreshDashboard = function() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'refresh_tracking_stats',
                nonce: market_google_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    
                    // به‌روزرسانی لیست کاربران آنلاین
                    $('#online-users-list').html(data.online_users_html);
                    
                    // به‌روزرسانی عدد بالای لیست کاربران آنلاین
                    $('.online-count').text(data.online_count);
                    
                    // به‌روزرسانی آمار
                    $('.online-users .pro-main-number').text(data.advanced_stats.total_users + ' نفر');
                    $('.online-users .pro-main-subtitle').html(data.advanced_stats.avg_remaining_time);
                }
            }
        });
    };

    // شروع به‌روزرسانی خودکار در لود صفحه
    startAutoUpdate();
    
    // بروزرسانی ساعت هر ثانیه با فرمت کامل نمونه
    timeInterval = setInterval(() => updateDateTime('dddd - dd / mm / yyyy - HH:ii:ss'), 1000);

    // دکمه‌های کنترل به‌روزرسانی خودکار
    $('.live-indicator').on('click', function() {
        if ($(this).hasClass('active')) {
            stopAutoUpdate();
        } else {
            startAutoUpdate();
        }
    });

    // دکمه رفرش دستی
    $('.refresh-btn').on('click', function() {
        refreshDashboard();
        updateDateTime();
    });

    window.triggerAllFilters && window.triggerAllFilters();

    window.refreshDashboard && window.refreshDashboard();
}); 
