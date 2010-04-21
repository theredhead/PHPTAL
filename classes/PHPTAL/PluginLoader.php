<?php
/**
 * PHPTAL templating engine
 *
 * PHP Version 5
 *
 * @category HTML
 * @package  PHPTAL
 * @author   Laurent Bedubourg <lbedubourg@motion-twin.com>
 * @author   Kornel Lesiński <kornel@aardvarkmedia.co.uk>
 * @author   Iván Montes <drslump@pollinimini.net>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @version  SVN: $Id: $
 * @link     http://phptal.org/
 */

class PHPTAL_PluginLoader
{
    protected $_registry = array();
    protected $_loaded = array();
    // 
    // public function simple()
    // {
    //     if (!class_exists($classname,true)) {
    //             $filename = $classname.'.php';
    //             if ($fp = @fopen($filename,"r",true)) {
    //                 fclose($fp);
    //                 
    //                 include $filename;
    //                 
    //                 if (!class_exists($classname,false)) {
    //                     throw new PHPTAL_ConfigurationException("Can't load prefilter '$name', file '$filename' does not contain class '$classname'");
    //                 }
    //             } else {
    //                 throw new PHPTAL_IOException("Can't load prefilter '$name'. Autoload didn't load class '$classname' and file '$filename' could not be found in include path");
    //             }
    //         }
    //     }
    
    public function addPrefixPath($classPrefix, $pathPrefix)
    {
        if (!isset($this->_registry[$classPrefix])) {
            $this->_registry[$classPrefix] = array();
        }
        
        // paths added later will be checked first
        array_unshift($this->_registry[$classPrefix], rtrim($pathPrefix,'/\\') . DIRECTORY_SEPARATOR);
    }
    
    public function getPaths($prefix = null)
    {
        if (empty($prefix)) {
            return $this->_registry;
        } else if (!empty($this->_registry[$prefix])) {
            return $this->_registry[$prefix];
        }
        
        return false;
    }
    
    /**
     * Load a plugin by its name
     *
     * @param string $name
     *
     * @return string|false Class name of loaded plugin or false
     */
    public function load($name)
    {
        if (isset($this->_loaded[$name])) {
            return $this->_loaded[$name];
        }
    
        $filePostfix = strtr($name, '\\_', DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR) . '.php';
        foreach ($this->_registry as $prefix => $paths) {
            $className = $prefix . '_' . $name;
            
            // Check if the class is already loaded
            if (class_exists($className, false)) {
                $this->_loaded[$name] = $className;
                return $className;
            }
            
            // Check each path for the plugin file
            foreach ($paths as $path) {
                $file = $path . $filePostfix;
                if (is_readable($file)) {
                    include_once $file;
                    
                    $this->_loaded[$name] = $className;
                    return $className;
                }
            }
        }
        
        if (class_exists($name,true)) {
            $this->_loaded[$name] = $name;
            return $name;
        }
        
        return false;
    }
}
