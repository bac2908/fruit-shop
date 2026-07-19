@props(['title', 'desc', 'hsd', 'img'])

<div class="coupon-item__inner">
	<div class="coupon-item__left">
		<div class="cp-img boxlazy-img">
			<span class="boxlazy-img__insert">
				<img src="{{ $img }}" alt="{{ $title }}" width="104" height="104" loading="lazy" decoding="async">
			</span>
		</div>
	</div>
	<div class="coupon-item__right">
		<button type="button" class="cp-icon" title="{{ $title }}">
			<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20">
				<path d="M8.333 0C3.738 0 0 3.738 0 8.333c0 4.595 3.738 8.334 8.333 8.334 4.595 0 8.334-3.739 8.334-8.334S12.928 0 8.333 0zm0 1.026c4.03 0 7.308 3.278 7.308 7.307 0 4.03-3.278 7.308-7.308 7.308-4.03 0-7.307-3.278-7.307-7.308 0-4.03 3.278-7.307 7.307-7.307zm.096 6.241c-.283 0-.512.23-.512.513v4.359c0 .283.23.513.512.513.284 0 .513-.23.513-.513V7.78c0-.283-.23-.513-.513-.513zm.037-3.114c-.474 0-.858.384-.858.858 0 .473.384.857.858.857s.858-.384.858-.857c0-.474-.384-.858-.858-.858z" fill="#28a745"></path>
			</svg>
		</button>
		<div class="cp-top">
			<h3>{{ $title }}</h3>
			<p>{{ $desc }}</p>
		</div>
		<div class="cp-bottom">
			<div class="cp-bottom-detail">
				<p>HSD: {{ $hsd }}</p>
			</div>
			<div class="cp-bottom-btn">
				<button class="cp-btn button">Sao chép mã</button>
			</div>
		</div>
	</div>
</div>
