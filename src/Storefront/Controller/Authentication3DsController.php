<?php 

namespace Checkoutcom\Storefront\Controller;

use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Checkoutcom\Service\PaymentService;
use Checkoutcom\Helper\CkoLogger;
use Checkoutcom\Helper\LogFields;

/**
 * @RouteScope(scopes={"storefront"})
 */
class Authentication3DsController extends StorefrontController
{
    public $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }
    
    /**
     * @Route("/cko/success3ds", name="cko.success3ds", methods={"GET"})
     */
    public function success3ds(Request $request, Context $context)
    {   
        // get success url in session
        $session = $this->container->get('session');
        $url = $session->get('3dsRedirection')
            . '&state='
            . PaymentService::PAYMENT_SUCCESS
            . '&msg='
            . PaymentService::PAYMENT_SUCCESS;

        // verify payment with the session id
        if (isset($_GET['cko-session-id'])) {
            $ckoSessionID = $_GET['cko-session-id'];
            $paymentResponse = $this->paymentService->checkPayment($ckoSessionID);
            
            // if payment approved, redirect to the success url
            if ($paymentResponse['state'] == 'APPROVED' ) {
                header("Location: $url", true, 301);
                exit();
            }
        } else {
            $msg ='No cko session id found';
            CkoLogger::log()->Error(
                $msg,
                [
                    LogFields::MESSAGE => $msg,
                    LogFields::TYPE => "checkout.verify.payment",
                ]
            );

            throw new \RuntimeException($msg);
        }
    }
     /**
     * @Route("/cko/fail3ds", name="cko.fail3ds", methods={"GET"})
     */
    public function fail3ds(Request $request, Context $context)
    {  
        // get fail url in session
        $session = $this->container->get('session');
        $url = $session->get('3dsRedirection')
            . '&state='
            . PaymentService::PAYMENT_ERROR
            . '&msg='
            . PaymentService::PAYMENT_ERROR;

        header("Location: $url", true, 301);
        exit(); 
    }
}