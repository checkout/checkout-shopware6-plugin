<?php 

namespace Checkoutcom\Storefront\Controller;

use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Checkoutcom\Service\PaymentService;

class VoidController extends StorefrontController
{

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route("/api/v{version}/cko/void", defaults={"auth_enabled"=true}, name="cko.api.void", methods={"POST"})
     *
     */
    public function void(Request $request): JsonResponse
    {
        $paymentId = $request->get('payment_id');
        $paymentMethod = $request->get('payment_method');

        // Send void request
        $voidResponse = $this->paymentService->void($paymentId,$paymentMethod);

        return new JsonResponse([
            "statusCode" => $voidResponse['statusCode'],
            "state" => $voidResponse['state'],
            "message" => isset($voidResponse['message']) ? $voidResponse['message'] : PaymentService::PAYMENT_SUCCESS
        ]);
    }
}