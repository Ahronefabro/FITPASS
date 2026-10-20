<input type="password" name="{{ $name }}" placeholder="{{ $placeholder }}" required>
<button type="button" title="Show or hide password"
    onclick="const i = this.previousElementSibling, show = i.type === 'password'; i.type = show ? 'text' : 'password'; this.firstElementChild.textContent = show ? 'visibility' : 'visibility_off';">
    <span class="material-symbols-outlined">visibility_off</span>
</button>
