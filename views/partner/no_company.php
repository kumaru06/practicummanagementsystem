<div class="hte-settings">
    <section class="card" style="max-width: 640px; margin: 1.5rem auto; padding: 1.5rem;">
        <span class="spf-eyebrow">Account</span>
        <h1>Host Training Establishment profile unavailable</h1>
        <p class="muted">
            Your partner login exists, but no company profile is linked to this account.
            Please contact the system administrator so they can reconnect or recreate your Host Training Establishment record.
        </p>
        <p class="muted" style="margin-top: 0.75rem;">
            You can change your password from this page, or ask an administrator for help.
        </p>
        <div style="margin-top: 1.25rem;">
            <a class="btn btn-primary" href="<?= e(route_url('partner.password.edit')) ?>">Change Password</a>
            <a class="btn" href="<?= e(asset('logout.php')) ?>">Sign out</a>
        </div>
    </section>
</div>
