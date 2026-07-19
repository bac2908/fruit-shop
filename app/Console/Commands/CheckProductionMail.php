<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class CheckProductionMail extends Command
{
    protected $signature = 'mail:check
        {--send= : Send one real smoke-test email to this address after validation}';

    protected $description = 'Validate production mail settings and optionally send a smoke-test email.';

    public function handle(): int
    {
        $mailer = (string) config('mail.default');
        $transport = (string) config("mail.mailers.{$mailer}.transport", $mailer);
        $fromAddress = trim((string) config('mail.from.address'));
        $fromName = trim((string) config('mail.from.name'));
        $appUrl = trim((string) config('app.url'));
        $isProduction = app()->environment('production');
        $checks = [];

        $this->check($checks, 'Mailer', $mailer !== '', $mailer ?: 'Chưa cấu hình', true);
        $this->check(
            $checks,
            'Transport production',
            ! $isProduction || ! in_array($transport, ['array', 'log'], true),
            $transport,
            $isProduction
        );
        $this->check(
            $checks,
            'Email người gửi',
            filter_var($fromAddress, FILTER_VALIDATE_EMAIL) !== false,
            $fromAddress ?: 'Chưa cấu hình',
            true
        );
        $this->check(
            $checks,
            'Tên người gửi',
            $fromName !== '' && strtolower($fromName) !== 'example',
            $fromName ?: 'Chưa cấu hình',
            true
        );

        $appHost = parse_url($appUrl, PHP_URL_HOST);
        $usesPublicHttps = filter_var($appUrl, FILTER_VALIDATE_URL)
            && strtolower((string) parse_url($appUrl, PHP_URL_SCHEME)) === 'https'
            && ! in_array(strtolower((string) $appHost), ['localhost', '127.0.0.1'], true);
        $this->check(
            $checks,
            'APP_URL công khai',
            ! $isProduction || $usesPublicHttps,
            $appUrl ?: 'Chưa cấu hình',
            $isProduction
        );

        if ($transport === 'smtp') {
            $smtp = config("mail.mailers.{$mailer}", []);
            $host = trim((string) ($smtp['host'] ?? ''));
            $port = (int) ($smtp['port'] ?? 0);
            $isLocalMailServer = in_array(strtolower($host), ['mailhog', 'mailpit', 'localhost', '127.0.0.1'], true);

            $this->check($checks, 'SMTP host', $host !== '', $host ?: 'Chưa cấu hình', true);
            $this->check($checks, 'SMTP port', $port > 0 && $port <= 65535, (string) $port, true);
            $this->check(
                $checks,
                'SMTP xác thực',
                ! $isProduction || $isLocalMailServer || (
                    trim((string) ($smtp['username'] ?? '')) !== ''
                    && trim((string) ($smtp['password'] ?? '')) !== ''
                ),
                $isLocalMailServer ? 'Máy chủ mail local' : 'Thông tin bí mật đã được ẩn',
                $isProduction
            );
        }

        $this->table(['Hạng mục', 'Trạng thái', 'Giá trị an toàn'], array_map(
            fn (array $check) => [$check['label'], $check['passed'] ? 'OK' : ($check['required'] ? 'LỖI' : 'CẢNH BÁO'), $check['value']],
            $checks
        ));

        $hasFailure = collect($checks)->contains(fn (array $check) => $check['required'] && ! $check['passed']);
        if ($hasFailure) {
            $this->error('Cấu hình email chưa sẵn sàng. Không gửi thư thử.');

            return self::FAILURE;
        }

        $recipient = trim((string) $this->option('send'));
        if ($recipient === '') {
            $this->info('Cấu hình hợp lệ. Thêm --send=ban@example.com để gửi một email thử thật.');

            return self::SUCCESS;
        }

        if (filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
            $this->error('Địa chỉ nhận thử không hợp lệ.');

            return self::INVALID;
        }

        try {
            Mail::raw(
                'Email kiểm tra từ Thế Giới Trái Cây. Nếu bạn nhận được thư này, cấu hình gửi mail đang hoạt động.',
                function ($message) use ($recipient) {
                    $message->to($recipient)->subject('Kiểm tra email Thế Giới Trái Cây');
                }
            );
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Gửi email thử thất bại: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Đã gửi email thử tới {$recipient}. Hãy kiểm tra cả Inbox và Spam.");

        return self::SUCCESS;
    }

    private function check(array &$checks, string $label, bool $passed, string $value, bool $required): void
    {
        $checks[] = compact('label', 'passed', 'value', 'required');
    }
}
