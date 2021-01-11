<?php

namespace Acquia\Console\ContentHub\Command\Cron;

use Acquia\Console\Cloud\Command\AcquiaCloudCommandBase;
use Acquia\Console\Cloud\Platform\AcquiaCloudPlatform;
use AcquiaCloudApi\Endpoints\Crons;
use AcquiaCloudApi\Response\CronsResponse;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AcquiaCloudCronCheck.
 *
 * @package Acquia\Console\ContentHub\Command\Cron
 */
class AcquiaCloudCronCheck extends AcquiaCloudCommandBase {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ace:cron:check';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('Checks for Scheduled Jobs which are running Content Hub queues.');
    $this->setAliases(['ace-cch']);
    $this->addOption('fix', 'f', InputOption::VALUE_NONE, 'Disable scheduled jobs which are running Content Hub queues.');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $cron = new Crons($this->acquiaCloudClient);
    $filtered_crons = [];
    foreach ($this->getEnvironmentInfo() as $env_id) {
      $jobs = $cron->getAll($env_id);
      $filtered_crons = array_merge($filtered_crons, $this->filterCrons($jobs));
    }

    if (empty($filtered_crons)) {
      $output->writeln('There are no enabled cron which running ACH queues.');
      $output->writeln('<comment>If you have custom cron jobs for Content Hub, please disable them.</comment>');

      return 0;
    }

    $output->writeln('<comment>Scheduled jobs running ACH queues:</comment>');
    $table = new Table($output);
    $table->setHeaders(['Environment id', 'Environment name', 'Label', 'Cron ID']);
    $table->addRows($filtered_crons);
    $table->render();

    if (!$input->getOption('fix')) {
      return 0;
    }

    $output->writeln('<comment>Disabling scheduled jobs.</comment>');
    foreach ($filtered_crons as $filtered_cron) {
      try {
        $cron->disable($filtered_cron['env_id'], $filtered_cron['id']);
        $output->writeln("<warning>{$filtered_cron['label']} has been disabled!</warning>");
      } catch (\Exception $exception) {
        $output->writeln("<error>Cannot disable cron with id: {$filtered_cron['id']}</error>");
      }
    }

    $output->writeln('<comment>We detected and disabled the cron jobs above. If you have custom cron jobs for Content Hub, please disable them.</comment>');

    return 0;
  }

  /**
   * Get environment info from platform config.
   *
   * @return array
   *   Environment config.
   */
  protected function getEnvironmentInfo(): array {
    return $this->platform->get(AcquiaCloudPlatform::ACE_ENVIRONMENT_DETAILS);
  }

  /**
   * Returns information about enabled scheduled jobs running ACH queues.
   *
   * If cron command contains one of the following strings and not in disabled
   * status, it will be returned. (environment id, label, name, and cron id)
   *   'acquia_contenthub_export_queue'
   *   'acquia_contenthub_import_queue'
   *   'queue-run'
   *
   * @param \AcquiaCloudApi\Response\CronsResponse $jobs
   *   Response of cron listing endpoint.
   *
   * @return array
   *   Array containing information about ACH queue running scheduled jobs.
   */
  protected function filterCrons(CronsResponse $jobs): array {
    $filtered_jobs = [];

    foreach ($jobs as $job) {
      if (
        !isset($job->flags->enabled) ||
        $job->flags->enabled !== TRUE ||
        !$this->isQueueRunner($job->command)
      ) {
        continue;
      }

      $filtered_jobs[] = [
        'env_id' => $job->environment->id,
        'name' => $job->environment->name,
        'label' => $job->label,
        'id' => $job->id,
      ];
    }

    return $filtered_jobs;
  }

  /**
   * Looking for specific strings in the command string.
   *
   * @param string $command
   *   Command ran by cron job.
   *
   * @return bool
   *   True if specific string found, false otherwise.
   */
  protected function isQueueRunner(string $command): bool {
    $pattern = '/acquia_contenthub_export_queue|acquia_contenthub_import_queue|queue-run/';
    return (bool) preg_match($pattern, $command);
  }

}
