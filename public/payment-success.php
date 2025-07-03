<?php
/**
 * صفحه موفقیت پرداخت - طراحی مدرن و مینیمال
 */

// دریافت پارامترها از URL
$location_id = isset($_GET['location_id']) ? intval($_GET['location_id']) : 0;
$transaction_id = isset($_GET['transaction_id']) ? sanitize_text_field($_GET['transaction_id']) : '';
$ref_id = isset($_GET['ref_id']) ? sanitize_text_field($_GET['ref_id']) : '';
$gateway = isset($_GET['gateway']) ? sanitize_text_field($_GET['gateway']) : '';
$amount = isset($_GET['amount']) ? intval($_GET['amount']) : 0;
$user_name = isset($_GET['user_name']) ? sanitize_text_field($_GET['user_name']) : '';
$business_name = isset($_GET['business_name']) ? sanitize_text_field($_GET['business_name']) : '';

// دریافت اطلاعات لوکیشن از دیتابیس
if ($location_id > 0) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'market_google_locations';
    $location = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $location_id));
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پرداخت موفق - Market Google</title>
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazir-font@v30.1.0/dist/font-face.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Vazir', Tahoma, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            background: linear-gradient(135deg, #10b981, #059669);
            padding: 30px 25px;
            text-align: center;
            color: white;
        }
        
        .success-icon {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 40px;
            animation: bounce 1s ease-in-out;
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
        
        .status-active {
            color: #10b981 !important;
            font-weight: 700 !important;
        }
        
        .success-message {
            background: linear-gradient(135deg, #dcfce7, #bbf7d0);
            border: 2px solid #10b981;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            text-align: center;
        }
        
        .success-message p {
            color: #065f46;
            margin: 8px 0;
            font-size: 0.95rem;
            line-height: 1.6;
        }
        
        .success-message .highlight {
            font-weight: 600;
            font-size: 1rem;
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
        
        .telegram-info {
            background: linear-gradient(135deg, #0088cc, #006bb3);
            color: white;
            border-radius: 12px;
            padding: 20px;
            margin: 25px 0;
            text-align: center;
        }
        
        .telegram-info h4 {
            font-size: 1.1rem;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .telegram-info p {
            font-size: 0.9rem;
            line-height: 1.5;
            margin: 5px 0;
        }
        
        .telegram-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.2);
            padding: 10px 20px;
            border-radius: 8px;
            color: white;
            text-decoration: none;
            margin-top: 15px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .telegram-link:hover {
            background: rgba(255,255,255,0.3);
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
// اضافه کردن قبل از </body>
<script>
// پاک کردن localStorage وقتی کاربر از درگاه برمی‌گردد
if (localStorage.getItem('market_location_form_data')) {
    localStorage.removeItem('market_location_form_data');
    console.log('✅ localStorage cleared after payment return');
}
</script>
</body>
</html>