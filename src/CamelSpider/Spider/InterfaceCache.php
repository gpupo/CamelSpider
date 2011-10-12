<?php

namespace CamelSpider\Spider;

/*
* This file is part of the CamelSpider package.
*
* (c) Gilmar Pupo <g@g1mr.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*
* @package     CamelSpider
* @subpackage  Spider
* @author      Gilmar Pupo <g@g1mr.com>
*
*/
interface InterfaceCache
{

    public function save($id, $data, array $tags);
    public function getObject($id);
    public function isObject($id);

}
