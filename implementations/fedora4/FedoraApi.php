<?php
/**
 * @file
 * The RAW API wrappers for the Fedora interface.
 *
 * This file currently contains fairly raw wrappers around the Fedora REST
 * interface. These could also be reinmplemented to use for example the Fedora
 * SOAP interface. If there are version specific modifications to be made for
 * Fedora, this is the place to make them.
 */

require_once 'RepositoryException.php';
require_once 'implementations/fedora3/FedoraApi.php';
require_once 'implementations/fedora3/RepositoryConnection.php';

/**
 * This is a simple class that brings FedoraApiM and FedoraApiA together.
 */
class Fedora4Api extends FedoraApi {

  /**
   * Fedora APIA Class
   * @var FedoraApiA
   */
  public $a;

  /**
   * Fedora APIM Class
   * @var FedoraApiM
   */
  public $m;

  /**
   *
   */
  public $connection;

  /**
   * Constructor for the FedoraApi object.
   *
   * @param RepositoryConnection $connection
   *   (Optional) If one isn't provided a default one will be used.
   * @param FedoraApiSerializer $serializer
   *   (Optional) If one isn't provided a default will be used.
   */
  public function  __construct(RepositoryConnection $connection = NULL, FedoraApiSerializer $serializer = NULL) {
    if (!$connection) {
      $connection = new RepositoryConnection();
    }

    if (!$serializer) {
      $serializer = new FedoraApiSerializer();
    }

    $this->a = new Fedora4ApiA($connection, $serializer);
    $this->m = new Fedora4ApiM($connection, $serializer);

    $this->connection = $connection;
  }
}

/**
 * This class implements the Fedora API-A interface. This is a light wrapper
 * around the Fedora interface. Very little attempt is put into putting things
 * into native PHP datastructures.
 *
 * See this page for more information:
 * https://wiki.duraspace.org/display/FEDORA35/REST+API
 */
class Fedora4ApiA extends FedoraApiA {
  public function describeRepository() {
    // This is weird and undocumented, but its what the web client does.
    $request = "/fcr:describe";
    $seperator = '?';
    $this->connection->addParam($request, $seperator, 'xml', 'true');
    $options['headers'] = array('Accept: application/xml');
    $response = $this->connection->getRequest($request, $options);
    $response = $this->serializer->describeRepository($response);
    return $response;
  }

}

/**
 * This class implements the Fedora API-M interface. This is a light wrapper
 * around the Fedora interface. Very little attempt is put into putting things
 * into native PHP datastructures.
 *
 * See this page for more information:
 * https://wiki.duraspace.org/display/FEDORA35/REST+API
 */
class Fedora4ApiM  extends FedoraApiM {
}
