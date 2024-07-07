<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;

class CreateProductRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    
    
    public function rules(): array
    {
        
        Validator::extend('required_without_sale_end_date', function ($attribute, $value, $parameters, $validator) {
            $sale = $validator->getData()['sale'];
            $saleEndDate = $validator->getData()['sale_end_date'];

            return !($sale && empty($saleEndDate));
        });

        Validator::extend('required_without_sale_quantity', function ($attribute, $value, $parameters, $validator) {
            $sale = $validator->getData()['sale'];
            $saleEndDate = $validator->getData()['sale_quantity'];

            return !($sale && empty($saleEndDate));
        });

        return [
            'name' =>['bail','unique:products,name', 'required','string','min:1', 'max:500'],
            'description' => ['bail', 'required','string','min:1', 'max:1000'],
            'type' => ['bail', 'required','string','min:1', 'max:256'],
            'category_id' => ['bail','integer', 'required', 'exists:categories,id'],
            'quantity' =>  ['bail','required', 'integer', 'min:0'],
            'status' => ['bail', 'required','string','min:1', 'max:256'],

            'tags' => ['bail', 'min:3', 'array', 'required'],
            'tags.*' => ['bail','required', 'string', 'min:2', 'max:256'],

            'original_price' => ['bail', 'required', 'numeric', 'min:0','max:999999.99'],
            'selling_price' => ['bail', 'required', 'numeric', 'min:0','max:999999.99'],

            'sale' => ['bail', 'sometimes' ,'boolean'],
            'sale_quantity' => ['bail','required_without_sale_end_date','integer', 'nullable', 'min:0'],
            'sale_end_date' => ['bail','required_without_sale_quantity','date','nullable','sometimes','after:today'],
            'sale_start_date' => ['bail','required_if:sale,true','date','after_or_equal:today'],
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
            
            'images_data' => ['bail','sometimes'],
            'images_data.*.images' => ['bail','sometimes','array'],
            'images_data.*.color' =>  ['bail','required_unless:images_data.*.images,null','string','min:7','regex:/^#[a-fA-F0-9]{6}$/'],
            'images_data.*.images.*' =>  ['bail','sometimes','image','max:5000','mimes:jpg,png,jpeg']
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'You must specify a name for your product.',
            'images_data.*.images.*.max' => 'Image can be at most 5Mb.',
            'images_data.*.color.required_unless' => 'Color is required if you submit images!',
            'thumbnail_data.color.required' => 'You must select a color for your thumbnail.',
            'thumbnail_data.image.required' => "You must select a thumbnail for your product.",
            'thumbnail_data.image.max' => "Thumbnail can be at most 5Mb.",
            'thumbnail_data.image.mimes' => "Thumbnail can be of types jpg,png,jpeg.",
            'thumbnail_data.image.image' => "Thumbnail field is an image.",
            'sizes.required' => "Select at least 1 size for your product.",
            'colors.required' => "Select at least 1 color for your product.",
            'category_id.required' => "Category field is required.",
            'category_id.exists' => "Category does not exist."
        ];
    }
}
