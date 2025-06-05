<?php

	namespace App\utilities\Blueprints;

	enum CacheDriver: string {
		case Memcached = 'memcached';
		case Redis = 'redis';
	}