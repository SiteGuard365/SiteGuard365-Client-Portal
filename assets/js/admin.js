(function ($) {
  const settings = sg365Admin.settings || {};

  function renderMenuPreview(container, items) {
    if (!container) {
      return;
    }
    container.innerHTML = items
      .filter((item) => item.enabled)
      .map((item) => `<div><span class="dashicons ${item.icon}"></span> ${item.label}</div>`)
      .join('');
  }

  function gatherSettings() {
    return {
      ...settings,
      brand_name: $('#sg365-brand-name').val(),
      notification_poll_interval: parseInt($('#sg365-notification-interval').val(), 10) || 20,
    };
  }

  $(document).on('click', '#sg365-save-settings', function () {
    const payload = gatherSettings();
    $.post(sg365Admin.ajaxUrl, {
      action: 'sg365_save_settings',
      nonce: sg365Admin.nonce,
      settings: JSON.stringify(payload),
    }).done((response) => {
      if (response.success) {
        Object.assign(settings, response.data.settings);
      }
    });
  });

  $(document).on('click', '#sg365-reset-settings', function () {
    $.post(sg365Admin.ajaxUrl, {
      action: 'sg365_reset_settings',
      nonce: sg365Admin.nonce,
    }).done((response) => {
      if (response.success) {
        Object.assign(settings, response.data.settings);
        $('#sg365-brand-name').val(settings.brand_name);
        $('#sg365-notification-interval').val(settings.notification_poll_interval);
        renderMenuPreview(document.querySelector('[data-menu="client"]'), settings.client_menu || []);
        renderMenuPreview(document.querySelector('[data-menu="staff"]'), settings.staff_menu || []);
      }
    });
  });

  $(document).ready(function () {
    renderMenuPreview(document.querySelector('[data-menu="client"]'), settings.client_menu || []);
    renderMenuPreview(document.querySelector('[data-menu="staff"]'), settings.staff_menu || []);
  });
})(jQuery);
