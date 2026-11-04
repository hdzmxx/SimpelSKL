<?php

return [
    'admin' => [
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    'seed_sample_data' => env('SEED_SAMPLE_DATA', false),

    // Mapel bawaan (bisa diubah di menu Mapel & Bobot).
    // [kode, nama, nama di surat rapor, ada praktik, bobot semester, bobot tertulis, bobot praktik, ujian tertulis dari Sem 6, tampil di surat rapor]
    'mapel' => [
        ['PAI',  'Pendidikan Agama dan Budi Pekerti',           'Pendidikan Agama & Budi Pekerti', true,  60, 12, 28, false, true],
        ['PP',   'Pendidikan Pancasila',                        'Pendidikan Pancasila',            false, 60, 40, 0,  false, true],
        ['BI',   'Bahasa Indonesia',                            'Bahasa Indonesia',                false, 60, 40, 0,  false, true],
        ['MTK',  'Matematika (Umum)',                           'Matematika',                      false, 60, 40, 0,  false, true],
        ['IPA',  'Ilmu Pengetahuan Alam (IPA)',                 'Ilmu Pengetahuan Alam',           false, 60, 40, 0,  false, true],
        ['IPS',  'Ilmu Pengetahuan Sosial (IPS)',               'Ilmu Pengetahuan Sosial',         false, 60, 40, 0,  false, true],
        ['BING', 'Bahasa Inggris',                              'Bahasa Inggris',                  false, 60, 40, 0,  false, true],
        ['PJOK', 'Pendidikan Jasmani, Olahraga, dan Kesehatan', 'Pendidikan Jasmani, Olahraga dan Kesehatan', true, 60, 12, 28, false, false],
        ['IT',   'Informatika',                                 'Informatika',                     true,  60, 12, 28, false, false],
        ['PKY',  'Prakarya',                                    'Prakarya',                        true,  60, 12, 28, false, false],
        ['BJ',   'Muatan Lokal Bahasa Daerah',                  'Muatan Lokal Bahasa Daerah',      false, 60, 40, 0,  false, false],
    ],

    // Nilai bawaan pengaturan (dapat diubah dari menu Pengaturan)
    'pengaturan' => [
        'nama_sekolah'   => '',
        'pemda'          => '',
        'dinas'          => '',
        'alamat'         => '',
        'telp'           => '',
        'email'          => '',
        'npsn'           => '',
        'wilayah'        => '',
        'kepsek'         => '',
        'nip'            => '',
        'logo_ukuran'    => '22',
        'logo_aplikasi'  => '',
        'logo_kiri'      => '',
        'logo_kanan'     => '',
        'tahun_ajaran'   => '2025/2026',
        'sk_nomor'       => '',
        'sk_tahun'       => '',
        'sk_tanggal'     => '',
        'tgl_lulus'      => '',
        'skl_mulai'      => '',
        'nomor_skl'      => '',
        'nomor_transkrip' => '',
        'nomor_rapor'    => '',
        'ttd_skl'        => '',
        'ttd_transkrip'  => '',
        'ttd_rapor'      => '',
    ],
];
