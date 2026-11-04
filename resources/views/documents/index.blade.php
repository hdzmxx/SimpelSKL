@extends('layouts.app')
@section('content')
<div class="documents-page">
<div class="documents-heading">
<h2 class="t">Cetak Dokumen</h2>
  <a class="btn" href="{{ route('sk-kelulusan') }}">Menu SK Kelulusan & Pengaturan Dokumen</a>
</div>
<form class="card documents-card" method="GET" target="_blank" id="batch-form">
  <div class="documents-section-title"><div><h3>Pengaturan cetak</h3><p>Pilih cakupan siswa dan format nama sebelum mencetak.</p></div></div>
  <div class="documents-filters">
    <label class="f">Kelas<select name="kelas"><option value="">Semua kelas</option>@foreach ($kelasList as $k)<option>{{ $k }}</option>@endforeach</select></label>
    <label class="f">No. urut siswa (dari)<input type="number" name="awal" min="1" placeholder="Opsional"></label>
    <label class="f">No. urut siswa (sampai)<input type="number" name="akhir" min="1" placeholder="Opsional"></label>
    <label class="f">Format nama
      <select name="format_nama"><option value="title">Capitalized Each Word</option><option value="upper">UPPERCASE</option></select>
    </label>
    <label class="chk"><input type="checkbox" name="bold_nama" value="1"> Tebalkan nama siswa</label>
  </div>
  <p class="documents-note">Isi nomor urut awal dan akhir untuk mencetak siswa dalam rentang tersebut.</p>
  <fieldset class="documents-subjects">
    <legend>Mapel pada Surat Nilai Rapor</legend>
    <div class="chks">
    @foreach ($subjects as $m)<label class="chk"><input type="checkbox" name="mapel[]" value="{{ $m->id }}" @checked($m->tampil_di_rapor)>{{ $m->nama_rapor ?: $m->nama }}</label>@endforeach
    </div>
  </fieldset>
  <div class="documents-actions">
    <button class="p" formaction="{{ route('cetak.show', 'skl') }}">Surat Keterangan Lulus</button>
    <button class="p" formaction="{{ route('cetak.show', 'transkrip') }}">Transkrip Nilai</button>
    <button class="p" formaction="{{ route('cetak.show', 'rapor') }}">Surat Nilai Rapor Sem 1–5</button>
  </div>
  <p class="documents-note">Dokumen terbuka di tab baru. Gunakan tombol Cetak / Simpan PDF pada halaman dokumen. Untuk mencetak siswa tertentu, gunakan bagian di bawah.</p>
</form>

<div class="card documents-card" id="per-siswa">
  <div class="documents-section-title"><div><h3>Cetak per siswa</h3><p>Cari atau saring siswa, lalu pilih siswa yang akan dicetak.</p></div></div>
  <div class="documents-filters documents-student-filters">
    <label class="f">Cari siswa<input type="search" id="ps-q" placeholder="Nama, NISN, atau NIS" autocomplete="off"></label>
    <label class="f">Kelas<select id="ps-kelas" class="sm"><option value="">Semua kelas</option>@foreach ($kelasList as $k)<option>{{ $k }}</option>@endforeach</select></label>
    <span class="hint" id="ps-count" style="margin:0"></span>
  </div>
  <form method="GET" target="_blank" id="ps-form" style="margin:0">
    <input type="hidden" name="format_nama" id="ps-format-nama" value="title">
    <input type="hidden" name="bold_nama" id="ps-bold-nama" value="0">
    <div class="documents-actions documents-student-actions">
      <button class="p" formaction="{{ route('cetak.show', 'skl') }}">SKL terpilih</button>
      <button class="p" formaction="{{ route('cetak.show', 'transkrip') }}">Transkrip terpilih</button>
      <button class="p" formaction="{{ route('cetak.show', 'rapor') }}">Rapor terpilih</button>
      <span class="hint" id="ps-sel" style="margin:0" aria-live="polite"></span>
    </div>
    <div class="tw" style="max-height:55vh"><table class="t">
      <thead><tr><th style="width:28px"><input type="checkbox" id="ps-all" title="Pilih semua yang tampil"></th><th>No</th><th>Nama</th><th>NISN</th><th>Kelas</th><th>Cetak</th></tr></thead>
      <tbody>
      @foreach ($students as $st)
        <tr data-q="{{ mb_strtolower($st->nama . ' ' . $st->nisn . ' ' . $st->nis) }}" data-k="{{ $st->kelas }}">
          <td><input type="checkbox" name="siswa[]" value="{{ $st->id }}" class="ps-cb"></td>
          <td>{{ $st->no_urut }}</td><td>{{ $st->nama }}</td><td>{{ $st->nisn }}</td><td>{{ $st->kelas }}</td>
          <td class="nw">
            <a class="btn" target="_blank" data-j="skl" href="{{ route('cetak.show', ['jenis' => 'skl', 'siswa' => [$st->id]]) }}">SKL</a>
            <a class="btn" target="_blank" data-j="transkrip" href="{{ route('cetak.show', ['jenis' => 'transkrip', 'siswa' => [$st->id]]) }}">Transkrip</a>
            <a class="btn ps-rapor" target="_blank" data-j="rapor" href="{{ route('cetak.show', ['jenis' => 'rapor', 'siswa' => [$st->id]]) }}">Rapor</a>
          </td>
        </tr>
      @endforeach
      </tbody></table></div>
  </form>
  <p class="documents-note">Rapor memakai mapel yang dipilih pada pengaturan cetak di atas.</p>
