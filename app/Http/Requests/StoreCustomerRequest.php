<?php

namespace App\Http\Requests;

use App\Models\Customer;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class StoreCustomerRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('customer_create');
    }

    public function rules()
    {
        return [
            'name'        => [
                'string',
                'required',
                'unique:customers',
            ],
            'description' => [
                'string',
                'nullable',
            ],
            'menu_domain' => [
                'string',
                'nullable',
            ],
        ];
    }
}
