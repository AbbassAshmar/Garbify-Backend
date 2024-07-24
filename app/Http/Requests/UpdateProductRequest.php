<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Auth\Events\Failed;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;

class UpdateProductRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    
    
    public function rules(): array
    {
        return [
            'name' =>['bail','unique:products,name', 'sometimes','string','min:1', 'max:500'],
            'description' => ['bail', 'sometimes','string','min:1', 'max:1000'],
            'type' => ['bail', 'sometimes','string','min:1', 'max:256'],
            
            'category_id' => ['bail','integer', 'sometimes', 'exists:categories,id'],
            'quantity' =>  ['bail','sometimes', 'integer', 'min:0'],
            'status' => ['bail', 'sometimes','string','min:1', 'max:256'],

            'tags' => ['bail', 'min:3', 'array', 'sometimes'],
            'tags.*' => ['bail','sometimes', 'string', 'min:2', 'max:256'],

            'original_price' => ['bail', 'sometimes', 'numeric', 'min:0','max:999999.99'],
            'selling_price' => ['bail', 'sometimes', 'numeric', 'min:0','max:999999.99'],

            'sale_quantity' => ["bail",'sometimes', 'integer', 'nullable', 'min:1'],
            'discount_percentage' => ['bail',"sometimes",'nullable', 'min:0.01','max:100','regex:/^\d{1,2}(\.\d{1,2})?$/',],

            'sale_end_date' => [
                'bail',
                'date',
                'nullable',
                'after:today',
                'date_format:Y-m-d H:i'
            ],

            'sale_start_date' => [
                'bail',
                'sometimes',
                'nullable','date',
                'date_format:Y-m-d H:i',
                function ($attribute, $value, $fail) {
                    $saleEndDate = $this->request->get('sale_end_date');
                    if ($saleEndDate) {
                        $startDate = Carbon::parse($value);
                        $endDate = Carbon::parse($saleEndDate);
                        if ($startDate->greaterThan($endDate)) {
                            $fail('Sale start date must be before sale end date.');
                        }
                    }
                }
            ],

            'colors' => ['bail','array', 'sometimes', 'min:1','max:10'],
            'colors.*' => ['bail','sometimes', 'string','distinct','regex:/^#[a-fA-F0-9]{6}$/'],

            'sizes' => ['bail', 'sometimes','array', 'min:1', 'max:10'],
            'sizes.*' =>  ['bail', 'sometimes', 'string', 'max:256'],
            'sizes_unit' =>  ['bail', 'sometimes', 'string', 'max:256'],

            'sizes_data' => [
                'bail', 
                'sometimes', 
                'array', 
                'min:1', 
                'max:10',
                function ($attribute, $value, $fail) {
                    $sizes = $this->request->all()['sizes'];
                    if ($sizes) {
                        $sizesArray = array_map(function($size){
                            return $size['size'];
                        },$value);
                        
                        if (!array_diff($sizesArray, $sizes) && count($sizes) >= count($sizesArray)){
                            $fail('Main sizes in sizes_data should be the same as the sizes in sizes array.');
                        }
                    }
                }
            ],
            
            'sizes_data.*.size' => ['bail', 'sometimes', 'string', 'max:256'],
            'sizes_data.*.unit' => ['bail', 'sometimes', 'string','max:256'],

            'sizes_data.*.alternative_sizes' => ['bail','sometimes','array','max:10'],
            'sizes_data.*.alternative_sizes.*.size' => ['bail','sometimes','string','max:256'],
            'sizes_data.*.alternative_sizes.*.unit' => ['bail','sometimes','string','max:256'],

            'thumbnail_data.color' => ['bail','sometimes','string','regex:/^#[a-fA-F0-9]{6}$/'],
            'thumbnail_data.image' => ['bail','sometimes','image','max:5000','mimes:jpg,png,jpeg'],
            
            'images_data' => [
                'bail',
                'sometimes',
                function ($attribute, $value, $fail) {
                    $colors = $this->request->all()['colors'];
                    if ($colors) {
                        $colorsArray = array_map(function($imageData){
                            return $imageData['color'];
                        },$value);

                        if (!array_diff($colorsArray, $colors) && count($colors) >= count($colorsArray)){
                            $fail('Colors of images should be the same as the colors in the colors array.');
                        }
                    }
                }
            ],
            'images_data.*.images' => ['bail','sometimes','array'],
            'images_data.*.color' =>  ['bail','required_unless:images_data.*.images,null','string','regex:/^#[a-fA-F0-9]{6}$/'],
            'images_data.*.images.*' =>  ['bail','sometimes','image','max:5000','mimes:jpg,png,jpeg']
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'You must specify a name for your product.',

            'images_data.*.images.*.max' => 'Images can be at most 5Mb.', 
            'images_data.*.images.*.mimes' => "Images can only be of type jpg, png and jpeg.", 

            'images_data.*.color.required_unless' => 'Color is required if you submit images!',
            'images_data.*.color.regex' => "Colors should be in hex format ex. #000000",

            'thumbnail_data.color.required' => 'You must select a color for your thumbnail.',
            "thumbnail_data.color.regex" => "Colors should be in hex format ex. #000000",

            'thumbnail_data.image.required' => "You must select a thumbnail for your product.",
            'thumbnail_data.image.max' => "Thumbnail can be at most 5Mb.",
            'thumbnail_data.image.mimes' => "Thumbnail can be of types jpg,png,jpeg.",
            'thumbnail_data.image.image' => "Thumbnail field is an image.",

            'sizes.required' => "Select at least 1 size for your product.",
            'sizes_data.*.alternative_sizes.max' => "You can have at most 10 columns",

            'colors.required' => "Select at least 1 color for your product.",
            'colors.*.regex' => "Colors should be in hex format ex. #000000",
            'colors.*.distinct' => 'Colors should be unique',
            
            'category_id.required' => "Category field is required.",
            'category_id.exists' => "Category does not exist.",

            "sale_start_date.required_if" => "Sale start date is required if sale is ON.",
            "discount_percentage.required_if" => "Discount percentage is required if sale is ON."
        ];
    }
}
