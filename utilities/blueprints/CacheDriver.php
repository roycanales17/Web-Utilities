<?php

	namespace App\Utilities\Blueprints;

	/**
	 * Enum CacheDriver
	 *
	 * Defines available cache drivers for the application.
	 */
	enum CacheDriver: string
	{
		/** Memcached driver */
		case Memcached = 'memcached';

		/** Redis driver */
		case Redis = 'redis';
	}
