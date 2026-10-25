<?php

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;

class PlanAccessTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('access_type')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:plan_access_types,slug,'.$id],
            'description' => ['nullable', 'string'],
            'start_time' => ['nullable', 'required_if:is_anytime,false', 'date_format:H:i:s'],
            'end_time' => ['nullable', 'required_if:is_anytime,false', 'date_format:H:i:s', 'after:start_time'],
            'is_anytime' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
