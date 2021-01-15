<?php

namespace Acquia\Console\ContentHub\Command;

use Acquia\Console\ContentHub\Client\ContentHubCommandBase;
use Acquia\ContentHubClient\Settings;
use Drupal\Core\Config\Config;
use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ContentHubAuditChSettings.
 *
 * @package Acquia\Console\ContentHub\Command\Migrate
 */
class ContentHubAuditChSettings extends ContentHubCommandBase implements PlatformBootStrapCommandInterface {

  /**
   * {@inheritDoc}
   */
  protected static $defaultName = 'ach:audit:settings';

  /**
   * {@inheritDoc}
   */
  protected function configure() {
    $this->setDescription('Audits Content Hub settings for differences between database settings and overridden ones.')
      ->setHelp('Checks if there is a difference between the settings located in database and the one in settings.php')
      ->setAliases(['ach-as'])
      ->addOption(
        'fix',
        'f',
        InputOption::VALUE_NONE,
        'If set, the command will attempt to sync the settings and write the overwritten data to database.'
      );
  }

  /**
   * {@inheritDoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $output->writeln('Running Content Hub config audit...');
    $attempt_fix = (bool) $input->getOption('fix');
    $config = $this->drupalServiceFactory->getDrupalService('config.factory')->getEditable('acquia_contenthub.admin_settings');
    $config_with_overwrites = $this->getChSettings(TRUE);
    if (!array_values($config_with_overwrites)) {
      $output->writeln('<error>Content Hub configuration data should not be empty. Terminating...</error>');
      return 2;
    }

    $config_raw = $this->getChSettings();
    if (empty(array_values($config_raw))) {
      $output->writeln('<warning>Content Hub configuration stored in database is empty. Please make sure it is intentional.</warning>');
    }
    $diff = array_diff($this->normalizeWebhookConfigKeys($config_with_overwrites), $this->normalizeWebhookConfigKeys($config_raw));
    if (!empty($diff) && $attempt_fix === FALSE) {
      $output->writeln('<comment>Configuration does not match the one stored in the database.</comment>');
      $table = new Table($output);
      $table->setHeaders(['Config Key',
        'Value in Database',
        'Overwritten Value'
      ]);
      foreach ($diff as $key => $val) {
        $table->addRow([$key, $config_raw[$key] ?? '', $val]);
      }
      $table->render();
      $output->writeln('<warning>Run `--fix` to synchronize Content Hub settings.');
      return 1;
    }

    if (!empty($diff) && $attempt_fix === TRUE) {
      $this->syncSettings($config, $diff);
      $output->writeln('<info>The configuration has been synchronized.</info>');
    }
    $output->writeln('<info>Content Hub configuration is in order. You may proceed.</info>');
    return 0;
  }

  /**
   * Normalizes webhook related keys in the configuration array.
   *
   * The webhook is going to be unregistered in a later process.
   *
   * @param array $data
   *   The data array to modify.
   *
   * @return array
   *   The normalized array.
   */
  protected function normalizeWebhookConfigKeys(array &$data): array {
    foreach (array_keys($data) as $key) {
      if (strpos($key, 'webhook') !== FALSE) {
        $webhook = $data[$key];
        unset($data[$key]);
        foreach ($webhook as $key => $item) {
          $data["webhook:{$key}"] = $item;
        }
      }
    }
    return $data;
  }

  /**
   * Synchronize settings.
   *
   * @param \Drupal\Core\Config\Config $config
   *   The configuration object.
   * @param array $overwrites
   *   The values to use as overwrites.
   */
  protected function syncSettings(Config $config, array $overwrites): void {
    foreach ($overwrites as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();
  }

  /**
   * Returns the configuration in array format.
   *
   * @param bool $with_overwrites
   *   [Optional] If set to TRUE overwrites will be included.
   *
   * @return array
   *   The configuration list.
   *
   * @throws \Exception
   */
  public function getChSettings(bool $with_overwrites = FALSE): array {
    $version = $this->drupalServiceFactory->getModuleVersion();
    $factory = $this->drupalServiceFactory->getDrupalService('config.factory');
    if ($version === 1) {
      return $with_overwrites ?
        $factory->get('acquia_contenthub.admin_settings')->getOriginal() :
        $factory->getEditable('acquia_contenthub.admin_settings')->getRawData();
    }

    if ($with_overwrites) {
      $settings = $this->drupalServiceFactory->getDrupalService('acquia_contenthub.client.factory')->getSettings();
      return $this->normalize($settings);
    }

    return $factory->getEditable('acquia_contenthub.admin_settings')->getRawData();
  }

  /**
   * Generates a compatible format out of the Settings object.
   *
   * @param \Acquia\ContentHubClient\Settings $settings
   *   The content hub config representation.
   *
   * @return array
   *   The normalized config.
   */
  protected function normalize(Settings $settings): array {
    return [
      'hostname' => $settings->getUrl(),
      'api_key' => $settings->getApiKey(),
      'secret_key' => $settings->getSecretKey(),
      'origin' => $settings->getUuid(),
      'client_name' => $settings->getName(),
      'shared_secret' => $settings->getSharedSecret(),
      'webhook' => $settings->toArray()['webhook'],
    ];
  }

}
