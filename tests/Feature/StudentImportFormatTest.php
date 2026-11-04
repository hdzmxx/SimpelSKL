<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class StudentImportFormatTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_import_accepts_blank_optional_cells_including_gender(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->post(route('siswa.impor'), [
            'file' => UploadedFile::fake()->createWithContent(
                'siswa.csv',
                "NISN;NIS;Kelas;Nama;Jenis Kelamin\n1234567890;;;;"
            ),
        ]);

        $response->assertRedirect(route('siswa.index'));
        $this->assertDatabaseHas('siswa', [
            'nisn' => '1234567890',
            'nis' => '',
            'nama' => '',
            'kelas' => '',
            'jenis_kelamin' => null,
        ]);
    }

    public function test_student_import_requires_nisn_nis_and_class_headers(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->post(route('siswa.impor'), [
            'file' => UploadedFile::fake()->createWithContent(
                'siswa.csv',
                "NISN;Nama;Kelas\n1234567890;Siswa Uji;X"
            ),
        ]);

        $response->assertSessionHasErrors([
            'file' => 'Kolom wajib tidak ditemukan: NIS. Unduh template Data Siswa untuk format yang benar.',
        ]);
    }
}
