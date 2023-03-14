<?php

namespace Umyproxy;

use Illuminate\Support\ServiceProvider;

class UmyproxyProvider extends ServiceProvider
{
    protected $commands = [
        Console\ProxyCommand::class
    ];

    public function register()
    {
        $this->commands($this->commands);
    }
}

