// Pratinjau cetak (SKL, Transkrip, Rapor, cetak nilai semester) dibuka di kotak dialog, bukan tab baru.
// Tautan/form yang menuju halaman cetak otomatis dialihkan; bila JavaScript mati, tetap terbuka di tab baru.
(function () {
  var dlg = document.createElement('dialog');
  dlg.id = 'dlg-cetak';
  dlg.innerHTML = '<div class="dlg-bar"><b id="dlg-judul">Pratinjau cetak</b><span style="flex:1"></span>' +
    '<button type="button" id="dlg-tutup">Tutup</button></div><iframe id="dlg-frame" title="Pratinjau cetak"></iframe>';
  document.body.appendChild(dlg);
  var frame = dlg.querySelector('#dlg-frame'), judul = dlg.querySelector('#dlg-judul');

  function buka(url, teks) {
    judul.textContent = teks || 'Pratinjau cetak';
    frame.src = url;
    if (!dlg.open) dlg.showModal();
  }
  function tutup() { if (dlg.open) dlg.close(); }

  dlg.querySelector('#dlg-tutup').addEventListener('click', tutup);
  dlg.addEventListener('close', function () { frame.src = 'about:blank'; });
  dlg.addEventListener('click', function (e) { if (e.target === dlg) tutup(); });          // klik latar gelap
  window.addEventListener('message', function (e) { if (e.data === 'tutup-cetak') tutup(); });

  function halamanCetak(u) {
    try {
      var x = new URL(u, location.href);
      return x.origin === location.origin && /\/(cetak\/(skl|transkrip|rapor)|nilai-semester\/cetak)\/?$/.test(x.pathname);
    } catch (err) { return false; }
  }

  document.addEventListener('click', function (e) {
    var a = e.target.closest ? e.target.closest('a[target="_blank"]') : null;
    if (!a || e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || !halamanCetak(a.href)) return;
    e.preventDefault();
    buka(a.href, a.textContent.trim() || 'Pratinjau cetak');
  });

  document.addEventListener('submit', function (e) {
    if (e.defaultPrevented) return;                                    // mis. validasi "centang minimal satu"
    var f = e.target, sb = e.submitter;
    if (!(f.target === '_blank' || (sb && sb.formTarget === '_blank'))) return;
    if ((f.method || 'get').toLowerCase() !== 'get') return;
    var act = (sb && sb.hasAttribute('formaction')) ? sb.formAction : f.action;
    if (!halamanCetak(act)) return;
    e.preventDefault();
    var u = new URL(act, location.href);
    u.search = '';
    var fd;
    try { fd = new FormData(f, sb); } catch (err) { fd = new FormData(f); }
    fd.forEach(function (v, k) { if (typeof v === 'string' && v !== '') u.searchParams.append(k, v); });
    buka(u.toString(), sb ? sb.textContent.trim() : 'Pratinjau cetak');
  });
})();
