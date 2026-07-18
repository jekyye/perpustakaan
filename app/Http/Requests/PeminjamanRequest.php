<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PeminjamanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Anggota can borrow, or petugas can create for them.
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'buku_ids' => 'required|array|min:1',
            'buku_ids.*' => 'required|exists:buku,id',
            'tenggat_kembali' => 'required|date|after_or_equal:today',
            // if admin/petugas creates peminjaman, they can specify user_id
            'user_id' => 'sometimes|exists:users,id',
        ];
    }
}
