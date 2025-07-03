(function($) {
    'use strict';

    // متغیرهای سراسری
    let currentOrderId = null;
    let isLoading = false;
    let searchTimeout = null;
    let lastSearchData = {};
    let isInitialized = false;
    let orderMap = null;

    // تنظیمات پیش‌فرض
    const settings = {
        searchDelay: 500,
        clearDelay: 300,
        notificationTimeout: 5000,
        ajaxTimeout: 30000
    };

    // مخفی کردن همه مودال‌ها در زمان بارگذاری صفحه
    $(window).on('load', function() {
        // مطمئن شویم که همه مودال‌ها مخفی هستند
        $('.orders-modal').hide();
        $('#order-edit-modal').hide();
        $('#order-details-modal').hide();
        console.log('Window loaded: hiding all modals');
    });

    $(document).ready(function() {
        console.log('Market Google Orders script loaded');
        console.log('jQuery version:', $.fn.jquery);
        console.log('market_google_orders_params available:', typeof market_google_orders_params !== 'undefined');
        console.log('ajaxurl available:', typeof ajaxurl !== 'undefined');
        if (typeof market_google_orders_params !== 'undefined') {
            console.log('market_google_orders_params:', market_google_orders_params);
        }
        
        // مخفی کردن مودال‌ها به صورت پیش‌فرض
        $('.orders-modal').hide();
        $('#order-edit-modal').hide();
        $('#order-details-modal').hide();
        
        // تأخیر کوتاه برای اطمینان از بارگذاری کامل DOM
        setTimeout(function() {
            console.log('Filter buttons found:', $('.button.button-primary[value="جستجو و فیلتر"]').length);
            console.log('Clear buttons found:', $('a.button:contains("پاک کردن فیلترها")').length);
            initializeOrdersList();
        }, 100);
    });

    function initializeOrdersList() {
        if (isInitialized) return;
        
        console.log('Initializing orders list...');
        
        try {
            // بررسی وجود عناصر ضروری
            if (!checkRequiredElements()) {
                console.warn('Required elements not found, retrying in 1 second...');
                setTimeout(initializeOrdersList, 1000);
                return;
            }

            // مخفی کردن همه مودال‌ها در ابتدا
            $('.orders-modal').hide();

            // اضافه کردن استایل‌های اضافی
            addCustomStyles();
            
            // فعال‌سازی جستجوی زنده و فیلترها
            initLiveSearch();
            
            // راه‌اندازی autocomplete
            setupSearchAutocomplete();
            
            // اضافه کردن تقویم جلالی (با تأخیر برای اطمینان از بارگذاری کتابخانه)
            setTimeout(() => {
                setupJalaliDatepickers();
            }, 500);
            
            // کپی مختصات
            bindCopyCoordinates();
            
            // مشاهده جزئیات سفارش
            bindViewOrder();
            
            // ویرایش سفارش
            bindEditOrder();
            
            // حذف سفارش
            bindDeleteOrder();
            
            // تکمیل سفارش
            bindCompleteOrder();
            
            // Toggle read status
            bindToggleReadStatus();
            
            // Toggle read status از داخل مودال
            bindReadStatusFromModal();
            
            // ارسال پیامک اطلاعات
            bindSendInfoSMS();
            
            // مودال‌ها
            bindModalEvents();
            
            // ذخیره تغییرات
            bindSaveOrder();
            
            // تایید عملیات‌ها
            bindConfirmActions();
            
            // تغییر وضعیت سفارش و پرداخت از داخل مودال
            bindStatusChanges();
            
            // اضافه کردن دکمه پاک کردن فیلترها
            addClearFiltersButton();
            
            isInitialized = true;
            console.log('Orders list initialized successfully');
            
        } catch (error) {
            console.error('Error initializing orders list:', error);
        }
    }

    // بررسی وجود عناصر ضروری
    function checkRequiredElements() {
        const requiredSelectors = [
            '.orders-table-container, .orders-list-wrap',
            'input[name="s"], .search-box input[type="search"]'
        ];
        
        for (let selector of requiredSelectors) {
            if ($(selector).length === 0) {
                console.warn('Required element not found:', selector);
                return false;
            }
        }
        
        return true;
    }

    // اضافه کردن دکمه پاک کردن فیلترها
    function addClearFiltersButton() {
        if ($('.clear-filters-btn').length > 0) return;
        
        const $searchForm = $('.search-box, .tablenav.top');
        if ($searchForm.length > 0) {
            const $clearBtn = $('<button type="button" class="button clear-filters-btn">پاک کردن فیلترها</button>');
            $searchForm.append($clearBtn);
        }
    }

    // فعال‌سازی جستجوی زنده و فیلترهای خودکار
    function initLiveSearch() {
        console.log('Initializing live search...');
        setupEventListeners();
    }
    
    // راه‌اندازی autocomplete برای جستجو
    function setupSearchAutocomplete() {
        const searchSelectors = 'input[name="s"], .search-box input[type="search"], #post-search-input';
        
        $(searchSelectors).each(function() {
            const $input = $(this);
            
            // بررسی وجود jQuery UI autocomplete
            if (typeof $input.autocomplete === 'function') {
                $input.autocomplete({
                    source: function(request, response) {
                        // حداقل 2 کاراکتر برای شروع جستجو
                        if (request.term.length < 2) {
                            response([]);
                            return;
                        }
                        
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'market_google_autocomplete_orders',
                                term: request.term,
                                security: market_google_orders_params.security
                            },
                            success: function(data) {
                                if (data.success && data.data) {
                                    response(data.data);
                                } else {
                                    response([]);
                                }
                            },
                            error: function() {
                                response([]);
                            }
                        });
                    },
                    minLength: 2,
                    delay: 300,
                    select: function(event, ui) {
                        $(this).val(ui.item.value);
            performLiveSearch();
                        return false;
                    },
                    focus: function(event, ui) {
                        $(this).val(ui.item.value);
                        return false;
                    }
                });
            }
        });
    }
        
    // اضافه کردن رویدادهای لازم
    function setupEventListeners() {
        // رویداد تغییر برای فیلترهای select - بهینه‌سازی شده
        $(document).off('change.ordersFilter').on('change.ordersFilter', 'select[name="order_status"], select[name="payment_status"], select[name="per_page"]', function() {
            const fieldName = $(this).attr('name');
            const fieldValue = $(this).val();
            console.log('Filter changed:', fieldName, '=', fieldValue);
            performLiveSearch();
        });
        
        // رویداد تغییر برای فیلدهای تاریخ - با selector های گسترده‌تر
        $(document).off('change.ordersDate').on('change.ordersDate', 'input[name="date_from"], input[name="date_to"], input#date_from, input#date_to, .jalali-datepicker', function() {
            const fieldName = $(this).attr('name') || $(this).attr('id');
            const fieldValue = $(this).val();
            console.log('📅 Date field changed:', fieldName, '=', fieldValue);
            console.log('📅 Element details:', {
                tagName: this.tagName,
                name: this.name,
                id: this.id,
                className: this.className,
                value: this.value
            });
            if (validateDateRange()) {
                console.log('📅 Date range valid, performing search...');
                performLiveSearch();
            } else {
                console.log('❌ Date range validation failed');
            }
        });
        
        // جستجوی فوری با debounce
        const searchSelectors = 'input[name="s"], .search-box input[type="search"], #post-search-input';
        $(document).off('input.ordersSearch').on('input.ordersSearch', searchSelectors, function() {
            const searchTerm = $(this).val();
            console.log('Search input changed:', searchTerm);
            
            clearTimeout(searchTimeout);
            
            // اگر طول جستجو کمتر از 2 کاراکتر است، فیلتر را پاک کن
            if (searchTerm.length === 0) {
            searchTimeout = setTimeout(performLiveSearch, 300);
            } else if (searchTerm.length >= 2) {
                // جستجوی زنده برای عبارات با حداقل 2 کاراکتر
                searchTimeout = setTimeout(performLiveSearch, 500);
            }
        });
        
        // دکمه پاک کردن فیلترها - selector بهتر
        $(document).off('click.clearFilters').on('click.clearFilters', 'a.button[href*="market-google-orders-list"], .clear-filters-btn, .clear-filters, a.button:contains("پاک کردن فیلترها")', function(e) {
            e.preventDefault();
            console.log('Clear filters clicked');
            clearAllFilters();
        });
        
        // مدیریت submit فرم
        $(document).off('submit.ordersForm').on('submit.ordersForm', '.orders-search-form, form, .search-box form, #posts-filter', function(e) {
            e.preventDefault();
            console.log('Form submitted, performing search...');
            performLiveSearch();
        });
        
        // pagination links
        $(document).off('click.ordersPagination').on('click.ordersPagination', '.tablenav-pages a, .pagination-links a', function(e) {
            e.preventDefault();
            const href = $(this).attr('href');
            if (href) {
                try {
                    const url = new URL(href, window.location.origin);
                    const paged = url.searchParams.get('paged') || $(this).data('page') || 1;
                    console.log('Pagination clicked, page:', paged);
                    performLiveSearch(paged);
                } catch (error) {
                    console.error('Error parsing pagination URL:', error);
                }
            } else {
                const page = $(this).data('page') || 1;
                console.log('Pagination clicked with data-page, page:', page);
                performLiveSearch(page);
            }
        });
        
        // دکمه جستجو و فیلتر - selector دقیق‌تر
        $(document).off('click.ordersSearchBtn').on('click.ordersSearchBtn', 'input[type="submit"][value="جستجو و فیلتر"], .search-submit, input[type="submit"][value*="جستجو"], .button-primary[value*="فیلتر"]', function(e) {
            e.preventDefault();
            console.log('Search/Filter button clicked');
            performLiveSearch();
        });
        
        // نمایش دکمه‌های فیلتر
        $('.button.button-primary[value="جستجو و فیلتر"]').show();
        $('a.button:contains("پاک کردن فیلترها")').show();
        
        // تست مستقیم دکمه‌ها
        setTimeout(function() {
            const $filterBtn = $('input[type="submit"][value="جستجو و فیلتر"]');
            const $clearBtn = $('a.button:contains("پاک کردن فیلترها")');
            const $completeButtons = $('.complete-order');
            
            console.log('🔍 Testing buttons:', {
                filterButton: $filterBtn.length,
                clearButton: $clearBtn.length,
                completeButtons: $completeButtons.length,
                ajaxUrl: typeof market_google_orders_params !== 'undefined' ? market_google_orders_params.ajax_url : 'undefined',
                nonce: typeof market_google_orders_params !== 'undefined' ? 'Present' : 'Missing'
            });
            
            // تست Ajax ساده
            if (typeof market_google_orders_params !== 'undefined') {
                console.log('✅ Ajax parameters are loaded correctly');
            } else {
                console.error('❌ Ajax parameters are missing!');
            }
            
            // اضافه کردن event listener مستقیم
            $filterBtn.off('click.directTest').on('click.directTest', function(e) {
                e.preventDefault();
                console.log('DIRECT FILTER BUTTON CLICKED!');
                performLiveSearch();
            });
            
            $clearBtn.off('click.directTest').on('click.directTest', function(e) {
                e.preventDefault();
                console.log('DIRECT CLEAR BUTTON CLICKED!');
                clearAllFilters();
            });
        }, 500);
        
        console.log('Event listeners setup completed');
    }

    // اعتبارسنجی بازه تاریخ
    function validateDateRange() {
        const $dateFrom = $('input[name="date_from"], input[type="date"]:first, .jalali-datepicker:first');
        const $dateTo = $('input[name="date_to"], input[type="date"]:last, .jalali-datepicker:last');
        
        const dateFrom = $dateFrom.val();
        const dateTo = $dateTo.val();
        
        if (dateFrom && dateTo) {
            try {
                // برای تاریخ‌های جلالی با فرمت yyyy/mm/dd
                if (dateFrom.match(/^\d{4}\/\d{1,2}\/\d{1,2}$/) && dateTo.match(/^\d{4}\/\d{1,2}\/\d{1,2}$/)) {
                    const [fromYear, fromMonth, fromDay] = dateFrom.split('/').map(Number);
                    const [toYear, toMonth, toDay] = dateTo.split('/').map(Number);
                    
                    // مقایسه تاریخ‌های جلالی
                    if (fromYear > toYear || 
                        (fromYear === toYear && fromMonth > toMonth) || 
                        (fromYear === toYear && fromMonth === toMonth && fromDay > toDay)) {
                        showNotification('تاریخ شروع نمی‌تواند بعد از تاریخ پایان باشد', 'error');
                        $dateTo.val('');
                        return false;
                    }
                } else {
                    // برای تاریخ‌های میلادی
                    const fromDate = new Date(dateFrom);
                    const toDate = new Date(dateTo);
                    
                    if (fromDate > toDate) {
                        showNotification('تاریخ شروع نمی‌تواند بعد از تاریخ پایان باشد', 'error');
                        $dateTo.val('');
                        return false;
                    }
                }
            } catch (error) {
                console.error('Error validating date range:', error);
            }
        }
        return true;
    }

    // جمع‌آوری داده‌های فیلتر از تمام منابع ممکن
    function collectFilterData() {
        const data = {
            s: '',
            order_status: '',
            payment_status: '',
            date_from: '',
            date_to: '',
            per_page: 20
        };
        
        // جستجوی متن
        const $searchInput = $('input[name="s"]');
        if ($searchInput.length && $searchInput.val()) {
            data.s = $searchInput.val().trim();
        }
        
        // وضعیت سفارش
        const $orderStatusSelect = $('select[name="order_status"]');
        if ($orderStatusSelect.length) {
            data.order_status = $orderStatusSelect.val() || '';
        }
        
        // وضعیت پرداخت - اولویت اصلی
        const $paymentStatusSelect = $('select[name="payment_status"]');
        if ($paymentStatusSelect.length) {
            data.payment_status = $paymentStatusSelect.val() || '';
            console.log('Payment status collected:', data.payment_status);
        }
        
        // تاریخ از - با selector های مختلف
        const $dateFromInput = $('input[name="date_from"], input#date_from, .jalali-datepicker:first');
        if ($dateFromInput.length && $dateFromInput.val()) {
            data.date_from = $dateFromInput.val().trim();
            console.log('📅 Date FROM collected:', data.date_from, 'from selector:', $dateFromInput.attr('name') || $dateFromInput.attr('id') || $dateFromInput.attr('class'));
        } else {
            console.log('❌ Date FROM not found or empty. Available date inputs:', $('input[type="text"], .jalali-datepicker').map(function() { return this.name || this.id || this.className; }).get());
        }
        
        // تاریخ تا - با selector های مختلف  
        const $dateToInput = $('input[name="date_to"], input#date_to, .jalali-datepicker:last');
        if ($dateToInput.length && $dateToInput.val()) {
            data.date_to = $dateToInput.val().trim();
            console.log('📅 Date TO collected:', data.date_to, 'from selector:', $dateToInput.attr('name') || $dateToInput.attr('id') || $dateToInput.attr('class'));
        } else {
            console.log('❌ Date TO not found or empty.');
            console.log('   Found elements:', $dateToInput.length);
            console.log('   Exact values:', $('input[name="date_to"], input#date_to').map(function() { 
                return {
                    name: this.name, 
                    id: this.id, 
                    value: this.value,
                    selector: this.name ? '[name="' + this.name + '"]' : '[id="' + this.id + '"]'
                }; 
            }).get());
            console.log('   All date inputs:', $('input[type="text"], .jalali-datepicker').map(function() { return {name: this.name, id: this.id, value: this.value, class: this.className}; }).get());
        }
        
        // تعداد در صفحه - چک کردن selector های مختلف
        const $perPageSelect = $('select[name="per_page"], #per-page-selector, select.per-page-select');
        if ($perPageSelect.length && $perPageSelect.val()) {
            data.per_page = parseInt($perPageSelect.val()) || 20;
        }
        
        // اگر هنوز پیدا نشد، بررسی input هم بکن
        if (data.per_page === 20) {
            const $perPageInput = $('input[name="per_page"]');
            if ($perPageInput.length && $perPageInput.val()) {
                data.per_page = parseInt($perPageInput.val()) || 20;
            }
        }
        
        console.log('Collected filter data:', data);
        return data;
    }

    // انجام جستجوی زنده
    function performLiveSearch(page = 1) {
        if (isLoading) return;
        
        console.log('🔍 Starting performLiveSearch, page:', page);
        
        // جمع‌آوری داده‌های فیلتر
        const filterData = collectFilterData();
        filterData.paged = page;
        filterData.action = 'market_google_search_orders';
        
        // تست مستقیم نمایش همه input ها
        console.log('🔍 Testing all inputs on page:');
        $('input[type="text"], input[type="search"], .jalali-datepicker').each(function() {
            console.log('   Input:', {
                selector: this.tagName + (this.name ? '[name="' + this.name + '"]' : '') + (this.id ? '[id="' + this.id + '"]' : '') + (this.className ? '[class="' + this.className + '"]' : ''),
                name: this.name,
                id: this.id,
                className: this.className,
                value: this.value
            });
        });
        
        // استفاده از nonce صحیح
        if (typeof market_google_orders_params !== 'undefined' && market_google_orders_params.security) {
            filterData.security = market_google_orders_params.security;
            console.log('✅ Using nonce from market_google_orders_params:', filterData.security);
        } else {
            // جستجوی nonce در صفحه
            const nonceElement = document.querySelector('#market_google_orders_nonce, input[name="market_google_orders_nonce"]');
            if (nonceElement) {
                filterData.security = nonceElement.value;
                console.log('✅ Using nonce from DOM element:', filterData.security);
            } else {
                console.error('❌ No nonce found!');
                showNotification('خطا: نتوان nonce را یافت', 'error');
                return;
            }
        }
        
        // استفاده از URL صحیح AJAX
        const ajaxUrl = (typeof ajaxurl !== 'undefined') ? ajaxurl : '/wp-admin/admin-ajax.php';
        
        console.log('📤 AJAX Data being sent:');
        console.log('   URL:', ajaxUrl);
        console.log('   Security token:', filterData.security);
        console.log('   📅 Date FROM:', filterData.date_from);
        console.log('   📅 Date TO:', filterData.date_to);
        console.log('   🔍 Search term:', filterData.s);
        console.log('   📊 Order status:', filterData.order_status);
        console.log('   💳 Payment status:', filterData.payment_status);
        console.log('   📄 Per page:', filterData.per_page);
        console.log('   📃 Page:', filterData.paged);
        console.log('   🔧 Action:', filterData.action);
        console.log('   📦 Full data object:', JSON.stringify(filterData, null, 2));
        
        isLoading = true;
        showLoadingIndicator(true);
        
        // ذخیره آخرین جستجو
        lastSearchData = filterData;
        
        console.log('🚀 Starting AJAX request...');
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: filterData,
            dataType: 'json',
            timeout: settings.ajaxTimeout,
            beforeSend: function() {
                console.log('📡 AJAX request sent to server');
            },
            success: function(response) {
                console.log('✅ Search response received:', response);
                console.log('Response type:', typeof response);
                
                // اگر response یک string است، تلاش برای parse کن
                if (typeof response === 'string') {
                    try {
                        response = JSON.parse(response);
                    } catch (e) {
                        console.error('❌ Failed to parse response:', e);
                        console.error('Raw response:', response);
                        showNotification('خطا در تجزیه پاسخ سرور', 'error');
                        return;
                    }
                }
                
                if (response && response.success) {
                    console.log('✅ Search successful, updating table...');
                    console.log('Total items found:', response.data.total_items);
                    console.log('HTML length:', response.data.html ? response.data.html.length : 0);
                    
                    // جایگزین کردن محتوای container بدون تغییر خود container
                    const $container = $('.orders-table-container');
                    if ($container.length > 0) {
                        $container.html(response.data.html);
                    } else {
                        // اگر container وجود نداشت، کل صفحه را جایگزین کن
                        $('.orders-list-wrap').html(response.data.html);
                    }
                    
                    // بروزرسانی pagination جداگانه
                    updatePagination(response.data.current_page || page, response.data.total_pages || 1, response.data.total_items || 0);
                    updateResultsCount(response.data.total_items || 0);
                    
                    // بایند مجدد رویدادها
                    bindCopyCoordinates();
                    bindViewOrder();
                    bindEditOrder();
                    bindDeleteOrder();
                    bindCompleteOrder();
                    bindToggleReadStatus();
                    bindSendInfoSMS();
                    
                    showNotification(`${response.data.total_items || 0} سفارش یافت شد`, 'success');
                } else {
                    const errorMessage = response && response.data ? response.data : 'خطای نامشخص در جستجو';
                    showNotification(errorMessage, 'error');
                    console.error('❌ Search failed:', response);
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ AJAX error details:');
                console.error('   Status:', status);
                console.error('   Error:', error);
                console.error('   Response text:', xhr.responseText);
                console.error('   Status code:', xhr.status);
                console.error('   Ready state:', xhr.readyState);
                showNotification('خطا در ارتباط با سرور: ' + error, 'error');
            },
            complete: function() {
                isLoading = false;
                showLoadingIndicator(false);
                console.log('🏁 AJAX request completed');
            }
        });
    }

    // نمایش/مخفی کردن نشانگر بارگذاری
    function showLoadingIndicator(show) {
        let $indicator = $('.search-loading-indicator');
        
        if (show) {
            if ($indicator.length === 0) {
                $indicator = $('<div class="search-loading-indicator">در حال جستجو...</div>');
                $('.orders-list-wrap h1, .wrap h1').first().after($indicator);
            }
            $indicator.show();
        } else {
            $indicator.hide();
        }
    }

    // بروزرسانی تعداد نتایج
    function updateResultsCount(count) {
        // این پیام آبی رو نمایش نمی‌دهیم - فقط پیام سبز نمایش داده می‌شود
        // پیام سبز در performLiveSearch نمایش داده می‌شود
    }

    // بروزرسانی pagination بدون ایجاد المان‌های تکراری
    function updatePagination(currentPage, totalPages, totalItems) {
        console.log('🔄 Updating pagination:', { currentPage, totalPages, totalItems });
        
        // پیدا کردن container pagination
        let $paginationContainer = $('.orders-pagination .tablenav-pages');
        
        // اگر pagination container وجود نداشت، آن را ایجاد کن
        if ($paginationContainer.length === 0) {
            const $ordersWrap = $('.orders-list-wrap');
            if ($ordersWrap.length > 0) {
                const paginationHtml = `
                    <div class="orders-pagination">
                        <div class="tablenav-pages"></div>
                    </div>
                `;
                $ordersWrap.append(paginationHtml);
                $paginationContainer = $('.orders-pagination .tablenav-pages');
            }
        }
        
        // پاک کردن محتوای قبلی pagination
        $paginationContainer.empty();
        
        if (totalPages <= 1) {
            // اگر فقط یک صفحه داریم، pagination را مخفی کن
            $('.orders-pagination').hide();
            return;
        }
        
        // نمایش pagination
        $('.orders-pagination').show();
        
        // افزودن تعداد کل موارد
        $paginationContainer.append(`<span class="displaying-num">${totalItems.toLocaleString()} مورد</span>`);
        
        // افزودن لینک‌های pagination
        let paginationLinks = '';
        
        // لینک صفحه قبل
        if (currentPage > 1) {
            paginationLinks += `<a class="page-numbers" href="#" data-page="${currentPage - 1}">‹ قبلی</a>`;
        }
        
        // لینک‌های صفحات
        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, currentPage + 2);
        
        for (let i = startPage; i <= endPage; i++) {
            if (i === currentPage) {
                paginationLinks += `<span class="page-numbers current">${i}</span>`;
            } else {
                paginationLinks += `<a class="page-numbers" href="#" data-page="${i}">${i}</a>`;
            }
        }
        
        // لینک صفحه بعد
        if (currentPage < totalPages) {
            paginationLinks += `<a class="page-numbers" href="#" data-page="${currentPage + 1}">بعدی ›</a>`;
        }
        
        $paginationContainer.append(paginationLinks);
        
        console.log('✅ Pagination updated successfully');
    }

    // پاک کردن تمام فیلترها
    function clearAllFilters() {
        console.log('Clearing all filters...');
        
        // پاک کردن فیلد جستجو
        $('input[name="s"], .search-box input[type="search"], #post-search-input').val('');
        
        // پاک کردن فیلترهای select
        $('select[name="order_status"], select[name="payment_status"], #filter-by-order-status, #filter-by-payment-status').val('');
        
        // پاک کردن تاریخ‌ها
        $('input[name="date_from"], input[name="date_to"], input[type="date"], .jalali-datepicker').val('');
        
        // تنظیم مجدد تعداد در صفحه
        $('select[name="per_page"], #per-page-selector').val('20');
        
        // پاک کردن نتایج جستجو
        $('.results-counter').hide();
        
        // انجام جستجوی جدید
        setTimeout(function() {
            performLiveSearch();
        }, 100);
        
        showNotification('فیلترها پاک شد', 'success');
    }

    // کپی مختصات به کلیپ‌بورد
    function bindCopyCoordinates() {
        $(document).off('click.copyCoordinates').on('click.copyCoordinates', '.copy-coordinates, .copyable', function(e) {
            e.preventDefault();
            
            let text = '';
            
            // چک کردن آیا این یک المان کپی شونده در مودال است یا دکمه کپی مختصات در جدول
            if ($(this).hasClass('copyable')) {
                text = $(this).data('clipboard') || $(this).text();
            } else {
            const lat = $(this).data('lat');
            const lng = $(this).data('lng');
                text = lat + ', ' + lng;
            }
            
            if (!text) {
                console.error('No text to copy');
                return;
            }
            
            console.log('Copying to clipboard:', text);
            
            // استفاده از API کلیپ‌بورد
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text)
                    .then(() => {
                        showNotification('متن با موفقیت کپی شد: ' + text, 'success');
                    })
                    .catch(error => {
                        console.error('Error copying text:', error);
                        fallbackCopyToClipboard(text);
                });
            } else {
                fallbackCopyToClipboard(text);
            }
        });
    }

    // روش جایگزین برای کپی کردن
    function fallbackCopyToClipboard(text) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            document.execCommand('copy');
            showNotification('مختصات کپی شد: ' + text, 'success');
        } catch (err) {
            showNotification('خطا در کپی کردن مختصات', 'error');
        }
        
        document.body.removeChild(textArea);
    }

    // مشاهده جزئیات سفارش
    function bindViewOrder() {
        $(document).off('click.viewOrder').on('click.viewOrder', '.view-order', function(e) {
            e.preventDefault();
            
            if (isLoading) return;
            
            const orderId = $(this).data('id');
            if (!orderId) {
                showNotification('شناسه سفارش یافت نشد', 'error');
                return;
            }
            
            currentOrderId = orderId;
            loadOrderDetails(orderId);
        });
    }

    // بارگذاری جزئیات سفارش
    function loadOrderDetails(orderId) {
        isLoading = true;
        
        const $modal = $('#order-details-modal');
        const $body = $('#order-details-body');
        
        if ($modal.length === 0 || $body.length === 0) {
            showNotification('مودال جزئیات سفارش یافت نشد', 'error');
            isLoading = false;
            return;
        }
        
        // مخفی کردن سایر مودال‌ها
        $('.orders-modal').not($modal).hide();
        
        $body.html('<div class="loading-spinner">در حال بارگذاری...</div>');
        $modal.fadeIn(250);
        
        // مرکز کردن مودال در صفحه
        centerModalOnScreen();
        
        const ajaxUrl = (typeof market_google_orders_params !== 'undefined' && market_google_orders_params.ajax_url) 
            ? market_google_orders_params.ajax_url 
            : (typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php');
            
        const nonce = (typeof market_google_orders_params !== 'undefined' && market_google_orders_params.security) 
            ? market_google_orders_params.security 
            : $('input[name="market_google_orders_nonce"]').val() || '';
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_order_details',
                order_id: orderId,
                security: nonce
            },
            success: function(response) {
                if (response && response.success) {
                    $body.html(response.data.html).fadeIn();
                    
                    // اتوماتیک mark کردن به عنوان خوانده شده
                    markOrderAsRead(orderId);
                    
                    // بارگذاری نقشه
                    setTimeout(function() {
                        initOrderMap();
                    }, 200);
                    
                    // اضافه کردن رویدادهای کپی متن
                    bindCopyableElements();
                    
                    // مرکز کردن مودال در صفحه بعد از بارگذاری محتوا
                    setTimeout(function() {
                        centerModalOnScreen();
                    }, 300);
                } else {
                    $body.html('<div class="error-message">خطا در بارگذاری اطلاعات: ' + (response.data || 'خطای نامشخص') + '</div>');
                }
            },
            error: function() {
                $body.html('<div class="error-message">خطا در ارتباط با سرور</div>');
            },
            complete: function() {
                isLoading = false;
            }
        });
    }
    
    // مقداردهی نقشه در مودال
    function initOrderMap() {
        const mapElement = document.getElementById('order-map');
        if (!mapElement) return;
        
        const lat = parseFloat(mapElement.dataset.lat);
        const lng = parseFloat(mapElement.dataset.lng);
        
        if (isNaN(lat) || isNaN(lng)) return;
        
        // اگر نقشه قبلاً وجود دارد، حذف کن
        if (orderMap) {
            orderMap.remove();
        }
        
        // ایجاد نقشه جدید با کنترل‌های محدود
        orderMap = L.map('order-map', {
            center: [lat, lng],
            zoom: 15,
            zoomControl: true, // دکمه‌های + و -
            scrollWheelZoom: true, // فعال کردن زوم با اسکرول موس
            doubleClickZoom: true, // فعال کردن زوم با دابل کلیک
            boxZoom: true, // فعال کردن زوم با کادر
            keyboard: true, // فعال کردن کنترل با کیبورد
            dragging: true, // فعال کردن کشیدن نقشه
            touchZoom: true, // فعال کردن زوم لمسی
            tap: true // فعال کردن tap
        });
        
        // اضافه کردن لایه نقشه
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(orderMap);
        
        // اضافه کردن نشانگر
        L.marker([lat, lng]).addTo(orderMap)
            .bindPopup('موقعیت مکانی سفارش')
            .openPopup();
    }
    
    // اضافه کردن رویدادهای کپی به المان‌های قابل کپی
    function bindCopyableElements() {
        $('.copyable').off('click.copyable').on('click.copyable', function() {
            const text = $(this).data('clipboard') || $(this).text().trim();
            
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text)
                    .then(() => {
                        showNotification('متن کپی شد: ' + text, 'success');
                    })
                    .catch(err => {
                        fallbackCopyToClipboard(text);
                    });
            } else {
                fallbackCopyToClipboard(text);
            }
        });
    }

    // ویرایش سفارش
    function bindEditOrder() {
        $(document).off('click.editOrder').on('click.editOrder', '.edit-order', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const $row = $(this).closest('tr');
            const orderId = $row.data('id');

            if (!orderId) {
                alert('خطا: شناسه سفارش یافت نشد');
                return;
            }

            // استفاده از همان مودال جزئیات سفارش اما در حالت ویرایش
            $.ajax({
                url: marketGoogleAjax.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_order_details',
                    order_id: orderId,
                    edit_mode: true,
                    nonce: marketGoogleAjax.nonce
                },
                beforeSend: function() {
                    $('#order-modal .modal-content').html('<div style="text-align:center;padding:50px;"><div class="spinner is-active"></div><p>در حال بارگذاری...</p></div>');
                    $('#order-modal').show();
                },
                success: function(response) {
                    $('#order-modal .modal-content').html(response);
                    
                    // راه‌اندازی نقشه اگر موجود باشد
                    initOrderMap();
                    
                    // راه‌اندازی قابلیت کپی
                    initCopyFunctionality();
                    
                    // علامت‌گذاری سفارش به عنوان خوانده شده
                    markOrderAsRead(orderId);
                },
                error: function() {
                    $('#order-modal .modal-content').html('<div style="text-align:center;padding:50px;color:red;">خطا در بارگذاری اطلاعات</div>');
                }
            });
        });
    }

    // ذخیره تغییرات سفارش
    function bindSaveOrder() {
        $(document).off('click.saveOrder').on('click.saveOrder', '.save-order', function(e) {
            e.preventDefault();
            
            if (isLoading || !currentOrderId) return;
            
            const $form = $('#order-edit-form');
            if ($form.length === 0) {
                showNotification('فرم ویرایش یافت نشد', 'error');
                return;
            }
            
            const formData = $form.serialize();
            saveOrderChanges(currentOrderId, formData);
        });
    }

    // ذخیره تغییرات در سرور
    function saveOrderChanges(orderId, formData) {
        isLoading = true;
        
        const $saveButton = $('.save-order');
        const originalText = $saveButton.text();
        
        $saveButton.prop('disabled', true).text('در حال ذخیره...');
        
        const ajaxUrl = (typeof market_google_orders_params !== 'undefined' && market_google_orders_params.ajax_url) 
            ? market_google_orders_params.ajax_url 
            : (typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php');
            
        const nonce = (typeof market_google_orders_params !== 'undefined' && market_google_orders_params.security) 
            ? market_google_orders_params.security 
            : $('input[name="market_google_orders_nonce"]').val() || '';
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'update_order',
                order_id: orderId,
                security: nonce,
                form_data: formData
            },
            success: function(response) {
                if (response && response.success) {
                    showNotification('تغییرات با موفقیت ذخیره شد', 'success');
                    $('#order-edit-modal').hide();
                    
                    // بروزرسانی جدول بدون reload
                    setTimeout(function() {
                        performLiveSearch();
                    }, 500);
                } else {
                    showNotification('خطا در ذخیره تغییرات: ' + (response.data || 'خطای نامشخص'), 'error');
                }
            },
            error: function() {
                showNotification('خطا در ارتباط با سرور', 'error');
            },
            complete: function() {
                isLoading = false;
                $saveButton.prop('disabled', false).text(originalText);
            }
        });
    }

    // حذف سفارش
    function bindDeleteOrder() {
        $(document).off('click.deleteOrder').on('click.deleteOrder', '.delete-order', function(e) {
            e.preventDefault();
            
            const orderId = $(this).data('id');
            const $row = $(this).closest('tr');
            
            if (confirm('آیا مطمئن هستید که می‌خواهید این سفارش را حذف کنید؟\n\nاین عمل قابل بازگشت نیست.')) {
                deleteOrder(orderId, $row);
            }
        });
    }

    // حذف سفارش از سرور
    function deleteOrder(orderId, $row) {
        isLoading = true;
        
        $row.addClass('loading');
        
        const ajaxUrl = (typeof market_google_orders_params !== 'undefined' && market_google_orders_params.ajax_url) 
            ? market_google_orders_params.ajax_url 
            : (typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php');
            
        const nonce = (typeof market_google_orders_params !== 'undefined' && market_google_orders_params.security) 
            ? market_google_orders_params.security 
            : $('input[name="market_google_orders_nonce"]').val() || '';
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'market_google_delete_order',
                order_id: orderId,
                security: nonce
            },
            success: function(response) {
                if (response && response.success) {
                    $row.fadeOut(300, function() {
                        $(this).remove();
                        
                        // بررسی اینکه آیا ردیف دیگری باقی مانده یا نه
                        if ($('.orders-table tbody tr:visible, .wp-list-table tbody tr:visible').length === 0) {
                            $('.orders-table tbody, .wp-list-table tbody').html('<tr><td colspan="11" class="no-items">موردی یافت نشد.</td></tr>');
                        }
                        
                        // بروزرسانی شمارنده نتایج
                        const currentCount = parseInt($('.results-counter span').text().replace(/[^0-9]/g, '')) || 0;
                        updateResultsCount(Math.max(0, currentCount - 1));
                    });
                    
                    showNotification('سفارش با موفقیت حذف شد', 'success');
                } else {
                    showNotification('خطا در حذف سفارش: ' + (response.data || 'خطای نامشخص'), 'error');
                    $row.removeClass('loading');
                }
            },
            error: function() {
                showNotification('خطا در ارتباط با سرور', 'error');
                $row.removeClass('loading');
            },
            complete: function() {
                isLoading = false;
            }
        });
    }

    // تکمیل/برگرداندن سفارش
    function bindCompleteOrder() {
        // حذف event listener قبلی
        $(document).off('click.completeOrder click.uncompleteOrder');
        
        // اضافه کردن event listener برای دکمه تکمیل سفارش
        $(document).on('click.completeOrder', '.complete-order:not(.disabled):not(.loading)', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (isLoading) {
                alert('❌ لطفاً صبر کنید، درخواست قبلی در حال پردازش است...');
                return;
            }
            
            const orderId = $(this).data('id');
            const $row = $(this).closest('tr');
            const isCompleted = $row.hasClass('order-completed');
            
            console.log('🎯 Complete button clicked:', {
                orderId: orderId,
                isCompleted: isCompleted,
                buttonClasses: $(this).attr('class'),
                hasAjaxParams: typeof market_google_orders_params !== 'undefined',
                ajaxUrl: typeof market_google_orders_params !== 'undefined' ? market_google_orders_params.ajax_url : 'Missing'
            });
            
            // تست Ajax parameters
            if (typeof market_google_orders_params === 'undefined') {
                alert('❌ خطا: تنظیمات Ajax موجود نیست! لطفاً صفحه را refresh کنید');
                return;
            }
            
            const confirmMessage = isCompleted 
                ? 'آیا مطمئن هستید که می‌خواهید این سفارش را به حالت "در انتظار انجام" برگردانید؟'
                : 'آیا مطمئن هستید که می‌خواهید این سفارش را تکمیل کنید؟';
            
            if (confirm(confirmMessage)) {
                toggleOrderStatus(orderId, $(this), !isCompleted);
            }
        });
        
        console.log('✅ Complete order events bound successfully');
    }

    // تغییر وضعیت سفارش (تکمیل/برگرداندن)
    function toggleOrderStatus(orderId, $button, toCompleted) {
        isLoading = true;
        
        const $icon = $button.find('.dashicons');
        const $row = $button.closest('tr');
        
        // غیرفعال کردن دکمه موقتاً
        $button.prop('disabled', true);
        
        const ajaxUrl = (typeof market_google_orders_params !== 'undefined' && market_google_orders_params.ajax_url) 
            ? market_google_orders_params.ajax_url 
            : (typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php');
            
        const nonce = (typeof market_google_orders_params !== 'undefined' && market_google_orders_params.security) 
            ? market_google_orders_params.security 
            : ($('input[name="market_google_orders_nonce"]').val() || $('input[name="_wpnonce"]').val() || '');
        
        const action = toCompleted ? 'market_google_complete_order' : 'market_google_uncomplete_order';
        
        console.log('Ajax request details:', {
            url: ajaxUrl,
            action: action,
            orderId: orderId,
            nonce: nonce ? 'Present' : 'Missing',
            toCompleted: toCompleted
        });
        
        console.log('📡 Sending Ajax request:', {
            url: ajaxUrl,
            action: action,
            orderId: orderId,
            nonce: nonce ? 'Present' : 'Missing'
        });
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: action,
                order_id: orderId,
                security: nonce
            },
            beforeSend: function() {
                console.log('🚀 Ajax request started');
            },
            success: function(response) {
                console.log('✅ Ajax Success Response:', response);
                if (response && response.success) {
                    if (toCompleted) {
                        // تغییر به وضعیت تکمیل شده
                        $row.addClass('order-completed');
                        
                        // تغییر وضعیت سفارش
                        const $orderStatus = $row.find('.order-status .status-badge');
                        $orderStatus.removeClass('order-pending').addClass('order-completed').text('تکمیل شده');
                        
                        // تغییر آیکون دکمه
                        $button.attr('title', 'برگرداندن به در انتظار انجام');
                        $button.find('.dashicons').removeClass('dashicons-yes').addClass('dashicons-undo');
                        
                        let message = 'سفارش با موفقیت تکمیل شد';
                        let notificationType = 'success';
                        
                        if (response.data && response.data.sms_sent) {
                            message += ' و پیامک ارسال شد';
                        } else if (response.data && response.data.sms_message) {
                            message += ' (خطا در ارسال پیامک: ' + response.data.sms_message + ')';
                            notificationType = 'warning'; // تغییر نوع notification به warning
                        }
                        
                        showNotification(message, notificationType);
                    } else {
                        // تغییر به وضعیت در انتظار انجام
                        $row.removeClass('order-completed');
                        
                        // تغییر وضعیت سفارش
                        const $orderStatus = $row.find('.order-status .status-badge');
                        $orderStatus.removeClass('order-completed').addClass('order-pending').text('در انتظار انجام');
                        
                        // تغییر آیکون دکمه
                        $button.attr('title', 'تکمیل سفارش');
                        $button.find('.dashicons').removeClass('dashicons-undo').addClass('dashicons-yes');
                        
                        showNotification('سفارش به حالت "در انتظار انجام" برگردانده شد', 'success');
                    }
                } else {
                    const errorMessage = toCompleted ? 'خطا در تکمیل سفارش' : 'خطا در برگرداندن سفارش';
                    showNotification(errorMessage + ': ' + (response.data || 'خطای نامشخص'), 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Ajax Error Details:', {
                    status: status,
                    error: error,
                    statusCode: xhr.status,
                    responseText: xhr.responseText,
                    readyState: xhr.readyState,
                    url: ajaxUrl,
                    action: action,
                    orderId: orderId,
                    nonce: nonce ? 'Present' : 'Missing'
                });
                
                let errorMessage = 'خطا در ارتباط با سرور';
                
                if (xhr.status === 0) {
                    errorMessage = 'خطا در اتصال به سرور - احتمالاً مشکل شبکه';
                } else if (xhr.status === 403) {
                    errorMessage = 'دسترسی مجاز نیست - لطفاً دوباره وارد شوید';
                } else if (xhr.status === 500) {
                    errorMessage = 'خطای داخلی سرور';
                } else if (xhr.responseText) {
                    try {
                        const errorData = JSON.parse(xhr.responseText);
                        if (errorData.data) {
                            errorMessage = errorData.data;
                        } else if (errorData.message) {
                            errorMessage = errorData.message;
                        }
                    } catch (e) {
                        // اگر response HTML باشد، سعی کن خطای PHP را پیدا کن
                        if (xhr.responseText.includes('Fatal error') || xhr.responseText.includes('Parse error')) {
                            errorMessage = 'خطای برنامه‌نویسی - لطفاً با مدیر سایت تماس بگیرید';
                        } else {
                            errorMessage += ': ' + xhr.responseText.substring(0, 200);
                        }
                    }
                }
                
                showNotification(errorMessage, 'error');
            },
            complete: function() {
                isLoading = false;
                $button.prop('disabled', false);
            }
        });
    }

    // Mark order as read (automatic)
    function markOrderAsRead(orderId) {
        const ajaxUrl = (typeof ajaxurl !== 'undefined') ? ajaxurl : '/wp-admin/admin-ajax.php';
        const nonce = (typeof market_google_orders_params !== 'undefined' && market_google_orders_params.security) 
            ? market_google_orders_params.security 
            : $('input[name="market_google_orders_nonce"]').val() || '';
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'market_google_mark_as_read',
                order_id: orderId,
                security: nonce
            },
            success: function(response) {
                if (response && response.success) {
                    // تغییر ظاهر row اگر در همان صفحه باشد
                    const $row = $('.orders-table tr').filter(function() {
                        return $(this).find('.view-order[data-id="' + orderId + '"]').length > 0;
                    });
                    
                    if ($row.length > 0) {
                        $row.removeClass('order-unread').addClass('order-read');
                        const $toggleBtn = $row.find('.toggle-read-status');
                        $toggleBtn.find('.dashicons').removeClass('dashicons-star-filled').addClass('dashicons-star-empty');
                        $toggleBtn.attr('title', 'علامت‌گذاری به عنوان خوانده نشده');
                    }
                }
            },
            error: function() {
                // Silent fail - not critical
                console.log('Failed to mark order as read');
            }
        });
    }

    // مدیریت toggle read status
    function bindToggleReadStatus() {
        $(document).off('click.toggleRead').on('click.toggleRead', '.toggle-read-status', function(e) {
            e.preventDefault();
            
            if (isLoading) return;
            
            const orderId = $(this).data('id');
            if (!orderId) {
                showNotification('شناسه سفارش یافت نشد', 'error');
                return;
            }
            
            const $button = $(this);
            const $row = $button.closest('tr');
            
            isLoading = true;
            
            const ajaxUrl = (typeof ajaxurl !== 'undefined') ? ajaxurl : '/wp-admin/admin-ajax.php';
            const nonce = (typeof market_google_orders_params !== 'undefined' && market_google_orders_params.security) 
                ? market_google_orders_params.security 
                : $('input[name="market_google_orders_nonce"]').val() || '';
            
            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'market_google_toggle_read_status',
                    order_id: orderId,
                    security: nonce
                },
                success: function(response) {
                    if (response && response.success) {
                        // تغییر کلاس row
                        if (response.data.new_status === 1) {
                            $row.removeClass('order-unread').addClass('order-read');
                            $button.find('.dashicons').removeClass('dashicons-star-filled').addClass('dashicons-star-empty');
                            $button.attr('title', 'علامت‌گذاری به عنوان خوانده نشده');
                        } else {
                            $row.removeClass('order-read').addClass('order-unread');
                            $button.find('.dashicons').removeClass('dashicons-star-empty').addClass('dashicons-star-filled');
                            $button.attr('title', 'علامت‌گذاری به عنوان خوانده شده');
                        }
                        
                        showNotification(response.data.message, 'success');
                    } else {
                        showNotification(response.data || 'خطا در تغییر وضعیت', 'error');
                    }
                },
                error: function() {
                    showNotification('خطا در ارتباط با سرور', 'error');
                },
                complete: function() {
                    isLoading = false;
                }
            });
        });
    }

    // مدیریت رویدادهای مودال
    function bindModalEvents() {
        // مخفی کردن همه مودال‌ها در ابتدا
        $('.orders-modal').hide();
        
        // بستن مودال با کلیک روی دکمه بستن
        $(document).off('click.modalClose').on('click.modalClose', '.modal-close', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $('.orders-modal').fadeOut(200);
            currentOrderId = null;
        });
        
        // بستن مودال با کلیک در فضای خالی
        $(document).off('click.modalBg').on('click.modalBg', '.orders-modal', function(e) {
            if ($(e.target).hasClass('orders-modal')) {
                $(this).fadeOut(200);
                currentOrderId = null;
            }
        });
        
        // بستن مودال با کلید ESC
        $(document).off('keydown.modalEsc').on('keydown.modalEsc', function(e) {
            if (e.keyCode === 27) { // ESC key
                $('.orders-modal').fadeOut(200);
                currentOrderId = null;
            }
        });
        
        // جلوگیری از بسته شدن مودال با کلیک روی محتوا
        $(document).off('click.modalContent').on('click.modalContent', '.modal-content', function(e) {
            e.stopPropagation();
        });
        
        // مرکز کردن مودال در صفحه
        $(window).off('resize.modalPosition').on('resize.modalPosition', function() {
            centerModalOnScreen();
        });

        // دکمه‌های جدید در فوتر مودال جزئیات
        // ارسال پیامک اطلاعات
        $(document).off('click.sendInfoSmsModal').on('click.sendInfoSmsModal', '.send-info-sms-modal', function(e) {
            e.preventDefault();
            if (!currentOrderId) return;
            
            const $orderDetails = $('#order-details-body');
            const phone = $orderDetails.find('.order-phone').text().trim() || $orderDetails.find('[data-field="phone"]').text().trim();
            const name = $orderDetails.find('.order-name').text().trim() || $orderDetails.find('[data-field="full_name"]').text().trim();
            const business = $orderDetails.find('.order-business').text().trim() || $orderDetails.find('[data-field="business_name"]').text().trim();
            
            // استفاده از تابع موجود برای ارسال پیامک
            const $btn = $(this);
            $btn.prop('disabled', true).text('در حال ارسال...');
            
            const ajaxUrl = (typeof ajaxurl !== 'undefined') ? ajaxurl : '/wp-admin/admin-ajax.php';
            const nonce = (typeof market_google_orders_params !== 'undefined' && market_google_orders_params.security) 
                ? market_google_orders_params.security 
                : $('input[name="market_google_orders_nonce"]').val() || '';
            
            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'market_google_send_location_info_sms',
                    order_id: currentOrderId,
                    phone: phone,
                    security: nonce
                },
                success: function(response) {
                    if (response && response.success) {
                        showNotification(response.data.message || 'پیامک با موفقیت ارسال شد', 'success');
                    } else {
                        showNotification('خطا در ارسال پیامک: ' + (response.data || 'خطای نامشخص'), 'error');
                    }
                },
                error: function() {
                    showNotification('خطا در ارتباط با سرور', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('ارسال پیامک اطلاعات');
                }
            });
        });
        
        // ویرایش سفارش
        $(document).off('click.editOrderModal').on('click.editOrderModal', '.edit-order-modal', function(e) {
            e.preventDefault();
            if (!currentOrderId) return;
            
            // بستن مودال فعلی
            $('#order-details-modal').fadeOut(200);
            
            // باز کردن مودال ویرایش
            loadOrderEditForm(currentOrderId);
        });
        
        // حذف سفارش
        $(document).off('click.deleteOrderModal').on('click.deleteOrderModal', '.delete-order-modal', function(e) {
            e.preventDefault();
            if (!currentOrderId) return;
            
            if (confirm('آیا مطمئن هستید که می‌خواهید این سفارش را حذف کنید؟\n\nاین عمل قابل بازگشت نیست.')) {
                // بستن مودال
                $('#order-details-modal').fadeOut(200);
                
                // حذف سفارش
                const $row = $('tr').filter(function() {
                    return $(this).find('.view-order[data-id="' + currentOrderId + '"]').length > 0;
                });
                
                deleteOrder(currentOrderId, $row);
            }
        });
    }
    
    // مرکز کردن مودال در صفحه
    function centerModalOnScreen() {
        const $modal = $('.orders-modal:visible');
        if ($modal.length === 0) return;
        
        const $content = $modal.find('.modal-content');
        if ($content.length === 0) return;
        
        const windowHeight = $(window).height();
        
        // تنظیم ارتفاع مودال به 80 درصد ارتفاع صفحه
        $content.css({
            'height': '80vh',
            'max-height': '80vh',
            'margin': '10vh auto'
        });
        
        // تنظیم استایل‌های مودال
        $modal.css({
            'display': 'flex',
            'align-items': 'center',
            'justify-content': 'center',
            'overflow-y': 'hidden'
        });
        
        // تنظیم اسکرول برای محتوای مودال
        const $modalContent = $modal.find('.order-details-content');
        if ($modalContent.length > 0) {
            $modalContent.css('overflow-y', 'auto');
        }
        
        // تنظیم هدر و فوتر به صورت ثابت
        const $header = $modal.find('.modal-header');
        const $footer = $modal.find('.modal-footer');
        
        if ($header.length > 0) {
            $header.css({
                'position': 'sticky',
                'top': '0',
                'z-index': '10',
                'background': 'linear-gradient(135deg, #3498db, #2980b9)',
                'color': 'white',
                'padding': '10px 15px',
                'border-bottom': '1px solid #e0e0e0',
                'display': 'flex',
                'justify-content': 'space-between',
                'align-items': 'center'
            });
            
            $header.find('.modal-close').css({
                'cursor': 'pointer',
                'font-size': '24px',
                'color': 'white'
            });
        }
        
        if ($footer.length > 0) {
            $footer.css({
                'position': 'sticky',
                'bottom': '0',
                'z-index': '10',
                'background': '#f9f9f9',
                'padding': '10px 15px',
                'border-top': '1px solid #e0e0e0',
                'text-align': 'left',
                'display': 'flex',
                'justify-content': 'flex-end',
                'gap': '10px'
            });
            
            // استایل دکمه‌های فوتر
            $footer.find('.button-primary').css({
                'background': '#3498db',
                'color': 'white',
                'border-color': '#2980b9'
            });
            
            $footer.find('.button-danger').css({
                'background': '#e74c3c',
                'color': 'white',
                'border-color': '#c0392b'
            });
        }
    }

    // تایید عملیات‌های مهم
    function bindConfirmActions() {
        // تایید تکمیل سفارش (لینک مستقیم)
        $(document).off('click.confirmComplete').on('click.confirmComplete', 'a[href*="action=complete"]', function(e) {
            if (!confirm('آیا مطمئن هستید که می‌خواهید این سفارش را تکمیل کنید؟')) {
                e.preventDefault();
            }
        });
        
        // تایید حذف سفارش (لینک مستقیم)
        $(document).off('click.confirmDelete').on('click.confirmDelete', 'a[href*="action=delete"]', function(e) {
            if (!confirm('آیا مطمئن هستید که می‌خواهید این سفارش را حذف کنید؟\n\nاین عمل قابل بازگشت نیست.')) {
                e.preventDefault();
            }
        });
    }

    // نمایش پیام‌های اطلاع‌رسانی
    function showNotification(message, type = 'info') {
        // حذف نوتیفیکیشن‌های قبلی
        $('.mg-notification').remove();
        
        // تعیین عنوان بر اساس نوع پیام
        let title = 'اطلاعات';
        if (type === 'success') {
            title = 'موفقیت';
        } else if (type === 'error') {
            title = 'خطا';
        } else if (type === 'warning') {
            title = 'هشدار';
        }
        
        // ساخت نوتیفیکیشن جدید
        const $notification = $(`
            <div class="mg-notification ${type}">
                <div class="mg-notification-content">
                    <div class="mg-notification-title">${title}</div>
                    <div class="mg-notification-message">${message}</div>
                </div>
                <span class="mg-notification-close">&times;</span>
            </div>
        `);
        
        // اضافه کردن به صفحه
        $('body').append($notification);
        
        // نمایش با انیمیشن
        setTimeout(() => {
            $notification.addClass('show');
        }, 10);
        
        // بستن با کلیک روی دکمه بستن
        $notification.find('.mg-notification-close').on('click', function() {
            $notification.removeClass('show');
            setTimeout(() => {
                $notification.remove();
            }, 300);
        });
        
        // حذف خودکار بعد از مدت زمان مشخص
        setTimeout(() => {
            $notification.removeClass('show');
            setTimeout(() => {
                $notification.remove();
            }, 300);
        }, 5000);
    }

    // تابع کمکی برای فرمت کردن اعداد
    function numberFormat(number) {
        if (typeof Intl !== 'undefined' && Intl.NumberFormat) {
            return new Intl.NumberFormat('fa-IR').format(number);
        }
        return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    // تابع کمکی برای تبدیل تاریخ
    function formatDate(dateString) {
        try {
            const date = new Date(dateString);
            if (typeof Intl !== 'undefined' && Intl.DateTimeFormat) {
                return date.toLocaleDateString('fa-IR');
            }
            return date.toLocaleDateString();
        } catch (error) {
            return dateString;
        }
    }

    // اضافه کردن استایل‌های اضافی
    function addCustomStyles() {
        if ($('#market-google-orders-styles').length > 0) return;
        
        const customCSS = `
            <style id="market-google-orders-styles">
            .loading-spinner {
                text-align: center;
                padding: 40px;
                color: #666;
                font-size: 14px;
            }
            
            .loading-spinner:before {
                content: "";
                display: inline-block;
                width: 20px;
                height: 20px;
                border: 2px solid #f3f3f3;
                border-top: 2px solid #0073aa;
                border-radius: 50%;
                animation: spin 1s linear infinite;
                margin-right: 10px;
                vertical-align: middle;
            }
            
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            
            .error-message {
                text-align: center;
                padding: 20px;
                color: #d63638;
            }
            
            /* مودال جزئیات سفارش - طراحی جدید */
            .orders-modal {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.7);
                z-index: 100000;
                display: none;
            }
            
            .modal-content {
                background: #fff;
                width: 90%;
                max-width: 900px;
                height: 80vh;
                margin: 10vh auto;
                border-radius: 8px;
                box-shadow: 0 5px 30px rgba(0, 0, 0, 0.3);
                position: relative;
                padding: 0;
                overflow: hidden;
                display: flex;
                flex-direction: column;
            }
            
            /* هدر مودال */
            .modal-header {
                background: linear-gradient(135deg, #1e40af, #3b82f6);
                color: white;
                padding: 15px 20px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                border-radius: 8px 8px 0 0;
                position: sticky;
                top: 0;
                z-index: 10;
            }
            
            .modal-header h2 {
                margin: 0;
                font-size: 18px;
                font-weight: 600;
                color: white;
            }
            
            .modal-close {
                width: 30px;
                height: 30px;
                background: rgba(255, 255, 255, 0.2);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                font-size: 20px;
                color: white;
                transition: all 0.2s ease;
            }
            
            .modal-close:hover {
                background: rgba(255, 255, 255, 0.3);
                transform: rotate(90deg);
            }
            
            /* محتوای مودال */
            .order-details-content {
                overflow-y: auto;
            }
            
            /* فوتر مودال */
            .modal-footer {
                background: #f8fafc;
                padding: 20px 20px;
                border-top: 1px solid #e2e8f0;
                display: flex;
                justify-content: center;
                gap: 10px;
                border-radius: 0 0 8px 8px;
                position: sticky;
                bottom: 0;
                z-index: 100;
            }
            
            /* دکمه‌های عملیات */
            .button-primary {
                background: #3b82f6 !important;
                border-color: #2563eb !important;
                color: white !important;
                padding: 10px 20px;
            }
            
            .button-primary:hover {
                background: #2563eb !important;
            }
            
            .button-danger {
                background: #ef4444 !important;
                border-color: #dc2626 !important;
                color: white !important;
                padding: 10px 20px;
            }
            
            .button-danger:hover {
                background: #dc2626 !important;
            }
            
            /* اطلاعات سفارش */
            .order-main-details {
                background: #f8fafc;
                padding: 15px;
                border-radius: 8px;
                border: 1px solid #e2e8f0;
                margin-bottom: 20px;
            }
            
            .order-main-details h3 {
                margin-top: 0;
                margin-bottom: 15px;
                color: #1e293b;
                font-size: 16px;
                border-bottom: 1px solid #e2e8f0;
                padding-bottom: 10px;
            }
            @media (min-width: 768px) {
                .order-main-details {
                    grid-template-columns: 1fr 1fr;
                }
            }
            
            .order-info-section {
                background: #f8fafc;
                padding: 15px;
                border-radius: 8px;
                border: 1px solid #e2e8f0;
            }
            
            .info-group {
                display: flex;
                margin-bottom: 10px;
                padding-bottom: 8px;
            }
            
            .info-label {
                font-weight: bold;
                min-width: 140px;
                color: #64748b;
            }
            
            .copyable {
                cursor: pointer;
                position: relative;
                padding-right: 20px;
                transition: all 0.2s ease;
            }
            
            .copyable:after {
                content: "📋";
                position: absolute;
                right: 0;
                top: 50%;
                transform: translateY(-50%);
                font-size: 12px;
                opacity: 0.5;
            }
            
            .copyable:hover {
                color: #3b82f6;
            }
            
            .copyable:hover:after {
                opacity: 1;
            }
            
            /* محصولات */
            .order-products-section {
                background: #f8fafc;
                padding: 15px;
                border-radius: 8px;
                border: 1px solid #e2e8f0;
                margin-bottom: 20px;
            }
            
            .order-products-section h3 {
                margin-top: 0;
                margin-bottom: 15px;
                color: #1e293b;
                font-size: 16px;
                border-bottom: 1px solid #e2e8f0;
                padding-bottom: 10px;
            }
            
            .product-item {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                padding: 10px;
                background: white;
                border-radius: 4px;
                margin-bottom: 8px;
                border: 1px solid #e2e8f0;
            }
            
            .product-name {
                flex: 1;
            }
            
            .package-contents {
                margin-top: 8px;
                font-size: 12px;
                color: #64748b;
            }
            
            .package-contents ul {
                margin: 0;
                padding-right: 20px;
            }
            
            .product-quantity {
                background: #f1f5f9;
                padding: 3px 8px;
                border-radius: 4px;
                    font-size: 12px;
                white-space: nowrap;
            }
            
            /* وضعیت‌ها */
            .status-badge {
                display: inline-block;
                padding: 3px 8px;
                border-radius: 4px;
                    font-size: 12px;
                font-weight: 500;
            }
            
            .status-badge.order-pending {
                background: #fef3c7;
                color: #92400e;
            }
            
            .status-badge.order-completed {
                background: #dcfce7;
                color: #166534;
            }
            
            .status-badge.payment-pending {
                background: #fef3c7;
                color: #92400e;
            }
            
            .status-badge.payment-success {
                background: #dcfce7;
                color: #166534;
            }
            
            .status-badge.payment-failed {
                background: #fee2e2;
                color: #b91c1c;
            }
            
            .status-badge.payment-cancelled {
                background: #f1f5f9;
                color: #475569;
            }
            
            .status-badge.read-yes {
                background: #dbeafe;
                color: #1e40af;
            }
            
            .status-badge.read-no {
                background: #f1f5f9;
                color: #475569;
            }
            
            /* نقشه */
            .map-container {
                grid-column: 1 / -1;
                margin-top: 20px;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                overflow: hidden;
            }
            
            /* نوتیفیکیشن */
            .mg-notification {
                position: fixed;
                top: 20px;
                left: 50%;
                transform: translateX(-50%) translateY(-100px);
                background: white;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                padding: 15px 20px;
                min-width: 300px;
                max-width: 500px;
                z-index: 100001;
                display: flex;
                align-items: flex-start;
                transition: all 0.3s ease;
            }
            
            .mg-notification.show {
                transform: translateX(-50%) translateY(0);
            }
            
            .mg-notification-content {
                flex: 1;
            }
            
            .mg-notification-title {
                font-weight: bold;
                margin-bottom: 5px;
            }
            
            .mg-notification-message {
                color: #64748b;
            }
            
            .mg-notification-close {
                cursor: pointer;
                font-size: 18px;
                line-height: 1;
                margin-right: 10px;
                color: #94a3b8;
            }
            
            .mg-notification.success {
                border-right: 4px solid #22c55e;
            }
            
            .mg-notification.success .mg-notification-title {
                color: #166534;
            }
            
            .mg-notification.error {
                border-right: 4px solid #ef4444;
            }
            
            .mg-notification.error .mg-notification-title {
                color: #b91c1c;
            }
            
            .mg-notification.info {
                border-right: 4px solid #3b82f6;
            }
            
            .mg-notification.info .mg-notification-title {
                color: #1e40af;
            }
            
            .mg-notification.warning {
                border-right: 4px solid #f59e0b;
            }
            
            .mg-notification.warning .mg-notification-title {
                color: #92400e;
            }
            </style>
        `;
        
        $('head').append(customCSS);
    }

    // اضافه کردن تقویم جلالی به فیلدهای تاریخ
    function setupJalaliDatepickers() {
        console.log('🗓️ Setting up Jalali datepickers...');
        
        // بررسی وجود کتابخانه JalaliCalendar
        if (typeof window.JalaliCalendar === 'undefined') {
            console.warn('⚠️ JalaliCalendar library not found. Falling back to simple text input.');
            setupFallbackDateInputs();
            return;
        }
        
        $('.jalali-datepicker').each(function(index) {
            const $input = $(this);
            
            // پاک کردن تنظیمات قبلی
            $input.removeClass('jalali-datepicker-initialized');
            $input.next('.calendar-icon').remove();
            if ($input.parent('.date-input-wrapper').length) {
                $input.unwrap();
            }
            
            // تنظیم ID منحصر به فرد
            let inputId = $input.attr('id');
            if (!inputId) {
                inputId = 'jalali-datepicker-' + index + '-' + Date.now();
                $input.attr('id', inputId);
            }
            
            // تنظیمات اولیه فیلد
            $input.attr({
                'placeholder': 'انتخاب تاریخ - مثال: 1403/01/01',
                'maxlength': '10',
                'dir': 'rtl',
                'readonly': true
            }).addClass('jalali-datepicker-initialized');
            
            try {
                // ایجاد datepicker با کتابخانه JalaliCalendar
                const picker = window.JalaliCalendar.createDatePicker(inputId, {
                    format: 'Y/m/d',
                    placeholder: 'انتخاب تاریخ',
                    showToday: true,
                    rtl: true
                });
                
                // Event handler برای تغییر تاریخ
                $input.off('change.jalali').on('change.jalali', function() {
                    const value = this.value.trim();
                    console.log('📅 Date changed:', value);
                    
                    // اعتبارسنجی فرمت
                    if (value !== '' && !value.match(/^\d{4}\/\d{2}\/\d{2}$/)) {
                        showNotification('فرمت تاریخ صحیح نیست', 'error');
                        return;
                    }
                    
                    // اجرای فیلتر بعد از تغییر
                    setTimeout(() => {
                        if (validateDateRange()) {
                            performLiveSearch();
                        }
                    }, 100);
                });
                
                console.log('✅ Jalali datepicker created for:', inputId);
                
            } catch (error) {
                console.error('❌ Error creating Jalali datepicker:', error);
                setupSingleFallbackInput($input);
            }
        });
    }
    
    // تنظیم fallback برای input های ساده
    function setupFallbackDateInputs() {
        $('.jalali-datepicker').each(function() {
            setupSingleFallbackInput($(this));
        });
    }
    
    function setupSingleFallbackInput($input) {
        // تنظیمات فیلد ساده
        $input.attr({
            'placeholder': '1403/01/01',
            'pattern': '[0-9]{4}/[0-9]{2}/[0-9]{2}',
            'maxlength': '10',
            'dir': 'rtl',
            'readonly': false
        });
        
        // فرمت کردن خودکار هنگام تایپ
        $input.off('input.fallback').on('input.fallback', function() {
            let value = this.value.replace(/[^0-9]/g, '');
            if (value.length >= 4) {
                value = value.substring(0, 4) + '/' + value.substring(4);
            }
            if (value.length >= 7) {
                value = value.substring(0, 7) + '/' + value.substring(7, 9);
            }
            this.value = value;
        });
        
        // اعتبارسنجی و فیلتر
        $input.off('change.fallback blur.fallback').on('change.fallback blur.fallback', function() {
            const value = this.value.trim();
            
            if (value === '') {
                if (validateDateRange()) {
                    performLiveSearch();
                }
                return;
            }
            
            if (!value.match(/^\d{4}\/\d{2}\/\d{2}$/)) {
                showNotification('فرمت تاریخ باید مثل 1403/01/01 باشد', 'error');
                this.focus();
                return;
            }
            
            // بررسی صحت تاریخ
            const parts = value.split('/');
            const year = parseInt(parts[0]);
            const month = parseInt(parts[1]);
            const day = parseInt(parts[2]);
            
            if (year < 1300 || year > 1500 || month < 1 || month > 12 || day < 1 || day > 31) {
                showNotification('تاریخ وارد شده صحیح نیست', 'error');
                this.focus();
                return;
            }
            
            if (validateDateRange()) {
                performLiveSearch();
            }
        });
    }
    
    // تابع تبدیل تاریخ میلادی به شمسی (ساده)
    function convertToJalali(gregorianDate) {
        const year = gregorianDate.getFullYear();
        const month = gregorianDate.getMonth() + 1;
        const day = gregorianDate.getDate();
        
        // تبدیل ساده (تقریبی)
        const jalaliYear = year - 621;
        const jalaliMonth = month;
        const jalaliDay = day;
        
        return `${jalaliYear}/${jalaliMonth.toString().padStart(2, '0')}/${jalaliDay.toString().padStart(2, '0')}`;
    }

    // Export functions for external use
    window.MarketGoogleOrders = {
        performSearch: performLiveSearch,
        clearFilters: clearAllFilters,
        showNotification: showNotification,
        isLoading: function() { return isLoading; }
    };

    // ارسال پیامک اطلاعات
    function bindSendInfoSMS() {
        $(document).off('click.sendInfoSMS').on('click.sendInfoSMS', '.send-info-sms', function(e) {
            e.preventDefault();
            
            if (isLoading) return;
            
            const $btn = $(this);
            const orderId = $btn.data('id');
            const phone = $btn.data('phone');
            const name = $btn.data('name');
            const business = $btn.data('business');
            
            if (!orderId || !phone) {
                showNotification('اطلاعات ناکافی برای ارسال پیامک', 'error');
                return;
            }
            
            // تأیید ارسال
            const confirmMessage = `آیا مطمئن هستید که می‌خواهید پیامک اطلاعات را برای:\n\n${name}\nشماره: ${phone}\nکسب‌وکار: ${business}\n\nارسال کنید؟`;
            
            if (!confirm(confirmMessage)) {
                return;
            }
            
            // نمایش وضعیت در حال ارسال
            const $icon = $btn.find('.dashicons');
            const originalClass = $icon.attr('class');
            const originalTitle = $btn.attr('title');
            
            $icon.attr('class', 'dashicons dashicons-update-alt').css('animation', 'rotation 1s infinite linear');
            $btn.prop('disabled', true).attr('title', 'در حال ارسال...');
            
            isLoading = true;
            
            const ajaxUrl = (typeof ajaxurl !== 'undefined') ? ajaxurl : '/wp-admin/admin-ajax.php';
            const nonce = (typeof market_google_orders_params !== 'undefined' && market_google_orders_params.security) 
                ? market_google_orders_params.security 
                : $('input[name="market_google_orders_nonce"]').val() || '';
            
            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'market_google_send_location_info_sms',
                    order_id: orderId,
                    phone: phone,
                    security: nonce
                },
                timeout: 30000, // 30 ثانیه timeout
                success: function(response) {
                    if (response && response.success) {
                        showNotification(response.data.message || 'پیامک با موفقیت ارسال شد', 'success');
                        
                        // نمایش متن ارسال شده در console برای debug
                        if (response.data.sent_message) {
                            console.log('SMS sent to', phone + ':', response.data.sent_message);
                        }
                        
                        // تغییر رنگ دکمه برای نشان دادن موفقیت
                        $btn.css('color', '#10b981');
                        setTimeout(() => {
                            $btn.css('color', '');
                        }, 5000);
                        
                        // اضافه کردن نشانگر ارسال موفق
                        $btn.addClass('sms-sent');
                        
                    } else {
                        const errorMessage = response && response.data ? response.data : 'خطای نامشخص در ارسال پیامک';
                        showNotification('خطا در ارسال پیامک: ' + errorMessage, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('SMS Error:', {xhr, status, error});
                    
                    let errorMessage = 'خطا در اتصال به سرور';
                    if (status === 'timeout') {
                        errorMessage = 'زمان انتظار برای ارسال پیامک به پایان رسید';
                    } else if (xhr.responseJSON && xhr.responseJSON.data) {
                        errorMessage = xhr.responseJSON.data;
                    }
                    
                    showNotification(errorMessage, 'error');
                },
                complete: function() {
                    // بازگرداندن وضعیت دکمه
                    $icon.attr('class', originalClass).css('animation', '');
                    $btn.prop('disabled', false).attr('title', originalTitle);
                    isLoading = false;
                }
            });
        });
    }

    // تغییر وضعیت سفارش از داخل مودال
    function bindStatusChanges() {
        // تغییر وضعیت سفارش
        $(document).off('change.statusChange').on('change.statusChange', '.status-change-select', function(e) {
            e.preventDefault();
            
            const orderId = $(this).data('order-id');
            const newStatus = $(this).val();
            
            if (orderId) {
                if (newStatus === 'completed') {
                    completeOrder(orderId, $(this));
                } else {
                    uncompleteOrder(orderId, $(this));
                }
            }
        });
        
        // تغییر وضعیت پرداخت
        $(document).off('change.paymentStatusChange').on('change.paymentStatusChange', '.payment-status-change-select', function(e) {
            e.preventDefault();
            
            const orderId = $(this).data('order-id');
            const newStatus = $(this).val();
            
            if (orderId) {
                changePaymentStatus(orderId, newStatus, $(this));
            }
        });
    }
    
    // تغییر وضعیت پرداخت
    function changePaymentStatus(orderId, newStatus, $element) {
        if (isLoading) return;
        isLoading = true;
        
        if (!orderId) {
            showNotification('شناسه سفارش نامعتبر است', 'error');
            isLoading = false;
            return;
        }
        
        const originalText = $element.closest('.detail-value').text().trim();
        
        const ajaxUrl = (typeof market_google_orders_params !== 'undefined' && market_google_orders_params.ajax_url) 
            ? market_google_orders_params.ajax_url 
            : (typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php');
            
        const nonce = (typeof market_google_orders_params !== 'undefined' && market_google_orders_params.security) 
            ? market_google_orders_params.security 
            : $('input[name="market_google_orders_nonce"]').val() || '';
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'change_payment_status',
                order_id: orderId,
                status: newStatus,
                security: nonce
            },
            beforeSend: function() {
                $element.prop('disabled', true);
            },
            success: function(response) {
                if (response && response.success) {
                    showNotification(response.data.message || 'وضعیت پرداخت با موفقیت تغییر کرد', 'success');
                    
                    // بروزرسانی نمایش وضعیت در مودال
                    const newStatusLabel = response.data.status_label || '';
                    if (newStatusLabel) {
                        $element.closest('.detail-value').find('span').text(newStatusLabel);
                    }
                    
                    // بروزرسانی جدول
                    setTimeout(function() {
                        performLiveSearch();
                    }, 500);
                } else {
                    showNotification(response.data || 'خطا در بروزرسانی وضعیت پرداخت', 'error');
                }
            },
            error: function() {
                showNotification('خطا در ارتباط با سرور', 'error');
            },
            complete: function() {
                isLoading = false;
                $element.prop('disabled', false);
            }
        });
    }
    
    // تکمیل کردن سفارش
    function completeOrder(orderId, $element) {
        if (isLoading) return;
        isLoading = true;
        
        const originalText = $element.closest('.detail-value').text().trim();
        
        const ajaxUrl = (typeof market_google_orders_params !== 'undefined' && market_google_orders_params.ajax_url) 
            ? market_google_orders_params.ajax_url 
            : (typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php');
            
        const nonce = (typeof market_google_orders_params !== 'undefined' && market_google_orders_params.security) 
            ? market_google_orders_params.security 
            : $('input[name="market_google_orders_nonce"]').val() || '';
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'complete_order',
                order_id: orderId,
                security: nonce
            },
            beforeSend: function() {
                $element.prop('disabled', true);
            },
            success: function(response) {
                if (response && response.success) {
                    showNotification(response.data.message || 'سفارش با موفقیت تکمیل شد', 'success');
                    
                    // بروزرسانی نمایش وضعیت در مودال
                    const newStatusLabel = response.data.status_label || 'تکمیل شده';
                    if (newStatusLabel) {
                        $element.closest('.detail-value').find('span').text(newStatusLabel);
                    }
                    
                    // بروزرسانی جدول
                    setTimeout(function() {
                        performLiveSearch();
                    }, 500);
                } else {
                    showNotification(response.data || 'خطا در تکمیل سفارش', 'error');
                }
            },
            error: function() {
                showNotification('خطا در ارتباط با سرور', 'error');
            },
            complete: function() {
                isLoading = false;
                $element.prop('disabled', false);
            }
        });
    }
    
    // برگرداندن به حالت در انتظار
    function uncompleteOrder(orderId, $element) {
        if (isLoading) return;
        isLoading = true;
        
        const originalText = $element.closest('.detail-value').text().trim();
        
        const ajaxUrl = (typeof market_google_orders_params !== 'undefined' && market_google_orders_params.ajax_url) 
            ? market_google_orders_params.ajax_url 
            : (typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php');
            
        const nonce = (typeof market_google_orders_params !== 'undefined' && market_google_orders_params.security) 
            ? market_google_orders_params.security 
            : $('input[name="market_google_orders_nonce"]').val() || '';
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'uncomplete_order',
                order_id: orderId,
                security: nonce
            },
            beforeSend: function() {
                $element.prop('disabled', true);
            },
            success: function(response) {
                if (response && response.success) {
                    showNotification(response.data.message || 'وضعیت سفارش با موفقیت تغییر کرد', 'success');
                    
                    // بروزرسانی نمایش وضعیت در مودال
                    const newStatusLabel = response.data.status_label || 'در انتظار انجام';
                    if (newStatusLabel) {
                        $element.closest('.detail-value').find('span').text(newStatusLabel);
                    }
                    
                    // بروزرسانی جدول
                    setTimeout(function() {
                        performLiveSearch();
                    }, 500);
                } else {
                    showNotification(response.data || 'خطا در بروزرسانی وضعیت سفارش', 'error');
                }
            },
            error: function() {
                showNotification('خطا در ارتباط با سرور', 'error');
            },
            complete: function() {
                isLoading = false;
                $element.prop('disabled', false);
            }
        });
    }

    // علامت زدن سفارش به عنوان خوانده شده از داخل مودال
    function bindReadStatusFromModal() {
        $(document).off('click.toggleReadModal').on('click.toggleReadModal', '.toggle-read-btn', function(e) {
            e.preventDefault();
            
            const orderId = $(this).data('order-id');
            const isRead = $(this).data('is-read') === '1';
            
            if (orderId) {
                markOrderAsReadFromModal(orderId, !isRead, $(this));
            }
        });
    }
    
    // تغییر وضعیت خواندن سفارش از داخل مودال
    function markOrderAsReadFromModal(orderId, makeRead, $button) {
        if (isLoading) return;
        isLoading = true;
        
        if (!orderId) {
            showNotification('شناسه سفارش نامعتبر است', 'error');
            isLoading = false;
            return;
        }
        
        const originalText = $button.text();
        
        const ajaxUrl = (typeof market_google_orders_params !== 'undefined' && market_google_orders_params.ajax_url) 
            ? market_google_orders_params.ajax_url 
            : (typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php');
            
        const nonce = (typeof market_google_orders_params !== 'undefined' && market_google_orders_params.security) 
            ? market_google_orders_params.security 
            : $('input[name="market_google_orders_nonce"]').val() || '';
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'toggle_read_status',
                order_id: orderId,
                is_read: makeRead ? 1 : 0,
                security: nonce
            },
            beforeSend: function() {
                $button.prop('disabled', true).text('در حال اعمال...');
            },
            success: function(response) {
                if (response && response.success) {
                    const newReadStatus = response.data.is_read;
                    const statusText = newReadStatus ? 'خوانده شده' : 'خوانده نشده';
                    const buttonText = newReadStatus ? 'علامت نخوانده' : 'علامت خوانده شده';
                    
                    // بروزرسانی وضعیت در مودال
                    const $statusValue = $button.closest('.read-status-value');
                    $statusValue.contents().filter(function() {
                        return this.nodeType === 3; // text node
                    }).first().replaceWith(statusText);
                    
                    // بروزرسانی دکمه
                    $button.text(buttonText).data('is-read', newReadStatus ? '1' : '0');
                    
                    // نمایش پیام موفقیت
                    showNotification('وضعیت خواندن با موفقیت تغییر کرد', 'success');
                    
                    // بروزرسانی نمایش در جدول
                    const $row = $('tr[data-id="' + orderId + '"]');
                    if ($row.length > 0) {
                        if (newReadStatus) {
                            $row.removeClass('unread').addClass('read');
                        } else {
                            $row.removeClass('read').addClass('unread');
                        }
                        
                        // بروزرسانی آیکون
                        const $icon = $row.find('.toggle-read-status .dashicons');
                        if ($icon.length > 0) {
                            if (newReadStatus) {
                                $icon.removeClass('dashicons-star-filled').addClass('dashicons-star-empty');
                            } else {
                                $icon.removeClass('dashicons-star-empty').addClass('dashicons-star-filled');
                            }
                        }
                    }
                } else {
                    showNotification('خطا در تغییر وضعیت خواندن: ' + (response.data || 'خطای نامشخص'), 'error');
                    $button.text(originalText);
                }
            },
            error: function() {
                showNotification('خطا در ارتباط با سرور', 'error');
                $button.text(originalText);
            },
            complete: function() {
                isLoading = false;
                $button.prop('disabled', false);
            }
        });
    }

    // قابلیت کپی کردن
    function initCopyFunctionality() {
        $('.copyable').off('click').on('click', function() {
            const text = $(this).text().trim();
            if (text && text !== 'توسط کاربر تکمیل نشده') {
                navigator.clipboard.writeText(text).then(function() {
                    showNotification('متن کپی شد: ' + text.substring(0, 30) + (text.length > 30 ? '...' : ''), 'success');
                }).catch(function() {
                    // روش جایگزین برای مرورگرهای قدیمی
                    const textarea = document.createElement('textarea');
                    textarea.value = text;
                    document.body.appendChild(textarea);
                    textarea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textarea);
                    showNotification('متن کپی شد', 'success');
                });
            }
        });
    }

    // دکمه انصراف ویرایش - بازگشت به حالت نمایش
    $(document).on('click', '.cancel-edit-btn', function() {
        const orderId = $('.save-order-btn').data('order-id');
        
        $.ajax({
            url: marketGoogleAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'get_order_details',
                order_id: orderId,
                edit_mode: false,
                nonce: marketGoogleAjax.nonce
            },
            success: function(response) {
                $('#order-modal .modal-content').html(response);
                initOrderMap();
                initCopyFunctionality();
            },
            error: function() {
                $('#order-modal .modal-content').html('<div style="text-align:center;padding:50px;color:red;">خطا در بارگذاری اطلاعات</div>');
            }
        });
    });

    // دکمه ذخیره تغییرات
    $(document).on('click', '.save-order-btn', function() {
        const orderId = $(this).data('order-id');
        const formData = {};
        
        // جمع‌آوری داده‌های فرم
        $('.editable-field').each(function() {
            const fieldName = $(this).attr('name');
            const fieldValue = $(this).val();
            formData[fieldName] = fieldValue;
        });
        
        // اضافه کردن order_id
        formData.action = 'update_order';
        formData.order_id = orderId;
        formData.nonce = marketGoogleAjax.nonce;
        
        $.ajax({
            url: marketGoogleAjax.ajax_url,
            type: 'POST',
            data: formData,
            beforeSend: function() {
                $('.save-order-btn').prop('disabled', true).text('در حال ذخیره...');
            },
            success: function(response) {
                if (response.success) {
                    alert('تغییرات با موفقیت ذخیره شد.');
                    
                    // بازگشت به حالت نمایش
                    $.ajax({
                        url: marketGoogleAjax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'get_order_details',
                            order_id: orderId,
                            edit_mode: false,
                            nonce: marketGoogleAjax.nonce
                        },
                        success: function(response) {
                            $('#order-modal .modal-content').html(response);
                            initOrderMap();
                            initCopyFunctionality();
                            
                            // به‌روزرسانی جدول
                            location.reload();
                        }
                    });
                } else {
                    alert('خطا در ذخیره تغییرات: ' + (response.data || 'خطای نامشخص'));
                }
            },
            error: function() {
                alert('خطا در ارتباط با سرور');
            },
            complete: function() {
                $('.save-order-btn').prop('disabled', false).text('ذخیره تغییرات');
            }
        });
    });

    // دکمه ویرایش در داخل مودال
    $(document).on('click', '.edit-order-modal', function() {
        const orderId = $(this).data('order-id');
        
        $.ajax({
            url: marketGoogleAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'get_order_details',
                order_id: orderId,
                edit_mode: true,
                nonce: marketGoogleAjax.nonce
            },
            success: function(response) {
                $('#order-modal .modal-content').html(response);
                initOrderMap();
                initCopyFunctionality();
            },
            error: function() {
                $('#order-modal .modal-content').html('<div style="text-align:center;padding:50px;color:red;">خطا در بارگذاری اطلاعات</div>');
            }
        });
    });

    // دکمه ارسال پیامک در مودال
    $(document).on('click', '.send-sms-btn', function() {
        const orderId = $(this).data('order-id');
        const phone = $('.copyable[data-clipboard]:contains("09")').text().trim() || $('.copyable:contains("09")').text().trim();
        
        if (!phone || !phone.match(/^09\d{9}$/)) {
            alert('شماره موبایل معتبری یافت نشد');
            return;
        }
        
        if (!confirm('آیا مطمئن هستید که می‌خواهید پیامک اطلاعات را به شماره ' + phone + ' ارسال کنید؟')) {
            return;
        }
        
        const $btn = $(this);
        const originalText = $btn.text();
        
        $.ajax({
            url: marketGoogleAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'send_location_info_sms',
                order_id: orderId,
                phone: phone,
                nonce: marketGoogleAjax.nonce
            },
            beforeSend: function() {
                $btn.prop('disabled', true).text('در حال ارسال...');
            },
            success: function(response) {
                if (response.success) {
                    alert('پیامک با موفقیت ارسال شد.');
                } else {
                    alert('خطا در ارسال پیامک: ' + (response.data || 'خطای نامشخص'));
                }
            },
            error: function() {
                alert('خطا در ارتباط با سرور');
            },
            complete: function() {
                $btn.prop('disabled', false).text(originalText);
            }
        });
    });

})(jQuery);