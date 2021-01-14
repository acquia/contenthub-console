<?php

namespace Acquia\Console\ContentHub\Command;

use Acquia\Console\ContentHub\Client\ContentHubCommandBase;
use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *
 */
class ContentHubCustomFields extends ContentHubCommandBase implements PlatformBootStrapCommandInterface {

  /**
   * Field types supported by ACH.
   */
  const ACCEPTABLE_FIELD_TYPES = [
    'boolean',
    'changed',
    'comment',
    'created',
    'email',
    'entity_reference',
    'entity_reference_revisions',
    'file',
    'file_uri',
    'id',
    'image',
    'integer',
    'language',
    'layout_section',
    'link',
    'parent',
    'parent_id',
    'password',
    'path',
    'revision',
    'string',
    'string_long',
    'taxonomy_term',
    'text',
    'text_long',
    'text_with_summary',
    'timestamp',
    'uri',
    'uuid',
  ];

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:custom-fields';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('Checks if custom field type implementations are supported by Content Hub.');
    $this->setAliases(['ach-cf']);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $manager = \Drupal::service('entity_field.manager');
    $field_types = [];
    foreach ($manager->getFieldMap() as $entity_fields) {
      foreach ($entity_fields as $field) {
        $field_types[] = $field['type'];
      }
    }

    $not_supported = array_diff(array_unique($field_types), self::ACCEPTABLE_FIELD_TYPES);

    if (empty($not_supported)) {
      $output->writeln('There are no unsupported field types in use. You may proceed!');
      return 0;
    }

    $not_supported = implode(", ", $not_supported);
    $output->writeln("<comment>The following unsupported field types are used in your instance: $not_supported</comment>");

    return 0;
  }

}
