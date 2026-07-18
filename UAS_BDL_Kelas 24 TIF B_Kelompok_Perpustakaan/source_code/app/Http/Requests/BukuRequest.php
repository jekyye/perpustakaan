<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BukuRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && in_array($this->user()->role, ['admin', 'petugas']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $bukuId = $this->route('buku') ? $this->route('buku')->id : null;
        if (!$bukuId && $this->route('id')) {
            $bukuId = $this->route('id');
        }

        return [
            'kategori_id' => 'required|exists:kategori,id',
            'judul' => 'required|string|max:255',
            'pengarang' => 'required|string|max:255',
            'penerbit' => 'required|string|max:255',
            'tahun_terbit' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'isbn' => [
                'required',
                'string',
                Rule::unique('buku', 'isbn')->ignore($bukuId),
            ],
            'stok' => 'required|integer|min:0',
        ];
    }
}
