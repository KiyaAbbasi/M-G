<?php
/**
 * تقویم جلالی حرفه‌ای برای پلاگین Market Google
 * 
 * @package MarketGoogle
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * کلاس تقویم جلالی حرفه‌ای
 */
class MarketGoogleJalaliCalendar {
    
    private static $jalali_months = [
        1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد', 4 => 'تیر',
        5 => 'مرداد', 6 => 'شهریور', 7 => 'مهر', 8 => 'آبان',
        9 => 'آذر', 10 => 'دی', 11 => 'بهمن', 12 => 'اسفند'
    ];
    
    private static $jalali_days = [
        0 => 'یکشنبه', 1 => 'دوشنبه', 2 => 'سه‌شنبه', 3 => 'چهارشنبه',
        4 => 'پنج‌شنبه', 5 => 'جمعه', 6 => 'شنبه'
    ];
    
    /**
     * تبدیل تاریخ میلادی به جلالی
     */
    public static function gregorian_to_jalali($gy, $gm, $gd) {
        $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        
        if ($gy <= 1600) {
            $jy = 0;
            $gy -= 621;
        } else {
            $jy = 979;
            $gy -= 1600;
        }
        
        if ($gm > 2) {
            $gy2 = ($gy + 1);
        } else {
            $gy2 = $gy;
        }
        
        $days = (365 * $gy) + ((int)(($gy2 + 3) / 4)) - ((int)(($gy2 + 99) / 100))
            + ((int)(($gy2 + 399) / 400)) - 80 + $gd + $g_d_m[$gm - 1];
        
        $jy += 33 * ((int)($days / 12053));
        $days %= 12053;
        
        $jy += 4 * ((int)($days / 1461));
        $days %= 1461;
        
        if ($days > 365) {
            $jy += (int)(($days - 1) / 365);
            $days = ($days - 1) % 365;
        }
        
        if ($days < 186) {
            $jm = 1 + (int)($days / 31);
            $jd = 1 + ($days % 31);
        } else {
            $jm = 7 + (int)(($days - 186) / 30);
            $jd = 1 + (($days - 186) % 30);
        }
        
        return [$jy, $jm, $jd];
    }
    
    /**
     * تبدیل تاریخ جلالی به میلادی
     */
    public static function jalali_to_gregorian($jy, $jm, $jd) {
        if ($jy <= 979) {
            $gy = 1600;
            $jy += 621;
        } else {
            $gy = 2000;
            $jy -= 979;
        }
        
        if ($jm < 7) {
            $days = ($jm - 1) * 31;
        } else {
            $days = ($jm - 7) * 30 + 186;
        }
        $days += (365 * $jy) + ((int)($jy / 33) * 8) + ((int)((($jy % 33) + 3) / 4)) + 78 + $jd;
        
        $gy += 400 * ((int)($days / 146097));
        $days %= 146097;
        
        if ($days >= 36525) {
            $days--;
            $gy += 100 * ((int)($days / 36524));
            $days %= 36524;
            if ($days >= 365) $days++;
        }
        
        $gy += 4 * ((int)($days / 1461));
        $days %= 1461;
        
        if ($days >= 366) {
            $days--;
            $gy += (int)($days / 365);
            $days = $days % 365;
        }
        
        $gd = $days + 1;
        
        $is_leap = ((($gy % 4) == 0) && ((($gy % 100) != 0) || (($gy % 400) == 0)));
        $sal_a = [0, 31, ($is_leap ? 29 : 28), 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        
        $gm = 0;
        while ($gm < 13 && $gd > $sal_a[$gm]) {
            $gd -= $sal_a[$gm];
            $gm++;
        }
        
        return [$gy, $gm, $gd];
    }
    
    /**
     * فرمت کردن تاریخ جلالی
     */
    public static function jdate($format, $timestamp = null) {
        if ($timestamp === null) {
            $timestamp = time();
        }
        
        $date = getdate($timestamp);
        list($jy, $jm, $jd) = self::gregorian_to_jalali($date['year'], $date['mon'], $date['mday']);
        
        $replacements = [
            'Y' => $jy,
            'y' => substr($jy, -2),
            'm' => sprintf('%02d', $jm),
            'n' => $jm,
            'd' => sprintf('%02d', $jd),
            'j' => $jd,
            'F' => self::$jalali_months[$jm],
            'M' => substr(self::$jalali_months[$jm], 0, 3),
            'l' => self::$jalali_days[$date['wday']],
            'D' => substr(self::$jalali_days[$date['wday']], 0, 3),
            'w' => $date['wday'],
            'H' => sprintf('%02d', $date['hours']),
            'h' => sprintf('%02d', $date['hours'] > 12 ? $date['hours'] - 12 : $date['hours']),
            'i' => sprintf('%02d', $date['minutes']),
            's' => sprintf('%02d', $date['seconds']),
            'A' => $date['hours'] >= 12 ? 'ب.ظ' : 'ق.ظ',
            'a' => $date['hours'] >= 12 ? 'pm' : 'am'
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $format);
    }
    
    /**
     * دریافت نام ماه جلالی
     */
    public static function get_month_name($month) {
        return isset(self::$jalali_months[$month]) ? self::$jalali_months[$month] : '';
    }
    
    /**
     * دریافت نام روز جلالی
     */
    public static function get_day_name($day) {
        return isset(self::$jalali_days[$day]) ? self::$jalali_days[$day] : '';
    }
    
    /**
     * بررسی سال کبیسه جلالی
     */
    public static function is_leap_year($year) {
        $breaks = [-61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181, 1210, 1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178];
        $jp = $breaks[0];
        $jump = 0;
        
        for ($j = 1; $j < count($breaks); $j++) {
            $jm = $breaks[$j];
            $jump = $jm - $jp;
            if ($year < $jm) break;
            $jp = $jm;
        }
        
        $n = $year - $jp;
        
        if ($n < $jump) {
            if ($jump - $n < 6) {
                $n = $n - $jump + ((int)(($jump + 4) / 6)) * 6;
            }
            
            $leap = ((($n + 1) % 6) == 0);
            if ($jump == 33 && (($n % 6) == 1)) {
                $leap = true;
            }
            
            return $leap;
        }
        
        return false;
    }
    
    /**
     * تعداد روزهای ماه جلالی
     */
    public static function get_month_days($year, $month) {
        if ($month <= 6) {
            return 31;
        } elseif ($month <= 11) {
            return 30;
        } else {
            return self::is_leap_year($year) ? 30 : 29;
        }
    }
}

/**
 * تابع jdate برای سازگاری با کدهای موجود
 */
if (!function_exists('jdate')) {
    function jdate($format, $timestamp = null) {
        return MarketGoogleJalaliCalendar::jdate($format, $timestamp);
    }
}

/**
 * تابع کمکی برای تبدیل تاریخ میلادی به جلالی
 */
if (!function_exists('gregorian_to_jalali')) {
    function gregorian_to_jalali($gy, $gm, $gd) {
        return MarketGoogleJalaliCalendar::gregorian_to_jalali($gy, $gm, $gd);
    }
}

/**
 * تابع کمکی برای تبدیل تاریخ جلالی به میلادی
 */
if (!function_exists('jalali_to_gregorian')) {
    function jalali_to_gregorian($jy, $jm, $jd) {
        return MarketGoogleJalaliCalendar::jalali_to_gregorian($jy, $jm, $jd);
    }
}