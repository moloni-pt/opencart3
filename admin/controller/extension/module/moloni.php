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
    private $git_user = "nunong21";
    private $git_repo = "opencart3";
    private $git_branch = "master";
    private $updated_files = false;
    private $store_id = "0";
    private $settings;
    private $document_type;
    public $data;

    public function __construct($registry)
    {
        parent::__construct($registry);
    }

    public function __start()
    {
        $this->__modelHandler();

        $this->load->library("moloni");
        $this->data = $this->load->language('extension/module/moloni');

        $this->store_id = isset($this->request->get["store_id"]) ? $this->request->get["store_id"] : 0;

        if (isset($this->request->post["username"]) && isset($this->request->post["password"])) {
            $this->moloni->username = $_POST["username"];
            $this->moloni->password = $_POST["password"];
        }

        if (isset($this->request->get["action"]) && $this->request->get["action"] == 'logout') {
            $this->ocdb->qDeleteMoloniTokens();
        }

        if (isset($this->request->get["company_id"])) {
            $this->ocdb->qUpdateMoloniCompany($this->request->get["company_id"]);
        }

        $tokens = $this->ocdb->qGetMoloniTokens();
        $this->moloni->client_id = "devapi";
        $this->moloni->client_secret = "53937d4a8c5889e58fe7f105369d9519a713bf43";
        $this->moloni->access_token = !empty($tokens['access_token']) ? $tokens['access_token'] : false;
        $this->moloni->refresh_token = !empty($tokens['refresh_token']) ? $tokens['refresh_token'] : false;
        $this->moloni->expire_date = !empty($tokens['expire_date']) ? $tokens['expire_date'] : "";
        $this->moloni->company_id = !empty($tokens['company_id']) ? $tokens['company_id'] : false;

        $this->moloni->verifyTokens();
        if ($this->moloni->updated_tokens) {
            if ($tokens) {
                $tokens = $this->ocdb->qUpdateMoloniTokens($this->moloni->access_token, $this->moloni->refresh_token, $this->moloni->expire_date);
            } else {
                $tokens = $this->ocdb->qInsertMoloniTokens($this->moloni->access_token, $this->moloni->refresh_token, $this->moloni->expire_date);
            }
        }

        /* Save settings from the settings form */
        if (isset($this->request->post["store_id"]) && is_array($this->request->post['moloni'])) {
            $this->setSettings($_POST['moloni'], $this->store_id);
        }

        $this->data['options'] = $this->setMoloniSettings();
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
        $this->__start();
        if ($this->allowed()) {
            $this->page = "home";
        }
        $this->loadDefaults();
        $this->response->setOutput($this->load->view($this->modulePathView . $this->page, $this->data));
    }

    public function settings()
    {
        $this->__start();
        if ($this->allowed()) {

            if ($this->ocdb->getTotalStores() > 0 && !isset($this->request->get['store_id'])) {
                $this->data['content'] = $this->getStoreListData();
                $this->page = "store_list";
            } else {
                $this->data['content'] = $this->getSettingsData();
                $this->page = "settings";
            }
        }

        $this->loadDefaults();
        $this->response->setOutput($this->load->view($this->modulePathView . $this->page, $this->data));
    }

    private function allowed()
    {
        if ($this->moloni->logged) {
            if ($this->moloni->company_id) {
                return true;
            } else {
                $this->getCompaniesAll();
                $this->page = "companies";
            }
        } else {
            $this->page = "login";
        }
    }

    private function loadDefaults()
    {

        $this->data['header'] = $this->load->controller('common/header');
        $this->data['footer'] = $this->load->controller('common/footer');
        $this->data['column_left'] = $this->load->controller('common/column_left');

        $this->data['url'] = $this->defaultTemplateUrls();
        $this->data['breadcrumbs'] = $this->createBreadcrumbs();
        $this->data['document_types'] = $this->getDocumentTypes();

        $this->data['debug_window'] = $this->moloni->debug->getLogs("all");
        $this->data['error_warnings'] = $this->moloni->errors->getError("all");
        $this->data['update_result'] = $this->updated_files;
    }

    private function defaultTemplateUrls()
    {
        $url = array();
        $url['login']['form'] = $this->url->link('extension/module/moloni', array('user_token' => $this->session->data['user_token']), true);
        $url['logout'] = $this->url->link('extension/module/moloni', array("action" => "logout", 'user_token' => $this->session->data['user_token']), true);
        $url['settings']['save'] = $this->url->link('extension/module/moloni/settings', array("store_id" => (isset($_GET['store_id']) ? $_GET['store_id'] : 0), "action" => "save", 'user_token' => $this->session->data['user_token']), true);
        $url['settings']['cancel'] = $this->url->link('extension/module/moloni/settings', array('user_token' => $this->session->data['user_token']), true);
        return $url;
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
            case "home":
                $breadcrumbs[] = array("text" => "Home", 'href' => $this->url->link('extension/module/moloni', array("page" => "home", 'user_token' => $this->session->data['user_token']), true));
                $breadcrumbs[] = array("text" => "Orders", 'href' => $this->url->link('extension/module/moloni', array("page" => "home", 'user_token' => $this->session->data['user_token']), true));
                break;
            case "store_list":
                $breadcrumbs[] = array("text" => "Settings", 'href' => $this->url->link('extension/module/moloni', array("page" => "settings", 'user_token' => $this->session->data['user_token']), true));
                $breadcrumbs[] = array("text" => "Choose your store", 'href' => $this->url->link('extension/module/moloni', array("page" => "settings", 'user_token' => $this->session->data['user_token']), true));
                break;
            case "settings":
                $breadcrumbs[] = array("text" => "Settings", 'href' => $this->url->link('extension/module/moloni', array("page" => "settings", 'user_token' => $this->session->data['user_token']), true));
                $breadcrumbs[] = array("text" => "Stores", 'href' => $this->url->link('extension/module/moloni', array("page" => "settings", 'user_token' => $this->session->data['user_token']), true));
                $breadcrumbs[] = array("text" => "Edit store settings", 'href' => $this->url->link('extension/module/moloni', array("page" => "settings", "store_id" => (isset($_GET['store_id']) ? $_GET['store_id'] : 0), 'user_token' => $this->session->data['user_token']), true));
                break;
            default :
                $breadcrumbs[] = (array("href" => "extension/module/moloni", "text" => "login"));
                break;
        }
        return $breadcrumbs;
    }

    private function getCompaniesAll()
    {
        $this->data['companies'] = $this->moloni->companies->getAll();
        foreach ($this->data['companies'] as &$company) {
            $company['select_url'] = $this->url->link('extension/module/moloni', array('company_id' => $company['company_id'], 'user_token' => $this->session->data['user_token']), true);
        }
    }

    private function getDocumentTypes()
    {
        $this->document_type["invoices"] = array("name" => "invoices", "url" => "Faturas");
        $this->document_type["invoiceReceipts"] = array("name" => "invoiceReceipts", "url" => "FaturasRecibo");
        $this->document_type["simplifiedInvoices"] = array("name" => "simplifiedInvoices", "url" => "FaturaSimplificada");
        $this->document_type["billsOfLading"] = array("name" => "billsOfLading", "url" => "GuiasTransporte");
        $this->document_type["deliveryNotes"] = array("name" => "deliveryNotes", "url" => "NotasEncomenda");
        $this->document_type["internalDocuments"] = array("name" => "internalDocuments", "url" => "DocumentosInternos");
        $this->document_type["estimates"] = array("name" => "estimates", "url" => "Orcamentos");

        return $this->document_type;
    }

    private function getSettingsData()
    {
        $data = array();
        $data['store_id'] = $this->store_id;
        $data['settings_values']['document_sets'] = $this->moloni->document_sets->getAll();
        $data['settings_values']['document_types'] = $this->getDocumentTypes();
        $data['settings_values']['document_status'] = array("0" => "draft", "1" => "closed");

        $data['settings_values']['products_taxes'] = $this->moloni->taxes->getAll();
        $data['settings_values']['products_exemptions'] = $this->moloni->taxes->getExemptions();
        $data['settings_values']['products_at_categories'] = array();
        $data['settings_values']['products_at_categories'][] = array("code" => "M", "name" => "Mercadorias");
        $data['settings_values']['products_at_categories'][] = array("code" => "P", "name" => "Matérias-primas, subsidiárias e de consumo");
        $data['settings_values']['products_at_categories'][] = array("code" => "A", "name" => "Produtos acabados e intermédios");
        $data['settings_values']['products_at_categories'][] = array("code" => "S", "name" => "Subprodutos, desperdícios e refugos");
        $data['settings_values']['products_at_categories'][] = array("code" => "T", "name" => "Produtos e trabalhos em curso");

        $this->load->model('localisation/order_status');

        $data['settings_values']['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        return $data;
    }

    private function getStoreListData()
    {
        $data = array();
        $data['stores'][] = array(
            'store_id' => 0,
            'name' => $this->config->get('config_name') . $this->language->get('text_default'),
            'url' => $this->config->get('config_secure') ? HTTPS_CATALOG : HTTP_CATALOG,
            'edit' => $this->url->link('extension/module/moloni/settings', array("store_id" => "0", 'user_token' => $this->session->data['user_token']), true)
        );

        $stores = $this->ocdb->getStores();
        foreach ($stores as $store) {
            $data['stores'][] = array(
                'store_id' => $store['store_id'],
                'name' => $store['name'],
                'url' => $store['url'],
                'edit' => $this->url->link('extension/module/moloni/settings', array("store_id" => $store['store_id'], 'user_token' => $this->session->data['user_token']), true)
            );
        }

        return $data;
    }

    private function setMoloniSettings()
    {
        $this->settings = array();
        $settings = $this->ocdb->getMoloniSettings($this->moloni->company_id, $this->store_id);
        foreach ($settings as $setting) {
            switch ($setting['label']) {
                case "order_statuses":
                    $this->settings[$setting["label"]] = json_decode($setting["value"], true);
                    break;
                default:
                    $this->settings[$setting["label"]] = $setting["value"];
                    break;
            }
        }
        return $this->settings;
    }

    private function setSettings($settings, $store_id = 0)
    {
        if (is_array($settings)) {
            foreach ($settings as $name => $value) {

                if (is_array($value)) {
                    $value = json_encode($value);
                }

                if ($this->ocdb->qExistsSetting($name, $store_id, $this->moloni->company_id)) {
                    $this->ocdb->qUpdateMoloniSetting($name, $store_id, $this->moloni->company_id, $value);
                } else {
                    $this->ocdb->qInsertMoloniSetting($name, $store_id, $this->moloni->company_id, $value);
                }
            }
        }
    }

    private function update($method = "github")
    {
        switch ($method) {
            case "github":
                $this->githubUpdate();
                break;
        }
    }

    private function githubUpdate()
    {
        $settingsRaw = $this->curl("https://api.github.com/repos/" . $this->git_user . "/" . $this->git_repo . "/branches/" . $this->git_branch);
        $settings = json_decode($settingsRaw, true);
        if (isset($settings['commit'])) {
            $treeRaw = $this->curl("https://api.github.com/repos/" . $this->git_user . "/" . $this->git_repo . "/git/trees/" . $settings['commit']['sha'] . "?recursive=1");
            $tree = json_decode($treeRaw, true);
            foreach ($tree['tree'] as $file) {
                $file_info = pathinfo($file['path']);
                if ($file['type'] == "blob" && isset($file_info['extension']) && in_array($file_info['extension'], array("php", "twigg", "css"))) {
                    $raw = $this->curl("https://raw.githubusercontent.com/" . $this->git_user . "/" . $this->git_repo . "/" . $this->git_branch . "/" . $file['path']);
                    if ($raw) {
                        $this->updated_files['true'][] = $path = str_replace("/admin", "", DIR_APPLICATION) . $file['path'];
                        file_put_contents($path, $raw, LOCK_EX);
                    } else {
                        $this->updated_files['false'] = str_replace("/admin", "", DIR_APPLICATION) . $file['path'];
                    }
                }
            }
        } else {
            $this->updated_files['false'] = $settings['message'];
        }
    }

    private function curl($url, $values = false)
    {
        $con = curl_init();
        curl_setopt($con, CURLOPT_URL, $url);
        curl_setopt($con, CURLOPT_HEADER, false);
        curl_setopt($con, CURLOPT_HTTPHEADER, array(
            'Accept: application/json',
            'Content-Type: application/json',
            'User-Agent: Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.111 YaBrowser/16.3.0.7146 Yowser/2.5 Safari/537.36'
        ));
        curl_setopt($con, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($con, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($con, CURLOPT_SSL_VERIFYPEER, true);
        //curl_setopt($con, CURLOPT_USERPWD, "user:pwd");

        $result = curl_exec($con);
        if (curl_errno($con)) {
            echo $result;
            $result = false;
        }

        curl_close($con);
        return $result;
    }
    /*     * ******* INSTAL FUNCTIONS *********** */

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
                'href' => $this->url->link('extension/module/moloni/documents', array("page" => "documents", 'user_token' => $this->session->data['user_token']), true),
                'children' => array()
            );

            $moloni[] = array(
                'name' => $this->language->get('Settings'),
                'href' => $this->url->link('extension/module/moloni/settings', array("page" => "settings", 'user_token' => $this->session->data['user_token']), true),
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
