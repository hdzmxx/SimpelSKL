// Mengatur ukuran kertas/orientasi dan memastikan setiap lembar (satu kelas) muat 1 halaman
// dengan font tetap 12 pt: hanya jarak antarbaris yang dirapatkan, nama panjang boleh turun baris.
(function () {
  var PAPER = { a4: [210, 297], f4: [215, 330], letter: [215.9, 279.4], legal: [215.9, 355.6] };
  var LABEL = { a4: 'A4', f4: 'F4 / Folio', letter: 'Letter', legal: 'Legal' };
  var sheets = [].slice.call(document.querySelectorAll('.sheet'));
  var selK = document.getElementById('kertas'), selO = document.getElementById('orientasi');
  var info = document.getElementById('info'), warn = document.getElementById('warn'), pg = document.getElementById('pg-style');

  function apply(k, o) {
    var d = PAPER[k] || PAPER.a4, w = o === 'landscape' ? d[1] : d[0], h = o === 'landscape' ? d[0] : d[1];
    document.documentElement.style.setProperty('--pw', w + 'mm');
    document.documentElement.style.setProperty('--ph', h + 'mm');
    pg.textContent = '@page{size:' + w + 'mm ' + h + 'mm;margin:0}';
  }

  // true bila lembar muat 1 halaman
  function fit(s) {
    var f = s.querySelector('.fit');
    s.classList.remove('wrap', 'over');
    var set = function (rp, lh, cp) { s.style.setProperty('--rp', rp); s.style.setProperty('--lh', lh); s.style.setProperty('--cp', cp || '1.2mm'); };
    var tooWide = function () { return f.scrollWidth > f.clientWidth + 1; };
    var tooTall = function () { return f.scrollHeight > f.clientHeight + 1; };
    set('.5mm', '1.15', '1.2mm');
    if (!tooWide() && !tooTall()) return true;
    if (tooWide()) s.classList.add('wrap');                       // nama panjang -> boleh dua baris
    var steps = [['.5mm', '1.15', '1.2mm'], ['.35mm', '1.1', '1.2mm'], ['.2mm', '1.05', '1mm'], ['0mm', '1', '.8mm'], ['0mm', '1', '.5mm']];
    for (var i = 0; i < steps.length; i++) {
      set(steps[i][0], steps[i][1], steps[i][2]);
      if (!tooTall() && !tooWide()) return true;
    }
    s.classList.add('over');
    return false;
  }

  function run() { return sheets.filter(function (s) { return !fit(s); }); }

  function layout() {
    var k = selK.value, o = selO.value, used = o, over;
    if (o === 'auto') {                                          // coba landscape dulu; bila ada yang tidak muat -> potret
      apply(k, 'landscape'); over = run(); used = 'landscape';
      if (over.length) { apply(k, 'portrait'); over = run(); used = 'portrait'; }
    } else { apply(k, o); over = run(); }
    info.textContent = sheets.length + ' lembar · ' + (LABEL[k] || k) + ' ' + (used === 'landscape' ? 'landscape' : 'potret') + ' · font 12 pt';
    if (over.length) {
      var names = over.map(function (s) { return s.querySelector('.ttl').textContent.replace(/\s+/g, ' ').trim(); });
      warn.textContent = 'Tidak muat 1 lembar dengan font 12 pt: ' + names.join('; ') + '. Pilih kertas lebih besar (mis. F4) atau orientasi potret.';
      warn.hidden = false;
    } else { warn.hidden = true; }
  }

  selK.addEventListener('change', layout);
  selO.addEventListener('change', layout);
  document.addEventListener('urutan-berubah', layout);
  document.getElementById('btn-print').addEventListener('click', function () { window.print(); });
  layout();
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(layout);
  window.addEventListener('load', layout);
})();

