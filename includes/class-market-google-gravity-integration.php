<?php
/**
 * کلاس ادغام با گراویتی فرم
 */
class Market_Google_Gravity_Integration {
    
    /**
     * راه‌اندازی کلاس
     */
    public function init() {
        // اضافه کردن فیلد نقشه به گراویتی فرم
        add_action('gform_field_standard_settings', array($this, 'location_picker_settings'), 10, 2);
        add_action('gform_editor_js', array($this, 'editor_script'));
        add_filter('gform_add_field_buttons', array($this, 'add_location_field_button'));
        add_filter('gform_field_type_title', array($this, 'location_field_title'));
        
        // ذخیره اطلاعات لوکیشن
        add_action('gform_after_submission', array($this, 'save_location_data'), 10, 2);
        
        // اضافه کردن اسکریپت‌ها و استایل‌ها
        add_action('gform_enqueue_scripts', array($this, 'enqueue_form_scripts'), 10, 2);
    }
    
    /**
     * اضافه کردن دکمه فیلد لوکیشن به ویرایشگر فرم
     */
    public function add_location_field_button($field_groups) {
        foreach ($field_groups as &$group) {
            if ($group['name'] == 'advanced_fields') {
                $group['fields'][] = array(
                    'class' => 'button',
                    'value' => __('Location Picker', 'market-google-location'),
                    'data-type' => 'location_picker'
                );
                break;
            }
        }
        return $field_groups;
    }
    
    /**
     * تنظیم عنوان فیلد لوکیشن
     */
    public function location_field_title($title) {
        if ($title == 'location_picker') {
            return __('Location Picker', 'market-google-location');
        }
        return $title;
    }
    
    /**
     * اضافه کردن تنظیمات فیلد لوکیشن
     */
    public function location_picker_settings($position, $form_id) {
        if ($position == 25) {
            ?>
            <li class="location_picker_setting field_setting">
                <label for="location_picker_api_key">
                    <?php _e('Google Maps API Key', 'market-google-location'); ?>
                    <?php gform_tooltip('location_picker_api_key_tooltip'); ?>
                </label>
                <input type="text" id="location_picker_api_key" class="fieldwidth-3" onchange="SetFieldProperty('locationApiKey', this.value);" />
                
                <label for="location_picker_default_zoom">
                    <?php _e('Default Zoom Level (1-20)', 'market-google-location'); ?>
                </label>
                <input type="number" id="location_picker_default_zoom" class="fieldwidth-2" min="1" max="20" onchange="SetFieldProperty('locationDefaultZoom', this.value);" />
                
                <label for="location_picker_default_lat">
                    <?php _e('Default Latitude', 'market-google-location'); ?>
                </label>
                <input type="text" id="location_picker_default_lat" class="fieldwidth-2" onchange="SetFieldProperty('locationDefaultLat', this.value);" />
                
                <label for="location_picker_default_lng">
                    <?php _e('Default Longitude', 'market-google-location'); ?>
                </label>
                <input type="text" id="location_picker_default_lng" class="fieldwidth-2" onchange="SetFieldProperty('locationDefaultLng', this.value);" />
                
                <label for="location_picker_height">
                    <?php _e('Map Height (px)', 'market-google-location'); ?>
                </label>
                <input type="number" id="location_picker_height" class="fieldwidth-2" onchange="SetFieldProperty('locationHeight', this.value);" />
            </li>
            <?php
        }
    }
    
    /**
     * اضافه کردن اسکریپت به ویرایشگر فرم
     */
    public function editor_script() {
        ?>
        <script type="text/javascript">
            // اضافه کردن تنظیمات فیلد لوکیشن
            fieldSettings.location_picker = '.location_picker_setting';
            
            // اضافه کردن اطلاعات پیش‌فرض برای فیلد لوکیشن
            jQuery(document).on('gform_load_field_settings', function(event, field, form) {
                if (field.type == 'location_picker') {
                    jQuery('#location_picker_api_key').val(field.locationApiKey);
                    jQuery('#location_picker_default_zoom').val(field.locationDefaultZoom ? field.locationDefaultZoom : 12);
                    jQuery('#location_picker_default_lat').val(field.locationDefaultLat ? field.locationDefaultLat : 35.6892);
                    jQuery('#location_picker_default_lng').val(field.locationDefaultLng ? field.locationDefaultLng : 51.3890);
                    jQuery('#location_picker_height').val(field.locationHeight ? field.locationHeight : 400);
                }
            });
        </script>
        <?php
    }
    
