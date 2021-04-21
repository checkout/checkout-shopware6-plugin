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
    public $paymentService;

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
        $param = [];
        $param["payment_id"] = $request->get('payment_id');
        $param["payment_method"] = $request->get('payment_method');

        // Send void request
        $voidResponse = $param["payment_method"] === "Klarna" ? $this->paymentService->klarnaVoid($param) : $this->paymentService->void($param);

        return new JsonResponse([
            "statusCode" => $voidResponse['statusCode'],
            "state" => $voidResponse['state'],
            "message" => isset($voidResponse['message']) ? $voidResponse['message'] : PaymentService::PAYMENT_SUCCESS
        ]);
    }
}