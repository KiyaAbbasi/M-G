<?php
/**
 * صفحات نتیجه پرداخت - طراحی مدرن و مینیمال
 */

// بررسی دسترسی مستقیم
if (!defined('WPINC')) {
    die;
}

class Market_Google_Payment_Pages {
    
    /**
     * نمایش صفحه نتیجه پرداخت
     */
    public static function display_payment_result() {
        // دریافت پارامترها از URL
        $payment_result = isset($_GET['payment_result']) ? sanitize_text_field($_GET['payment_result']) : '';
        $location_id = isset($_GET['location_id']) ? intval($_GET['location_id']) : 0;
        $transaction_id = isset($_GET['transaction_id']) ? sanitize_text_field($_GET['transaction_id']) : '';
        $gateway = isset($_GET['gateway']) ? sanitize_text_field($_GET['gateway']) : '';
        $ref_id = isset($_GET['ref_id']) ? sanitize_text_field($_GET['ref_id']) : '';
        $amount = isset($_GET['amount']) ? intval($_GET['amount']) : 0;
        $user_name = isset($_GET['user_name']) ? sanitize_text_field($_GET['user_name']) : '';
        $business_name = isset($_GET['business_name']) ? sanitize_text_field($_GET['business_name']) : '';
        $error = isset($_GET['error']) ? sanitize_text_field($_GET['error']) : '';
        
        // تعیین نوع صفحه
        switch($payment_result) {
            case 'success':
                self::display_success_page($location_id, $transaction_id, $gateway, $ref_id, $amount, $user_name, $business_name);
                break;
            case 'failed':
                self::display_failed_page($transaction_id, $gateway, $error, $amount, $user_name, $business_name);
                break;
            case 'canceled':
                self::display_canceled_page($transaction_id, $gateway, $amount, $user_name, $business_name);
                break;
            case 'pending':
                self::display_pending_page($transaction_id, $gateway, $amount, $user_name, $business_name);
                break;
            case 'error':
                self::display_error_page($error, $transaction_id, $gateway, $amount, $user_name, $business_name);
                break;
            default:
                self::display_unknown_page();
        }
    }
    
