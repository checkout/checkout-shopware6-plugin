<?php 

namespace Checkoutcom\Storefront\Controller;

use Checkoutcom\Helper\Utilities;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Checkoutcom\Service\PaymentService;

class CaptureController extends StorefrontController
{

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route("/api/v{version}/cko/capture", defaults={"auth_enabled"=true}, name="cko.api.capture", methods={"POST"})
     *
     */
    public function capture(Request $request): JsonResponse
    {
        $param = [];
        $param["payment_id"] = $request->get('payment_id');
        $param["payment_method"] = $request->get('payment_method');

        if(!is_null($request->get('amount'))) {
            $amount = $request->get('amount');
            $currency = $request->get('currency');

            //Format amount in cents
            $amountCents= Utilities::fixAmount($amount,$currency);
            
            $param["amount"] = $amountCents;
        }

        // Send capture request
        $captureResponse = $param["payment_method"] == "klarna" ? $this->paymentService->klarnaCapture($param) : $this->paymentService->capture($param);

        return new JsonResponse([
            "statusCode" => $captureResponse['statusCode'],
            "state" => $captureResponse['state'],
            "message" => isset($captureResponse['message']) ? $captureResponse['message'] : PaymentService::PAYMENT_SUCCESS
        ]);
    }
}