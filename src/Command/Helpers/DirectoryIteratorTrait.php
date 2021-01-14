<?php

namespace Acquia\Console\ContentHub\Command\Helpers;

/**
 * Trait DirectoryIteratorTrait.
 *
 * @package Acquia\Console\ContentHub\Command\Helpers
 */
trait DirectoryIteratorTrait {

  /**
   * Returns RegexIterator filtered by given pattern.
   *
   * @param string $pattern
   *   Regex pattern for filtering files.
   * @param string $docroot_directory
   *   Directory name as a string within site path.
   *   (modules, modules/contrib, etc...)
   *
   * @return \RegexIterator
   *   Returns iterable with file info.
   *
   * @throws \Exception
   */
  public function getFilesInfo(string $pattern, string $docroot_directory): \RegexIterator {
    $kernel = \Drupal::service('kernel');
    $directories = [
      $kernel->getAppRoot(),
      "{$kernel->getAppRoot()}/{$kernel->getSitePath()}",
    ];

    foreach ($directories as $directory) {
      if (!file_exists("$directory/$docroot_directory")) {
        continue;
      }

      $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator("$directory/$docroot_directory"));
    }

    if (!isset($iterator)) {
      throw new \Exception("Cannot find directory: $directory/$docroot_directory");
    }

    return new \RegexIterator($iterator, $pattern, \RecursiveRegexIterator::GET_MATCH);
  }

}
