<?php

namespace Acquia\Console\ContentHub\Tests\Helpers;

/**
 * Generates a new tmp file and records for later deletion.
 */
trait TempFileGeneratorTrait {

  /**
   * Registered files.
   *
   * @var array
   */
  private $files = [];

  /**
   * Creates a new temp file.
   *
   * @param string $fileName
   *   The name of the file.
   * @param string $content
   *   The content of the file.
   *
   * @return object
   *   The created file with two attributes: dirname, filename
   */
  public function generateTmpFile(string $fileName, string $content): object {
    $tmpDir = '/tmp';
    $fullPath = $tmpDir . '/' . $fileName;
    file_put_contents($fullPath, $content, FILE_APPEND);
    $this->files[] = $fullPath;
    $file = new \stdClass();
    $file->dirname = $tmpDir;
    $file->fileName = $fileName;
    return $file;
  }

  /**
   * Deletes all files created during a test run.
   *
   * It is advised to delete the files in the same test or as part of a tear
   * down process, so the file gets deleted even if there was an error during
   * the test run.
   */
  public function deleteCreatedTmpFiles(): void {
    foreach ($this->files as $filePath) {
      unlink($filePath);
    }
    $this->files = [];
  }

}
