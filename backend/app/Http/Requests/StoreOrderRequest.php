<?php

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'symbol' => ['required', 'string', Rule::in(['BTC', 'ETH', 'USDT', 'BNB', 'SOL', 'ADA', 'DOT', 'MATIC'])],
            'side' => ['required', 'string', Rule::in([Order::SIDE_BUY, Order::SIDE_SELL])],
            'price' => ['required', 'numeric', 'min:0.00000001', 'max:999999999'],
            'amount' => ['required', 'numeric', 'min:0.00000001', 'max:999999999'],
        ];
    }

    public function messages(): array
    {
        return [
            'symbol.required' => 'Trading symbol is required.',
            'side.required' => 'Order side (buy/sell) is required.',
            'side.in' => 'Order side must be either buy or sell.',
            'price.required' => 'Order price is required.',
            'price.numeric' => 'Price must be a valid number.',
            'price.min' => 'Price must be greater than 0.',
            'amount.required' => 'Order amount is required.',
            'amount.numeric' => 'Amount must be a valid number.',
            'amount.min' => 'Amount must be greater than 0.',
        ];
    }
}
