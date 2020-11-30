<?php

use PHPUnit\Framework\TestCase;
use Checkoutcom\Subscriber\CheckoutPageSubscriber;
use Checkoutcom\Tests\Unit\Common\MockTest;
use Checkoutcom\Tests\Unit\Common\ConstantTest;

class CheckoutPageSubscriberTest extends TestCase 
{
    public function testGetApms()
    {
        $ckoContext = MockTest::ckoContextResponse();
        $array = (array) json_decode($ckoContext, true);
    
        $value = CheckoutPageSubscriber::getApms($array);
        
        $this->assertContains($value[0], ConstantTest::APMS);
    }

    public function testGetApmsNull()
    {
        $ckoContext = MockTest::ckoContextNoApms();
        $array = (array) json_decode($ckoContext, true);

        $value = CheckoutPageSubscriber::getApms($array);
        
        $this->assertSame($value, null) ; 
    }

    public function testGetPaymentMethodCategory()
    {
        $ckoContext = MockTest::ckoContextResponse();
        $array = (array) json_decode($ckoContext, true);
    
        $apms = CheckoutPageSubscriber::getApms($array);
        $value = CheckoutPageSubscriber::getPaymentMethodCategory($apms);
            
        $this->assertContains($value['0'], ConstantTest::PAYMENT_METHOD_CATEGORY) ; 
        $this->assertContains($value['1'], ConstantTest::PAYMENT_METHOD_CATEGORY) ; 
    }
}