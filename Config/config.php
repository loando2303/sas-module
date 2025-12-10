<?php

return [
    'name'                  => 'SAS',
    'domain'                => env('APP_DOMAIN', ''),
    'client'                => env('APP_CLIENT', 'ohs'),
    'manifest'              => [
        'uploaded'  => 1,
        'processed' => 2,
        'error'     => 9,
    ],
    'location_construction' => [
        'ceiling' => 1060,
        'door'    => 1513,
        'floor'   => 1405,
        'wall'    => 1168,
        'window'  => 1558
    ],
    'location_void'         => [
        'ceilingVoid' => LOCATION_CEILING_VOID,
        'cavities'    => LOCATION_CAVITIES,
        'risers'      => LOCATION_RISERS,
        'ducting'     => LOCATION_DUCTING,
        'boxing'      => LOCATION_BOXING,
        'pipework'    => LOCATION_PIPEWORK,
        'floorVoid'   => LOCATION_FLOOR_VOID,
    ],
    'features'              => [
        'ohs'            => ['site_diagram', 'rams_signature','nshare','customer', 'scope_change'],
        'lbhc'           => ['void_details', 'survey_timeline', 'no_sample_email', 'sample_sequence', 'general_observation','is_mas_override'],
        'westmister'     => ['void_details'],
        'stockporthomes' => ['void_details', 'customer', 'scope_change'],
        'nhsggc'         => ['override_function'],
    ],
];
