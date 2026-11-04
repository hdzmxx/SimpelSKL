// Urutan kolom mapel per semester (khusus cetak nilai semester) di halaman Mapel & Bobot.
(function () {
  var box = document.getElementById('urutan-sem'); if (!box) return;
  var token = box.getAttribute('data-token') || '', url = box.getAttribute('data-url');
  var def = []; try { def = JSON.parse(box.getAttribute('data-default') || '[]'); } catch (e) {}

  function kodes(ul) { return [].map.call(ul.querySelectorAll('.uc-chip'), function (c) { return c.getAttribute('data-kode'); }); }
  function arrange(ul, list) { list.forEach(function (k) { var c = ul.querySelector('.uc-chip[data-kode="' + k + '"]'); if (c) ul.appendChild(c); }); }
  function save(sem, list) {
    return fetch(url, {
      method: 'POST', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
      body: JSON.stringify({ sem: +sem, urut: list })
    }).then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); });
  }
  var rows = [].slice.call(box.querySelectorAll('.uc-row'));
  function gagal(st) { st.textContent = 'Gagal menyimpan (muat ulang halaman lalu coba lagi)'; }

  rows.forEach(function (row) {
    var ul = row.querySelector('.uc-chips'), sem = row.getAttribute('data-sem'), st = row.querySelector('.uc-st'), drag = null;

    ul.addEventListener('dragstart', function (e) {
      var c = e.target.closest ? e.target.closest('.uc-chip') : null; if (!c) return;
      drag = c; c.classList.add('drag'); e.dataTransfer.effectAllowed = 'move';
      try { e.dataTransfer.setData('text/plain', c.getAttribute('data-kode')); } catch (x) {}
    });
    ul.addEventListener('dragover', function (e) {
      if (!drag) return; e.preventDefault();
      var target = null, min = Infinity;
      [].forEach.call(ul.querySelectorAll('.uc-chip'), function (c) {
        if (c === drag) return;
        var b = c.getBoundingClientRect(), d = Math.hypot(e.clientX - (b.left + b.width / 2), e.clientY - (b.top + b.height / 2));
        if (d < min) { min = d; target = c; }
      });
      if (!target) return;
      var b = target.getBoundingClientRect();
      if (e.clientX < b.left + b.width / 2) ul.insertBefore(drag, target); else ul.insertBefore(drag, target.nextSibling);
    });
    ul.addEventListener('drop', function (e) { e.preventDefault(); });
    ul.addEventListener('dragend', function () { if (drag) drag.classList.remove('drag'); drag = null; st.textContent = 'Belum disimpan'; });
    ul.addEventListener('keydown', function (e) {
      var c = e.target.closest ? e.target.closest('.uc-chip') : null; if (!c) return;
      if (e.key === 'ArrowLeft' && c.previousElementSibling) ul.insertBefore(c, c.previousElementSibling);
      else if (e.key === 'ArrowRight' && c.nextElementSibling) ul.insertBefore(c.nextElementSibling, c);
      else return;
      e.preventDefault(); c.focus(); st.textContent = 'Belum disimpan';
    });

    row.addEventListener('click', function (e) {
      var b = e.target.closest ? e.target.closest('button[data-act]') : null; if (!b) return;
      var act = b.getAttribute('data-act');
      st.textContent = 'Menyimpan…';
      if (act === 'reset') {
        arrange(ul, def);
        save(sem, []).then(function () { st.textContent = 'Kembali ke urutan standar'; }).catch(function () { gagal(st); });
      } else if (act === 'salin') {
        var list = kodes(ul);
        Promise.all(rows.map(function (r2) {
          arrange(r2.querySelector('.uc-chips'), list);
          return save(r2.getAttribute('data-sem'), list).then(function () { r2.querySelector('.uc-st').textContent = 'Tersimpan'; });
        })).catch(function () { gagal(st); });
      } else {
        save(sem, kodes(ul)).then(function () { st.textContent = 'Tersimpan'; }).catch(function () { gagal(st); });
      }
    });
  });
})();