// Urutan kolom mapel khusus cetak nilai semester (per semester): seret kotak mapel atau pakai panah kiri/kanan.
(function () {
  var box = document.getElementById('urutan'); if (!box) return;
  var token = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
  var def = []; try { def = JSON.parse(box.getAttribute('data-default') || '[]'); } catch (e) {}

  function kodes(ul) { return [].map.call(ul.querySelectorAll('.chip'), function (c) { return c.getAttribute('data-kode'); }); }

  // pindahkan kolom di semua lembar semester itu (header, baris siswa, baris rata-rata)
  function applyOrder(sem, list) {
    [].forEach.call(document.querySelectorAll('.sheet[data-sem="' + sem + '"] tr'), function (tr) {
      var end = tr.querySelector('[data-end]'); if (!end) return;
      var cells = {};
      [].forEach.call(tr.querySelectorAll('[data-kode]'), function (c) { cells[c.getAttribute('data-kode')] = c; });
      list.forEach(function (k) { if (cells[k]) tr.insertBefore(cells[k], end); });
    });
    document.dispatchEvent(new Event('urutan-berubah'));
  }

  function save(sem, list, st) {
    st.textContent = 'Menyimpan…';
    fetch(box.getAttribute('data-url'), {
      method: 'POST', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
      body: JSON.stringify({ sem: +sem, urut: list })
    }).then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
      .then(function () { st.textContent = 'Tersimpan'; })
      .catch(function () { st.textContent = 'Gagal menyimpan (muat ulang halaman lalu coba lagi)'; });
  }

  [].forEach.call(box.querySelectorAll('.urut-row'), function (row) {
    var ul = row.querySelector('.chips'), sem = row.getAttribute('data-sem'), st = row.querySelector('.st'), drag = null;

    ul.addEventListener('dragstart', function (e) {
      var c = e.target.closest ? e.target.closest('.chip') : null; if (!c) return;
      drag = c; c.classList.add('drag'); e.dataTransfer.effectAllowed = 'move';
      try { e.dataTransfer.setData('text/plain', c.getAttribute('data-kode')); } catch (x) {}
    });
    ul.addEventListener('dragover', function (e) {
      if (!drag) return; e.preventDefault();
      var target = null, min = Infinity;
      [].forEach.call(ul.querySelectorAll('.chip'), function (c) {
        if (c === drag) return;
        var b = c.getBoundingClientRect(), d = Math.hypot(e.clientX - (b.left + b.width / 2), e.clientY - (b.top + b.height / 2));
        if (d < min) { min = d; target = c; }
      });
      if (!target) return;
      var b = target.getBoundingClientRect();
      if (e.clientX < b.left + b.width / 2) ul.insertBefore(drag, target); else ul.insertBefore(drag, target.nextSibling);
    });
    ul.addEventListener('drop', function (e) { e.preventDefault(); });
    ul.addEventListener('dragend', function () {
      if (drag) drag.classList.remove('drag');
      drag = null; applyOrder(sem, kodes(ul)); st.textContent = 'Belum disimpan';
    });
    ul.addEventListener('keydown', function (e) {
      var c = e.target.closest ? e.target.closest('.chip') : null; if (!c) return;
      if (e.key === 'ArrowLeft' && c.previousElementSibling) ul.insertBefore(c, c.previousElementSibling);
      else if (e.key === 'ArrowRight' && c.nextElementSibling) ul.insertBefore(c.nextElementSibling, c);
      else return;
      e.preventDefault(); c.focus(); applyOrder(sem, kodes(ul)); st.textContent = 'Belum disimpan';
    });
    row.addEventListener('click', function (e) {
      var b = e.target.closest ? e.target.closest('button[data-act]') : null; if (!b) return;
      if (b.getAttribute('data-act') === 'reset') {
        def.forEach(function (k) { var c = ul.querySelector('.chip[data-kode="' + k + '"]'); if (c) ul.appendChild(c); });
        applyOrder(sem, def); save(sem, [], st);
      } else { save(sem, kodes(ul), st); }
    });
  });
})();
