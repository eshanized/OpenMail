<?php

namespace App\Services;

use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\Config;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;
use Webklex\PHPIMAP\Support\Masks\AttachmentMask;
use Webklex\PHPIMAP\Support\Masks\MessageMask;

class ImapConnectionTester
{
    public function __construct(
        protected ?OutboundConnectionGuard $guard = null
    ) {
        $this->guard = $guard ?? app(OutboundConnectionGuard::class);
    }

    public function test(
        string $host,
        int $port,
        string $encryption,
        string $username,
        string $password
    ): array {
        try {
            // Enforce SSRF boundary on outbound connection sink
            $this->guard->validateTarget($host, $port, [143, 993, 1143, 9930]);

            $config = new Config([
                'default' => 'default',
                'accounts' => [
                    'default' => [
                        'host' => $host,
                        'port' => $port,
                        'encryption' => $encryption,
                        'username' => $username,
                        'password' => $password,
                        'protocol' => 'imap',
                        'timeout' => 10,
                        'validate_cert' => true,
                    ],
                ],
                'masks' => [
                    'message' => MessageMask::class,
                    'attachment' => AttachmentMask::class,
                ],
                'events' => [
                    'message' => [],
                    'folder' => [],
                ],
            ]);

            $client = new Client($config);
            $client->connect();

            $folders = $client->getFolders();
            $folderNames = $folders->map(fn ($f) => $f->path)->toArray();

            $client->disconnect();

            return [
                'success' => true,
                'folders' => $folderNames,
            ];
        } catch (\InvalidArgumentException $e) {
            return [
                'success' => false,
                'error' => 'Security validation failed: '.$e->getMessage(),
            ];
        } catch (ConnectionFailedException $e) {
            return [
                'success' => false,
                'error' => 'Could not connect to IMAP server. Please check host, port, and encryption settings.',
                'technical' => [
                    'exception' => get_class($e),
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'IMAP authentication failed. Please check your email and password.',
                'technical' => [
                    'exception' => get_class($e),
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                ],
            ];
        }
    }
}
