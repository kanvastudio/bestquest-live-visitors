/* BestQuest Live Visitors — https://bestquest.net/ | GPL-2.0-or-later */
(() => {
  if (!window.BQLV?.endpoint) return;

  const uuid = () => crypto.randomUUID ? crypto.randomUUID() :
    'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g,c=>{
      const r=Math.random()*16|0,v=c==='x'?r:(r&3|8); return v.toString(16);
    });

  let visitor = localStorage.getItem('bqlv_vid');
  if (!visitor) { visitor = uuid(); localStorage.setItem('bqlv_vid', visitor); }

  // Session expires after 30 minutes without a user interaction.
  // A heartbeat is not evidence of engagement and must not reset inactivity.
  const sessionKey = 'bqlv_sid';
  const lastKey = 'bqlv_last_activity';
  const idleMs = 30 * 60 * 1000;
  let lastActivity = Number(sessionStorage.getItem(lastKey) || 0);
  let session = sessionStorage.getItem(sessionKey);

  const newSession = () => {
    session = uuid();
    sessionStorage.setItem(sessionKey, session);
  };

  if (!session || Date.now() - lastActivity > idleMs || lastActivity > Date.now()) newSession();
  lastActivity = Date.now();
  sessionStorage.setItem(lastKey, String(lastActivity));

  const markActivity = () => {
    const now = Date.now();
    if (now - lastActivity > idleMs) newSession();
    lastActivity = now;
    sessionStorage.setItem(lastKey, String(now));
  };

  ['pointerdown', 'keydown', 'scroll', 'touchstart'].forEach(type =>
    document.addEventListener(type, markActivity, {passive:true}));

  const context = () => ({
    visitor,
    session,
    path: location.pathname,
    title: document.title || '',
    referrer: document.referrer || '',
    timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || ''
  });

  const send = () => {
    if (document.visibilityState === 'hidden') return;
    if (Date.now() - lastActivity > idleMs) return;

    fetch(BQLV.endpoint, {
      method:'POST',
      credentials:'same-origin',
      keepalive:true,
      headers:{'Content-Type':'application/json','X-BQLV-Nonce':String(BQLV.nonce || '')},
      body:JSON.stringify(context())
    }).catch(()=>{});
  };

  // Track clicks inside article content, plus explicit affiliate/tracking hooks.
  // Header, menu, footer, and WordPress admin navigation are intentionally excluded unless explicitly marked.
  const trackedLinkSelector = [
    '.entry-content a',
    'a.bq-affiliate-text-link',
    '.bq-affiliate-cta a',
    'a[rel~="sponsored"]',
    'a.bq-track-click',
    'a[data-bqlv-track="click"]'
  ].join(',');

  const linkLabel = link => {
    const text = (link.textContent || '').replace(/\s+/g, ' ').trim();
    if (text) return text.slice(0, 255);
    const aria = (link.getAttribute('aria-label') || '').trim();
    if (aria) return aria.slice(0, 255);
    const title = (link.getAttribute('title') || '').trim();
    if (title) return title.slice(0, 255);
    const imgAlt = (link.querySelector('img')?.getAttribute('alt') || '').trim();
    return (imgAlt || 'Tracked link').slice(0, 255);
  };

  const sendAction = link => {
    if (!BQLV.actionEndpoint || !link?.href) return;
    let url;
    try { url = new URL(link.href, location.href); } catch (_) { return; }
    if (!['http:', 'https:'].includes(url.protocol)) return;
    markActivity();

    fetch(BQLV.actionEndpoint, {
      method:'POST',
      credentials:'same-origin',
      keepalive:true,
      headers:{'Content-Type':'application/json','X-BQLV-Nonce':String(BQLV.nonce || '')},
      body:JSON.stringify({
        ...context(),
        action_text: linkLabel(link),
        action_url: url.href
      })
    }).catch(()=>{});
  };

  document.addEventListener('click', event => {
    const target = event.target instanceof Element ? event.target : null;
    const link = target?.closest(trackedLinkSelector);
    if (!link) return;
    sendAction(link);
  }, true);

  send();
  setInterval(send, Math.max(10, Number(BQLV.heartbeat || 20)) * 1000);

  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
      markActivity();
      send();
    }
  });

  window.addEventListener('pageshow', () => {
    markActivity();
    send();
  });
})();
