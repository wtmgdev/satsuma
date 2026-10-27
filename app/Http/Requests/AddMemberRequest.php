<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        $organization = $this->attributes->get('currentOrganization');

        return $organization !== null
            && $this->user()->can('manageMembers', $organization);
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', Rule::in(['admin', 'member'])],
        ];
    }
}
