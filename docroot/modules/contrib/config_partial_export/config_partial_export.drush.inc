<?php
use Drupal\Core\Config\FileStorage;
use Drush\Log\LogLevel;

/**
 * @file
 * Drush integration for the config_partial_export module.
 */

/**
 * Implements hook_drush_command().
 */
function config_partial_export_drush_command() {
  $items['config-partial-export'] = array(
    'description' => 'Export a partial configuration to a directory.',
    'core' => array('8+'),
    'aliases' => array('cpex'),
    'arguments' => array(
      'config' => "Configuration keys, comma separated",
      'label' => "A config directory label (i.e. a key in \$config_directories array in settings.php). Defaults to 'sync'",
    ),
    'options' => array(),
    'required-arguments' => 1,
    'examples' => array(
      'drush config-partial-export --config core.date_format.long' => 'Export configuration; Save files in a backup directory named config-export.',
    ),
  );
  return $items;
}

/**
 * Drush command callback to perform a partial config export.
 *
 * @param $config
 * @return mixed
 */
function drush_config_partial_export($config) {
  global $config_directories;
  $choices = drush_map_assoc(array_keys($config_directories));
  unset($choices[CONFIG_ACTIVE_DIRECTORY]);
  if (!isset($destination) && count($choices) >= 2) {
    $destination = drush_choice($choices, 'Choose a destination.');
    if (empty($destination)) {
      return drush_user_abort();
    }
  }
  elseif (!isset($destination)) {
    $destination = CONFIG_SYNC_DIRECTORY;
  }
  $destination_dir = config_get_config_directory($destination);
  $destination_storage = new FileStorage($destination_dir);
  $source_storage = \Drupal::service('config.storage');

  $config_keys = explode(",", $config);
  foreach ($config_keys as $config_key) {
    _config_partial_export_write_config($config_key, $source_storage, $destination_storage);
    drush_log(dt('Writing !name to !target.', array('!name' => $config_key, '!target' => $destination_dir)), LogLevel::SUCCESS);
  }

  drush_log(dt('Configuration successfully exported to !target.', array('!target' => $destination_dir)), LogLevel::SUCCESS);
  return TRUE;
}

/**
 * Writes a YAML configuration file to the specified directory.
 *
 * @param $key
 * @param $source_storage
 * @param $destination_storage
 */
function _config_partial_export_write_config($key, $source_storage, $destination_storage) {
  $destination_storage->write($key, $source_storage->read($key));
}
