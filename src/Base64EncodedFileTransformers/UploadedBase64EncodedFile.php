<?php

namespace App\Base64EncodedFileTransformers;


use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadedBase64EncodedFile extends UploadedFile
{
    /**
     * @param Base64EncodedFile $file
     * @param string $originalName
     * @param null $mimeType
     * @param null $size
     */
    public function __construct(Base64EncodedFile $file, $originalName = '', $mimeType = null, $size = null)
    {
        parent::__construct($file->getPathname(), $originalName ?: $file->getFilename(), $mimeType, $size, null, true);
    }
}
