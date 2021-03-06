<?php

namespace Drupal\config_split\Config;

use Drupal\Core\Config\StorageInterface;

/**
 * Class StorageWrapper.
 *
 * This class wraps another storage.
 * It filters the arguments before passing them on to the storage for write
 * operations and filters the result of read operations before returning them.
 *
 * @package Drupal\config_split\Config
 */
class StorageWrapper implements StorageInterface {

  /**
   * The storage container that we are wrapping.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $storage;

  /**
   * The storage filters.
   *
   * @var \Drupal\config_split\Config\StorageFilterInterface[]
   */
  protected $filters;

  /**
   * Create a StorageWrapper with some storage and a filter.
   */
  public function __construct($storage, $filterOrFilters) {
    $this->storage = $storage;
    $this->filters = is_array($filterOrFilters) ? $filterOrFilters : [$filterOrFilters];
  }

  /**
   * {@inheritdoc}
   */
  public function exists($name) {
    $exists = $this->storage->exists($name);
    foreach ($this->filters as $filter) {
      $exists = $filter->filterExists($name, $exists);
    }

    return $exists;
  }

  /**
   * {@inheritdoc}
   */
  public function read($name) {
    $data = $this->storage->read($name);
    foreach ($this->filters as $filter) {
      $data = $filter->filterRead($name, $data);
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function readMultiple(array $names) {
    $data = $this->storage->readMultiple($names);
    foreach ($this->filters as $filter) {
      $data = $filter->filterReadMultiple($names, $data);
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function write($name, array $data) {
    foreach ($this->filters as $filter) {
      if ($data) {
        $data = $filter->filterWrite($name, $data, $this->storage);
      }
    }

    if ($data) {
      return $this->storage->write($name, $data);
    }

    if ($this->storage->exists($name)) {
      foreach ($this->filters as $filter) {
        if ($filter->filterWriteEmptyIsDelete($name)) {
          return $this->storage->delete($name);
        }
      }
    }

    // The data was not written, but it is not an error.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function delete($name) {
    $success = TRUE;
    foreach ($this->filters as $filter) {
      $success = $filter->filterDelete($name, $success);
    }

    if ($success) {
      $success = $this->storage->delete($name);
    }

    return $success;
  }

  /**
   * {@inheritdoc}
   */
  public function rename($name, $new_name) {
    $success = TRUE;
    foreach ($this->filters as $filter) {
      $success = $filter->filterRename($name, $new_name, $success);
    }

    if ($success) {
      $success = $this->storage->rename($name, $new_name);
    }

    return $success;
  }

  /**
   * {@inheritdoc}
   */
  public function encode($data) {
    return $this->storage->encode($data);
  }

  /**
   * {@inheritdoc}
   */
  public function decode($raw) {
    return $this->storage->decode($raw);
  }

  /**
   * {@inheritdoc}
   */
  public function listAll($prefix = '') {
    $data = $this->storage->listAll($prefix);
    foreach ($this->filters as $filter) {
      $data = $filter->filterListAll($prefix, $data);
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll($prefix = '') {
    $delete = TRUE;
    foreach ($this->filters as $filter) {
      $delete = $filter->filterDeleteAll($prefix, $delete);
    }

    if ($delete) {
      $delete = $this->storage->deleteAll($prefix);
    }

    return $delete;
  }

  /**
   * {@inheritdoc}
   */
  public function createCollection($collection) {
    $filters = [];
    foreach ($this->filters as $filter) {
      $filter = $filter->filterCreateCollection($collection);
      if ($filter) {
        $filters[] = $filter;
      }
    }

    return new static($this->storage->createCollection($collection), $filters);
  }

  /**
   * {@inheritdoc}
   */
  public function getAllCollectionNames() {
    $collections = $this->storage->getAllCollectionNames();
    foreach ($this->filters as $filter) {
      $collections = $filter->filterGetAllCollectionNames($collections);
    }

    return $collections;
  }

  /**
   * {@inheritdoc}
   */
  public function getCollectionName() {
    $collection = $this->storage->getCollectionName();
    foreach ($this->filters as $filter) {
      $collection = $filter->filterGetCollectionName($collection);
    }

    return $collection;
  }

}
