<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\LiveStreamUrl;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateUserLiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'url' => ['required', 'string', 'max:2048', $this->container->make(LiveStreamUrl::class)],
        ];
    }
}
