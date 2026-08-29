<?php
declare(strict_types=1);

namespace RoseShayan\FormGuard\Tests;

use PHPUnit\Framework\TestCase;
use RoseShayan\FormGuard\Validator;

final class FileValidationTest extends TestCase
{
    public function testUploadedImageUsesDetectedMimeTypeInsteadOfClientMimeType(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'form-guard-');
        self::assertIsString($tmp);

        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9Zl1sAAAAASUVORK5CYII=',
            true
        );
        self::assertIsString($png);
        file_put_contents($tmp, $png);

        try {
            $validator = Validator::make(
                [
                    'avatar' => [
                        'name' => 'avatar.png',
                        'type' => 'text/plain',
                        'tmp_name' => $tmp,
                        'error' => UPLOAD_ERR_OK,
                        'size' => filesize($tmp),
                    ],
                ],
                [
                    'avatar' => 'required|file|image|max_file:10|mimetypes:image/png|extensions:png',
                ]
            );

            self::assertTrue($validator->passes());
        } finally {
            @unlink($tmp);
        }
    }

    public function testMissingOptionalUploadIsIgnored(): void
    {
        $validator = Validator::make(
            [
                'avatar' => [
                    'name' => '',
                    'type' => '',
                    'tmp_name' => '',
                    'error' => UPLOAD_ERR_NO_FILE,
                    'size' => 0,
                ],
            ],
            ['avatar' => 'file|image']
        );

        self::assertTrue($validator->passes());
    }

    public function testRequiredUploadRejectsNoFileError(): void
    {
        $validator = Validator::make(
            [
                'avatar' => [
                    'name' => '',
                    'type' => '',
                    'tmp_name' => '',
                    'error' => UPLOAD_ERR_NO_FILE,
                    'size' => 0,
                ],
            ],
            ['avatar' => 'required|file']
        );

        self::assertTrue($validator->fails());
        self::assertArrayHasKey('avatar', $validator->errors());
    }
}
