# PHP Utilities Library

A comprehensive collection of essential **PHP utilities** designed to streamline development and enhance productivity. This library includes versatile tools for common tasks, offering a robust foundation for building efficient and maintainable applications.

---

## ⚙️ Installation

```bash
composer require roy404/utilities
```

Then include the autoloader in your project entry file (e.g., index.php):
```php
require_once 'autoload.php';
```

---

## 🚀 Features

This library includes a range of utility classes to handle common development needs:

- [**Cache**](#cache) — Simplified caching interface for fast, temporary storage.
- [**Carbon**](#carbon) — Simplified date and time manipulation.
- [**Config**](#config) — Easy access to configuration files and environment-based settings.
- [**Logger**](#logger) — Lightweight logging system to track events, errors, and debug output.
- [**Mail**](#mail) — Utility for sending emails with flexible transport options.
- [**Storage**](#storage) — Easy-to-use file storage and management with local and cloud support (e.g., AWS S3).
- [**RateLimiter**](#ratelimiter) — Control access frequency and throttle requests effectively.
- [**Server**](#server) — Useful helpers for interacting with server and request data.
- [**Session**](#session) — Streamlined session management and flash messaging.
- [**StreamWire**](#stream-wire) - Build interactive, stateful PHP components with real-time updates — no JavaScript required.

This library is modular, lightweight, and optimized for seamless integration into your PHP projects.  
Whether you're working on a small application or a large-scale system, **PHP Utilities Library** provides the tools you need to get the job done efficiently.

---

## 🧩 Feature Documentation

## Cache

A lightweight caching system that improves performance and reduces database overhead.
It provides a unified interface for **Redis** and **Memcached** drivers, allowing you to store, retrieve, and manage cached data efficiently.

**Usage Example:**

```php
use App\Utilities\Cache;
use App\Utilities\Blueprints\CacheDriver;

// ------------------------------------------------------
// STEP 1: Configure the Cache Driver
// ------------------------------------------------------

// Supported drivers: Redis | Memcached
$driver = CacheDriver::Memcached->value;
$host = 'memcached';
$port = '11211';

// Initialize cache connection
Cache::configure($driver, $host, $port);

// ------------------------------------------------------
// STEP 2: Basic Cache Operations
// ------------------------------------------------------

// Store an item in cache for 3 minutes (60 * 3 seconds)
Cache::set('key', 'value', 60 * 3);

// Retrieve an item from cache (returns 'default' if not found)
$value = Cache::get('key', 'default');

// Check if a cache key exists
if (Cache::has('key')) {
    echo "Cache key exists!";
}

// Delete a specific cache key
Cache::delete('key');

// Clear all cache entries (use carefully in production)
Cache::clear();

// ------------------------------------------------------
// STEP 3: Advanced Usage
// ------------------------------------------------------

// Get the expiration timestamp of a cache entry
$expiration = Cache::getExpiration('key');

// Retrieve cached value if exists, otherwise compute and cache it
$result = Cache::remember('user_profile', function () {
    // Example: Fetch data from a slow API or database
    return ['name' => 'Roy', 'role' => 'Admin'];
}, 60 * 3);

// ------------------------------------------------------
// Example Output
// ------------------------------------------------------
var_dump($value, $expiration, $result);
```

### Remarks
* **Supported Drivers:**
  * **Redis** - Recommended for high-performance, distributed caching.
  * **Memcached** - Great for lightweight, in-memory key-value caching.
* **Performance Tip:** <br> Cache frequently accessed or computationally expensive data (e.g., database queries, API calls).
* **Best Practice:** <br> Configure your cache driver once (typically on application boot) before any cache operations.
* **Safe Clearing:** <br> Avoid using `Cache::clear()` in production unless you intend to reset the entire cache.
* **Expiration Management:** <br> Use shorter durations for frequently changing data and longer durations for stable or rarely updated information.

---

## Carbon

A lightweight utility class for date and time manipulation, built on top of PHP’s native `DateTime`.
It provides an expressive and consistent API for handling time operations such as adding days, comparing dates, and formatting timestamps.

**Usage Example:**

```php
use App\Utilities\Carbon;

// ------------------------------------------------------
// STEP 1: Working with the Current Date and Time
// ------------------------------------------------------

// Get the current date and time
$now = Carbon::now();

// Get today's date (time set to 00:00:00)
$today = Carbon::today();

// ------------------------------------------------------
// STEP 2: Date Manipulation
// ------------------------------------------------------

// Add days to the current date
$futureDate = Carbon::addDays(1);

// Subtract days from the current date
$pastDate = Carbon::subtractDays(2);

// ------------------------------------------------------
// STEP 3: Date Comparison
// ------------------------------------------------------

// Check if a given date is in the future
$isFuture = Carbon::isFuture('2025-12-25');

// Check if a given date is in the past
$isPast = Carbon::isPast('2023-01-01');

// Calculate the difference in days between two dates
$daysBetween = Carbon::diffInDays('2025-01-01', '2025-02-01');

// ------------------------------------------------------
// STEP 4: Formatting and Parsing
// ------------------------------------------------------

// Format the current date and time
$formatted = Carbon::format('Y-m-d H:i:s');

// Parse a string date into a Carbon instance
$parsedDate = Carbon::parse('2025-10-17 14:30:00');

// ------------------------------------------------------
// Example Output
// ------------------------------------------------------
var_dump($now, $today, $futureDate, $pastDate, $isFuture, $isPast, $daysBetween, $formatted, $parsedDate);
```

### Remarks
* **Immutable Operations:** <br> Each Carbon operation returns a new instance, leaving the original date unchanged.
* **Flexible Input:** <br> You can pass any valid date string or timestamp to Carbon methods.
* **Readable Syntax:** Carbon’s method naming makes date manipulation expressive and intuitive (e.g., `addDays`, `diffInDays`).
* **Timezone-Aware:** <br> Automatically respects PHP’s timezone settings (`date_default_timezone_set`).


---

## Config

Provides a simple and consistent way to **load**, **access**, and **modify configuration values** across your application.

This utility reads environment variables or configuration files, ensuring that settings are centralized, easy to maintain, and environment-aware.

**Usage Example:**
```php
use App\Utilities\Config;

// ------------------------------------------------------
// STEP 1: Retrieving Configuration Values
// ------------------------------------------------------

// Get a configuration value by key.
// Returns 'Framework' if 'APP_NAME' is not set.
$appName = Config::get('APP_NAME', 'Framework');

// Retrieve a database setting (example)
$dbHost = Config::get('DB_HOST', 'localhost');

// ------------------------------------------------------
// STEP 2: Setting Configuration Values
// ------------------------------------------------------

// Dynamically set a configuration value at runtime.
Config::set('APP_ENV', 'production');

// You can also override an existing configuration key.
Config::set('APP_DEBUG', false);
```

### Remarks
* **Centralized Management:** <br> Keeps environment and application settings organized in one place.
* **Safe Defaults:** <br> The second argument of `Config::get()` provides a fallback when a key is missing.
* **Runtime Flexibility:** <br> You can modify configurations on the fly, useful for testing or dynamic environments.
* **Environment Integration:** <br> Works seamlessly with `.env` or array-based configuration files.
* **Best Practice:** <br> Avoid hardcoding credentials or secrets — store them in environment variables instead.

---

## Logger

A lightweight and flexible logging system for `application-level event tracking`, `debugging`, and `error reporting`.

It allows you to record messages of different severity levels and automatically store them in log files for later analysis.

**Usage Example:**

```php
use App\Utilities\Logger;

// ------------------------------------------------------
// STEP 1: Initialize the Logger
// ------------------------------------------------------
// Parameters:
// 1️⃣ Log filename (optional, default: 'app.log')
// 2️⃣ Directory path where logs are stored

$logger = new Logger('app.log', 'storage/logs');

// ------------------------------------------------------
// STEP 2: Logging Different Message Types
// ------------------------------------------------------

// Debug → For development and diagnostic details
$logger->debug('Debug message', [
    'context1' => 'value1',
    'context2' => 'value2'
]);

// Info → General runtime events
$logger->info('User successfully logged in.');

// Warning → Non-critical issue that may need attention
$logger->warning('Memory usage nearing threshold.');

// Error → Exception or failed operation
$logger->error('Database connection failed.', [
    'file' => __FILE__,
    'line' => __LINE__,
    'context' => [
        'user_id' => 42,
        'endpoint' => '/login'
        + and more...
    ]
]);
```

### **Example Error Trace Output**

When an unhandled exception occurs, the logger automatically generates a detailed trace like the example below:

```log
🚨 [ERROR] [2025-10-17 00:10:46]

Type     : ERROR
Message  : Call to undefined function asdasds()
File     : /var/www/html/routes/web.php
Line     : 16

🌐 Context:
url           : /
method        : GET
ip            : 172.19.0.1
host          : localhost:8000
protocol      : HTTP/1.1
secure        : false
is_ajax       : false
request_id    : 1d8ba2d8ee4b967f
response_code : 200
request_time  : 1760659846 [2025-10-17 00:10:46]
client_port   : 64666
server_ip     : 172.19.0.9
referer       : No Referer
content_type  : Unknown Content-Type
session_id    : 52a10629187821c8402284734b3b19bc

🔍 Trace:
#0 /var/www/html/routes/src/Scheme/Facade.php(148): require()
#1 /var/www/html/routes/src/Scheme/Facade.php(45): App\Routes\Scheme\Facade->loadRoutes(true)
#2 /var/www/html/routes/src/Route.php(48): App\Routes\Scheme\Facade->__construct('', Array, Array, '/var/www/html/r...', Array, '', true, 'localhost')
#3 /var/www/html/utilities/core/Application.php(101): App\Routes\Route::configure('/var/www/html/r...', Array, '', 'localhost')
#4 /var/www/html/app/Bootstrap.php(8): App\Bootstrap\Application->run(Object(Closure))
#5 /var/www/html/public/index.php(18): require_once('/var/www/html/a...')
#6 {main}
-----------------------------------------------
```

### Remarks
* **Log Levels:** 
  * 🐞 `debug` -> Detailed system info for development
  * ℹ️ `info` -> Standard runtime events
  * ⚠️ `warning` -> Non-breaking but important notices
  * 🚨 `error` -> Exceptions, failures, or fatal errors
* **Structured & Human-Readable:** <br> Each log entry is formatted with clear sections for `Message`, `Context`, and `Trace`.
* **Thread-Safe Writes:** <br> Uses `LOCK_EX` to prevent race conditions during concurrent writes.
* **Environment Integration:** <br> Works seamlessly with `.env` or array-based configuration files.
* **Customizable Output:** <br> You can adjust directory, file name, or (optionally) verbosity level for different environments.

---

## Mail

A simple and extensible email utility that supports multiple transport options such as **SMTP**, **TLS**, and **custom mail drivers**.

It allows you to send emails directly or by defining reusable **Mailable** classes for clean and maintainable implementations.

**Configuration:**

```php
use App\Utilities\Mail;

// ------------------------------------------------------
// STEP 1: Configure the mail transport
// ------------------------------------------------------
// Parameters:
// 1️⃣ Hostname
// 2️⃣ Port
// 3️⃣ Encryption type (tls | ssl | none)
// 4️⃣ Transport driver (smtp, sendmail, etc.)
// 5️⃣ Credentials (username/password)

Mail::configure('smtp.mailserver.com', 587, 'tls', 'smtp', [
    'username' => 'admin',
    'password' => 'admin123',
]);
```

**Basic Usage:**

```php
use App\Utilities\Mail;

// ------------------------------------------------------
// STEP 2: Compose and send an email
// ------------------------------------------------------

Mail::to(['user@example.com', 'support@example.com'])
    ->from('noreply@example.com')
    ->subject('Welcome to our platform!')
    ->body('<h1>Hello!</h1><p>Thanks for joining us.</p>')
    ->contentType('text/html')
    ->cc('manager@example.com')
    ->bcc('audit@example.com')
    ->replyTo('support@example.com')
    ->attach('This is the content', 'attachment.pdf') // Optional
    ->embedImage(base_path('/public/favicon.ico'), 'inlineImage') // Optional
    ->charset('UTF-8')
    ->header('X-Mailer', 'PHP Utilities Mailer')
    ->send();
```

Notes:
  * Supports HTML and plain text bodies.
  * `to()`, `cc()`, and `bcc()` accept a string or array of recipients.
  * `attach()` and `embedImage()` allow file attachments and inline images.
  * Automatically handles MIME type, boundary, and encoding.

**Using a Mailable Class:** <br> For more structured and reusable emails, extend the `Mailable` handler.

```php
namespace Mails;

use App\Utilities\Handler\Mailable;

class Test extends Mailable
{
    public array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function send(): bool
    {
        // ------------------------------------------------------
        // STEP 1: Define the Blade template and data to inject
        // ------------------------------------------------------
        // 'template' refers to /views/template.blade.php

        // STEP 2: Build and send the email immediately
        return $this->view('template', $this->data)->build();
    }
}
```

Then call it like this:

```php
use App\Utilities\Mail;
use Mails\Test;

Mail::mail(new Test([
    'name' => 'Roy'
]));
```

**Example Blade Template:** <br> Your email view should be located at `/views/template.blade.php`:

```bladehtml
<div>
    Hi {{ $name }},
    <p>Welcome to our PHP Utilities Library!</p>
</div>
```

### Remarks
* **Flexible Transport Layer:** Works with SMTP, Sendmail, or custom adapters.
* **Template Rendering:** Uses Blade for dynamic and clean templating.
* **Chainable API:** Intuitive builder-style syntax for composing messages.
* **Attachment & Embedding:** Easily attach files or embed inline images.
* **Reusable Mailables:** Great for larger projects or multiple email templates.
* **Production Safe:** Credentials and host details can be loaded from configuration or environment variables.

---

## Storage

A unified file storage and management system supporting local and cloud drivers (e.g., **AWS S3**).  <br> It provides a simple, fluent interface for reading, writing, and managing files and directories.

**Configuration** <br> Before using, configure the default storage path:

```php
use App\Utilities\Storage;

// Set default storage directory (optional)
// Default: 'storage'
Storage::configure('storage');
```

You can also define multiple disks like `local` or `s3`, each referring to different storage handlers.

**Basic Usage:**
```php
use App\Utilities\Storage;

// Store a file
Storage::disk('local')->put('public/file.txt', 'File contents');

// Retrieve file content
$content = Storage::disk('local')->get('public/file.txt');

// Check existence
if (Storage::disk('local')->exists('public/file.txt')) {
    echo 'File exists!';
}

// Delete a file
Storage::disk('local')->delete('public/file.txt');
```

### **Common Operations:**

1. **Write Files** <br> Writes content to a file. Automatically creates the file if it doesn’t exist.
```php
App\Utilities\Storage::put('notes/todo.txt', 'Buy groceries');
```

2. **Read Files** <br> Returns file content as a string or `null` if the file doesn’t exist.
```php
$content = App\Utilities\Storage::get('notes/todo.txt');
```

3. **Check Existence**
```php
if (App\Utilities\Storage::exists('notes/todo.txt')) {
    // Do something
}
```

4. **Delete Files** <br> Removes the file if it exists.
```php
App\Utilities\Storage::delete('notes/todo.txt');
```

### **Copy or Move Files:**
```php
use App\Utilities\Storage;

// Copy file
Storage::copy('old/file.txt', 'backup/file.txt');

// Move file
Storage::move('temp/file.txt', 'final/file.txt');
```

### File Info
```php
use App\Utilities\Storage;

$size = Storage::size('data/report.csv'); // in bytes
$lastModified = Storage::lastModified('data/report.csv'); // timestamp
```

### URLs
```php
use App\Utilities\Storage;

// returns a permanent public link.
$url = Storage::url('public/image.png');

// generates a signed temporary URL valid until expiration.
$tempUrl = Storage::temporaryUrl('public/image.png', new DateTime('+1 hour'));
```

### Directory Management
```php
use App\Utilities\Storage;

// List all files in directory
$files = Storage::allFiles('images');

// List all directories
$directories = Storage::allDirectories('images');

// Create new directory
Storage::makeDirectory('uploads/new-folder');

// Delete a directory
Storage::deleteDirectory('uploads/old-folder');
```

### Working with Multiple Disks
```php
use App\Utilities\Storage;

// Local storage
Storage::disk('local')->put('local.txt', 'Stored locally');

// S3 storage
Storage::disk('s3')->put('cloud.txt', 'Stored in the cloud');
```

### Example: Private File with Temporary Access
```php
use App\Utilities\Storage;
use DateTime;

// 1. Generate temporary URL valid for 10 minutes
$tempUrl = Storage::temporaryUrl('private/report.pdf', new DateTime('+10 minutes'));

// 2. User accesses link (validation + secure serve)
if (Storage::validateTemporaryUrl($_GET['url'])) {
    return Storage::serveTemporaryFile($_GET['url']);
}

echo 'Link expired or invalid.';
```

---

## RateLimiter

The `RateLimiter` provides a simple and efficient way to throttle actions and prevent abuse or brute-force attacks by limiting how many times a specific action can be performed within a given time frame.

It uses a cache-based mechanism (via the `Cache` utility) to track request counts per user/IP and automatically resets limits after the defined decay period.

### Concept
Each limit is tracked by a unique key (e.g., `login`, `password_reset`, or API endpoint name) combined with the user's IP address. <br> This allows you to independently control rate limits for different operations or users.

**Usage Example:**
```php
use App\Utilities\RateLimiter;

// Allow up to 5 login attempts every 60 seconds
if (!RateLimiter::attempt('login', 5, 60)) {
    echo "⛔ Too many login attempts. Please try again later.";
    exit;
}

echo "✅ Login attempt allowed.";
```

`attempt($key, $limit, $decayRate)`
  * `$key` - unique action name
  * `$limit` - max attempts allowed
  * `$decayRate` - window in seconds before reset

**Time-Based Shortcuts** <br> You can also apply limits using convenient, readable helpers:

```php
use App\Utilities\RateLimiter;

// Allow 3 attempts per minute
RateLimiter::perMinute('api_request', 3);

// Allow 100 operations per hour
RateLimiter::perHour('user_uploads', 100);

// Allow 1000 requests per day
RateLimiter::perDay('daily_report', 1000);

// Allow 5000 actions per month
RateLimiter::perMonth('monthly_summary', 5000);
```

Each helper internally calls `attempt()` with the appropriate decay window.

### How It Works
1. The limiter identifies the user by IP (via `Server::IPAddress()`).
2. It checks the cache for the remaining number of allowed attempts.
3. If none exist, it creates a new entry with the configured limit and expiration.
4. Each valid attempt **decrements the counter** until it reaches zero.
5. Once the window expires, the counter automatically resets.

---

## Server

Provides helper methods to interact with the server and request environment data.

This utility offers an abstraction layer over PHP’s `$_SERVER` global, ensuring safer access and cleaner syntax for retrieving request information.

### Features
  * Retrieves client and server IPs
  * Detects secure (HTTPS) connections 
  * Identifies AJAX requests 
  * Provides headers and environment metadata 
  * Generates a unique request ID if missing


**Usage Example:**
```php
use App\Utilities\Server;

$ip         = Server::IPAddress();
$userAgent  = Server::UserAgent();
$method     = Server::RequestMethod();
$isSecure   = Server::IsSecureConnection();
$isAjax     = Server::isAjaxRequest();
$requestId  = Server::RequestId();

echo "Client IP: {$ip}";
echo "User Agent: {$userAgent}";
```

### Available Methods

| Method                 | Description                                                                  |
| ---------------------- | ---------------------------------------------------------------------------- |
| `IPAddress()`          | Gets the client’s IP address, checking proxy headers like `X-Forwarded-For`. |
| `UserAgent()`          | Retrieves the client’s User-Agent string.                                    |
| `HostName()`           | Returns the requested host name (domain).                                    |
| `RequestMethod()`      | Gets the HTTP request method (`GET`, `POST`, etc.).                          |
| `RequestURI()`         | Gets the full request URI path.                                              |
| `Referer()`            | Returns the HTTP referer or `"No Referer"` if unavailable.                   |
| `QueryString()`        | Retrieves the request’s query string parameters.                             |
| `IsSecureConnection()` | Checks if the connection uses HTTPS.                                         |
| `ClientPort()`         | Gets the client’s port number.                                               |
| `ServerIPAddress()`    | Retrieves the server’s IP address.                                           |
| `RequestTime()`        | Returns the timestamp of the request.                                        |
| `isAjaxRequest()`      | Detects if the current request was made via AJAX.                            |
| `ContentType()`        | Gets the request’s `Content-Type` header.                                    |
| `Accept()`             | Retrieves the `Accept` header value.                                         |
| `Protocol()`           | Returns the HTTP protocol version (`HTTP/1.1`, `HTTP/2`, etc.).              |
| `RequestId()`          | Gets the `X-Request-ID` header or generates one if missing.                  |

---

## Session

Provides a simple and consistent interface for managing PHP sessions, including **flash data**, **custom handlers**, and **configurable drivers**.

**Supports:**
  * File-based sessions 
  * Database-based sessions
  * (Future) Redis-based sessions 
  * Flash messages (temporary session data)
  * Session regeneration and cleanup

**Usage Example:**
```php
use App\Utilities\Session;

// Start or configure the session
Session::configure([
    'driver' => 'file',
    'session' => [
        'lifetime' => 120,
        'path' => '/',
        'http_only' => true,
        'same_site' => 'lax',
    ],
]);

Session::start();

// Store and retrieve session data
Session::set('user', 'Roy');
echo Session::get('user'); // Outputs: Roy

// Flash message (available for one request)
Session::flash('success', 'Profile updated successfully!');

// Retrieve flash data later
$message = Session::flash('success'); // "Profile updated successfully!"
```

### Available Methods
| Method                                      | Description                                                                                                       |
| ------------------------------------------- | ----------------------------------------------------------------------------------------------------------------- |
| `configure(array $config)`                  | Configures session settings such as driver, lifetime, and cookie parameters. Supports file and database handlers. |
| `start()`                                   | Starts the session if it’s not already active.                                                                    |
| `started()`                                 | Checks whether a session is currently active.                                                                     |
| `set(string $key, mixed $value)`            | Stores a value in the session under the specified key.                                                            |
| `get(string $key, mixed $default = false)`  | Retrieves a value from the session or returns a default if not found.                                             |
| `has(string $key)`                          | Determines if a session key exists.                                                                               |
| `remove(string $key)`                       | Removes a specific key from the session.                                                                          |
| `flash(string $key, mixed $value = false)`  | Sets or retrieves temporary “flash” data that persists for one request only.                                      |
| `destroy()`                                 | Completely destroys the session and clears all data.                                                              |
| `regenerate(bool $deleteOldSession = true)` | Regenerates the session ID for improved security.                                                                 |


## Stream-Wire

Stream-Wire enables you to build dynamic, interactive PHP components without writing JavaScript.
It bridges backend and frontend communication automatically, allowing components to update and react to user interactions seamlessly — using pure **PHP**.

### Installation & Setup
1. **Publish Required Assets:** 
   ```php
   php artisan public:stream
   ```
   This publishes all necessary JavaScript and CSS files to:
   ```bash
   /public/libraries/streamdom/
   ```
2. **Include Assets in Your Layout** <br>
   In your main layout (e.g., `layout.blade.php`):
    ```html
    <script src="/libraries/streamdom/stream.js"></script>
    <link rel="stylesheet" href="/libraries/streamdom/stream.css">
    ```
    Or in your stylesheet:
    ```css
    @import "/libraries/streamdom/stream.css";
    ```
3. **Add Stream-Wire Route** <br> In your routes file:
   ```php
   App\Routes\Route::post('/api/stream-wire/{identifier}', [App\Utilities\Stream::class, 'capture']);
   ```

### Creating a Component

Generate a new component using Artisan:
```shell
php artisan make:component Counter
```

This will create:

| File                                   | Description                                       |
|----------------------------------------| ------------------------------------------------- |
| `./components/Counter.php`             | Contains the backend logic and lifecycle methods. |
| `./views/components/counter.blade.php` | Defines the frontend markup and data bindings.    |


### Component Lifecycle

Each Stream-Wire component extends the base class:

```php
use App\Utilities\Handler\Component;
```

Your component may define any of the following methods:

| Method                     | Purpose                                                                                |
| -------------------------- | -------------------------------------------------------------------------------------- |
| **init()**                 | Initializes internal state or dependencies. Called when the component is first loaded. |
| **verify(): bool**         | *(Optional)* Perform validation or pre-render checks.                                  |
| **identifier(): string**   | Must be `static`. Used by the frontend to reference this component.                    |
| **loader(): string**       | Returns temporary content shown while the component is processing.                     |
| **redirect(string $path)** | Performs an AJAX-based redirect.                                                       |
| **render(): array**        | Renders the final component view. Must return `$this->compile([...])`.                 |


### Example 1 — Simple Counter Component
`./components/Counter.php`
```php
namespace Components\Counter;

use App\Utilities\Handler\Component;

class Counter extends Component
{
    public string $name = "Robroy";

    /** @see ./views/components/counter.blade.php */
    public function render(): array
    {
        return $this->compile([
            'name' => $this->name,
        ]);
    }
}
```

`./views/components/counter.blade.php`
```bladehtml
@php
use Components\Counter;
/**
 * This file is rendered by:
 * @see Components\Counter::render()
 */
@endphp

<div class="max-w-sm mx-auto mt-10 p-6 bg-white rounded-2xl shadow-md space-y-4">
    <h2 class="text-xl font-semibold text-gray-800">
        Your Name is <span class="text-blue-600">{{ $name }}</span>
    </h2>

    <!-- Simple two-way binding -->
    <input
        type="text"
        wire:model="name"
        wire:keydown.enter.clear="render"
        placeholder="Enter your name"
        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
    />

    <!-- Using `this.value` as parameter -->
    <input
        type="text"
        wire:model="name"
        wire:keydown.enter.clear="method(this.value)"
        placeholder="Enter your name again"
        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
    />

    <!-- Re-render the component -->
    <button
        type="button"
        wire:click="render"
        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 rounded-lg transition-colors duration-200">
        Submit
    </button>

    <!-- Advanced: Execute directly using helper -->
    <button
        type="button"
        wire:click="{!! stream()->execute([Counter::class, 'render']) !!}"
        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 rounded-lg transition-colors duration-200">
        Submit 2
    </button>

    <!-- Cross-component action -->
    <button
        type="button"
        wire:click="{!! stream()->target([Counter2::class, 'render']) !!}"
        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 rounded-lg transition-colors duration-200">
        Trigger Another Component
    </button>
</div>
```

### Usage in Blade or PHP Views
To render a component:
```php
$component = Components\Counter::class;
$init_params = ['name' => 'Robroy'];
$asynchronous = true;

echo stream($component, $init_params, $asynchronous);
```

#### Parameters

| Parameter         | Type     | Description                                                                                                                                                                                                                                                              |
| ----------------- | -------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| **$component**    | `string` | Fully-qualified class name of the component.                                                                                                                                                                                                                             |
| **$init_params**  | `array`  | Optional initial data to assign to the component’s public properties during initialization.                                                                                                                                                                              |
| **$asynchronous** | `bool`   | Determines whether the component should load asynchronously. When set to `true`, a loader or skeleton placeholder is rendered immediately, and the actual component is loaded in the background—allowing other components or page processes to continue without waiting. |


### Available Frontend Directives

| Directive                | Description                                                   |
| ------------------------ | ------------------------------------------------------------- |
| `wire:click="method()"`  | Calls a backend method when clicked.                          |
| `wire:model="property"`  | Binds an input field to a backend property (two-way binding). |
| `wire:submit="method()"` | Handles form submissions via AJAX.                            |
| `wire:loading`           | Displays content while waiting for backend response.          |


#### Key-based Directives

| Directive                | Description                    |
| ------------------------ | ------------------------------ |
| `wire:keydown.enter`     | Triggers on Enter key press.   |
| `wire:keydown.escape`    | Triggers on Escape key press.  |
| `wire:keydown.backspace` | Triggers on Backspace.         |
| `wire:keydown.tab`       | Triggers on Tab.               |
| `wire:keydown.delete`    | Triggers on Delete.            |
| `wire:keydown.keypress`  | Fires when any key is pressed. |


#### Each of the above supports chained modifiers:
| Modifier                                     | Description                               |
|----------------------------------------------| ----------------------------------------- |
| `.clear`                                     | Clears the input value after execution.   |
| `.refresh`                                   | Forces a full component refresh.          |
| `.rebind`                                    | Re-runs scripts inside the rendered HTML. |
| `.prevent`                                   | Equivalent to `e.preventDefault()`.       |
| `.100ms`, `.300ms`, `.500ms`, +... `1000ms`. | Adds delay before executing the action.   |

#### Loader Directive
The `wire:loader` directive executes before a request is made.

**Example:**
```html
<button
    wire:click="save"
    wire:loader.classList.add="opacity-50 cursor-not-allowed"
    wire:loader.classList.remove="opacity-100"
    wire:loader.style="background: gray;"
>
    Saving...
</button>
```

**Modifiers**

| Modifier                      | Description                          |
| ----------------------------- | ------------------------------------ |
| `.classList.add="..."`        | Adds a class while loading.          |
| `.classList.add.retain="..."` | Retains class after completion.      |
| `.classList.remove="..."`     | Removes a class while loading.       |
| `.style="..."`                | Adds inline style during request.    |
| `.style.retain="..."`         | Keeps style after request completes. |
| `.attr="..."`                 | Adds an attribute during request.    |
| `.attr.retain="..."`          | Retains the attribute after request. |


### Advanced Features
**Execute Methods Manually**

You can call backend methods directly using the helper:
```php
stream()->execute([Counter::class, 'render']);
```

**Target Other Components**
To execute a method on a different component:
```bladehtml
wire:click="{!! stream()->target([Counter2::class, 'render']) !!}"
```

### Behavior Summary
Stream-Wire automatically synchronizes backend data and frontend state:

* No page reloads.
* No manual JavaScript.
* Automatic partial updates.
* Built-in loading feedback.

Write expressive PHP, and let Stream-Wire handle the browser updates.