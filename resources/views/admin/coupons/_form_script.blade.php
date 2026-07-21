<script>
    (() => {
        const type = document.getElementById('type');
        const valueLabel = document.querySelector('label[for="value"]');
        const discountFields = document.querySelectorAll('.coupon-discount-field');
        const percentFields = document.querySelectorAll('.coupon-percent-field');
        const giftFields = document.querySelectorAll('.coupon-gift-field');
        if (!type) return;

        const sync = () => {
            const current = type.value;
            discountFields.forEach((field) => field.hidden = current === 'gift');
            percentFields.forEach((field) => field.hidden = current !== 'percent');
            giftFields.forEach((field) => field.hidden = current !== 'gift');
            if (valueLabel) valueLabel.textContent = current === 'percent' ? 'Phần trăm giảm (%) *' : 'Số tiền giảm (VND) *';
        };

        type.addEventListener('change', sync);
        sync();
    })();
</script>
