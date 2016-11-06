<?php

namespace DreamFactory\Core\Wemo;

use DreamFactory\Core\Components\ServiceDocBuilder;
use DreamFactory\Core\Services\ServiceManager;
use DreamFactory\Core\Services\ServiceType;
use DreamFactory\Core\Wemo\Models\WemoConfig;
use DreamFactory\Core\Wemo\Services\Wemo;
use DreamFactory\Core\Enums\ServiceTypeGroups;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    use ServiceDocBuilder;

    public function register()
    {
        // Add our service types.
        $this->app->resolving('df.service', function (ServiceManager $df){
            $df->addType(
                new ServiceType([
                    'name'            => 'wemo',
                    'label'           => 'Wemo',
                    'description'     => 'Belkin Wemo service.',
                    'group'           => ServiceTypeGroups::IOT,
                    'config_handler'  => WemoConfig::class,
                    'default_api_doc' => function ($service){
                        return $this->buildServiceDoc($service->id, Wemo::getApiDocInfo($service));
                    },
                    'factory'         => function ($config){
                        return new Wemo($config);
                    },
                ])
            );
        });
    }
}