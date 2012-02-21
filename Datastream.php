<?php

abstract class AbstractDatastream extends MagicProperty {
  
  /* functions */
  abstract public function purge();
  abstract public function delete();
  abstract public function setContentFromFile($file);
  abstract public function setContentFromUrl($url);
  abstract public function setContentFromString($string);
  
  /* magic properties */
  abstract protected function idMagicProperty($function, $value);
  abstract protected function contentMagicProperty($function, $value);
  abstract protected function urlMagicProperty($function, $value);
  abstract protected function labelMagicProperty($function, $value);
  abstract protected function versionableMagicProperty($function, $value);
  abstract protected function stateMagicProperty($fuction, $value);
  abstract protected function mimetypeMagicProperty($function, $value);
  abstract protected function formatUriMagicProperty($function, $value);
  abstract protected function infoTypeMagicProperty($function, $value);
  abstract protected function locationMagicProperty($function, $value);
  abstract protected function locationTypeMagicProperty($function, $value);
  abstract protected function checksumMagicProperty($function, $value);
  abstract protected function checksumTypeMagicProperty($function, $value);
}

class TestDatastream extends AbstractDatastream {
  private $properties = array();
  private $content = array();
  private $valid = TRUE;
  
  private function checkObjectValidity() {
    if(!$this->valid) {
      throw new Exception('Object no longer valid.');
    }
  }
  
  public function __construct() {
    $this->properties['id'] = NULL;
    $this->properties['state'] = 'A';
  }
  
  public function purge() {
    $this->checkObjectValidity();
    $this->valid = FALSE;
  }
  
  public function delete() {
    $this->checkObjectValidity();
    $this->state = 'deleted';
  }
  
  public function setContentFromFile($file) {
    $this->checkObjectValidity();
    $this->content = file_get_contents($file);
  }
  
  public function setContentFromUrl($url) {
    $this->checkObjectValidity();
    $ch = curl_init();
    $timeout = 5;
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
    $this->content = curl_exec($ch);
    curl_close($ch);
  }
  
  public function setContentFromString($string) {
    $this->checkObjectValidity();
    $this->content = $string;
  }
  
  protected function idMagicProperty($function, $value) {
    $this->checkObjectValidity();
    switch ($function) {
      case 'get':
        return $this->properties['id'];
        break;
      case 'isset':
        return isset($this->properties['id']);
        break;
      case 'unset':
      case 'set':
      default:
        throw new Exception();
    }
  }
  
  protected function contentMagicProperty($function, $value) {
    $this->checkObjectValidity();
    switch($function) {
      case 'get':
        return $this->content;
        break;
      case 'set':
        $this->setContentFromString($value);
        break;
      case 'unset':
        $this->content = NULL;
        break;
      case 'isset':
        return isset($this->content);
        break;
    }
  }
  
  protected function generalMagic($function, $name, $value) {
    $this->checkObjectValidity();
    
    switch($function) {
      case 'get':
        return isset($this->properties[$name]) ? $this->properties[$name] : NULL;
        break;
      case 'set':
        $this->properties[$name] = $value;
        break;
      case 'unset':
        unset($this->properties[$name]);
        break;
      case 'isset':
        return isset($this->properties[$name]);
        break;
    }
  }
  
  protected function urlMagicProperty($function, $value) {
    $this->generalMagic($function, 'url', $value);
  }
  
  protected function labelMagicProperty($function, $value) {
    $this->generalMagic($function, 'label', $value);
  }
  
  protected function versionableMagicProperty($function, $value) {
    $this->generalMagic($function, 'versionable', $value);
  }
  
  protected function stateMagicProperty($fuction, $value) {
    $this->generalMagic($function, 'state', $value);
  }
  
  protected function mimetypeMagicProperty($function, $value) {
    $this->generalMagic($function, 'mimetype', $value);
  }
  
  protected function formatUriMagicProperty($function, $value) {
    $this->generalMagic($function, 'formatUri', $value);
  }
  
  protected function infoTypeMagicProperty($function, $value) {
    $this->generalMagic($function, 'infoType', $value);
  }
  
  protected function locationMagicProperty($function, $value) {
    $this->generalMagic($function, 'location', $value);
  }
  
  protected function locationTypeMagicProperty($function, $value) {
    $this->generalMagic($function, 'locationType', $value);
  }
  
  protected function checksumMagicProperty($function, $value) {
    $this->generalMagic($function, 'checksum', $value);
  }
  
  protected function checksumTypeMagicProperty($function, $value) {
    $this->generalMagic($function, 'checksumType', $value);
  }
    
}