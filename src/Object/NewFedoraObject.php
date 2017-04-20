<?php

namespace Islandora\Tuque\Object;

use ArrayIterator;
use Islandora\Tuque\Datastream\AbstractFedoraDatastream;
use Islandora\Tuque\Repository\FedoraRepository;

/**
 * This represents a new fedora object that hasn't been ingested yet. It lets
 * us pass around the object and add datastreams before we ingest it. This
 * shouldn't be constructed on its own, it should only be created from a
 * factory class.
 */
class NewFedoraObject extends AbstractFedoraObject
{

    /**
     * An array of cached datastream objects.
     * @var array
     */
    protected $datastreams = [];

    public function __construct($id, FedoraRepository $repository)
    {
        parent::__construct($id, $repository);
        $this->objectProfile = [];
        $this->objectProfile['objState'] = 'A';
        $this->objectProfile['objOwnerId'] = $this->repository->api->connection->username;
        $this->objectProfile['objLabel'] = '';
        $this->objectProfile['objLogMessage'] = '';
    }

    /**
     * We override this as the object may need to manipulate its ID before ingestion.
     *
     * @see AbstractObject::id
     */
    protected function idMagicProperty($function, $value)
    {
        switch ($function) {
            case 'get':
                return isset($this->objectId) ? $this->objectId : null;
            case 'isset':
                return isset($this->objectId);
            case 'set':
                $this->objectId = $value;
                if (isset($this['RELS-EXT'])) {
                    $this->relationships->changeObjectID($value);
                }
                if (isset($this['RELS-INT'])) {
                    $this['RELS-INT']->relationships->changeObjectID($value);
                }
                break;
            case 'unset':
                unset($this->objectId);
                break;
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function constructDatastream($id, $control_group = 'M')
    {
        return parent::constructDatastream($id, $control_group);
    }


    /**
     * Create a NewFedoraDatastream copy, and wrap it instead of what we had.
     *
     * This is necessary to avoid the possibility of changing a datastream for
     * another object, when copying datastreams between objects.
     *
     * @param AbstractFedoraDatastream $datastream
     */
    private function createNewDatastreamCopy(AbstractFedoraDatastream &$datastream)
    {
        $old_datastream = $datastream;

        $datastream = $this->constructDatastream($old_datastream->id, $old_datastream->controlGroup);

        // Copying the datastream particulars...
        $properties = ['checksumType', 'checksum', 'format', 'mimetype', 'versionable', 'label', 'state'];
        if (in_array($old_datastream->controlGroup, ['R', 'E'])) {
            $properties[] = 'url';
        } else {
            // Get the content into a file, and add the file.
            $temp_file = tempnam(sys_get_temp_dir(), 'tuque');
            $old_datastream->getContent($temp_file);
            $datastream->setContentFromFile($temp_file);
            unlink($temp_file);
        }
        foreach ($properties as $property) {
            $datastream->$property = $old_datastream->$property;
        }

        $datastream->logMessage = 'Datastream contents copied.';

        unset($this[$datastream->id]);
    }

    /**
     * This function doesn't actually ingest the datastream, it just adds it to
     * the queue to be ingested whenever this object is ingested.
     *
     * @see AbstractObject::ingestDatastream()
     *
     * @param \Islandora\Tuque\Datastream\NewFedoraDatastream $ds
     *   the datastream to be ingested
     *
     * @return bool
     *   FALSE if the datastream already exists; TRUE otherwise.
     */
    public function ingestDatastream(&$ds)
    {
        if (!isset($this->datastreams[$ds->id])) {
            // The datastream does not already belong to this object, aka was created
            // by this object.
            if ($ds->parent !== $this) {
                // Create a NewFedoraDatastream copy.
                $this->createNewDatastreamCopy($ds);
            }
            $this->datastreams[$ds->id] = $ds;
            return true;
        } else {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function purgeDatastream($id)
    {
        if (isset($this->datastreams[$id])) {
            unset($this->datastreams[$id]);
            return true;
        } else {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDatastream($id)
    {
        if (isset($this->datastreams[$id])) {
            return $this->datastreams[$id];
        } else {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->datastreams);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->datastreams[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        if ($this->offsetExists($offset)) {
            return $this->datastreams[$offset];
        } else {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        trigger_error(
            "Datastreams must be added though the NewFedoraObect->ingestDatastream() function.",
            E_USER_WARNING
        );
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        $this->purgeDatastream($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new ArrayIterator($this->datastreams);
    }
}
