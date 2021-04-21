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
use Symfony\Component\HttpFoundation\Response;

use Checkoutcom\Helper\Utilities;
/**
 * @RouteScope(scopes={"storefront"})
 */
class SepaSourceController extends StorefrontController
{
    public $paymentService;
    
    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }
    
    /**
     * @Route("/cko/getsource/{iban}", name="cko.getsource", methods={"GET"})
     * 
     * @param string $iban
     * 
     */
    public function getSourceID(string $iban): JsonResponse {   

        $session = new Session();
        $session->set('iban', $iban);
        
        return new JsonResponse([
            "statusCode" =>  "200",
            "state" => "SUCCESS"
        ]);
    }
}