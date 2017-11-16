<?php

class ControllerExtensionModuleMoloni extends Controller
{

    private $moduleName = 'Moloni';
    private $moduleNameSmall = 'moloni';
    private $moduleData_module = 'errorlogmanager_module';
    private $modulePathBase = 'extension/module/moloni/';
    public $modelsRequired = array(
        "install" => "model_extension_module_moloni_install",
        "moloni" => "model_extension_module_moloni_moloni"
    );
    private $modulePath = 'extension/module/moloni';
    private $eventGroup = 'moloni';
    private $version = '1.01';

    public function __construct($registry)
    {
        parent::__construct($registry);

        $this->load->model("setting/setting");

        $this->__modelHandler();
    }

    public function __modelHandler()
    {

        if (isset($this->modelsRequired) && is_array($this->modelsRequired)) {

            foreach ($this->modelsRequired as $name => $model) {

                $this->load->model($this->modulePathBase . $name);

                $this->{$name} = $this->{$model};
            }
        }
    }

    public function index()
    {

        $this->moloni->loadLibrary();

        echo '<br>';
        echo '<br>';

        print_r($this->moloni->lib("errors"));
    }

    public function install()
    {

        $this->load->model("setting/event");

        $this->model_setting_event->addEvent($this->eventGroup, "admin/view/common/column_left/before", $this->modulePath . "/injectAdminMenuItem");
    }

    public function uninstall()
    {

        $this->{$this->moduleModel}->uninstall();

        $this->model_setting_setting->deleteSetting("errorlogmanager");

        $this->load->model("setting/event");

        $this->model_setting_event->deleteEventByCode($this->eventGroup);
    }

    public function injectAdminMenuItem($eventRoute, &$data)
    {
        if ($this->user->hasPermission('access', 'extension/module/moloni')) {
            $moloni[] = array(
                'name' => $this->language->get('Home'),
                'href' => $this->url->link('extension/module/moloni', array("page" => "home", 'user_token' => $this->session->data['user_token']), true),
                'children' => array()
            );

            $moloni[] = array(
                'name' => $this->language->get('Documents'),
                'href' => $this->url->link('extension/module/moloni', array("page" => "documents", 'user_token' => $this->session->data['user_token']), true),
                'children' => array()
            );

            $moloni[] = array(
                'name' => $this->language->get('Settings'),
                'href' => $this->url->link('extension/module/moloni', array("page" => "settings", 'user_token' => $this->session->data['user_token']), true),
                'children' => array()
            );

            if ($moloni) {
                array_splice($data['menus'], 5, 0, array(array(
                        'id' => 'menu-moloni',
                        'icon' => 'fa-file-text',
                        'name' => $this->language->get('Moloni'),
                        'href' => '',
                        'children' => $moloni
                )));
            }
        }
    }
}
