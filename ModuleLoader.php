<?php

namespace bmsrox\autoloader;

use Yii;
use yii\base\BootstrapInterface;
use yii\base\Event;
use yii\base\InvalidConfigException;

/**
 * ModuleLoader provide a simple module autoload based on humhub component
 * @see https://github.com/humhub/humhub/blob/master/protected/humhub/components/bootstrap/ModuleAutoLoader.php
 * @package bmsrox\autoloader
 * @author Bruno Marinho <bmsrox@gmail.com>
 */

class ModuleLoader implements BootstrapInterface
{
    const CACHE_ID = 'modules_config';

    /**
     * Specify the modules folders
     *
     * Basic template use ['@app/modules']
     * Advanced template use ['@backend/modules', '@frontend/modules', '@common/modules']
     * @var array
     */
    public $modules_paths = ['@app/modules'];

    public function bootstrap($app)
    {
        $this->getModulesConfig();
    }

    /**
     * Get the module configuration file `module/config.php`
     * ```php
     * return [
     *    'id' => 'admin',
     *    'class' => AdminModule::className(),
     *    'events' => [
     *        [
     *            'class' => SidebarMenu::className(),
     *            'event' => SidebarMenu::REGISTER,
     *            'callback' => [Events::className(), 'onMenuRegister']
     *        ],
     *    ],
     *    'urlManagerRules' => [
     *       '/admin' => '/admin/default/index'
     *    ]
     *  ];
     * ```
     * @throws InvalidConfigException
     */
    private function getModulesConfig()
    {
        $modules = Yii::$app->cache->get(self::CACHE_ID);
        
        if ($modules !== false) {
            return $this->load($modules);
        }

        $modules = [];

        foreach ($this->modules_paths as $module_path) {
            $path = Yii::getAlias($module_path);

            if (!is_dir($path)) {
                continue;
            }

            $scanDir = scandir($path);

            foreach ($scanDir as $module) {
                // skip ".", ".." and hidden files
                if ($module[0] == '.') {
                    continue;
                }

                $base = $path . DIRECTORY_SEPARATOR . $module;
                $configFile = $base . DIRECTORY_SEPARATOR . 'config.php';

                if (!is_file($configFile)) {
                    throw new InvalidConfigException("Module configuration requires a 'config.php' file!");
                }

                $modules[$base] = require($configFile);
            }
        }

        if (!YII_DEBUG) {
            Yii::$app->cache->set(self::CACHE_ID, $modules);
        }

        return $this->load($modules);
    }

    /**
     * @param $modules
     * @throws InvalidConfigException
     */
    private function load($modules)
    {
        foreach ($modules as $basePath => $config) {
            // Check mandatory config options
            if (!isset($config['class']) || !isset($config['id'])) {
                throw new InvalidConfigException("Module configuration requires an id and class attribute!");
            }

            $this->register($basePath, $config);
        }
    }

    /**
     * Registers a module
     *
     * @param string $basePath the modules base path
     * @param array $config the module configuration (config.php)
     * @throws InvalidConfigException
     */
    private function register($basePath, $config)
    {
        $aliasName = '@' . $config['id'];

        // Set module alias
        if (isset($config['namespace'])) {
            $aliasName = '@' . str_replace('\\', '/', $config['namespace']);
        }

        Yii::setAlias($aliasName, $basePath);
            
        // Handle Submodules
        if (!isset($config['modules'])) {
            $config['modules'] = [];
        }

        // Append URL Rules
        if (isset($config['urlManagerRules'])) {
            Yii::$app->urlManager->addRules($config['urlManagerRules'], false);
        }

        $moduleConfig = [
            'class' => $config['class'],
            'modules' => $config['modules']
        ];

        // Register Yii Module
        Yii::$app->setModule($config['id'], $moduleConfig);

        // Register Event Handlers
        if (isset($config['events'])) {
            $this->registerEvents($config['events']);
        }
    }

    /**
     * Auxiliar method to register the module events
     * @param string $events list of events
     * @return void
     */
    private function registerEvents(array $events)
    {
        foreach ($events as $event) {
            if (isset($event['class'])) {
                Event::on($event['class'], $event['event'], $event['callback']);
            } else {
                Event::on($event[0], $event[1], $event[2]);
            }
        }
    }
}
