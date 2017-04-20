<?php

namespace Islandora\Tuque\Object;

use Exception;
use Islandora\Tuque\Datastream\FedoraDatastream;
use Islandora\Tuque\Datastream\NewFedoraDatastream;
use Islandora\Tuque\Relationships\FedoraRelsExt;
use Islandora\Tuque\Repository\FedoraRepository;

/**
 * This is the base class for a Fedora Object.
 */
abstract class AbstractFedoraObject extends AbstractObject
{
    /**
     * This is an object for manipulating relationships related to this object.
     * @var \Islandora\Tuque\Relationships\FedoraRelsExt
     */
    public $relationships;

    /**
     * The repository this object belongs to.
     * @var \Islandora\Tuque\Repository\FedoraRepository
     */
    public $repository;

    /**
     * The ID of this object.
     * @var string
     */
    protected $objectId;

    /**
     * The object profile from fedora for this object.
     * @var array
     * @see FedoraApiA::getObjectProfile
     */
    protected $objectProfile;

    /**
     * The name of the class that the Factory for FedoraDatastream should
     * produce. This allows us to override the factory in inhereted classes.
     *
     * @var string
     */
    protected $fedoraDatastreamClass = FedoraDatastream::class;

    /**
     * The name of the class that the Factory for NewFedoraDatastream should
     * produce. This allows us to override the factory in inhereted classes.
     *
     * @var string
     */
    protected $newFedoraDatastreamClass = NewFedoraDatastream::class;

    /**
     * The name of the class to use for RelsExt
     *
     * @var string
     */
    protected $fedoraRelsExtClass = FedoraRelsExt::class;

    public function __construct($id, FedoraRepository $repository)
    {
        parent::__construct();
        $this->repository = $repository;
        $this->objectId = $id;
        $this->relationships = new FedoraRelsExt($this);
    }

    /**
     * Implements Magic Method.  Returns PID of object when object is printed
     *
     * @return string
     */
    public function __toString()
    {
        return $this->id;
    }

    /**
     * @see AbstractObject::delete()
     */
    public function delete()
    {
        $this->state = 'D';
    }

    /**
     * @see AbstractObject::id
     */
    protected function idMagicProperty($function, $value)
    {
        switch ($function) {
            case 'get':
                return $this->objectId;
                break;

            case 'isset':
                return true;
                break;

            case 'set':
            case 'unset':
                trigger_error("Cannot $function the readonly object->id property.", E_USER_WARNING);
                throw new Exception();
                break;
        }
        return null;
    }

    /**
     * @see AbstractObject::state
     */
    protected function stateMagicProperty($function, $value)
    {
        switch ($function) {
            case 'get':
                return $this->objectProfile['objState'];
                break;

            case 'isset':
                return true;
                break;

            case 'set':
                switch (strtolower($value)) {
                    case 'd':
                    case 'deleted':
                        $this->objectProfile['objState'] = 'D';
                        break;

                    case 'a':
                    case 'active':
                        $this->objectProfile['objState'] = 'A';
                        break;

                    case 'i':
                    case 'inactive':
                        $this->objectProfile['objState'] = 'I';
                        break;

                    default:
                        trigger_error("$value is not a valid value for the object->state property.", E_USER_WARNING);
                        break;
                }
                break;

            case 'unset':
                trigger_error("Cannot unset the required object->state property.", E_USER_WARNING);
                break;
        }
        return null;
    }

    /**
     * @see AbstractObject::label
     */
    protected function labelMagicProperty($function, $value)
    {
        switch ($function) {
            case 'get':
                return $this->objectProfile['objLabel'];
                break;

            case 'isset':
                if ($this->objectProfile['objLabel'] === '') {
                    return false;
                } else {
                    return isset($this->objectProfile['objLabel']);
                }
                break;

            case 'set':
                $this->objectProfile['objLabel'] = function_exists('mb_substr')
                    ? mb_substr($value, 0, 255)
                    : substr($value, 0, 255);
                break;

            case 'unset':
                $this->objectProfile['objLabel'] = '';
                break;
        }
        return null;
    }

    /**
     * @see AbstractObject::owner
     */
    protected function ownerMagicProperty($function, $value)
    {
        switch ($function) {
            case 'get':
                return $this->objectProfile['objOwnerId'];
                break;

            case 'isset':
                if ($this->objectProfile['objOwnerId'] === '') {
                    return false;
                } else {
                    return isset($this->objectProfile['objOwnerId']);
                }
                break;

            case 'set':
                $this->objectProfile['objOwnerId'] = $value;
                break;

            case 'unset':
                $this->objectProfile['objOwnerId'] = '';
                break;
        }
        return null;
    }

    /**
     * @see AbstractObject::logMessage
     */
    protected function logMessageMagicProperty($function, $value)
    {
        switch ($function) {
            case 'get':
                return $this->objectProfile['objLogMessage'];
                break;

            case 'isset':
                if ($this->objectProfile['objLogMessage'] === '') {
                    return false;
                } else {
                    return isset($this->objectProfile['objLogMessage']);
                }
                break;

            case 'set':
                $this->objectProfile['objLogMessage'] = $value;
                break;

            case 'unset':
                $this->objectProfile['objLogMessage'] = '';
                break;
        }
        return null;
    }

    /**
     * @see AbstractObject::models
     */
    protected function modelsMagicProperty($function, $value)
    {
        switch ($function) {
            case 'get':
                $models = [];

                $rels_models = $this->relationships->get(FEDORA_MODEL_URI, 'hasModel');

                foreach ($rels_models as $model) {
                    $models[] = $model['object']['value'];
                }
                if (!in_array('fedora-system:FedoraObject-3.0', $models)) {
                    $models[] = 'fedora-system:FedoraObject-3.0';
                }
                return $models;
                break;

            case 'isset':
                $rels_models = $this->relationships->get(FEDORA_MODEL_URI, 'hasModel');
                return (count($rels_models) > 0);
                break;

            case 'set':
                if (!is_array($value)) {
                    $models = [$value];
                } else {
                    $models = $value;
                }

                if (!in_array('fedora-system:FedoraObject-3.0', $models)) {
                    $models[] = 'fedora-system:FedoraObject-3.0';
                }
                foreach ($models as $model) {
                    if (!in_array($model, $this->models)) {
                        $this->relationships->add(FEDORA_MODEL_URI, 'hasModel', $model);
                    }
                }
                foreach (array_diff($this->models, $models) as $model) {
                    $this->relationships->remove(FEDORA_MODEL_URI, 'hasModel', $model);
                }
                break;

            case 'unset':
                $this->relationships->remove(FEDORA_MODEL_URI, 'hasModel');
                break;
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function constructDatastream($id, $control_group = 'M')
    {
        return new $this->newFedoraDatastreamClass($id, $control_group, $this, $this->repository);
    }
}
