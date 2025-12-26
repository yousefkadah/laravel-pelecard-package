<?php

namespace Yousefkadah\Pelecard\Tests\Unit;

use Yousefkadah\Pelecard\Http\Response;
use Yousefkadah\Pelecard\Tests\TestCase;

class ResponseTest extends TestCase
{
    /** @test */
    public function it_detects_successful_response(): void
    {
        $response = new Response(['StatusCode' => '000'], 200);

        $this->assertTrue($response->successful());
        $this->assertFalse($response->failed());
    }

    /** @test */
    public function it_detects_failed_response(): void
    {
        $response = new Response(['StatusCode' => '001', 'ErrorMessage' => 'Payment declined'], 200);

        $this->assertTrue($response->failed());
        $this->assertFalse($response->successful());
    }

    /** @test */
    public function it_extracts_transaction_id(): void
    {
        $response = new Response(['PelecardTransactionId' => '123456'], 200);

        $this->assertEquals('123456', $response->getTransactionId());
    }

    /** @test */
    public function it_extracts_error_message(): void
    {
        $response = new Response(['ErrorMessage' => 'Invalid card'], 200);

        $this->assertEquals('Invalid card', $response->getErrorMessage());
    }

    /** @test */
    public function it_throws_on_failed_response(): void
    {
        $this->expectException(\Yousefkadah\Pelecard\Exceptions\PaymentException::class);

        $response = new Response(['StatusCode' => '001', 'ErrorMessage' => 'Failed'], 200);
        $response->throw();
    }
}
