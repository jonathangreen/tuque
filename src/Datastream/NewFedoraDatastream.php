<?php

namespace Islandora\Tuque\Datastream;

use Islandora\Tuque\Object\AbstractFedoraObject;
use Islandora\Tuque\Repository\FedoraRepository;

/**
 * This defines a new fedora datastream. This is the class used to contain the
 * inforamtion for a new fedora datastream before it is ingested.
 */
class NewFedoraDatastream extends AbstractFedoraDatastream
{

    /**
     * Used to determine if we should delete the contents of this datastream when
     * this class is destroyed.
     *
     * @var boolean
     */
    protected $copied = false;

    /**
     * The constructor for a new fedora datastream.
     *
     * @param string $id
     *   The unique identifier of the DS.
     * @param FedoraObject $object
     *   The FedoraObject that this DS belongs to.
     * @param FedoraRepository $repository
     *   The FedoraRepository that this DS belongs to.
     * @param string $control_group
     *   The control group this DS will belong to.
     *
     * @todo test for valid identifiers. it can't start with a number etc.
     */
    public function __construct(
        $id,
        $control_group,
        AbstractFedoraObject $object,
        FedoraRepository $repository
    ) {
        parent::__construct($id, $object, $repository);

        $group = $this->validateControlGroup($control_group);

        if ($group === false) {
            trigger_error(
                "Invalid control group \"$control_group\", using managed instead.",
                E_USER_WARNING
            );
            $group = 'M';
        }

        // Set defaults!
        $this->datastreamInfo['dsControlGroup'] = $group;
        $this->datastreamInfo['dsState'] = 'A';
        $this->datastreamInfo['dsLabel'] = '';
        $this->datastreamInfo['dsVersionable'] = true;
        $this->datastreamInfo['dsMIME'] = 'text/xml';
        $this->datastreamInfo['dsFormatURI'] = '';
        $this->datastreamInfo['dsChecksumType'] = 'DISABLED';
        $this->datastreamInfo['dsChecksum'] = 'none';
        $this->datastreamInfo['dsLogMessage'] = '';
        $this->datastreamInfo['content'] = array(
        'type' => 'string',
        'content' => ' '
        );
    }

    /**
     * Validates and normalizes the control group.
     *
     * @param string $value
     *   The passed in control group.
     *
     * @return mixed
     *   The sting for the ControlGroup or FALSE if validation fails.
     */
    protected function validateControlGroup($value)
    {
        switch (strtolower($value)) {
            case 'x':
            case 'inline':
            case 'inline xml':
                return 'X';
                break;

            case 'm':
            case 'managed':
            case 'managed content':
                return 'M';
                break;

            case 'r':
            case 'redirect':
                return 'R';
                break;

            case 'e':
            case 'external':
            case 'external referenced':
                return 'E';
                break;

            default:
                return false;
                break;
        }
    }

