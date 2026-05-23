<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $user = auth()->user();
        $address = session('purchase_address', [
            'postal_code' => $user->postal_code,
            'address'     => $user->address,
            'building'    => $user->building,
        ]);

        $this->merge($address);
    }

    public function rules()
    {
        return [
            'payment_method' => ['required'],
            'postal_code'    => ['required'],
            'address'        => ['required'],
        ];
    }

    public function messages()
    {
        return [
            'payment_method.required' => '支払い方法を選択してください',
            'postal_code.required'    => '配送先を入力してください',
            'address.required'        => '配送先を入力してください',
        ];
    }
}
