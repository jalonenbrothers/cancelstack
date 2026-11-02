/* CancelStack — vanilla JS, no build step. Handles:
   - Home: resume-by-token, last-token chip
   - New case: form submit → token reveal
   - Timeline: load case, render entries, log/edit attempt modal
*/
(function () {
  'use strict';

  var $ = function (sel, root) { return (root || document).querySelector(sel); };

  function api(method, url, body) {
    return fetch(url, {
      method: method,
      headers: body ? { 'Content-Type': 'application/json' } : {},
      body: body ? JSON.stringify(body) : undefined
    }).then(function (r) {
      return r.json().catch(function () { return {}; }).then(function (data) {
        if (!r.ok) throw new Error(data.error || 'Request failed (' + r.status + ')');
        return data;
      });
    });
  }

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  var STATUS_CLASS = {
    'In progress': 'chip-inprogress',
    'Cancelled': 'chip-cancelled',
    'Disputed': 'chip-disputed'
  };

  // ---------- Home ----------
  var resumeForm = $('#resume-form');
  if (resumeForm) {
    var last = null;
    try { last = JSON.parse(localStorage.getItem('cs_last') || 'null'); } catch (e) {}
    if (last && last.token && last.merchant) {
      var p = $('#resume-last');
      var a = $('#resume-last-link');
      if (p && a) {
        a.href = '/c/' + encodeURIComponent(last.token);
        a.textContent = 'Resume last case — ' + last.merchant;
        p.hidden = false;
      }
    }
    resumeForm.addEventListener('submit', function (ev) {
      ev.preventDefault();
      var token = ($('#resume-token').value || '').trim().toUpperCase();
      var err = $('#resume-error');
      err.hidden = true;
      api('GET', '/api/case/' + encodeURIComponent(token))
        .then(function () { window.location.href = '/c/' + encodeURIComponent(token); })
        .catch(function () { err.hidden = false; });
    });
  }

  // ---------- New case ----------
  var newForm = $('#new-case-form');
  if (newForm) {
    var d = newForm.querySelector('input[name=started]');
    if (d) d.max = new Date().toISOString().slice(0, 10);
    newForm.addEventListener('submit', function (ev) {
      ev.preventDefault();
      var err = $('#new-case-error');
      err.hidden = true;
      var fd = new FormData(newForm);
      var body = {};
      ['merchant', 'plan', 'amount', 'cycle', 'started', 'ref', 'tried'].forEach(function (k) {
        body[k] = fd.get(k);
      });
      api('POST', '/api/case', body).then(function (res) {
        newForm.closest('.form-card').hidden = true;
        var card = $('#token-reveal');
        $('#token-text').textContent = res.token;
        $('#open-case').href = '/c/' + encodeURIComponent(res.token);
        $('#copy-link').dataset.link = res.case_url;
        card.hidden = false;
        card.scrollIntoView();
        try {
          localStorage.setItem('cs_last', JSON.stringify({
            token: res.token, merchant: body.merchant
          }));
        } catch (e) {}
      }).catch(function (e) {
        err.textContent = e.message;
        err.hidden = false;
      });
    });
    document.addEventListener('click', function (ev) {
      var btn = ev.target.closest('#copy-link');
      if (!btn) return;
      ev.preventDefault();
      var link = btn.dataset.link || '';
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(link).then(function () {
          btn.textContent = 'Link copied';
        }, function () { btn.textContent = link; });
      } else {
        btn.textContent = link;
      }
    });
  }

  // ---------- Timeline ----------
  var caseHead = $('#case-head');
  if (!caseHead) return;

  var token = caseHead.dataset.token;
  var state = { case: null, attempts: [] };

  function fmtDate(iso) {
    try { return new Date(iso + 'T12:00:00').toLocaleDateString('en-US',
      { month: 'short', day: 'numeric', year: 'numeric' }); } catch (e) { return iso; }
  }

  function loadCase() {
    return api('GET', '/api/case/' + encodeURIComponent(token)).then(function (data) {
      state.case = data.case;
      state.attempts = data.attempts;
      render();
    }).catch(function () {
      window.location.href = '/';
    });
  }

  function render() {
    var c = state.case;
    $('#c-merchant').textContent = c.merchant;
    var sub = [];
    if (c.plan) sub.push(c.plan);
    if (c.amount !== null) sub.push('$' + c.amount.toFixed(2) + (c.cycle ? '/' + c.cycle : ''));
    $('#c-sub').textContent = sub.join(' — ');
    $('#c-days').textContent = 'Case ' + c.token + ' — open ' + c.days_open +
      (c.days_open === 1 ? ' day' : ' days');
    var chip = $('#c-status');
    chip.textContent = c.status;
    chip.className = 'chip ' + (STATUS_CLASS[c.status] || 'chip-default');

    var tl = $('#timeline');
    tl.innerHTML = '';
    $('#empty-state').hidden = state.attempts.length > 0;
    state.attempts.forEach(function (a) {
      var li = document.createElement('li');
      li.className = 't-entry';
      var conf = a.confirmation ? '<p class="t-meta">Confirmation: ' + esc(a.confirmation) + '</p>' : '';
      var shot = a.screenshot_url
        ? '<p class="t-meta">Screenshot: <a href="' + esc(a.screenshot_url) + '" rel="noopener" target="_blank">' + esc(a.screenshot_url) + '</a></p>'
        : '';
      var note = a.note ? '<p class="t-note">' + esc(a.note) + '</p>' : '';
      li.innerHTML =
        '<div class="t-head"><span class="t-ts">' + esc(a.attempt_date) +
        ' · logged ' + esc((a.logged_at || '').slice(0, 16).replace('T', ' ')) + '</span>' +
        '<button type="button" class="t-edit" data-id="' + a.id + '">Edit</button></div>' +
        '<p class="t-channel">' + esc(a.channel) +
        ' <span class="chip ' + (a.outcome === 'still-charged' ? 'chip-disputed' :
          (a.outcome === 'success' ? 'chip-cancelled' : 'chip-inprogress')) + '">' +
          esc(a.outcome) + '</span></p>' + note + conf + shot;
      tl.appendChild(li);
    });
  }

  var modal = $('#attempt-modal');
  var form = $('#attempt-form');
  $('#f-date').max = new Date().toISOString().slice(0, 10);

  function openModal(attempt) {
    $('#attempt-id').value = attempt ? attempt.id : '';
    $('#attempt-error').hidden = true;
    form.reset();
    $('#f-date').value = new Date().toISOString().slice(0, 10);
    $('#f-date').max = new Date().toISOString().slice(0, 10);
    if (attempt) {
      form.date.value = attempt.attempt_date;
      form.channel.value = attempt.channel;
      form.outcome.value = attempt.outcome;
      if (attempt.confirmation) form.confirmation.value = attempt.confirmation;
      if (attempt.note) form.note.value = attempt.note;
      if (attempt.screenshot_url) form.screenshot_url.value = attempt.screenshot_url;
      $('#modal-title').textContent = 'Edit attempt';
    } else {
      $('#modal-title').textContent = 'Log an attempt';
    }
    modal.hidden = false;
    form.date.focus();
  }

  function closeModal() { modal.hidden = true; }

  $('#log-attempt').addEventListener('click', function () { openModal(null); });
  modal.addEventListener('click', function (ev) {
    if (ev.target === modal || ev.target.closest('[data-close-modal]')) closeModal();
  });
  document.addEventListener('keydown', function (ev) {
    if (ev.key === 'Escape' && !modal.hidden) closeModal();
  });

  form.addEventListener('submit', function (ev) {
    ev.preventDefault();
    var err = $('#attempt-error');
    err.hidden = true;
    var body = {
      date: form.date.value,
      channel: form.channel.value,
      outcome: form.outcome.value,
      confirmation: form.confirmation.value,
      note: form.note.value,
      screenshot_url: form.screenshot_url.value
    };
    var id = $('#attempt-id').value;
    var p = id
      ? api('PATCH', '/api/attempt/' + encodeURIComponent(id), body)
      : api('POST', '/api/case/' + encodeURIComponent(token) + '/attempt', body);
    p.then(function () {
      closeModal();
      return loadCase();
    }).catch(function (e) {
      err.textContent = e.message;
      err.hidden = false;
    });
  });

  $('#timeline').addEventListener('click', function (ev) {
    var btn = ev.target.closest('.t-edit');
    if (!btn) return;
    var id = parseInt(btn.dataset.id, 10);
    var a = state.attempts.find(function (x) { return parseInt(x.id, 10) === id; });
    if (a) openModal(a);
  });

  loadCase();
})();
