<?php

class ControllerExtensionModuleMoloni extends Controller
{

    private $moduleName = 'Moloni';
    private $modulePathBase = 'extension/module/moloni/';
    private $modulePathView = 'extension/module/moloni/';
    public $modelsRequired = array(
        "install" => "model_extension_module_moloni_install",
        "ocdb" => "model_extension_module_moloni_ocdb"
    );
    private $eventGroup = 'moloni';
    private $version = '1.01';

    public function __construct($registry)
    {
        parent::__construct($registry);
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

        $data = $this->load->language('extension/module/moloni');

        $data['heading_title'] = "Moloni";

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $tokens = $this->ocdb->qGetMoloniTokens();

        $this->load->library("moloni");

        $this->moloni->access_token = !empty($tokens['access_token']) ? $tokens['access_token'] : false;
        $this->moloni->refresh_token = !empty($tokens['refresh_token']) ? $tokens['refresh_token'] : false;
        $this->moloni->expire_date = !empty($tokens['expire_date']) ? $tokens['expire_date'] : "";

        print_r(get_class_methods($this->moloni));

        $this->moloni->verifyTokens();
        if ($this->moloni->logged) {
            if ($this->moloni->company_id) {
                switch ($_GET['page']) {
                    case "settings" :
                        $this->page = "settings";
                        break;
                    case "documents" :
                        $this->page = "documents";
                        break;
                    case "home" :
                    default:
                        $this->page = "home";
                        break;
                }
            } else {
                $this->page = "companies";
            }
        } else {
            $data['error_warning'] = $this->moloni->errors->getError("last")['message'];
            $data['login_form_url'] = $this->url->link('extension/module/moloni', array("page" => "login", 'user_token' => $this->session->data['user_token']), true);
            $this->page = "login";
        }


        $data['breadcrumbs'] = $this->createBreadcrumbs();
        $this->response->setOutput($this->load->view($this->modulePathView . $this->page, $data));
    }

    private function createBreadcrumbs()
    {
        switch ($this->page) {
            case "login":
                $breadcrumbs[] = array("text" => "Login", 'href' => $this->url->link('extension/module/moloni', array("page" => "home", 'user_token' => $this->session->data['user_token']), true));
                break;
            default :
                return array(array("href" => "extension/module/moloni", "text" => "login"));
                break;
        }
    }

    public function install()
    {
        $this->install->createTables();

        $this->load->model("setting/event");
        $this->model_setting_event->addEvent($this->eventGroup, "admin/view/common/column_left/before", $this->modulePath . "/injectAdminMenuItem");
    }

    public function uninstall()
    {
        $this->install->dropTables();

        $this->load->model("setting/event");
        $this->model_setting_event->deleteEventByCode('moloni');
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
