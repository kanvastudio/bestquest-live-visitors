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

  const render = data => {
    $('#bqlv-live-count').textContent = data.live_count;
    $('#bqlv-active-count').textContent = data.active_15m;
    $('#bqlv-pageviews').textContent = data.visitors_today;
    $('#bqlv-updated').textContent = 'Updated ' + data.updated;
    const tb = $('#bqlv-live-body');

    if (!data.rows.length) {
      tb.innerHTML = '<tr><td colspan="7">No live visitors right now.</td></tr>';
      return;
    }

    tb.innerHTML = data.rows.map(row => {
      const journey = (row.journey || []).map((j,i)=>
        `<div>${i+1}. ${esc(j.title || j.path)}</div>`).join('');
      const ref = row.referrer === 'Internal' ? 'Internal' :
        (row.referrer ? `<a href="${esc(row.referrer)}" target="_blank" rel="noopener">${esc(row.referrer)}</a>` : 'Direct / unknown');
      return `<tr>
        <td><strong>${esc(row.title || row.path)}</strong><br><code>${esc(row.path)}</code></td>
        <td class="bqlv-ref">${ref}</td>
        <td>${esc(row.browser)} / ${esc(row.device)}</td>
        <td>${esc(row.timezone || '—')}</td>
        <td>${esc(row.on_site)}</td>
        <td>${esc(row.last_active)}</td>
        <td><details><summary>${row.journey.length} page${row.journey.length===1?'':'s'}</summary>${journey}</details></td>
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
    tbody.innerHTML = '<tr><td colspan="8">Loading…</td></tr>';
    try {
      const r = await post('bqlv_recent_data');
      if (!r.success) throw new Error('Request failed');
      const rows = r.data.rows || [];
      tbody.innerHTML = rows.length ? rows.map(row => {
        const journey = (row.journey || []).map((j,i) => `<div>${i+1}. ${esc(j.title || j.path)}</div>`).join('');
        const ref = row.referrer === 'Internal' ? 'Internal' :
          (row.referrer ? esc(row.referrer) : 'Direct / unknown');
        return `<tr><td><strong>${esc(row.title || row.path)}</strong><br><code>${esc(row.path)}</code></td>
          <td>${ref}</td><td>${esc(row.browser)} / ${esc(row.device)}</td>
          <td>${esc(row.timezone || '—')}</td><td>${esc(row.first_seen)}</td>
          <td>${esc(row.last_seen)}</td><td>${esc(row.recorded_span)}</td>
          <td><details><summary>${(row.journey || []).length} pages</summary>${journey}</details></td></tr>`;
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
    const r = await post('bqlv_save_settings', {
      heartbeat:$('#bqlv-heartbeat').value,
      live_window:$('#bqlv-live-window').value,
      retention:$('#bqlv-retention').value,
      public_credit:$('#bqlv-public-credit').checked ? '1' : '0'
    });
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