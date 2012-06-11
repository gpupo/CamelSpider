<?php

namespace CamelSpider\Entity;

interface InterfaceSubscription extends InterfaceLink
{

    public function getDomain();
    public function getHref();
    public function getFilters();
    public function getMaxDepth();
    public function getLink($sha1);
    public function getSourceType();
    public function getAuthInfo();

}
