<?php

namespace org\octris\core\type {
    /****c* type/collection
     * NAME
     *      collection
     * FUNCTION
     *      collection type -- implements special access on array
     *      objects
     * COPYRIGHT
     *      copyright (c) 2010 by Harald Lapp
     * AUTHOR
     *      Harald Lapp <harald@octris.org>
     ****
     */

    class collection implements \IteratorAggregate, \ArrayAccess, \Serializable, \Countable {
        /****v* collection/$data
         * SYNOPSIS
         */
        protected $data = array();
        /*
         * FUNCTION
         *      collection data
         ****
         */

        /****v* collection/$keys
         * SYNOPSIS
         */
        protected $keys = array();
        /*
         * FUNCTION
         *      parameter names
         ****
         */
        
        /****m* collection/__construct
         * SYNOPSIS
         */
        public function __construct($array = null)
        /*
         * FUNCTION
         *      constructor
         * INPUTS
         *      * $array (mixed) -- (optional) array to construct
         ****
         */
        {
            if (is_null($array)) {
                $array = array();
            } elseif (is_scalar($array)) {
                // a scalar will be splitted into bytes
                $array = str_split((string)$array, 1);
            } elseif (is_object($array)) {
                if (($array instanceof collection) || ($array instanceof collection\Iterator) || ($array instanceof \ArrayIterator)) {
                    $array = $array->getArrayCopy();
                } else {
                    $array = (array)$array;
                }
            } elseif (!is_array($array)) {
                throw new Exception('don\'t know how to handle parameter of type "' . gettype($array) . '"');
            }
        
            $this->keys = array_keys($array);
            $this->data = $array;
        }
    
        /****m* collection/getIterator
         * SYNOPSIS
         */
        public function getIterator()
        /*
         * FUNCTION
         *      returns iterator object for collection
         * OUTPUTS
         *      (iterator) -- iterator object
         ****
         */
        {
            return new \ArrayIterator($this->data);
        }
        
        /****m* collection/getArrayCopy
         * SYNOPSIS
         */
        public function getArrayCopy()
        /*
         * FUNCTION
         *      returns copy of data as PHP array
         * OUTPUTS
         *      (array) -- collection data
         ****
         */
        {
            return $this->data;
        }
        
        /****m* collection/offsetExists
         * SYNOPSIS
         */
        public function offsetExists($offs)
        /*
         * FUNCTION
         *      whether a offset exists
         * INPUTS
         *      * $offs (string) -- offset to test
         * OUTPUTS
         *      (bool) -- returns true, if offset exists
         ****
         */
        {
            return (in_array($offs, $this->keys));
        }

        /****m* collection/offsetGet
         * SYNOPSIS
         */
        public function offsetGet($offs)
        /*
         * FUNCTION
         *      offset to retrieve
         * INPUTS
         *      * $offs (string) -- offset to retrieve
         * OUTPUTS
         *      (mixed) -- array value for offset
         ****
         */
        {
            $idx = array_search($offs, $this->keys, true);
        
            return ($idx !== false ? $this->data[$this->keys[$idx]] : false);
        }

        /****m* collection/offsetSet
         * SYNOPSIS
         */
        public function offsetSet($offs, $value)
        /*
         * FUNCTION
         *      offset to set
         * INPUTS
         *      * $offs (string) -- offset to set
         *      * $value (mixed) -- value for offset to set
         ****
         */
        {
            // is_null implements $...[] = ...
            if (!is_null($offs) && ($idx = array_search($offs, $this->keys, true)) !== false) {
                $this->data[$this->keys[$idx]] = $value;
            } else {
                $this->keys[]      = $offs;
                $this->data[$offs] = $value;
            }
        }
        
        /****m* collection/offsetUnset
         * SYNOPSIS
         */
        public function offsetUnset($offs)
        /*
         * FUNCTION
         *      offset to unset
         * INPUTS
         *      * $offs (string) -- offset to unset
         ****
         */
        {
            $idx = array_search($offs, $this->keys, true);
        
            if ($idx !== false) {
                unset($this->keys[$idx]);
                unset($this->data[$offs]);
            }
        }

        /****m* collection/serialize
         * SYNOPSIS
         */
        public function serialize()
        /*
         * FUNCTION
         *      when serializing collection
         * OUTPUTS
         *      (string) -- serialized collection data
         ****
         */
        {
            return serialize($this->data);
        }

        /****m* collection/unserialize
         * SYNOPSIS
         */
        public function unserialize($data)
        /*
         * FUNCTION
         *      when collection data is unserialized
         * INPUTS
         *      * $data (string) -- serialized data to unserialize und pull into collection
         ****
         */
        {
            $this->__construct(unserialize($data));
        }

        /****m* collection/count
         * SYNOPSIS
         */
        public function count()
        /*
         * FUNCTION
         *      count items in collection
         * OUTPUTS
         *      (int) -- items in collection
         ****
         */
        {
            return count($this->data);
        }
        
