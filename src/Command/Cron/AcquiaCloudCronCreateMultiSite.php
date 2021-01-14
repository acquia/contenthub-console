<?php

namespace Acquia\Console\ContentHub\Command\Cron;

use Acquia\Console\Cloud\Platform\AcquiaCloudMultiSitePlatform;
use Acquia\Console\Cloud\Platform\AcquiaCloudPlatform;
use Acquia\Console\ContentHub\Command\ContentHubQueue;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * Class AcquiaCloudCronCreateMultiSite.
 *
 * @package Acquia\Console\ContentHub\Command\Cron
 */
class AcquiaCloudCronCreateMultiSite extends AcquiaCloudCronCreate {

  /**
   * {@inheritdoc}
   */
  public static $defaultName = 'ace-multi:cron:create';

  /**
   * {@inheritdoc}
   */
  public static function getExpectedPlatformOptions(): array {
    return ['source' => AcquiaCloudMultiSitePlatform::getPlatformId()];
  }

  /**
   * {@inheritdoc}
   */
  public function configure() {
    $this->setDescription('Create cron jobs for queues in multi-site environment.');
    $this->setAliases(['ace-ccm']);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $helper = $this->getHelper('question');
    $job_count = $this->getQueueRunnersCount($input, $output, $helper);

    $sites = $this->getMultiSiteInfo($output);
    if (!$sites) {
      $output->writeln('No sites found within environment.');
      return 1;
    }

    $raw = $this->platformCommandExecutioner->runWithMemoryOutput(ContentHubQueue::getDefaultName(), $this->getPlatform('source'));
    $env_uuid = current($this->platform->get(AcquiaCloudPlatform::ACE_ENVIRONMENT_DETAILS));
    try {
      $servers = $this->getServerInfo($env_uuid);
      if (count($servers) > 1) {
        $server_question = new ChoiceQuestion('Please select which server to use for running the scheduled jobs.', $servers);
        $server_name = $helper->ask($input, $output, $server_question);
      }
    }
    catch (\Exception $e) {
      $servers = [];
    }

    $counter = 0;
    $lines = explode(PHP_EOL, trim($raw));
    foreach ($lines as $line) {
      $data = $this->fromJson($line, $output);
      if (!$data) {
        continue;
      }

      $data->server_id = isset($server_name) ? $servers[$server_name] : NULL;
      for ($i = 0; $i < $job_count; $i++) {
        $data->counter = $counter;
        $data->file_name = $this->generateLogFileName($data);

        $message = $this->createScheduledJob($env_uuid, $data);
        $output->writeln($message);
        $counter++;
      }
    }

    return 0;
  }

  /**
   * Gets multi site URIs.
   *
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   OutputInterface instance.
   *
   * @return array
   *   Array containing site URIs.
   */
  protected function getMultiSiteInfo(OutputInterface $output): array {
    $raw = $this->platformCommandExecutioner->runWithMemoryOutput(AcquiaCloudMultiSites::getDefaultName(), $this->getPlatform('source'));

    $lines = explode(PHP_EOL, trim($raw));
    foreach ($lines as $line) {
      $data = $this->fromJson($line, $output);
      if (!$data) {
        continue;
      }

      if (isset($data->sites)) {
        return (array) $data->sites;
      }
    }

    return [];
  }

}
