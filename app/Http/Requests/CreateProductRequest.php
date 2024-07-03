<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateProductRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' =>['bail','unique:products,name', 'required','string','min:1', 'max:500'],
            'description' => ['bail', 'required','string','min:1', 'max:1000'],
            'type' => ['bail', 'required','string','min:1', 'max:256'],
            'category' => ['bail','integer', 'required', 'exists:categories,id'],
            'quantity' =>  ['bail','required', 'integer', 'min:0'],
            'status' => ['bail', 'required','string','min:1', 'max:256'],

            'tags' => ['bail', 'min:3', 'array', 'required'],
            'tags.*' => ['bail','required', 'string', 'min:2', 'max:256'],

            'original_price' => ['bail', 'required', 'numeric', 'min:0','max:999999.99'],
            'selling_price' => ['bail', 'required', 'numeric', 'min:0','max:999999.99'],

            'sale' => ['bail', 'sometimes' ,'boolean'],
            'sale_quantity' => ['bail','integer', 'nullable', 'min:0'],
            'sale_start_date' => ['bail','required_if:sale,true','date','after_or_equal:today'],
            'sale_end_date' => ['bail','date','nullable','sometimes','after:today'],
            'discount_percentage' => ['bail','required_if:sale,true', 'min:0.01','max:100','regex:/^\d{1,2}(\.\d{1,2})?$/',],

            'colors' => ['bail','array', 'required', 'min:1'],
            'colors.*' => ['bail','required', 'string','min:7','regex:/^#[a-fA-F0-9]{6}$/'],

            'sizes' => ['bail', 'min:1', 'required', 'array'],
            'sizes.*' =>  ['bail', 'required', 'string', 'max:256'],
            'sizes_measurement_unit' =>  ['bail', 'required', 'string', 'max:256'],

            'sizes_data' => ['bail', 'required', 'array', 'min:1'],
            'sizes_data.*.value' => ['bail', 'required', 'string', 'max:256'],
            'sizes_data.*.measurement_unit' => ['bail', 'required', 'string','max:256'],

            'thumbnail_data.color' => ['bail','required','string','min:7','regex:/^#[a-fA-F0-9]{6}$/'],
            'thumbnail_data.image' => ['bail','required','image','max:5000','mimes:jpg,png,jpeg'],
            
            'images_data' => ['bail'],
            'images_data.*.color' =>  ['bail','required','string','min:7','regex:/^#[a-fA-F0-9]{6}$/'],
            'images_data.*.image' => ['bail','required','image','max:5000','mimes:jpg,png,jpeg'],
        ];
    }
}
