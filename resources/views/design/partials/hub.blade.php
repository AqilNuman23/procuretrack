<div class="section-label"><span>EXPLORE YOUR MODULES</span><span>03 MODULES</span></div>
<section class="module-grid" aria-label="Available modules">
    @foreach($design['modules'] as $item)
    <a class="module-card {{ $item['accent'] }}" href="{{ $url($item['home']) }}"><div class="module-art" aria-hidden="true"><span class="art-grid"></span><span class="module-symbol"><x-design.icon :name="$item['icon']" /></span><span class="art-dot"></span></div><div class="module-body"><span class="eyebrow">{{ $item['tag'] }}</span><h2>{{ $item['name'] }}</h2><p>{{ $item['description'] }}</p><div class="module-link">{{ $ui['open_module'] }}<span class="arrow-circle"><x-design.icon name="arrow" /></span></div></div></a>
    @endforeach
</section>
<div class="hub-note"><span class="hub-note-icon"><x-design.icon name="shield" /></span><div><strong>A focused workspace, built around your role.</strong><p>This administrator preview shows all three foundation modules.</p></div><a class="quiet-link" href="{{ $url('pages') }}">Explore all screens <x-design.icon name="arrow" /></a></div>
<section class="getting-started"><div><span class="eyebrow">A GOOD PLACE TO START</span><h2>Bring your team together.</h2><p>Create a team, register its PIC, and follow each colleague’s account setup.</p></div><a class="button secondary" href="{{ $url('teams') }}">{{ $design['pages']['teams']['title'] }}<x-design.icon name="arrow" /></a></section>
