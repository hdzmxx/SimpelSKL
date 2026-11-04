<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\User;
use App\Support\Year;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkLegalTextSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sk_legal_points_can_be_edited_for_the_selected_academic_year(): void
    {
        $year = AcademicYear::create(['nama' => '2026/2027', 'aktif' => true]);
        $user = User::factory()->create();
        Year::forget();

        $this->actingAs($user)
            ->withSession(['tahun_id' => $year->id])
            ->post(route('cetak.pengaturan'), [
                'sk_menimbang' => "pertimbangan pertama;\npertimbangan kedua.",
                'sk_mengingat' => "Peraturan pertama;\nPeraturan kedua.",
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('pengaturan', [
            'key' => 'sk_menimbang',
            'value' => "pertimbangan pertama;\npertimbangan kedua.",
            'academic_year_id' => $year->id,
        ]);
        $this->assertDatabaseHas('pengaturan', [
            'key' => 'sk_mengingat',
            'value' => "Peraturan pertama;\nPeraturan kedua.",
            'academic_year_id' => $year->id,
        ]);
    }
}
