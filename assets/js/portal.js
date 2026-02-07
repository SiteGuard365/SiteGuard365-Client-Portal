(function () {
  const config = window.sg365Portal || {};
  const interval = config.interval || 20;

  function fetchNotifications() {
    if (!config.root) {
      return;
    }
    fetch(`${config.root}/client/notifications`, {
      headers: {
        'X-WP-Nonce': config.nonce,
      },
    });
  }

  setInterval(fetchNotifications, interval * 1000);
})();
