@php
    $analyticsMeasurementId = trim((string) config('services.analytics.measurement_id'));
    $analyticsEnabled = (bool) config('services.analytics.enabled')
        && preg_match('/^G-[A-Z0-9]+$/', $analyticsMeasurementId) === 1;
@endphp

@if($analyticsEnabled)
    <section class="analytics-consent" data-analytics-consent hidden role="region" aria-label="Lựa chọn quyền riêng tư">
        <div class="analytics-consent__content">
            <div>
                <strong>Quyền riêng tư của bạn</strong>
                <p>Website chỉ bật đo lường ẩn danh sau khi bạn đồng ý. Xem <a href="{{ route('page.privacy') }}">chính sách bảo mật</a>.</p>
            </div>
            <div class="analytics-consent__actions">
                <button type="button" class="analytics-consent__button analytics-consent__button--secondary" data-analytics-reject>Từ chối</button>
                <button type="button" class="analytics-consent__button analytics-consent__button--primary" data-analytics-accept>Đồng ý</button>
            </div>
        </div>
    </section>
    <script>
        (function () {
            var root = document.querySelector('[data-analytics-consent]');
            var measurementId = @json($analyticsMeasurementId);
            var storageKey = 'tgc_analytics_consent_v1';

            if (!root || !measurementId) return;

            function loadAnalytics() {
                if (document.querySelector('script[data-google-analytics]')) return;

                window.dataLayer = window.dataLayer || [];
                window.gtag = window.gtag || function () { window.dataLayer.push(arguments); };
                window.gtag('js', new Date());
                window.gtag('config', measurementId, { anonymize_ip: true });

                var script = document.createElement('script');
                script.async = true;
                script.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(measurementId);
                script.dataset.googleAnalytics = 'true';
                document.head.appendChild(script);
            }

            function storeChoice(choice) {
                try {
                    localStorage.setItem(storageKey, choice);
                } catch (error) {}

                root.hidden = true;
            }

            var savedChoice = null;
            try {
                savedChoice = localStorage.getItem(storageKey);
            } catch (error) {}

            if (savedChoice === 'granted') {
                loadAnalytics();
            } else if (savedChoice !== 'denied') {
                root.hidden = false;
            }

            root.querySelector('[data-analytics-accept]').addEventListener('click', function () {
                storeChoice('granted');
                loadAnalytics();
            });

            root.querySelector('[data-analytics-reject]').addEventListener('click', function () {
                storeChoice('denied');
            });
        })();
    </script>
@endif
