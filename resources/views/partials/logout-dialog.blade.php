{{-- Asks before signing out. Opened by the log out button in the top bar. --}}
<dialog id="logout-dialog" class="logout">
    <span class="logout-icon"><span class="material-symbols-outlined">logout</span></span>
    <h2>LOG OUT?</h2>
    <p>Are you sure you want to log out? You will need to sign in again to continue.</p>

    <div class="logout-who">
        <span class="avatar"><span class="material-symbols-outlined">person</span></span>
        <div><b>{{ auth()->user()->name }}</b><small>{{ auth()->user()->role }}</small></div>
    </div>

    <form method="POST" action="{{ route('logout') }}" class="logout-actions">
        @csrf
        <button type="button" class="btn" data-close autofocus>CANCEL</button>
        <button class="btn danger">
            <span class="material-symbols-outlined">logout</span> LOG OUT
        </button>
    </form>
</dialog>
