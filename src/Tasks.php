<?php

namespace EC\OpenEuropa\Robo;

use Robo\Robo;
use Robo\Tasks as RoboTasks;

/**
 * Class Tasks.
 *
 * @package EC\OpenEuropa\Robo\Task\Build
 */
class Tasks extends RoboTasks {

  use \Boedah\Robo\Task\Drush\loadTasks;
  use \NuvoleWeb\Robo\Task\Config\loadTasks;

  /**
   * Setup Behat.
   *
   * @command project:setup-behat
   * @aliases psb
   */
  public function projectSetupBehat() {
    $tokens = $this->config('behat.tokens');
    $this->collectionBuilder()->addTaskList([
      $this->taskFilesystemStack()->copy($this->config('behat.source'), $this->config('behat.destination'), TRUE),
      $this->taskReplaceInFile($this->config('behat.destination'))->from(array_keys($tokens))->to($tokens),
    ])->run();
  }

  /**
   * Setup PHPUnit.
   *
   * @command project:setup-phpunit
   * @aliases pspu
   */
  public function projectSetupPhpUnit() {
    $tokens = $this->config('phpunit.tokens');
    $destination = $this->config('phpunit.destination');
    $this->collectionBuilder()->addTaskList([
      $this->taskFilesystemStack()->copy($this->config('phpunit.source'), $destination, TRUE),
      $this->taskReplaceInFile($destination)->from(array_keys($tokens))->to($tokens),
    ])->run();
    $this->setupPhpUnitXml($destination);
  }

  /**
   * Install site.
   *
   * @command project:install
   * @aliases pi
   */
  public function projectInstall() {
    $this->getInstallTask()
      ->siteInstall($this->config('site.profile'))
      ->run();
    $this->projectSetupSettings();
  }

  /**
   * Install site from given configuration.
   *
   * @command project:install-config
   * @aliases pic
   */
  public function projectInstallConfig() {
    $this->getInstallTask()
      ->arg('config_installer_sync_configure_form.sync_directory=' . $this->config('settings.config_directories.sync'))
      ->siteInstall('config_installer')
      ->run();
    $this->projectSetupSettings();
  }

  /**
   * Setup Drupal settings.
   *
   * @command project:setup-settings
   * @aliases pss
   */
  public function projectSetupSettings() {
    $settings_file = $this->root() . '/build/sites/default/settings.php';
    $processor = new SettingsProcessor(Robo::config());
    $content = $processor->process($settings_file);
    $this->collectionBuilder()->addTaskList([
      $this->taskFilesystemStack()->chmod('build/sites', 0775, 0000, TRUE),
      $this->taskWriteToFile($settings_file)->text($content),
    ])->run();
  }

  /**
   * Get installation task.
   *
   * @return \Boedah\Robo\Task\Drush\DrushStack
   *   Drush installation task.
   */
  protected function getInstallTask() {
    return $this->taskDrushStack($this->config('bin.drush'))
      ->arg("--root={$this->root()}/build")
      ->siteName($this->config('site.name'))
      ->siteMail($this->config('site.mail'))
      ->locale($this->config('site.locale'))
      ->accountMail($this->config('account.mail'))
      ->accountName($this->config('account.name'))
      ->accountPass($this->config('account.password'))
      ->dbPrefix($this->config('database.prefix'))
      ->dbUrl(sprintf("mysql://%s:%s@%s:%s/%s",
        $this->config('database.user'),
        $this->config('database.password'),
        $this->config('database.host'),
        $this->config('database.port'),
        $this->config('database.name')));
  }

  /**
   * Get root directory.
   *
   * @return string
   *   Root directory.
   */
  protected function root() {
    return getcwd();
  }

  /**
   * Sets-up the PHPUnit file.
   *
   * @param string $file
   *   The local config file to be parsed.
   */
  protected function setupPhpUnitXml($file) {
    // Load the PHPUnit file.
    $document = new \DOMDocument('1.0', 'UTF-8');
    $document->preserveWhiteSpace = FALSE;
    $document->formatOutput = TRUE;
    $document->load($file);

    $phpunit = $document->getElementsByTagName('phpunit')->item(0);

    // Fix the bootstrap path.
    if (!empty($this->config('phpunit.bootstrap'))) {
      $phpunit->setAttribute('bootstrap', $this->config('phpunit.bootstrap'));
    }
    // Add the printer class, if any.
    if (!empty($this->config('phpunit.printer_class'))) {
      $phpunit->setAttribute('printerClass', $this->config('phpunit.printer_class'));
    }

    // Add test suites.
    $test_suites = $document->getElementsByTagName('testsuites')->item(0);
    if ($suites = $this->config('phpunit.test_suites')) {
      foreach ($suites as $suite_name => $suite) {
        if ($suite) {
          $test_suite = $document->createElement('testsuite');
          $test_suite->setAttribute('name', $suite_name);
          foreach ($suite as $type => $paths) {
            foreach ($paths as $path) {
              $element = $document->createElement($type);
              $element->textContent = $path;
              $test_suite->appendChild($element);
            }
          }
          $test_suites->appendChild($test_suite);
        }
      }
    }

    // Save the file.
    file_put_contents($file, $document->saveXML());
  }

}
