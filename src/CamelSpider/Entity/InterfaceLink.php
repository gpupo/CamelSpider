<?php

namespace CamelSpider\Entity;

interface InterfaceLink
{

    public function getId();
    public function isDone();
    public function isWaiting();
    public function toMinimal();

}
