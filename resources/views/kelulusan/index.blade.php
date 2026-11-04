@extends('layouts.app')
@section('content')
@use('App\Services\Grading')
@use('App\Services\Kelulusan')
<h2 class="t">Kelulusan</h2>
<p class="hint" style="max-width:780px">Aplikasi mencocokkan tiap siswa dengan kriteria yang Anda atur di bawah dan menampilkan alasannya. Hasil ini <b>saran</b>: kelulusan resmi ditetapkan sekolah (SK kepala sekolah / rapat dewan guru). Gunakan <b>Tetapkan</b> untuk mengunci keputusan akhir.</p>

<div class="cards">
  <div class="stat"><b>{{ $n['ok'] }}</b><span>Memenuhi kriteria</span></div>
  <div class="stat"><b>{{ $n['belum'] }}</b><span>Data belum lengkap</span></div>
  <div class="stat"><b>{{ $n['tidak'] }}</b><span>Belum memenuhi</span></div>
  <div class="stat"><b>{{ $n['tetap_lulus'] }}</b><span>Ditetapkan lulus</span></div>
  <div class="stat"><b>{{ $n['tetap_tidak'] }}</b><span>Ditetapkan tidak lulus</span></div>
</div>

<details class="print-box" open>
  <summary>Aturan kelulusan (sesuaikan dengan SK / pedoman sekolah)</summary>
  <form method="POST" action="{{ route('kelulusan.aturan') }}">@csrf
    <div class="grid2">
      <label class="f">Nilai akhir tiap mapel minimal<input type="number" step="any" min="0" max="100" name="lulus_min_mapel" value="{{ $a['min_mapel'] }}" placeholder="kosong = tidak dipakai"></label>
      <label class="f">Rata-rata nilai akhir minimal<input type="number" step="any" min="0" max="100" name="lulus_min_rata" value="{{ $a['min_rata'] }}" placeholder="kosong = tidak dipakai"></label>
      <label class="f">Sikap / perilaku minimal
        <select name="lulus_sikap"><option value="">Tidak dipakai</option>@foreach (array_keys(Kelulusan::SIKAP) as $k)<option @selected($a['sikap'] === $k)>{{ $k }}</option>@endforeach</select></label>
      <label class="f">Alpa maksimal (hari, Sem 5 + Sem 6)<input type="number" min="0" name="lulus_maks_alpa" value="{{ $a['maks_alpa'] !== null ? (int) $a['maks_alpa'] : '' }}" placeholder="kosong = tidak dipakai"></label>
    </div>
    <label class="chk" style="margin:10px 0"><input type="checkbox" name="lulus_lengkap" value="1" @checked($a['lengkap'])>Semua nilai (Semester 1–6 dan PSAJ semua mapel aktif) harus sudah lengkap</label>
    <button class="p">Simpan aturan</button>
    <span class="help" style="margin-left:8px">Aturan berlaku untuk tahun pelajaran yang sedang dipilih. Angka tidak diisi otomatis; isi sesuai ketentuan sekolah atau Dinas.</span>
  </form>
</details>

<form method="GET" class="tb">
  <select name="kelas" class="sm" onchange="this.form.submit()"><option value="">Semua kelas</option>@foreach ($kelasList as $k)<option @selected($k === $kelas)>{{ $k }}</option>@endforeach</select>
  <select name="status" class="sm" onchange="this.form.submit()">
    @foreach (['' => 'Semua status', 'ok' => 'Memenuhi kriteria', 'belum' => 'Data belum lengkap', 'tidak' => 'Belum memenuhi', 'tetap_lulus' => 'Ditetapkan lulus', 'tetap_tidak' => 'Ditetapkan tidak lulus', 'belum_tetap' => 'Belum ditetapkan'] as $v => $t)
      <option value="{{ $v }}" @selected((string) $status === (string) $v)>{{ $t }}</option>
    @endforeach
  </select>
  <span class="hint" style="margin:0">{{ $list->count() }} dari {{ $total }} siswa</span>
  <span style="flex:1"></span>
  <a class="btn" href="{{ route('kelulusan.ekspor', ['kelas' => $kelas, 'status' => $status]) }}">Ekspor Excel</a>
  <a class="btn" href="{{ route('kelulusan.ekspor', ['kelas' => $kelas, 'status' => $status, 'format' => 'csv']) }}">CSV</a>
</form>

