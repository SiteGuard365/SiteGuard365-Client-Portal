(function ($) {
  const root = SG365Portal.root;
  const nonce = SG365Portal.nonce;

  const request = (path, method = "GET", data = null) =>
    $.ajax({
      url: `${root}${path}`,
      method,
      data,
      beforeSend: (xhr) => xhr.setRequestHeader("X-WP-Nonce", nonce),
    });

  const loadDashboard = () => {
    request("/client/dashboard").done((response) => {
      if (!response.success) return;
      const data = response.data || {};
      $(".sg365-dashboard__sites").text(data.sites ?? 0);
      $(".sg365-dashboard__projects").text(data.projects ?? 0);
      $(".sg365-dashboard__tickets").text(data.tickets ?? 0);
    });
  };

  $(document).ready(() => {
    loadDashboard();
  });
})(jQuery);
