<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UpdateAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'image',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:5120',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'file' => '头像图片',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $contentType = strtolower((string) $this->header('Content-Type'));
        $file = $this->file('file');

        if (
            ! str_starts_with($contentType, 'multipart/form-data')
            && ! $file instanceof UploadedFile
        ) {
            throw new HttpException(415, 'Unsupported media type.');
        }

        $failedRules = $validator->failed()['file'] ?? [];

        if (
            array_key_exists('Max', $failedRules)
            || (
                $file instanceof UploadedFile
                && in_array($file->getError(), [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
            )
        ) {
            throw new HttpException(413, 'Uploaded avatar is too large.');
        }

        if (
            array_key_exists('Image', $failedRules)
            || array_key_exists('Mimetypes', $failedRules)
        ) {
            throw new HttpException(415, 'Unsupported avatar image type.');
        }

        parent::failedValidation($validator);
    }
}
