<?php

namespace App\Exceptions;

class NotValidFileType extends TransformerException
{
    /**
     * @var string
     */
    private $fileType;

    public function __construct(string $message, string $fileType)
    {
        parent::__construct($message);

        $this->fileType = $fileType;
    }

    /**
     * @return string
     */
    public function getFileType(): string
    {
        return $this->fileType;
    }

    /**
     * @param string $fileType
     */
    public function setFileType(string $fileType): void
    {
        $this->fileType = $fileType;
    }
}
