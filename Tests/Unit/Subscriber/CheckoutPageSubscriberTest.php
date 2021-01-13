<?php

use PHPUnit\Framework\TestCase;
use Checkoutcom\Subscriber\CheckoutPageSubscriber;
use Checkoutcom\Helper\ckoException;
use Checkoutcom\Tests\Unit\Common\MockTest;
use Checkoutcom\Tests\Unit\Common\ConstantTest;

class CheckoutPageSubscriberTest extends TestCase 
{
    public function testGetApmData()
    {
        $ckoContext = MockTest::ckoContextResponse();
        $array = (array) json_decode($ckoContext, true);
    
        $value = CheckoutPageSubscriber::getApmData($array);

        $this->assertEquals($value->apmName, ConstantTest::APMS);
        
    }

    public function testGetPaymentMethodCategory()
    {
        $ckoContext = MockTest::ckoContextResponse();
        $array = (array) json_decode($ckoContext, true);
    
        $apms = CheckoutPageSubscriber::getApmData($array);

        $value = CheckoutPageSubscriber::getPaymentMethodCategory($apms->paymentMethodAvailable);
            
        $this->assertContains($value[0], ConstantTest::PAYMENT_METHOD_CATEGORY); 
        $this->assertContains($value[1], ConstantTest::PAYMENT_METHOD_CATEGORY); 
    }

    public function testExpectException() {

        CheckoutPageSubscriber::getCkoContext("token_123", "wrong_pk", 'EUR');

        $this->expectException(ckoException::class);

    }
}