    /**
     * صفحه موفقیت پرداخت
     */
    private static function display_success_page($location_id, $transaction_id, $gateway, $ref_id, $amount, $user_name, $business_name) {
        ?>
        <!DOCTYPE html>
        <html lang="fa" dir="rtl">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>پرداخت موفق - Market Google</title>
            <?php self::include_styles(); ?>
        </head>
        <body>
            <div class="container">
                <div class="logo">
                    <h1>Market Google</h1>
                    <p>سیستم ثبت کسب‌وکار در نقشه‌های آنلاین</p>
                </div>
                
                <div class="payment-result-container success">
                    <div class="result-header">
                        <div class="result-icon success-icon">
                            <svg width="80" height="80" viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="12" r="10" fill="#10B981"/>
                                <path d="M9 12l2 2 4-4" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h1>پرداخت موفق!</h1>
                        <p class="result-subtitle">تبریک! پرداخت شما با موفقیت انجام شد و کسب‌وکار شما ثبت گردید.</p>
                    </div>
                    
                    <div class="result-details">
                        <h3>جزئیات تراکنش</h3>
                        
                        <?php if (!empty($user_name)): ?>
                        <div class="detail-row">
                            <span class="detail-label">نام متقاضی:</span>
                            <span class="detail-value"><?php echo esc_html($user_name); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($business_name)): ?>
                        <div class="detail-row">
                            <span class="detail-label">نام کسب‌وکار:</span>
                            <span class="detail-value"><?php echo esc_html($business_name); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($transaction_id)): ?>
                        <div class="detail-row">
                            <span class="detail-label">شماره تراکنش:</span>
                            <span class="detail-value"><?php echo esc_html($transaction_id); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($ref_id)): ?>
                        <div class="detail-row">
                            <span class="detail-label">شماره مرجع:</span>
                            <span class="detail-value"><?php echo esc_html($ref_id); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="detail-row">
                            <span class="detail-label">مبلغ پرداختی:</span>
                            <span class="detail-value"><?php echo number_format($amount); ?> تومان</span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">درگاه پرداخت:</span>
                            <span class="detail-value">
                                <?php 
                                echo $gateway === 'bmi' ? 'بانک ملی ایران (سداد)' : 
                                    ($gateway === 'zarinpal' ? 'زرین‌پال' : 'نامشخص');
                                ?>
                            </span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">تاریخ و زمان:</span>
                            <span class="detail-value"><?php echo jdate('Y/m/d - H:i'); ?></span>
                        </div>
                        
                        <div class="detail-row highlight">
                            <span class="detail-label">وضعیت:</span>
                            <span class="detail-value status-success">تایید شده و فعال</span>
                        </div>
                    </div>
                    
                    <div class="success-message">
                        <div class="message-item">
                            <span class="message-icon">📍</span>
                            <span>کسب‌وکار شما در نقشه‌های معتبر قرار خواهد گرفت</span>
                        </div>
                        <div class="message-item">
                            <span class="message-icon">📧</span>
                            <span>رسید پرداخت به شماره تماس شما ارسال خواهد شد</span>
                        </div>
                        <div class="message-item">
                            <span class="message-icon">⏰</span>
                            <span>حداکثر تا 24 ساعت آینده کسب‌وکار شما فعال خواهد شد</span>
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <a href="<?php echo home_url(); ?>" class="btn btn-primary">بازگشت به صفحه اصلی</a>
                        <a href="#" onclick="window.print()" class="btn btn-secondary">چاپ رسید</a>
                    </div>
                </div>
            </div>
            
            <?php self::include_scripts(); ?>
        </body>
        </html>
        <?php
    }
    
    /**
     * صفحه شکست پرداخت
     */
    private static function display_failed_page($transaction_id, $gateway, $error, $amount, $user_name, $business_name) {
        ?>
        <!DOCTYPE html>
        <html lang="fa" dir="rtl">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>پرداخت ناموفق - Market Google</title>
            <?php self::include_styles(); ?>
        </head>
        <body>
            <div class="container">
                <div class="logo">
                    <h1>Market Google</h1>
                    <p>سیستم ثبت کسب‌وکار در نقشه‌های آنلاین</p>
                </div>
                
                <div class="payment-result-container failed">
                    <div class="result-header">
                        <div class="result-icon failed-icon">
                            <svg width="80" height="80" viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="12" r="10" fill="#EF4444"/>
                                <path d="M15 9l-6 6M9 9l6 6" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h1>پرداخت ناموفق</h1>
                        <p class="result-subtitle">متأسفانه پرداخت شما انجام نشد. لطفاً دوباره تلاش کنید.</p>
                    </div>
                    
                    <div class="result-details">
                        <h3>جزئیات تراکنش</h3>
                        
                        <?php if (!empty($user_name)): ?>
                        <div class="detail-row">
                            <span class="detail-label">نام متقاضی:</span>
                            <span class="detail-value"><?php echo esc_html($user_name); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($business_name)): ?>
                        <div class="detail-row">
                            <span class="detail-label">نام کسب‌وکار:</span>
                            <span class="detail-value"><?php echo esc_html($business_name); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($transaction_id)): ?>
                        <div class="detail-row">
                            <span class="detail-label">شماره تراکنش:</span>
                            <span class="detail-value"><?php echo esc_html($transaction_id); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="detail-row">
                            <span class="detail-label">مبلغ:</span>
                            <span class="detail-value"><?php echo number_format($amount); ?> تومان</span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">درگاه پرداخت:</span>
                            <span class="detail-value">
                                <?php 
                                echo $gateway === 'bmi' ? 'بانک ملی ایران (سداد)' : 
                                    ($gateway === 'zarinpal' ? 'زرین‌پال' : 'نامشخص');
                                ?>
                            </span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">تاریخ و زمان:</span>
                            <span class="detail-value"><?php echo jdate('Y/m/d - H:i'); ?></span>
                        </div>
                        
                        <div class="detail-row highlight">
                            <span class="detail-label">وضعیت:</span>
                            <span class="detail-value status-failed">ناموفق</span>
                        </div>
                        
                        <?php if (!empty($error)): ?>
                        <div class="detail-row error">
                            <span class="detail-label">علت خطا:</span>
                            <span class="detail-value"><?php echo esc_html($error); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="failed-reasons">
                        <h3>دلایل احتمالی شکست پرداخت:</h3>
                        <ul>
                            <li>موجودی ناکافی در حساب</li>
                            <li>اتصال اینترنت ناپایدار</li>
                            <li>انصراف از عملیات پرداخت</li>
                            <li>خطای موقت در درگاه پرداخت</li>
                            <li>اشتباه در وارد کردن اطلاعات کارت</li>
                        </ul>
                    </div>
                    
                    <div class="action-buttons">
                        <a href="<?php echo home_url(); ?>" class="btn btn-primary">تلاش مجدد</a>
                        <a href="#" class="btn btn-secondary">تماس با پشتیبانی</a>
                    </div>
                </div>
            </div>
            
            <?php self::include_scripts(); ?>
        </body>
        </html>
        <?php
    }
    
    /**
     * صفحه لغو پرداخت
     */
    private static function display_canceled_page($transaction_id, $gateway, $amount, $user_name, $business_name) {
        ?>
        <!DOCTYPE html>
        <html lang="fa" dir="rtl">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>لغو پرداخت - Market Google</title>
            <?php self::include_styles(); ?>
        </head>
        <body>
            <div class="container">
                <div class="logo">
                    <h1>Market Google</h1>
                    <p>سیستم ثبت کسب‌وکار در نقشه‌های آنلاین</p>
                </div>
                
                <div class="payment-result-container canceled">
                    <div class="result-header">
                        <div class="result-icon canceled-icon">
                            <svg width="80" height="80" viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="12" r="10" fill="#F59E0B"/>
                                <path d="M12 8v4M12 16h.01" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h1>پرداخت لغو شد</h1>
                        <p class="result-subtitle">شما عملیات پرداخت را لغو کردید. در صورت تمایل می‌توانید دوباره تلاش کنید.</p>
                    </div>
                    
                    <div class="result-details">
                        <h3>جزئیات تراکنش</h3>
                        
                        <?php if (!empty($user_name)): ?>
                        <div class="detail-row">
                            <span class="detail-label">نام متقاضی:</span>
                            <span class="detail-value"><?php echo esc_html($user_name); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($business_name)): ?>
                        <div class="detail-row">
                            <span class="detail-label">نام کسب‌وکار:</span>
                            <span class="detail-value"><?php echo esc_html($business_name); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($transaction_id)): ?>
                        <div class="detail-row">
                            <span class="detail-label">شماره تراکنش:</span>
                            <span class="detail-value"><?php echo esc_html($transaction_id); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="detail-row">
                            <span class="detail-label">مبلغ:</span>
                            <span class="detail-value"><?php echo number_format($amount); ?> تومان</span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">درگاه پرداخت:</span>
                            <span class="detail-value">
                                <?php 
                                echo $gateway === 'bmi' ? 'بانک ملی ایران (سداد)' : 
                                    ($gateway === 'zarinpal' ? 'زرین‌پال' : 'نامشخص');
                                ?>
                            </span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">تاریخ و زمان:</span>
                            <span class="detail-value"><?php echo jdate('Y/m/d - H:i'); ?></span>
                        </div>
                        
                        <div class="detail-row highlight">
                            <span class="detail-label">وضعیت:</span>
                            <span class="detail-value status-canceled">لغو شده</span>
                        </div>
                    </div>
                    
                    <div class="info-message">
                        <div class="message-item">
                            <span class="message-icon">ℹ️</span>
                            <span>هیچ مبلغی از حساب شما کسر نشده است</span>
                        </div>
                        <div class="message-item">
                            <span class="message-icon">🔄</span>
                            <span>می‌توانید در هر زمان دوباره اقدام به ثبت کسب‌وکار کنید</span>
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <a href="<?php echo home_url(); ?>" class="btn btn-primary">تلاش مجدد</a>
                        <a href="#" class="btn btn-secondary">بازگشت به صفحه اصلی</a>
                    </div>
                </div>
            </div>
            
            <?php self::include_scripts(); ?>
        </body>
        </html>
        <?php
    }
    
    /**
     * صفحه پرداخت در انتظار
     */
    private static function display_pending_page($transaction_id, $gateway, $amount, $user_name, $business_name) {
        ?>
        <!DOCTYPE html>
        <html lang="fa" dir="rtl">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>پرداخت در انتظار - Market Google</title>
            <?php self::include_styles(); ?>
        </head>
        <body>
            <div class="container">
                <div class="logo">
                    <h1>Market Google</h1>
                    <p>سیستم ثبت کسب‌وکار در نقشه‌های آنلاین</p>
                </div>
                
                <div class="payment-result-container pending">
                    <div class="result-header">
                        <div class="result-icon pending-icon">
                            <svg width="80" height="80" viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="12" r="10" fill="#3B82F6"/>
                                <path d="M12 6v6l4 2" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h1>پرداخت در انتظار</h1>
                        <p class="result-subtitle">پرداخت شما در حال بررسی است. لطفاً صبر کنید تا نتیجه نهایی اعلام شود.</p>
                    </div>
                    
                    <div class="result-details">
                        <h3>جزئیات تراکنش</h3>
                        
                        <?php if (!empty($user_name)): ?>
                        <div class="detail-row">
                            <span class="detail-label">نام متقاضی:</span>
                            <span class="detail-value"><?php echo esc_html($user_name); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($business_name)): ?>
                        <div class="detail-row">
                            <span class="detail-label">نام کسب‌وکار:</span>
                            <span class="detail-value"><?php echo esc_html($business_name); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($transaction_id)): ?>
                        <div class="detail-row">
                            <span class="detail-label">شماره تراکنش:</span>
                            <span class="detail-value"><?php echo esc_html($transaction_id); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="detail-row">
                            <span class="detail-label">مبلغ:</span>
                            <span class="detail-value"><?php echo number_format($amount); ?> تومان</span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">درگاه پرداخت:</span>
                            <span class="detail-value">
                                <?php 
                                echo $gateway === 'bmi' ? 'بانک ملی ایران (سداد)' : 
                                    ($gateway === 'zarinpal' ? 'زرین‌پال' : 'نامشخص');
                                ?>
                            </span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">تاریخ و زمان:</span>
                            <span class="detail-value"><?php echo jdate('Y/m/d - H:i'); ?></span>
                        </div>
                        
                        <div class="detail-row highlight">
                            <span class="detail-label">وضعیت:</span>
                            <span class="detail-value status-pending">در انتظار تایید</span>
                        </div>
                    </div>
                    
                    <div class="pending-message">
                        <div class="message-item">
                            <span class="message-icon">⏳</span>
                            <span>پرداخت شما در حال بررسی توسط بانک است</span>
                        </div>
                        <div class="message-item">
                            <span class="message-icon">📱</span>
                            <span>نتیجه نهایی از طریق پیامک اطلاع‌رسانی خواهد شد</span>
                        </div>
                        <div class="message-item">
                            <span class="message-icon">🔄</span>
                            <span>این صفحه هر 30 ثانیه به‌روزرسانی می‌شود</span>
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <a href="#" onclick="location.reload()" class="btn btn-primary">بررسی مجدد</a>
                        <a href="<?php echo home_url(); ?>" class="btn btn-secondary">بازگشت به صفحه اصلی</a>
                    </div>
                </div>
            </div>
            
            <script>
                // به‌روزرسانی خودکار هر 30 ثانیه
                setTimeout(function() {
                    location.reload();
                }, 30000);
            </script>
            
            <?php self::include_scripts(); ?>
        </body>
        </html>
        <?php
    }
    
    /**
     * صفحه خطای پرداخت
     */
    private static function display_error_page($error, $transaction_id, $gateway, $amount, $user_name, $business_name) {
        ?>
        <!DOCTYPE html>
        <html lang="fa" dir="rtl">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>خطای پرداخت - Market Google</title>
            <?php self::include_styles(); ?>
        </head>
        <body>
            <div class="container">
                <div class="logo">
                    <h1>Market Google</h1>
                    <p>سیستم ثبت کسب‌وکار در نقشه‌های آنلاین</p>
                </div>
                
                <div class="payment-result-container error">
                    <div class="result-header">
                        <div class="result-icon error-icon">
                            <svg width="80" height="80" viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="12" r="10" fill="#DC2626"/>
                                <path d="M12 8v4M12 16h.01" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h1>خطای پرداخت</h1>
                        <p class="result-subtitle">در فرآیند پرداخت خطایی رخ داده است. لطفاً با پشتیبانی تماس بگیرید.</p>
                    </div>
                    
                    <div class="result-details">
                        <h3>جزئیات خطا</h3>
                        
                        <?php if (!empty($user_name)): ?>
                        <div class="detail-row">
                            <span class="detail-label">نام متقاضی:</span>
                            <span class="detail-value"><?php echo esc_html($user_name); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($business_name)): ?>
                        <div class="detail-row">
                            <span class="detail-label">نام کسب‌وکار:</span>
                            <span class="detail-value"><?php echo esc_html($business_name); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($transaction_id)): ?>
                        <div class="detail-row">
                            <span class="detail-label">شماره تراکنش:</span>
                            <span class="detail-value"><?php echo esc_html($transaction_id); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="detail-row">
                            <span class="detail-label">مبلغ:</span>
                            <span class="detail-value"><?php echo number_format($amount); ?> تومان</span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">درگاه پرداخت:</span>
                            <span class="detail-value">
                                <?php 
                                echo $gateway === 'bmi' ? 'بانک ملی ایران (سداد)' : 
                                    ($gateway === 'zarinpal' ? 'زرین‌پال' : 'نامشخص');
                                ?>
                            </span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">تاریخ و زمان:</span>
                            <span class="detail-value"><?php echo jdate('Y/m/d - H:i'); ?></span>
                        </div>
                        
                        <div class="detail-row highlight">
                            <span class="detail-label">وضعیت:</span>
                            <span class="detail-value status-error">خطا</span>
                        </div>
                        
                        <?php if (!empty($error)): ?>
                        <div class="detail-row error">
                            <span class="detail-label">پیام خطا:</span>
                            <span class="detail-value"><?php echo esc_html($error); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="error-message">
                        <div class="message-item">
                            <span class="message-icon">⚠️</span>
                            <span>خطای سیستمی رخ داده است</span>
                        </div>
                        <div class="message-item">
                            <span class="message-icon">📞</span>
                            <span>لطفاً با پشتیبانی تماس بگیرید: 021 91 55 35 85</span>
                        </div>
                        <div class="message-item">
                            <span class="message-icon">💳</span>
                            <span>در صورت کسر مبلغ، طی 72 ساعت بازگردانده خواهد شد</span>
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <a href="tel:02191553585" class="btn btn-primary">تماس با پشتیبانی</a>
                        <a href="<?php echo home_url(); ?>" class="btn btn-secondary">بازگشت به صفحه اصلی</a>
                    </div>
                </div>
            </div>
            
            <?php self::include_scripts(); ?>
        </body>
        </html>
        <?php
    }
    
    /**
     * صفحه نامشخص
     */
    private static function display_unknown_page() {
        ?>
        <!DOCTYPE html>
        <html lang="fa" dir="rtl">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>نتیجه نامشخص - Market Google</title>
            <?php self::include_styles(); ?>
        </head>
        <body>
            <div class="container">
                <div class="logo">
                    <h1>Market Google</h1>
                    <p>سیستم ثبت کسب‌وکار در نقشه‌های آنلاین</p>
                </div>
                
                <div class="payment-result-container unknown">
                    <div class="result-header">
                        <div class="result-icon unknown-icon">
                            <svg width="80" height="80" viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="12" r="10" fill="#6B7280"/>
                                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3M12 17h.01" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h1>نتیجه نامشخص</h1>
                        <p class="result-subtitle">نتیجه پرداخت مشخص نیست. لطفاً با پشتیبانی تماس بگیرید.</p>
                    </div>
                    
                    <div class="action-buttons">
                        <a href="tel:02191553585" class="btn btn-primary">تماس با پشتیبانی</a>
                        <a href="<?php echo home_url(); ?>" class="btn btn-secondary">بازگشت به صفحه اصلی</a>
                    </div>
                </div>
            </div>
            
            <?php self::include_scripts(); ?>
        </body>
        </html>
        <?php
    }
    
    /**
     * اضافه کردن استایل‌ها
     */
    private static function include_styles() {
        ?>
        <style>
            /* فونت Vazir */
            @import url('https://cdn.jsdelivr.net/gh/rastikerdar/vazir-font@v30.1.0/dist/font-face.css');
            
            /* ریست و تنظیمات کلی */
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
                font-family: 'Vazir', Tahoma, sans-serif;
            }
            
            body {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: #1e293b;
                line-height: 1.6;
                padding: 20px;
                min-height: 100vh;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                direction: rtl;
            }
            
            .container {
                max-width: 100%;
                width: 100%;
                display: flex;
                flex-direction: column;
                align-items: center;
            }
            
            .logo {
                margin-bottom: 30px;
                text-align: center;
                color: white;
            }
            
            .logo h1 {
                font-size: 2rem;
                margin-bottom: 8px;
                font-weight: 700;
            }
            
            .logo p {
                font-size: 1rem;
                opacity: 0.9;
            }
            
            .payment-result-container {
                background: white;
                border-radius: 20px;
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
                width: 100%;
                max-width: 600px;
                overflow: hidden;
                animation: slideUp 0.6s ease-out;
            }
            
            @keyframes slideUp {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .result-header {
                padding: 40px 30px 30px;
                text-align: center;
                position: relative;
            }
            
            .result-icon {
                margin-bottom: 20px;
                display: inline-block;
                animation: bounce 1s ease-out;
            }
            
            @keyframes bounce {
                0%, 20%, 50%, 80%, 100% {
                    transform: translateY(0);
                }
                40% {
                    transform: translateY(-10px);
                }
                60% {
                    transform: translateY(-5px);
                }
            }
            
            .result-header h1 {
                font-size: 2rem;
                margin-bottom: 10px;
                font-weight: 700;
            }
            
            .result-subtitle {
                font-size: 1.1rem;
                color: #64748b;
                line-height: 1.5;
            }
            
            /* رنگ‌بندی برای انواع نتایج */
            .success .result-header h1 { color: #10B981; }
            .failed .result-header h1 { color: #EF4444; }
            .canceled .result-header h1 { color: #F59E0B; }
            .pending .result-header h1 { color: #3B82F6; }
            .error .result-header h1 { color: #DC2626; }
            .unknown .result-header h1 { color: #6B7280; }
            
            .result-details {
                margin: 30px;
                background: #f8fafc;
                border-radius: 12px;
                padding: 25px;
            }
            
            .result-details h3 {
                font-size: 1.3rem;
                margin-bottom: 20px;
                color: #374151;
                text-align: center;
                font-weight: 600;
            }
            
            .detail-row {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 12px 0;
                border-bottom: 1px solid #e5e7eb;
            }
            
            .detail-row:last-child {
                border-bottom: none;
            }
            
            .detail-row.highlight {
                background: rgba(59, 130, 246, 0.05);
                border-radius: 8px;
                padding: 15px 12px;
                margin: 10px 0;
                border: 1px solid rgba(59, 130, 246, 0.2);
            }
            
            .detail-row.error {
                background: rgba(239, 68, 68, 0.05);
                border-radius: 8px;
                padding: 15px 12px;
                margin: 10px 0;
                border: 1px solid rgba(239, 68, 68, 0.2);
            }
            
            .detail-label {
                font-weight: 600;
                color: #6b7280;
                font-size: 0.95rem;
            }
            
            .detail-value {
                font-weight: 500;
                color: #374151;
                font-size: 0.95rem;
            }
            
            .status-success { color: #10B981 !important; font-weight: 700 !important; }
            .status-failed { color: #EF4444 !important; font-weight: 700 !important; }
            .status-canceled { color: #F59E0B !important; font-weight: 700 !important; }
            .status-pending { color: #3B82F6 !important; font-weight: 700 !important; }
            .status-error { color: #DC2626 !important; font-weight: 700 !important; }
            
            .success-message,
            .failed-reasons,
            .info-message,
            .pending-message,
            .error-message {
                margin: 30px;
                border-radius: 12px;
                padding: 25px;
            }
            
            .success-message {
                background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
                border: 1px solid #10B981;
            }
            
            .failed-reasons {
                background: linear-gradient(135deg, #fef2f2 0%, #fecaca 100%);
                border: 1px solid #EF4444;
            }
            
            .info-message {
                background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
                border: 1px solid #F59E0B;
            }
            
            .pending-message {
                background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
                border: 1px solid #3B82F6;
            }
            
            .error-message {
                background: linear-gradient(135deg, #fef2f2 0%, #fecaca 100%);
                border: 1px solid #DC2626;
            }
            
            .message-item {
                display: flex;
                align-items: center;
                margin-bottom: 15px;
                font-size: 1rem;
            }
            
            .message-item:last-child {
                margin-bottom: 0;
            }
            
            .message-icon {
                font-size: 1.2rem;
                margin-left: 12px;
                flex-shrink: 0;
            }
            
            .failed-reasons h3 {
                color: #DC2626;
                margin-bottom: 15px;
                font-size: 1.1rem;
                font-weight: 600;
            }
            
            .failed-reasons ul {
                list-style: none;
                padding: 0;
                margin: 0;
            }
            
            .failed-reasons li {
                padding: 8px 0;
                color: #DC2626;
                font-size: 0.95rem;
                position: relative;
                padding-right: 20px;
            }
            
            .failed-reasons li:before {
                content: "•";
                color: #DC2626;
                font-weight: bold;
                position: absolute;
                right: 0;
            }
            
            .action-buttons {
                padding: 30px;
                text-align: center;
                background: #f8fafc;
                display: flex;
                gap: 15px;
                justify-content: center;
                flex-wrap: wrap;
            }
            
            .btn {
                display: inline-block;
                padding: 15px 30px;
                border-radius: 12px;
                text-decoration: none;
                font-weight: 600;
                font-size: 1rem;
                transition: all 0.3s ease;
                border: 2px solid;
                min-width: 160px;
                text-align: center;
            }
            
            .btn-primary {
                background: linear-gradient(135deg, #3B82F6 0%, #1D4ED8 100%);
                color: white;
                border-color: #3B82F6;
            }
            
            .btn-primary:hover {
                background: linear-gradient(135deg, #1D4ED8 0%, #1E40AF 100%);
                border-color: #1D4ED8;
                transform: translateY(-2px);
                box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
            }
            
            .btn-secondary {
                background: linear-gradient(135deg, #6B7280 0%, #4B5563 100%);
                color: white;
                border-color: #6B7280;
            }
            
            .btn-secondary:hover {
                background: linear-gradient(135deg, #4B5563 0%, #374151 100%);
                border-color: #4B5563;
                transform: translateY(-2px);
                box-shadow: 0 10px 20px rgba(107, 114, 128, 0.3);
            }
            
            /* ریسپانسیو */
            @media (max-width: 768px) {
                body {
                    padding: 10px;
                }
                
                .logo h1 {
                    font-size: 1.5rem;
                }
                
                .logo p {
                    font-size: 0.9rem;
                }
                
                .payment-result-container {
                    margin: 10px;
                }
                
                .result-header {
                    padding: 30px 20px 20px;
                }
                
                .result-header h1 {
                    font-size: 1.5rem;
                }
                
                .result-subtitle {
                    font-size: 1rem;
                }
                
                .result-details,
                .success-message,
                .failed-reasons,
                .info-message,
                .pending-message,
                .error-message {
                    margin: 20px;
                    padding: 20px;
                }
                
                .detail-row {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 5px;
                    padding: 10px 0;
                }
                
                .action-buttons {
                    padding: 20px;
                    flex-direction: column;
                }
                
                .btn {
                    width: 100%;
                    margin: 5px 0;
                }
            }
            
            @media print {
                body {
                    background: white;
                    padding: 0;
                }
                
                .logo {
                    color: #333;
                }
                
                .payment-result-container {
                    box-shadow: none;
                    border: 1px solid #ddd;
                }
                
                .action-buttons {
                    display: none;
                }
            }
        </style>
        <?php
    }
    
    /**
     * اضافه کردن اسکریپت‌ها
     */
    private static function include_scripts() {
        ?>
        <script>
            // انیمیشن‌های اضافی
            document.addEventListener('DOMContentLoaded', function() {
                // انیمیشن fade-in برای المان‌ها
                const elements = document.querySelectorAll('.detail-row, .message-item');
                elements.forEach((el, index) => {
                    el.style.opacity = '0';
                    el.style.transform = 'translateY(20px)';
                    setTimeout(() => {
                        el.style.transition = 'all 0.5s ease';
                        el.style.opacity = '1';
                        el.style.transform = 'translateY(0)';
                    }, index * 100);
                });
                
                // کپی کردن شماره تراکنش با کلیک
                const transactionElements = document.querySelectorAll('.detail-value');
                transactionElements.forEach(el => {
                    if (el.textContent.match(/^[0-9]+$/)) {
                        el.style.cursor = 'pointer';
                        el.title = 'کلیک کنید تا کپی شود';
                        el.addEventListener('click', function() {
                            navigator.clipboard.writeText(this.textContent).then(() => {
                                const originalText = this.textContent;
                                this.textContent = 'کپی شد!';
                                setTimeout(() => {
                                    this.textContent = originalText;
                                }, 1000);
                            });
                        });
                    }
                });
            });
        </script>
        <?php
    }
}

// فراخوانی کلاس
if (isset($_GET['payment_result'])) {
    Market_Google_Payment_Pages::display_payment_result();
    exit;
}
?>