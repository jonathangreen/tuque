<?php

namespace Islandora\Tuque\Datastream;

use Countable;
use ArrayAccess;
use ArrayIterator;
use Islandora\Tuque\Object\FedoraObject;
use Islandora\Tuque\Relationships\FedoraRelsExt;
use Islandora\Tuque\Repository\FedoraRepository;
use IteratorAggregate;
use Islandora\Tuque\Date\FedoraDate;

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
class FedoraDatastream extends AbstractExistingFedoraDatastream implements Countable, ArrayAccess, IteratorAggregate
{

    /**
     * An array containing the datastream history.
     * @var array
     */
    protected $datastreamHistory = null;

    /**
     * If this is set to TRUE then datastream locking won't be respected. This is
     * dangerous as any changes could clobber someone elses changes.
     *
     * @var boolean
     */
    public $forceUpdate = false;

    /**
     * Domo arigato, Mr. Roboto. Constructor.
     *
     * @param string $id
     * @param FedoraObject $object
     * @param FedoraRepository $repository
     * @param array|null $datastream_info
     */
    public function __construct(
        $id,
        FedoraObject $object,
        FedoraRepository $repository,
        array $datastream_info = null
    ) {
        parent::__construct($id, $object, $repository);
        $this->datastreamInfo = $datastream_info;
    }

    /**
     * This function clears the datastreams caches, so everything will be
     * requested directly from fedora again.
     */
    public function refresh()
    {
        $this->datastreamInfo = null;
        $this->datastreamHistory = null;
    }

    /**
     * This populates datastream history if it needs to be populated.
     */
    protected function populateDatastreamHistory()
    {
        if ($this->datastreamHistory === null) {
            $this->datastreamHistory = $this->getDatastreamHistory();
        }
    }

    /**
     * This function uses datastream history to populate datastream info.
     */
    protected function populateDatastreamInfo()
    {
        $this->datastreamHistory = $this->getDatastreamHistory();
        $this->datastreamInfo = $this->datastreamHistory[0];
    }

    /**
     * This function modifies the datastream in fedora while adding the
     * parameters needed to respect datastream locking and making sure that we
     * keep the internal class cache of the datastream up to date.
     *
     * @param array $args
     * @return null
     */
    protected function modifyDatastream(array $args)
    {
        $versionable = $this->versionable;
        if (!$this->forceUpdate) {
            $args = array_merge(
                $args,
                ['lastModifiedDate' => (string)$this->createdDate]
            );
        }
        $this->datastreamInfo = parent::modifyDatastream($args);
        if ($this->datastreamHistory !== null) {
            if ($versionable) {
                array_unshift($this->datastreamHistory, $this->datastreamInfo);
            } else {
                $this->datastreamHistory[0] = $this->datastreamInfo;
            }
        }
        $this->parent->refresh();

        return null;
    }

    /**
     * @see AbstractDatastream::controlGroup
     */
    protected function controlGroupMagicProperty($function, $value)
    {
        if (!isset($this->datastreamInfo['dsControlGroup'])) {
            $this->populateDatastreamInfo();
        }
        return parent::controlGroupMagicProperty($function, $value);
    }

