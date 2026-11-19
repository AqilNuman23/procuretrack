<?php

namespace Database\Seeders;

use App\Models\Foundation\AccessGroup;
use App\Models\Foundation\GroupItem;
use App\Models\Foundation\Module;
use App\Models\Foundation\User;
use App\Services\Access\ModuleCatalog;
use App\Support\Foundation\FoundationWriter;
use Illuminate\Database\Seeder;
use RuntimeException;

class AccessSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Demo access seeds are local/testing only.');
        }
        $writer = app(FoundationWriter::class);
        $admin = User::query()->where('USWorkerID', 'PT-1000')->whereHas('company', fn ($q) => $q->where('COCode', 'DEMO'))->firstOrFail();
        app(ModuleCatalog::class)->sync($admin);
        $modules = Module::query()->get()->keyBy('MOCode');
        foreach (['read' => 'Finance viewers', 'edit' => 'Finance editors'] as $level => $name) {
            $group = AccessGroup::query()->where('AG_CONo', $admin->US_CONo)->where('AGName', $name)->first()
                ?? $writer->create(AccessGroup::class, ['AG_CONo' => $admin->US_CONo, 'AGName' => $name], $admin);
            if (! $group->items()->where('GI_MONo', $modules['finance.overview']->getKey())->exists()) {
                $writer->create(GroupItem::class, ['GI_AGNo' => $group->getKey(), 'GI_MONo' => $modules['finance.overview']->getKey(), 'GILevel' => $level], $admin);
            }
        }
    }
}
