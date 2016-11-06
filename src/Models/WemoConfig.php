<?php

namespace DreamFactory\Core\Wemo\Models;

use DreamFactory\Core\Models\BaseServiceConfigModel;

class WemoConfig extends BaseServiceConfigModel
{
    /** {@inheritdoc} */
    protected $table = 'wemo_config';

    /** {@inheritdoc} */
    protected $fillable = ['service_id', 'port', 'device_file_path'];

    /** {@inheritdoc} */
    protected $casts = [
        'port' => 'integer'
    ];

    /** {@inheritdoc} */
    protected static function prepareConfigSchemaField(array &$schema)
    {
        parent::prepareConfigSchemaField($schema);

        switch ($schema['name']) {
            case 'port':
                $schema['label'] = 'Port';
                $schema['default'] = '49153';
                $schema['description'] = 'Port number that Wemo devices are listening on. Default is 49153';
                break;
            case 'device_file_path':
                $schema['label'] = 'Device Cache File Path';
                $schema['default'] = base_path('storage/app/device.json');
                $schema['description'] =
                    'An absolute path to a file where discovered Wemo devices are cached. This file must be writable by the DreamFactory Web Server.';
                break;
        }
    }
}