<dialog class="form-error-dialog" data-form-error-dialog aria-labelledby="form-error-title">
    <div class="dialog-icon"><x-design.icon name="alert" /></div>
    <h2 id="form-error-title">{{ __('We couldn’t submit this form.') }}</h2>
    <ul data-form-error-messages></ul>
    <form method="dialog" class="dialog-actions">
        <button class="button primary" autofocus>{{ __('Close') }}</button>
    </form>
</dialog>
