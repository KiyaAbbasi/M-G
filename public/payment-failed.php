<?php
/**
 * صفحه شکست پرداخت - طراحی مدرن و مینیمال
 */

// دریافت پارامترها از URL
$error = isset($_GET['error']) ? sanitize_text_field($_GET['error']) : '';
$gateway = isset($_GET['gateway']) ? sanitize_text_field($_GET['gateway']) : '';
$transaction_id = isset($_GET['transaction_id']) ? sanitize_text_field($_GET['transaction_id']) : '';
$user_name = isset($_GET['user_name']) ? sanitize_text_field($_GET['user_name']) : '';
$amount = isset($_GET['amount']) ? intval($_GET['amount']) : 0;
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پرداخت ناموفق - Market Google</title>
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazir-font@v30.1.0/dist/font-face.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Vazir', Tahoma, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            direction: rtl;
        }
        
        .container {
            max-width: 500px;
            width: 100%;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            color: white;
            font-size: 1.8rem;
            margin-bottom: 8px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .logo p {
            color: rgba(255,255,255,0.9);
            font-size: 1rem;
        }
        
        .payment-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
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
        
        .card-header {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            padding: 30px 25px;
            text-align: center;
            color: white;
        }
        
        .failed-icon {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 40px;
            animation: shake 1s ease-in-out;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        
        .card-header h2 {
            font-size: 1.8rem;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .card-header p {
            font-size: 1.1rem;
            opacity: 0.95;
            line-height: 1.5;
        }
        
        .card-body {
            padding: 30px 25px;
        }
        
        .error-info {
            background: #fef2f2;
            border: 2px solid #ef4444;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            text-align: center;
        }
        
        .error-info h3 {
            color: #dc2626;
            font-size: 1.2rem;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .error-message {
            color: #dc2626;
            font-size: 1rem;
            font-weight: 500;
            margin-bottom: 15px;
        }
        
        .failed-reasons {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .failed-reasons h3 {
            color: #1e293b;
            font-size: 1.2rem;
            margin-bottom: 15px;
            text-align: center;
            font-weight: 600;
        }
        
        .failed-reasons ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .failed-reasons li {
            padding: 10px 0;
            color: #64748b;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .failed-reasons li:before {
            content: "❌";
            font-size: 1rem;
        }
        
        .info-section {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .info-section h3 {
            color: #1e293b;
            font-size: 1.2rem;
            margin-bottom: 15px;
            text-align: center;
            font-weight: 600;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: #64748b;
            font-weight: 500;
            font-size: 0.95rem;
        }
        
        .info-value {
            color: #1e293b;
            font-weight: 600;
            font-size: 0.95rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            border: 2px solid;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-width: 140px;
            justify-content: center;
        }
        
        .btn-primary {
            background: #2563eb;
            color: white;
            border-color: #2563eb;
        }
        
        .btn-primary:hover {
            background: #1d4ed8;
            border-color: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3);
        }
        
        .btn-secondary {
            background: white;
            color: #6b7280;
            border-color: #d1d5db;
        }
        
        .btn-secondary:hover {
            background: #f9fafb;
            border-color: #9ca3af;
            transform: translateY(-2px);
        }
        
        .support-info {
            background: linear-gradient(135deg, #0088cc, #006bb3);
            color: white;
            border-radius: 12px;
            padding: 20px;
            margin: 25px 0;
            text-align: center;
        }
        
        .support-info h4 {
            font-size: 1.1rem;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .support-info p {
            font-size: 0.9rem;
            line-height: 1.5;
            margin: 5px 0;
        }
        
        @media (max-width: 768px) {
            .container {
                margin: 10px;
            }
            
            .card-header {
                padding: 25px 20px;
            }
            
            .card-body {
                padding: 25px 20px;
            }
            
            .info-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
                padding: 10px 0;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>Market Google</h1>
            <p>سیستم ثبت کسب و کار در نقشه</p>
        </div>
        
        <div class="payment-card">
            <div class="card-header">
                <div class="failed-icon">❌</div>
                <h2>پرداخت ناموفق</h2>
                <p>متأسفانه پرداخت شما انجام نشد</p>
            </div>
            
            <div class="card-body">
                <?php if (!empty($error)): ?>
                <div class="error-info">
                    <h3>⚠️ خطای رخ داده</h3>
                    <div class="error-message"><?php echo esc_html($error); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($transaction_id) || !empty($gateway) || $amount > 0): ?>
                <div class="info-section">
                    <h3>📋 جزئیات تراکنش</h3>
                    <?php if (!empty($user_name)): ?>
                    <div class="info-row">
                        <span class="info-label">نام کاربر:</span>
                        <span class="info-value"><?php echo esc_html($user_name); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($transaction_id)): ?>
                    <div class="info-row">
                        <span class="info-label">شماره تراکنش:</span>
                        <span class="info-value"><?php echo esc_html($transaction_id); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($gateway)): ?>
                    <div class="info-row">
                        <span class="info-label">درگاه پرداخت:</span>
                        <span class="info-value">
                            <?php 
                            echo $gateway === 'bmi' ? 'بانک ملی ایران (سداد)' : 
                                ($gateway === 'zarinpal' ? 'زرین‌پال' : 'نامشخص');
                            ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    <?php if ($amount > 0): ?>
                    <div class="info-row">
                        <span class="info-label">مبلغ:</span>
                        <span class="info-value"><?php echo number_format($amount); ?> تومان</span>
                    </div>
                    <?php endif; ?>
                    <div class="info-row">
                        <span class="info-label">وضعیت:</span>
                        <span class="info-value" style="color: #ef4444;">ناموفق</span>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="failed-reasons">
                    <h3>🔍 دلایل احتمالی شکست پرداخت</h3>
                    <ul>
                        <li>اتصال اینترنت ناپایدار یا قطع شده</li>
                        <li>موجودی ناکافی در حساب بانکی</li>
                        <li>انصراف از عملیات پرداخت</li>
                        <li>خطای موقت در درگاه پرداخت</li>
                        <li>اشتباه در وارد کردن اطلاعات کارت</li>
                        <li>مسدود بودن کارت یا حساب</li>
                    </ul>
                </div>
                
                <div class="support-info">
                    <h4>🆘 نیاز به کمک دارید؟</h4>
                    <p>تیم پشتیبانی ما آماده کمک به شماست</p>
                    <p>📞 تماس: 021 91 55 20 85</p>
                    <p>📱 تلگرام: @MarketGoogle_ir</p>
                </div>
                
                <div class="action-buttons">
                    <a href="/" class="btn btn-primary">
                        🔄 تلاش مجدد
                    </a>
                    <a href="tel:02191552085" class="btn btn-secondary">
                        📞 تماس با پشتیبانی
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>