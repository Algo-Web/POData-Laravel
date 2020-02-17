<?php

namespace AlgoWeb\PODataLaravel\Requests;

use Illuminate\Foundation\Http\FormRequest as Request;

class TestRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string',
            'added_at' => 'required|date',
            'weight' => 'required|numeric',
            'code' => 'required|string',
            'success' => 'required|boolean' // whether to trip a successful result or error
        ];
    }
}
