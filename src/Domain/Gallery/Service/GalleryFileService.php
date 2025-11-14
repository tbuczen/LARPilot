<?php

namespace App\Domain\Gallery\Service;

use App\Domain\Gallery\Entity\Gallery;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class GalleryFileService
{
    public function __construct(
        private readonly SluggerInterface $slugger,
        private readonly ParameterBagInterface $parameterBag
    ) {
    }

    /**
     * Upload a zip file for a gallery.
     *
     * @throws FileException
     */
    public function uploadZipFile(Gallery $gallery, UploadedFile $zipFile): string
    {
        $originalFilename = pathinfo($zipFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $zipFile->guessExtension();

        $uploadDirectory = $this->getGalleryUploadDirectory($gallery);

        // Create directory if it doesn't exist
        if (!is_dir($uploadDirectory)) {
            mkdir($uploadDirectory, 0755, true);
        }

        $zipFile->move($uploadDirectory, $newFilename);

        return $newFilename;
    }

    /**
     * Delete the zip file associated with a gallery.
     */
    public function deleteZipFile(Gallery $gallery): void
    {
        $zipFile = $gallery->getZipFile();
        if (!$zipFile) {
            return;
        }

        $filePath = $this->getGalleryUploadDirectory($gallery) . '/' . $zipFile;

        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    /**
     * Delete all files in the gallery directory.
     */
    public function deleteGalleryDirectory(Gallery $gallery): void
    {
        $directory = $this->getGalleryUploadDirectory($gallery);

        if (!is_dir($directory)) {
            return;
        }

        // Delete all files in directory
        $files = glob($directory . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        // Remove directory
        rmdir($directory);
    }

    private function getGalleryUploadDirectory(Gallery $gallery): string
    {
        return $this->parameterBag->get('kernel.project_dir')
            . '/public/uploads/galleries/'
            . $gallery->getId();
    }
}
