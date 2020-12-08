<?php

namespace Acquia\Console\ContentHub\Command\Helpers;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Trait PlatformCmdOutputFormatterTrait.
 *
 * @package Acquia\Console\ContentHub\Command\Helpers
 */
trait PlatformCmdOutputFormatterTrait {

  /**
   * Returns a json formatted, success message.
   *
   * @param array $data
   *   The data to format.
   *
   * @return string
   *   The encoded json.
   */
  public function toJsonSuccess(array $data): string {
    return json_encode([
      'success' => TRUE,
      'data' => $data,
    ]);
  }

  /**
   * Returns a json formatted, failed response.
   *
   * @param string $message
   *   The message of the error.
   * @param array $data
   *   The data to encode.
   *
   * @return string
   *   The formatted json.
   */
  public function toJsonError(string $message, array $data = []): string {
    $output = [
      'success' => FALSE,
      'error' => [
        'message' => $message,
      ],
    ];
    $output = $data ? array_merge($output, ['data' => $data]) : $output;

    return json_encode($output);
  }

  /**
   * Decodes json response and writes into output in case of error.
   *
   * @param string $json
   *   The decoded json.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output to write to.
   *
   * @return \stdClass|null
   *   The decoded json array.
   */
  public function fromJson(string $json, OutputInterface $output): ?\stdClass {
    $data = json_decode($json);
    // Console level error.
    if (!$data instanceof \stdClass) {
      $output->writeln($json);
      return NULL;
    }

    // Response level error.
    if (!$this->isValid($data, $output)) {
      return NULL;
    }

    return (object) $data->data;
  }

  /**
   * Checks if the returned client data is valid.
   *
   * @param object $client_data
   *   The client data.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output object to write to.
   *
   * @return bool
   *   TRUE if it is valid.
   */
  protected function isValid(object $client_data, OutputInterface $output) {
    if (!property_exists($client_data, 'success')) {
      return FALSE;
    }

    if ($client_data->success === FALSE) {
      $out = $client_data->error->message;
      if (property_exists($client_data, 'data')) {
        $out = sprintf('<error>Message: %s. Data: %s</error>', $out, var_export($client_data->data, TRUE));
      }

      $output->writeln($out);
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Helper function to get drush output.
   *
   * @param string $raw
   *   Raw output from remote output stream.
   *
   * @param OutputInterface $output
   *   Output stream.
   *
   * @param int $exit_code
   *   Exit code from drush command.
   *
   * @param string $drush_command
   *   Drush command executed.
   *
   * @param bool $print_output
   *   Whether to print the output.
   *
   * @return object|NULL
   *   Actual Drush command output.
   */
  protected function getDrushOutput(string $raw, OutputInterface $output, int $exit_code, string $drush_command, bool $print_output = TRUE): ?object {
    $result = NULL;
    $data = NULL;
    if ($exit_code > 0) {
      $output->writeln(sprintf('<error>Error executing drush command "%s" (Exit code = %s):</error>', $drush_command, $exit_code));
    }
    $lines = explode(PHP_EOL, trim($raw));
    foreach ($lines as $line) {
      $data = $this->fromJson($line, $output);
      if (!$data) {
        continue;
      }
      $result = $data;
    }
    if ($print_output && $result) {
      $output->writeln($result->drush_output);
      $output->writeln($result->drush_error);
    }
    return $result;
  }

}
