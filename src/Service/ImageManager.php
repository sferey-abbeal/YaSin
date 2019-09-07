<?php

namespace App\Service;

use App\Entity\Image;
use App\Exceptions\NotValidFileType;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

abstract class ImageManager
{
    private $uploadDirectory;
    /**
     * @var ImageCropperResizer
     */
    private $cropperResizer;

    private $currentTargetDirectory;
    private $currentImageSize;

    public function __construct(
        $uploadDirectory,
        ImageCropperResizer $cropperResizer,
        $directory,
        $size
    ) {
        $this->uploadDirectory = $uploadDirectory;
        $this->cropperResizer = $cropperResizer;
        $this->currentTargetDirectory = $directory;
        $this->currentImageSize = $size;
    }

    public function createImage($filename, $alt, $linkedTo): Image
    {
        $image = new Image();
        $image->setFile($filename);
        $image->setAlt($alt);
        $image->setLinkedTo($linkedTo);

        return $image;
    }

    /**
     * @param UploadedFile $file
     * @throws NotValidFileType
     */
    public function checkFileType(UploadedFile $file): void
    {
        $fileType = $file->guessExtension();
        if ($fileType !== 'jpg' && $fileType !== 'jpeg' && $fileType !== 'png') {
            $notValidFileType = new NotValidFileType(
                'File type must be jpg, jpeg or png!',
                $fileType
            );
            throw $notValidFileType;
        }
    }

    /**
     * @param UploadedFile $uploadedFile
     * @param $filename
     */
    public function saveImageInDirectory(UploadedFile $uploadedFile, $filename): void
    {
        $croppedImage = $this->cropperResizer->centerSquareCrop($uploadedFile);

        $fullPath = $this->uploadDirectory . $this->currentTargetDirectory;
        $uploadedFile->move($fullPath . 'original/', $filename);

        $pathFormat = '%s%sx%s/';
        foreach ($this->currentImageSize as $size) {
            $path = sprintf($pathFormat, $fullPath, $size['width'], $size['height']);
            $image = $this->cropperResizer->resize($croppedImage, $size['width'], $size['height']);
            $image->move($path, $filename);
        }
    }

    /**
     * @param $filename
     */
    public function removeImageFromDirectory($filename): void
    {
        $fullPath = $this->uploadDirectory . $this->currentTargetDirectory;

        $filesystem = new Filesystem();
        $pathFormat = '%s%sx%s/%s';
        foreach ($this->currentImageSize as $size) {
            $path = sprintf($pathFormat, $fullPath, $size['width'], $size['height'], $filename);
            $filesystem->remove($path);
        }
        $filesystem->remove($fullPath . 'original/' . $filename);
    }

    public function getAvailableResolutions(Image $image): array
    {
        $filename = $image->getFile();
        $resolutions = [];
        $resolutions['original'] = $this->currentTargetDirectory . 'original/' . $filename;
        foreach ($this->currentImageSize as $size) {
            $directoryFormat = '%sx%s';
            $directory = sprintf($directoryFormat, $size['width'], $size['height']);
            $pathFormat = '%s%s/%s';
            $path = sprintf($pathFormat, $this->currentTargetDirectory, $directory, $filename);
            $resolutions[$directory] = $path;
        }

        return $resolutions;
    }
}
