{{-- Satu format impor/ekspor: langkah 1 unduh, langkah 2 impor. Parameter: id, ket, ekspor, impor, accept, catatan, sem (bool), mapelLinks (bool) --}}
<section class="xp" id="xp-{{ $id }}" hidden>
  <p class="hint">{{ $ket }}</p>

  <div class="xstep">
    <span class="xn">1</span>
    <div class="xbody">
      <b>Unduh template atau data</b>
      <form method="GET" action="{{ $ekspor }}" class="tb">
        <select name="kelas" class="sm" aria-label="Kelas"><option value="">Semua kelas</option>@foreach ($kelasList as $k)<option>{{ $k }}</option>@endforeach</select>
        @if (!empty($sem))
          <select name="sem" class="sm" aria-label="Semester">@for ($i = 1; $i <= 6; $i++)<option value="{{ $i }}">Semester {{ $i }}</option>@endfor</select>
        @endif
        <button class="p" name="blank" value="1">Template kosong</button>
        <button>Nilai terisi (cadangan)</button>
      </form>
      @if (!empty($mapelLinks))
        <p class="hint" style="margin:6px 0 0">Satu mapel saja (Excel atau CSV): buka halaman nilai mapelnya —
          @foreach ($subjects as $m)<a href="{{ route('nilai.grid', $m) }}">{{ $m->kode }}</a>@if (!$loop->last), @endif @endforeach
        </p>
      @endif
    </div>
  </div>

  <div class="xstep">
    <span class="xn">2</span>
    <div class="xbody">
      <b>Isi di Excel, lalu impor</b>
      <form method="POST" action="{{ $impor }}" enctype="multipart/form-data" class="tb">@csrf
        @if (!empty($sem))
          <select name="sem" class="sm" required aria-label="Impor ke semester">@for ($i = 1; $i <= 6; $i++)<option value="{{ $i }}">Impor ke Semester {{ $i }}</option>@endfor</select>
        @endif
        <input type="file" name="file" accept="{{ $accept }}" required>
        <button class="p">{{ $tombolImpor ?? 'Baca & pratinjau' }}</button>
      </form>
      <p class="hint" style="margin:6px 0 0">{{ $catatan }}</p>
    </div>
  </div>
</section>
