/**
 * کتابخانه تقویم جلالی حرفه‌ای
 * @version 1.0.0
 */

// بررسی وجود کلاس قبل از تعریف مجدد
if (typeof JalaliCalendar === 'undefined') {
    class JalaliCalendar {
        constructor() {
            this.jalaliMonths = [
                'فروردین', 'اردیبهشت', 'خرداد', 'تیر',
                'مرداد', 'شهریور', 'مهر', 'آبان',
                'آذر', 'دی', 'بهمن', 'اسفند'
            ];
            
            this.jalaliDays = [
                'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه',
                'پنج‌شنبه', 'جمعه', 'شنبه'
            ];
            
            this.jalaliDaysShort = [
                'ی', 'د', 'س', 'چ', 'پ', 'ج', 'ش'
            ];
        }
        
        /**
         * تبدیل تاریخ میلادی به جلالی - الگوریتم تصحیح شده
         */
        gregorianToJalali(gy, gm, gd) {
            const g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
            
            let jy, days;
            
            if (gy <= 1600) {
                jy = 0;
                gy -= 621;
            } else {
                jy = 979;
                gy -= 1600;
            }
            
            const gy2 = (gm > 2) ? (gy + 1) : gy;
            days = (365 * gy) + Math.floor((gy2 + 3) / 4) - Math.floor((gy2 + 99) / 100) + 
                   Math.floor((gy2 + 399) / 400) - 80 + gd + g_d_m[gm - 1];
            
            jy += 33 * Math.floor(days / 12053);
            days %= 12053;
            
            jy += 4 * Math.floor(days / 1461);
            days %= 1461;
            
            if (days >= 366) {
                jy += Math.floor((days - 1) / 365);
                days = (days - 1) % 365;
            }
            
            let jm, jd_result;
            if (days < 186) {
                jm = 1 + Math.floor(days / 31);
                jd_result = 1 + (days % 31);
            } else {
                jm = 7 + Math.floor((days - 186) / 30);
                jd_result = 1 + ((days - 186) % 30);
            }
            
            return [jy, jm, jd_result];
        }
        
        /**
         * تبدیل تاریخ جلالی به میلادی - الگوریتم تصحیح شده
         */
        jalaliToGregorian(jy, jm, jd) {
            let gy, days;
            
            if (jy <= 979) {
                gy = 1600;
                jy += 621;
            } else {
                gy = 2000;
                jy -= 979;
            }
            
            if (jm < 7) {
                days = (jm - 1) * 31;
            } else {
                days = (jm - 7) * 30 + 186;
            }
            
            days += (365 * jy) + Math.floor(jy / 33) * 8 + Math.floor(((jy % 33) + 3) / 4) + 78 + jd;
            
            if (jy <= 979) {
                days += Math.floor(jy / 4) - Math.floor(jy / 100) + Math.floor(jy / 400) - 80;
            }
            
            gy += 400 * Math.floor(days / 146097);
            days %= 146097;
            
            let leap = true;
            if (days >= 36525) {
                days--;
                gy += 100 * Math.floor(days / 36524);
                days %= 36524;
                if (days >= 365) {
                    days++;
                    leap = false;
                }
            }
            
            gy += 4 * Math.floor(days / 1461);
            days %= 1461;
            
            if (days >= 366) {
                leap = false;
                days--;
                gy += Math.floor(days / 365);
                days = days % 365;
            }
            
            let gd = days + 1;
            
            const isLeapYear = ((gy % 4) === 0) && (((gy % 100) !== 0) || ((gy % 400) === 0));
            const sal_a = [0, 31, (isLeapYear ? 29 : 28), 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
            
            let gm = 0;
            while (gm < 13 && gd > sal_a[gm]) {
                gd -= sal_a[gm];
                gm++;
            }
            
            return [gy, gm, gd];
        }
        
        /**
         * فرمت کردن تاریخ جلالی
         */
        jdate(format, timestamp = null) {
            if (timestamp === null) {
                timestamp = Date.now();
            }
            
            const date = new Date(timestamp);
            const [jy, jm, jd] = this.gregorianToJalali(date.getFullYear(), date.getMonth() + 1, date.getDate());
            
            const replacements = {
                'Y': jy,
                'y': jy.toString().substr(-2),
                'm': jm.toString().padStart(2, '0'),
                'n': jm,
                'd': jd.toString().padStart(2, '0'),
                'j': jd,
                'F': this.jalaliMonths[jm - 1],
                'M': this.jalaliMonths[jm - 1].substr(0, 3),
                'l': this.jalaliDays[date.getDay()],
                'D': this.jalaliDaysShort[date.getDay()],
                'w': date.getDay(),
                'H': date.getHours().toString().padStart(2, '0'),
                'h': (date.getHours() > 12 ? date.getHours() - 12 : date.getHours()).toString().padStart(2, '0'),
                'i': date.getMinutes().toString().padStart(2, '0'),
                's': date.getSeconds().toString().padStart(2, '0'),
                'A': date.getHours() >= 12 ? 'ب.ظ' : 'ق.ظ',
                'a': date.getHours() >= 12 ? 'pm' : 'am'
            };
            
            let result = format;
            for (const [key, value] of Object.entries(replacements)) {
                result = result.replace(new RegExp(key, 'g'), value);
            }
            
            return result;
        }
        
        /**
         * دریافت تاریخ جلالی امروز
         */
        today() {
            return this.jdate('Y/m/d');
        }
        
        /**
         * دریافت تاریخ و زمان جلالی کامل
         */
        now() {
            return this.jdate('Y/m/d H:i:s');
        }
        
        /**
         * بررسی سال کبیسه جلالی
         */
        isLeapYear(year) {
            const breaks = [-61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181, 1210, 1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178];
            let jp = breaks[0];
            let jump = 0;
            
            for (let j = 1; j < breaks.length; j++) {
                const jm = breaks[j];
                jump = jm - jp;
                if (year < jm) break;
                jp = jm;
            }
            
            const n = year - jp;
            
            if (n < jump) {
                if (jump - n < 6) {
                    const adjustedN = n - jump + Math.floor((jump + 4) / 6) * 6;
                    return ((adjustedN + 1) % 6) === 0;
                }
                
                let leap = ((n + 1) % 6) === 0;
                if (jump === 33 && (n % 6) === 1) {
                    leap = true;
                }
                
                return leap;
            }
            
            return false;
        }
        
        /**
         * تعداد روزهای ماه جلالی
         */
        getMonthDays(year, month) {
            if (month <= 6) {
                return 31;
            } else if (month <= 11) {
                return 30;
            } else {
                return this.isLeapYear(year) ? 30 : 29;
            }
        }
        
        /**
         * ایجاد date picker جلالی
         */
        createDatePicker(elementId, options = {}) {
            const element = document.getElementById(elementId);
            if (!element) return;
            
            const defaultOptions = {
                format: 'Y/m/d',
                placeholder: 'انتخاب تاریخ',
                showToday: true,
                rtl: true
            };
            
            const config = { ...defaultOptions, ...options };
            
            // ایجاد wrapper
            const wrapper = document.createElement('div');
            wrapper.className = 'jalali-datepicker-wrapper';
            wrapper.style.position = 'relative';
            wrapper.style.display = 'inline-block';
            
            // تنظیم input
            element.placeholder = config.placeholder;
            element.readOnly = true;
            element.style.cursor = 'pointer';
            element.style.direction = 'rtl';
            
            // ایجاد calendar popup
            const calendar = this.createCalendarPopup(config);
            
            // جایگزینی element با wrapper
            element.parentNode.insertBefore(wrapper, element);
            wrapper.appendChild(element);
            wrapper.appendChild(calendar);
            
            // Event handlers
            element.addEventListener('click', () => {
                calendar.style.display = calendar.style.display === 'block' ? 'none' : 'block';
            });
            
            document.addEventListener('click', (e) => {
                if (!wrapper.contains(e.target)) {
                    calendar.style.display = 'none';
                }
            });
            
            return {
                getValue: () => element.value,
                setValue: (value) => {
                    element.value = value;
                    element.dispatchEvent(new Event('change'));
                },
                destroy: () => {
                    wrapper.parentNode.insertBefore(element, wrapper);
                    wrapper.remove();
                }
            };
        }
        
        /**
         * ایجاد popup تقویم
         */
        createCalendarPopup(config) {
            const popup = document.createElement('div');
            popup.className = 'jalali-calendar-popup';
            popup.style.cssText = `
                display: none;
                position: absolute;
                top: 100%;
                right: 0;
                background: white;
                border: 1px solid #ddd;
                border-radius: 4px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                z-index: 1000;
                min-width: 280px;
                direction: rtl;
                font-family: 'Vazir', 'Tahoma', sans-serif;
            `;
            
            // تاریخ امروز
            const today = new Date();
            const [currentJY, currentJM, currentJD] = this.gregorianToJalali(today.getFullYear(), today.getMonth() + 1, today.getDate());
            
            // اضافه کردن تاریخ امروز به config
            config.currentJY = currentJY;
            config.currentJM = currentJM;
            config.currentJD = currentJD;
            config.currentYear = currentJY;
            config.currentMonth = currentJM;
            
            // ایجاد محتوای تقویم
            this.renderCalendar(popup, config);
            
            return popup;
        }
        
        /**
         * رندر کردن تقویم
         */
        renderCalendar(container, config) {
            let displayYear = config.currentYear;
            let displayMonth = config.currentMonth;
            
            let isRendering = false; // پرچم برای جلوگیری از render مضاعف
            
            const render = () => {
                // جلوگیری از render مضاعف
                if (isRendering) {
                    console.log('⚠️ Render already in progress, skipping...');
                    return;
                }
                
                isRendering = true;
                console.log('🔄 Calendar render started - clearing container');
                
                // پاک کردن کامل محتویات container
                container.innerHTML = '';
                console.log('✅ Container cleared, children count:', container.children.length);
                
                // Header با انتخابگر سال و ماه
                const header = document.createElement('div');
                header.className = 'calendar-header';
                header.style.cssText = `
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 10px;
                    background: #0073aa;
                    color: white;
                    border-radius: 4px 4px 0 0;
                `;
                
                // انتخابگر سال
                const yearSelect = document.createElement('select');
                yearSelect.style.cssText = `
                    padding: 4px 12px;
                    border: none;
                    border-radius: 3px;
                    background: white;
                    color: #333;
                    font-size: 13px;
                    cursor: pointer;
                    margin: 0 5px;
                    text-align: center;
                    text-align-last: center;
                    min-width: 80px;
                `;
                
                // محدوده سال: از 1404 تا 4 سال بعد از سال جاری (حداقل تا 1408)
                const startYear = 1404;
                const currentYear = config.currentJY;
                const endYear = Math.max(1408, currentYear + 4); // حداقل تا 1408 یا 4 سال بعد از امسال
                
                for (let year = startYear; year <= endYear; year++) {
                    const option = document.createElement('option');
                    option.value = year;
                    option.textContent = year;
                    if (year === displayYear) option.selected = true;
                    yearSelect.appendChild(option);
                }
                
                yearSelect.onchange = (e) => {
                    e.stopPropagation();
                    displayYear = parseInt(yearSelect.value);
                    render();
                };
                
                // انتخابگر ماه
                const monthSelect = document.createElement('select');
                monthSelect.style.cssText = `
                    padding: 4px 12px;
                    border: none;
                    border-radius: 3px;
                    background: white;
                    color: #333;
                    font-size: 13px;
                    cursor: pointer;
                    margin: 0 5px;
                    text-align: center;
                    text-align-last: center;
                    min-width: 100px;
                `;
                
                this.jalaliMonths.forEach((month, index) => {
                    const option = document.createElement('option');
                    option.value = index + 1;
                    option.textContent = month;
                    if (index + 1 === displayMonth) option.selected = true;
                    monthSelect.appendChild(option);
                });
                
                monthSelect.onchange = (e) => {
                    e.stopPropagation();
                    console.log('📅 Month select changed to:', monthSelect.value);
                    displayMonth = parseInt(monthSelect.value);
                    render();
                };
                
                // فقط dropdown های سال و ماه - بدون فلش‌های navigation
                const middleSection = document.createElement('div');
                middleSection.style.cssText = 'display: flex; align-items: center; justify-content: center; width: 100%;';
                middleSection.appendChild(monthSelect);
                middleSection.appendChild(yearSelect);
                
                header.appendChild(middleSection);
                
                // Days header
                const daysHeader = document.createElement('div');
                daysHeader.className = 'calendar-days-header';
                daysHeader.style.cssText = `
                    display: grid;
                    grid-template-columns: repeat(7, 1fr);
                    background: #f8f9fa;
                    border-bottom: 1px solid #dee2e6;
                `;
                
                this.jalaliDaysShort.forEach(day => {
                    const dayCell = document.createElement('div');
                    dayCell.textContent = day;
                    dayCell.style.cssText = `
                        padding: 10px 8px;
                        text-align: center;
                        font-weight: bold;
                        font-size: 12px;
                        color: #6c757d;
                        background: #f8f9fa;
                    `;
                    daysHeader.appendChild(dayCell);
                });
                
                // Days grid
                const daysGrid = document.createElement('div');
                daysGrid.className = 'calendar-days-grid';
                daysGrid.style.cssText = `
                    display: grid;
                    grid-template-columns: repeat(7, 1fr);
                    background: white;
                `;
                
                // محاسبه روز اول ماه
                const [firstGY, firstGM, firstGD] = this.jalaliToGregorian(displayYear, displayMonth, 1);
                const firstDay = new Date(firstGY, firstGM - 1, firstGD).getDay();
                const monthDays = this.getMonthDays(displayYear, displayMonth);
                
                // خانه‌های خالی قبل از شروع ماه
                for (let i = 0; i < firstDay; i++) {
                    const emptyCell = document.createElement('div');
                    emptyCell.style.cssText = 'padding: 12px; border: 1px solid #f1f1f1;';
                    daysGrid.appendChild(emptyCell);
                }
                
                // روزهای ماه
                for (let day = 1; day <= monthDays; day++) {
                    const dayCell = document.createElement('div');
                    dayCell.textContent = day;
                    dayCell.style.cssText = `
                        padding: 12px;
                        text-align: center;
                        cursor: pointer;
                        border: 1px solid #f1f1f1;
                        transition: all 0.2s;
                        color: #333;
                        font-weight: 500;
                    `;
                    
                    // هایلایت امروز
                    if (displayYear === config.currentJY && displayMonth === config.currentJM && day === config.currentJD) {
                        dayCell.style.backgroundColor = '#0073aa';
                        dayCell.style.color = 'white';
                        dayCell.style.fontWeight = 'bold';
                    }
                    
                    dayCell.onmouseover = () => {
                        if (!(displayYear === config.currentJY && displayMonth === config.currentJM && day === config.currentJD)) {
                            dayCell.style.backgroundColor = '#e6f3ff';
                            dayCell.style.color = '#0073aa';
                        }
                    };
                    
                    dayCell.onmouseout = () => {
                        if (!(displayYear === config.currentJY && displayMonth === config.currentJM && day === config.currentJD)) {
                            dayCell.style.backgroundColor = 'white';
                            dayCell.style.color = '#333';
                        }
                    };
                    
                    dayCell.onclick = (e) => {
                        e.stopPropagation();
                        // تصحیح نمایش تاریخ انتخابی
                        const formattedDate = `${displayYear}/${displayMonth.toString().padStart(2, '0')}/${day.toString().padStart(2, '0')}`;
                        const input = container.parentNode.querySelector('input');
                        input.value = formattedDate;
                        console.log('روز انتخاب شد - مقدار:', formattedDate);
                        input.dispatchEvent(new Event('change'));
                        container.style.display = 'none';
                    };
                    
                    daysGrid.appendChild(dayCell);
                }
                
                // اول چک کنیم که footer قبلاً وجود نداره
                const existingFooter = container.querySelector('.calendar-footer');
                if (existingFooter) {
                    console.log('⚠️ Footer already exists! Removing...');
                    existingFooter.remove();
                }
                
                // Footer با دکمه امروز و بستن
                console.log('🦶 Creating footer element');
                const footer = document.createElement('div');
                footer.className = 'calendar-footer';
                footer.style.cssText = `
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    border-top: 1px solid #dee2e6;
                    background: #f8f9fa;
                    border-radius: 0 0 4px 4px;
                    padding: 10px;
                `;
                
                if (config.showToday) {
                    const todayBtn = document.createElement('button');
                    todayBtn.textContent = 'امروز';
                    todayBtn.style.cssText = `
                        padding: 4px 12px;
                        border: none;
                        border-radius: 3px;
                        background: #28a745;
                        color: white;
                        font-size: 13px;
                        cursor: pointer;
                        margin: 0 5px;
                        text-align: center;
                        text-align-last: center;
                        min-width: 80px;
                        transition: background 0.2s;
                    `;
                    todayBtn.onmouseover = () => todayBtn.style.background = '#218838';
                    todayBtn.onmouseout = () => todayBtn.style.background = '#28a745';
                    todayBtn.onclick = (e) => {
                        e.stopPropagation();
                        // تصحیح نمایش تاریخ امروز
                        const todayFormatted = `${config.currentJY}/${config.currentJM.toString().padStart(2, '0')}/${config.currentJD.toString().padStart(2, '0')}`;
                        const input = container.parentNode.querySelector('input');
                        input.value = todayFormatted;
                        console.log('امروز کلیک شد - مقدار:', todayFormatted);
                        input.dispatchEvent(new Event('change'));
                        container.style.display = 'none';
                    };
                    footer.appendChild(todayBtn);
                }
                
                const closeBtn = document.createElement('button');
                closeBtn.textContent = 'بستن';
                closeBtn.style.cssText = `
                    padding: 4px 12px;
                    border: none;
                    border-radius: 3px;
                    background: #6c757d;
                    color: white;
                    font-size: 13px;
                    cursor: pointer;
                    margin: 0 5px;
                    text-align: center;
                    text-align-last: center;
                    min-width: 80px;
                    transition: background 0.2s;
                `;
                closeBtn.onmouseover = () => closeBtn.style.background = '#5a6268';
                closeBtn.onmouseout = () => closeBtn.style.background = '#6c757d';
                closeBtn.onclick = (e) => {
                    e.stopPropagation();
                    container.style.display = 'none';
                };
                footer.appendChild(closeBtn);

                container.appendChild(header);
                container.appendChild(daysHeader);
                container.appendChild(daysGrid);
                container.appendChild(footer);
                
                console.log('📦 Calendar components added, final children count:', container.children.length);
                console.log('🔍 Footer elements found:', container.querySelectorAll('.calendar-footer').length);
                
                // تمام کردن render
                isRendering = false;
                console.log('✅ Render completed successfully');
            };

            render();
        }
    }

    // ایجاد instance سراسری
    window.JalaliCalendar = new JalaliCalendar();

    // تابع کمکی برای سازگاری
    window.jdate = (format, timestamp = null) => {
        return window.JalaliCalendar.jdate(format, timestamp);
    };
}

// Auto-initialize date pickers
document.addEventListener('DOMContentLoaded', function() {
    // پیدا کردن تمام input هایی که کلاس jalali-datepicker دارند
    const dateInputs = document.querySelectorAll('.jalali-datepicker');
    dateInputs.forEach(input => {
        window.JalaliCalendar.createDatePicker(input.id || 'datepicker_' + Math.random().toString(36).substr(2, 9));
    });
});