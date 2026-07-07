<?php

declare(strict_types=1);

arch('strict types everywhere')
    ->expect('App')
    ->toUseStrictTypes();

arch('no debug functions')
    ->expect(['dd', 'dump', 'var_dump', 'ray'])
    ->not->toBeUsed();

arch('env() only in config files')
    ->expect('env')
    ->not->toBeUsedIn('App');

arch('actions are final, readonly, invokable and suffixed')
    ->expect('App\Actions')
    ->toBeFinal()
    ->toBeReadonly()
    ->toBeInvokable()
    ->toHaveSuffix('Action');

arch('dtos are final and readonly')
    ->expect('App\DataTransferObjects')
    ->toBeFinal()
    ->toBeReadonly();

arch('controllers are final and do not use eloquent builder directly')
    ->expect('App\Http\Controllers')
    ->toBeFinal()
    ->ignoring(App\Http\Controllers\Controller::class)
    ->not->toUse(Illuminate\Database\Eloquent\Builder::class);

arch('view models extend the base view model')
    ->expect('App\ViewModels')
    ->toExtend(App\ViewModels\ViewModel::class)
    ->ignoring(App\ViewModels\ViewModel::class);

arch('view models are final and suffixed')
    ->expect('App\ViewModels')
    ->toBeFinal()
    ->ignoring(App\ViewModels\ViewModel::class)
    ->toHaveSuffix('ViewModel');

arch('base models are abstract')
    ->expect('App\Models\Base')
    ->toBeAbstract();

arch('models and builders are final')
    ->expect('App\Models')
    ->toBeFinal()
    ->ignoring(['App\Models\Base', 'App\Models\Concerns']);

arch('filters extend the base filter')
    ->expect('App\Filters')
    ->toExtend(App\Filters\Filter::class)
    ->ignoring([App\Filters\Filter::class, App\Filters\FilterValue::class]);

arch('policies are final and suffixed')
    ->expect('App\Policies')
    ->toBeFinal()
    ->toHaveSuffix('Policy');

arch('services are final, readonly and suffixed')
    ->expect('App\Services')
    ->toBeFinal()
    ->toBeReadonly()
    ->toHaveSuffix('Service');

arch('resources are final and suffixed')
    ->expect('App\Http\Resources')
    ->toBeFinal()
    ->toHaveSuffix('Resource');

arch('jobs are final, queued and suffixed')
    ->expect('App\Jobs')
    ->toBeFinal()
    ->toImplement(Illuminate\Contracts\Queue\ShouldQueue::class)
    ->toHaveSuffix('Job');

arch('notifications are final and suffixed')
    ->expect('App\Notifications')
    ->toBeFinal()
    ->toHaveSuffix('Notification');

arch('validation rules are final')
    ->expect('App\Rules')
    ->toBeFinal();

arch('providers are final')
    ->expect('App\Providers')
    ->toBeFinal();
