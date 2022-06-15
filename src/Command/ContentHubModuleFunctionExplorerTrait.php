<?php

namespace Acquia\Console\ContentHub\Command;

/**
 * ContentHub audit command related functions.
 */
trait ContentHubModuleFunctionExplorerTrait {

  /**
   * Hooks provided by CH 1.x module.
   *
   * @var array
   */
  protected $hooks = [
    'acquia_contenthub_drupal_to_cdf_alter',
    'acquia_contenthub_cdf_from_drupal_alter',
    'acquia_contenthub_cdf_from_hub_alter',
    'acquia_contenthub_drupal_from_cdf_alter',
    'acquia_contenthub_exclude_fields_alter',
    'acquia_contenthub_field_type_mapping_alter',
    'acquia_contenthub_cdf_alter',
    'acquia_contenthub_is_eligible_entity',
  ];

  /**
   * Tokenizes module functions.
   *
   * @todo considering genericizing this in another class.
   *
   * @param string $file
   *   The file to tokenize.
   *
   * @return array
   *   Array.
   */
  protected function getModuleFunctions(string $file): array {
    $source = file_get_contents($file);
    $tokens = token_get_all($source);

    $functions = [];
    $nextStringIsFunc = FALSE;
    $inClass = FALSE;
    $bracesCount = 0;

    foreach ($tokens as $token) {
      switch ($token[0]) {
        case T_CLASS:
          $inClass = TRUE;
          break;

        case T_FUNCTION:
          if (!$inClass) {
            $nextStringIsFunc = TRUE;
          }
          break;

        case T_STRING:
          if ($nextStringIsFunc) {
            $nextStringIsFunc = FALSE;
            $functions[] = $token[1];
          }
          break;

        // Anonymous functions.
        case '(':
        case ';':
          $nextStringIsFunc = FALSE;
          break;

        // Exclude Classes.
        case '{':
          if ($inClass) {
            $bracesCount++;
          }
          break;

        case '}':
          if ($inClass) {
            $bracesCount--;
            if ($bracesCount === 0) {
              $inClass = FALSE;
            }
          }
          break;
      }
    }

    return $functions;
  }

}
