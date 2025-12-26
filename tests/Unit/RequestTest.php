<?php

namespace Yousefkadah\Pelecard\Tests\Unit;

use Yousefkadah\Pelecard\Http\Request;
use Yousefkadah\Pelecard\Tests\TestCase;

class RequestTest extends TestCase
{
    /** @test */
    public function it_can_create_a_request_with_data()
    {
        $request = Request::make(['amount' => 1000, 'currency' => 'ILS']);

        $this->assertEquals(1000, $request->get('amount'));
        $this->assertEquals('ILS', $request->get('currency'));
    }

    /** @test */
    public function it_can_set_and_get_data()
    {
        $request = new Request();
        $request->set('amount', 5000);

        $this->assertEquals(5000, $request->get('amount'));
    }

    /** @test */
    public function it_converts_to_pascal_case()
    {
        $request = Request::make([
            'amount' => 1000,
            'card_number' => '4580000000000000',
            'expiry_month' => '12',
        ]);

        $formatted = $request->toPelecardFormat();

        $this->assertArrayHasKey('Amount', $formatted);
        $this->assertArrayHasKey('CardNumber', $formatted);
        $this->assertArrayHasKey('ExpiryMonth', $formatted);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $this->expectException(\Yousefkadah\Pelecard\Exceptions\ValidationException::class);

        $request = Request::make(['amount' => 1000]);
        $request->setRequiredFields(['amount', 'currency']);
        $request->validate();
    }
}
