<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class RenameProjectRequest
 * @package App\Http\Requests
 */
class RenameProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'name' => 'required|min:2|max:32|regex:/^[a-z0-9_]+$/',
            'projectId' => 'required|uuid|exists:projects,uuid'
        ];
    }

    /**
     * @return string[]
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Project name is required.',
            'name.min' => 'Project name must contain at-least 2 characters.',
            'name.max' => 'Project name must not exceed 32 characters.',
            'name.regex' => 'Project name must only contain lowercase letters, digits and underscores.',
        ];
    }
}
