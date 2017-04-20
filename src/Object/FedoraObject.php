<?php

namespace Islandora\Tuque\Object;

use ArrayIterator;
use Islandora\Tuque\Date\FedoraDate;
use Islandora\Tuque\Repository\FedoraRepository;

/**
 * Represents a Fedora Object. Should be created by the factory method in the
 * repository. Respects object locking. Will throw an exception if an object
 * is modified under us.
 */
class FedoraObject extends AbstractFedoraObject
{

    /**
     * Instantiated list of datastream objects.
     * @var array
     */
    protected $datastreams = null;
    /**
     * If this is true we won't respect object locking, we will clobber anything
     * that has been changed.
     * @var boolean
     */
    public $forceUpdate = false;

    /**
     * The class constructor. Should be instantiated by the repository.
     *
     * @param string $id
     * @param FedoraRepository $repository
     */
    public function __construct($id, FedoraRepository $repository)
    {
        parent::__construct($id, $repository);
        $this->refresh();
    }

    /**
     * This function clears the object cache, so everything will be
     * requested directly from fedora again.
     */
    public function refresh()
    {
        $this->objectProfile = $this->repository->api->a->getObjectProfile($this->id);
        $this->objectProfile['objCreateDate'] = new FedoraDate($this->objectProfile['objCreateDate']);
        $this->objectProfile['objLastModDate'] = new FedoraDate($this->objectProfile['objLastModDate']);
        $this->objectProfile['objLogMessage'] = '';
    }

    /**
     * this will populate the datastream list the first time it is needed.
     */
    protected function populateDatastreams()
    {
        if (!isset($this->datastreams)) {
            $datastreams = $this->repository->api->a->listDatastreams($this->id);
            $this->datastreams = [];
            foreach ($datastreams as $key => $value) {
                $this->datastreams[$key] = new $this->fedoraDatastreamClass(
                    $key,
                    $this,
                    $this->repository,
                    [
                        "dsLabel" => $value['label'],
                        "dsMIME" => $value['mimetype']
                    ]
                );
            }
        }
    }

    /**
     * This is a wrapper on APIM modify object to make sure we respect locking.
     */
    protected function modifyObject($params)
    {
        if (!$this->forceUpdate) {
            $params['lastModifiedDate'] = (string) $this->lastModifiedDate;
        }
        $moddate = $this->repository->api->m->modifyObject($this->id, $params);
        $this->objectProfile['objLastModDate'] = new FedoraDate($moddate);
    }

    /**
     * {@inheritdoc}
     */
    public function purgeDatastream($id)
    {
        $this->populateDatastreams();

        if (!array_key_exists($id, $this->datastreams)) {
            return false;
        }

        $this->repository->api->m->purgeDatastream($this->id, $id);
        unset($this->datastreams[$id]);
        $this->refresh();
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getDatastream($id)
    {
        $this->populateDatastreams();

        if (!array_key_exists($id, $this->datastreams)) {
            return false;
        }

        return $this->datastreams[$id];
    }

    /**
     * @see AbstractObject::state
     */
    protected function stateMagicPropertySet($value)
    {
        if ($this->objectProfile['objState'] != $value) {
            parent::stateMagicProperty('set', $value);
            $this->modifyObject(['state' => $this->objectProfile['objState']]);
        }
    }

    /**
     * @see AbstractObject::label
     */
    protected function labelMagicPropertySet($value)
    {
        if ($this->objectProfile['objLabel'] != $value) {
            $this->modifyObject(['label' => mb_substr($value, 0, 255)]);
            parent::labelMagicProperty('set', $value);
        }
    }

    /**
     * @see AbstractObject::owner
     */
    protected function ownerMagicPropertySet($value)
    {
        if ($this->objectProfile['objOwnerId'] != $value) {
            $this->modifyObject(['ownerId' => $value]);
            parent::ownerMagicProperty('set', $value);
        }
    }

    /**
     * @see AbstractObject::createdDate
     */
    protected function createdDateMagicProperty($function, $value)
    {
        switch ($function) {
            case 'get':
                return $this->objectProfile['objCreateDate'];
                break;

            case 'isset':
                return true;
                break;

            case 'set':
            case 'unset':
                trigger_error(
                    "Cannot $function the readonly object->createdDate property.",
                    E_USER_WARNING
                );
                break;
        }
    }

    /**
     * @see AbstractObject::lastModifiedDate
     */
    protected function lastModifiedDateMagicProperty($function, $value)
    {
        switch ($function) {
            case 'get':
                return $this->objectProfile['objLastModDate'];
                break;

            case 'isset':
                return true;
                break;

            case 'set':
            case 'unset':
                trigger_error(
                    "Cannot $function the readonly object->lastModifiedDate property.",
                    E_USER_WARNING
                );
                break;
        }
    }

    /**
     * @see AbstractObject::logMessage
     */
    protected function logMessageMagicPropertySet($value)
    {
        if ($this->objectProfile['objLogMessage'] != $value) {
            $this->modifyObject(['logMessage' => $value]);
            parent::logMessageMagicProperty('set', $value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function constructDatastream($id, $control_group = 'M')
    {
        return parent::constructDatastream($id, $control_group);
    }

    /**
     * {@inheritdoc}
     */
    public function ingestDatastream(&$ds)
    {
        $this->populateDatastreams();
        if (!isset($this->datastreams[$ds->id])) {
            $params = [
                'controlGroup' => $ds->controlGroup,
                'dsLabel' => $ds->label,
                'versionable' => $ds->versionable,
                'dsState' => $ds->state,
                'formatURI' => $ds->format,
                'checksumType' => $ds->checksumType,
                'mimeType' => $ds->mimetype,
                // Assume NewFedoraObjects will have a log message set.
                'logMessage' => ($ds instanceof NewFedoraObject) ?
                    $ds->logMessage:
                    "Copied datastream from {$ds->parent->id}.",
            ];
            $temp = tempnam(sys_get_temp_dir(), 'tuque');
            if ($ds->controlGroup == 'E' || $ds->controlGroup == 'R' || $ds->getContent($temp) !== true) {
                $type = 'url';
                $content = $ds->content;
            } else {
                $type = 'file';
                $content = $temp;
            }
            $dsinfo = $this->repository->api->m->addDatastream($this->id, $ds->id, $type, $content, $params);
            unlink($temp);
            $ds = new $this->fedoraDatastreamClass($ds->id, $this, $this->repository, $dsinfo);
            $this->datastreams[$ds->id] = $ds;
            $this->objectProfile['objLastModDate'] = $ds->createdDate;
            return $ds;
        } else {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        $this->populateDatastreams();
        return count($this->datastreams);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        $this->populateDatastreams();
        return isset($this->datastreams[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        $this->populateDatastreams();
        return $this->getDatastream($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        trigger_error("Datastreams must be added using the FedoraObject->ingestDatastream function.", E_USER_WARNING);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        $this->populateDatastreams();
        if (isset($this->datastreams[$offset])) {
            $this->purgeDatastream($offset);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        $this->populateDatastreams();
        return new ArrayIterator($this->datastreams);
    }

    /**
     * Returns IDs of collections of which object is a member
     *
     * @return array
     */
    public function getParents()
    {
        $collections = array_merge(
            $this->relationships->get(FEDORA_RELS_EXT_URI, 'isMemberOfCollection'),
            $this->relationships->get(FEDORA_RELS_EXT_URI, 'isMemberOf')
        );
        $collection_ids = [];
        foreach ($collections as $collection) {
            $collection_ids[] = $collection['object']['value'];
        }
        return $collection_ids;
    }
}
