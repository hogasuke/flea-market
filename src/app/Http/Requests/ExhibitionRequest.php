<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExhibitionRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'image'       => ['required', 'image', 'mimes:jpeg,png'],
            'categories'  => ['required'],
            'condition'   => ['required', 'string'],
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:255'],
            'price'       => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages()
    {
        return [
            'image.required'       => '商品画像を選択してください',
            'image.image'          => '画像ファイルをアップロードしてください',
            'image.mimes'          => 'jpeg, png形式の画像をアップロードしてください',
            'categories.required'  => 'カテゴリーを1つ以上選択してください',
            'condition.required'   => '商品の状態を選択してください',
            'name.required'        => '商品名を入力してください',
            'description.required' => '商品の説明を入力してください',
            'description.max'      => '商品の説明は255文字以内で入力してください',
            'price.required'       => '販売価格を入力してください',
            'price.integer'        => '販売価格は整数で入力してください',
            'price.min'            => '販売価格は0円以上で入力してください',
        ];
    }
}
