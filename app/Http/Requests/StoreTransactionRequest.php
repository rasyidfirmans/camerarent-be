<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // return auth()->check() && auth()->user()->tokenCan('access-token');
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'products' => ['required', 'array', 'min:1'],
            'products.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'products.*.start_date' => ['required', 'date', 'after_or_equal:today'],
            'products.*.end_date' => ['required', 'date', 'after_or_equal:products.*.start_date'],
            'products.*.quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:255'],
            'delivery' => ['string', 'required'],
            'payment_proof' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];
    }
}
