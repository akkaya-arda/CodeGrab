<?php

namespace Tests\Feature;

use Tests\TestCase;

class SteamGuardRegexTest extends TestCase
{
    private string $regex = '/(?i:Steam Guard|Guard kodu|Guard code).{1,150}?\b([A-Z0-9]{5})\b/su';

    public function test_matches_english_steam_guard_email(): void
    {
        $body = "Hello user,\n\nHere is the Steam Guard code you need to login to your account:\n\nRD72K\n\nIf you did not request this, please change your password.\n\nValve Corporation\nPO Box 1688\nBellevue, WA 98009";

        preg_match($this->regex, $body, $matches);

        $this->assertNotEmpty($matches);
        $code = isset($matches[1]) ? $matches[1] : $matches[0];
        $this->assertEquals('RD72K', $code);
    }

    public function test_matches_turkish_steam_guard_email(): void
    {
        $body = "Merhaba user,\n\nSteam hesabınız için Steam Guard kodu: 7D98K\n\nValve Corporation\nPO Box 1688\nBellevue, WA 98009";

        preg_match($this->regex, $body, $matches);

        $this->assertNotEmpty($matches);
        $code = isset($matches[1]) ? $matches[1] : $matches[0];
        $this->assertEquals('7D98K', $code);
    }

    public function test_does_not_match_zip_code_in_signature_if_no_guard_code(): void
    {
        // For example, a receipt email that mentions "Steam Guard is enabled" but does not contain a code in the text,
        // or a newsletter that has "Steam" in it.
        $body = "This is a purchase receipt from Steam.\n\nThank you for your purchase!\n\nValve Corporation\nPO Box 1688\nBellevue, WA 98009";

        preg_match($this->regex, $body, $matches);

        // Since the word "Steam" is followed by Bellevue zip code after a long distance (more than 150 characters),
        // it shouldn't match.
        $this->assertEmpty($matches);
    }

    public function test_does_not_extract_turkish_word_substring_like_aykur_from_caykur(): void
    {
        $body = "Giriş yapmaya çalışan hesap adı: ÇAYKUR.\n\nSteam hesabınız için Steam Guard kodu: G78K9\n\nValve Corporation\nPO Box 1688\nBellevue, WA 98009";

        preg_match($this->regex, $body, $matches);

        $this->assertNotEmpty($matches);
        $code = isset($matches[1]) ? $matches[1] : $matches[0];
        // It should match the actual Steam Guard code, not the AYKUR substring of ÇAYKUR
        $this->assertEquals('G78K9', $code);
    }
}
