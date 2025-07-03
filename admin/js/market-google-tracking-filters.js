document.addEventListener('DOMContentLoaded', function() {
  // تعریف همه فیلدها و سلکتورها
  const dateRange = document.getElementById('mg-date-range');
  const dateFrom = document.getElementById('mg-date-from');
  const dateTo = document.getElementById('mg-date-to');
  const locationSelect = document.getElementById('mg-location');
  const deviceSelect = document.getElementById('mg-device');
  const browserSelect = document.getElementById('mg-browser');
  const referrerSelect = document.getElementById('mg-referrer');
  const visitorTypeSelect = document.getElementById('mg-visitor-type');
  const durationSelect = document.getElementById('mg-duration');
  const pagesSelect = document.getElementById('mg-pages');
  const eventSelect = document.getElementById('mg-event');
  const ipInput = document.getElementById('mg-ip');
  const utmInput = document.getElementById('mg-utm');
  const searchInput = document.getElementById('mg-search');

  // راه‌اندازی تقویم جلالی
  const datePickerOptions = {
    format: 'YYYY/MM/DD',
    autoClose: true,
    initialValue: false,
    persianDigit: true,
    observer: true,
    toolbox: { calendarSwitch: { enabled: false } }
  };
  if (dateFrom) new JalaliDatepicker(dateFrom, datePickerOptions);
  if (dateTo) new JalaliDatepicker(dateTo, datePickerOptions);

  // نمایش/مخفی کردن فیلدهای تاریخ
  function toggleDateInputs() {
    const isCustom = dateRange && dateRange.value === 'custom';
    if (dateFrom && dateTo) {
      dateFrom.parentElement.style.display = isCustom ? 'flex' : 'none';
      dateTo.parentElement.style.display = isCustom ? 'flex' : 'none';
    }
  }
  if (dateRange) {
    dateRange.addEventListener('change', function() {
      toggleDateInputs();
      triggerFilter();
    });
    toggleDateInputs();
  }

  // راه‌اندازی Choices.js برای سلکت‌های داینامیک
  const choicesConfig = {
    removeItemButton: true,
    searchEnabled: true,
    searchResultLimit: 10,
    renderChoiceLimit: 10,
    searchFields: ['label', 'value'],
    itemSelectText: 'انتخاب',
    noResultsText: 'موردی یافت نشد',
    noChoicesText: 'موردی برای انتخاب وجود ندارد',
    addItemText: value => `برای افزودن "${value}" کلید Enter را بزنید`,
  };
  const locationChoices = locationSelect ? new Choices(locationSelect, { ...choicesConfig, placeholderValue: 'کشور، استان یا شهر...' }) : null;
  const browserChoices = browserSelect ? new Choices(browserSelect, { ...choicesConfig, placeholderValue: 'مثلاً Chrome' }) : null;
  const referrerChoices = referrerSelect ? new Choices(referrerSelect, { ...choicesConfig, placeholderValue: 'مثلاً Google' }) : null;
  const deviceChoices = deviceSelect ? new Choices(deviceSelect, { ...choicesConfig, placeholderValue: 'انتخاب دستگاه' }) : null;
  const pagesChoices = pagesSelect ? new Choices(pagesSelect, { ...choicesConfig, placeholderValue: 'صفحه مورد نظر...' }) : null;

  // داینامیک‌سازی سلکت‌ها با AJAX
  function fetchSuggestions(endpoint, query, callback) {
    fetch(endpoint + '?q=' + encodeURIComponent(query))
      .then(res => res.json())
      .then(data => {
        if (Array.isArray(data.suggestions)) {
          callback(data.suggestions);
        }
      });
  }
  function setupDynamicChoices(choicesInstance, endpoint) {
    if (!choicesInstance) return;
    choicesInstance.passedElement.element.addEventListener('search', function(e) {
      const val = e.detail.value.trim();
      if (val.length < 2) return;
      fetchSuggestions(endpoint, val, function(suggestions) {
        const currentOptions = choicesInstance.getValue(true);
        const newOptions = suggestions
          .filter(opt => !currentOptions.includes(opt))
          .map(opt => ({ value: opt, label: opt }));
        choicesInstance.setChoices(newOptions, 'value', 'label', false);
      });
    });
  }
  setupDynamicChoices(locationChoices, '/wp-admin/admin-ajax.php?action=mg_location_suggest');
  setupDynamicChoices(browserChoices, '/wp-admin/admin-ajax.php?action=mg_browser_suggest');
  setupDynamicChoices(referrerChoices, '/wp-admin/admin-ajax.php?action=mg_referrer_suggest');
  setupDynamicChoices(pagesChoices, '/wp-admin/admin-ajax.php?action=mg_pages_suggest');

  // رویدادهای تغییر فیلترها (همه فیلدها)
  [dateFrom, dateTo, locationSelect, deviceSelect, browserSelect, referrerSelect, visitorTypeSelect, durationSelect, pagesSelect, eventSelect, ipInput, utmInput].forEach(function(el) {
    if (el) el.addEventListener('change', triggerFilter);
  });
  if (searchInput) searchInput.addEventListener('input', triggerFilter);

  // ارسال همه مقادیر فیلترها به AJAX
  function triggerFilter() {
    const params = {
      date_range: dateRange ? dateRange.value : '',
      date_from: dateFrom ? dateFrom.value : '',
      date_to: dateTo ? dateTo.value : '',
      location: locationSelect ? locationSelect.value : '',
      device: deviceSelect ? deviceSelect.value : '',
      browser: browserSelect ? browserSelect.value : '',
      referrer: referrerSelect ? referrerSelect.value : '',
      visitor_type: visitorTypeSelect ? visitorTypeSelect.value : '',
      duration: durationSelect ? durationSelect.value : '',
      pages: pagesSelect ? pagesSelect.value : '',
      event: eventSelect ? eventSelect.value : '',
      ip: ipInput ? ipInput.value : '',
      utm: utmInput ? utmInput.value : '',
      search: searchInput ? searchInput.value : ''
    };
    fetch('/wp-admin/admin-ajax.php?action=mg_tracking_filter', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(params)
    })
    .then(res => res.json())
    .then(data => {
      const listContainer = document.getElementById('mg-tracking-list');
      if (listContainer && data.html) {
        listContainer.innerHTML = data.html;
      }
    });
  }

  // نمایش/مخفی‌سازی فیلد جستجو زیر سلکت‌ها
  document.querySelectorAll('.select-search-group select').forEach(function(sel){
      sel.addEventListener('focus',function(){
          var search=sel.parentElement.querySelector('.filter-search');
          if(search) search.style.display='block';
          if(search) setTimeout(()=>search.focus(), 100);
      });
      sel.addEventListener('blur',function(){
          setTimeout(()=>{
              var search=sel.parentElement.querySelector('.filter-search');
              if(search) search.style.display='none';
          },200);
      });
  });
  document.querySelectorAll('.filter-search').forEach(function(input){
      input.addEventListener('blur',function(){
          setTimeout(()=>{ input.style.display='none'; },200);
      });
  });

  // اتوکامپلیت برای inputهای search و text (مثل IP و UTM)
  function setupInputAutocomplete(input, endpoint) {
    if (!input) return;
    input.addEventListener('input', function() {
      const val = input.value.trim();
      if (val.length < 2) return;
      fetch(endpoint + '?q=' + encodeURIComponent(val))
        .then(res => res.json())
        .then(data => {
          if (Array.isArray(data.suggestions) && data.suggestions.length) {
            // نمایش پیشنهادها (می‌توانید با یک پلاگین یا کد ساده dropdown بسازید)
            // اینجا فقط لاگ می‌گیریم:
            console.log('Suggestions for', input.id, data.suggestions);
          }
        });
    });
  }
  setupInputAutocomplete(ipInput, '/wp-admin/admin-ajax.php?action=mg_ip_suggest');
  setupInputAutocomplete(utmInput, '/wp-admin/admin-ajax.php?action=mg_utm_suggest');
});