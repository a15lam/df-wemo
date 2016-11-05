<?php

namespace DreamFactory\Core\Wemo\Services;

use a15lam\PhpWemo\Discovery;
use DreamFactory\Core\Services\BaseRestService;
use DreamFactory\Core\Utility\Session;

class Wemo extends BaseRestService
{
    protected $deviceFile = null;

    public function __construct(array $settings)
    {
        parent::__construct($settings);
        $config = array_get($settings, 'config');
        Session::replaceLookups($config, true);

        $this->deviceFile = array_get($config, 'device_file_path');
    }

    public function getResources(
        /** @noinspection PhpUnusedParameterInspection */
        $only_handlers = false
    ){
        $refresh = $this->request->getParameterAsBool('refresh');
        $devices = Discovery::find($refresh);

        return $devices;
    }
}