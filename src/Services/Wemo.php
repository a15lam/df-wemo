<?php

namespace a15lam\Wemo\Services;

use a15lam\PhpWemo\Contracts\DeviceInterface;
use a15lam\PhpIot\Discovery;
use DreamFactory\Core\Contracts\ServiceResponseInterface;
use DreamFactory\Core\Enums\ApiOptions;
use DreamFactory\Core\Enums\Verbs;
use DreamFactory\Core\Exceptions\BadRequestException;
use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\Exceptions\NotFoundException;
use DreamFactory\Core\Facades\ServiceManager;
use DreamFactory\Core\Services\BaseRestService;

class Wemo extends BaseRestService
{
    const DEVICE_CACHE_FILE = 'storage/app/wemo-device.json';

    /** {@inheritdoc} */
    public function __construct(array $settings = [])
    {
        parent::__construct($settings);
        Discovery::$deviceFile = base_path(static::DEVICE_CACHE_FILE);
    }

    /** {@inheritdoc} */
    protected function handleGET()
    {
        if ($this->resource) {
            $device = $this->resource;
            $state = array_get($this->resourceArray, 1);
            $state = (null === $state) ? $state : strtolower($state);

            try {
                /** @var DeviceInterface $d */
                $d = Discovery::getDeviceById($device);
                if (!empty($state)) {
                    if ('on' === $state) {
                        $d->On();
                    } elseif ('off' === $state) {
                        $d->Off();
                    } elseif ('dim' === $state) {
                        if (!$d->isDimmable()) {
                            throw new BadRequestException('Device does not support dimming');
                        }
                        $dimLevel = (int)array_get($this->resourceArray, 2, 50);
                        $d->dim($dimLevel);
                    }
                }

                $state = (int)$d->state();
                $dimLevel = 'N/A';
                if ($d->isDimmable()) {
                    $dimLevel = $d->dimState();
                }

                return ['state' => $state, 'dim_level' => $dimLevel];
            } catch (\Exception $e) {
                throw new NotFoundException('Device not found with id [' . $device . ']. ' . $e->getMessage());
            }
        } else {
            $refresh = $this->request->getParameterAsBool('refresh');
            $asList = $this->request->getParameterAsBool(ApiOptions::AS_LIST);
            $asAL = $this->request->getParameterAsBool(ApiOptions::AS_ACCESS_LIST);

            if ($asAL) {
                return ['resource' => ['', '*']];
            } else {
                $list = $this->findDevices($refresh, $asList);

                return ['device' => $list];
            }
        }
    }

    /**
     * Searches for devices in the network
     *
     * @param bool $refresh
     * @param bool $asList
     *
     * @return array
     */
    protected function findDevices($refresh = false, $asList = false)
    {
        $list = [];
        $devices = Discovery::find($refresh);
        foreach ($devices as $device) {
            $model = array_get($device, 'modelName');
            if ('Bridge' === $model) {
                $bds = array_get($device, 'device');
                foreach ($bds as $bd) {
                    if ($asList) {
                        $list[] = array_get($bd, 'id');
                    } else {
                        $list[] = $this->getDeviceInfo($bd);
                    }
                }
            } else {
                if ($asList) {
                    $list[] = array_get($device, 'id');
                } else {
                    $list[] = $this->getDeviceInfo($device);
                }
            }
        }

        return $list;
    }

