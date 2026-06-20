<?php

namespace App\Infrastructure\Imap\Services;

use App\Models\ImapAccount;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Webklex\PHPIMAP\ClientManager;

class ImapService
{
    private function getClient(array $config)
    {
        $encryption = $config['encryption'] === 'none' ? null : $config['encryption'];

        $manager = new ClientManager();
        return $manager->make([
            'host' => $config['host'],
            'port' => $config['port'],
            'encryption' => $encryption,
            'validate_cert' => false, 
            'username' => $config['email'],
            'password' => $config['password'],
            'protocol' => 'imap'
        ]);
    }

    public function testConnection(array $config): array
    {
        try {
            $client = $this->getClient($config);
            $client->connect();
            $client->disconnect();
            return [
                'success' => true,
                'message' => 'IMAP connection successful.'
            ];
        } catch (Exception $e) {
            Log::error('IMAP Test Connection Failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'IMAP Connection Failed: ' . $e->getMessage()
            ];
        }
    }

    public function getRecentEmailsFrom(string $forEmail, string $sender, int $limit = 5): array
    {
        $account = ImapAccount::where('email', $forEmail)->first();
        if (!$account) {
            return [
                'success' => false,
                'message' => "IMAP account '{$forEmail}' not found in database."
            ];
        }

        if (!$account->is_active) {
            return [
                'success' => false,
                'message' => "IMAP account '{$forEmail}' is disabled."
            ];
        }

        try {
            $client = $this->getClient([
                'host' => $account->host,
                'port' => $account->port,
                'encryption' => $account->encryption,
                'email' => $account->email,
                'password' => $account->password, 
            ]);

            $client->connect();

            $folder = $client->getFolder('INBOX');
            if (!$folder) {
                
                $folders = $client->getFolders();
                foreach ($folders as $f) {
                    if (strtolower($f->name) === 'inbox') {
                        $folder = $f;
                        break;
                    }
                }
            }

            if (!$folder) {
                $client->disconnect();
                return [
                    'success' => false,
                    'message' => 'INBOX folder not found.'
                ];
            }

            
            $messages = $folder->query()
                ->from($sender)
                ->limit($limit)
                ->since(now()->format('d-M-Y'))
                ->get();

            $emails = [];
            foreach ($messages as $message) {
                $body = '';
                if ($message->hasTextBody()) {
                    $body = $message->getTextBody();
                } elseif ($message->hasHTMLBody()) {
                    $html = $message->getHTMLBody();
                    
                    $html = preg_replace('#<(style|script)[^>]*?>.*?</\1>#is', '', $html);
                    $body = html_entity_decode(strip_tags($html));
                }

                if ($body !== '') {
                    $body = trim(preg_replace('/\s+/', ' ', $body));
                }

                $msgDate = $message->getDate();
                $formattedDate = null;
                if ($msgDate instanceof Carbon) {
                    $formattedDate = $msgDate->toIso8601String();
                } elseif (is_array($msgDate) && !empty($msgDate)) {
                    $firstDate = reset($msgDate);
                    if ($firstDate instanceof Carbon) {
                        $formattedDate = $firstDate->toIso8601String();
                    } else {
                        $formattedDate = Carbon::parse(strval($firstDate))->toIso8601String();
                    }
                } elseif ($msgDate) {
                    $formattedDate = Carbon::parse(strval($msgDate))->toIso8601String();
                }

                $emails[] = [
                    'id' => $message->getUid(),
                    'body' => $body,
                    'date' => $formattedDate,
                    'subject' => $message->getSubject()
                ];
            }

            $client->disconnect();

            
            usort($emails, function ($a, $b) {
                return strcmp($b['date'] ?? '', $a['date'] ?? '');
            });

            return [
                'success' => true,
                'messages' => $emails
            ];

        } catch (Exception $e) {
            Log::error("IMAP Fetch Failed for {$forEmail}: " . $e->getMessage());
            \App\Models\Notification::create([
                'type' => 'connection_error',
                'title' => 'IMAP Query Failed: ' . $forEmail,
                'message' => 'Failed to connect/fetch from IMAP server for ' . $forEmail . ': ' . $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => 'IMAP Fetch Failed: ' . $e->getMessage()
            ];
        }
    }

    public function findCodeInLatestEmail(
        string $forEmail,
        string $sender,
        ?string $expression,
        ?string $subject,
        bool $enableHeuristic = false,
        string $grabbingStrategy = 'heuristic_first'
    ): array {
        $result = $this->getRecentEmailsFrom($forEmail, $sender, 10);
        if (!$result['success']) {
            return $result;
        }

        $messages = $result['messages'];
        if (empty($messages)) {
            return [
                'success' => false,
                'message' => 'No security code was found. Please request a new code on the platform.'
            ];
        }

        foreach ($messages as $msg) {
            $emailBody = $msg['body'];

            $extractResult = \App\Services\CodeExtractor::extract($emailBody, $expression, $enableHeuristic, $grabbingStrategy);
            if ($extractResult !== null) {
                return [
                    'success' => true,
                    'data' => $extractResult['code'],
                    'date' => $msg['date'],
                    'grab_pattern' => $extractResult['pattern']
                ];
            }
        }

        return [
            'success' => false,
            'message' => 'Code not found in any recent emails.'
        ];
    }
}
