<?php

namespace a15lam\Wemo\Services;

use a15lam\PhpWemo\Contracts\DeviceInterface;
use a15lam\PhpWemo\Discovery;
use DreamFactory\Core\Exceptions\BadRequestException;
use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\Exceptions\NotFoundException;
use DreamFactory\Core\Services\BaseRestService;
use DreamFactory\Core\Utility\Session;

class Wemo extends BaseRestService
{
    /** @var string|null File path where device info is cached. */
    protected $deviceFile = null;

    /** {@inheritdoc} */
    public function __construct(array $settings)
    {
        parent::__construct($settings);
        $config = array_get($settings, 'config');
        Session::replaceLookups($config, true);

        $this->deviceFile = array_get($config, 'device_file_path');
    }

    /** {@inheritdoc} */
    protected function handleGET()
    {
        if ($this->resource) {
            $device = $this->resource;
            $state = array_get($this->resourceArray, 1);
            $state = (null === $state) ? $state : strtolower($state);
            $list = $this->findDevices(false, true);

            if (in_array($device, $list)) {
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

                $state = (int) $d->state();
                $dimLevel = 'N/A';
                if ($d->isDimmable()) {
                    $dimLevel = $d->dimState();
                }

                return ['state' => $state, 'dim_level' => $dimLevel];
            } else {
                throw new NotFoundException('Device not found with id [' . $device . ']');
            }
        } else {
            $refresh = $this->request->getParameterAsBool('refresh');
            $asList = $this->request->getParameterAsBool('as_list');
            $list = $this->findDevices($refresh, $asList);

            return ['device' => $list];
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
        Discovery::$deviceFile = $this->deviceFile;
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

            if(true === $state){
                $out['state'] = (int) $device->state();
                if($dimmable){
                    $out['dim_level'] = $device->dimState();
                }
            }

            return $out;
        } else {
            throw new InternalServerErrorException('Unsupported device [' . $id . ']');
        }
    }
}