        /****m* collection/flatten
         * SYNOPSIS
         */
        public function flatten($sep = '.') 
        /*
         * FUNCTION
         *      flatten an array. convert a recursive index or key/value based array into a flat array with expanded
         *      keys.
         * INPUTS
         *      * $sep (string) -- (optional) separator for expanding keys
         * OUTPUTS
         *      (collection) -- flattened array
         ****
         */
        {
            $tmp = array();

            $array = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($this->data), \RecursiveIteratorIterator::SELF_FIRST);
            $d = 0;

            $property = array();

            foreach ($array as $k => $v) {
                if (!is_int($k)) {
                    if ($d > $array->getDepth()) {
                        array_splice($property, $array->getDepth());
                    }

                    $property[$array->getDepth()] = $k;

                    $d = $array->getDepth();
                }

                if (is_int($k)) {
                    $tmp[implode($sep, $property)][] = $v;
                } elseif (!is_array($v)) {
                    $tmp[implode($sep, $property)] = $v;
                }
            }

            return new collection($tmp);
        }

        /****m* collection/deflatten
         * SYNOPSIS
         */
        public function deflatten()
        /*
         * FUNCTION
         *      deflatten an array, which was flattened with flatten method
         ****
         */
        {
            $tmp = array();

            foreach ($this->data as $k => $v) {
                $key  = explode('.', $k);
                $ref =& $tmp;

                foreach ($key as $part) {
                    if (!isset($ref[$part])) {
                        $ref[$part] = array();
                    }

                    $ref =& $ref[$part];
                }

                $ref = $v;
            }

            return new collection($tmp);
        }
        
        /****m* collection/merge
         * SYNOPSIS
         */
        public function merge()
        /*
         * FUNCTION
         *       merge current collection with one or multiple others
         * INPUTS
         *      * $arg1 (mixed) -- array or collection to merge
         *      * ...
         ****
         */
        {
            for ($i = 0, $cnt = func_num_args(); $i < $cnt; ++$i) {
                $arg = func_get_arg($i);
                
                if (is_array($arg)) {
                    $this->data = array_merge($this->data, $arg);
                } elseif (is_object($arg)) {
                    if (($arg instanceof collection) || ($arg instanceof collection\Iterator) || ($arg instanceof \ArrayIterator)) {
                        $arg = $arg->getArrayCopy();
                    } else {
                        $arg = (array)$arg;
                    }

                    $this->data = array_merge($this->data, $arg);
                }
            }
        }
        
        /****m* collection/utf8Encode
         * SYNOPSIS
         */
        public function utf8Encode()
        /*
         * FUNCTION
         *      utf8 encode collection values
         * OUTPUTS
         *      (collection) -- encoded data
         ****
         */
        {
            $tmp = $this->data;
            
            array_walk_recursive($tmp, function(&$v) {
                if (is_string($v) && !preg_match('%^(?:  
                [\x09\x0A\x0D\x20-\x7E] # ASCII  
                | [\xC2-\xDF][\x80-\xBF] # non-overlong 2-byte  
                | \xE0[\xA0-\xBF][\x80-\xBF] # excluding overlongs  
                | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2} # straight 3-byte  
                | \xED[\x80-\x9F][\x80-\xBF] # excluding surrogates  
                | \xF0[\x90-\xBF][\x80-\xBF]{2} # planes 1-3  
                | [\xF1-\xF3][\x80-\xBF]{3} # planes 4-15  
                | \xF4[\x80-\x8F][\x80-\xBF]{2} # plane 16  
                )*$%xs', $v)) {
                    $v = utf8_encode($v);
                }            
            });

            return new collection($tmp);
        }

        /****m* collection/utf8Decode
         * SYNOPSIS
         */
        public function utf8Decode()
        /*
         * FUNCTION
         *      utf8 decode collection
         * OUTPUTS
         *      (collection) -- decoded data
         ****
         */
        {
            $tmp = $this->data;
            
            array_walk_recursive($tmp, function(&$v) {
                if (is_string($v) && preg_match('%^(?:  
                [\x09\x0A\x0D\x20-\x7E] # ASCII  
                | [\xC2-\xDF][\x80-\xBF] # non-overlong 2-byte  
                | \xE0[\xA0-\xBF][\x80-\xBF] # excluding overlongs  
                | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2} # straight 3-byte  
                | \xED[\x80-\x9F][\x80-\xBF] # excluding surrogates  
                | \xF0[\x90-\xBF][\x80-\xBF]{2} # planes 1-3  
                | [\xF1-\xF3][\x80-\xBF]{3} # planes 4-15  
                | \xF4[\x80-\x8F][\x80-\xBF]{2} # plane 16  
                )*$%xs', $v)) {
                    $v = utf8_decode($v);
                }            
            });

            return new collection($tmp);
        }
    }
}

