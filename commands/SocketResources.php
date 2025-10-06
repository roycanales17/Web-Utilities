<?php

	namespace Commands;

	use App\Console\Command;

	class SocketResources extends Command
	{
		protected string $signature = 'make:socket';
		protected string $description = 'Generates ready-made Node Socket.IO resources with Docker support.';

		public function handle(): void
		{
			$source = __DIR__ . '/../../resources/node';
			$destination = base_path('node');
			$publicSocket = base_path('public/socket.js');

			// --- Validate source
			if (!is_dir($source)) {
				$this->error("❌ Source directory not found: {$source}");
				return;
			}

			// --- Ensure destination exists
			if (!is_dir($destination)) {
				mkdir($destination, 0755, true);
				$this->info("📁 Created directory: {$destination}");
			}

			// --- Copy files recursively
			$this->copyRecursive($source, $destination);
			$this->info("✅ Node socket resources copied successfully.");

			// --- Create socket.js in /public for browser connection
			$this->createFrontendSocketScript($publicSocket);

			// --- Output Docker instructions
			$this->info("🚀 Add the following service block to your docker-compose.yml:");
			$this->info($this->getDockerInstruction());
			$this->info("✅ Setup complete! You can now build and run your Node socket service with:");
			$this->info('    docker compose up -d node');
		}

		/**
		 * Recursively copy files and folders from source to destination.
		 */
		private function copyRecursive(string $source, string $destination): void
		{
			$dir = opendir($source);

			if (!is_dir($destination)) {
				mkdir($destination, 0755, true);
			}

			while (($file = readdir($dir)) !== false) {
				if ($file === '.' || $file === '..') {
					continue;
				}

				$srcFile = $source . DIRECTORY_SEPARATOR . $file;
				$destFile = $destination . DIRECTORY_SEPARATOR . $file;

				if (is_dir($srcFile)) {
					$this->copyRecursive($srcFile, $destFile);
				} else {
					copy($srcFile, $destFile);
				}
			}

			closedir($dir);
		}

		/**
		 * Creates the public/socket.js file for frontend testing.
		 */
		private function createFrontendSocketScript(string $path): void
		{
			$content = <<<'JS'
(function () {
    const script = document.createElement("script");
    script.src = "https://cdn.socket.io/4.7.5/socket.io.min.js";
    script.onload = () => {
        const socket = io("http://localhost:3000");

        socket.on("connect", () => {
            console.log("✅ Connected:", socket.id);
            socket.emit("message", "Hello from frontend");
        });

        socket.on("message", (msg) => {
            console.log("💬 From server:", msg);
        });
    };
    document.head.appendChild(script);
})();
JS;

			// Create /public folder if missing
			$dir = dirname($path);
			if (!is_dir($dir)) {
				mkdir($dir, 0755, true);
			}

			file_put_contents($path, $content);
			$this->info("🧩 Frontend socket file created: {$path}");
		}

		/**
		 * Returns the Docker Compose YAML block to display in console.
		 */
		private function getDockerInstruction(): string
		{
			return <<<YAML
  # --------------------------
  # Node Socket
  # --------------------------
  node:
    build:
      context: ./node
      dockerfile: Dockerfile
    container_name: socket_container
    restart: unless-stopped
    ports:
      - "\${SOCKET_PORT:-3000}:3000"
    volumes:
      - "./logs/node:/usr/src/app/logs"
    networks:
      - project_network
    env_file:
      - .env
YAML;
		}
	}