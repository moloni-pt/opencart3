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

        $this->load->library("moloni");

        if ($_GET['page'] == "login" && isset($_POST["username"]) && isset($_POST["password"])) {
            $this->moloni->username = $_POST["username"];
            $this->moloni->password = $_POST["password"];
        }

        $tokens = $this->ocdb->qGetMoloniTokens();
        $this->moloni->client_id = "devapi";
        $this->moloni->client_secret = "53937d4a8c5889e58fe7f105369d9519a713bf43";
        $this->moloni->access_token = !empty($tokens['access_token']) ? $tokens['access_token'] : false;
        $this->moloni->refresh_token = !empty($tokens['refresh_token']) ? $tokens['refresh_token'] : false;
        $this->moloni->expire_date = !empty($tokens['expire_date']) ? $tokens['expire_date'] : "";

        $this->moloni->verifyTokens();
        if ($this->moloni->updated_tokens) {
            if ($tokens) {
                $tokens = $this->ocdb->qUpdateMoloniTokens($this->moloni->access_token, $this->moloni->refresh_token, $this->moloni->expire_date);
            } else {
                $tokens = $this->ocdb->qInsertMoloniTokens($this->moloni->access_token, $this->moloni->refresh_token, $this->moloni->expire_date);
            }
        }

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
                $data['companies'] = $this->moloni->companies->getAll();
                $this->page = "companies";
            }
        } else {
            $data['login_form_url'] = $this->url->link('extension/module/moloni', array("page" => "login", 'user_token' => $this->session->data['user_token']), true);
            $this->page = "login";
        }

        $data['breadcrumbs'] = $this->createBreadcrumbs();
        $data['debug_window'] = $this->moloni->debug->getLogs("all");
        $data['error_warnings'] = $this->moloni->errors->getError("all");
        $this->response->setOutput($this->load->view($this->modulePathView . $this->page, $data));
    }

    private function createBreadcrumbs()
    {
        switch ($this->page) {
            case "login":
                $breadcrumbs[] = array("text" => "Login", 'href' => $this->url->link('extension/module/moloni', array("page" => "home", 'user_token' => $this->session->data['user_token']), true));
                break;
            case "companies":
                $breadcrumbs[] = array("text" => "Empresas", 'href' => $this->url->link('extension/module/moloni', array("page" => "home", 'user_token' => $this->session->data['user_token']), true));
                break;
            default :
                $breadcrumbs[] = (array("href" => "extension/module/moloni", "text" => "login"));
                break;
        }

        return $breadcrumbs;
    }

    public function update($method = "github")
    {
        switch ($method) {
            case "github":
                $this->githubUpdate();
                break;
        }
    }

    public function githubUpdate()
    {
        $result = $this->getGithubFile("modulefiles");
        $files_list = array_filter(explode("\n", $result));
        foreach ($files_list as $file) {
            $raw = $this->getGithubFile($file);
            file_put_contents(str_replace("/admin", "", DIR_APPLICATION) . $file, $raw, LOCK_EX);
        }
    }

    public function getGithubFile($file, $branch = "master")
    {
        $con = curl_init();
        $url = "https://raw.githubusercontent.com/nunong21/opencart3/" . $branch . "/" . $file;
        curl_setopt($con, CURLOPT_URL, $url);
        curl_setopt($con, CURLOPT_HEADER, false);
        curl_setopt($con, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($con, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($con, CURLOPT_SSL_VERIFYPEER, true);

        $result = curl_exec($con);
        curl_close($con);

        return $result;
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
