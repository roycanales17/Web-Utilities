<?php

	use App\Routes\Route;

	Route::post('/api/stream-wire/{identifier}', [App\Utilities\Stream::class, 'capture']);