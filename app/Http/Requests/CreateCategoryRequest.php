<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateCategoryRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $rules = [
            "parent_id" => ["bail", "required","min:-1",'integer' ],
            "name" => ['bail','required', "string","min:1", "max:500"],
            'display_name' => ['bail','required',"string","min:1", "max:500"],
            'description' => ['bail','required',"string","min:1", "max:1000"],
            'image' => ["bail","nullable",'image', 'max:5000', 'mimes:jpg,png,jpeg']
        ];

        // Check if parent_id is not -1, then add the exists rule
        if ($this->input('parent_id') != -1) {
            $rules['parent_id'][] = 'exists:categories,id';
        }

        return $rules;
    }
}
