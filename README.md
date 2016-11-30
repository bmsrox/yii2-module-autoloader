YII2 Module AutoLoader
======================

### INSTALL

    composer require bmsrox/yii2-module-autoloader
    
or

    "bmsrox/yii2-module-autoloader":"dev-master"
    
### HOW TO USE

Create a module in your app and add config.php in the module root path

#### config.php

    use app\modules\admin\AdminModule;
    
    return [
        'id' => 'admin',
        'class' => AdminModule::className(),
        'urlManagerRules' => [
            '/admin' => '/admin/default/index'
        ]
    ];

Set the components in your web.php or main.php.

    'components' => [
            ...
            'moduleLoader' => [
                'class' => 'bmsrox\autoloader\ModuleLoader',
                'modules_paths' => [
                    '@backend/modules', 
                    '@frontend/modules', 
                    '@common/modules'
                    ]
            ],
            ...
     ]
     
PS: If you are using a basic template the default modules_paths is @app/modules. but you can specify any path.
          
Set the bootstrap as

    'bootstrap' => [
        ...
        'moduleLoader'
        ...
     ],
     
     
### Using Events

Example to use a events

I've been created an event called SidebarMenu to add menu dynamically when i create a new module.

    use yii\base\Component;
    
    class SidebarMenu extends Component {
    
        const REGISTER = 'register';
    
        private $items = [];
    
        public function init() {
            $this->trigger(self::REGISTER);
            return parent::init();
        }
    
        public function setItem($item) {
            if (!isset($item['sortOrder']))
                $item['sortOrder'] = 1000;
            $this->items[] = $item;
        }
    
        public function getItem() {
            $this->sortItems();
            return $this->items;
        }
    
        /**
         * Sorts the item attribute by sortOrder
         */
        private function sortItems() {
            usort($this->items, function ($a, $b) {
                if ($a['sortOrder'] == $b['sortOrder']) {
                    return 0;
                } else
                if ($a['sortOrder'] < $b['sortOrder']) {
                    return - 1;
                } else {
                    return 1;
                }
            });
        }
    
    }

Create a class Events in your module root path like
    
        use yii\base\Object;
        use yii\helpers\Html;
        
        class Events extends Object {
        
            public static function onMenuRegister($event) {
                $event->sender->setItem([
                    'label' => 'example',
                    'url' => ['/example/default/index'],
                    'visible' => !Yii::$app->user->isGuest,
                    'sortOrder' => 2
                ]);
            }
        
        }
        
        
In your module/config.php add a key into array config 

        'events' => [
            ['class' => SidebarMenu::className(), 'event' => SidebarMenu::REGISTER, 'callback' => [Events::className(), 'onMenuRegister']],
        ],
        
Call the Menu class to render a dynamic menu 
    
            echo Menu::widget([
                'items' => (new \app\components\SidebarMenu())->getItem(),
            ]);
           
        
So you can add many events into your module that it will be added automatically.