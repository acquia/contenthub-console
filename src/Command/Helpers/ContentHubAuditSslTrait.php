<?php

namespace Acquia\Console\ContentHub\Command\Helpers;

use Spatie\SslCertificate\SslCertificate;

/**
 * Trait ContentHubAuditSslTrait.
 *
 * Usable within classes which want to verify the ssl certificate for a url.
 *
 * @package Acquia\Console\ContentHub\Command\Helpers
 */
trait ContentHubAuditSslTrait {
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
   *   Organization name.
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
