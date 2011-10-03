<?php

namespace CamelSpider\Spider;

interface SubscriptionInterface {
	public function getUrl();
	public function getRecursive();
	public function getCredentials();
	public function getFilters();
	public function addDocument();
}

