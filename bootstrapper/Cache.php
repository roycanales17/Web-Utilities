<?php

	namespace App\Bootstrap\Bootstrapper;

	use App\Utilities\Cache as BaseCache;
	use App\Utilities\Config;
	use App\Utilities\Handler\Bootloader;

	final class Cache extends Bootloader
	{
		public function handler(): void {
			$conf = Config::get('Cache');

			if ($cache = $conf['cache']['driver'] ?? '') {
				$cache_attr = $conf['cache'][$cache];
				BaseCache::configure($cache_attr['driver'], $cache_attr['server'], $cache_attr['port']);
				console_log("Cache driver: %s", [$cache_attr['driver']->value ?? '']);
			}
		}
	}