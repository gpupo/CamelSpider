<?php

namespace CamelSpider\Spider;

interface InterfaceFeedReader {

    public function import();
    public function request($uri);
}
