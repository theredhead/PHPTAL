<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
//  
//  Copyright (c) 2004 Laurent Bedubourg
//  
//  This library is free software; you can redistribute it and/or
//  modify it under the terms of the GNU Lesser General Public
//  License as published by the Free Software Foundation; either
//  version 2.1 of the License, or (at your option) any later version.
//  
//  This library is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
//  Lesser General Public License for more details.
//  
//  You should have received a copy of the GNU Lesser General Public
//  License along with this library; if not, write to the Free Software
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
//  
//  Authors: Laurent Bedubourg <lbedubourg@motion-twin.com>
//  

/**
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 * @package PHPTAL
 */
class PHPTAL_RepeatController
{
    public $source;
    public $index;
    public $number;
    public $start;
    public $end;
    public $length;
    public $even;
    public $odd;

    public function __construct( $source )
    {
        $this->source = $source;
        $this->index = -1;
        $this->number = 0;
        $this->start = true;
        $this->end = false;
        $this->length = $this->_size();
    }

    public function key()
    {
        if (!isset($this->_keys)){
            $this->_keys = array_keys($this->source);
        }
        return $this->_keys[$this->index];
    }

    private function _size()
    {
        $iterable = $this->source;
        if (is_array($iterable)) 
            return count($iterable);
        if (is_string($iterable))
            return strlen($iterable);
        if (is_object($iterable) && method_exists($iterable, 'size')) 
            return $iterable->size();
        if (is_object($iterable) && method_exists($iterable, 'length')) 
            return $iterable->length();
        return 0;        
    }

    private $_keys;
}

?>
