<?php

namespace Islandora\Tuque\Datastream;

use Islandora\Tuque\Date\FedoraDate;
use Islandora\Tuque\Object\FedoraObject;
use Islandora\Tuque\Repository\FedoraRepository;

/**
 * This class implements an old version of a fedora datastream. Its properties
 * are the same of a normal fedora datastream, except since its an older verion
 * everything is read only.
 */
class FedoraDatastreamVersion extends AbstractExistingFedoraDatastream
{

    /**
     * The parent datastream.
     * @var FedoraDatastream
     */
    public $parent;

    /**
     * The Constructor! Sounds like a superhero doesn't it. Constructor away!
     */
    public function __construct(
        $id,
        array $datastream_info,
        FedoraDatastream $datastream,
        FedoraObject $object,
        FedoraRepository $repository
    ) {
        parent::__construct($id, $object, $repository);
        $this->datastreamInfo = $datastream_info;
        $this->parent = $object;
    }

    /*
     * This function gives us a consistent error across this whole class.
     */
    protected function error()
    {
        trigger_error(
            "All properties of previous datastream versions are read only. Please modify parent datastream object.",
            E_USER_WARNING
        );
    }

    /*
     * Since this whole class is read only, this is a general implementation of
     * the MagicPropery function that is ready only.
     */
    protected function generalReadOnly($offset, $unset_val, $function, $value)
    {
        switch ($function) {
            case 'get':
                return $this->datastreamInfo[$offset];
                break;

            case 'isset':
                if ($unset_val === null) {
                    // Object cannot be unset.
                    return true;
                } else {
                    return $this->isDatastreamPropertySet(
                        $this->datastreamInfo[$offset],
                        $unset_val
                    );
                }
                break;

            case 'set':
            case 'unset':
                $this->error();
                break;
        }
        return null;
    }

    /**
     * @see AbstractDatastream::state
     */
    protected function stateMagicProperty($function, $value)
    {
        return $this->generalReadOnly('dsState', null, $function, $value);
    }

    /**
     * @see AbstractDatastream::label
     */
    protected function labelMagicProperty($function, $value)
    {
        return $this->generalReadOnly('dsLabel', '', $function, $value);
    }

    /**
     * @see AbstractDatastream::versionable
     */
    protected function versionableMagicProperty($function, $value)
    {
        if (!is_bool($this->datastreamInfo['dsVersionable'])) {
            $this->datastreamInfo['dsVersionable'] = $this->datastreamInfo['dsVersionable'] == 'true' ? true : false;
        }
        return $this->generalReadOnly('dsVersionable', null, $function, $value);
    }

    /**
     * @see AbstractDatastream::mimetype
     */
    protected function mimetypeMagicProperty($function, $value)
    {
        return $this->generalReadOnly('dsMIME', '', $function, $value);
    }

    /**
     * @see AbstractDatastream::format
     */
    protected function formatMagicProperty($function, $value)
    {
        return $this->generalReadOnly('dsFormatURI', '', $function, $value);
    }

    /**
     * @see AbstractDatastream::size
     */
    protected function sizeMagicProperty($function, $value)
    {
        return $this->generalReadOnly('dsSize', null, $function, $value);
    }

    /**
     * @see AbstractDatastream::checksum
     */
    protected function checksumMagicProperty($function, $value)
    {
        return $this->generalReadOnly('dsChecksum', 'none', $function, $value);
    }

    /**
     * @see AbstractDatastream::url
     */
    protected function urlMagicProperty($function, $value)
    {
        if (in_array($this->controlGroup, array('R', 'E'))) {
            return $this->generalReadOnly(
                'dsLocation',
                false,
                $function,
                $value
            );
        } else {
            trigger_error(
                "No 'url' property on datastreams in control group {$this->controlGroup}",
                E_USER_WARNING
            );
        }
        return null;
    }

    /**
     * @see AbstractDatastream::createDate
     */
    protected function createdDateMagicProperty($function, $value)
    {
        if (!$this->datastreamInfo['dsCreateDate'] instanceof FedoraDate) {
            $this->datastreamInfo['dsCreateDate'] = new FedoraDate($this->datastreamInfo['dsCreateDate']);
        }
        return $this->generalReadOnly('dsCreateDate', null, $function, $value);
    }

    /**
     * @see AbstractDatastream::content
     */
    protected function contentMagicProperty($function, $value)
    {
        switch ($function) {
            case 'get':
                return $this->getDatastreamContent((string)$this->createdDate);
                break;

            case 'isset':
                return $this->isDatastreamPropertySet($this->content, '');
                break;

            case 'set':
            case 'unset':
                $this->error();
                break;
        }
        return null;
    }

    /**
     * @see AbstractDatastream::logMessage
     */
    protected function logMessageMagicProperty($function, $value)
    {
        return $this->generalReadOnly('dsLogMessage', '', $function, $value);
    }

    /**
     * @see AbstractDatastream::setContentFromFile()
     */
    public function setContentFromFile($file)
    {
        $this->error();
    }

    /**
     * @see AbstractDatastream::setContentFromString()
     */
    public function setContentFromString($string)
    {
        $this->error();
    }

    /**
     * @see AbstractDatastream::setContentFromUrl()
     */
    public function setContentFromUrl($url)
    {
        $this->error();
    }

    /**
     * @see AbstractDatastream::getContent()
     */
    public function getContent($file)
    {
        return $this->getDatastreamContent((string)$this->createdDate, $file);
    }
}