<form method="POST" id="kf">@csrf
  <input type="hidden" name="kelas" value="{{ $kelas }}"><input type="hidden" name="status" value="{{ $status }}">
  <div class="tb">
    <button type="button" class="s" id="k-ok">Centang yang memenuhi</button>
    <button type="button" class="s" id="k-all">Centang semua</button>
    <button type="button" class="s" id="k-none">Kosongkan</button>
    <span class="hint" id="k-n" style="margin:0"></span>
  </div>
  <div class="tb" style="background:#f8fafc;border:1px solid var(--line);border-radius:8px;padding:8px 10px">
    <b style="font-size:12.5px">Sikap massal</b>
    <select name="sikap_massal" class="sm" aria-label="Sikap"><option value="Baik">Baik</option><option value="Sangat Baik">Sangat Baik</option><option value="Cukup">Cukup</option><option value="Kurang">Kurang</option><option value="-">(kosongkan sikap)</option></select>
    <label class="chk"><input type="checkbox" name="hanya_kosong" value="1" checked>hanya yang belum terisi</label>
    <button name="cakupan" value="semua" formaction="{{ route('kelulusan.sikap.massal') }}">Terapkan ke semua yang tampil ({{ $list->count() }})</button>
    <button name="cakupan" value="pilih" formaction="{{ route('kelulusan.sikap.massal') }}">Terapkan ke yang dicentang</button>
  </div>
  <div class="tb">
    <input name="catatan" placeholder="Catatan penetapan (opsional), mis. SK No…" style="min-width:260px">
    <button class="p" name="aksi" value="lulus" formaction="{{ route('kelulusan.tetapkan') }}">Tetapkan LULUS</button>
    <button name="aksi" value="tidak" formaction="{{ route('kelulusan.tetapkan') }}">Tetapkan TIDAK LULUS</button>
    <button name="aksi" value="lepas" formaction="{{ route('kelulusan.tetapkan') }}">Lepas penetapan</button>
    <span style="flex:1"></span>
    <button formaction="{{ route('kelulusan.sikap') }}">Simpan sikap</button>
  </div>
  <div class="tw" style="max-height:62vh"><table class="t">
    <thead><tr><th style="width:28px"></th><th>No</th><th>Nama</th><th>Kelas</th><th class="n">Rata-rata</th><th>Sikap</th><th>Hasil hitungan</th><th class="n" title="Jumlah alpa Semester 5 dan Semester 6">Alpa</th><th>Penetapan</th></tr></thead>
    <tbody>
    @forelse ($list as $st)
      @php $e = $st->evaluasi; @endphp
      <tr data-s="{{ $e['status'] }}">
        <td><input type="checkbox" class="kc" name="pilih[]" value="{{ $st->id }}"></td>
        <td>{{ $st->no_urut }}</td><td>{{ $st->nama }}</td><td>{{ $st->kelas }}</td>
        <td class="n">{{ Grading::fmt($st->avg) }}</td>
        <td><select name="sikap[{{ $st->id }}]" class="sm"><option value="">—</option>@foreach (array_keys(Kelulusan::SIKAP) as $k)<option @selected($st->sikap === $k)>{{ $k }}</option>@endforeach</select></td>
        <td style="white-space:normal;min-width:220px"><span class="bd {{ ['ok' => 'ok', 'belum' => 'warn', 'tidak' => 'no'][$e['status']] }}">{{ ['ok' => 'Memenuhi', 'belum' => 'Data belum lengkap', 'tidak' => 'Belum memenuhi'][$e['status']] }}</span>
          @if ($e['alasan'])<div class="hint" style="margin:3px 0 0">{{ implode('; ', $e['alasan']) }}</div>@endif</td>
        <td class="n">{{ $st->alpa }}</td>
        <td style="white-space:normal">@if ($st->status_lulus)<span class="bd tetap">{{ $st->status_lulus === 'lulus' ? 'Lulus' : 'Tidak lulus' }}</span>@if ($st->catatan_lulus)<div class="hint" style="margin:3px 0 0">{{ $st->catatan_lulus }}</div>@endif @else <span class="hint">—</span>@endif</td>
      </tr>
    @empty
      <tr><td colspan="9">Tidak ada siswa.</td></tr>
    @endforelse
    </tbody></table></div>
</form>

@push('scripts')
<script>
(function () {
  var f = document.getElementById('kf'), cbs = [].slice.call(f.querySelectorAll('.kc')), n = document.getElementById('k-n');
  function count() { var c = cbs.filter(function (x) { return x.checked; }).length; n.textContent = c ? c + ' siswa dicentang' : ''; }
  function set(fn) { cbs.forEach(function (x) { x.checked = fn(x.closest('tr')); }); count(); }
  document.getElementById('k-ok').addEventListener('click', function () { set(function (r) { return r.dataset.s === 'ok'; }); });
  document.getElementById('k-all').addEventListener('click', function () { set(function () { return true; }); });
  document.getElementById('k-none').addEventListener('click', function () { set(function () { return false; }); });
  f.addEventListener('change', function (e) { if (e.target.classList.contains('kc')) count(); });
  f.addEventListener('submit', function (e) {
    var b = e.submitter;
    if (b && b.name === 'cakupan') {
      if (b.value === 'pilih' && !cbs.some(function (x) { return x.checked; })) { e.preventDefault(); alert('Centang minimal satu siswa.'); return; }
      var sel = f.elements['sikap_massal'], hk = f.elements['hanya_kosong'].checked;
      var cakup = b.value === 'semua' ? 'SEMUA siswa yang tampil' : 'siswa yang dicentang';
      if (!confirm('Terapkan sikap "' + sel.options[sel.selectedIndex].text + '" ke ' + cakup + (hk ? ' (yang belum terisi saja)' : ' (menimpa yang sudah terisi)') + '?')) e.preventDefault();
      return;
    }
    if (!b || b.name !== 'aksi') return;
    if (!cbs.some(function (x) { return x.checked; })) { e.preventDefault(); alert('Centang minimal satu siswa.'); return; }
    var t = { lulus: 'LULUS', tidak: 'TIDAK LULUS', lepas: 'dilepas penetapannya' }[b.value];
    if (!confirm('Tetapkan siswa yang dicentang: ' + t + '?')) e.preventDefault();
  });
  count();
})();
</script>
@endpush
@endsection
