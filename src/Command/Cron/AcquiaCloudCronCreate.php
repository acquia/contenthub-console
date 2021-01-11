<?php

namespace Acquia\Console\ContentHub\Command\Cron;

use Acquia\Console\Cloud\Command\AcquiaCloudCommandBase;
use Acquia\Console\Helpers\Client\PlatformCommandExecutioner;
use Acquia\Console\ContentHub\Command\ContentHubQueue;
use Acquia\Console\Helpers\Command\PlatformCmdOutputFormatterTrait;
use AcquiaCloudApi\Endpoints\Crons;
use AcquiaCloudApi\Endpoints\Servers;
use Symfony\Component\Console\Helper\HelperInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class AcquiaCloudCronCreate.
 *
 * @package Acquia\Console\ContentHub\Command\Cron
 */
class AcquiaCloudCronCreate extends AcquiaCloudCommandBase {

  use PlatformCmdOutputFormatterTrait;

  /**
   * The platform command executioner.
   *
   * @var \Acquia\Console\Helpers\Client\PlatformCommandExecutioner
   */
  protected $platformCommandExecutioner;

  /**
   * {@inheritdoc}
   */
  public static $defaultName = 'ace:cron:create';

  /**
   * {@inheritdoc}
   */
  public function configure() {
    $this->setDescription('Creates Scheduled Jobs for Acquia Content Hub Export/Import queues.');
    $this->setAliases(['ace-cc']);
  }

  /**
   * AcquiaCloudCronCreate constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher.
   * @param \Acquia\Console\Helpers\Client\PlatformCommandExecutioner $platform_command_executioner
   *   The platform command executioner.
   * @param string|NULL $name
   *   Command name.
   */
  public function __construct(EventDispatcherInterface $event_dispatcher, PlatformCommandExecutioner $platform_command_executioner, string $name = NULL) {
    parent::__construct($event_dispatcher, $name);
    $this->platformCommandExecutioner = $platform_command_executioner;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $helper = $this->getHelper('question');
    $job_count = $this->getQueueRunnersCount($input, $output, $helper);

    $raw = $this->platformCommandExecutioner->runWithMemoryOutput(ContentHubQueue::getDefaultName(), $this->getPlatform('source'));
    $sites = $this->getSiteInfo();

    $counter = 0;
    $lines = explode(PHP_EOL, trim($raw));
    foreach ($lines as $line) {
      $data = $this->fromJson($line, $output);
      if (!$data) {
        continue;
      }
      foreach ($sites as $site) {
        if ($site['active_domain'] === $data->base_url) {
          try {
            $servers = $this->getServerInfo($site['env_uuid']);
            if (count($servers) > 1) {
              $server_question = new ChoiceQuestion('Please select which server to use for running the scheduled jobs.', $servers);
              $server_name = $helper->ask($input, $output, $server_question);
            }
          }
          catch (\Exception $e) {
            $servers = [];
          }
          $data->server_id = isset($server_name) ? $servers[$server_name] : NULL;
          for ($i = 0; $i < $job_count; $i++) {
            $data->counter = $counter;
            $data->file_name = $this->generateLogFileName($data);
            $message = $this->createScheduledJob($site['env_uuid'], $data);
            $output->writeln($message);
            $counter++;
          }
        }
      }
    }

    return 0;
  }

  /**
   * Gets queue runners count.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   InputInterface instance.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   OutputInterface instance.
   * @param \Symfony\Component\Console\Helper\HelperInterface $helper
   *   Symfony helper instance.
   *
   * @return int
   *   Amount of queue runners.
   */
  protected function getQueueRunnersCount(InputInterface $input, OutputInterface $output, HelperInterface $helper): int {
    $output->writeln('<warning>You are about to create scheduled jobs for ACH import/export queues</warning>');
    $output->writeln('<warning>You can check the currently available jobs with ace-cl / acsf-cl command.</warning>');

    $question = new Question('How many queue-runners would you like to create for each queue type per site (1)? ', 1);
    $question->setValidator(function ($answer) {
      $answer = intval($answer);
      if ($answer === 0) {
        throw new \RuntimeException(
          'Please enter a valid number which is not 0!'
        );
      }

      return $answer;
    });


    $job_count = $helper->ask($input, $output, $question);
    $output->writeln("<warning>You are about to create {$job_count} cron per site!</warning>");

    $confirm_question = new ConfirmationQuestion('Do you want to proceed (y/n)? ');
    if (!$helper->ask($input, $output, $confirm_question)) {
      $output->writeln('<comment>Terminated by user.</comment>');
      return 0;
    }

    return $job_count;
  }


  /**
   * Generate unique name for log file.
   *
   * @param object $data
   *   Data object with command related values.
   *
   * @return string
   *   Name of log file.
   */
  protected function generateLogFileName(object $data): string {
    return substr($data->queue_name, 0, '3') . $data->counter;
  }

  /**
   * Gather site information for creating cron jobs.
   *
   * @return array
   *   Return site info from platform config.
   */
  protected function getSiteInfo(): array {
    return $this->platform->getActiveDomains();
  }

  /**
   * Create scheduled job for ACH import and export queue run.
   *
   * @param string $env_id
   *   Environment id.
   * @param object $data
   *   Data object with command related values.
   *
   * @return string
   *   Response message.
   */
  protected function createScheduledJob(string $env_id, object $data): string {
    $cron = new Crons($this->acquiaCloudClient);
    $frequency = "*/1 * * * *";
    $label = "ACH queue {$data->base_url}: {$data->queue_name} {$data->file_name}";
    $command = $this->createCommandForQueueRun($env_id, $data);

    if ($command === '') {
      return 'Scheduled job cannot be generated.';
    }

    $response = $cron->create($env_id, $command, $frequency, $label, $data->server_id);

    return $response->message;
  }

  /**
   * Gather server info.
   *
   * @param string $env_id
   *   Environment UUID.
   *
   * @return array
   *   Return server info.
   */
  protected function getServerInfo(string $env_id): array {
    $server_class = new Servers($this->acquiaCloudClient);
    $response = $server_class->getAll($env_id);
    $info = [];
    foreach ($response as $server_response) {
      if (in_array('web', $server_response->roles, TRUE)) {
        $info[$server_response->name] = $server_response->id;
      }
    }

    return $info;
  }

  /**
   * Create a command for scheduled job.
   *
   * @param string $env_id
   *   Environment id.
   * @param object $data
   *   Data object with command related values.
   *
   * @return string
   *   Command for the scheduled job.
   */
  protected function createCommandForQueueRun($env_id, object $data): string {
    $response = $this->acquiaCloudClient->request('get', "/environments/$env_id");
    if (!is_object($response)) {
      return '';
    }

    [$account] = explode('@', $response->ssh_url);
    $drush_command = "drush -v -l {$data->base_url} --root=/var/www/html/{$account}/docroot queue-run acquia_contenthub_{$data->queue_name}";

    return "flock -xn /tmp/ach_{$data->file_name}.lck -c '{$drush_command}' &>> /var/log/sites/$account/logs/{$data->file_name}.log";
  }

}
