<?php

namespace Database\Seeders;

use App\Models\Kategori;
use Illuminate\Database\Seeder;

class KategoriSeeder extends Seeder
{
    /**
     * Seed kategori buku perpustakaan.
     */
    public function run(): void
    {
        $kategoris = [
            ['nama_kategori' => 'Fiksi'],
            ['nama_kategori' => 'Non-Fiksi'],
            ['nama_kategori' => 'Sains'],
            ['nama_kategori' => 'Teknologi'],
            ['nama_kategori' => 'Sejarah'],
            ['nama_kategori' => 'Agama'],
            ['nama_kategori' => 'Pendidikan'],
        ];

        foreach ($kategoris as $kategori) {
            Kategori::firstOrCreate($kategori);
        }
    }
}
