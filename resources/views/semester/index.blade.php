@extends('layouts.app')
@section('title', 'Nilai Semester ' . $n)
@section('content')
@use('App\Services\Grading')
<h2 class="t">Nilai per Semester</h2>
<p class="hint">Gunakan rekap ini untuk mencocokkan nilai tiap mata pelajaran dengan rapor secara manual.</p>

<div class="tb">
  <div class="seg">
    @for ($i = 1; $i <= 6; $i++)
      <a class="btn {{ $i === $n ? 'on' : '' }}" href="{{ route('semester', ['sem' => $i, 'kelas' => $kelas]) }}">Sem {{ $i }}</a>
    @endfor
  </div>
  <form method="GET" action="{{ route('semester') }}" style="display:contents">
    <input type="hidden" name="sem" value="{{ $n }}">
    <select name="kelas" class="sm" onchange="this.form.submit()">
      <option value="">Semua kelas</option>
      @foreach ($kelasList as $k)<option @selected($k === $kelas)>{{ $k }}</option>@endforeach
    </select>
  </form>
  <span class="sp"></span>
  <a class="btn" href="{{ route('semester', ['sem' => $n, 'kelas' => $kelas, 'format' => 'xlsx']) }}">Ekspor Sem {{ $n }}</a>
  <a class="btn" href="{{ route('semester', ['sem' => $n, 'kelas' => $kelas, 'format' => 'csv']) }}">CSV</a>
  <a class="btn" href="{{ route('semester', ['kelas' => $kelas, 'format' => 'xlsx', 'semua' => 1]) }}">Semua semester</a>
</div>

<details class="print-box">
  <summary>Cetak nilai semester (per kelas, 1 lembar per kelas)</summary>
  <form method="GET" action="{{ route('semester.cetak') }}" target="_blank" id="print-form">
    <div class="grid2">
      <label class="f">Semester
        <select name="sem">
          @for ($i = 1; $i <= 6; $i++)<option value="{{ $i }}" @selected($i === $n)>Semester {{ $i }}</option>@endfor
          <option value="semua">Semua semester (1–6)</option>
        </select>
      </label>
      <label class="f">Ukuran kertas
        <select name="kertas">
          <option value="a4">A4 (210 × 297 mm)</option><option value="f4" selected>F4 / Folio (215 × 330 mm)</option>
          <option value="letter">Letter (216 × 279 mm)</option><option value="legal">Legal (216 × 356 mm)</option>
        </select>
      </label>
      <label class="f">Orientasi
        <select name="orientasi">
          <option value="auto">Otomatis (paling pas)</option><option value="landscape" selected>Landscape</option><option value="portrait">Potret</option>
        </select>
      </label>
      <label class="f">Urutan lembar (bila banyak)
        <select name="urut"><option value="kelas">Per kelas (semester berurutan)</option><option value="semester">Per semester (semua kelas)</option></select>
      </label>
    </div>
    <div class="pb-kelas">
      <b>Kelas</b>
      <button type="button" class="s" data-pick="all">Pilih semua</button>
      <button type="button" class="s" data-pick="none">Kosongkan</button>
    </div>
    <div class="chks">
      @foreach ($kelasList as $k)<label class="chk"><input type="checkbox" name="kelas[]" value="{{ $k }}" @checked($kelas ? $k === $kelas : true)>{{ $k }}</label>@endforeach
    </div>
    <div class="pb-act"><button class="p">Buka pratinjau cetak</button>
      <span class="help">Tiap kelas satu lembar, tanpa kop dan tanda tangan, font 12 pt. Ukuran kertas dan orientasi bisa diubah lagi di halaman pratinjau.</span></div>
  </form>
</details>

<form class="import-box" method="POST" action="{{ route('semester.impor') }}" enctype="multipart/form-data">@csrf
  <b>Impor Sem {{ $n }}</b>
  <input type="hidden" name="sem" value="{{ $n }}">
  <input type="file" name="file" accept=".xlsx,.xls,.csv,.txt" required>
  <button>Baca &amp; pratinjau</button>
  <span class="help">Kolom: NISN + kode mapel. File “semua semester” (sheet Sem 1–6) bisa diunggah sekaligus. Sel kosong dilewati.</span>
</form>

<div class="tw"><table class="t">
  <thead><tr><th>No</th><th>Nama</th><th>Kelas</th>@foreach ($subjects as $m)<th class="n" title="{{ $m->nama }}">{{ $m->kode }}</th>@endforeach<th class="n">Jumlah</th><th class="n">Rata-rata</th></tr></thead>
  <tbody>
  @forelse ($students as $s)
    <tr><td>{{ $s->no_urut }}</td><td>{{ $s->nama }}</td><td>{{ $s->kelas }}</td>
      @foreach ($subjects as $m)<td class="n">{{ $s->fin[$m->id] === null ? '' : $s->fin[$m->id] + 0 }}</td>@endforeach
      <td class="n">{{ $s->jumlah + 0 }}</td><td class="n"><b>{{ Grading::fmt($s->avg) }}</b></td></tr>
  @empty
    <tr><td colspan="{{ 5 + $subjects->count() }}">Tidak ada siswa.</td></tr>
  @endforelse
  @if ($students->count())
    <tr class="rata"><td colspan="3">Rata-rata {{ $kelas ? 'kelas ' . $kelas : 'semua siswa' }}</td>
      @foreach ($subjects as $m)<td class="n">{{ $rataMapel[$m->id] === null ? '' : Grading::fmt($rataMapel[$m->id]) }}</td>@endforeach<td></td><td></td></tr>
  @endif
  </tbody></table></div>
<p class="hint">Sel kosong = belum diisi dan dihitung 0 dalam rata-rata. Isi nilai lewat menu <b>Nilai → Input Nilai</b> (mata pelajaran tersedia di bagian atas) atau <b>Impor / Ekspor</b>.</p>
@push('scripts')
<script>
(function () {
  var f = document.getElementById('print-form'); if (!f) return;
  f.addEventListener('click', function (e) {
    var b = e.target.closest('[data-pick]'); if (!b) return;
    [].forEach.call(f.querySelectorAll('input[name="kelas[]"]'), function (c) { c.checked = b.dataset.pick === 'all'; });
  });
  f.addEventListener('submit', function (e) {
    if (!f.querySelector('input[name="kelas[]"]:checked')) { e.preventDefault(); alert('Pilih minimal satu kelas.'); }
  });
})();
</script>
@endpush
@endsection