</div>

</div>
@push('scripts')
<script>
(function () {
  var q = document.getElementById('ps-q'), kel = document.getElementById('ps-kelas'), cnt = document.getElementById('ps-count'),
      sel = document.getElementById('ps-sel'), all = document.getElementById('ps-all'), form = document.getElementById('ps-form'),
      rows = [].slice.call(document.querySelectorAll('#ps-form tbody tr'));
  function norm(t) { return (t || '').toLowerCase().trim(); }
  function apply() {
    var t = norm(q.value), k = kel.value, n = 0;
    rows.forEach(function (r) {
      var ok = (!t || r.dataset.q.indexOf(t) > -1) && (!k || r.dataset.k === k);
      r.hidden = !ok; if (ok) n++;
    });
    cnt.textContent = n + ' dari ' + rows.length + ' siswa';
    count();
  }
  function count() {
    var c = rows.filter(function (r) { return !r.hidden && r.querySelector('.ps-cb').checked; }).length;
    sel.textContent = c ? c + ' siswa dipilih' : '';
  }
  // mapel rapor mengikuti pilihan di kartu atas
  function mapelQs() {
    return [].map.call(document.querySelectorAll('#batch-form input[name="mapel[]"]:checked'), function (c) { return 'mapel[]=' + encodeURIComponent(c.value); }).join('&');
  }
  document.querySelectorAll('#per-siswa a[data-j]').forEach(function (a) {
    a.dataset.base = a.getAttribute('href');
    a.addEventListener('click', function () {
      var params = ['format_nama=' + encodeURIComponent(document.querySelector('#batch-form [name="format_nama"]').value)];
      if (a.dataset.j === 'rapor') {
        var m = mapelQs();
        if (m) params.push(m);
      }
      if (document.querySelector('#batch-form [name="bold_nama"]').checked) params.push('bold_nama=1');
      a.setAttribute('href', a.dataset.base + '&' + params.join('&'));
    });
  });
  form.addEventListener('submit', function (e) {
    var picked = rows.filter(function (r) { return !r.hidden && r.querySelector('.ps-cb').checked; });
    if (!picked.length) { e.preventDefault(); alert('Centang minimal satu siswa.'); return; }
    document.getElementById('ps-format-nama').value = document.querySelector('#batch-form [name="format_nama"]').value;
    document.getElementById('ps-bold-nama').value = document.querySelector('#batch-form [name="bold_nama"]').checked ? '1' : '0';
    rows.forEach(function (r) { r.querySelector('.ps-cb').disabled = r.hidden; });      // siswa yang tersaring tidak ikut
    [].forEach.call(form.querySelectorAll('input[name="mapel[]"]'), function (x) { x.remove(); });
    [].forEach.call(document.querySelectorAll('#batch-form input[name="mapel[]"]:checked'), function (c) {
      var h = document.createElement('input'); h.type = 'hidden'; h.name = 'mapel[]'; h.value = c.value; form.appendChild(h);
    });
    setTimeout(function () { rows.forEach(function (r) { r.querySelector('.ps-cb').disabled = false; }); }, 300);
  });
  all.addEventListener('change', function () {
    rows.forEach(function (r) { if (!r.hidden) r.querySelector('.ps-cb').checked = all.checked; }); count();
  });
  form.addEventListener('change', function (e) { if (e.target.classList.contains('ps-cb')) count(); });
  q.addEventListener('input', apply); kel.addEventListener('change', apply);
  apply();
})();
</script>
@endpush
@endsection
