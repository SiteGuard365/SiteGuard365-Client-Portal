(function ($) {
  const getMenuData = (container) => {
    const items = [];
    container.find(".sg365-menu-item").each(function () {
      const item = $(this);
      items.push({
        label: item.find('[data-sg365-item="label"]').val(),
        icon: item.find('[data-sg365-item="icon"]').val(),
        slug: item.find('[data-sg365-item="slug"]').val(),
        hidden: item.find('[data-sg365-item="hidden"]').is(":checked") ? 1 : 0,
      });
    });
    return items;
  };

  const buildSettings = () => ({
    brand_name: $('[data-sg365-field="brand_name"]').val(),
    help_url: $('[data-sg365-field="help_url"]').val(),
    polling_interval: $('[data-sg365-field="polling_interval"]').val(),
    client_menu: getMenuData($('[data-sg365-menu="client_menu"]')),
    staff_menu: getMenuData($('[data-sg365-menu="staff_menu"]')),
  });

  const sendAjax = (action, data, onSuccess) => {
    $.post(SG365Admin.ajaxUrl, {
      action,
      nonce: SG365Admin.nonce,
      ...data,
    })
      .done((response) => {
        if (response.success) {
          onSuccess && onSuccess(response);
        } else {
          alert(response.data?.message || "Request failed.");
        }
      })
      .fail(() => alert("Request failed."));
  };

  $('[data-sg365-save]').on("click", () => {
    sendAjax("sg365_save_settings", { settings: JSON.stringify(buildSettings()) }, () =>
      alert("Settings saved.")
    );
  });

  $('[data-sg365-reset]').on("click", () => {
    if (!confirm("Reset to defaults?")) return;
    sendAjax("sg365_reset_settings", {}, () => window.location.reload());
  });

  $('[data-sg365-import]').on("click", () => {
    const payload = $('[data-sg365-import-area]').val();
    if (!payload) return alert("Paste settings JSON first.");
    sendAjax("sg365_import_settings", { settings: payload }, () => window.location.reload());
  });

  $('[data-sg365-rebuild]').on("click", () => {
    sendAjax("sg365_rebuild_indexes", {}, () => alert("Indexes rebuilt."));
  });
})(jQuery);
