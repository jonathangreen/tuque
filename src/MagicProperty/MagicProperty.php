<?php

namespace Islandora\Tuque\MagicProperty;

/**
 * This abstract class allows us to implement PHP magic properties by defining
 * a private method in the class that extends it. It attempts to make the magic
 * properties behave as much like normal PHP properties as possible.
 *
 * This code lets the user define a new method that will be called when a
 * property is accessed. Any method that ends in MagicProperty is code that
 * implements a magic property.
 *
 * Usage Example
 * @code
 * class MyClass extends MagicProperty {
 *   private $secret;
 *
 *   protected function myExampleMagicProperty($function, $value) {
 *     switch($function) {
 *       case 'set':
 *         $secret = $value;
 *         return;
 *       case 'get':
 *         return $secret;
 *       case 'isset':
 *         return isset($secret);
 *       case 'unset':
 *         return unset($secret);
 *     }
 *   }
 * }
 *
 * $test = new MyClass();
 * $test->myExample = 'woot';
 * print($test->myExample);
 * @endcode
 */
abstract class MagicProperty
{

    /**
     * Returns the name of the magic property. Makes it easy to change what we
     * use as the name.
     *
     * @param string $name
     *
     * @return string
     */
    protected function getGeneralMagicPropertyMethodName($name)
    {
        $method = $name . 'MagicProperty';
        return $method;
    }

    /**
     * This implements the PHP __get function which is utilized for reading data
     * from inaccessible properties. It wraps it by calling the appropriately named
     * method in the inheriting class.
     * http://php.net/manual/en/language.oop5.overloading.php
     *
     * @param string $name
     *   The name of the function being called.
     *
     * @return mixed
     *   The data returned from the property.
     */
    public function __get($name)
    {
        $generalMethod = $this->getGeneralMagicPropertyMethodName($name);
        $specificMethod = $generalMethod . 'Get';
        if (method_exists($this, $specificMethod)) {
            return $this->$specificMethod();
        } elseif (method_exists($this, $generalMethod)) {
            return $this->$generalMethod('get', null);
        } else {
            // We trigger an error like php would. This helps with debugging.
            $trace = debug_backtrace();
            $class = get_class($trace[0]['object']);
            trigger_error(
                'Undefined property: ' . $class . '::$' . $name .
                ' in ' . $trace[0]['file'] .
                ' on line ' . $trace[0]['line'] . ' triggered via __get',
                E_USER_NOTICE
            );
            return null;
        }
    }

    /**
     * This implements the PHP __isset function which is utilized for testing if
     * data in inaccessible properties is set. This function calls the
     * appropriately named method in the inheriting class.
     * http://php.net/manual/en/language.oop5.overloading.php
     *
     * @param string $name
     *   The name of the function being called.
     *
     * @return boolean
     *   If the variable is set.
     */
    public function __isset($name)
    {
        $generalMethod = $this->getGeneralMagicPropertyMethodName($name);
        $specificMethod = $generalMethod . 'Isset';
        if (method_exists($this, $specificMethod)) {
            return $this->$specificMethod();
        } elseif (method_exists($this, $generalMethod)) {
            return $this->$generalMethod('isset', null);
        } else {
            return false;
        }
    }

    /**
     * This implements the PHP __set function which is utilized for setting
     * inaccessible properties.
     * http://php.net/manual/en/language.oop5.overloading.php
     *
     * @param string $name
     *   The property to set.
     * @param void $value
     *   The value it should be set with.
     *
     * @return null
     */
    public function __set($name, $value)
    {
        $generalMethod = $this->getGeneralMagicPropertyMethodName($name);
        $specificMethod = $generalMethod . 'Set';
        if (method_exists($this, $specificMethod)) {
            return $this->$specificMethod($value);
        } elseif (method_exists($this, $generalMethod)) {
            $this->$generalMethod('set', $value);
        } else {
            // Else we allow it to be set like a normal property.
            $this->$name = $value;
        }

        return null;
    }

    /**
     * This implements the PHP __unset function which is utilized for unsetting
     * inaccessable properties.
     * http://php.net/manual/en/language.oop5.overloading.php
     *
     * @param string $name
     *   The property to unset
     *
     * @return null
     */
    public function __unset($name)
    {
        $generalmethod = $this->getGeneralMagicPropertyMethodName($name);
        $specificmethod = $generalmethod . 'Unset';
        if (method_exists($this, $specificmethod)) {
            return $this->$specificmethod();
        } elseif (method_exists($this, $generalmethod)) {
            return $this->$generalmethod('unset', null);
        }

        return null;
    }

    /**
     * Test if a property appears to be magical.
     *
     * @param string $name
     *   The name of a property to test.
     *
     * @return bool
     *   TRUE if the property appears to be magically implemented;
     *   otherwise, FALSE.
     */
    protected function propertyIsMagical($name)
    {
        $generalmethod = $this->getGeneralMagicPropertyMethodName($name);
        if (method_exists($this, $generalmethod)) {
            return true;
        }
        $ops = array('set', 'isset', 'unset', 'get');
        foreach ($ops as $op) {
            if (method_exists($this, "{$generalmethod}{$op}")) {
                return true;
            }
        }
        return false;
    }
}
