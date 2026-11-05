/* BestQuest Live Visitors — https://bestquest.net/ | GPL-2.0-or-later */
(() => {
  if (!window.BQLVAdmin) return;
  const $ = s => document.querySelector(s);
  const esc = s => String(s ?? '').replace(/[&<>"']/g, m =>
    ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));

  const post = async (action, extra={}) => {
    const body = new URLSearchParams({action, nonce:BQLVAdmin.nonce, ...extra});
    const r = await fetch(BQLVAdmin.ajaxurl, {
      method:'POST', credentials:'same-origin',
      headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'}, body
    });
    return r.json();
  };

  const renderActions = (row, isOpen = false) => {
    const actions = Array.isArray(row.actions) ? row.actions : [];
    if (!actions.length) return '<span class="bqlv-no-action">—</span>';

    const latest = actions[0];
    const latestText = esc(latest.text || 'Tracked link');
    const latestMeta = `${esc(latest.time || '')}${latest.path ? ` · ${esc(latest.path)}` : ''}`;

    const history = actions.map(action => `
      <div class="bqlv-action-item">
        <div><strong>${esc(action.time || '')}</strong> ${esc(action.text || 'Tracked link')}</div>
        ${action.path ? `<div class="bqlv-action-page">From ${esc(action.page_title || action.path)} <code>${esc(action.path)}</code></div>` : ''}
        ${action.url ? `<code class="bqlv-action-url">${esc(action.url)}</code>` : ''}
      </div>`).join('');

    return `<div class="bqlv-action-latest"><strong>Clicked:</strong> ${latestText}</div>
      <div class="bqlv-action-meta">${latestMeta}</div>
      <details class="bqlv-action-details" data-action-key="${esc(row.session_key || '')}"${isOpen ? ' open' : ''}><summary>${actions.length === 1 ? 'Destination' : `${actions.length} tracked clicks`}</summary>${history}</details>`;
  };

  const render = data => {
    $('#bqlv-live-count').textContent = data.live_count;
    $('#bqlv-active-count').textContent = data.active_15m;
    $('#bqlv-pageviews').textContent = data.visitors_today;
    $('#bqlv-updated').textContent = 'Updated ' + data.updated;
    const tb = $('#bqlv-live-body');
    const openActionKeys = new Set(
      Array.from(tb.querySelectorAll('details.bqlv-action-details[open][data-action-key]'))
        .map(details => details.dataset.actionKey)
        .filter(Boolean)
    );

    if (!data.rows.length) {
      tb.innerHTML = '<tr><td colspan="8">No live visitors right now.</td></tr>';
      return;
    }

    tb.innerHTML = data.rows.map(row => {
      const ref = row.referrer === 'Internal' ? 'Internal' :
        (row.referrer ? `<a href="${esc(row.referrer)}" target="_blank" rel="noopener">${esc(row.referrer)}</a>` : 'Direct / unknown');
      return `<tr>
        <td><strong>${esc(row.title || row.path)}</strong><br><code>${esc(row.path)}</code></td>
        <td class="bqlv-ref">${ref}</td>
        <td>${esc(row.browser)} / ${esc(row.device)}</td>
        <td><code>${esc(row.ip_address || '—')}</code></td>
        <td>${esc(row.timezone || '—')}</td>
        <td class="bqlv-action-cell">${renderActions(row, openActionKeys.has(row.session_key || ''))}</td>
        <td>${esc(row.on_site)}</td>
        <td>${esc(row.last_active)}</td>
      </tr>`;
    }).join('');
  };

  const refresh = async () => {
    try { const r = await post('bqlv_live_data'); if (r.success) render(r.data); } catch(e) {}
  };
  refresh();
  setInterval(refresh, 10000);

  const recentButton = $('#bqlv-recent-toggle');
  const recentPanel = $('#bqlv-recent-content');
  const loadRecent = async () => {
    const tbody = $('#bqlv-recent-body');
    const openActionKeys = new Set(
      Array.from(tbody.querySelectorAll('details.bqlv-action-details[open][data-action-key]'))
        .map(details => details.dataset.actionKey)
        .filter(Boolean)
    );
    tbody.innerHTML = '<tr><td colspan="8">Loading…</td></tr>';
    try {
      const r = await post('bqlv_recent_data');
      if (!r.success) throw new Error('Request failed');
      const rows = r.data.rows || [];
      tbody.innerHTML = rows.length ? rows.map(row => {
        const ref = row.referrer === 'Internal' ? 'Internal' :
          (row.referrer ? esc(row.referrer) : 'Direct / unknown');
        return `<tr><td><strong>${esc(row.title || row.path)}</strong><br><code>${esc(row.path)}</code></td>
          <td><code>${esc(row.ip_address || '—')}</code></td><td>${ref}</td><td>${esc(row.browser)} / ${esc(row.device)}</td>
          <td>${esc(row.timezone || '—')}</td><td class="bqlv-action-cell">${renderActions(row, openActionKeys.has(row.session_key || ''))}</td>
          <td>${esc(row.first_seen)}</td><td>${esc(row.recorded_span)}</td></tr>`;
      }).join('') : '<tr><td colspan="8">No ended or inactive sessions within the retention period.</td></tr>';
      $('#bqlv-recent-updated').textContent = 'Updated ' + r.data.updated;
    } catch (e) {
      tbody.innerHTML = '<tr><td colspan="8">Unable to load history. Try again.</td></tr>';
    }
  };
  recentButton?.addEventListener('click', () => {
    recentPanel.hidden = !recentPanel.hidden;
    recentButton.textContent = recentPanel.hidden ? 'Show recent visitors' : 'Hide recent visitors';
    recentButton.setAttribute('aria-expanded', String(!recentPanel.hidden));
    if (!recentPanel.hidden) loadRecent();
  });
  $('#bqlv-recent-refresh')?.addEventListener('click', loadRecent);

  $('#bqlv-ignore-ip')?.addEventListener('click', async e => {
    e.preventDefault();
    const r = await post('bqlv_ignore_current_ip');
    if (r.success) location.reload();
  });

  document.addEventListener('click', async e => {
    if (!e.target.classList.contains('bqlv-remove-ip')) return;
    e.preventDefault();
    const r = await post('bqlv_remove_ignored_ip', {index:e.target.dataset.index});
    if (r.success) location.reload();
  });

  $('#bqlv-save-settings')?.addEventListener('click', async e => {
    e.preventDefault();
    const msg = $('#bqlv-settings-msg');
    msg.textContent = ' Saving…';
    const publicCredit = $('#bqlv-public-credit');
    const payload = {
      heartbeat:$('#bqlv-heartbeat').value,
      live_window:$('#bqlv-live-window').value,
      retention:$('#bqlv-retention').value,
      tracking_enabled:$('#bqlv-tracking-enabled')?.checked ? '1' : '0',
      store_ip:$('#bqlv-store-ip')?.checked ? '1' : '0',
      delete_on_uninstall:$('#bqlv-delete-on-uninstall')?.checked ? '1' : '0'
    };
    if (publicCredit) payload.public_credit = publicCredit.checked ? '1' : '0';
    const r = await post('bqlv_save_settings', payload);
    msg.textContent = r.success ? ' Saved.' : ' Could not save.';
  });

  $('#bqlv-cleanup-now')?.addEventListener('click', async e => {
    e.preventDefault();
    const msg = $('#bqlv-settings-msg');
    msg.textContent = ' Cleaning…';
    const r = await post('bqlv_cleanup_now');
    msg.textContent = r.success ? ' Cleaned.' : ' Could not clean.';
  });
})();
