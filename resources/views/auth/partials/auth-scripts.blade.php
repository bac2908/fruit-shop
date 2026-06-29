<script>
    (function () {
        function setupPasswordToggles() {
            document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
                var input = document.getElementById(button.getAttribute('data-password-toggle'));
                var icon = button.querySelector('i');

                if (!input) {
                    return;
                }

                function syncPasswordIcon() {
                    var isVisible = input.type === 'text';
                    button.setAttribute('aria-label', isVisible ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');

                    if (icon) {
                        icon.classList.toggle('fa-eye', isVisible);
                        icon.classList.toggle('fa-eye-slash', !isVisible);
                    }
                }

                syncPasswordIcon();

                button.addEventListener('click', function () {
                    var shouldShow = input.type === 'password';

                    input.type = shouldShow ? 'text' : 'password';
                    syncPasswordIcon();
                });
            });
        }

        function passwordScore(value) {
            var score = 0;

            if (value.length >= 8) {
                score += 1;
            }

            if (/[a-z]/.test(value) && /[A-Z]/.test(value)) {
                score += 1;
            }

            if (/[0-9]/.test(value)) {
                score += 1;
            }

            if (/[^A-Za-z0-9]/.test(value) && score > 1) {
                score += 1;
            }

            if (!value) {
                return 0;
            }

            return Math.min(score, 3);
        }

        function setupPasswordStrength() {
            var input = document.querySelector('[data-password-strength]');
            var meter = document.querySelector('[data-strength-meter]');
            var label = meter ? meter.querySelector('.auth-strength-label') : null;

            if (!input || !meter || !label) {
                return;
            }

            function updateMeter() {
                var score = passwordScore(input.value);
                var text = 'Chưa nhập';

                if (score === 1) {
                    text = 'Yếu';
                } else if (score === 2) {
                    text = 'Trung bình';
                } else if (score >= 3) {
                    text = 'Mạnh';
                }

                meter.setAttribute('data-score', String(score));
                label.textContent = text;
            }

            input.addEventListener('input', updateMeter);
            updateMeter();
        }

        setupPasswordToggles();
        setupPasswordStrength();
    })();
</script>
