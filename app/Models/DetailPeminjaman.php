<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailPeminjaman extends Model
{
    use HasFactory;

    protected $table = 'detail_peminjaman';

    protected $fillable = [
        'peminjaman_id',
        'buku_id',
        'denda',
        'status_kembali',
        'tanggal_kembali',
    ];

    protected $casts = [
        'denda' => 'integer',
        'status_kembali' => 'boolean',
        'tanggal_kembali' => 'date',
    ];

    public function peminjaman()
    {
        return $this->belongsTo(Peminjaman::class);
    }

    public function buku()
    {
        return $this->belongsTo(Buku::class);
    }

    public function scopeBelumKembali($query)
    {
        return $query->where('status_kembali', false);
    }
}
