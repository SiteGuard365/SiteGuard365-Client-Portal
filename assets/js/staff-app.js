(function ($) {
  const root = SG365Staff.root;
  const nonce = SG365Staff.nonce;
  const settings = SG365Staff.settings || {};
  const pollInterval = Math.max(parseInt(settings.polling_interval || 20, 10), 5);

  const request = (path, method = "GET", data = null) =>
    $.ajax({
      url: `${root}${path}`,
      method,
      data,
      beforeSend: (xhr) => xhr.setRequestHeader("X-WP-Nonce", nonce),
    });

  const updateNotifications = () => {
    request("/staff/notifications").done((response) => {
      if (!response.success) return;
      const notifications = response.data || [];
      const unread = notifications.filter((item) => item.is_read === "0" || item.is_read === 0);
      $(".sg365-notifications__badge").text(unread.length || "");
      const list = $(".sg365-notifications__list").empty();
      notifications.slice(0, 5).forEach((item) => {
        const li = $("<li />")
          .text(item.title)
          .attr("data-id", item.id)
          .toggleClass("is-unread", item.is_read === "0" || item.is_read === 0);
        list.append(li);
      });
    });
  };

  const setupTimer = () => {
    const timerKey = "sg365_worklog_timer";
    const startBtn = $('[data-sg365-start]');
    const stopBtn = $('[data-sg365-stop]');

    startBtn.on("click", () => {
      localStorage.setItem(timerKey, Date.now().toString());
      alert("Timer started.");
    });

    stopBtn.on("click", () => {
      const start = parseInt(localStorage.getItem(timerKey) || "0", 10);
      if (!start) return alert("Timer not started.");
      const minutes = Math.max(Math.round((Date.now() - start) / 60000), 1);
      localStorage.removeItem(timerKey);
      $('[data-sg365-worklog-minutes]').val(minutes);
      $('[data-sg365-modal]').addClass("is-open").css("display", "flex");
    });
  };

  const setupModal = () => {
    $('[data-sg365-open-worklog]').on("click", () => {
      $('[data-sg365-modal]').addClass("is-open").css("display", "flex");
    });
    $('[data-sg365-close-modal]').on("click", () => {
      $('[data-sg365-modal]').removeClass("is-open").hide();
    });
    $('[data-sg365-submit-worklog]').on("click", () => {
      const payload = {
        client_id: $('[data-sg365-worklog-client]').val(),
        title: $('[data-sg365-worklog-title]').val(),
        details: $('[data-sg365-worklog-details]').val(),
        internal_notes: $('[data-sg365-worklog-notes]').val(),
        time_minutes: $('[data-sg365-worklog-minutes]').val(),
        visibility_client: $('[data-sg365-worklog-visible]').is(":checked") ? 1 : 0,
        log_date: new Date().toISOString().split("T")[0],
      };
      request("/staff/worklogs", "POST", payload).done(() => {
        alert("Worklog saved.");
        $('[data-sg365-modal]').removeClass("is-open").hide();
      });
    });
  };

  $(document).ready(() => {
    updateNotifications();
    setInterval(updateNotifications, pollInterval * 1000);
    setupTimer();
    setupModal();
  });
})(jQuery);
