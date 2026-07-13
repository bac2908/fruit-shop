<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MomoCallbackService
{
    private $momo;
    private $orderAutomation;
    private $customerNotifications;

    public function __construct(
        MomoPaymentService $momo,
        OrderAutomationService $orderAutomation,
        CustomerNotificationService $customerNotifications
    ) {
        $this->momo = $momo;
        $this->orderAutomation = $orderAutomation;
        $this->customerNotifications = $customerNotifications;
    }

    public function handle(array $payload, ?string $expectedOrderCode = null): array
    {
        if (!$this->momo->verifyResult($payload)) {
            Log::warning('Rejected MoMo callback with invalid signature.');

            return $this->result(false, false, 'Invalid signature');
        }

        $orderCode = trim((string) ($payload['orderId'] ?? ''));
        if ($orderCode === '' || ($expectedOrderCode !== null && !hash_equals($expectedOrderCode, $orderCode))) {
            Log::warning('Rejected MoMo callback with mismatched orderId.', [
                'expected_order_id' => $expectedOrderCode,
                'received_order_id' => $orderCode,
            ]);

            return $this->result(false, false, 'Invalid orderId');
        }

        try {
            return DB::transaction(function () use ($payload, $orderCode) {
                $order = Order::query()
                    ->where('code', $orderCode)
                    ->lockForUpdate()
                    ->first();

                if (!$order) {
                    return $this->result(false, false, 'Order not found');
                }

                $identityError = $this->validateIdentity($order, $payload);
                if ($identityError !== null) {
                    $this->appendAdminNote($order, 'MoMo callback bị từ chối: ' . $identityError);
                    Log::warning('Rejected MoMo callback identity.', [
                        'order_id' => $order->id,
                        'reason' => $identityError,
                    ]);

                    return $this->result(false, false, $identityError, $order);
                }

                if ((int) $payload['resultCode'] !== 0) {
                    $message = trim((string) ($payload['message'] ?? 'Thanh toán không thành công.'));
                    $this->appendAdminNote($order, 'MoMo: ' . $message);

                    return $this->result(true, false, $message, $order);
                }

                $transactionId = trim((string) ($payload['transId'] ?? ''));
                if ($transactionId === '' || !preg_match('/^\d+$/', $transactionId)) {
                    $this->appendAdminNote($order, 'MoMo callback bị từ chối: transId không hợp lệ.');

                    return $this->result(false, false, 'Invalid transId', $order);
                }

                if ($order->status === Order::STATUS_CANCELLED) {
                    $this->appendAdminNote($order, 'MoMo callback đến sau khi đơn đã hủy; cần đối soát giao dịch ' . $transactionId . '.');

                    return $this->result(false, false, 'Order was cancelled', $order);
                }

                if ($order->payment_status === Order::PAYMENT_STATUS_PAID) {
                    $sameTransaction = hash_equals((string) $order->momo_transaction_id, $transactionId);

                    return $this->result($sameTransaction, $sameTransaction, $sameTransaction ? 'Already processed' : 'Transaction mismatch', $order);
                }

                $transactionAlreadyUsed = Order::query()
                    ->where('momo_transaction_id', $transactionId)
                    ->where('id', '<>', $order->id)
                    ->exists();

                if ($transactionAlreadyUsed) {
                    $this->appendAdminNote($order, 'MoMo callback bị từ chối: transId đã thuộc đơn khác.');

                    return $this->result(false, false, 'Duplicate transId', $order);
                }

                $this->appendAdminNote($order, 'MoMo sandbox paid. TransId: ' . $transactionId);
                $order->forceFill([
                    'momo_transaction_id' => $transactionId,
                    'payment_status' => Order::PAYMENT_STATUS_PAID,
                    'paid_at' => now(),
                ])->save();

                $this->customerNotifications->paymentReceived($order);

                if (!$order->requiresShippingConfirmation()) {
                    $this->orderAutomation->autoConfirmAfterStockReserved(
                        $order,
                        null,
                        'Hệ thống tự động xác nhận sau khi MoMo báo thanh toán thành công.'
                    );
                }

                return $this->result(true, true, 'Payment processed', $order->refresh());
            }, 3);
        } catch (QueryException $exception) {
            Log::error('MoMo callback database conflict.', [
                'order_id' => $orderCode,
                'error' => $exception->getMessage(),
            ]);

            return $this->result(false, false, 'Database conflict');
        }
    }

    private function validateIdentity(Order $order, array $payload): ?string
    {
        if ($order->payment_method !== Order::PAYMENT_METHOD_MOMO) {
            return 'payment method không phải MoMo';
        }

        if (!hash_equals($order->code, trim((string) ($payload['orderId'] ?? '')))) {
            return 'orderId không khớp';
        }

        $requestId = trim((string) ($payload['requestId'] ?? ''));
        if ($requestId === '' || !hash_equals((string) $order->momo_request_id, $requestId)) {
            return 'requestId không khớp';
        }

        $amount = filter_var($payload['amount'] ?? null, FILTER_VALIDATE_INT);
        if ($amount === false || (int) $amount !== (int) $order->total) {
            return 'amount không khớp';
        }

        if (!hash_equals((string) config('services.momo.partner_code'), trim((string) ($payload['partnerCode'] ?? '')))) {
            return 'partnerCode không khớp';
        }

        return null;
    }

    private function appendAdminNote(Order $order, string $note): void
    {
        $existingNote = trim((string) $order->admin_note);
        $newNote = '[' . now()->format('d/m/Y H:i') . '] ' . $note;

        $order->forceFill([
            'admin_note' => $existingNote !== '' ? $existingNote . PHP_EOL . $newNote : $newNote,
        ])->save();
    }

    private function result(bool $accepted, bool $paid, string $message, ?Order $order = null): array
    {
        return compact('accepted', 'paid', 'message', 'order');
    }
}
