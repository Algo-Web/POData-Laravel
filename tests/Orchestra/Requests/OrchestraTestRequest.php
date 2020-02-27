<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 23/02/20
 * Time: 11:52 PM.
 */
namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Request;

use Illuminate\Foundation\Http\FormRequest;

class OrchestraTestRequest extends FormRequest
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
