<?php

namespace Islandora\Tuque\Datastream;

use Islandora\Tuque\Object\AbstractFedoraObject;
use Islandora\Tuque\Relationships\FedoraRelsInt;
use Islandora\Tuque\Repository\FedoraRepository;

/**
 * Abstract base class implementing a datastream in Fedora.
 */
abstract class AbstractFedoraDatastream extends AbstractDatastream
{

    /**
     * The repository this object belongs to.
     * @var FedoraRepository
     */
    public $repository;

    /**
     * The fedora object this datastream belongs to.
     * @var AbstractFedoraObject
     */
    public $parent;

    /**
     * An object for manipulating the fedora relationships related to this DS.
     * @var \Islandora\Tuque\Relationships\FedoraRelsInt
     */
    public $relationships;

    /**
     * The read only ID of the datastream.
     *
     * @var string
     */
    protected $datastreamId = null;

    /**
     * The array defining what is in the datastream.
     *
     * @var array
     * @see FedoraApiM::getDatastream
     */
    protected $datastreamInfo = null;

    protected $fedoraRelsIntClass = FedoraRelsInt::class;
    protected $fedoraDatastreamVersionClass = FedoraDatastreamVersion::class;

    /**
     * The constructor for the datastream.
     *
     * @param string $id
     *   The identifier of the datastream.
     * @param AbstractFedoraObject $object
     * @param FedoraRepository $repository
     */
    public function __construct(
        $id,
        AbstractFedoraObject $object,
        FedoraRepository $repository
    ) {
        parent::__construct();
        $this->datastreamId = $id;
        $this->parent = $object;
        $this->repository = $repository;
        $this->relationships = new $this->fedoraRelsIntClass($this);
    }

    /**
     * @see AbstractDatastream::id
     */
    protected function idMagicProperty($function, $value)
    {
        switch ($function) {
            case 'get':
                return $this->datastreamId;
                break;

            case 'isset':
                return true;
                break;

            case 'set':
            case 'unset':
                trigger_error(
                    "Cannot $function the readonly datastream->id property.",
                    E_USER_WARNING
                );
                break;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function delete()
    {
        $this->state = 'd';
    }

    /**
     * This is a replacement for isset when things can't be unset. So we define
     * a default value, then return TRUE or FALSE based on if it is set.
     *
     * @param mixed $actual
     *   The value we are testing.
     * @param mixed $unsetVal
     *   The value it would be if it was unset.
     *
     * @return boolean
     *   TRUE or FALSE
     */
    protected function isDatastreamPropertySet($actual, $unsetVal)
    {
        if ($actual === $unsetVal) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Validates a mimetype using a regular expression.
     *
     * @param string $mime
     *   A string representing a mimetype
     *
     * @return boolean
     *   TRUE if the string looks like a mimetype.
     *
     * @todo test if this covers all cases.
     */
    protected function validateMimetype($mime)
    {
        if (preg_match('#^[-\w]+/[-\w\.+]+$#', $mime)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Validates and normalizes the datastream state.
     *
     * @param string $state
     *   The input state
     *
     * @return string
     *   Returns FALSE if validation fails, otherwise it returns the normalized
     *   datastream state.
     */
    protected function validateState($state)
    {
        switch (strtolower($state)) {
            case 'd':
            case 'deleted':
                return 'D';
                break;

            case 'a':
            case 'active':
                return 'A';
                break;

            case 'i':
            case 'inactive':
                return 'I';
                break;

            default:
                return false;
                break;
        }
    }

    /**
     * Validates the versionable setting of a datastream.
     *
     * @param mixed $versionable
     *   The input versionable argument.
     *
     * @return boolean
     *   Returns TRUE if the argument is a boolean, FALSE otherwise.
     */
    protected function validateVersionable($versionable)
    {
        return is_bool($versionable);
    }

    /**
     * Validates and normalizes the checksumType arguement.
     *
     * @param string $type
     *   The input string
     *
     * @return mixed
     *   FALSE if validation fails. The checksumType string otherwise.
     */
    protected function validateChecksumType($type)
    {
        switch ($type) {
            case 'DEFAULT':
            case 'DISABLED':
            case 'MD5':
            case 'SHA-1':
            case 'SHA-256':
            case 'SHA-384':
            case 'SHA-512':
                return $type;
                break;

            default:
                return false;
                break;
        }
    }

    /**
     * @see AbstractDatastream::controlGroup
     */
    protected function controlGroupMagicProperty($function, $value)
    {
        switch ($function) {
            case 'get':
                return $this->datastreamInfo['dsControlGroup'];
                break;

            case 'isset':
                return true;
                break;

            case 'set':
            case 'unset':
                trigger_error(
                    "Cannot $function the readonly datastream->controlGroup property.",
                    E_USER_WARNING
                );
                break;
        }

        return null;
    }
}
