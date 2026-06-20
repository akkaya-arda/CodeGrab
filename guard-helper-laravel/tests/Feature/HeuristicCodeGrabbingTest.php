<?php

namespace Tests\Feature;

use App\Services\CodeExtractor;
use Tests\TestCase;

class HeuristicCodeGrabbingTest extends TestCase
{
    public function test_extracts_six_digit_numeric_code_heuristically(): void
    {
        $body = "Hi user, use this verification code to login: 849204. It expires in 10 minutes.";
        
        $result = CodeExtractor::extract(
            $body, 
            null, // No regex
            true, // enable heuristic
            'heuristic_first'
        );

        $this->assertNotNull($result);
        $this->assertEquals('849204', $result['code']);
        $this->assertEquals('Heuristics', $result['pattern']);
    }

    public function test_extracts_steam_guard_alphanumeric_code_heuristically(): void
    {
        $body = "Your Steam Guard code is WB65C. Enter it on your computer.";

        $result = CodeExtractor::extract(
            $body,
            null, // No regex
            true, // enable heuristic
            'heuristic_first'
        );

        $this->assertNotNull($result);
        $this->assertEquals('WB65C', $result['code']);
        $this->assertEquals('Heuristics', $result['pattern']);
    }

    public function test_falls_back_to_regex_when_heuristic_fails(): void
    {
        $body = "Here is your registration voucher token ab-12345.";
        $regex = '/voucher token ([a-z0-9-]{8})/su';
        
        $result = CodeExtractor::extract(
            $body,
            $regex,
            true, // enable heuristic
            'heuristic_first'
        );

        $this->assertNotNull($result);
        $this->assertEquals('ab-12345', $result['code']);
        $this->assertEquals($regex, $result['pattern']);
    }

    public function test_regex_takes_priority_under_regex_first(): void
    {
        $body = "Your confirmation code is 998877 but transaction ID is TX-4567.";
        $regex = '/ID is (TX-[0-9]{4})/su';
        
        $result = CodeExtractor::extract(
            $body,
            $regex,
            true, // enable heuristic
            'regex_first'
        );

        $this->assertNotNull($result);
        $this->assertEquals('TX-4567', $result['code']);
        $this->assertEquals($regex, $result['pattern']);
    }

    public function test_heuristic_disabled_does_not_extract_heuristically(): void
    {
        $body = "Your verification code is 123456.";
        
        $result = CodeExtractor::extract(
            $body,
            null, // No regex
            false, // disable heuristic
            'heuristic_first'
        );

        $this->assertNull($result);
    }
}