    /**
     * Validates and normalizes the contentType.
     *
     * @param string $type
     *   The passed in value for type.
     *
     * @return mixed
     *   The stirng for the type or FALSE if validation fails.
     */
    protected function validateType($type)
    {
        switch (strtolower($type)) {
            case 'string':
                return 'string';
                break;

            case 'url':
                return 'url';
                break;

            case 'file':
                return 'file';
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
        return parent::controlGroupMagicProperty($function, $value);
    }

    /**
     * @see AbstractDatastream::state
     */
    protected function stateMagicProperty($function, $value)
    {
        switch ($function) {
            case 'get':
                return $this->datastreamInfo['dsState'];
                break;

            case 'isset':
                return true;
                break;

            case 'set':
                $state = $this->validateState($value);
                if ($state !== false) {
                    $this->datastreamInfo['dsState'] = $state;
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
                return $this->datastreamInfo['dsLabel'];
                break;

            case 'isset':
                return $this->isDatastreamPropertySet(
                    $this->datastreamInfo['dsLabel'],
                    ''
                );
                break;

            case 'set':
                $this->datastreamInfo['dsLabel'] =
                function_exists('mb_substr') ?
                mb_substr($value, 0, 255) : substr($value, 0, 255);
                break;

            case 'unset':
                $this->datastreamInfo['dsLabel'] = '';
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
                return $this->datastreamInfo['dsVersionable'];
                break;

            case 'isset':
                return true;
                break;

            case 'set':
                if ($this->validateVersionable($value)) {
                    $this->datastreamInfo['dsVersionable'] = $value;
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
                return $this->datastreamInfo['dsMIME'];
                break;

            case 'isset':
                return true;
                break;

            case 'set':
                if ($this->validateMimetype($value)) {
                    $this->datastreamInfo['dsMIME'] = $value;
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
                return $this->datastreamInfo['dsFormatURI'];
                break;

            case 'isset':
                return $this->isDatastreamPropertySet(
                    $this->datastreamInfo['dsFormatURI'],
                    ''
                );
                break;

            case 'set':
                $this->datastreamInfo['dsFormatURI'] = $value;
                break;

            case 'unset':
                $this->datastreamInfo['dsFormatURI'] = '';
                break;
        }
        return null;
    }

    /**
     * @see AbstractDatastream::checksum
     * @todo this should be refined a bit
     */
    protected function checksumMagicProperty($function, $value)
    {
        switch ($function) {
            case 'get':
                return $this->datastreamInfo['dsChecksum'];
                break;

            case 'isset':
                return $this->isDatastreamPropertySet(
                    $this->datastreamInfo['dsChecksum'],
                    'none'
                );
                break;

            case 'set':
                $this->datastreamInfo['dsChecksum'] = $value;
                break;

            case 'unset':
                $this->datastreamInfo['dsChecksum'] = 'none';
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
                return $this->datastreamInfo['dsChecksumType'];
                break;

            case 'isset':
                return $this->isDatastreamPropertySet(
                    $this->datastreamInfo['dsChecksumType'],
                    'DISABLED'
                );
                break;

            case 'set':
                $type = $this->validateChecksumType($value);
                if ($type !== false) {
                    $this->datastreamInfo['dsChecksumType'] = $type;
                } else {
                    trigger_error(
                        "$value is not a valid value for the datastream->checksumType property.",
                        E_USER_WARNING
                    );
                }
                break;

            case 'unset':
                $this->datastreamInfo['dsChecksumType'] = 'DISABLED';
                break;
        }
        return null;
    }

    /**
     * @see AbstractDatastream::content
     */
    protected function contentMagicProperty($function, $value)
    {
        switch ($function) {
            case 'get':
                switch ($this->datastreamInfo['content']['type']) {
                    case 'string':
                    case 'url':
                        return $this->datastreamInfo['content']['content'];
                    case 'file':
                        return file_get_contents($this->datastreamInfo['content']['content']);
                }
                break;

            case 'isset':
                return $this->isDatastreamPropertySet(
                    $this->datastreamInfo['content']['content'],
                    ' '
                );
                break;

            case 'set':
                if ($this->controlGroup == 'M' || $this->controlGroup == 'X') {
                    $this->deleteTempFile();
                    $this->datastreamInfo['content']['type'] = 'string';
                    $this->datastreamInfo['content']['content'] = $value;
                } else {
                    trigger_error(
                        "Cannot set content of a {$this->controlGroup} datastream, please use datastream->url.",
                        E_USER_WARNING
                    );
                }
                break;

            case 'unset':
                if ($this->controlGroup == 'M' || $this->controlGroup == 'X') {
                    $this->datastreamInfo['content']['type'] = 'string';
                    $this->datastreamInfo['content']['content'] = ' ';
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
                if ($this->controlGroup == 'E' || $this->controlGroup == 'R') {
                    return $this->datastreamInfo['content']['content'];
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
                    $this->datastreamInfo['content']['type'] = 'url';
                    $this->datastreamInfo['content']['content'] = $value;
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
                return $this->datastreamInfo['dsLogMessage'];
                break;

            case 'isset':
                return $this->isDatastreamPropertySet(
                    $this->datastreamInfo['dsLogMessage'],
                    ''
                );
                break;

            case 'set':
                $this->datastreamInfo['dsLogMessage'] = $value;
                break;

            case 'unset':
                $this->datastreamInfo['dsLogMessage'] = '';
                break;
        }
        return null;
    }

    /**
     * @see AbstractDatastream::setContentFromFile
     *
     * @param boolean $copy
     *   If TRUE this object will copy and manage the given file, if FALSE the
     *   management of the files is up to the caller.
     */
    public function setContentFromFile($file, $copy = true)
    {
        if ($this->controlGroup == 'E' || $this->controlGroup == 'R') {
            trigger_error(
                "Function cannot be called on a {$this->controlGroup} datastream. Please use datastream->url.",
                E_USER_WARNING
            );
            return;
        }
        $this->deleteTempFile();
        $this->copied = $copy;
        if ($copy) {
            $tmpfile = tempnam(sys_get_temp_dir(), 'tuque');
            copy($file, $tmpfile);
            $file = $tmpfile;
        }
        $this->datastreamInfo['content']['type'] = 'file';
        $this->datastreamInfo['content']['content'] = $file;
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
        $this->deleteTempFile();
        $this->datastreamInfo['content']['type'] = 'url';
        $this->datastreamInfo['content']['content'] = $url;
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
        $this->deleteTempFile();
        $this->datastreamInfo['content']['type'] = 'string';
        $this->datastreamInfo['content']['content'] = $string;
    }

    /**
     * @see AbstractDatastream::getContent
     */
    public function getContent($file)
    {
        if ($this->controlGroup == 'E' || $this->controlGroup == 'R') {
            trigger_error(
                "Function cannot be called on a {$this->controlGroup} datastream. Please use datastream->url.",
                E_USER_WARNING
            );
            return null;
        }
        switch ($this->datastreamInfo['content']['type']) {
            case 'file':
                copy($this->datastreamInfo['content']['content'], $file);
                return true;
            case 'string':
                file_put_contents(
                    $file,
                    $this->datastreamInfo['content']['content']
                );
                return true;
            case 'url':
                return false;
        }
        return null;
    }

    public function __destruct()
    {
        $this->deleteTempFile();
    }

    /**
     * Deletes any temp files that may be present such that we do not
     * 'leak' any files.
     */
    private function deleteTempFile()
    {
        if ($this->datastreamInfo['content']['type'] == 'file' && $this->copied == true) {
            unlink($this->datastreamInfo['content']['content']);
        }
    }
}
