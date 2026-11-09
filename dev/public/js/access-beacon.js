(function () {
  var endpoint = window.LABELUP_ACCESS_BEACON || '/api/access/beacon';
  var started = Date.now();
  var sentLeave = false;

  function payload(kind) {
    var tz = '';
    try {
      tz = Intl.DateTimeFormat().resolvedOptions().timeZone || '';
    } catch (e) {
      tz = '';
    }
    return {
      kind: kind,
      path: location.pathname || '/',
      referrer: document.referrer || '',
      title: (document.title || '').slice(0, 120),
      screen: (screen.width || 0) + 'x' + (screen.height || 0),
      viewport: (window.innerWidth || 0) + 'x' + (window.innerHeight || 0),
      lang: navigator.language || '',
      tz: tz,
      dwell_ms: Math.max(0, Date.now() - started)
    };
  }

  function send(kind) {
    if (kind === 'leave' && sentLeave) {
      return;
    }
    if (kind === 'leave') {
      sentLeave = true;
    }
    var body = JSON.stringify(payload(kind));
    if (kind === 'leave' && navigator.sendBeacon) {
      try {
        navigator.sendBeacon(endpoint, new Blob([body], { type: 'application/json' }));
        return;
      } catch (e) {}
    }
    fetch(endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: body,
      credentials: 'same-origin',
      keepalive: true
    }).catch(function () {});
  }

  setTimeout(function () { send('ping'); }, 900);
  document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'hidden') {
      send('leave');
    }
  });
  window.addEventListener('pagehide', function () { send('leave'); });
})();
