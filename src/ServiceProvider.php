<?php

namespace a15lam\Wemo;

//use DreamFactory\Core\Components\ServiceDocBuilder;
use DreamFactory\Core\Models\BaseModel;
use DreamFactory\Core\Models\Service;
use DreamFactory\Core\Services\ServiceManager;
use DreamFactory\Core\Services\ServiceType;
use a15lam\Wemo\Services\Wemo;
use Cache;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    //use ServiceDocBuilder;
//
//    public function boot()
//    {
//        // Auto seed the service as it doesn't requir any configuration.
//        // Default service name is 'wemo'. You can change this using
//        // WEMO_SERVICE_NAME Environment option.
//        if (false === Cache::get('wemo-seeded', false)) {
//            $serviceName = env('WEMO_SERVICE_NAME', 'wemo');
//            $model = Service::whereName($serviceName)->whereType('wemo')->get()->first();
//
//            if (empty($model)) {
//                $model = Service::create([
//                    'name'        => $serviceName,
//                    'type'        => 'wemo',
//                    'label'       => 'Belkin Wemo Service',
//                    'description' => 'A DreamFactory service for discovering and controlling Wemo devices in your network',
//                    'is_active'   => 1
//                ]);
//
//                BaseModel::unguard();
//                $model->mutable = 0;
//                $model->update();
//                BaseModel::reguard();
//                Cache::forever('wemo-seeded', true);
//            }
//        }
//    }

    public function register()
    {
        // Add our service types.
        $this->app->resolving('df.service', function (ServiceManager $df){
            $df->addType(
                new ServiceType([
                    'name'            => 'wemo',
                    'label'           => 'Wemo',
                    'description'     => 'Belkin Wemo service.',
                    'group'           => 'IOT',
                    'config_handler'  => null,
//                    'default_api_doc' => function ($service){
//                        return $this->buildServiceDoc($service->id, Wemo::getApiDocInfo($service));
//                    },
                    'factory'         => function ($config){
                        return new Wemo($config);
                    },
                ])
            );
        });
    }
}