<?php

declare(strict_types=1);

namespace App\Install;

class ConfigurationWriter
{
    private string $envPath;

    public function __construct()
    {
        $this->envPath = base_path('.env');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Public API
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Write or update key=value pairs in .env.
     *
     * Rules:
     * - Existing keys are updated in-place (preserving line order).
     * - Missing keys are appended at the end.
     * - Values are quoted when they contain spaces, quotes, # or are empty.
     * - Writes atomically with LOCK_EX.
     *
     * @param  array<string, string|int|bool>  $values
     */
    public function write(array $values): bool
    {
        // Normalize all values to strings
        $normalized = [];
        foreach ($values as $k => $v) {
            $normalized[$k] = (string) ($v === true ? 'true' : ($v === false ? 'false' : $v));
        }

        $content = file_exists($this->envPath) ? (file_get_contents($this->envPath) ?: '') : '';
        $lines = $content !== '' ? explode("\n", rtrim($content, "\n")) : [];
        $found = [];

        foreach ($lines as &$line) {
            // Skip blank lines and comments
            $trimmed = ltrim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            foreach ($normalized as $key => $value) {
                if (isset($found[$key])) {
                    continue;
                }
                // Match KEY= at start of (possibly leading-space-free) key
                if (preg_match('/^'.preg_quote($key, '/').'\s*=/', $trimmed)) {
                    $line = $key.'='.$this->quote($value);
                    $found[$key] = true;
                    break;
                }
            }
        }
        unset($line);

        // Append keys that didn't exist yet
        foreach ($normalized as $key => $value) {
            if (! isset($found[$key])) {
                $lines[] = $key.'='.$this->quote($value);
            }
        }

        $newContent = implode("\n", $lines)."\n";
        $result = file_put_contents($this->envPath, $newContent, LOCK_EX);

        return $result !== false;
    }

    /**
     * Read a single value from .env.
     */
    public function read(string $key): ?string
    {
        if (! file_exists($this->envPath)) {
            return null;
        }

        $content = file_get_contents($this->envPath) ?: '';
        if (preg_match('/^'.preg_quote($key, '/').'\s*=(.*)$/m', $content, $m)) {
            return $this->unquote(trim($m[1]));
        }

        return null;
    }

    /**
     * Check whether the .env file is writable (or can be created).
     */
    public function isWritable(): bool
    {
        if (file_exists($this->envPath)) {
            return is_writable($this->envPath);
        }

        return is_writable(dirname($this->envPath));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Value formatting
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Quote a value if it needs quoting for .env format.
     *
     * Must quote when the value:
     * - is empty
     * - contains whitespace
     * - contains a double quote
     * - contains a # (would be parsed as inline comment)
     * - starts with a single quote
     */
    private function quote(string $value): string
    {
        if ($value === '' || preg_match('/[\s"#]/', $value) || str_starts_with($value, "'")) {
            // Escape backslashes, then double-quote the value
            $escaped = str_replace(['\\', '"'], ['\\\\', '\\"'], $value);

            return '"'.$escaped.'"';
        }

        return $value;
    }

    /**
     * Strip surrounding quotes from a value read out of .env.
     */
    private function unquote(string $value): string
    {
        if (strlen($value) >= 2) {
            if ($value[0] === '"' && $value[-1] === '"') {
                return stripslashes(substr($value, 1, -1));
            }
            if ($value[0] === "'" && $value[-1] === "'") {
                return substr($value, 1, -1);
            }
        }

        return $value;
    }
}
