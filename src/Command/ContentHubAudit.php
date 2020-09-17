<?php

namespace Acquia\Console\ContentHub\Command;

use Acquia\Console\ContentHub\Command\Helpers\CommandExecutionTrait;
use Drupal\Core\Config\Entity\ConfigEntityType;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ContentHubAudit extends Command implements PlatformBootStrapCommandInterface {

  use CommandExecutionTrait;

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:audit:full';

  /**
   * {@inheritdoc}
   */
  public function getPlatformBootstrapType(): string {
    return 'drupal8';
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('Audit an existing code base to determine if there are any ContentHub level concerns.');
    $this->addOption('fix', 'f', InputOption::VALUE_NONE, 'Run audit command and fix any errors found.');
    $this->addOption('early-return', 'er', InputOption::VALUE_NONE, 'Run audit command and return early at first error.');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $return_early = $input->getOption('early-return');
    // CH 1.x hook implementations
    $this->findDeprecatedHookImplementations($output);
    // Custom code that uses 1.x CH services.
    // Missing UUIDs
    $status_code = $this->executeCommand(ContentHubAuditCheckUuid::getDefaultName(), $input, $output);
    if ($return_early && $status_code !== 0) {
      $output->writeln('<error>Please fix the entities without UUIDs before proceeding.</error>');
      return $status_code;
    }

    // Tmp file bs
    $status_code = $this->executeCommand(ContentHubAuditTmpFiles::getDefaultName(), $input, $output);
    if ($return_early && $status_code !== 0) {
      $output->writeln('<error>Please fix the temporary files before proceeding.</error>');
      return $status_code;
    }

    // Check for depcalc module.
    $status_code = $this->executeCommand(ContentHubAuditDepcalc::getDefaultName(), $input, $output);
    if ($return_early && $status_code === 2) {
      $output->writeln('<error>Using Composer, please add the Depcalc module to your codebase.</error>');
      return $status_code;
    }

    // Synchronize Content Hub settings overwrites.
    $status_code = $this->executeCommand(ContentHubAuditChSettings::getDefaultName(), $input, $output);
    if ($return_early && $status_code !== 0) {
      $output->writeln('<error>Settings do not match. Please rerun the audit command with "-fix" option before proceeding.</error>');
      return $status_code;
    }

    // What stream wrappers exist on this site?
    $this->executeCommand(ContentHubAuditStreamWrappers::getDefaultName(), $input, $output);
    // Problematic modules
    // Layout Builder defaults
    // Active connection to Plexus
    // Origin/domain mismatch (within Plexus)
    // SSL Check
    if ($input->getOption('uri')) {
      // $status_code = $this->executeCommand(ContentHubAuditSslCertificate::getDefaultName(), $input, $output);
      // if ($return_early && $status_code !== 0) {
        // $output->writeln('<error>Content Hub requires valid SSL certificates. Please fix the SSL certificate for this site before proceeding by running the audit command with "--fix" option.</error>');
        // return $status_code;
      // }
    }
    else {
      $output->writeln('<warning>No SSL check was run because the --uri option was not passed.</warning>');
    }

    $output->writeln('Audit command executed successfully. Please proceed.');
    return 0;
  }

  /**
   * Finds deprecated hook implementations for ContentHub.
   *
   * @todo considering genericizing this in a trait.
   *
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output object.
   */
  protected function findDeprecatedHookImplementations(OutputInterface $output) {
    $ok = TRUE;
    $kernel = \Drupal::service('kernel');
    $directories = [
      $kernel->getAppRoot(),
      "{$kernel->getAppRoot()}/{$kernel->getSitePath()}",
    ];

    foreach ($directories as $directory) {
      if (!file_exists("$directory/modules")) {
        continue;
      }
      $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator("$directory/modules"));
      $regex = new \RegexIterator($iterator, '/^.+\.module$/i', \RecursiveRegexIterator::GET_MATCH);

      $hooks = [
        "acquia_contenthub_drupal_to_cdf_alter",
        "acquia_contenthub_cdf_from_drupal_alter",
        "acquia_contenthub_cdf_from_hub_alter",
        "acquia_contenthub_drupal_from_cdf_alter",
        "acquia_contenthub_exclude_fields_alter",
        "acquia_contenthub_field_type_mapping_alter",
        "acquia_contenthub_cdf_alter",
        "acquia_contenthub_is_eligible_entity",
      ];

      foreach ($regex as $module_file) {
        $functions = $this->getModuleFunctions($module_file[0]);
        $file_info = pathinfo($module_file[0]);
        foreach ($hooks as $hook) {
          if (array_search("{$file_info['filename']}_$hook", $functions) !== FALSE) {
            $ok = FALSE;
            $output->writeln(sprintf("The %s module implements ContentHub 1.x hook %s and must be converted before upgrading to 2.x.", $file_info['filename'], $hook));
          }
        }
      }
    }
    if ($ok) {
      // Let's style this so it looks awesome.
      $output->writeln("No deprecated ContentHub 1.x API implementations detected.");
    }
  }

  /**
   * Tokenizes module functions.
   *
   * @todo considering genericizing this in another class.
   *
   * @param string $file
   *   The file to tokenize.
   *
   * @return array
   */
  protected function getModuleFunctions(string $file) {
    $source = file_get_contents($file);
    $tokens = token_get_all($source);

    $functions = array();
    $nextStringIsFunc = false;
    $inClass = false;
    $bracesCount = 0;

    foreach($tokens as $token) {
      switch($token[0]) {
        case T_CLASS:
          $inClass = true;
          break;
        case T_FUNCTION:
          if(!$inClass) $nextStringIsFunc = true;
          break;

        case T_STRING:
          if($nextStringIsFunc) {
            $nextStringIsFunc = false;
            $functions[] = $token[1];
          }
          break;

        // Anonymous functions
        case '(':
        case ';':
          $nextStringIsFunc = false;
          break;

        // Exclude Classes
        case '{':
          if($inClass) $bracesCount++;
          break;

        case '}':
          if($inClass) {
            $bracesCount--;
            if($bracesCount === 0) $inClass = false;
          }
          break;
      }
    }

    return $functions;
  }

}
