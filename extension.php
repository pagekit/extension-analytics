<?php

use Pagekit\Analytics\OAuthHelper;

return [

    'name' => 'analytics',

    'autoload' => [

        'Pagekit\\Analytics\\' => 'src'

    ],

    'main' => function ($app) {

        $app->set('analytics/oauth', function () {
            return new OAuthHelper();
        });

    },

    'events' => [

        'request' => function () use ($app) {

            $presetList = [];
            $groupList = [];

            foreach (json_decode(file_get_contents(__DIR__ . '/presets.json'), true) as $group) {

                if (!$group) {
                    continue;
                }

                $groupList[] = [
                    'id' => $group['id'],
                    'label' => $group['label']
                ];

                $groupPresets = array_map(function ($preset) use ($group) {
                    $preset['groupID'] = $group['id'];

                    return $preset;
                }, $group['presets']);

                $presetList = array_merge($presetList, $groupPresets);
            }

            $app['scripts']->register('analytics-config', sprintf('var $analytics = %s;', json_encode([
                    'root' => 'admin',
                    'groups' => $groupList,
                    'presets' => $presetList,
                    'connected' => isset($this->config()['token']),
                    'profile' => $this->config('profile', false),
                    'geo' => [
                        'world' => $app['intl']->territory()->getName('001'),
                        'continents' => $app['intl']->territory()->getContinents(),
                        'subcontinents' => $app['intl']->territory()->getList('S'),
                        'countries' => $app['intl']->territory()->getCountries()
                    ]
                ])
            ), [], 'string');

            $app['scripts']->register('google', '//www.google.com/jsapi');
            $app['scripts']->register('widget-analytics', 'analytics:app/bundle/analytics.js', ['~dashboard', 'google', 'analytics-config']);
        },

        'uninstall.analytics' => function () use ($app) {
            $app['config']->remove($this->name);
        }

    ],

    'routes' => [

        '/' => [
            'name' => '@analytics',
            'controller' => [
                'Pagekit\\Analytics\\Controller\\AnalyticsController'
            ]
        ]

    ],

    'resources' => [

        'analytics:' => ''

    ],

    'config' => [

        'credentials' => [
            'client_id' => '845083612678-l0324vjmuc8q3m7fk5r37v9o4reor61j.apps.googleusercontent.com',
            'client_secret' => 'CiYpV-u9AASBXax5y38TbWmG'
        ]

    ]

];
