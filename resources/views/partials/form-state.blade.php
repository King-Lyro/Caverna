@php
    $preservedInput = collect(session()->getOldInput())->except(['password', 'password_confirmation', 'current_password', '_token'])->all();
@endphp
<script type="application/json" id="form-old-input">@json($preservedInput)</script>
