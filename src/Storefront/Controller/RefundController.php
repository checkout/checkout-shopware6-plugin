<?php 

namespace Checkoutcom\Storefront\Controller;

use Checkoutcom\Helper\Utilities;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Checkoutcom\Service\PaymentService;

class RefundController extends StorefrontController
{

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route("/api/v{version}/cko/refund", defaults={"auth_enabled"=true}, name="cko.api.refund", methods={"POST"})
     *
     */
    public function refund(Request $request): JsonResponse
    {
        $param = [];
        $param["payment_id"] = $request->get('payment_id');

        if(!is_null($request->get('amount'))) {
            $amount = $request->get('amount');
            $currency = $request->get('currency');

            //Format amount in cents
            $amountCents= Utilities::fixAmount($amount,$currency);
            
            $param["amount"] = $amountCents;
        }

        // Send refund request
        $refundResponse = $this->paymentService->refund($param);

        return new JsonResponse([
            "statusCode" => $refundResponse['statusCode'],
            "state" => $refundResponse['state'],
            "message" => isset($refundResponse['message']) ? $refundResponse['message'] : PaymentService::PAYMENT_SUCCESS
        ]);
    }
}