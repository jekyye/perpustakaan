<?php

namespace Tests\Feature;

use App\Models\Buku;
use App\Models\Kategori;
use App\Models\User;
use App\Models\Peminjaman;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeminjamanApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->kategori = Kategori::create(['nama_kategori' => 'Sains']);
        $this->buku = Buku::create([
            'kategori_id' => $this->kategori->id,
            'judul' => 'Buku Sains',
            'pengarang' => 'Ilmuwan',
            'penerbit' => 'Penerbit Sains',
            'tahun_terbit' => 2023,
            'isbn' => '999999999',
            'stok' => 2,
        ]);
        $this->anggota = User::factory()->create(['role' => 'anggota']);
    }

    public function test_borrow_book_success()
    {
        $payload = [
            'buku_ids' => [$this->buku->id],
            'tenggat_kembali' => now()->addDays(7)->toDateString(),
        ];

        $response = $this->actingAs($this->anggota)->postJson('/api/peminjaman', $payload);

        $response->assertStatus(201)
                 ->assertJson(['message' => 'Peminjaman berhasil']);
        
        $this->assertDatabaseHas('peminjaman', ['user_id' => $this->anggota->id, 'status' => 'dipinjam']);
        $this->assertDatabaseHas('detail_peminjaman', ['buku_id' => $this->buku->id, 'status_kembali' => false]);
        $this->assertDatabaseHas('buku', ['id' => $this->buku->id, 'stok' => 1]); // Stok berkurang
    }

    public function test_borrow_book_validation_failed_stock_empty()
    {
        $this->buku->update(['stok' => 0]);

        $payload = [
            'buku_ids' => [$this->buku->id],
            'tenggat_kembali' => now()->addDays(7)->toDateString(),
        ];

        $response = $this->actingAs($this->anggota)->postJson('/api/peminjaman', $payload);

        $response->assertStatus(422)
                 ->assertJson(['message' => 'Stok buku habis']);
    }

    public function test_borrow_book_duplication_active()
    {
        // Pinjam pertama kali
        $payload = [
            'buku_ids' => [$this->buku->id],
            'tenggat_kembali' => now()->addDays(7)->toDateString(),
        ];
        $this->actingAs($this->anggota)->postJson('/api/peminjaman', $payload);

        // Pinjam kedua kali buku yang sama sebelum dikembalikan
        $response = $this->actingAs($this->anggota)->postJson('/api/peminjaman', $payload);

        $response->assertStatus(409)
                 ->assertJson(['message' => 'User masih meminjam salah satu buku ini dan belum dikembalikan']);
    }

    public function test_return_book_success()
    {
        // Pinjam dulu
        $payload = [
            'buku_ids' => [$this->buku->id],
            'tenggat_kembali' => now()->addDays(7)->toDateString(),
        ];
        $borrowResponse = $this->actingAs($this->anggota)->postJson('/api/peminjaman', $payload);
        $peminjamanId = $borrowResponse->json('data.id');

        // Kembalikan
        $returnPayload = [
            'buku_ids' => [$this->buku->id],
        ];
        $response = $this->actingAs($this->anggota)->postJson("/api/peminjaman/{$peminjamanId}/kembali", $returnPayload);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Pengembalian buku berhasil']);
        
        $this->assertDatabaseHas('buku', ['id' => $this->buku->id, 'stok' => 2]); // Stok kembali
        $this->assertDatabaseHas('peminjaman', ['id' => $peminjamanId, 'status' => 'dikembalikan']);
        $this->assertDatabaseHas('detail_peminjaman', ['buku_id' => $this->buku->id, 'status_kembali' => true]);
    }
}
