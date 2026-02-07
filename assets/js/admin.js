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
      help_url: $('#sg365-help-url').val(),
      notification_poll_interval: parseInt($('#sg365-notification-interval').val(), 10) || 20,
      smtp_password: $('#sg365-smtp-password').val(),
      api_token: $('#sg365-api-token').val(),
      webhook_secret: $('#sg365-webhook-secret').val(),
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
        $('#sg365-smtp-password').val(settings.smtp_password ? '••••••' : '');
        $('#sg365-api-token').val(settings.api_token ? '••••••' : '');
        $('#sg365-webhook-secret').val(settings.webhook_secret ? '••••••' : '');
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
        $('#sg365-help-url').val(settings.help_url);
        $('#sg365-notification-interval').val(settings.notification_poll_interval);
        $('#sg365-smtp-password').val(settings.smtp_password ? '••••••' : '');
        $('#sg365-api-token').val(settings.api_token ? '••••••' : '');
        $('#sg365-webhook-secret').val(settings.webhook_secret ? '••••••' : '');
        renderMenuPreview(document.querySelector('[data-menu="client"]'), settings.client_menu || []);
        renderMenuPreview(document.querySelector('[data-menu="staff"]'), settings.staff_menu || []);
      }
    });
  });

  $(document).ready(function () {
    renderMenuPreview(document.querySelector('[data-menu="client"]'), settings.client_menu || []);
    renderMenuPreview(document.querySelector('[data-menu="staff"]'), settings.staff_menu || []);
  });

  $(document).on('click', '[data-secret]', function () {
    const key = $(this).data('secret');
    $.post(sg365Admin.ajaxUrl, {
      action: 'sg365_reveal_secret',
      nonce: sg365Admin.nonce,
      key,
    }).done((response) => {
      if (response.success) {
        if (key === 'smtp_password') {
          $('#sg365-smtp-password').val(response.data.value);
        }
        if (key === 'api_token') {
          $('#sg365-api-token').val(response.data.value);
        }
        if (key === 'webhook_secret') {
          $('#sg365-webhook-secret').val(response.data.value);
        }
      }
    });
  });
})(jQuery);
