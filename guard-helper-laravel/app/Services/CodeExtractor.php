<?php

namespace App\Services;

class CodeExtractor
{
    
    public static function extract(?string $body, ?string $expression, bool $enableHeuristic, string $grabbingStrategy): ?array
    {
        if (empty($body)) {
            return null;
        }

        
        $bodyClean = trim(preg_replace('/\s+/', ' ', $body));

        
        if (!$enableHeuristic) {
            $code = self::extractRegex($bodyClean, $expression);
            return $code !== null ? ['code' => $code, 'pattern' => $expression] : null;
        }

        
        if ($grabbingStrategy === 'regex_first') {
            $code = self::extractRegex($bodyClean, $expression);
            if ($code !== null) {
                return ['code' => $code, 'pattern' => $expression];
            }
            $code = self::extractHeuristic($bodyClean);
            return $code !== null ? ['code' => $code, 'pattern' => 'Heuristics'] : null;
        }

        
        $code = self::extractHeuristic($bodyClean);
        if ($code !== null) {
            return ['code' => $code, 'pattern' => 'Heuristics'];
        }
        $code = self::extractRegex($bodyClean, $expression);
        return $code !== null ? ['code' => $code, 'pattern' => $expression] : null;
    }

    
    private static function extractRegex(string $body, ?string $expression): ?string
    {
        if (empty($expression)) {
            return null;
        }

        
        set_error_handler(function () {
            return true;
        });
        $matches = [];
        $result = preg_match($expression, $body, $matches);
        restore_error_handler();

        if ($result && !empty($matches)) {
            return isset($matches[1]) ? $matches[1] : $matches[0];
        }

        return null;
    }

    
    private static function extractHeuristic(string $body): ?string
    {
        $cleanBody = preg_replace('/<a\s+(?:[^>]*?\s+)?href=([\'"])(.*?)\1/i', '', $body);
        $cleanBody = preg_replace('/https?:\/\/\S+/i', '', $cleanBody);
        $cleanBody = strip_tags($cleanBody);
        $cleanBody = html_entity_decode($cleanBody, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        preg_match_all('/\b[a-zA-Z0-9-]{4,8}\b/', $cleanBody, $matches);
        $tokens = array_unique($matches[0] ?? []);

        $candidates = [];

        $keywords = [
            'code',
            'verification',
            'security',
            'guard',
            'pin',
            'passcode',
            'authorization',
            'auth',
            'confirm',
            'login',
            'one-time',
            'otp',
            'kod',
            'dogrulama',
            'onay',
            'sifre',
            'guvenlik',
            'giris',
            'tek kullanımlık',
            'tek kullanımlik',
            'pass',
            'access'
        ];

        foreach ($tokens as $token) {
            if (preg_match('/^[a-z]+$/', $token)) {
                continue;
            }

            if (preg_match('/^20[0-9]{2}$/', $token)) {
                continue;
            }

            if (in_array(strtolower($token), ['steam', 'epic', 'games', 'disney', 'netflix', 'ubi', 'ubisoft', 'riot', 'html', 'http', 'https', 'email', 'user'])) {
                continue;
            }

            $score = 0;
            $length = strlen($token);

            if (ctype_digit($token)) {
                if ($length === 6) {
                    $score += 60;
                } elseif ($length === 5 || $length === 8) {
                    $score += 45;
                } else {
                    $score += 30;
                }
            } else {
                $hasDigit = preg_match('/[0-9]/', $token);
                $hasLower = preg_match('/[a-z]/', $token);
                $hasUpper = preg_match('/[A-Z]/', $token);

                if ($hasUpper && !$hasLower) {
                    if ($hasDigit) {
                        $score += 50;
                    } else {
                        $score += 20;
                    }
                } elseif ($hasDigit) {
                    $score += 25;
                } else {
                    continue;
                }
            }

            $pos = strpos($cleanBody, $token);
            if ($pos !== false) {
                $start = max(0, $pos - 100);
                $end = min(strlen($cleanBody), $pos + strlen($token) + 100);
                $context = substr($cleanBody, $start, $end - $start);
                $contextLower = strtolower($context);

                $keywordMatchCount = 0;
                foreach ($keywords as $keyword) {
                    if (str_contains($contextLower, $keyword)) {
                        $keywordMatchCount++;
                        $keywordPos = strpos($contextLower, $keyword);
                        $tokenPosInContext = $pos - $start;
                        $distance = abs($keywordPos - $tokenPosInContext);

                        if ($distance < 30) {
                            $score += 40;
                        } elseif ($distance < 60) {
                            $score += 25;
                        } else {
                            $score += 15;
                        }
                    }
                }

                if ($keywordMatchCount > 0) {
                    $score += 30;
                }
            }

            $candidates[$token] = $score;
        }

        if (empty($candidates)) {
            return null;
        }

        arsort($candidates);
        reset($candidates);
        $bestToken = key($candidates);
        $bestScore = current($candidates);

        if ($bestScore >= 40) {
            return $bestToken;
        }

        return null;
    }
}
