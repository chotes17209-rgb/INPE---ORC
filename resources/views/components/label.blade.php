@props(['for' => null, 'requerido' => false])
<label @if($for) for="{{ $for }}" @endif {{ $attributes->merge(['class' => 'form-label']) }}>{{ $slot }}@if($requerido)<span class="req" aria-hidden="true">*</span><span class="visually-hidden"> (obligatorio)</span>@endif</label>
