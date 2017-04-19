<?php

namespace Islandora\Tuque\Datastream;

use Islandora\Tuque\MagicProperty\MagicProperty;
use Islandora\Tuque\Date\FedoraDate;

/**
 * This abstract class can be overriden by anything implementing a datastream.
 */
abstract class AbstractDatastream extends MagicProperty
{

    /**
     * This will set the state of the datastream to deleted.
     */
    abstract public function delete();

    /**
     * Set the contents of the datastream from a file.
     *
     * @param string $file
     *   The full path of the file to set to the contents of the datastream.
     */
    abstract public function setContentFromFile($file);

    /**
     * Set the contents of the datastream from a URL. The contents of this
     * URL will be fetched, and the datastream will be updated to contain the
     * contents of the URL.
     *
     * @param string $url
     *   The full URL to fetch.
     */
    abstract public function setContentFromUrl($url);

    /**
     * Set the contents of the datastream from a string.
     *
     * @param string $string
     *   The string whose contents will become the contents of the datastream.
     */
    abstract public function setContentFromString($string);

    /**
     * Get the contents of a datastream and output it to the file provided.
     *
     * @param string $file
     *   The path of the file to output the contents of the datastream to.
     *
     * @return
     *   TRUE on success or FALSE on failure.
     */
    abstract public function getContent($file);

    /**
     * The identifier of the datastream. This is a read-only property.
     *
     * @var string
     */
    public $id;

    /**
     * The label of the datastream. Fedora limits the label to be 255
     * characters. Anything after this amount is truncated.
     *
     * @var string
     */
    public $label;

    /**
     * the location of consists of a combination of
     * datastream id and datastream version id
     * @var string
     */
    public $location;

    /**
     * The control group of the datastream. This property is read-only. This
     * will return one of: "X", "M", "R", or "E" (Inline *X*ML,
     * *M*anaged Content, *R*edirect, or *E*xternal Referenced).
     * Defaults to "M".
     * @var string
     */
    public $controlGroup;

    /**
     * This defines if the datastream will be versioned or not.
     * @var boolean
     */
    public $versionable;

    /**
     * The state of the datastream. This will be one of: "A", "I", "D". When
     * setting the property you can use: A, I, D or Active, Inactive, Deleted.
     * @var string
     */
    public $state;

    /**
     * The mimetype of the datastrem.
     * @var string
     */
    public $mimetype;

    /**
     * The format of the datastream.
     * @var string
     */
    public $format;

    /**
     * The size in bytes of the datastream. This is only valid once a
     * datastream has been ingested.
     *
     * @var int
     */
    public $size;

    /**
     * The base64 encoded checksum string.
     *
     * @var string
     */
    public $checksum;

    /**
     * The type of checksum that will be done on this datastream. Defaults to
     * DISABLED. One of: DISABLED, MD5, SHA-1, SHA-256, SHA-384, SHA-512.
     *
     * @var string
     */
    public $checksumType;

    /**
     * The date the datastream was created.
     *
     * @var FedoraDate
     */
    public $createdDate;

    /**
     * The contents of the datastream as a string. This can only be set for
     * M and X datastreams. For R and E datastreams the URL property needs to be
     * set which will change the contents of this property. This should only be
     * used for small files, as it loads the contents into PHP memory. Otherwise
     * you should use the getContent function.
     *
     * @var string
     */
    public $content;

    /**
     * This is only valid for R and E datastreams. This is the URL that the
     * datastream references.
     *
     * @var string
     */
    public $url;

    /**
     * This is the log message that will be associated with the action in the
     * Fedora audit datastream.
     *
     * @var string
     */
    public $logMessage;

    /**
     * Unsets public members.
     *
     * We only define the public members of the object for Doxygen, they aren't
     * actually accessed or used, and if they are not unset, they can cause
     * problems after deserialization.
     */
    public function __construct()
    {
        $this->unsetMembers();
    }

    /**
     * Upon deserialization unset any public members.
     */
    public function __wakeup()
    {
        $this->unsetMembers();
    }

    /**
     * Unset public members, required for child classes to function properly
     * with MagicProperties.
     */
    private function unsetMembers()
    {
        unset($this->id);
        unset($this->label);
        unset($this->controlGroup);
        unset($this->versionable);
        unset($this->state);
        unset($this->mimetype);
        unset($this->format);
        unset($this->size);
        unset($this->checksum);
        unset($this->checksumType);
        unset($this->createdDate);
        unset($this->content);
        unset($this->url);
        unset($this->location);
        unset($this->logMessage);
    }
}
