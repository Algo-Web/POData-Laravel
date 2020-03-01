<?php

declare(strict_types=1);

namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Requests;

use Illuminate\Foundation\Http\FormRequest as Request;

class TestBulkUpdateRequest extends Request
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
        $rules = ['data' => 'required|array', 'keys' => 'required|array'];
        $data  = $this->request->get('data');
        $keys  = $this->request->get('keys');

        if (isset($data)) {
            foreach ($data as $key => $val) {
                $rules['data.' . $key]               = 'required|array';
                $rules['data.' . $key . '.name']     = 'required|string';
                $rules['data.' . $key . '.added_at'] = 'required|date';
                $rules['data.' . $key . '.weight']   = 'required|numeric';
                $rules['data.' . $key . '.code']     = 'required|string';
                $rules['data.' . $key . '.success']  = 'required|boolean';
            }
        }

        if (isset($keys)) {
            foreach ($keys as $key => $val) {
                $rules['keys.' .$key]       = 'required|array';
                $rules['keys.' .$key.'.id'] = 'required|integer|min:0';
            }
        }

        return $rules;
    }
}