    /**
     * Gets additional device info.
     *
     * @param $info
     *
     * @return array
     * @throws \DreamFactory\Core\Exceptions\InternalServerErrorException
     */
    protected function getDeviceInfo($info)
    {
        $id = array_get($info, 'id');
        /** @var DeviceInterface $device */
        $device = Discovery::getDeviceById($id);
        $details = $this->request->getParameterAsBool('details');
        $state = $this->request->getParameterAsBool('state');

        if ($device instanceof DeviceInterface) {
            $dimmable = $device->isDimmable();
            if (true === $details) {
                $info['dimmable'] = $dimmable;
                $out = $info;
            } else {
                $out = [
                    'id'       => $id,
                    'dimmable' => $dimmable
                ];
            }

            if (true === $state) {
                $out['state'] = (int)$device->state();
                if ($dimmable) {
                    $out['dim_level'] = $device->dimState();
                }
                // Special handling of garage door state
                // Fetching state from alarm.com
                if($out['id'] === 'garage'){
                    /** @var ServiceResponseInterface $result */
                    $result = ServiceManager::handleRequest('alarm', Verbs::GET, '92289759-7');
                    if($result->getStatusCode() === 200){
                        $content = $result->getContent();
                        $rawState = ($content['sensor'])->{'state'};
                        $out['state'] = ($rawState === 1)? 0 : 1;
                    }
                }
            }

            return $out;
        } else {
            throw new InternalServerErrorException('Unsupported device [' . $id . ']');
        }
    }

//    /** @inheritdoc */
//    public static function getApiDocInfo($service)
//    {
//        $serviceName = strtolower($service->name);
//
//        $base = [
//            'paths' => [
//                '/' . $serviceName                                      => [
//                    'get' => [
//                        'tags'        => [$serviceName],
//                        'summary'     => 'getDevices() - Get device list',
//                        'operationId' => 'getDevices',
//                        'consumes'    => ['application/json', 'application/xml'],
//                        'produces'    => ['application/json', 'application/xml'],
//                        'description' => 'Fetches device list',
//                        'parameters'  => [
//                            [
//                                'name'        => 'refresh',
//                                'type'        => 'boolean',
//                                'description' => 'Force device discovery',
//                                'in'          => 'query',
//                                'required'    => false,
//                            ],
//                            [
//                                'name'        => 'as_list',
//                                'type'        => 'boolean',
//                                'description' => 'Returns a plain list of devices',
//                                'in'          => 'query',
//                                'required'    => false,
//                            ],
//                        ],
//                        'responses'   => [
//                            '200'     => [
//                                'description' => 'Success',
//                                'schema'      => [
//                                    'type'       => 'object',
//                                    'properties' => [
//                                        'device' => [
//                                            'type'  => 'array',
//                                            'items' => [
//                                                'type'       => 'object',
//                                                'properties' => [
//                                                    'id'       => ['type' => 'string'],
//                                                    'dimmable' => ['type' => 'boolean']
//                                                ]
//                                            ]
//                                        ]
//                                    ]
//                                ]
//                            ],
//                            'default' => [
//                                'description' => 'Error',
//                                'schema'      => ['$ref' => '#/definitions/Error']
//                            ]
//                        ],
//                    ]
//                ],
//                '/' . $serviceName . '/{device_id}'                     => [
//                    'get' => [
//                        'tags'        => [$serviceName],
//                        'summary'     => 'getDeviceState() - Get device state',
//                        'operationId' => 'getDeviceState',
//                        'consumes'    => ['application/json', 'application/xml'],
//                        'produces'    => ['application/json', 'application/xml'],
//                        'description' => 'Fetches device state',
//                        'parameters'  => [
//                            [
//                                'name'        => 'device_id',
//                                'type'        => 'string',
//                                'description' => 'ID of the device to operate on',
//                                'in'          => 'path',
//                                'required'    => true,
//                            ],
//                        ],
//                        'responses'   => [
//                            '200'     => [
//                                'description' => 'Success',
//                                'schema'      => [
//                                    'type'       => 'object',
//                                    'properties' => [
//                                        'state'     => ['type' => 'boolean'],
//                                        'dim_level' => ['type' => 'integer']
//                                    ]
//                                ]
//                            ],
//                            'default' => [
//                                'description' => 'Error',
//                                'schema'      => ['$ref' => '#/definitions/Error']
//                            ]
//                        ],
//                    ]
//                ],
//                '/' . $serviceName . '/{device_id}/{state}/{dim_level}' => [
//                    'get' => [
//                        'tags'        => [$serviceName],
//                        'summary'     => 'setDeviceState() - Set device state',
//                        'operationId' => 'setDeviceState',
//                        'consumes'    => ['application/json', 'application/xml'],
//                        'produces'    => ['application/json', 'application/xml'],
//                        'description' => 'Sets device state',
//                        'parameters'  => [
//                            [
//                                'name'        => 'device_id',
//                                'type'        => 'string',
//                                'description' => 'ID of the device to operate on',
//                                'in'          => 'path',
//                                'required'    => true,
//                            ],
//                            [
//                                'name'        => 'state',
//                                'type'        => 'string',
//                                'description' => 'State of the device. Available options are on,off,dim',
//                                'in'          => 'path',
//                                'required'    => true,
//                            ],
//                            [
//                                'name'        => 'dim_level',
//                                'type'        => 'integer',
//                                'description' => 'Dim level in percentage. Available options are 0% - 100%. Default is 50%.',
//                                'in'          => 'path',
//                                'required'    => false,
//                            ],
//                        ],
//                        'responses'   => [
//                            '200'     => [
//                                'description' => 'Success',
//                                'schema'      => [
//                                    'type'       => 'object',
//                                    'properties' => [
//                                        'state'     => ['type' => 'boolean'],
//                                        'dim_level' => ['type' => 'integer']
//                                    ]
//                                ]
//                            ],
//                            'default' => [
//                                'description' => 'Error',
//                                'schema'      => ['$ref' => '#/definitions/Error']
//                            ]
//                        ],
//                    ]
//                ]
//            ],
//
//        ];
//
//        return $base;
//    }
}