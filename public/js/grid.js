// Tabel input nilai: tandai sel berubah, pindah dengan Enter/panah, tempel blok dari Excel, peringatan sebelum keluar.
(function () {
  var grid = document.getElementById('ngrid');
  if (!grid) return;
  var form = document.getElementById('gridform'), saved = false;
  var search = document.getElementById('score-search'), filterCount = document.getElementById('score-filter-count');
  var noResults = document.getElementById('score-no-results');
  var inputs = function (root) { return Array.prototype.slice.call(root.querySelectorAll('.ni')); };
  var dataRows = function () { return Array.prototype.slice.call(grid.querySelectorAll('tbody tr[data-student]')); };
  var count = function () {
    var n = grid.querySelectorAll('.ni.dirty').length, c = document.getElementById('dcount');
    if (c) c.textContent = n ? n + ' sel diubah' : '';
    return n;
  };
  var mark = function (i) { i.classList.toggle('dirty', i.value !== i.dataset.orig); count(); };

  grid.addEventListener('input', function (e) { if (e.target.classList.contains('ni')) mark(e.target); });

  grid.addEventListener('keydown', function (e) {
    var t = e.target; if (!t.classList.contains('ni')) return;
    var d = (e.key === 'Enter' || e.key === 'ArrowDown') ? 1 : (e.key === 'ArrowUp' ? -1 : 0); if (!d) return;
    e.preventDefault();
    var tr = t.closest('tr'), idx = inputs(tr).indexOf(t), nx = d > 0 ? tr.nextElementSibling : tr.previousElementSibling;
    while (nx && nx.hidden) nx = d > 0 ? nx.nextElementSibling : nx.previousElementSibling;
    var n = nx && inputs(nx)[idx]; if (n) { n.focus(); n.select(); }
  });

  if (search) search.addEventListener('input', function () {
    var query = (search.value || '').toLocaleLowerCase().trim(), visible = 0;
    var rows = dataRows();
    rows.forEach(function (row) {
      row.hidden = query !== '' && row.dataset.student.indexOf(query) === -1;
      if (!row.hidden) visible++;
    });
    if (noResults) noResults.hidden = visible > 0 || rows.length === 0;
    if (filterCount) {
      filterCount.textContent = query ? visible + ' dari ' + rows.length + ' siswa' : '';
    }
  });

  grid.addEventListener('paste', function (e) {
    var t = e.target; if (!t.classList.contains('ni')) return;
    var txt = (e.clipboardData || window.clipboardData).getData('text').replace(/\r/g, '');
    if (!/[\t\n]/.test(txt.trim())) return;
    e.preventDefault();
    var data = txt.replace(/\n+$/, '').split('\n').map(function (r) { return r.split('\t'); });
    var rows = dataRows().filter(function (row) { return !row.hidden; }), r0 = rows.indexOf(t.closest('tr')), c0 = inputs(t.closest('tr')).indexOf(t);
    data.forEach(function (cells, i) {
      var row = rows[r0 + i]; if (!row) return; var ins = inputs(row);
      cells.forEach(function (v, j) { var inp = ins[c0 + j]; if (inp && !inp.disabled) { inp.value = v.trim().replace(',', '.'); mark(inp); } });
    });
  });

  if (form) form.addEventListener('submit', function () { saved = true; });
  window.addEventListener('beforeunload', function (e) { if (!saved && count()) { e.preventDefault(); e.returnValue = ''; } });
})();