    /**
     * @see AbstractDatastream::location
     */
    protected function locationMagicProperty($function, $value)
    {
        switch ($function) {
            case 'get':
                if (!isset($this->datastreamInfo['dsLocation'])) {
                    $this->populateDatastreamInfo();
                }
                return $this->datastreamInfo['dsLocation'];
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
     * @see AbstractDatastream::state
     */
    protected function stateMagicProperty($function, $value)
    {
        switch ($function) {
            case 'get':
                if (!isset($this->datastreamInfo['dsState'])) {
                    $this->populateDatastreamInfo();
                }
                return $this->datastreamInfo['dsState'];
                break;

            case 'isset':
                return true;
                break;

            case 'set':
                $state = $this->validateState($value);
                if ($state !== false) {
                    $this->modifyDatastream(['dsState' => $state]);
                } else {
                    trigger_error(
                        "$value is not a valid value for the datastream->state property.",
                        E_USER_WARNING
                    );
                }
                break;

            case 'unset':
                trigger_error(
                    "Cannot unset the required datastream->state property.",
                    E_USER_WARNING
                );
                break;
        }
        return null;
    }

    /**
     * @see AbstractDatastream::label
     */
    protected function labelMagicProperty($function, $value)
    {
        switch ($function) {
            case 'get':
                if (!isset($this->datastreamInfo['dsLabel'])) {
                    $this->populateDatastreamInfo();
                }
                return $this->datastreamInfo['dsLabel'];
                break;

            case 'isset':
                if (!isset($this->datastreamInfo['dsLabel'])) {
                    $this->populateDatastreamInfo();
                }
                return $this->isDatastreamPropertySet(
                    $this->datastreamInfo['dsLabel'],
                    ''
                );
                break;

            case 'set':
                $this->modifyDatastream(['dsLabel' => mb_substr($value, 0, 255)]);
                break;

            case 'unset':
                $this->modifyDatastream(['dsLabel' => '']);
                break;
        }
        return null;
    }

    /**
     * @see AbstractDatastream::versionable
     */
    protected function versionableMagicProperty($function, $value)
    {
        switch ($function) {
            case 'get':
                if (!isset($this->datastreamInfo['dsVersionable'])) {
                    $this->populateDatastreamInfo();
                }
                // Convert to a boolean.
                $versionable = $this->datastreamInfo['dsVersionable'] == 'true' ? true : false;
                return $versionable;
                break;

            case 'isset':
                return true;
                break;

            case 'set':
                if ($this->validateVersionable($value)) {
                    $this->modifyDatastream(['versionable' => $value]);
                } else {
                    trigger_error(
                        "Datastream->versionable must be a boolean.",
                        E_USER_WARNING
                    );
                }
                break;

            case 'unset':
                trigger_error(
                    "Cannot unset the required datastream->versionable property.",
                    E_USER_WARNING
                );
                break;
        }
        return null;
    }

    /**
     * @see AbstractDatastream::mimetype
     */
    protected function mimetypeMagicProperty($function, $value)
    {
        switch ($function) {
            case 'get':
                if (!isset($this->datastreamInfo['dsMIME'])) {
                    $this->populateDatastreamInfo();
                }
                return $this->datastreamInfo['dsMIME'];
                break;

            case 'isset':
                return true;
                break;

            case 'set':
                if ($this->validateMimetype($value)) {
                    $this->modifyDatastream(['mimeType' => $value]);
                } else {
                    trigger_error("Invalid mimetype.", E_USER_WARNING);
                }
                break;

            case 'unset':
                trigger_error(
                    "Cannot unset the required datastream->mimetype property.",
                    E_USER_WARNING
                );
                break;
        }
        return null;
    }

    /**
     * @see AbstractDatastream::format
     */
    protected function formatMagicProperty($function, $value)
    {
        switch ($function) {
            case 'get':
                if (!isset($this->datastreamInfo['dsFormatURI'])) {
                    $this->populateDatastreamInfo();
                }
                return $this->datastreamInfo['dsFormatURI'];
                break;

            case 'isset':
                if (!isset($this->datastreamInfo['dsFormatURI'])) {
                    $this->populateDatastreamInfo();
                }
                return $this->isDatastreamPropertySet(
                    $this->datastreamInfo['dsFormatURI'],
                    ''
                );
                break;

            case 'set':
                $this->modifyDatastream(['formatURI' => $value]);
                break;

            case 'unset':
                $this->modifyDatastream(['formatURI' => '']);
                break;
        }
        return null;
    }

    /**
     * @see AbstractDatastream::size
     */
    protected function sizeMagicProperty($function, $value)
    {
        switch ($function) {
            case 'get':
                if (!isset($this->datastreamInfo['dsSize'])) {
                    $this->populateDatastreamInfo();
                }
                return $this->datastreamInfo['dsSize'];
                break;

            case 'isset':
                return true;
                break;

            case 'set':
            case 'unset':
                trigger_error(
                    "Cannot $function the readonly datastream->size property.",
                    E_USER_WARNING
                );
                break;
        }
        return null;
    }

    /**
     * @see AbstractDatastream::checksum
     * @todo maybe add functionality to set it to auto
     */
    protected function checksumMagicProperty($function, $value)
    {
        switch ($function) {
            case 'get':
                if (!isset($this->datastreamInfo['dsChecksum'])) {
                    $this->populateDatastreamInfo();
                }
                return $this->datastreamInfo['dsChecksum'];
                break;

            case 'isset':
                if (!isset($this->datastreamInfo['dsChecksum'])) {
                    $this->populateDatastreamInfo();
                }
                return $this->isDatastreamPropertySet(
                    $this->datastreamInfo['dsChecksum'],
                    'none'
                );
                break;

            case 'set':
            case 'unset':
                trigger_error(
                    "Cannot $function the readonly datastream->checksum property.",
                    E_USER_WARNING
                );
                break;
        }
        return null;
    }

    /**
     * @see AbstractDatastream::checksumType
     */
    protected function checksumTypeMagicProperty($function, $value)
    {
        switch ($function) {
            case 'get':
                if (!isset($this->datastreamInfo['dsChecksumType'])) {
                    $this->populateDatastreamInfo();
                }
                return $this->datastreamInfo['dsChecksumType'];
                break;

            case 'isset':
                if (!isset($this->datastreamInfo['dsChecksumType'])) {
                    $this->populateDatastreamInfo();
                }
                return $this->isDatastreamPropertySet(
                    $this->datastreamInfo['dsChecksumType'],
                    'DISABLED'
                );
                break;

            case 'set':
                $type = $this->validateChecksumType($value);
                if ($type) {
                    $this->modifyDatastream(['checksumType' => $type]);
                } else {
                    trigger_error(
                        "$value is not a valid value for the datastream->checksumType property.",
                        E_USER_WARNING
                    );
                }
                break;

            case 'unset':
                $this->modifyDatastream(['checksumType' => 'DISABLED']);
                break;
        }
        return null;
    }

    /**
     * @see AbstractDatastream::createdDate
     */
    protected function createdDateMagicProperty($function, $value)
    {
        switch ($function) {
            case 'get':
                if (!isset($this->datastreamInfo['dsCreateDate'])) {
                    $this->populateDatastreamInfo();
                }
                return new FedoraDate($this->datastreamInfo['dsCreateDate']);
                break;

            case 'isset':
                return true;
                break;

            case 'set':
            case 'unset':
                trigger_error(
                    "Cannot $function the readonly datastream->createdDate property.",
                    E_USER_WARNING
                );
                break;
        }
        return null;
    }

    /**
     * @see AbstractDatastream::content
     * @todo We should perhaps cache this? depending on size?
     */
    protected function contentMagicProperty($function, $value)
    {
        switch ($function) {
            case 'get':
                return $this->getDatastreamContent();
                break;

            case 'isset':
                return $this->isDatastreamPropertySet(
                    $this->getDatastreamContent(),
                    ''
                );
                break;

            case 'set':
                if ($this->controlGroup == 'M' || $this->controlGroup == 'X') {
                    $this->modifyDatastream(['dsString' => $value]);
                } else {
                    trigger_error(
                        "Cannot set content of a {$this->controlGroup} datastream, please use datastream->url.",
                        E_USER_WARNING
                    );
                }
                break;

            case 'unset':
                if ($this->controlGroup == 'M' || $this->controlGroup == 'X') {
                    $this->modifyDatastream(['dsString' => '']);
                } else {
                    trigger_error(
                        "Cannot unset content of a {$this->controlGroup} datastream, please use datastream->url.",
                        E_USER_WARNING
                    );
                }
                break;
        }
        return null;
    }

    /**
     * @see AbstractDatastream::url
     */
    protected function urlMagicProperty($function, $value)
    {
        switch ($function) {
            case 'get':
                if (!isset($this->datastreamInfo['dsLocation'])) {
                    $this->populateDatastreamInfo();
                }
                if ($this->controlGroup == 'E' || $this->controlGroup == 'R') {
                    return $this->datastreamInfo['dsLocation'];
                } else {
                    trigger_error(
                        "Datastream->url property is undefined for a {$this->controlGroup} datastream.",
                        E_USER_WARNING
                    );
                    return null;
                }
                break;

            case 'isset':
                if ($this->controlGroup == 'E' || $this->controlGroup == 'R') {
                    return true;
                } else {
                    return false;
                }
                break;

            case 'set':
                if ($this->controlGroup == 'E' || $this->controlGroup == 'R') {
                    $this->modifyDatastream(['dsLocation' => $value]);
                } else {
                    trigger_error(
                        "Cannot set url of a {$this->controlGroup} datastream, please use datastream->content.",
                        E_USER_WARNING
                    );
                }
                break;

            case 'unset':
                trigger_error(
                    "Cannot unset the required datastream->url property.",
                    E_USER_WARNING
                );
                break;
        }
        return null;
    }

    /**
     * @see AbstractDatastream::logMessage
     */
    protected function logMessageMagicProperty($function, $value)
    {
        switch ($function) {
            case 'get':
                if (!isset($this->datastreamInfo['dsLogMessage'])) {
                    $this->populateDatastreamInfo();
                }
                return $this->datastreamInfo['dsLogMessage'];
                break;

            case 'isset':
                if (!isset($this->datastreamInfo['dsLogMessage'])) {
                    $this->populateDatastreamInfo();
                }
                return $this->isDatastreamPropertySet(
                    $this->datastreamInfo['dsLogMessage'],
                    ''
                );
                break;

            case 'set':
                $this->modifyDatastream(['dsLogMessage' => $value]);
                break;

            case 'unset':
                $this->modifyDatastream(['dsLogMessage' => '']);
                break;
        }
        return null;
    }

    /**
     * @see AbstractDatastream::setContentFromFile
     */
    public function setContentFromFile($file)
    {
        if ($this->controlGroup == 'E' || $this->controlGroup == 'R') {
            trigger_error(
                "Function cannot be called on a {$this->controlGroup} datastream. Please use datastream->url.",
                E_USER_WARNING
            );
            return;
        }
        $this->modifyDatastream(['dsFile' => $file]);
    }

    /**
     * @see AbstractDatastream::setContentFromUrl
     *
     * @param string $url
     *   Https (SSL) URL's will cause this to fail.
     */
    public function setContentFromUrl($url)
    {
        if ($this->controlGroup == 'E' || $this->controlGroup == 'R') {
            trigger_error(
                "Function cannot be called on a {$this->controlGroup} datastream. Please use datastream->url.",
                E_USER_WARNING
            );
            return;
        }
        $this->modifyDatastream(['dsLocation' => $url]);
    }

    /**
     * @see AbstractDatastream::setContentFromString
     */
    public function setContentFromString($string)
    {
        if ($this->controlGroup == 'E' || $this->controlGroup == 'R') {
            trigger_error(
                "Function cannot be called on a {$this->controlGroup} datastream. Please use datastream->url.",
                E_USER_WARNING
            );
            return;
        }
        $this->modifyDatastream(['dsString' => $string]);
    }

    /**
     * @see Countable::count
     */
    public function count()
    {
        $this->populateDatastreamHistory();
        return count($this->datastreamHistory);
    }

    /**
     * @see ArrayAccess::offsetExists
     */
    public function offsetExists($offset)
    {
        $this->populateDatastreamHistory();
        return isset($this->datastreamHistory[$offset]);
    }

    /**
     * @see ArrayAccess::offsetGet
     */
    public function offsetGet($offset)
    {
        $this->populateDatastreamHistory();
        return new $this->fedoraDatastreamVersionClass(
            $this->id,
            $this->datastreamHistory[$offset],
            $this,
            $this->parent,
            $this->repository
        );
    }

    /**
     * @see ArrayAccess::offsetSet
     */
    public function offsetSet($offset, $value)
    {
        trigger_error(
            "Datastream versions are read only and cannot be set.",
            E_USER_WARNING
        );
    }

    /**
     * @see ArrayAccess::offsetUnset
     */
    public function offsetUnset($offset)
    {
        $this->populateDatastreamHistory();
        if ($this->count() == 1) {
            trigger_error(
                "Cannot unset the last version of a datastream." .
                "To delete the datastream use the object->purgeDatastream() function.",
                E_USER_WARNING
            );
            return;
        }
        $this->purgeDatastream($this->datastreamHistory[$offset]['dsCreateDate']);
        unset($this->datastreamHistory[$offset]);
        $this->datastreamHistory = array_values($this->datastreamHistory);
        $this->datastreamInfo = $this->datastreamHistory[0];
    }

    /**
     * IteratorAggregate::getIterator()
     */
    public function getIterator()
    {
        $this->populateDatastreamHistory();
        $history = [];
        foreach ($this->datastreamHistory as $key => $value) {
            $history[$key] = new $this->fedoraDatastreamVersionClass(
                $this->id,
                $value,
                $this,
                $this->parent,
                $this->repository
            );
        }
        return new ArrayIterator($history);
    }

    /**
     * @see AbstractDatastream::getContent()
     */
    public function getContent($file)
    {
        return $this->getDatastreamContent(null, $file);
    }
}
