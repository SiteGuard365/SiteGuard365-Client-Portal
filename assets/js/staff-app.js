(function () {
  const config = window.sg365Staff || {};
  const interval = config.interval || 20;
  const timerKey = 'sg365Timer';

  function fetchNotifications() {
    if (!config.root) {
      return;
    }
    fetch(`${config.root}/staff/notifications`, {
      headers: {
        'X-WP-Nonce': config.nonce,
      },
    });
  }

  function startTimer() {
    localStorage.setItem(timerKey, JSON.stringify({ start: Date.now() }));
  }

  function stopTimer() {
    const data = JSON.parse(localStorage.getItem(timerKey) || '{}');
    if (!data.start) {
      return;
    }
    const minutes = Math.round((Date.now() - data.start) / 60000);
    localStorage.removeItem(timerKey);
    return minutes;
  }

  document.addEventListener('click', (event) => {
    if (event.target.matches('[data-action="start-timer"]')) {
      startTimer();
    }
    if (event.target.matches('[data-action="stop-timer"]')) {
      stopTimer();
    }
  });

  setInterval(fetchNotifications, interval * 1000);
})();
