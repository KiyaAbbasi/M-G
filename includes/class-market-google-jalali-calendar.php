<?php
/**
 * کلاس تبدیل تاریخ شمسی به میلادی و بالعکس
 * 
 * @package Market_Google_Location
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('MarketGoogleJalaliCalendar')) {

class MarketGoogleJalaliCalendar {
    
    /**
     * تبدیل تاریخ میلادی به شمسی
     * 
     * @param int $g_y سال میلادی
     * @param int $g_m ماه میلادی
     * @param int $g_d روز میلادی
     * @return array آرایه‌ای از سال، ماه و روز شمسی
     */
    public static function gregorian_to_jalali($g_y, $g_m, $g_d) {
        $g_days_in_month = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
        $j_days_in_month = array(31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29);
        
        $gy = $g_y - 1600;
        $gm = $g_m - 1;
        $gd = $g_d - 1;
        
        $g_day_no = 365 * $gy + self::div($gy + 3, 4) - self::div($gy + 99, 100) + self::div($gy + 399, 400);
        
        for ($i = 0; $i < $gm; ++$i) {
            $g_day_no += $g_days_in_month[$i];
        }
        
        if ($gm > 1 && (($gy % 4 == 0 && $gy % 100 != 0) || ($gy % 400 == 0))) {
            $g_day_no++;
        }
        
        $g_day_no += $gd;
        
        $j_day_no = $g_day_no - 79;
        
        $j_np = self::div($j_day_no, 12053);
        $j_day_no = $j_day_no % 12053;
        
        $jy = 979 + 33 * $j_np + 4 * self::div($j_day_no, 1461);
        
        $j_day_no %= 1461;
        
        if ($j_day_no >= 366) {
            $jy += self::div($j_day_no - 1, 365);
            $j_day_no = ($j_day_no - 1) % 365;
        }
        
        for ($i = 0; $i < 11 && $j_day_no >= $j_days_in_month[$i]; ++$i) {
            $j_day_no -= $j_days_in_month[$i];
        }
        
        $jm = $i + 1;
        $jd = $j_day_no + 1;
        
        return array($jy, $jm, $jd);
    }
    
    /**
     * تبدیل تاریخ شمسی به میلادی - نسخه اصلاح شده
     * 
     * @param int $j_y سال شمسی
     * @param int $j_m ماه شمسی
     * @param int $j_d روز شمسی
     * @return array آرایه‌ای از سال، ماه و روز میلادی
     */
    public static function jalali_to_gregorian($j_y, $j_m, $j_d) {
        $g_days_in_month = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
        $j_days_in_month = array(31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29);
        
        $jy = $j_y - 979;
        $jm = $j_m - 1;
        $jd = $j_d - 1;
        
        $j_day_no = 365 * $jy + self::div($jy, 33) * 8 + self::div($jy % 33 + 3, 4);
        
        for ($i = 0; $i < $jm; ++$i) {
            $j_day_no += $j_days_in_month[$i];
        }
        
        $j_day_no += $jd;
        
        $g_day_no = $j_day_no + 79;
        
        $gy = 1600 + 400 * self::div($g_day_no, 146097);
        $g_day_no = $g_day_no % 146097;
        
        $leap = true;
        if ($g_day_no >= 36525) {
            $g_day_no--;
            $gy += 100 * self::div($g_day_no, 36524);
            $g_day_no = $g_day_no % 36524;
            
            if ($g_day_no >= 365) {
                $g_day_no++;
            } else {
                $leap = false;
            }
        }
        
        $gy += 4 * self::div($g_day_no, 1461);
        $g_day_no %= 1461;
        
        if ($g_day_no >= 366) {
            $leap = false;
            
            $g_day_no--;
            $gy += self::div($g_day_no, 365);
            $g_day_no = $g_day_no % 365;
        }
        
        for ($i = 0; $g_day_no >= $g_days_in_month[$i] + ($i == 1 && $leap); $i++) {
            $g_day_no -= $g_days_in_month[$i] + ($i == 1 && $leap);
        }
        
        $gm = $i + 1;
        $gd = $g_day_no + 1;
        
        return array($gy, $gm, $gd);
    }
    
    /**
     * تبدیل تاریخ شمسی به میلادی - نسخه ساده و درست
     * 
     * @param int $jy سال شمسی
     * @param int $jm ماه شمسی  
     * @param int $jd روز شمسی
     * @return array آرایه‌ای از سال، ماه و روز میلادی
     */
    public static function jalali_to_gregorian_simple($jy, $jm, $jd) {
        // استفاده از datetime برای محاسبه دقیق
        // 1 فروردین 1 = 22 مارس 622
        $epoch_diff = 227015; // تفاوت روز بین شروع تقویم جلالی و میلادی
        
        // محاسبه تعداد روز از شروع تقویم جلالی
        $days = 0;
        
        // سال‌ها
        for ($y = 1; $y < $jy; $y++) {
            if (self::is_leap_jalali($y)) {
                $days += 366;
            } else {
                $days += 365;
            }
        }
        
        // ماه‌ها
        $j_days_in_month = array(31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29);
        if (self::is_leap_jalali($jy)) {
            $j_days_in_month[11] = 30; // اسفند در سال کبیسه
        }
        
        for ($m = 1; $m < $jm; $m++) {
            $days += $j_days_in_month[$m - 1];
        }
        
        // روزها
        $days += $jd - 1;
        
        // تاریخ میلادی مرجع: 22 مارس 622 = روز اول فروردین سال 1
        $reference_timestamp = mktime(0, 0, 0, 3, 22, 622);
        $target_timestamp = $reference_timestamp + ($days * 24 * 60 * 60);
        
        return array(
            (int)date('Y', $target_timestamp),
            (int)date('n', $target_timestamp), 
            (int)date('j', $target_timestamp)
        );
    }
    
    /**
     * بررسی سال کبیسه شمسی
     * 
     * @param int $year سال شمسی
     * @return bool
     */
    public static function is_leap_jalali($year) {
        $breaks = array(-14, 3, 13, 84, 111, 181, 210, 216, 244, 304, 317, 356, 358, 375, 403, 436, 447, 460, 473, 507, 516, 518, 571, 596, 607, 653, 656, 685, 700, 749, 762, 782, 792, 810, 844, 848, 851, 867, 889, 903, 905, 924, 934, 972, 975, 1001, 1016, 1020, 1047, 1049, 1071, 1089, 1113, 1121, 1132, 1161, 1164, 1210, 1212, 1218, 1224, 1258, 1264, 1283, 1292, 1307, 1313, 1316, 1326, 1342, 1343, 1347, 1348, 1349, 1361, 1363, 1370, 1371, 1373, 1392, 1410, 1411, 1412, 1439, 1441, 1464, 1471, 1472, 1473, 1478, 1479, 1493, 1496, 1501, 1502, 1506, 1507, 1512, 1514, 1516, 1518, 1537, 1545, 1546, 1547, 1548, 1549, 1550, 1551, 1553, 1554, 1555, 1556, 1558, 1559, 1560, 1565, 1569, 1570, 1571, 1574, 1575, 1576, 1577, 1578, 1579, 1580, 1581, 1583, 1584, 1585, 1586, 1587, 1588, 1589, 1590, 1591, 1592, 1593, 1594, 1595, 1596, 1597, 1598, 1599, 1600, 3000);
        
        $jp = 0;
        $j = 0;
        
        for ($j = 1; $j < count($breaks); $j++) {
            $jm = $breaks[$j];
            $jump = $jm - $breaks[$j - 1];
            
            if ($year < $jm) {
                break;
            }
            
            $jp = $jp + $jump;
        }
        
        $n = $year - $breaks[$j - 1];
        
        if ($n < $jump) {
            $jp = $jp + $n;
        } else {
            $jp = $jp + $jump;
        }
        
        if (($jp + 1) % 33 == 0 || ($jp + 1) % 33 == 1 || ($jp + 1) % 33 == 2 || ($jp + 1) % 33 == 3) {
            return false;
        }
        
        if (($jp + 1) % 33 > 3 && ($jp + 1) % 33 < 32) {
            return (($jp + 1) % 33) % 4 == 0;
        }
        
        return false;
    }
    
    /**
     * تقسیم صحیح بدون اعشار
     * 
     * @param int $a عدد اول
     * @param int $b عدد دوم
     * @return int نتیجه تقسیم صحیح
     */
    private static function div($a, $b) {
        return (int) ($a / $b);
    }
    
    /**
     * تبدیل تاریخ میلادی به شمسی با فرمت دلخواه
     * 
     * @param string $format فرمت خروجی
     * @param string $date تاریخ میلادی (Y-m-d)
     * @return string تاریخ شمسی فرمت شده
     */
    public static function format_date($format, $date) {
        if (empty($date)) {
            return '';
        }
        
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return '';
        }
        
        $date_parts = explode('-', date('Y-m-d', $timestamp));
        if (count($date_parts) !== 3) {
            return '';
        }
        
        list($gy, $gm, $gd) = $date_parts;
        list($jy, $jm, $jd) = self::gregorian_to_jalali($gy, $gm, $gd);
        
        $result = $format;
        $result = str_replace('Y', $jy, $result);
        $result = str_replace('m', str_pad($jm, 2, '0', STR_PAD_LEFT), $result);
        $result = str_replace('d', str_pad($jd, 2, '0', STR_PAD_LEFT), $result);
        $result = str_replace('n', $jm, $result);
        $result = str_replace('j', $jd, $result);
        
        return $result;
    }
}

} 