<?php

/**
 * @file
 * This file defines all the classes used to manipulate datastreams in the
 * repository.
 */
require_once '../../AbstractDatastream.php';
require_once 'implementations/fedora3/FedoraDate.php';

/**
 * Abstract base class implementing a datastream in Fedora.
 */
abstract class AbstractFedoraDatastream extends AbstractDatastream {

  /**
   * @see AbstractDatastream::controlGroup
   */
  protected function controlGroupMagicProperty($function, $value) {
    switch ($function) {
      case 'get':
        //pp changed for fcrepo4
        if (isset($this->datastreamInfo['dsControlGroup'])) {
          return $this->datastreamInfo['dsControlGroup'];
        }
        else {
          return 'no control group set';
        }
        break;

      case 'isset':
        return TRUE;
        break;

      case 'set':
      case 'unset':
        trigger_error("Cannot $function the readonly datastream->controlGroup property.", E_USER_WARNING);
        break;
    }
  }
}

/**
 * This abstract class defines some shared functionality between all classes
 * that implement exising fedora datastreams.
 */
abstract class AbstractExistingFedoraDatastream extends AbstractFedoraDatastream {

  /**
   * Wrapper around the APIM getDatastream function.
   *
   * @return array
   *   Array containing datastream info.
   */
  protected function getDatastream() {
    return $this->repository->api->m->getDatastream($this->parent->id, $this->id);
  }
}

/**
 * This class implements a fedora datastream.
 *
 * It also lets old versions of datastreams be accessed using array notation.
 * For example to see how many versions of a datastream there are:
 * @code
 *   count($datastream)
 * @endcode
 *
 * Old datastreams are indexed newest to oldest. The current version is always
 * index 0, and older versions are indexed from that. Old versions can be
 * discarded using the unset command.
 *
 * These functions respect datastream locking. If a datastream changes under
 * your feet then an exception will be raised.
 */
class FedoraDatastream extends AbstractExistingFedoraDatastream implements Countable, ArrayAccess, IteratorAggregate {

  /**
   * This populates datastream history if it needs to be populated.
   */
  protected function populateDatastreamHistory() {
    //pp changed this for fcrepo4
    if ($this->datastreamHistory === NULL) {
      $repositoryVersion = $this->repository->api->a->getRepositoryVersion();
      if ($repositoryVersion >= 4.0) {
        $this->datastreamHistory = array($this->getDatastream());
      }
      else {
        $this->datastreamHistory = $this->getDatastreamHistory();
      }
    }
  }

  /**
   * This function uses datastream history to populate datastream info.
   */
  protected function populateDatastreamInfo() {
    //pp changed this for fcrepo4 which currently does not support the datastream history call
    $repositoryVersion = $this->repository->api->a->getRepositoryVersion();
    if ($repositoryVersion >= 4.0) {
      $this->datastreamHistory = array($this->getDatastream());
    }
    else {
      $this->datastreamHistory = $this->getDatastreamHistory();
    }

    if (isset($this->datastreamHistory[0])) {
      $this->datastreamInfo = $this->datastreamHistory[0];
    }
    else {
      return array();
    }
  }

  /**
   * @see AbstractDatastream::createdDate
   */
  protected function createdDateMagicProperty($function, $value) {
    switch ($function) {
      case 'get':
        //pp changed for fcrepo4
        if (!isset($this->datastreamInfo['dsCreateDate'])) {
          $this->populateDatastreamInfo();
          if (isset($this->datastreamInfo['dsCreateDate'])) {
            return new FedoraDate($this->datastreamInfo['dsCreateDate']);
          }
          else {
            return NULL;
          }
        }
        return NULL;
        break;

      case 'isset':
        return TRUE;
        break;

      case 'set':
      case 'unset':
        trigger_error("Cannot $function the readonly datastream->createdDate property.", E_USER_WARNING);
        break;
    }
  }
}

