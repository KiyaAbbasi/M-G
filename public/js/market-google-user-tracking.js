/**
 * Market Google Advanced User Tracking Script
 * سیستم ردیابی پیشرفته و کامل رفتار کاربران
 * نسخه: 2.0.0 - Professional Edition
 */

console.log('🟢 market-google-user-tracking.js LOADED ON PAGE:', window.location.href);

(function($) {
    'use strict';
    
    // Core tracking variables
    let userSessionId = null;
    let trackingEnabled = false;
    let formStartTime = null;
    let pageLoadTime = null;
    let lastActivityTime = null;
    let sessionStartTime = null;
    
    // Advanced tracking data structures
    let keystrokeBuffer = [];
    let mouseMovements = [];
    let fieldFocusTimes = {};
    let fieldValues = {};
    let previousValues = {};
    let typingPatterns = {};
    let clickCoordinates = [];
    let scrollEvents = [];
    let errorEvents = [];
    
    // Performance monitoring
    let performanceData = {
        pageLoadTime: 0,
        networkSpeed: 'unknown',
        batteryLevel: null,
        memoryUsage: null
    };
    
    // Behavior analysis
    let behaviorMetrics = {
        rageClickCount: 0,
        deadClickCount: 0,
        copyPasteCount: 0,
        backspaceCount: 0,
        hesitationTime: 0,
        interactionDepth: 0,
        exitIntent: false
    };
    
    // Device and system info
    let deviceInfo = {};
    let browserInfo = {};
    
    console.log('🚀 Market Google Advanced Tracking Script Loading...');
    
    $(document).ready(function() {
        console.log('📋 Document ready, initializing advanced tracking system...');
        
        // Initialize tracking immediately
        initializeAdvancedTracking();
        
        // Collect system and device information
        collectSystemInfo();
        
        // Setup all tracking components
        setTimeout(function() {
            setupKeystrokeTracking();
            setupMouseTracking();
            setupScrollTracking();
            setupFormTracking();
            setupBehaviorAnalysis();
            setupPerformanceMonitoring();
        }, 500);
        
        // Test scenario for demonstration (remove in production)
        if (window.location.search.includes('test_scenario=true')) {
            setTimeout(function() {
                simulateUserScenario();
            }, 2000);
        }
    });
    
    /**
     * Initialize advanced tracking system
     */
    function initializeAdvancedTracking() {
        // Check if session already exists
        userSessionId = localStorage.getItem('mg_session_id');
        
        // If no session exists or expired (older than 30 minutes), create new one
        const lastActivity = localStorage.getItem('mg_last_activity');
        const now = Date.now();
        
        if (!userSessionId || !lastActivity || (now - parseInt(lastActivity)) > 1800000) {
            // Generate unique session ID with timestamp and random components
            userSessionId = 'MG_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            localStorage.setItem('mg_session_id', userSessionId);
        }
        
        // Update last activity
        localStorage.setItem('mg_last_activity', now.toString());
        
        trackingEnabled = true;
        pageLoadTime = performance.now();
        sessionStartTime = new Date();
        formStartTime = new Date();
        lastActivityTime = new Date();
        
        console.log('📊 Advanced tracking initialized with Session ID:', userSessionId);
        
        // Send initial page load event with comprehensive data
        trackAdvancedEvent('page_load', null, {
            session_start: true,
            page_load_time: Math.round(pageLoadTime),
            timestamp: new Date().toISOString(),
            page_title: document.title,
            referrer: document.referrer,
            conversion_funnel_step: 'page_entry'
        });
        
        // Setup heartbeat system (every 15 seconds for detailed monitoring)
        setInterval(function() {
            if (trackingEnabled && userSessionId) {
                sendHeartbeat();
            }
        }, 15000);
        
        // Setup session update (every 30 seconds)
        setInterval(updateSessionData, 30000);
    }
    
    /**
     * Collect comprehensive system and device information
     */
    function collectSystemInfo() {
        // Device information
        deviceInfo = {
            screen_width: screen.width,
            screen_height: screen.height,
            viewport_width: window.innerWidth,
            viewport_height: window.innerHeight,
            device_pixel_ratio: window.devicePixelRatio || 1,
            color_depth: screen.colorDepth,
            orientation: screen.orientation ? screen.orientation.type : 'unknown',
            touch_support: 'ontouchstart' in window,
            platform: navigator.platform,
            language: navigator.language,
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
            online: navigator.onLine
        };
        
        // Browser information
        browserInfo = {
            user_agent: navigator.userAgent,
            vendor: navigator.vendor,
            app_name: navigator.appName,
            app_version: navigator.appVersion,
            cookie_enabled: navigator.cookieEnabled,
            do_not_track: navigator.doNotTrack,
            hardware_concurrency: navigator.hardwareConcurrency,
            max_touch_points: navigator.maxTouchPoints || 0,
            pdf_viewer_enabled: navigator.pdfViewerEnabled
        };
        
        // Device Model Detection
        deviceInfo.device_model = detectDeviceModel();
        
        // Device Fingerprint (Unique ID)
        deviceInfo.device_fingerprint = generateDeviceFingerprint();
        
        // Get IP and Location
        getIPAndLocation();
        
        // Network information
        if (navigator.connection) {
            deviceInfo.connection_type = navigator.connection.effectiveType;
            deviceInfo.network_speed = navigator.connection.downlink + 'Mbps';
            performanceData.networkSpeed = navigator.connection.effectiveType;
        }
        
        // Battery information
        if (navigator.getBattery) {
            navigator.getBattery().then(function(battery) {
                performanceData.batteryLevel = Math.round(battery.level * 100);
                deviceInfo.battery_level = performanceData.batteryLevel;
                deviceInfo.battery_charging = battery.charging;
            });
        }
        
        // Memory information
        if (performance.memory) {
            performanceData.memoryUsage = Math.round(performance.memory.usedJSHeapSize / 1048576); // MB
            deviceInfo.memory_size = Math.round(performance.memory.totalJSHeapSize / 1048576);
        }
        
        // CPU class estimation
        deviceInfo.cpu_class = estimateCPUClass();
        
        console.log('🖥️ System info collected:', deviceInfo);
    }
    
    /**
     * Enhanced keystroke tracking
     */
    function setupKeystrokeTracking() {
        $(document).on('keydown keyup', 'input, textarea', function(e) {
            const field = $(this);
            const fieldId = getElementIdentifier(field);
            const currentTime = performance.now();
            
            if (e.type === 'keydown') {
                // Record keystroke data
                keystrokeBuffer.push({
                    field: fieldId,
                    key: e.key,
                    keyCode: e.keyCode,
                    timestamp: currentTime,
                    type: 'keydown'
                });
            
            // Track special keys
                if (e.key === 'Backspace') {
                behaviorMetrics.backspaceCount++;
                }
                
                // Detect copy/paste
                if ((e.ctrlKey || e.metaKey) && (e.key === 'v' || e.key === 'V')) {
                    behaviorMetrics.copyPasteCount++;
                    trackAdvancedEvent('paste_detected', fieldId, {
                        paste_count: behaviorMetrics.copyPasteCount
                    });
                }
            }
            
            if (e.type === 'keyup') {
                keystrokeBuffer.push({
                    field: fieldId,
                    key: e.key,
                    keyCode: e.keyCode,
                    timestamp: currentTime,
                    type: 'keyup'
                });
            }
        });
        
        // Track field input changes
        $(document).on('input', 'input, textarea', function(e) {
            const field = $(this);
            const fieldId = getElementIdentifier(field);
            const currentValue = field.val();
            const previousValue = fieldValues[fieldId] || '';
            
            // Update field values
            fieldValues[fieldId] = currentValue;
            
            // Calculate typing metrics
            const typingSpeed = calculateTypingSpeed(fieldId, currentValue);
            const hesitationTime = calculateHesitationTime(fieldId, currentValue);
            
            // Track the input event with detailed data
            trackAdvancedEvent('field_input', fieldId, {
                element_name: field.attr('name') || field.attr('id') || '',
                element_type: field.attr('type') || field.prop('tagName').toLowerCase(),
                element_value: currentValue,
                previous_value: previousValue,
                value_length: currentValue.length,
                typing_speed: typingSpeed,
                hesitation_time: hesitationTime,
                backspace_count: behaviorMetrics.backspaceCount,
                copy_paste_count: behaviorMetrics.copyPasteCount,
                form_progress: calculateFormProgress()
            });
            
            behaviorMetrics.interactionDepth++;
        });
    }
    
    /**
     * Enhanced mouse tracking
     */
    function setupMouseTracking() {
        let mouseBuffer = [];
        let lastMouseTime = 0;
        
        $(document).on('mousemove', function(e) {
            const currentTime = performance.now();
            
            // Throttle mouse tracking to avoid overwhelming data
            if (currentTime - lastMouseTime > 100) { // Track every 100ms
                mouseBuffer.push({
                x: e.pageX,
                y: e.pageY,
                    timestamp: currentTime
                });
                
                lastMouseTime = currentTime;
                
                // Keep buffer size manageable
                if (mouseBuffer.length > 50) {
                    mouseMovements = mouseMovements.concat(mouseBuffer);
                    mouseBuffer = [];
                }
            }
        });
        
        // Track clicks
        $(document).on('click', function(e) {
            const target = $(e.target);
            const targetId = getElementIdentifier(target);
            
            clickCoordinates.push({
                x: e.pageX,
                y: e.pageY,
                target: targetId,
                timestamp: performance.now()
            });
            
            trackAdvancedEvent('click', targetId, {
                    mouse_x: e.pageX,
                    mouse_y: e.pageY,
                element_type: target.prop('tagName').toLowerCase(),
                click_count: clickCoordinates.length
            });
            
            behaviorMetrics.interactionDepth++;
        });
        
        // Detect rage clicks
        let clickHistory = [];
        $(document).on('click', function(e) {
            const now = performance.now();
            clickHistory.push(now);
            
            // Keep only recent clicks (last 2 seconds)
            clickHistory = clickHistory.filter(time => now - time < 2000);
            
            if (clickHistory.length >= 5) {
                behaviorMetrics.rageClickCount++;
                trackAdvancedEvent('rage_click', getElementIdentifier($(e.target)), {
                    rage_click_count: behaviorMetrics.rageClickCount
                });
            }
        });
    }
    
    /**
     * Enhanced scroll tracking
     */
    function setupScrollTracking() {
        let scrollTimer = null;
        
        $(window).on('scroll', function() {
            clearTimeout(scrollTimer);
            
            scrollTimer = setTimeout(function() {
                const scrollPosition = $(window).scrollTop();
            const windowHeight = $(window).height();
                const documentHeight = $(document).height();
                const scrollDepth = Math.round((scrollPosition + windowHeight) / documentHeight * 100);
                
                scrollEvents.push({
                    position: scrollPosition,
                    depth: scrollDepth,
                timestamp: performance.now()
                });
                
                trackAdvancedEvent('scroll', null, {
                    scroll_position: scrollPosition,
                    scroll_depth: scrollDepth
                });
            }, 250);
        });
    }
    
    /**
     * Enhanced form tracking with real data collection for UX analysis
     */
    function setupFormTracking() {
        // ردیابی فرم market-location-form
        const mainForm = $('#market-location-form');
        if (mainForm.length === 0) {
            console.log('⚠️ Form not found on this page');
            return;
        }
        
        console.log('🎯 Market Location Form detected, setting up tracking...');
        
        // تشخیص تمام فیلدهای فرم
        const formFields = mainForm.find('input, textarea, select, button[type="button"]');
        const totalFields = formFields.length;
        
        console.log('🔍 Form fields detected:', totalFields, formFields);
        
        // ردیابی فیلدهای ورودی
        formFields.filter('input, textarea, select').each(function(index) {
            const field = $(this);
            const fieldId = getElementIdentifier(field);
            const fieldName = field.attr('name') || field.attr('id') || '';
            
            // Focus tracking with timing
            field.on('focus', function() {
                fieldFocusTimes[fieldId] = performance.now();
                
                trackAdvancedEvent('field_focus', fieldId, {
                    element_name: fieldName,
                    element_type: field.attr('type') || field.prop('tagName').toLowerCase(),
                    field_index: index,
                    total_fields: totalFields,
                    form_progress: calculateFormProgress(),
                    form_step: getCurrentFormStep(),
                    conversion_funnel_step: determineFormStep(fieldId, field)
                });
            });
            
            // Input tracking for real-time changes
            field.on('input', function() {
                const fieldValue = field.val();
                // لاگ برای بررسی دقیق نام و مقدار فیلد
                console.log('📝 field_input:', {name: fieldName, id: fieldId, value: fieldValue, type: field.attr('type'), tag: field.prop('tagName')});
                // خاص: وقتی فیلد full_name پر می‌شود
                if (fieldName === 'full_name' && fieldValue.length > 2) {
                    trackAdvancedEvent('user_name_entered', fieldId, {
                        element_name: fieldName,
                        element_value: fieldValue,
                        form_progress: calculateFormProgress(),
                        user_identity_revealed: true,
                        conversion_funnel_step: 'personal_info_entered'
                    });
                }
                trackAdvancedEvent('field_input', fieldId, {
                    element_name: fieldName,
                    element_type: field.attr('type') || field.prop('tagName').toLowerCase(),
                    element_value: fieldValue,
                    value_length: fieldValue.length,
                    form_progress: calculateFormProgress(),
                    form_step: getCurrentFormStep(),
                    conversion_funnel_step: determineFormStep(fieldId, field)
                });
                // Update field values for progress calculation
                fieldValues[fieldId] = fieldValue;
                behaviorMetrics.interactionDepth++;
            });
            
            // Blur tracking with analysis
            field.on('blur', function() {
                const fieldValue = field.val();
                const focusTime = fieldFocusTimes[fieldId];
                const timeOnField = focusTime ? performance.now() - focusTime : 0;
                
                trackAdvancedEvent('field_blur', fieldId, {
                    element_name: fieldName,
                    element_type: field.attr('type') || field.prop('tagName').toLowerCase(),
                    element_value: fieldValue,
                    time_on_element: Math.round(timeOnField),
                    field_completed: fieldValue.length > 0,
                    form_progress: calculateFormProgress(),
                    form_step: getCurrentFormStep(),
                    conversion_funnel_step: determineFormStep(fieldId, field)
                });
            });
            
            // Change tracking for selects
            field.on('change', function() {
                const fieldValue = field.val();
                const selectedText = field.find('option:selected').text() || fieldValue;
                
                trackAdvancedEvent('field_change', fieldId, {
                    element_name: fieldName,
                        element_type: field.attr('type') || field.prop('tagName').toLowerCase(),
                    element_value: fieldValue,
                    selected_text: selectedText,
                        form_progress: calculateFormProgress(),
                    form_step: getCurrentFormStep(),
                        conversion_funnel_step: determineFormStep(fieldId, field)
                    });
            });
        });
        
        // ردیابی دکمه‌های navigation
        mainForm.find('.btn-next').on('click', function() {
            const currentStep = getCurrentFormStep();
            const nextStep = currentStep + 1;
            
            trackAdvancedEvent('form_step_next', 'btn-next', {
                current_step: currentStep,
                next_step: nextStep,
                form_progress: calculateFormProgress(),
                step_completion_time: Math.round((new Date() - formStartTime) / 1000),
                conversion_funnel_step: `step_${currentStep}_to_${nextStep}`
            });
        });
        
        mainForm.find('.btn-prev').on('click', function() {
            const currentStep = getCurrentFormStep();
            const prevStep = currentStep - 1;
            
            trackAdvancedEvent('form_step_prev', 'btn-prev', {
                current_step: currentStep,
                previous_step: prevStep,
                form_progress: calculateFormProgress(),
                conversion_funnel_step: `step_${currentStep}_to_${prevStep}`
            });
        });
        
        // ردیابی انتخاب پکیج
        $(document).on('click', '.package-option', function() {
            const packageElement = $(this);
            const packageName = packageElement.find('.package-title').text() || 'نامشخص';
            const packagePrice = packageElement.find('.package-price').text() || '0';
            
            trackAdvancedEvent('package_selected', 'package-option', {
                package_name: packageName,
                package_price: packagePrice,
                form_progress: calculateFormProgress(),
                form_step: getCurrentFormStep(),
                conversion_funnel_step: 'package_selection'
            });
        });
        
        // ردیابی کلیک روی نقشه
        $(document).on('click', '#map', function() {
            trackAdvancedEvent('map_clicked', 'map', {
                form_progress: calculateFormProgress(),
                form_step: getCurrentFormStep(),
                conversion_funnel_step: 'location_selection'
            });
        });
        
        // Form submission tracking
        mainForm.on('submit', function(e) {
            const formData = gatherCompleteFormData(mainForm);
            
            trackAdvancedEvent('form_submit_attempt', 'market-location-form', {
                form_id: 'market-location-form',
                form_data: JSON.stringify(formData),
                form_completion_time: Math.round((new Date() - formStartTime) / 1000),
                form_progress: 95, // قبل از پرداخت
                conversion_funnel_step: 'payment_initiation'
            });
        });
        
        // ردیابی نتیجه پرداخت از URL
        checkPaymentResult();
        
        // Track form sections if they exist
        trackFormSections();
    }
    
    /**
     * Track specific form sections
     */
    function trackFormSections() {
        // Personal information section
        const personalFields = $('input[name*="name"], input[id*="name"], input[name*="phone"], input[id*="phone"], input[name*="email"], input[id*="email"]');
        personalFields.on('input', function() {
            trackAdvancedEvent('personal_info_input', getElementIdentifier($(this)), {
                    section: 'personal_information',
                form_progress: calculateFormProgress()
            });
        });
        
        // Business information section
        const businessFields = $('input[name*="business"], input[id*="business"], select[name*="business"], select[id*="business"]');
        businessFields.on('input change', function() {
            trackAdvancedEvent('business_info_input', getElementIdentifier($(this)), {
                section: 'business_information',
                form_progress: calculateFormProgress()
            });
        });
        
        // Location information section
        const locationFields = $('input[name*="address"], input[id*="address"], input[name*="city"], input[id*="city"], input[name*="province"], input[id*="province"], select[name*="province"], select[id*="province"], select[name*="city"], select[id*="city"]');
        locationFields.on('input change', function() {
            trackAdvancedEvent('location_info_input', getElementIdentifier($(this)), {
                section: 'location_information',
                form_progress: calculateFormProgress()
            });
        });
    }
    
    /**
     * Determine form step
     */
    function determineFormStep(fieldId, field) {
        const fieldName = (field.attr('name') || field.attr('id') || '').toLowerCase();
        
        if (fieldName.includes('name') || fieldName.includes('نام')) {
            return 'personal_info';
        } else if (fieldName.includes('phone') || fieldName.includes('تلفن') || fieldName.includes('موبایل')) {
            return 'contact_info';
        } else if (fieldName.includes('business') || fieldName.includes('کسب') || fieldName.includes('شغل')) {
            return 'business_info';
        } else if (fieldName.includes('address') || fieldName.includes('آدرس') || fieldName.includes('city') || fieldName.includes('شهر') || fieldName.includes('province') || fieldName.includes('استان')) {
            return 'location_info';
        } else if (fieldName.includes('website') || fieldName.includes('وب') || fieldName.includes('سایت')) {
            return 'additional_info';
        } else {
            return 'other';
        }
    }
    
    /**
     * Get form step label
     */
    function getFormStepLabel(step) {
        const labels = {
            'personal_info': 'اطلاعات شخصی',
            'contact_info': 'اطلاعات تماس',
            'business_info': 'اطلاعات کسب و کار',
            'location_info': 'اطلاعات مکانی',
            'additional_info': 'اطلاعات تکمیلی',
            'other': 'سایر'
        };
        return labels[step] || step;
    }
    
    /**
     * Determine exit point
     */
    function determineExitPoint(fieldId, field, hasValue) {
        const step = determineFormStep(fieldId, field);
        const label = getFormStepLabel(step);
        return hasValue ? `${label} (تکمیل شده)` : `${label} (ناتمام)`;
    }
    
    /**
     * Find abandonment point
     */
    function findAbandonmentPoint() {
        const allFields = $('input, textarea, select');
        let lastFilledField = null;
        
        allFields.each(function() {
            const field = $(this);
            if (field.val() && field.val().trim() !== '') {
                lastFilledField = field;
            }
        });
        
        if (lastFilledField) {
            return determineExitPoint(getElementIdentifier(lastFilledField), lastFilledField, true);
        }
        
        return 'شروع فرم';
    }
    
    /**
     * Gather complete form data
     */
    function gatherCompleteFormData(form) {
        const formData = {};
        
        form.find('input, textarea, select').each(function() {
            const field = $(this);
            const fieldName = field.attr('name') || field.attr('id');
            if (fieldName) {
                formData[fieldName] = field.val();
            }
        });
        
        return formData;
    }
    
    /**
     * Validate field
     */
    function validateField(field, value) {
        const fieldType = field.attr('type');
        const fieldName = (field.attr('name') || field.attr('id') || '').toLowerCase();
        
        if (fieldName.includes('email')) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
        } else if (fieldName.includes('phone') || fieldName.includes('mobile')) {
            return /^[\d\-\+\(\)\s]+$/.test(value) && value.length >= 10;
        } else if (fieldType === 'url') {
            return /^https?:\/\/.+/.test(value);
        } else if (field.prop('required')) {
            return value.trim().length > 0;
        }
        
        return true;
    }
    
    /**
     * Get form validation errors
     */
    function getFormValidationErrors(form) {
        const errors = [];
        
        form.find('input, textarea, select').each(function() {
            const field = $(this);
            const value = field.val();
            const fieldName = field.attr('name') || field.attr('id') || 'unknown';
            
            if (!validateField(field, value)) {
                errors.push(fieldName);
            }
        });
        
        return errors;
    }
    
    /**
     * Setup behavior analysis
     */
    function setupBehaviorAnalysis() {
        // Exit intent detection
        $(document).on('mouseleave', function(e) {
            if (e.clientY < 0) {
                behaviorMetrics.exitIntent = true;
                trackAdvancedEvent('exit_intent', null, {
                    exit_intent: true,
                    abandonment_point: findAbandonmentPoint()
                });
            }
        });
        
        // Dead click detection
        $(document).on('click', function(e) {
            const target = $(e.target);
            if (!target.is('a, button, input, select, textarea') && !target.closest('a, button').length) {
                behaviorMetrics.deadClickCount++;
                if (behaviorMetrics.deadClickCount > 3) {
                    trackAdvancedEvent('dead_click', getElementIdentifier(target), {
                        dead_click_count: behaviorMetrics.deadClickCount
                    });
                }
            }
        });
        
        // Error tracking
        window.addEventListener('error', function(e) {
            errorEvents.push({
                message: e.message,
                filename: e.filename,
                lineno: e.lineno,
                timestamp: performance.now()
            });
            
            trackAdvancedEvent('javascript_error', null, {
                error_message: e.message,
                error_count: errorEvents.length
            });
        });
    }
    
    /**
     * Setup performance monitoring
     */
    function setupPerformanceMonitoring() {
        // Page load performance
        window.addEventListener('load', function() {
            setTimeout(function() {
                const perfData = performance.getEntriesByType('navigation')[0];
                if (perfData) {
                    performanceData.pageLoadTime = Math.round(perfData.loadEventEnd - perfData.fetchStart);
                    
                    trackAdvancedEvent('page_load_complete', null, {
                        page_load_time: performanceData.pageLoadTime,
                        dom_content_loaded: Math.round(perfData.domContentLoadedEventEnd - perfData.fetchStart),
                        first_paint: Math.round(perfData.responseEnd - perfData.fetchStart)
                    });
                }
            }, 1000);
        });
    }
    
    /**
     * Calculate form progress for Market Location Form - COMPLETE VERSION
     */
    function calculateFormProgress() {
        const mainForm = $('#market-location-form');
        if (mainForm.length === 0) {
            // Fallback for other forms
            const allFields = $('input:not([type="hidden"]), textarea, select');
            let filledFields = 0;
            
            allFields.each(function() {
                const field = $(this);
                if (field.val() && field.val().trim() !== '') {
                    filledFields++;
                }
            });
            
            return allFields.length > 0 ? Math.round((filledFields / allFields.length) * 100) : 0;
        }
        
        let progress = 0;
        const currentStep = getCurrentFormStep();
        
        // مرحله 1: اطلاعات شخصی (20% کل)
        // فیلد 1: نام کامل (10%)
        const fullName = $('#full_name').val();
        if (fullName && fullName.length > 2) {
            progress += 10;
        }
        
        // فیلد 2: تلفن همراه (10%)
        const phone = $('#phone').val();
        if (phone && phone.length > 10) {
            progress += 10;
        }
        
        // مرحله 2: کسب‌وکار و موقعیت (50% کل)
        // فیلد 3: نام کسب‌وکار (8%)
        const businessName = $('#business_name').val();
        if (businessName && businessName.length > 2) {
            progress += 8;
        }
        
        // فیلد 4: تلفن کسب‌وکار (7%)
        const businessPhone = $('#business_phone').val();
        if (businessPhone && businessPhone.length > 10) {
            progress += 7;
        }
        
        // فیلد 5+6: انتخاب موقعیت روی نقشه (15%)
        const latitude = $('#latitude').val();
        const longitude = $('#longitude').val();
        if (latitude && longitude) {
            progress += 15;
        }
        
        // فیلد 7: انتخاب استان (5%)
        const province = $('#province').val();
        if (province && province !== '') {
            progress += 5;
        }
        
        // فیلد 8: انتخاب شهر (5%)
        const city = $('#city').val();
        if (city && city !== '') {
            progress += 5;
        }
        
        // فیلد 9: آدرس دقیق (اختیاری - 3%)
        const manualAddress = $('#manual_address').val();
        if (manualAddress && manualAddress.length > 5) {
            progress += 3;
        }
        
        // فیلد 10: وب سایت (اختیاری - 2%)
        const website = $('#website').val();
        if (website && website.length > 3) {
            progress += 2;
        }
        
        // فیلد 11: ساعت کاری (پیش‌فرض دارد - 5%)
        const workingHours = $('#working_hours_text').val();
        if (workingHours && workingHours.length > 2) {
            progress += 5; // این همیشه پر است
        }
        
        // مرحله 3: انتخاب محصولات (20% کل)
        // فیلد 12: انتخاب پکیج (20%)
        const selectedPackages = $('#selected_packages').val();
        if (selectedPackages && selectedPackages.length > 0) {
            progress += 20;
        }
        
        // مرحله 4: تایید و پرداخت (10% کل)
        // فیلد 13: تایید قوانین (5%)
        const termsAccepted = $('#terms').is(':checked');
        if (termsAccepted) {
            progress += 5;
        }
        
        // فیلد 14: کلیک دکمه submit (5%)
        // این در form_submit_attempt ردیابی می‌شود
        
        // بونوس progression بر اساس مرحله فعلی
        if (currentStep >= 2) {
            progress = Math.max(progress, 20); // حداقل 20% در مرحله 2
        }
        if (currentStep >= 3) {
            progress = Math.max(progress, 70); // حداقل 70% در مرحله 3
        }
        if (currentStep >= 4) {
            progress = Math.max(progress, 90); // حداقل 90% در مرحله 4
        }
        
        // فیلد 15-17: نتایج پرداخت (5% باقی‌مانده)
        // این در checkPaymentResult() محاسبه می‌شود
        
        return Math.min(progress, 95); // حداکثر 95% قبل از تکمیل پرداخت
    }
    
    /**
     * Get current form step
     */
    function getCurrentFormStep() {
        const activeStep = $('.form-step.active');
        if (activeStep.length > 0) {
            return parseInt(activeStep.attr('data-step')) || 1;
        }
        return 1;
    }
    
    /**
     * Check payment result from URL - تکمیل 5% باقی‌مانده
     */
    function checkPaymentResult() {
        const urlParams = new URLSearchParams(window.location.search);
        const paymentStatus = urlParams.get('payment_status');
        const orderId = urlParams.get('order_id');
        
        if (paymentStatus) {
            let finalProgress = 95;
            let funnelStep = 'payment_failed';
            let currentProgress = calculateFormProgress(); // دریافت پیشرفت فعلی
            
            switch(paymentStatus) {
                case 'success':
                case 'completed':
                    finalProgress = 100;
                    funnelStep = 'payment_success_conversion_complete';
                    break;
                case 'failed':
                case 'error':
                    finalProgress = Math.min(currentProgress + 2, 96);
                    funnelStep = 'payment_failed';
                    break;
                case 'cancelled':
                    finalProgress = Math.min(currentProgress + 1, 94);
                    funnelStep = 'payment_cancelled';
                    break;
                case 'pending':
                    finalProgress = Math.min(currentProgress + 3, 98);
                    funnelStep = 'payment_pending';
                    break;
            }
            
            trackAdvancedEvent('payment_result', 'payment-gateway', {
                payment_status: paymentStatus,
                order_id: orderId || 'unknown',
                form_progress: finalProgress,
                previous_progress: currentProgress,
                conversion_funnel_step: funnelStep,
                is_conversion: finalProgress === 100,
                payment_step_completion: true
            });
        }
    }
    
    /**
     * Calculate typing speed
     */
    function calculateTypingSpeed(elementId, currentValue) {
        if (!typingPatterns[elementId]) {
            typingPatterns[elementId] = {
                startTime: performance.now(),
                keystrokes: 0
            };
        }
        
        typingPatterns[elementId].keystrokes++;
        const timeElapsed = (performance.now() - typingPatterns[elementId].startTime) / 1000;
        
        return timeElapsed > 0 ? Math.round(typingPatterns[elementId].keystrokes / timeElapsed) : 0;
    }
    
    /**
     * Calculate hesitation time
     */
    function calculateHesitationTime(elementId, value) {
        const recentKeystrokes = keystrokeBuffer.filter(k => 
            k.field === elementId && 
            performance.now() - k.timestamp < 5000
        );
        
        if (recentKeystrokes.length < 2) return 0;
        
        let totalPauses = 0;
        let pauseCount = 0;
        
        for (let i = 1; i < recentKeystrokes.length; i++) {
            const pause = recentKeystrokes[i].timestamp - recentKeystrokes[i-1].timestamp;
            if (pause > 500) { // Pause longer than 500ms
                totalPauses += pause;
                pauseCount++;
            }
        }
        
        return pauseCount > 0 ? Math.round(totalPauses / pauseCount) : 0;
    }
    
    /**
     * Estimate CPU class
     */
    function estimateCPUClass() {
        const startTime = performance.now();
        for (let i = 0; i < 100000; i++) {
            Math.random();
        }
        const endTime = performance.now();
        const executionTime = endTime - startTime;
        
        if (executionTime < 1) return 'high';
        if (executionTime < 5) return 'medium';
        return 'low';
    }
    
    /**
     * Detect device model from user agent
     */
    function detectDeviceModel() {
        const userAgent = navigator.userAgent;
        
        // iPhone detection
        if (/iPhone/.test(userAgent)) {
            if (/iPhone OS 15_/.test(userAgent)) return 'iPhone 13 Series';
            if (/iPhone OS 14_/.test(userAgent)) return 'iPhone 12 Series';
            if (/iPhone OS 13_/.test(userAgent)) return 'iPhone 11 Series';
            if (/iPhone/.test(userAgent)) return 'iPhone (Other)';
        }
        
        // iPad detection
        if (/iPad/.test(userAgent)) {
            return 'iPad';
        }
        
        // Android detection
        if (/Android/.test(userAgent)) {
            if (/SM-G/.test(userAgent)) return 'Samsung Galaxy';
            if (/Pixel/.test(userAgent)) return 'Google Pixel';
            if (/HUAWEI/.test(userAgent)) return 'Huawei';
            if (/Xiaomi/.test(userAgent)) return 'Xiaomi';
            if (/OnePlus/.test(userAgent)) return 'OnePlus';
            return 'Android Device';
        }
        
        // Desktop detection
        if (/Windows NT/.test(userAgent)) {
            if (/Windows NT 10/.test(userAgent)) return 'Windows 10/11 PC';
            if (/Windows NT 6/.test(userAgent)) return 'Windows 7/8 PC';
            return 'Windows PC';
        }
        
        if (/Macintosh/.test(userAgent)) {
            if (/Intel/.test(userAgent)) return 'Intel Mac';
            if (/Apple/.test(userAgent)) return 'Apple Silicon Mac';
            return 'Mac Computer';
        }
        
        if (/Linux/.test(userAgent)) {
            return 'Linux Computer';
        }
        
        return 'Unknown Device';
    }
    
    /**
     * Generate unique device fingerprint
     */
    function generateDeviceFingerprint() {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        ctx.textBaseline = 'top';
        ctx.font = '14px Arial';
        ctx.fillText('Device Fingerprint', 2, 2);
        
        const fingerprint = [
            navigator.userAgent,
            navigator.language,
            screen.width + 'x' + screen.height,
            screen.colorDepth,
            new Date().getTimezoneOffset(),
            navigator.platform,
            navigator.cookieEnabled,
            navigator.hardwareConcurrency || 0,
            canvas.toDataURL()
        ].join('|');
        
        // Create hash from fingerprint
        let hash = 0;
        for (let i = 0; i < fingerprint.length; i++) {
            const char = fingerprint.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash; // Convert to 32-bit integer
        }
        
        return 'FP_' + Math.abs(hash).toString(36).toUpperCase();
    }
    
    /**
     * Get IP and location information
     */
    function getIPAndLocation() {
        // Try to get IP and location from multiple sources
        const sources = [
            'https://ipapi.co/json/',
            'https://freegeoip.app/json/',
            'https://ipinfo.io/json'
        ];
        
        sources.forEach(function(url, index) {
            $.ajax({
                url: url,
                method: 'GET',
                timeout: 5000,
                success: function(data) {
                    if (data && data.ip) {
                        deviceInfo.ip = data.ip;
                        deviceInfo.country = data.country_name || data.country;
                        deviceInfo.city = data.city;
                        deviceInfo.region = data.region || data.region_name;
                        deviceInfo.isp = data.org || data.isp;
                        deviceInfo.location_string = `${data.city || ''}, ${data.region || ''}, ${data.country_name || data.country || ''}`;
                        
                        console.log('📍 Location info retrieved from source', index + 1, deviceInfo);
                    }
                },
                error: function() {
                    console.log('⚠️ Failed to get location from source', index + 1);
                }
            });
            });
    }
    
    /**
     * Get element identifier
     */
    function getElementIdentifier(element) {
        return element.attr('id') || element.attr('name') || element.prop('tagName').toLowerCase() + '_' + element.index();
    }
    
    /**
     * Send heartbeat
     */
    function sendHeartbeat() {
        trackAdvancedEvent('heartbeat_detailed', null, {
            active_time: Math.round((new Date() - lastActivityTime) / 1000),
            session_duration: Math.round((new Date() - sessionStartTime) / 1000),
            form_progress: calculateFormProgress(),
            interaction_depth: behaviorMetrics.interactionDepth,
            mouse_movements: mouseMovements.length,
            scroll_events: scrollEvents.length
        });
    }
    
    /**
     * Update session data
     */
    function updateSessionData() {
        localStorage.setItem('mg_last_activity', Date.now().toString());
        lastActivityTime = new Date();
    }
    
    /**
     * Track advanced events with comprehensive data collection
     */
    function trackAdvancedEvent(eventType, elementId = null, additionalData = {}) {
        if (!trackingEnabled || !userSessionId) {
            console.log('⚠️ Tracking disabled or no session ID');
            return;
        }
        
        lastActivityTime = new Date();
        
        // Update localStorage activity timestamp
        localStorage.setItem('mg_last_activity', Date.now().toString());
        
        // Prepare comprehensive tracking data
        let trackingData = {
            action: 'track_user_progress',
            session_id: userSessionId,
            event_type: eventType,
            element_id: elementId,
            page_url: window.location.href,
            page_title: document.title,
            referrer: document.referrer,
            timestamp: new Date().toISOString(),
            
            // Device and system information
            viewport_width: window.innerWidth,
            viewport_height: window.innerHeight,
            screen_width: screen.width,
            screen_height: screen.height,
            device_pixel_ratio: window.devicePixelRatio || 1,
            window_focus: document.hasFocus(),
            
            // New location and device data
            ip_country: deviceInfo.country || '',
            ip_city: deviceInfo.city || '',
            ip_region: deviceInfo.region || '',
            ip_isp: deviceInfo.isp || '',
            ip_location_string: deviceInfo.location_string || '',
            device_model: deviceInfo.device_model || '',
            device_fingerprint: deviceInfo.device_fingerprint || '',
            
            // Browser and performance data
            user_agent: navigator.userAgent,
            language: navigator.language,
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
            connection_type: deviceInfo.connection_type || 'unknown',
            network_speed: deviceInfo.network_speed || 'unknown',
            cpu_class: deviceInfo.cpu_class || 'unknown',
            memory_size: deviceInfo.memory_size || 0,
            battery_level: deviceInfo.battery_level || 0,
            touch_support: deviceInfo.touch_support ? 1 : 0,
            orientation: deviceInfo.orientation || 'unknown',
            
            // Behavioral metrics
            confidence_score: calculateConfidenceScore(),
            bot_score: calculateBotScore(),
            fraud_indicators: JSON.stringify(detectFraudIndicators()),
            
            // Session metrics
            session_duration: Math.round((new Date() - sessionStartTime) / 1000),
            interaction_depth: behaviorMetrics.interactionDepth,
            
            // Advanced data structures as JSON
            browser_info: JSON.stringify(browserInfo),
            device_info: JSON.stringify(deviceInfo)
        };
        
        // Add element-specific data if available
        if (elementId) {
            let element = $(elementId);
            if (element.length === 0) {
                element = $('[id="' + elementId + '"], [name="' + elementId + '"]');
            }
            if (element.length > 0) {
                trackingData.element_name = element.attr('name') || element.attr('id') || '';
                trackingData.element_type = element.attr('type') || element.prop('tagName').toLowerCase();
                trackingData.element_value = element.val() || element.text() || '';
                
                // Previous value tracking
                if (previousValues[elementId]) {
                    trackingData.previous_value = previousValues[elementId];
                }
                previousValues[elementId] = trackingData.element_value;
            }
        }
        
        // Merge additional data
        Object.assign(trackingData, additionalData);
        
        // Send to server
        $.ajax({
            url: getAjaxUrl(),
            method: 'POST',
            data: trackingData,
            success: function(response) {
                if (response.success) {
                    console.log('✅ Advanced event tracked:', eventType, response.data);
                } else {
                    console.log('⚠️ Tracking response:', response);
                }
            },
            error: function(xhr, status, error) {
                console.log('❌ Tracking failed:', error);
                console.log('📋 Failed data:', trackingData);
            }
        });
    }
    
    /**
     * Calculate user confidence score (0-100)
     */
    function calculateConfidenceScore() {
        let score = 50; // Base score
        
        // Positive indicators
        if (behaviorMetrics.interactionDepth > 5) score += 10;
        if (Object.keys(fieldValues).length > 2) score += 15;
        if (behaviorMetrics.copyPasteCount === 0) score += 10;
        if (behaviorMetrics.rageClickCount === 0) score += 10;
        if (mouseMovements.length > 10) score += 5;
        
        // Negative indicators
        if (behaviorMetrics.rageClickCount > 2) score -= 20;
        if (behaviorMetrics.copyPasteCount > 3) score -= 15;
        if (errorEvents.length > 0) score -= 10;
        
        return Math.max(0, Math.min(100, score));
    }
    
    /**
     * Calculate bot probability score (0-100)
     */
    function calculateBotScore() {
        let score = 0; // Assume human
        
        // Bot indicators
        if (behaviorMetrics.interactionDepth === 0) score += 30;
        if (mouseMovements.length === 0) score += 25;
        if (behaviorMetrics.copyPasteCount > 5) score += 20;
        if (Object.keys(fieldValues).length > 10 && (new Date() - formStartTime) < 5000) score += 30;
        if (navigator.webdriver) score += 40;
        if (navigator.languages && navigator.languages.length === 0) score += 20;
        
        // Human indicators (reduce score)
        if (mouseMovements.length > 20) score -= 15;
        if (behaviorMetrics.interactionDepth > 10) score -= 20;
        if (keystrokeBuffer.length > 0) score -= 10;
        
        return Math.max(0, Math.min(100, score));
    }
    
    /**
     * Detect fraud indicators
     */
    function detectFraudIndicators() {
        const indicators = [];
        
        if (navigator.webdriver) indicators.push('webdriver_detected');
        if (behaviorMetrics.rageClickCount > 5) indicators.push('excessive_rage_clicks');
        if (behaviorMetrics.copyPasteCount > 10) indicators.push('excessive_copy_paste');
        if (mouseMovements.length === 0 && behaviorMetrics.interactionDepth > 0) indicators.push('no_mouse_movement');
        if (errorEvents.length > 5) indicators.push('excessive_errors');
        if (deviceInfo.touch_support && mouseMovements.length > 100) indicators.push('inconsistent_input_method');
        
        return indicators;
    }
    
    /**
     * Get AJAX URL with fallbacks
     */
    function getAjaxUrl() {
        if (typeof marketGoogleTracking !== 'undefined' && marketGoogleTracking.ajaxUrl) {
            return marketGoogleTracking.ajaxUrl;
        }
        if (typeof marketGoogleTracking !== 'undefined' && marketGoogleTracking.ajax_url) {
            return marketGoogleTracking.ajax_url;
        }
        if (typeof marketTrackingVars !== 'undefined' && marketTrackingVars.ajaxUrl) {
            return marketTrackingVars.ajaxUrl;
        }
        return window.location.origin + '/wp-admin/admin-ajax.php';
    }
    
    // Public API
    window.MarketGoogleAdvancedTracking = {
        trackEvent: trackAdvancedEvent,
        getSessionId: function() { return userSessionId; },
        isEnabled: function() { return trackingEnabled; },
        getBehaviorMetrics: function() { return behaviorMetrics; },
        getDeviceInfo: function() { return deviceInfo; },
        getFormProgress: calculateFormProgress,
        getConfidenceScore: calculateConfidenceScore,
        getBotScore: calculateBotScore
    };
    
    console.log('✅ Market Google Advanced Tracking Script Loaded Successfully');
    
    /**
     * Simulate a test user scenario for demonstration
     * This shows how the system tracks users like "علیرضا فرجامی"
     */
    function simulateUserScenario() {
        console.log('🧪 شروع شبیه‌سازی سناریو کاربر علیرضا فرجامی...');
        
        setTimeout(function() {
            // User focuses on full name field
            trackAdvancedEvent('field_focus', 'full_name', {
                conversion_funnel_step: 'personal_info_start'
            });
            
            setTimeout(function() {
                // User types their full name
                trackAdvancedEvent('field_input', 'full_name', {
                    element_name: 'full_name',
                    element_value: 'علیرضا فرجامی',
                    form_progress: 20,
                    conversion_funnel_step: 'personal_info'
                });
                
                setTimeout(function() {
                    // User moves to phone field
                    trackAdvancedEvent('field_focus', 'phone', {
                        conversion_funnel_step: 'contact_info_start'
                    });
                    
                    setTimeout(function() {
                        trackAdvancedEvent('field_input', 'phone', {
                            element_name: 'phone',
                            element_value: '09123456789',
                            form_progress: 40,
                            conversion_funnel_step: 'contact_info'
                        });
                        }, 2000);
                }, 1000);
            }, 1000);
        }, 500);
    }
    
})(jQuery); 