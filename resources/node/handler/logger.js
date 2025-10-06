/**
 * Custom Logger utility for structured logging.
 *
 * Logs messages to both the console and a file, with log levels and timestamps.
 * Creates the log directory if it does not exist.
 *
 * Example usage:
 *   const logger = new Logger({ level: Logger.LEVELS.INFO });
 *   logger.info("Server started");
 *   logger.warn("Cache miss");
 *   logger.error("Database connection failed");
 */

const fs = require("fs");
const path = require("path");
class Logger {
    /**
     * Available log levels.
     * Lower numbers = more verbose logging.
     */
    static LEVELS = {
        INFO: 0,
        WARN: 1,
        ERROR: 2
    };

    /**
     * Creates a new Logger instance.
     *
     * @param {Object} options - Logger configuration
     * @param {number} [options.level=Logger.LEVELS.WARN] - Minimum log level to output
     * @param {string} [options.logDir="logs"] - Directory where log files are stored
     * @param {string} [options.fileName="socket.log"] - Name of the log file
     */
    constructor(options = {}) {
        const {
            level = Logger.LEVELS.WARN,
            logDir = "logs",
            fileName = "socket.log"
        } = options;

        this.level = level;
        this.logDir = path.isAbsolute(logDir)
            ? logDir
            : path.join(process.cwd(), logDir);

        // Ensure log directory exists
        if (!fs.existsSync(this.logDir)) {
            fs.mkdirSync(this.logDir, { recursive: true });
        }

        this.logFile = path.join(this.logDir, fileName);
    }

    /**
     * Formats a log entry with timestamp, emoji, and message.
     *
     * @param {number} level - Log level (INFO, WARN, ERROR)
     * @param {string} message - Message to log
     * @returns {string} - Formatted log entry
     */
    formatLog(level, message) {
        const timestamp = new Date().toISOString();
        const emoji =
            level === Logger.LEVELS.ERROR ? "❌" :
                level === Logger.LEVELS.WARN  ? "⚠️" :
                    "ℹ️";

        return `----------------------------------\n\n${emoji} [${timestamp}]\nMessage: ${message}\n`;
    }

    /**
     * Core logging method.
     *
     * @param {number} level - Log level (INFO, WARN, ERROR)
     * @param {string} message - Message to log
     */
    log(level, message) {
        if (level < this.level) return;

        const formatted = this.formatLog(level, message);

        // Append to file
        fs.appendFileSync(this.logFile, formatted + "\n");

        // Print to console
        console.log(formatted.trim());
    }

    /**
     * Logs an informational message.
     *
     * @param {string} msg - The message to log
     */
    info(msg) {
        this.log(Logger.LEVELS.INFO, msg);
    }

    /**
     * Logs a warning message.
     *
     * @param {string} msg - The message to log
     */
    warn(msg) {
        this.log(Logger.LEVELS.WARN, msg);
    }

    /**
     * Logs an error message.
     *
     * @param {string} msg - The message to log
     */
    error(msg) {
        this.log(Logger.LEVELS.ERROR, msg);
    }
}

module.exports = Logger;
