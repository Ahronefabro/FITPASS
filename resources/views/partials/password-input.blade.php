{{-- A password box with an eye button to show or hide what was typed, for the
     forms inside the portals. The sign-in pages use password-field instead. --}}
<span class="pw-toggle">
    <input type="password" name="{{ $name }}" autocomplete="{{ $autocomplete ?? 'off' }}"
        @isset($minlength) minlength="{{ $minlength }}" @endisset required>
    <button type="button" title="Show or hide password" aria-label="Show or hide password"
        onclick="const i = this.previousElementSibling, show = i.type === 'password'; i.type = show ? 'text' : 'password'; this.firstElementChild.textContent = show ? 'visibility' : 'visibility_off';">
        <span class="material-symbols-outlined">visibility_off</span>
    </button>
</span>
