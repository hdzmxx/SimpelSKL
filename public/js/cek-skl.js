// Cetak SKL: cek dulu apakah kelulusan sudah ditetapkan. Bila siap, SKL dibuka di tab baru seperti biasa;
// bila ada siswa yang belum ditetapkan, tampil dialog (tidak membuka tab baru).
(function () {
  var me = document.currentScript, urlCek = me.getAttribute('data-cek'), urlKel = me.getAttribute('data-kelulusan');
  var dlg = document.createElement('dialog');
  dlg.id = 'dlg-skl';
  document.body.appendChild(dlg);
  dlg.addEventListener('click', function (e) { if (e.target === dlg) dlg.close(); });

  function el(tag, teks, cls) { var x = document.createElement(tag); if (teks) x.textContent = teks; if (cls) x.className = cls; return x; }
  function tombol(teks, cls, fn) { var b = el('button', teks, cls); b.type = 'button'; b.addEventListener('click', fn); return b; }

  function dialog(judul, paragraf, nama, aksi) {
    dlg.textContent = '';
    dlg.appendChild(el('h3', judul));
    dlg.appendChild(el('p', paragraf));
    if (nama && nama.length) {
      var ul = el('ul');
      nama.slice(0, 15).forEach(function (n) { ul.appendChild(el('li', n)); });
      if (nama.length > 15) ul.appendChild(el('li', '… dan ' + (nama.length - 15) + ' siswa lainnya'));
      dlg.appendChild(ul);
    }
    var box = el('div', '', 'aksi');
    aksi.forEach(function (a) { box.appendChild(a); });
    dlg.appendChild(box);
    if (!dlg.open) dlg.showModal();
  }
  var tutup = function () { return tombol('Tutup', '', function () { dlg.close(); }); };
  function bukaTab(url) { return window.open(url, '_blank'); }

  function periksa(url) {
    var cek = new URL(url);
    cek.pathname = cek.pathname.replace(/\/cetak\/skl\/?$/, '/cetak/skl/cek');
    fetch(cek.toString(), { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
      .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
      .then(function (d) {
        if (d.total === 0) {
          dialog('Tidak ada siswa', 'Tidak ada siswa yang cocok dengan pilihan atau filter ini.', null, [tutup()]);
        } else if (d.belum.length === 0) {
          if (!bukaTab(url)) {                                   // pop-up diblokir browser
            dialog('SKL siap dicetak', 'Browser memblokir tab baru. Klik tombol di bawah untuk membuka SKL.', null,
              [tutup(), tombol('Buka SKL', 'p', function () { bukaTab(url); dlg.close(); })]);
          }
        } else {
          var aksi = [tutup(), tombol('Buka menu Kelulusan', d.siap ? '' : 'p', function () { location.href = urlKel; })];
          if (d.siap) aksi.splice(1, 0, tombol('Cetak yang sudah ditetapkan (' + d.siap + ')', 'p', function () { bukaTab(url); dlg.close(); }));
          dialog('SKL belum bisa dicetak',
            d.belum.length + ' siswa belum ditetapkan lulus / tidak lulus. Tetapkan dulu di menu Nilai → Kelulusan.' + (d.siap ? ' ' + d.siap + ' siswa lain sudah siap dicetak.' : ''),
            d.belum, aksi);
        }
      })
      .catch(function () { bukaTab(url); });                     // gagal memeriksa: buka seperti biasa (server tetap menahan yang belum ditetapkan)
  }

  function halamanSkl(u) {
    try { var x = new URL(u, location.href); return x.origin === location.origin && /\/cetak\/skl\/?$/.test(x.pathname); }
    catch (err) { return false; }
  }

  document.addEventListener('click', function (e) {
    var a = e.target.closest ? e.target.closest('a[target="_blank"]') : null;
    if (!a || e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || !halamanSkl(a.href)) return;
    e.preventDefault();
    periksa(a.href);
  });

  document.addEventListener('submit', function (e) {
    if (e.defaultPrevented) return;                               // mis. validasi "centang minimal satu"
    var f = e.target, sb = e.submitter;
    if (!(f.target === '_blank' || (sb && sb.formTarget === '_blank'))) return;
    if ((f.method || 'get').toLowerCase() !== 'get') return;
    var act = (sb && sb.hasAttribute('formaction')) ? sb.formAction : f.action;
    if (!halamanSkl(act)) return;
    e.preventDefault();
    var u = new URL(act, location.href);
    u.search = '';
    var fd;
    try { fd = new FormData(f, sb); } catch (err) { fd = new FormData(f); }
    fd.forEach(function (v, k) { if (typeof v === 'string' && v !== '') u.searchParams.append(k, v); });
    periksa(u.toString());
  });
})();
