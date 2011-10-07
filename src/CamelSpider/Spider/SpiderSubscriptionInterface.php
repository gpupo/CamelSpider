<?php

namespace CamelSpider\Spider;

interface SpiderSubscriptionInterface {
	public function getUrl();
	public function getRecursive();
	public function getCredentials();
	public function getFilters();
	public function addDocument();
}

