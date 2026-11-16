<?php

namespace App\Services\Access;

use App\Models\Foundation\Module;
use App\Models\Foundation\User;
use App\Support\Foundation\FoundationChanges;
use App\Support\Foundation\FoundationWriter;

class ModuleCatalog
{
    public function sync(?User $actor = null): void
    {
        app(FoundationWriter::class)->transaction($actor, function (FoundationChanges $changes) {
            $modules = [];
            foreach (config('modules') as $code => $definition) {
                $values = ['MOName' => $definition['name'], 'MOParent_MONo' => $definition['parent'] ? $modules[$definition['parent']]->getKey() : null, 'MODelegable' => $definition['delegable']];
                $existing = Module::query()->where('MOCode', $code)->first();
                $modules[$code] = $existing ? $changes->update($existing, $values) : $changes->create(Module::class, ['MOCode' => $code] + $values);
            }
            foreach (Module::query()->whereNotIn('MOCode', array_keys(config('modules')))->where('MOActive', true)->get() as $obsolete) {
                $changes->update($obsolete, ['MOActive' => false]);
            }
        });
    }
}
