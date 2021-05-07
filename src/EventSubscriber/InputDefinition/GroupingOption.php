<?php

namespace Acquia\Console\ContentHub\EventSubscriber\InputDefinition;

use EclipseGc\CommonConsole\CommonConsoleEvents;
use EclipseGc\CommonConsole\Event\CreateApplicationEvent;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class GroupingOption
 *
 * @package Acquia\Console\ContentHub\EventSubscriber\InputDefinition
 */
class GroupingOption implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[CommonConsoleEvents::CREATE_APPLICATION] = 'onCreateApplication';
    return $events;
  }

  /**
   * Add a grouping option.
   *
   * @param \EclipseGc\CommonConsole\Event\CreateApplicationEvent $event
   *   The create input event.
   */
  public function onCreateApplication(CreateApplicationEvent $event) {
    // Provide the option for specifying groups.
    $event->getApplication()->getDefinition()->addOption(new InputOption('group', NULL, InputOption::VALUE_OPTIONAL, 'The group for which to run a request.'));
  }

}
