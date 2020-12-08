<?php

namespace Acquia\Console\ContentHub\Command;

use Spatie\SslCertificate\SslCertificate;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for checking SSL certificate.
 *
 * @package Acquia\Console\ContentHub\Command
 */
class ContentHubAuditSslCertificate extends Command {

  /**
   * List of trusted providers.
   */
  public const TRUSTED_SSL_PROVIDERS = [
    'DigiCert Inc',
  ];

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:audit:ssl-cert';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setHidden(TRUE)
      ->setDescription('Checks SSL cert');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $output->writeln('Getting SSL certificate...');
    $url = $input->getOption('uri');
    if (!$url) {
      $output->writeln("<error>No uri specified, add --uri= to your command with a valid hostname.</error>");
      return 1;
    }
    // @TODO: This needs to be fixed. The 'https' is not present in the URL.
    if (strpos($url, 'https') !== 0) {
      $output->writeln("<warning>This site does not have an SSL Certificate.</warning>");
      return 0;
    }
    try {
      $cert = $this->getCertByHostname($url);
    } catch (\Exception $e) {
      $output->writeln("<warning>Something went wrong getting the SSL cert of $url</warning>");
      return 1;
    }

    if (!$cert->isValid()) {
      $output->writeln('<error>Your SSL cert is not valid!</error>');
      return 2;
    }

    // Working with a limited list so only check $is_trusted when isSelfSigned()
    // returns TRUE
    if ($cert->isSelfSigned() && !$this->isTrustedOrganization($cert)) {
      $output->writeln('<error>Site is using self signed SSL certificate!</error>');
      return 3;
    }

    $days_until_expiration = $cert->daysUntilExpirationDate();
    $output->writeln("<info>Your SSL certificate is okay. Expires in $days_until_expiration days!</info>");

    return 0;
  }

  /**
   * Gets SSL cert of a site by hostname.
   *
   * @param string $hostname
   *   Hostname.
   *
   * @return \Spatie\SslCertificate\SslCertificate
   *   SSL cert object of given host.
   */
  public function getCertByHostname(string $hostname): SslCertificate {
    return SslCertificate::createForHostName($hostname);
  }

  /**
   * Determines if organization trusted or not.
   *
   * @param \Spatie\SslCertificate\SslCertificate $cert
   *  Organization name.
   *
   * @return bool
   *   True if organization name found in trusted providers list.
   */
  protected function isTrustedOrganization(SslCertificate $cert): bool {
    $fields = $cert->getRawCertificateFields();
    $provider = $fields['issuer']['O'] ?? '';
    return in_array($provider, self::TRUSTED_SSL_PROVIDERS, TRUE);
  }

}
