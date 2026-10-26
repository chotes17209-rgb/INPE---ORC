@props(['label', 'wide' => false, 'destacado' => false])
<div {{ $attributes->class(['dato', 'dato--wide' => $wide, 'dato--destacado' => $destacado]) }}>
    <div class="dato__label">{{ $label }}</div>
    <div class="dato__value">{{ trim($slot) !== '' ? $slot : '—' }}</div>
</div>
