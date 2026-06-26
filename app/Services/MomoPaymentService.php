<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class MomoPaymentService
{
    public function createPayment(Order $order): array
    {
        $this->ensureConfigured();

        $amount = (int) $order->total;
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'payment_method' => 'Số tiền thanh toán MoMo không hợp lệ.',
            ]);
        }

        $partnerCode = (string) config('services.momo.partner_code');
        $accessKey = (string) config('services.momo.access_key');
        $requestType = (string) config('services.momo.request_type', 'payWithMethod');
        $requestId = $order->code;
        $orderId = $order->code;
        $orderInfo = 'Thanh toan don hang ' . $order->code . ' tai The Gioi Trai Cay';
        $redirectUrl = route('checkout.momo.return', [
            'code' => $order->code,
            'token' => $order->public_token,
        ]);
        $ipnUrl = route('checkout.momo.ipn');
        $extraData = base64_encode(json_encode([
            'order_code' => $order->code,
            'user_id' => $order->user_id,
        ]));

        $signatureFields = [
            'accessKey' => $accessKey,
            'amount' => $amount,
            'extraData' => $extraData,
            'ipnUrl' => $ipnUrl,
            'orderId' => $orderId,
            'orderInfo' => $orderInfo,
            'partnerCode' => $partnerCode,
            'redirectUrl' => $redirectUrl,
            'requestId' => $requestId,
            'requestType' => $requestType,
        ];

        $payload = [
            'partnerCode' => $partnerCode,
            'partnerName' => 'The Gioi Trai Cay',
            'storeId' => 'thegioitraicay',
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $orderId,
            'orderInfo' => $orderInfo,
            'redirectUrl' => $redirectUrl,
            'ipnUrl' => $ipnUrl,
            'lang' => 'vi',
            'requestType' => $requestType,
            'autoCapture' => true,
            'extraData' => $extraData,
            'signature' => $this->sign($signatureFields),
            'userInfo' => [
                'name' => $order->customer_name,
                'phoneNumber' => $order->customer_phone,
                'email' => $order->customer_email,
            ],
        ];

        $response = Http::timeout(30)
            ->asJson()
            ->post((string) config('services.momo.endpoint'), $payload);

        if (!$response->successful()) {
            throw ValidationException::withMessages([
                'payment_method' => 'Không kết nối được MoMo sandbox. Vui lòng thử lại.',
            ]);
        }

        $data = $response->json();
        if (!is_array($data) || (int) ($data['resultCode'] ?? -1) !== 0 || empty($data['payUrl'])) {
            throw ValidationException::withMessages([
                'payment_method' => $data['message'] ?? 'MoMo chưa tạo được liên kết thanh toán.',
            ]);
        }

        return $data;
    }

    public function verifyResult(array $payload): bool
    {
        $this->ensureConfigured();

        if (empty($payload['signature'])) {
            return false;
        }

        $signatureFields = [
            'accessKey' => (string) config('services.momo.access_key'),
            'amount' => $payload['amount'] ?? '',
            'extraData' => $payload['extraData'] ?? '',
            'message' => $payload['message'] ?? '',
            'orderId' => $payload['orderId'] ?? '',
            'orderInfo' => $payload['orderInfo'] ?? '',
            'orderType' => $payload['orderType'] ?? '',
            'partnerCode' => $payload['partnerCode'] ?? '',
            'payType' => $payload['payType'] ?? '',
            'requestId' => $payload['requestId'] ?? '',
            'responseTime' => $payload['responseTime'] ?? '',
            'resultCode' => $payload['resultCode'] ?? '',
            'transId' => $payload['transId'] ?? '',
        ];

        return hash_equals(
            strtolower((string) $payload['signature']),
            strtolower($this->sign($signatureFields))
        );
    }

    private function ensureConfigured(): void
    {
        if (
            !config('services.momo.partner_code')
            || !config('services.momo.access_key')
            || !config('services.momo.secret_key')
        ) {
            throw ValidationException::withMessages([
                'payment_method' => 'Chưa cấu hình MoMo sandbox trong file .env.',
            ]);
        }
    }

    private function sign(array $fields): string
    {
        $rawHash = collect($fields)
            ->map(function ($value, $key) {
                return $key . '=' . $value;
            })
            ->implode('&');

        return hash_hmac('sha256', $rawHash, (string) config('services.momo.secret_key'));
    }
}
