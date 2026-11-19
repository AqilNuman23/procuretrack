<section class="panel">
    <div class="panel-heading">
        <h2>{{ __('Development tickets') }}</h2>
        <span class="badge blue">{{ $access->can($actor, $currentModule, 'edit') ? __('Read & Edit') : __('Read') }}</span>
    </div>
    <div class="panel-body">
        <div class="button-row">
            @foreach($tickets as $ticket)
                <span class="badge">{{ $ticket }}</span>
            @endforeach
        </div>
    </div>
    <x-foundation.state>{{ __('This workspace is ready for its first workflow.') }}</x-foundation.state>
</section>
