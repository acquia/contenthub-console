# Acquia Content Hub Console
Acquia Cloud Content Hub Console provides a command line tool to execute Acquia Content Hub related commands on all sites that belong to a 
particular platform, like
  - Acquia Cloud
  - Acquia Cloud - Multisite
  - Acquia Site Factory 

One of the most important commands that is provided by this tool is the one that allows you to upgrade your sites from
Content Hub 1.x to 2.x by executing a single command and performing all the operations that are required by the upgrade. 

# Installation
Install the package with the latest version of composer:

    $composer require acquia/contenthub-console
    $composer install

Note that this package must be installed locally and in the codebase on your remote platform (Acquia Cloud or Acquia 
Site Factory) in order for commands to work.

# Create a Platform

In order for this tool to execute commands remotely on your Acquia Cloud Platform, first create a platform with the 
following command:

    $./vendor/bin/commoncli pc
    
This command will guide you through the platform creation. Notice that the alias given to this platform will be what you 
will use later when executing commands remotely.
    
# Usage
The following are some of the commands that are available to you:

    ./vendor/bin/commoncli 
    CommonConsole 0.0.1
    
    Usage:
      command [options] [arguments]
    
    Options:
      -h, --help            Display this help message
      -q, --quiet           Do not output any message
      -V, --version         Display this application version
          --ansi            Force ANSI output
          --no-ansi         Disable ANSI output
      -n, --no-interaction  Do not ask any interactive question
          --uri[=URI]       The url from which to mock a request.
          --bare            Prevents output styling.
      -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
    
    Available commands:
      help                               Displays help for a command
      list                               Lists commands
     ace
      ace:cron:check                     [ace-cch] Checks for Scheduled Jobs which are running Content Hub queues.
      ace:cron:create                    [ace-cc] Creates Scheduled Jobs for Acquia Content Hub Export/Import queues.
      ace:cron:list                      [ace-cl] Lists Scheduled Jobs.
      ace:database:backup:create         [ace-dbcr] Creates database backups.
      ace:database:backup:delete         [ace-dbdel] Deletes database backups.
      ace:database:backup:list           [ace-dbl] Lists database backups.
      ace:database:backup:restore        [ace-dbres] Restores database backups.
     ace-multi
      ace-multi:cron:create              [ace-ccm] Create cron jobs for queues in multi-site environment.
      ace-multi:database:backup:create   [ace-dbcrm] Creates database backups for ACE Multi-site environments.
      ace-multi:database:backup:delete   [ace-dbdelm] Deletes database backups for ACE Multi-site environments.
      ace-multi:database:backup:list     [ace-dblm] Lists database backups for ACE Multi-site environments.
      ace-multi:database:backup:restore  [ace-dbresm] Restores database backups for ACE Multisite environments.
     ach
      ach:audit:config-uuid              [audit-uuid] Audits configuration entities for empty UUIDs.
      ach:audit:full                     [ach-audit] Audits an existing site and code base to determine if there are any Content Hub level concerns.
      ach:audit:publisher-queue          [ach-apq] Checks whether the publisher queue is empty and there are no queued entities in the publisher tracking table.
      ach:audit:settings                 [ach-as] Audits Content Hub settings for differences between database settings and overridden ones.
      ach:clients                        [ach-cl] Lists the clients registered in the Acquia Content Hub Subscription.
      ach:custom-fields                  [ach-cf] Checks if custom field type implementations are supported by Content Hub.
      ach:drush                          [drush] A wrapper for running Drush commands.
      ach:health-check:interest-diff     [ach-hc-id] Lists the differences between webhook's interest list and export/import tracking tables.
      ach:health-check:webhook-status    [ach-hc-ws] Prints status of Webhooks and if they are suppressed.
      ach:layout-builder-defaults        [ach-lbd] Checks Layout Builder defaults usage.
      ach:migrate:purge-delwh            [ach-pdw] Purges Content Hub Subscription and deletes Webhooks.
      ach:module:version                 [ach-mv] Checks if platform sites have the Content Hub module 2.x version.
      ach:panelizer-check                [ach-pan] Checks use of Panelizer module.
      ach:subscription                   [ach-sub] Sets up the credentials for a Content Hub Subscription.
      ach:upgrade:start                  [ach-ustart] Starts the Upgrade Process from Acquia Content Hub 1.x to 2.x.
      ach:verify-current-site-webhook    [ach-vcsw] Verify if this site's webhook as defined in the configuration is actually registered in the Content Hub service.
      ach:webhook:status                 [ach-ws] Uses the Content Hub Service to collect information about the status of webhooks.
     acsf
      acsf:cron:check                    [acsf-cch] Checks for Scheduled Jobs which are running Content Hub queues.
      acsf:cron:create                   [acsf-cc] Creates Scheduled Jobs for Acquia Content Hub Export/Import queues.
      acsf:cron:list                     [acsf-cl] List Scheduled Jobs
      acsf:database:backup:create        [acsf-dbc] Creates database backups for each site on the ACSF platform.
      acsf:database:backup:delete        [acsf-dbd] Deletes a database backup of a site in the ACSF platform.
      acsf:database:backup:list          [acsf-dbl] List database backups for ACSF sites.
      acsf:database:backup:restore       [acsf-dbr] Restores database backups for ACSF sites.
     backup
      backup:create                      [bc] Creates a backup bundle of Acquia Content Hub Service snapshot and database site backups.
      backup:delete                      [bd] Deletes a backup bundle of Acquia Content Hub Service snapshot and database site backups.
      backup:list                        [bl] List available backup bundles of Content Hub Service snapshots and database site backups.
      backup:restore                     [br] Restores a backup bundle of Acquia Content Hub Service snapshot and database site backups.
     drush
      drush:version                      Checks drush version on the server.
     platform
      platform:create                    [pc] Create a new platform on which to execute common console commands.
      platform:delete                    [pdel] Deletes the specified platform.
      platform:describe                  [pd] Obtain more details about a platform.
      platform:list                      [pl] List available platforms.
      platform:sites                     List available sites registered in the platform.

The following command will allow you to upgrade your sites from Content Hub 1.x to 2.x in your platform 'sample-platform'':

    $./vendor/bin/commoncli ach:upgrade:start @sample-platform
   
Where the platform "sample-platform" has been previously created.

## Documentation

Documentation `docs/api` can be generated using [phpDocumentor v3](https://www.phpdoc.org/).  
The fastest and easiest way to generate a documentation without pollution:
```bash
docker run -it --rm -v $(pwd):/data phpdoc/phpdoc:3 -t docs/api -d .
```

## Copyright and license

Copyright &copy; 2021 Acquia Inc.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