    /**
     * اضافه کردن اسکریپت‌ها و استایل‌ها به فرم
     */
    public function enqueue_form_scripts($form, $is_ajax) {
        // بررسی وجود فیلد لوکیشن در فرم
        $has_location_field = false;
        $api_key = '';
        
        foreach ($form['fields'] as $field) {
            if ($field->type == 'location_picker') {
                $has_location_field = true;
                $api_key = $field->locationApiKey;
                break;
            }
        }
        
        if ($has_location_field) {
            // اضافه کردن Google Maps API
            wp_enqueue_script('google-maps', 'https://maps.googleapis.com/maps/api/js?key=' . $api_key . '&libraries=places', array(), null, true);
            
            // اضافه کردن اسکریپت و استایل افزونه
            wp_enqueue_script('market-google-location', MARKET_GOOGLE_PLUGIN_URL . 'public/js/market-google-public.js', array('jquery', 'google-maps'), MARKET_GOOGLE_VERSION, true);
            wp_enqueue_style('market-google-location', MARKET_GOOGLE_PLUGIN_URL . 'public/css/market-google-public.css', array(), MARKET_GOOGLE_VERSION, 'all');
            
            // انتقال متغیرها به جاوااسکریپت
            wp_localize_script('market-google-location', 'marketGoogleVars', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('market_google_nonce'),
                'i18n' => array(
                    'searchPlaceholder' => __('جستجوی آدرس...', 'market-google-location'),
                    'latitude' => __('عرض جغرافیایی', 'market-google-location'),
                    'longitude' => __('طول جغرافیایی', 'market-google-location'),
                    'dragMarker' => __('مارکر را برای تغییر موقعیت بکشید', 'market-google-location'),
                    'confirmLocation' => __('تأیید موقعیت', 'market-google-location'),
                )
            ));
        }
    }
    
    /**
     * ذخیره اطلاعات لوکیشن
     */
    public function save_location_data($entry, $form) {
        global $wpdb;
        
        // بررسی وجود فیلد لوکیشن در فرم
        $location_data = array();
        $has_location_field = false;
        
        foreach ($form['fields'] as $field) {
            if ($field->type == 'location_picker') {
                $has_location_field = true;
                $location_data['latitude'] = rgar($entry, $field->id . '.1');
                $location_data['longitude'] = rgar($entry, $field->id . '.2');
                break;
            }
        }
        
        if (!$has_location_field || empty($location_data['latitude']) || empty($location_data['longitude'])) {
            return;
        }
        
        // جمع‌آوری سایر اطلاعات فرم
        foreach ($form['fields'] as $field) {
            $field_id = $field->id;
            $field_label = strtolower($field->label);
            
            // تشخیص فیلدها براساس برچسب
            if (strpos($field_label, 'نام کسب') !== false || strpos($field_label, 'business name') !== false) {
                $location_data['business_name'] = rgar($entry, $field_id);
            } elseif (strpos($field_label, 'حوزه فعالیت') !== false || strpos($field_label, 'business type') !== false) {
                $location_data['business_type'] = rgar($entry, $field_id);
            } elseif (strpos($field_label, 'شماره تماس کسب') !== false || strpos($field_label, 'business phone') !== false) {
                $location_data['business_phone'] = rgar($entry, $field_id);
            } elseif (strpos($field_label, 'ساعات کاری') !== false || strpos($field_label, 'business hours') !== false) {
                $location_data['business_hours'] = rgar($entry, $field_id);
            } elseif (strpos($field_label, 'استان') !== false || strpos($field_label, 'province') !== false) {
                $location_data['province'] = rgar($entry, $field_id);
            } elseif (strpos($field_label, 'شهر') !== false || strpos($field_label, 'city') !== false) {
                $location_data['city'] = rgar($entry, $field_id);
            } elseif (strpos($field_label, 'آدرس') !== false || strpos($field_label, 'address') !== false) {
                $location_data['address'] = rgar($entry, $field_id);
            } elseif (strpos($field_label, 'وب‌سایت') !== false || strpos($field_label, 'website') !== false) {
                $location_data['website'] = rgar($entry, $field_id);
            } elseif (strpos($field_label, 'نقشه') !== false || strpos($field_label, 'maps') !== false) {
                // اگر چند گزینه‌ای باشد
                if (is_array(rgar($entry, $field_id))) {
                    $location_data['selected_maps'] = json_encode(rgar($entry, $field_id));
                } else {
                    $location_data['selected_maps'] = rgar($entry, $field_id);
                }
            }
        }
        
        // ذخیره اطلاعات در دیتابیس
        $table_name = $wpdb->prefix . 'market_google_locations';
        
        $wpdb->insert(
            $table_name,
            array(
                'entry_id' => $entry['id'],
                'form_id' => $form['id'],
                'latitude' => $location_data['latitude'],
                'longitude' => $location_data['longitude'],
                'business_name' => isset($location_data['business_name']) ? $location_data['business_name'] : '',
                'business_type' => isset($location_data['business_type']) ? $location_data['business_type'] : '',
                'business_phone' => isset($location_data['business_phone']) ? $location_data['business_phone'] : '',
                'business_hours' => isset($location_data['business_hours']) ? $location_data['business_hours'] : '',
                'province' => isset($location_data['province']) ? $location_data['province'] : '',
                'city' => isset($location_data['city']) ? $location_data['city'] : '',
                'address' => isset($location_data['address']) ? $location_data['address'] : '',
                'website' => isset($location_data['website']) ? $location_data['website'] : '',
                'selected_maps' => isset($location_data['selected_maps']) ? $location_data['selected_maps'] : '',
                'created_at' => current_time('mysql')
            )
        );
    }
}