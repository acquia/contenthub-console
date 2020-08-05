<?php

namespace Acquia\Console\ContentHub\Command\Helpers;

use Acquia\Console\ContentHub\Client\ContentHubClientFactory;
use Drupal\acquia_contenthub\Form\ContentHubSettingsForm;
use Drupal\Core\Form\FormState;
use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use EclipseGc\CommonConsole\Command\Traits\OptionsCheckerTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ContentHubRegistrar.
 *
 * Registers a new client using the provided credentials and returns the
 * output of the operation in json format.
 *
 * @package Acquia\Console\ContentHub\Command\Helpers
 */
class ContentHubRegistrar extends Command implements PlatformBootStrapCommandInterface {

  use OptionsCheckerTrait;
  use PlatformCmdOutputFormatterTrait;
  use PlatformCommandExecutionTrait;

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:register';

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
    $this->setDescription('Registers a new client using "drush acquia:contenthub-connect-site" command.')
      ->setHidden(TRUE)
      ->addOption('name', '', InputOption::VALUE_OPTIONAL, 'Content Hub client name', '')
      ->addOption('hostname', '', InputOption::VALUE_REQUIRED, 'Content Hub host name')
      ->addOption('api-key', '', InputOption::VALUE_REQUIRED, 'Content Hub api key')
      ->addOption('secret-key', '', InputOption::VALUE_REQUIRED, 'Content Hub secret key');
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  protected function initialize(InputInterface $input, OutputInterface $output) {
    parent::initialize($input, $output);

    $this->checkRequiredOptions(
      $this->getDefinition()->getOptions(),
      $input
    );
  }

  /**
   * {@inheritDoc}
   *
   * @throws \Exception
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $name = $input->getOption('name') ?: \Drupal::service('uuid')->generate();
    static::register([
      'name' => $name,
      'api_key' => $input->getOption('api-key'),
      'secret_key' => $input->getOption('secret-key'),
      'hostname' => $input->getOption('hostname'),
    ]);

    $config = \Drupal::config('acquia_contenthub.admin_settings');
    if ($config->isNew()) {
      $output->writeln($this->toJsonError(
        'Registration was unsuccessful, configuration could not be created.'
      ));
      return 1;
    }

    $output->writeln($this->toJsonSuccess([
      'origin' => $config->get('origin'),
      'client_name' => $name,
    ]));

    return 0;
  }

  /**
   * Registers a new client by posting ContentHubSettingsForm form.
   *
   * @param array $credentials
   *   The credentials to use for the registration.
   *
   * @throws \Exception
   */
  public static function register(array $credentials): void {
    $form_state = new FormState();
    $form_state->setValues([
      'hostname' => $credentials['hostname'],
      'api_key' => $credentials['api_key'],
      'secret_key' => $credentials['secret_key'],
      'client_name' => $credentials['name'],
      'op' => t('Save configuration'),
    ]);

    $form_builder = \Drupal::formBuilder();
    $form = $form_builder->buildForm(ContentHubSettingsForm::class, new FormState());
    $form_state->setTriggeringElement($form['actions']['submit']);
    $form_builder->submitForm(ContentHubSettingsForm::class, $form_state);
    $errors = $form_state->getErrors();
    if ($errors) {
      $message = 'The following errors occurred during registration:' . PHP_EOL;
      foreach ($errors as $field => $error) {
        $message .= "$field: {$error->__toString()}" . PHP_EOL;
      }
      throw new \Exception($message);
    }
  }

}
