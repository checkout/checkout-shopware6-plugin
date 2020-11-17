<?php 

namespace Checkoutcom\Storefront\Controller;

use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Context;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Checkoutcom\Service\PaymentService;

use Checkoutcom\Helper\Utilities;
/**
 * @RouteScope(scopes={"storefront"})
 */
class RedirectionController extends StorefrontController
{

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }
    
    /**
     * @Route("/cko/successRedirection", name="cko.successRedirection", methods={"GET"})
     */
    public function successRedirect(Request $request, Context $context)
    {

        // get success url in session
        $session = $this->container->get('session');
        $url = $session->get('Redirection')
            . '&state='
            . PaymentService::PAYMENT_SUCCESS
            . '&msg='
            . PaymentService::PAYMENT_SUCCESS;
        
        $isPaymentValid = $this->checkResponse();

        // if payment approved, redirect to the success url
        if ($isPaymentValid['state'] == 'APPROVED' ) {
            header("Location: $url", true, 301);
            exit();
        }
    }

     /**
     * @Route("/cko/failRedirection", name="cko.failRedirection", methods={"GET"})
     */
    public function failRedirect(Request $request, Context $context)
    {  
        // get fail url in session
        $session = $this->container->get('session');
        $url = $session->get('Redirection')
            . '&state='
            . PaymentService::PAYMENT_ERROR
            . '&msg='
            . PaymentService::PAYMENT_ERROR;

        header("Location: $url", true, 301);
        exit(); 
    }
    /**
     * check the response
     */
    public function checkResponse() {

        if (isset($_GET['cko-session-id'])) {
            $ckoSessionID = $_GET['cko-session-id'];
            $paymentResponse = $this->paymentService->checkPayment($ckoSessionID);
        }
        return $paymentResponse;
    }
}