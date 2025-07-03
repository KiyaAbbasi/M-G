<?php
/**
 * صفحه لغو پرداخت - طراحی مدرن و مینیمال
 */

// دریافت پارامترها از URL
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
    <title>لغو پرداخت - Market Google</title>
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazir-font@v30.1.0/dist/font-face.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Vazir', Tahoma, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
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
            background: linear-gradient(135deg, #f59e0b, #d97706);
            padding: 30px 25px;
            text-align: center;
            color: white;
        }
        
        .canceled-icon {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 40px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
            100% {
                transform: scale(1);
            }
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
        
        .canceled-message {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border: 2px solid #f59e0b;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            text-align: center;
        }
        
        .canceled-message h3 {
            color: #92400e;
            font-size: 1.2rem;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .canceled-message p {
            color: #92400e;
            margin: 8px 0;
            font-size: 0.95rem;
            line-height: 1.6;
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
                <div class="canceled-icon">⚠️</div>
                <h2>پرداخت لغو شد</h2>
                <p>عملیات پرداخت توسط شما لغو شد</p>
            </div>
            
            <div class="card-body">
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
                        <span class="info-value" style="color: #f59e0b;">لغو شده</span>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="canceled-message">
                    <h3>🔄 عملیات لغو شد</h3>
                    <p>شما عملیات پرداخت را لغو کردید</p>
                    <p>هیچ مبلغی از حساب شما کسر نشده است</p>
                    <p>می‌توانید دوباره تلاش کنید</p>
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
    
    <script>
        // پاک کردن localStorage وقتی کاربر از درگاه برمی‌گردد
        if (localStorage.getItem('market_location_form_data')) {
            localStorage.removeItem('market_location_form_data');
            console.log('✅ localStorage cleared after payment cancellation');
        }
    </script>
</body>
</html>