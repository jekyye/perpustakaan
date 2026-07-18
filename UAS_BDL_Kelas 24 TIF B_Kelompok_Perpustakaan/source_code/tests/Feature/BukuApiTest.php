<?php

namespace Tests\Feature;

use App\Models\Buku;
use App\Models\Kategori;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BukuApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->kategori = Kategori::create(['nama_kategori' => 'Fiksi']);
    }

    public function test_get_all_buku_success()
    {
        $user = User::factory()->create(['role' => 'anggota']);
        Buku::create([
            'kategori_id' => $this->kategori->id,
            'judul' => 'Buku Test',
            'pengarang' => 'Pengarang Test',
            'penerbit' => 'Penerbit Test',
            'tahun_terbit' => 2023,
            'isbn' => '123456789',
            'stok' => 10,
        ]);

        $response = $this->actingAs($user)->getJson('/api/buku');

        $response->assertStatus(200)
                 ->assertJsonStructure(['data' => [['id', 'judul', 'kategori']]]);
    }

    public function test_create_buku_success_as_admin()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $payload = [
            'kategori_id' => $this->kategori->id,
            'judul' => 'Buku Baru',
            'pengarang' => 'Pengarang Baru',
            'penerbit' => 'Penerbit Baru',
            'tahun_terbit' => 2024,
            'isbn' => '987654321',
            'stok' => 5,
        ];

        $response = $this->actingAs($admin)->postJson('/api/buku', $payload);

        $response->assertStatus(201)
                 ->assertJson(['message' => 'Buku created successfully']);
        
        $this->assertDatabaseHas('buku', ['judul' => 'Buku Baru']);
    }

    public function test_create_buku_unauthorized_as_anggota()
    {
        $anggota = User::factory()->create(['role' => 'anggota']);
        
        $payload = [
            'kategori_id' => $this->kategori->id,
            'judul' => 'Buku Unauthorized',
            'pengarang' => 'Pengarang',
            'penerbit' => 'Penerbit',
            'tahun_terbit' => 2024,
            'isbn' => '111111111',
            'stok' => 5,
        ];

        $response = $this->actingAs($anggota)->postJson('/api/buku', $payload);

        $response->assertStatus(403);
    }
}
