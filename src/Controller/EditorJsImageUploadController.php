<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class EditorJsImageUploadController extends AbstractController
{
    #[Route('/editorjs/upload/{folderName}', name: 'editorjs_image_upload', methods: ['POST'])]
    public function uploadImage(
        Request $request,
        string $folderName,
        SluggerInterface $slugger
    ): JsonResponse {
        /** @var UploadedFile|null $file */
        $file = $request->files->get('image');

        if (!$file) {
            return new JsonResponse([
                'success' => 0,
                'error' => 'No file uploaded'
            ], 400);
        }

        // Normalize folder name
        $safeFolderName = $slugger->slug($folderName)->lower()->toString();

        // Create upload directory if it doesn't exist
        $uploadDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/' . $safeFolderName;

        if (!file_exists($uploadDirectory)) {
            mkdir($uploadDirectory, 0777, true);
        }

        // Generate a unique filename
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        try {
            // Move the file to the upload directory
            $file->move($uploadDirectory, $newFilename);

            // Return EditorJS compatible response
            return new JsonResponse([
                'success' => 1,
                'file' => [
                    'url' => '/uploads/' . $safeFolderName . '/' . $newFilename,
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => 0,
                'error' => 'File upload failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
