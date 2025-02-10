<?php

/**
 * Class ControllerExtensionModuleMoloni
 *
 * @property  moloni $moloni
 * @property  ModelExtensionModuleMoloniOcdb $ocdb
 * @property  Loader $load
 */
class ControllerExtensionModuleMoloni extends Controller
{
    private $moduleName = 'moloni';
    private $modulePathBase = 'extension/module/moloni/';
    private $modulePathView = 'extension/module/moloni/';
    public $modelsRequired = array(
        'install' => 'model_extension_module_moloni_install',
        'ocdb' => 'model_extension_module_moloni_ocdb'
    );
    public $data;
    private $eventGroup = 'moloni';
    private $version = 'v1.03';
    private $git_user = 'moloni';
    private $git_repo = 'opencart3';
    private $git_branch = 'master';
    private $updated_files = false;
    private $update_available = false;
    private $store_id = '0';
    private $settings;
    private $document_type;
    private $_myOrder;
    private $messages = [];
    private $hasNegative = false;
    private $categoriesCache = [];

    /**
     * @throws Exception
     */
    public function __construct($registry)
    {
        parent::__construct($registry);

        if (isset($this->request->get['update']) &&
            $this->request->get['update'] &&
            strpos($this->request->get['route'], 'extension/module/moloni') !== false) {
            $this->update();
        }

        $this->modelHandler();
    }

    /**
     * @throws Exception
     */
    public function start()
    {
        $this->load->library('moloni');
        $this->data = $this->load->language('extension/module/moloni');

        $this->store_id = isset($this->request->get['store_id']) ? $this->request->get['store_id'] : 0;

        if (isset($this->request->post['username']) && isset($this->request->post['password'])) {
            $this->moloni->username = $this->request->post['username'];
            $this->moloni->password = $this->request->post['password'];
        }

        if (isset($this->request->get['action']) && $this->request->get['action'] === 'logout') {
            $this->ocdb->qDeleteMoloniTokens();
        }

        if (isset($this->request->get['company_id'])) {
            $this->ocdb->qUpdateMoloniCompany($this->request->get['company_id']);
        }

        $tokens = $this->ocdb->qGetMoloniTokens();
        $this->moloni->client_id = 'devapi';
        $this->moloni->client_secret = '53937d4a8c5889e58fe7f105369d9519a713bf43';
        $this->moloni->access_token = !empty($tokens['access_token']) ? $tokens['access_token'] : false;
        $this->moloni->refresh_token = !empty($tokens['refresh_token']) ? $tokens['refresh_token'] : false;
        $this->moloni->expire_date = !empty($tokens['expire_date']) ? $tokens['expire_date'] : '';
        $this->moloni->company_id = !empty($tokens['company_id']) ? $tokens['company_id'] : false;

        $this->moloni->verifyTokens();
        if ($this->moloni->updated_tokens) {
            if ($tokens) {
                $this->ocdb->qUpdateMoloniTokens($this->moloni->access_token, $this->moloni->refresh_token, $this->moloni->expire_date);
            } else {
                $this->ocdb->qInsertMoloniTokens($this->moloni->access_token, $this->moloni->refresh_token, $this->moloni->expire_date);
            }
        }

        /* Save settings from the settings form */
        if (isset($this->request->post['store_id']) && is_array($this->request->post['moloni'])) {
            $this->setSettings($_POST['moloni'], $this->store_id);
        }

        $this->data['options'] = $this->settings = $this->getMoloniSettings();
    }

    /**
     * Handles model call
     *
     * @return void
     *
     * @throws Exception
     */
    public function modelHandler()
    {
        if (isset($this->modelsRequired) && is_array($this->modelsRequired)) {
            foreach ($this->modelsRequired as $name => $model) {
                $this->load->model($this->modulePathBase . $name);
                $this->{$name} = $this->{$model};
            }
        }
    }

    public function scriptHandler()
    {
        $this->document->addScript('view/javascript/moloni/compiled.min.js');
        $this->document->addStyle('view/stylesheet/moloni/compiled.min.css');
    }

    /**             Pages              */

    /**
     * Orders page
     *
     * @return void
     *
     * @throws Exception
     */
    public function index()
    {
        $this->start();
        $this->scriptHandler();
        $this->versionCheck();

        if ($this->allowed()) {
            $this->page = 'home';
            $this->data['content'] = $this->getIndexData();
        }

        $this->loadDefaults();
        $this->response->setOutput($this->load->view($this->modulePathView . $this->page, $this->data));
    }

    /**
     * Settings page
     *
     * @return void
     *
     * @throws Exception
     */
    public function settings()
    {
        $this->start();
        $this->scriptHandler();

        if ($this->allowed()) {
            if ($this->ocdb->getTotalStores() > 0 && !isset($this->request->get['store_id'])) {
                $this->data['content'] = $this->getStoreListData();
                $this->page = 'store_list';
            } else {
                $this->data['content'] = $this->getSettingsData();
                $this->page = 'settings';
            }
        }

        $this->loadDefaults();
        $this->response->setOutput($this->load->view($this->modulePathView . $this->page, $this->data));
    }

    /**
     * Documents page
     *
     * @return void
     *
     * @throws Exception
     */
    public function documents()
    {
        $this->start();
        $this->scriptHandler();

        if ($this->allowed()) {
            $this->page = 'documents';
            $this->data['content'] = $this->getDocumentsData();
        }

        $this->loadDefaults();
        $this->response->setOutput($this->load->view($this->modulePathView . $this->page, $this->data));
    }

    public function invoice()
    {
        $this->start();
        $this->scriptHandler();

        if((isset($this->request->get['evento']) && $this->request->get['evento'] == 'moloni' && isset($this->settings['order_auto']) && $this->settings['order_auto']) || (!isset($this->request->get['evento']) || empty($this->request->get['evento']))){
            if ($this->allowed()) {
                $this->page = 'home';

                $order_id = $this->request->get['order_id'];

                if ($order_id) {
                    $parsed = json_decode($order_id, true);
                    if (is_array($parsed)) {
                        foreach ($parsed as $order_id) {
                            $this->createDocumentFromOrder($order_id);
                        }
                    } else {
                        $this->createDocumentFromOrder($order_id);
                    }
                }

                $this->data['content'] = $this->getIndexData();
            }

            $this->loadDefaults();
            if(isset($this->request->get['evento']) && $this->request->get['evento'] == 'moloni'){
                $json['error'] = isset($this->data['messages']['errors']) ? implode(" - ", $this->data['messages']['errors'][0]) : NULL;
                $json['success'] = isset($this->data['messages']['success']) ? implode(" - ",$this->data['messages']['success'][0]) : NULL;
                $this->response->setOutput(json_encode($json));
            } else {
                $this->response->setOutput($this->load->view($this->modulePathView . $this->page, $this->data));
            }
        } else {
            $json['success'] = 'Success: You have modified orders!';

            $this->response->setOutput(json_encode($json));
        }
    }

    /**
     * Action that imports products from Moloni account
     *
     * @throws Exception
     */
    public function importProducts()
    {
        $this->start();
        $this->scriptHandler();

        if ($this->allowed()) {
            $offset = 0;
            $lastModified = 0;
            $failSafe = 10000;

            if (isset($_POST['moloni']['import_product_since_date_hidden'])) {
                $lastModified = $_POST['moloni']['import_product_since_date_hidden'];
            }

            $this->load->model('catalog/product');
            $this->load->model('catalog/category');
            $this->load->model('localisation/stock_status');

            $this->data['artigos_importados']['count'] = 0; // Quantos artigos foram importados
            $this->data['artigos_atualizados']['count'] = 0; // Quantos artigos foram atualizados

            try {
                do{
                    $moloniProducts = $this->moloni->products->getModifiedSince(false, $offset, $lastModified);
                    $gotThemAll = (count($moloniProducts) === 50);

                    foreach($moloniProducts as $moloniProduct){
                        $openCartProducts = $this->ocdb->getProductsByReference($moloniProduct['reference']);

                        //Only sync simple products
                        if (empty($openCartProducts) && (int)$moloniProduct['composition_type'] !== 0) {
                            continue;
                        }

                        $productToSave = [];
                        $this->importProduct($productToSave, $moloniProduct, $openCartProducts);

                        if(empty($openCartProducts)){
                            $this->model_catalog_product->addProduct($productToSave);

                            $this->data['artigos_importados']['count']++;
                            $this->data['artigos_importados']['references'][] = $productToSave['sku'];
                        } else {
                            $this->model_catalog_product->editProduct($productToSave['product_id'], $productToSave);

                            $this->data['artigos_atualizados']['count']++;
                            $this->data['artigos_atualizados']['references'][] = $productToSave['sku'];
                        }
                    }

                    $offset += 50;
                } while ($gotThemAll && $failSafe > $offset);
            } catch (ErrorException $e){
                $this->toolWriteLog($e);
            }

            if ($this->data['artigos_importados']['count'] === 0 && $this->data['artigos_atualizados']['count'] === 0) {
                $this->messages['errors'] = [
                    'title' => $this->language->get('alert'),
                    'message' => $this->language->get('no_products_found'),
                ];
            } else {
                $this->messages['success'] = [
                    'title' => $this->language->get('success'),
                    'message' => $this->language->get('products_import_success'),
                ];
            }
        }

        $this->page = 'settings';
        $this->data['content'] = $this->getSettingsData();

        $this->loadDefaults();

        $this->response->setOutput($this->load->view($this->modulePathView . $this->page, $this->data));
    }

    /**             Ajax requests              */

    public function getOrders()
    {
        $json = [];

        $data['orders_list'][0] = $this->ocdb->getOrdersAll(@$this->settings['order_statuses'], @$this->settings['order_since']);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**             Privates              */

    /**
     * Prepares Moloni product to be saved
     *
     * @param array $productToSave Product to save
     * @param array $moloniProduct Moloni product data
     * @param array $openCartProduct Opencart product data
     *
     * @return void
     */
    private function importProduct(&$productToSave, $moloniProduct, $openCartProduct)
    {
        $opencartCategoryId = $this->importProductCategories($moloniProduct);
        $languageId = $this->config->get('config_language_id');

        $productToSave['product_store'][0] = $this->store_id;
        $productToSave['product_category'][0] = $opencartCategoryId;

        if (!empty($openCartProduct)) {
            // The arg is an array of arrays
            $openCartProduct = $openCartProduct[0];
            $productToSave['product_id'] = $openCartProduct['product_id'];
            $productToSave['model'] = $openCartProduct['model'];
            $productToSave['sku'] = $openCartProduct['sku'];
            $productToSave['minimum'] = $openCartProduct['minimum'];
            $productToSave['stock_status_id'] = $openCartProduct['stock_status_id'];
            $productToSave['tax_class_id'] = $openCartProduct['tax_class_id'];
            $productToSave['weight_class_id'] = $openCartProduct['weight_class_id'];
            $productToSave['length_class_id'] = $openCartProduct['length_class_id'];

            $this->importProductSecureConnections($productToSave);


            if(isset($_POST['moloni']['update_import_products_stock_hidden']) && $_POST['moloni']['update_import_products_stock_hidden']){
                $productToSave['quantity'] = (float)$moloniProduct['stock'];
            } else {
                $productToSave['quantity'] = $openCartProduct['quantity'];
            }

            if(isset($_POST['moloni']['update_import_products_price_hidden']) && $_POST['moloni']['update_import_products_price_hidden']){
                $productToSave['price'] = round($moloniProduct['price'], 4);
            } else {
                $productToSave['price'] = $openCartProduct['price'];
            }

            if(isset($_POST['moloni']['update_import_products_name_hidden']) && $_POST['moloni']['update_import_products_name_hidden']){
                $productToSave['product_description'][$languageId]['name'] = $moloniProduct['name'];
                $productToSave['product_description'][$languageId]['meta_title'] = $moloniProduct['name'];
            } else {
                $productToSave['product_description'][$languageId]['name'] = $openCartProduct['name'];
                $productToSave['product_description'][$languageId]['meta_title'] = $openCartProduct['meta_title'];
            }

            $productToSave['product_description'][$languageId]['tag'] = $openCartProduct['tag'];
            $productToSave['product_description'][$languageId]['meta_description'] = $openCartProduct['meta_description'];
            $productToSave['product_description'][$languageId]['meta_keyword'] = $openCartProduct['meta_keyword'];
        } else {
            $stockStatuses = $this->model_localisation_stock_status->getStockStatuses();

            $productToSave['model'] = $moloniProduct['reference'];
            $productToSave['sku'] = $moloniProduct['reference'];
            $productToSave['quantity'] = (int)$moloniProduct['stock'];
            $productToSave['minimum'] = (int)$moloniProduct['minimum_stock'];
            $productToSave['product_description'][$languageId]['description'] = $moloniProduct['description'];
            $productToSave['product_description'][$languageId]['tag'] = '';
            $productToSave['product_description'][$languageId]['meta_description'] = '';
            $productToSave['product_description'][$languageId]['meta_keyword'] = '';
            $productToSave['product_description'][$languageId]['name'] = $moloniProduct['name'];
            $productToSave['product_description'][$languageId]['meta_title'] = $moloniProduct['name'];
            $productToSave['weight_class_id'] = $this->config->get('config_weight_class_id');
            $productToSave['length_class_id'] = $this->config->get('config_length_class_id');
            $productToSave['price'] = round($moloniProduct['price'],4);

            $productToSave['stock_status_id'] = (!empty($stockStatuses) && is_array($stockStatuses)) ? ($stockStatuses[0]['stock_status_id']) : 0;
            $productToSave['tax_class_id'] = isset($_POST['moloni']['import_tax_class_hidden']) ? (int)$_POST['moloni']['import_tax_class_hidden'] : 0;
        }

        $productToSave['upc'] = isset($openCartProduct['upc']) ? $openCartProduct['upc'] : '';
        $productToSave['jan'] = isset($openCartProduct['jan']) ? $openCartProduct['jan'] : '';
        $productToSave['isbn'] = isset($openCartProduct['isbn']) ? $openCartProduct['isbn'] : '';
        $productToSave['mpn'] = isset($openCartProduct['mpn']) ? $openCartProduct['mpn'] : '';
        $productToSave['location'] = isset($openCartProduct['location']) ? $openCartProduct['location'] : 0;
        $productToSave['subtract'] = isset($openCartProduct['subtract']) ? $openCartProduct['subtract'] : 1;
        $productToSave['date_available'] = isset($openCartProduct['date_available']) ? $openCartProduct['date_available'] : date('Y-m-d');
        $productToSave['manufacturer_id'] = isset($openCartProduct['manufacturer_id']) ? $openCartProduct['manufacturer_id'] : 0;
        $productToSave['shipping'] = isset($openCartProduct['shipping']) ? $openCartProduct['shipping'] : 1;
        $productToSave['points'] = isset($openCartProduct['points']) ? $openCartProduct['points'] : 0;
        $productToSave['weight'] = isset($openCartProduct['weight']) ? $openCartProduct['weight'] : 0;
        $productToSave['length'] = isset($openCartProduct['length']) ? $openCartProduct['length'] : 0;
        $productToSave['width'] = isset($openCartProduct['width']) ? $openCartProduct['width'] : 0;
        $productToSave['height'] = isset($openCartProduct['height']) ? $openCartProduct['height'] : 0;
        $productToSave['status'] = isset($openCartProduct['status']) ? $openCartProduct['status'] : 1;
        $productToSave['sort_order'] = isset($openCartProduct['sort_order']) ? $openCartProduct['sort_order'] : 0;

        $productToSave['ean'] = $moloniProduct['ean'];
        $productToSave['product_description'][$languageId]['description'] = $moloniProduct['summary'];

        $this->importProductImage($productToSave, $moloniProduct, $openCartProduct);
    }

    /**
     * Gets the category tree from a Moloni product and creates it in Opencart
     *
     * @param array $moloniProduct Moloni product values
     *
     * @return int Opencart category id (Last id from the tree)
     */
    private function importProductCategories($moloniProduct)
    {
        $moloniCategories = $this->moloni->products->getProductCategoryTree($moloniProduct['product_id']);

        $parentCategoryId = 0;

        foreach ($moloniCategories as $moloniCategory) {
            $currentCategoryId = null;

            // Check cache to get current Moloni category link
            if (isset($this->categoriesCache[$moloniCategory['category_id']])) {
                $currentCategoryId = $this->categoriesCache[$moloniCategory['category_id']];
                $parentCategoryId = $this->categoriesCache[$moloniCategory['category_id']];

                continue;
            }

            $opencartCategories = $this->model_catalog_category->getCategories(['filter_name' => $moloniCategory['name']]);

            foreach ($opencartCategories as $opencartCategory) {
                if ((int)$opencartCategory['parent_id'] === $parentCategoryId) {
                    $currentCategoryId = (int)$opencartCategory['category_id'];
                    break;
                }
            }

            if ($currentCategoryId === null) {
                $data['parent_id'] = $parentCategoryId;
                $data['column'] = 1;
                $data['sort_order'] = 0;
                $data['status'] = 1;
                $data['category_store'][0] = $this->store_id;
                $data['category_description'][$this->config->get('config_language_id')]['name'] = $moloniCategory['name'];
                $data['category_description'][$this->config->get('config_language_id')]['meta_title'] = $moloniCategory['name'];
                $data['category_description'][$this->config->get('config_language_id')]['description'] = $moloniCategory['name'];
                $data['category_description'][$this->config->get('config_language_id')]['meta_description'] = '';
                $data['category_description'][$this->config->get('config_language_id')]['meta_keyword'] = '';

                $currentCategoryId = $this->model_catalog_category->addCategory($data);
            }

            // Set next parent to current ID
            $parentCategoryId = $currentCategoryId;

            // Save results in cache
            $this->categoriesCache[$moloniCategory['category_id']] = $currentCategoryId;
        }

        return isset($currentCategoryId) ? $currentCategoryId : 0;
    }

    /**
     * Imports Moloni image if necessary
     *
     * @param array $productToSave Product to save
     * @param array $moloniProduct Moloni product data
     * @param array $openCartProduct Opencart product data
     *
     * @return void
     */
    private function importProductImage(&$productToSave, $moloniProduct, $openCartProduct)
    {
        $productToSave['product_image'] = [];

        if (isset($openCartProduct['product_id'])) {
            $productToSave['product_image'] = $this->model_catalog_product->getProductImages($openCartProduct['product_id']);
        }

        // Check if user wants to sync Moloni Product image
        if (!empty($openCartProduct) && (!isset($_POST['moloni']['update_import_products_image_hidden']) || !$_POST['moloni']['update_import_products_image_hidden'])) {
            return;
        }

        $openCartImageName = null;
        $moloniImageName = null;

        if(isset($openCartProduct['image']) && !empty($openCartProduct['image'])){
            $openCartImageName = explode('/', $openCartProduct['image']);
            $openCartImageName = end($openCartImageName);
        }

        if(isset($moloniProduct['image']) && !empty($moloniProduct['image'])){
            $moloniImageName = explode('/', $moloniProduct['image'], 3);
            $moloniImageName = end($moloniImageName);
        }

        if ($moloniImageName !== null && $moloniImageName !== $openCartImageName) {
            $productToSave['image'] = $this->moloni->connection->getMoloniImage($moloniProduct['image']);

            $imagesCount = count($productToSave['product_image']);

            $productToSave['product_image'][$imagesCount]['sort_order'] = $imagesCount;
            $productToSave['product_image'][$imagesCount]['image'] = $productToSave['image'];
        }
    }

    /**
     * Maintain important connections
     *
     * @param array $productToSave New product to save
     *
     * @return void
     */
    private function importProductSecureConnections(&$productToSave)
    {
        $productToSave['product_attribute'] = $this->model_catalog_product->getProductAttributes($productToSave['product_id']);
        $productToSave['product_description'] = $this->model_catalog_product->getProductDescriptions($productToSave['product_id']);
        $productToSave['product_discount'] = $this->model_catalog_product->getProductDiscounts($productToSave['product_id']);
        $productToSave['product_filter'] = $this->model_catalog_product->getProductFilters($productToSave['product_id']);
        $productToSave['product_image'] = $this->model_catalog_product->getProductImages($productToSave['product_id']);
        $productToSave['product_option'] = $this->model_catalog_product->getProductOptions($productToSave['product_id']);
        $productToSave['product_related'] = $this->model_catalog_product->getProductRelated($productToSave['product_id']);
        $productToSave['product_reward'] = $this->model_catalog_product->getProductRewards($productToSave['product_id']);
        $productToSave['product_special'] = $this->model_catalog_product->getProductSpecials($productToSave['product_id']);
        $productToSave['product_download'] = $this->model_catalog_product->getProductDownloads($productToSave['product_id']);
        $productToSave['product_layout'] = $this->model_catalog_product->getProductLayouts($productToSave['product_id']);
        $productToSave['product_store'] = $this->model_catalog_product->getProductStores($productToSave['product_id']);
        $productToSave['product_recurring'] = $this->model_catalog_product->getRecurrings($productToSave['product_id']);
        $productToSave['product_seo_url'] = $this->model_catalog_product->getProductSeoUrls($productToSave['product_id']);
    }

    private function allowed()
    {
        if ($this->moloni->logged) {
            if ($this->moloni->company_id) {
                return true;
            } else {
                $this->getCompaniesAll();
                $this->page = 'companies';
            }
        } else {
            $this->page = 'login';
        }
    }

    private function createDocumentFromOrder($order_id)
    {
        if (isset($this->request->get['action']) && $this->request->get['action'] === 'delete') {
            $values['order_id'] = $order_id;
            $values['invoice_id'] = '10';
            $this->ocdb->setDocumentInserted($values);
            return false;
        }

        $this->load->model('sale/order');
        $this->load->model('catalog/product');
        $this->current_order = $order = $this->model_sale_order->getOrder($order_id);
        $this->moloni_taxes = $this->moloni->taxes->getAll();

        $this->store_id = $order['store_id'];
        $this->language_id = $this->ocdb->language_id = $order['language_id'];

        $moloni_document = $this->ocdb->getDocumentFromOrderId($order_id);
        if (!$moloni_document || isset($this->request->get['force'])) {

            $this->settings = $this->getMoloniSettings();
            $this->company = $this->moloni->companies->getOne();
            $this->countries = $this->moloni->countries->getAll();

            $oc_products = $this->model_sale_order->getOrderProducts($order_id);
            $oc_totals = $this->model_sale_order->getOrderTotals($order_id);

            $this->_myOrder['has_exchange'] = ($this->company['currency_id'] == 1 && $this->ocdb->getStoreCurrency($this->store_id) !== 'EUR' ? true : false);
            $this->_myOrder['currency'] = $order['currency_code'];

            $customer = $this->moloniCustomerHandler($order);
            $discounts = $this->toolsDiscountsHandlers($oc_totals);

            foreach ($oc_products as $key => &$product) {
                $product['order_id'] = $order_id;
                $product['discount'] = $discounts['products'];
                $products[] = $this->moloniProductHandler($product, $key);
            }

            $extras = $this->moloniShippingHandler($oc_totals, (count($products) + 1), $discounts);
            if (!empty($extras) && is_array($extras)) {
                $products = array_merge($products, $extras);
            }

            $document = array();
            $document['date'] = date('Y-m-d');
            $document['expiration_date'] = date('Y-m-d');
            $document['document_set_id'] = $this->settings['document_set_id'];

            $document['customer_id'] = $customer;
            $document['alternate_address_id'] = (isset($customer['alternate_address_id']) ? $customer['alternate_address_id'] : '');

            $document['products'] = $products;

            $document['our_reference'] = '#' . $order_id;
            $document['your_reference'] = '#' . $order_id;

            $document['financial_discount'] = $discounts['document'];
            $document['special_discount'] = '0';
            $document['eac_id'] = '';

            if ($this->_myOrder['has_exchange']) {
                $document['exchange_currency_id'] = $this->toolsExchangeHandler('EUR', $order['currency_code']);
                $document['exchange_rate'] = 1 / ($this->ocdb->getCurrencyValue('EUR'));
            }

            if (isset($this->settings['shipping_details']) && $this->settings['shipping_details'] && $order['shipping_method'] !== '') {
                $document['delivery_method_id'] = $this->toolDeliveryMethodHandler($order['shipping_method']);
                $document['delivery_datetime'] = date('Y-m-d H:i:s');

                if (!isset($this->settings['store_location']) || $this->settings['store_location'] == 0) {
                    $document['delivery_departure_address'] = $this->company['address'];
                    $document['delivery_departure_city'] = $this->company['city'];
                    $document['delivery_departure_zip_code'] = $this->company['zip_code'];
                    $document['delivery_departure_country'] = $this->company['country_id'];
                } else {
                    $location = $this->ocdb->getStoreLocation($this->settings['store_location']);
                    $document['delivery_departure_address'] = $location['address'];
                    $document['delivery_departure_city'] = $location['geocode'];
                    $document['delivery_departure_zip_code'] = '1000-100';
                    $document['delivery_departure_country'] = 1;
                }

                $document['delivery_destination_address'] = empty($order['shipping_address_1']) ? '' : $order['shipping_address_1'];
                $document['delivery_destination_address'] .= empty($order['shipping_address_2']) ? '' : ' ' . $order['shipping_address_2'];
                $document['delivery_destination_city'] = empty($order['shipping_city']) ? '' : $order['shipping_city'];
                $document['delivery_destination_zip_code'] = $order['shipping_iso_code_2'] === 'PT' ? $this->toolValidateZipCode($order['shipping_postcode']) : $order['shipping_postcode'];
                $document['delivery_destination_country'] = $this->toolCountryHandler($order['shipping_iso_code_2']);
            }

            $document['notes'] = '';
            $document['status'] = '0';

            if (!$this->moloni->errors->getError('all')) {
                // Insert document

                if (isset($this->settings['shipping_document']) && $this->settings['shipping_document'] == 1 && $order['shipping_method'] !== '') {
                    $shipping_document_insert = $this->moloni->documents('billsOfLading')->insert($document);
                    if ($shipping_document_insert) {
                        $shipping_document_details = $this->moloni->documents()->getOne($shipping_document_insert['document_id']);
                        if ($this->settings['document_status'] == '1' && !$this->hasNegative) {
                            if ((float)round($shipping_document_details['net_value'], 2) == (float)round($this->_myOrder['net_value'], 2)) {
                                $document['document_id'] = $shipping_document_details['document_id'];
                                $document['status'] = '1';

                                $this->moloni->documents('billsOfLading')->update($document);

                                $document['associated_documents'][] = array(
                                    'associated_id' => $shipping_document_details['document_id'],
                                    'value' => $shipping_document_details['net_value']
                                );
                            } else {
                                $message = 'Os totais não batem certo - moloni '
                                    . $shipping_document_details['net_value'] . '€ | encomenda '
                                    . $this->_myOrder['net_value'] . '€';
                                $link = "<a target='_BLANK' href='https://moloni.pt/" . $this->company['slug'] . '/' .
                                    $this->moloni->documents('billsOfLading')->getViewUrl($shipping_document_details['document_id']) .
                                    "'>ver documento</a>";

                                $this->messages['errors'] = array(
                                    'title' => 'Erro ao inserir documento de transporte',
                                    'message' => $message,
                                    'link' => $link,
                                    'fatal' => 0
                                );
                            }
                        } elseif ($this->hasNegative){
                            $this->messages['errors'] = array(
                                'title' => 'Documento inserido em rascunho pois tem preços a negativo',
                                'message' => 'Documento possui promoções ou taxas a negativo',
                            );
                        }
                    }
                }

                /**
                 * This prevents document to be inserted as closed
                 * (status could be closed if bill of lading was inserted)
                 */
                $document['status'] = 0;

                $insert = $this->moloni->documents($this->settings['document_type'])->insert($document);
                if ($insert) {
                    $document_details = $this->moloni->documents()->getOne($insert['document_id']);
                    if ((round($document_details['net_value'], 2) == round($this->_myOrder['net_value'], 2)) && !$this->hasNegative) {
                        if ($this->settings['document_status'] == '1') {
                            $document_update['document_id'] = $document_details['document_id'];
                            $document_update['status'] = '1';

                            if(isset($this->settings['client_email']) && $this->settings['client_email']){
                                $document_update['send_email'] = [];
                                $document_update['send_email'][] = [
                                    'email' => $order['email'],
                                    'name' => (!empty($order['payment_company']) ? $order['payment_company'] : $order['payment_firstname'] . ' ' . $order['payment_lastname']),
                                    'msg' => ''
                                ];
                            }

                            $this->moloni->documents($this->settings['document_type'])->update($document_update);
                        }
                    } else {
                        $message = ($this->hasNegative) ? 'Encomenda possui taxas/promoções a negativo' : 'Os totais não batem certo - moloni '
                            . $document_details['net_value'] . '€ | encomenda '
                            . $this->_myOrder['net_value'] . '€';
                        $link = "<a target='_BLANK' href='https://moloni.pt/" . $this->company['slug'] . '/' .
                            $this->moloni->documents($this->settings['document_type'])->getViewUrl($document_details['document_id']) .
                            "'>ver documento</a>";

                        $this->messages['errors'] = array(
                            'title' => 'Erro ao inserir documento | Documento inserido em rascunho',
                            'message' => $message,
                            'link' => $link
                        );
                    }

                    $values = array();
                    $values['company_id'] = $this->company['company_id'];
                    $values['store_id'] = $this->store_id;
                    $values['order_id'] = $order_id;
                    $values['order_total'] = $this->_myOrder['net_value'];
                    $values['invoice_id'] = $document_details['document_id'];
                    $values['invoice_total'] = $document_details['net_value'];
                    $values['invoice_date'] = $document['date'];
                    $values['invoice_status'] = isset($document_update['status']) ? $document_update['status'] : $document['status'] = '0';
                    $values['metadata'] = json_encode($document_details);
                    $this->ocdb->setDocumentInserted($values);

                    $link = "<a target='_BLANK' href='https://moloni.pt/" . $this->company['slug'] . '/' .
                        $this->moloni->documents($this->settings['document_type'])->getViewUrl($values['invoice_id'], $values['invoice_status']) .
                        "'>ver documento</a>";

                    $this->messages['success'] = array(
                        'title' => 'Sucesso',
                        'message' => 'Documento inserido com sucesso',
                        'link' => $link
                    );
                }
            }
        } else {

            $this->messages['errors'] = array(
                'title' => 'Erro',
                'message' => 'O documento já tinha sido gerado',
                'link' => "<a href='" . $moloni_url = $this->url->link('extension/module/moloni/invoice', array('order_id' => $order['order_id'], 'force' => 'true', 'user_token' => $this->session->data['user_token']), true) . "'>Gerar novamente</a>"
            );
        }
    }

    private function moloniCustomerHandler($order)
    {

        $moloni_customer_exists = false;

        $order['vat_number'] = '999999990';

        if ($this->settings['client_vat'] > 0) {
            if (isset($order['custom_field'][$this->settings['client_vat']]) && !empty(trim(isset($order['custom_field'][$this->settings['client_vat']])))) {
                $order['vat_number'] = trim($order['custom_field'][$this->settings['client_vat']]);
            } elseif (isset($order['payment_custom_field'][$this->settings['client_vat']]) && !empty(trim($order['payment_custom_field'][$this->settings['client_vat']]))) {
                $order['vat_number'] = trim($order['payment_custom_field'][$this->settings['client_vat']]);
            }
        }

        $order['vat_number'] = str_ireplace('pt', '', $order['vat_number']);

        $order['vat_number'] = trim($order['vat_number']) == '' ? '999999990' : $order['vat_number'];

        $order['payment_entity'] = (!empty($order['payment_company']) ? $order['payment_company'] : $order['payment_firstname'] . ' ' . $order['payment_lastname']);

        if (in_array(trim($order['vat_number']), array('999999990', ''))) {
            $moloni_customer_search = $this->moloni->customers->getBySearch($order['payment_entity'], true);
            if ($moloni_customer_search && is_array($moloni_customer_search)) {
                foreach ($moloni_customer_search as $result) {
                    if ($result['email'] == $order['email'] && $result['vat'] == $order['vat_number']) {
                        $moloni_customer_exists = $result;
                    }
                }
            }
        } else {
            $moloni_customer_exists_aux = $this->moloni->customers->getByVat($order['vat_number'], true);
            $moloni_customer_exists = $moloni_customer_exists_aux[0];
        }

        $moloni_customer['name'] = $order['payment_entity'];
        $moloni_customer['address'] = empty($order['payment_address_1']) ? 'Desconhecido' : $order['payment_address_1'];
        $moloni_customer['address'] .= empty($order['payment_address_2']) ? '' : ' ' . $order['payment_address_2'];
        $moloni_customer['zip_code'] = $order['payment_iso_code_2'] === 'PT' ? $this->toolValidateZipCode($order['payment_postcode']) : $order['payment_postcode'];
        $moloni_customer['city'] = empty($order['payment_city']) ? 'Desconhecida' : $order['payment_city'];

        $moloni_customer['contact_name'] = $order['payment_firstname'] . ' ' . $order['payment_lastname'];
        $moloni_customer['contact_email'] = filter_var($order['email'], FILTER_VALIDATE_EMAIL) ? $order['email'] : '';
        $moloni_customer['contact_phone'] = trim($order['telephone']);

        $moloni_customer['email'] = filter_var($order['email'], FILTER_VALIDATE_EMAIL) ? $order['email'] : '';
        $moloni_customer['phone'] = $order['telephone'];
        $moloni_customer['website'] = '';
        $moloni_customer['fax'] = '';

        $moloni_customer['maturity_date_id'] = $this->settings['maturity_date'];
        $moloni_customer['payment_method_id'] = $this->toolPaymentMethodHandler($order['payment_method']);

        if ($order['shipping_method'] !== '') {
            $moloni_customer['delivery_method_id'] = $this->toolDeliveryMethodHandler($order['shipping_method']);
        }

        $moloni_customer['country_id'] = $this->toolCountryHandler($order['payment_iso_code_2']);
        if((int)$moloni_customer['country_id'] === 1){
            $moloni_customer['language_id'] = 1;
        } else {
            $country_spanish = array('MX', 'CO', 'ES', 'AR', 'PE', 'VE', 'CL', 'EC', 'GT', 'CU', 'BO', 'DO', 'HN', 'PY', 'SV', 'NI', 'CR', 'PA', 'UY', 'PR', 'GQ');
            $moloni_customer['language_id'] = in_array($order['payment_iso_code_2'], $country_spanish)? 3 : 2;
        }

        $moloni_customer['copies'] = $this->company['copies'];

        $moloni_customer['notes'] = '';
        $moloni_customer['salesman_id'] = '';
        $moloni_customer['payment_day'] = '';
        $moloni_customer['discount'] = '0';
        $moloni_customer['credit_limit'] = '0';
        $moloni_customer['field_notes'] = '';

        if ($moloni_customer_exists) {
            if (isset($this->settings['client_update']) && $this->settings['client_update'] == '1') {
                $moloni_customer['customer_id'] = $moloni_customer_exists['customer_id'];
                $result = $this->moloni->customers->update($moloni_customer);
                $customer_id = isset($result['customer_id']) ? $result['customer_id'] : false;
            } else {
                $customer_id = $moloni_customer_exists['customer_id'];
            }
        } else {
            $moloni_customer['number'] = $this->toolNumberCreator($order);
            $moloni_customer['vat'] = $order['vat_number'];
            $result = $this->moloni->customers->insert($moloni_customer);
            $customer_id = isset($result['customer_id']) ? $result['customer_id'] : false;
        }

        return $customer_id;
    }

    private function moloniProductHandler($product, $key)
    {
        $oc_product = $this->model_catalog_product->getProduct($product['product_id']);

        if (isset($oc_product['product_id'])) {
            $option_reference_sufix = '';
            $option_name_sufix = '';
            $reference_prefix = isset($this->settings['products_prefix']) && !empty($this->settings['products_prefix']) ? $this->settings['products_prefix'] : '';

            $options = $this->model_sale_order->getOrderOptions($product['order_id'], $product['order_product_id']);

            $options_string = '';
            if ($options && is_array($options)) {
                foreach ($options as $option) {
                    $options_string .= $option['name'] . ' ' . $option['value'] . "\n";
                    $option_reference_sufix_aux = false;

                    if (isset($this->settings['moloni_options_reference']) && $this->settings['moloni_options_reference']) {
                        $option_reference_sufix_aux = $this->ocdb->getOptionMoloniReference($option['product_option_value_id']);
                    }

                    $option_name_sufix .= ' ' . $option['value'];
                    $option_reference_sufix .= ($option_reference_sufix_aux) ?: $option['product_option_value_id'];
                }
            }

            $moloni_reference = $oc_product['product_id'];

            if (!empty($oc_product['model'])) {
                $moloni_reference = $oc_product['model'];
            }

            if (!empty($oc_product['sku'])) {
                $moloni_reference = $oc_product['sku'];
            }

            if(isset($this->settings['replace_white_space']) && empty($this->settings['replace_white_space'])){
                $moloni_reference = mb_substr($reference_prefix . $moloni_reference . $option_reference_sufix, 0, 28);
            } else {
                $moloni_reference = mb_substr(str_replace(' ', '_', $reference_prefix . $moloni_reference . $option_reference_sufix), 0, 28);
            }

            $moloni_product_exists = $this->moloni->products->getByReference($moloni_reference);

            if (!empty($options_string)) {
                $description = rtrim($options_string, "\n");
            } else {
                $description = mb_substr(preg_replace('/&lt;([\s\S]*?)&gt;/s', '', ($oc_product['description'])), 0, 250);
            }

            $taxes = $this->toolsTaxesHandler($oc_product);

            $values['name'] = $product['name'] . $option_name_sufix;
            $values['summary'] = $description . (strlen($description) >= 250 ? '...' : '');
            $values['price'] = $product['price_without_taxes'] = $this->toolsPriceHandler($product, $taxes);
            $values['discount'] = $product['discount'];
            $values['qty'] = $product['quantity'];

            $values['order'] = $key;
            $values['unit_id'] = $this->settings['measure_unit'];

            //======= TAXES =======//
            if (!empty($taxes) && is_array($taxes)) {
                $values['taxes'] = $taxes;
            } else {
                $values['exemption_reason'] = $this->settings['products_tax_exemption'];
            }

            if ($moloni_product_exists) {
                $values['product_id'] = $moloni_product_exists[0]['product_id'];
            } else {
                $values['reference'] = $moloni_reference;
                $values['ean'] = $oc_product['ean'];

                $oc_category = $this->model_catalog_product->getProductCategories($product['product_id']);
                if (isset($oc_category[0])) {
                    $category = $this->ocdb->getCategory($oc_category[0]);
                    $values['category_id'] = $this->toolCategoryHandler($category['name']);
                } else {
                    $values['category_id'] = $this->toolCategoryHandler('Sem categoria');
                }

                if ($oc_product['subtract']) {
                    $values['type'] = '1';
                    $values['has_stock'] = '1';
                    $values['stock'] = $oc_product['quantity'];
                    $values['at_product_category'] = $this->settings['products_at_category'];
                } else {
                    $values['type'] = '2';
                    $values['has_stock'] = '0';
                }

                if(isset($this->settings['products_description_moloni']) && empty($this->settings['products_description_moloni'])){
                    unset($values['summary']);
                }

                $inserted = $this->moloni->products->save($values);

                if (isset($inserted['product_id'])) {
                    $values['product_id'] = $inserted['product_id'];
                }
            }

            if(isset($this->settings['products_description_document']) && !empty($this->settings['products_description_document']) && !isset($values['summary'])){
                $values['summary'] = $description . (strlen($description) >= 250 ? '...' : '');
            } else if(isset($this->settings['products_description_document']) && empty($this->settings['products_description_document']) && isset($values['summary'])){
                unset($values['summary']);
            }

            return $values;
        }
    }

    private function moloniShippingHandler($totals, $order, $discounts = false)
    {
        $products = [];

        foreach ($totals as $total) {
            if (!in_array($total['code'],['total','sub_total','tax'])) {

                $values = [];

                if((float)$total['value'] < 0){
                    $this->hasNegative = true;
                    continue;
                }

                if ($total['code'] === 'shipping') {
                    $moloni_reference = 'Portes';
                    $values['discount'] = isset($discounts['shipping']) ? $discounts['shipping'] : 0;
                } else {
                    $moloni_reference = 'Taxa';
                    $values['discount'] = 0;
                }

                $moloni_product_exists = $this->moloni->products->getByReference($moloni_reference);

                $values['name'] = $total['title'];
                $values['summary'] = '';
                $values['qty'] = '1';
                $values['order'] = $order;

                if ($this->settings['shipping_tax'] == '0') {
                    $shipping_method = explode(".", $this->current_order['shipping_code']);
                    $shipping_code_tax_id = $this->config->get('shipping_' . $shipping_method[0] . '_tax_class_id');
                    if(!empty($shipping_code_tax_id)){
                        $tax_rules = $this->ocdb->getTaxRules($shipping_code_tax_id);
                        foreach ($tax_rules as $tax_order => $tax_rule) {
                            $geo_zone = ($tax_rule['based'] == 'shipping') ? $this->ocdb->getClientGeoZone($this->current_order['shipping_country_id'], $this->current_order['shipping_zone_id']) : $this->ocdb->getClientGeoZone($this->current_order['payment_country_id'], $this->current_order['payment_zone_id']);
                            $geo_zone_id = !empty($geo_zone) ? $geo_zone : 0;
                            $oc_tax = $this->ocdb->getTaxRate($tax_rule['tax_rate_id'], $geo_zone_id);

                            if (empty($oc_tax)) {
                                continue;
                            }

                            foreach ($this->moloni_taxes as $moloni_tax) {
                                if ((($oc_tax['type'] === 'P' && $moloni_tax['saft_type'] == 1) || ($oc_tax['type'] === 'F' && $moloni_tax['saft_type'] > 1))
                                    && (($this->company['country_id'] != 1) || ($this->company['country_id'] == 1 && empty($values['taxes']))) &&
                                    (float)round($oc_tax['rate'], 5) === (float)round($moloni_tax['value'], 5)) {
                                    if($total['code'] == 'shipping'){
                                        $values['price'] = $this->toolRemoveExtraTaxShipping($total['value'], $moloni_tax['value']);
                                    } else {
                                        $values['price']= $this->toolRemoveExtraTax($total['value'], $moloni_tax['value']);
                                    }
                                    $values['taxes'][] = array('tax_id' => $moloni_tax['tax_id'], 'value' => $moloni_tax['value'], 'order' => $tax_order, 'cumulative' => '1');
                                    break;
                                }
                            }
                        }
                    }
                } else {
                    foreach ($this->moloni_taxes as $moloni_tax) {
                        if ($moloni_tax['tax_id'] == $this->settings['shipping_tax']) {
                            if($total['code'] == 'shipping'){
                                $values['price'] = $this->toolRemoveExtraTaxShipping($total['value'], $moloni_tax['value']);
                            } else {
                                $values['price'] = $this->toolRemoveExtraTax($total['value'], $moloni_tax['value']);
                            }
                            $values['taxes'][] = array('tax_id' => $moloni_tax['tax_id'], 'value' => $moloni_tax['value'], 'order' => '0', 'cumulative' => '1');
                            break;
                        }
                    }
                }

                if(!isset($values['taxes']) || (isset($values['taxes']) && empty($values['taxes']))){
                    $values['price'] = $this->_myOrder['has_exchange'] ? $this->currency->convert($total['value'], $this->_myOrder['currency'], 'EUR') : $total['value'];
                    $values['exemption_reason'] = $this->settings['shipping_tax_exemption'];
                }

                if ($moloni_product_exists) {
                    $values['product_id'] = $moloni_product_exists[0]['product_id'];
                } else {
                    $values['reference'] = $moloni_reference;
                    $values['category_id'] = $this->toolCategoryHandler('Outros');
                    $values['type'] = '2';
                    $values['has_stock'] = '0';
                    $values['unit_id'] = $this->settings['measure_unit'];

                    $inserted = $this->moloni->products->save($values);

                    if (isset($inserted['product_id'])) {
                        $values['product_id'] = $inserted['product_id'];
                    }
                }

                $products[] = $values;
            }
        }

        return $products;
    }

    private function loadDefaults()
    {
        $this->data['header'] = $this->load->controller('common/header');
        $this->data['footer'] = $this->load->controller('common/footer');
        $this->data['column_left'] = $this->load->controller('common/column_left');

        $this->data['url'] = $this->defaultTemplateUrls();
        $this->data['breadcrumbs'] = $this->createBreadcrumbs();
        $this->data['document_types'] = $this->getDocumentTypes();

        $this->data['debug_window'] = (isset($this->settings['debug_console']) && $this->settings['debug_console']) ? $this->moloni->debug->getLogs('all') : false;
        $this->data['debug_console'] = ($this->data['debug_window']) ? $this->load->view('extension/module/moloni/debug', $this->data) : false;
        $this->data['error_warnings'] = $this->moloni->errors->getError('all');
        $this->data['update_result'] = $this->updated_files;
        $this->data['update_available'] = $this->update_available;

        if (isset($this->messages['errors']) && is_array($this->messages['errors'])) {
            $this->data['messages']['errors'][] = $this->messages['errors'];
        }

        if (isset($this->messages['success']) && is_array($this->messages['success'])) {
            $this->data['messages']['success'][] = $this->messages['success'];
        }

        $this->data['errors_template'] = (!empty($this->data['error_warnings']) || !empty($this->data['messages']['errors']) || !empty($this->data['messages']['success'])) ? $this->load->view('extension/module/moloni/errors', $this->data) : false;
    }

    private function defaultTemplateUrls()
    {
        $url = array();
        $url['login']['form'] = $this->url->link('extension/module/moloni', array('user_token' => $this->session->data['user_token']), true);
        $url['logout'] = $this->url->link('extension/module/moloni', array('action' => 'logout', 'user_token' => $this->session->data['user_token']), true);
        $url['settings']['save'] = $this->url->link('extension/module/moloni/settings', array('store_id' => (isset($this->request->get['store_id']) ? $this->request->get['store_id'] : 0), 'action' => 'save', 'user_token' => $this->session->data['user_token']), true);
        $url['settings']['cancel'] = $this->url->link('extension/module/moloni/settings', array('user_token' => $this->session->data['user_token']), true);
        $url['import_products'] = $this->url->link('extension/module/moloni/importProducts', array('user_token' => $this->session->data['user_token']), true);

        return $url;
    }

    private function createBreadcrumbs()
    {
        switch ($this->page) {
            case 'login':
                $breadcrumbs[] = array('text' => 'Login', 'href' => $this->url->link('extension/module/moloni/login', array('user_token' => $this->session->data['user_token']), true));
                break;
            case 'companies':
                $breadcrumbs[] = array('text' => 'Empresas', 'href' => $this->url->link('extension/module/moloni/home', array('user_token' => $this->session->data['user_token']), true));
                break;
            case 'home':
                $breadcrumbs[] = array('text' => 'Home', 'href' => $this->url->link('extension/module/moloni/home', array('user_token' => $this->session->data['user_token']), true));
                $breadcrumbs[] = array('text' => 'Orders', 'href' => $this->url->link('extension/module/moloni/home', array('user_token' => $this->session->data['user_token']), true));
                break;
            case 'documents':
                $breadcrumbs[] = array('text' => 'Home', 'href' => $this->url->link('extension/module/moloni/home', array('user_token' => $this->session->data['user_token']), true));
                $breadcrumbs[] = array('text' => 'Documents', 'href' => $this->url->link('extension/module/documents', array('user_token' => $this->session->data['user_token']), true));
                break;
            case 'store_list':
                $breadcrumbs[] = array('text' => 'Settings', 'href' => $this->url->link('extension/module/moloni/settings', array('user_token' => $this->session->data['user_token']), true));
                $breadcrumbs[] = array('text' => 'Choose your store', 'href' => $this->url->link('extension/module/moloni/settings', array('user_token' => $this->session->data['user_token']), true));
                break;
            case 'settings':
                $breadcrumbs[] = array('text' => 'Settings', 'href' => $this->url->link('extension/module/moloni/settings', array('user_token' => $this->session->data['user_token']), true));
                $breadcrumbs[] = array('text' => 'Stores', 'href' => $this->url->link('extension/module/moloni/settings', array('user_token' => $this->session->data['user_token']), true));
                $breadcrumbs[] = array('text' => 'Edit store settings', 'href' => $this->url->link('extension/module/moloni/settings', array('store_id' => (isset($this->request->get['store_id']) ? $this->request->get['store_id'] : 0), 'user_token' => $this->session->data['user_token']), true));
                break;
            default :
                $breadcrumbs[] = (array('href' => 'extension/module/moloni', 'text' => 'login'));
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
        $this->document_type['invoices'] = array('name' => 'invoices', 'url' => 'Faturas');
        $this->document_type['invoiceReceipts'] = array('name' => 'invoiceReceipts', 'url' => 'FaturasRecibo');
        $this->document_type['simplifiedInvoices'] = array('name' => 'simplifiedInvoices', 'url' => 'FaturaSimplificada');
        $this->document_type['billsOfLading'] = array('name' => 'billsOfLading', 'url' => 'GuiasTransporte');
        $this->document_type['deliveryNotes'] = array('name' => 'deliveryNotes', 'url' => 'GuiasRemessa');
        $this->document_type['purchaseOrder'] = array('name' => 'purchaseOrder', 'url' => 'NotasEncomenda');
        $this->document_type['internalDocuments'] = array('name' => 'internalDocuments', 'url' => 'DocumentosInternos');
        $this->document_type['estimates'] = array('name' => 'estimates', 'url' => 'Orcamentos');
        $this->document_type['purchaseOrder'] = array('name' => 'purchaseOrder', 'url' => 'NotasEncomenda');

        return $this->document_type;
    }

    private function getIndexData()
    {
        $data['order_get_more'] = $this->url->link('extension/module/moloni/getOrders', ['user_token' => $this->session->data['user_token']]);
        $data['order_url_base'] = $this->url->link('extension/module/moloni/invoice', ['user_token' => $this->session->data['user_token'], 'order_id' => '']);

        return $data;
    }

    private function getDocumentsData()
    {
        $this->company = $this->moloni->companies->getOne();

        if (!isset($this->settings['order_statuses'])) {
            $this->settings['order_statuses'] = null;
        }

        if (!isset($this->settings['order_since'])) {
            $this->settings['order_since'] = null;
        }

        $data['orders_list'] = $this->ocdb->getOrdersAll($this->settings['order_statuses'], $this->settings['order_since'], false, false, true);
        if (is_array($data['orders_list']) && !empty($data['orders_list'])) {
            foreach ($data['orders_list'] as &$document) {
                $document['info'] = $this->moloni->documents()->getOne($document['invoice_id']);
                $document['view_url'] = 'https://moloni.pt/' . $this->company['slug'] . '/' . $this->moloni->documents($document['info']['document_type']['saft_code'])->getViewUrl($document['invoice_id'], $document['info']['status']);
                $document['redo_url'] = $this->url->link('extension/module/moloni/invoice', array('order_id' => $document['order_id'], 'force' => true, 'user_token' => $this->session->data['user_token']), true);
                if ($document['info']['status'] == '1') {
                    $document['download_url'] = $this->moloni->documents()->getPDFLink($document['invoice_id']);
                }
            }
        }

        return $data;
    }

    private function getSettingsData()
    {
        $data = array();
        $data['store_id'] = $this->store_id;
        $data['settings_values']['document_sets'] = $this->moloni->document_sets->getAll();
        $data['settings_values']['document_types'] = $this->getDocumentTypes();
        $data['settings_values']['document_status'] = array('0' => 'draft', '1' => 'closed');

        $data['settings_values']['products_taxes'] = $this->moloni->taxes->getAll();
        $data['settings_values']['products_exemptions'] = $this->moloni->taxes->getExemptions();
        $data['settings_values']['products_at_categories'] = array();
        $data['settings_values']['products_at_categories'][] = array('code' => 'M', 'name' => 'Mercadorias');
        $data['settings_values']['products_at_categories'][] = array('code' => 'P', 'name' => 'Matérias-primas, subsidiárias e de consumo');
        $data['settings_values']['products_at_categories'][] = array('code' => 'A', 'name' => 'Produtos acabados e intermédios');
        $data['settings_values']['products_at_categories'][] = array('code' => 'S', 'name' => 'Subprodutos, desperdícios e refugos');
        $data['settings_values']['products_at_categories'][] = array('code' => 'T', 'name' => 'Produtos e trabalhos em curso');

        $data['settings_values']['client_vat_custom_fields'][] = array('custom_field_id' => '0', 'name' => 'Use final consumer');
        $data['settings_values']['client_vat_custom_fields'] = array_merge($data['settings_values']['client_vat_custom_fields'], $this->ocdb->getCustomFieldsAll());

        $data['settings_values']['client_maturity_dates'] = $this->moloni->maturity_dates->getAll();
        $data['settings_values']['measure_units'] = $this->moloni->measurements->getAll();

        $data['settings_values']['caes'] = $this->moloni->cae->getAll();

        $data['settings_values']['store_locations'][] = array('id' => '0', 'name' => 'Default Moloni');
        foreach ($this->ocdb->getStoreLocation('all') as $store) {
            $data['settings_values']['store_locations'][] = array('id' => $store['location_id'], 'name' => $store['name']);
        }

        $this->load->model('localisation/order_status');

        $data['settings_values']['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        $this->load->model('localisation/tax_class');

        $data['settings_values']['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();

        if (defined('DIR_LOGS') && file_exists(DIR_LOGS . '/moloni/' .  date('Ymd') . '.log')) {
            $data['settings_values']['log_url'] = $this->url->link('extension/module/moloni/toolDownloadLog', ['user_token' => $this->session->data['user_token']], true);
        } else {
            $data['settings_values']['log_url'] = '';
        }

        return $data;
    }

    private function getStoreListData()
    {
        $data = array();
        $data['stores'][] = array(
            'store_id' => 0,
            'name' => $this->config->get('config_name') . $this->language->get('text_default'),
            'url' => $this->config->get('config_secure') ? HTTPS_CATALOG : HTTP_CATALOG,
            'edit' => $this->url->link('extension/module/moloni/settings', array('store_id' => '0', 'user_token' => $this->session->data['user_token']), true)
        );

        $stores = $this->ocdb->getStores();
        if (count($stores) > 0) {
            foreach ($stores as $store) {
                $data['stores'][] = array(
                    'store_id' => $store['store_id'],
                    'name' => $store['name'],
                    'url' => $store['url'],
                    'edit' => $this->url->link('extension/module/moloni/settings', array('store_id' => $store['store_id'], 'user_token' => $this->session->data['user_token']), true)
                );
            }
        }

        return $data;
    }

    private function getMoloniSettings()
    {
        $settings = array();
        $settings_list = $this->ocdb->getMoloniSettings($this->moloni->company_id, $this->store_id);
        foreach ($settings_list as $setting) {
            switch ($setting['label']) {
                case 'order_statuses':
                    $settings[$setting['label']] = json_decode($setting['value'], true);
                    break;
                default:
                    $settings[$setting['label']] = $setting['value'];
                    break;
            }
        }

        if (!isset($settings['git_username']) || empty($settings['git_username'])) {
            $settings['git_username'] = $this->git_user;
        }

        if (!isset($settings['git_branch']) || empty($settings['git_branch'])) {
            $settings['git_branch'] = $this->git_branch;
        }

        if (!isset($settings['git_repository']) || empty($settings['git_repository1'])) {
            $settings['git_repository'] = $this->git_repo;
        }

        return $settings;
    }

    private function setSettings($settings, $store_id = 0)
    {
        if (is_array($settings)) {
            foreach ($settings as $name => $value) {

                if (is_array($value)) {
                    $value = json_encode($value);
                }

                if ($this->ocdb->qExistsSetting($name, $store_id, $this->moloni->company_id)) {
                    $this->ocdb->qUpdateMoloniSetting($name, $store_id, $this->moloni->company_id, trim($value));
                } else {
                    $this->ocdb->qInsertMoloniSetting($name, $store_id, $this->moloni->company_id, trim($value));
                }
            }
        }
    }

    private function versionCheck()
    {
        $release_raw = $this->curl('https://plugins.moloni.com/opencart3/release');
        $release = json_decode($release_raw, true);

        if (($release['version'] !== $this->version)) {
            $this->data['update_page'] = $this->load->view($this->modulePathView . 'update');
            $this->update_available = $this->url->link('extension/module/moloni', array('update' => 'true', 'user_token' => $this->session->data['user_token']), true);
        }
    }

    private function update($method = 'github')
    {
        switch ($method) {
            case 'github':
                $this->githubUpdate();
                break;
        }

        $this->clearThemeCache();

        $this->messages['success'] = array(
            'title' => 'Sucesso',
            'message' => 'Actualização feita com sucesso'
        );
    }

    private function clearThemeCache()
    {
        $directories = glob(DIR_CACHE . '*', GLOB_ONLYDIR);
        if ($directories) {
            foreach ($directories as $directory) {
                $files = glob($directory . '/*');

                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }

                if (is_dir($directory)) {
                    rmdir($directory);
                }
            }
        }
    }

    private function githubUpdate()
    {
        $settingsRaw = $this->curl('https://api.github.com/repos/' . $this->git_user . '/' . $this->git_repo . '/branches/' . $this->git_branch);
        $settings = json_decode($settingsRaw, true);
        if (isset($settings['commit'])) {
            $treeRaw = $this->curl('https://api.github.com/repos/' . $this->git_user . '/' . $this->git_repo . '/git/trees/' . $settings['commit']['sha'] . '?recursive=1');
            $tree = json_decode($treeRaw, true);
            foreach ($tree['tree'] as $file) {
                $file_info = pathinfo($file['path']);
                if ($file['type'] === 'blob' && isset($file_info['extension']) && in_array($file_info['extension'], array('php', 'twig', 'css'))) {
                    $raw = $this->curl('https://raw.githubusercontent.com/' . $this->git_user . '/' . $this->git_repo . '/' . $this->git_branch . '/' . $file['path']);
                    if ($raw) {
                        $this->updated_files['true'][] = $path = str_replace('/admin', '', DIR_APPLICATION) . $file['path'];
                        file_put_contents($path, $raw);
                    } else {
                        $this->updated_files['false'] = str_replace('/admin', '', DIR_APPLICATION) . $file['path'];
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

    public function install()
    {

        $this->install->createTables();

        $this->load->model('setting/event');
        $this->model_setting_event->addEvent($this->eventGroup, 'admin/view/common/column_left/before', $this->modulePathBase . 'injectAdminMenuItem');
        $this->model_setting_event->addEvent($this->eventGroup . '_invoice_button', 'admin/view/sale/order_list/before', $this->modulePathBase . 'invoiceButtonCheck');
        $this->model_setting_event->addEvent($this->eventGroup . '_options_reference', 'admin/view/catalog/product_form/before', $this->modulePathBase . 'optionsReferenceCheck');
        $this->model_setting_event->addEvent($this->eventGroup . '_product_check_edit', 'admin/model/catalog/product/editProduct/after', $this->modulePathBase . 'eventProductCheck');
        $this->model_setting_event->addEvent($this->eventGroup . '_product_check_add', 'admin/model/catalog/product/addProduct/after', $this->modulePathBase . 'eventProductCheck');
        $this->model_setting_event->addEvent($this->eventGroup . '_order_edit_check_paid', 'catalog/model/checkout/order/addOrderHistory/after', $this->modulePathBase . 'eventCreateDocument');
    }

    public function uninstall()
    {
        $this->install->dropTables();

        $this->load->model('setting/event');
        $this->model_setting_event->deleteEventByCode($this->eventGroup);
        $this->model_setting_event->deleteEventByCode($this->eventGroup . '_invoice_button');
        $this->model_setting_event->deleteEventByCode($this->eventGroup . '_options_reference');
        $this->model_setting_event->deleteEventByCode($this->eventGroup . '_product_check_edit');
        $this->model_setting_event->deleteEventByCode($this->eventGroup . '_product_check_add');
        $this->model_setting_event->deleteEventByCode($this->eventGroup . '_order_edit_check_paid');
    }

    public function patch()
    {
        if ($this->config->get('moloni_status') == 1) {

        }
    }

    public function injectAdminMenuItem($eventRoute, &$data)
    {
        if ($this->user->hasPermission('access', 'extension/module/moloni')) {
            $moloni[] = array(
                'name' => $this->language->get('Home'),
                'href' => $this->url->link('extension/module/moloni', array('page' => 'home', 'user_token' => $this->session->data['user_token']), true),
                'children' => array()
            );

            $moloni[] = array(
                'name' => $this->language->get('Documents'),
                'href' => $this->url->link('extension/module/moloni/documents', array('page' => 'documents', 'user_token' => $this->session->data['user_token']), true),
                'children' => array()
            );

            $moloni[] = array(
                'name' => $this->language->get('Settings'),
                'href' => $this->url->link('extension/module/moloni/settings', array('page' => 'settings', 'user_token' => $this->session->data['user_token']), true),
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

    public function invoiceButtonCheck($eventRoute, &$data)
    {
        $this->start();
        foreach ($data['orders'] as &$order) {
            if (isset($this->settings['order_statuses']) && is_array($this->settings['order_statuses'])) {
                $order_info = $this->ocdb->getOrderById($order['order_id']);
                if (isset($order_info['invoice_id']) && !empty(isset($order_info['invoice_id']))) {
                    # $moloni_url = "https://moloni.pt/" . $this->company['slug'] . $this->moloni->documents($this->settings['document_type'])->getViewUrl($order_info['invoice_id'], $order_info['invoice_status']);
                    # $order['moloni_button'] = '<a href="' . $moloni_url . '" data-toggle="tooltip" title="' . $this->language->get('details') . '" class="btn btn-primary"><i class="fa fa-file-text-o"></i></a>';
                    $order['moloni_button'] = false;
                } else {
                    if (in_array($order_info['order_status_id'], $this->settings['order_statuses'])) {
                        $moloni_url = $this->url->link('extension/module/moloni/invoice', array('order_id' => $order['order_id'], 'user_token' => $this->session->data['user_token']), true);
                        $order['moloni_button'] = '<a href="' . $moloni_url . '" data-toggle="tooltip" title="' . $this->language->get('create_moloni_document') . '" class="btn btn-primary"><i class="fa fa-usd"></i></a>';
                    } else {
                        $order['moloni_button'] = false;
                    }
                }
            }
        }
    }

    public function optionsReferenceCheck($evenRoute, &$data)
    {
        $this->start();
        $data['moloni_reference'] = $this->language->get('moloni_reference');
        $data['use_moloni_references'] = (isset($this->settings['moloni_options_reference']) && $this->settings['moloni_options_reference']) ? true : false;

        foreach ($data['product_options'] as &$product_option) {
            foreach ($product_option['product_option_value'] as &$product_option_value) {
                $moloni_reference = $this->ocdb->getOptionMoloniReference($product_option_value['product_option_value_id']);
                $product_option_value['moloni_reference'] = is_null($moloni_reference) ? '' : $moloni_reference;
            }
        }
    }

    /**
     * Event called when a product is saved in Opencart
     *
     * @param string $evenRoute Event route
     * @param array $data Opencart product
     *
     * @return void
     *
     * @throws Exception
     */
    public function eventProductCheck($evenRoute, &$data)
    {
        $this->start();

        if ($this->moloni->logged) {
            if (isset($data[1])) {
                $product = $data[1];
                if (isset($product['product_option'])) {
                    if (isset($this->settings['moloni_options_reference']) && $this->settings['moloni_options_reference'] == true) {
                        foreach ($product['product_option'] as $product_option) {
                            if ($product_option['type'] === 'select') {
                                foreach ($product_option['product_option_value'] as $product_option_value) {
                                    if (isset($product_option_value['moloni_reference'])) {
                                        $this->ocdb->updateOptionMoloniReference($product_option_value['moloni_reference'], (int)$product_option_value['product_option_value_id']);
                                    }
                                }
                            }
                        }
                    }
                }

                if (isset($this->settings['products_auto']) && (int)$this->settings['products_auto'] === 1) {
                    $this->eventProductHandler($product);
                }
            }
        }
    }

    /**
     * Creates or updates an product in Moloni based on Opencart product
     *
     * @param array $opencartProduct Opencart product
     *
     * @return void
     *
     * @throws Exception
     */
    private function eventProductHandler($opencartProduct)
    {
        if (empty($opencartProduct) || !is_array($opencartProduct)) {
            return;
        }

        $this->load->model('setting/setting');
        $this->load->model('localisation/geo_zone');
        $this->load->model('catalog/category');

        $this->moloni_taxes = $this->moloni->taxes->getAll();

        $referencePrefix = isset($this->settings['products_prefix']) && !empty($this->settings['products_prefix']) ? $this->settings['products_prefix'] : '';

        if (!empty($opencartProduct['sku'])) {
            $moloni_reference = $opencartProduct['sku'];
        } else if (!empty($opencartProduct['model'])) {
            $moloni_reference = $opencartProduct['model'];
        } else {
            $moloni_reference = $opencartProduct['product_id'];
        }

        if (isset($this->settings['replace_white_space']) && empty($this->settings['replace_white_space'])) {
            $moloni_reference = mb_substr($referencePrefix . $moloni_reference, 0, 28);
        } else {
            $moloni_reference = mb_substr(str_replace(' ', '_', $referencePrefix . $moloni_reference), 0, 28);
        }

        $moloni_product_exists = $this->moloni->products->getByReference($moloni_reference);

        if ($moloni_product_exists) {
            $values['product_id'] = $moloni_product_exists[0]['product_id'];
            $values['summary'] = $moloni_product_exists[0]['summary'];
        }

        // *Hacks* Use the first description
        foreach ($opencartProduct['product_description'] as $description) {
            $opencartProduct['description'] = $description['description'];
            $opencartProduct['name'] = $description['name'];

            if (true) {
                break;
            }
        }

        $values['name'] = $opencartProduct['name'];
        $values['price'] = $opencartProduct['price'];
        $values['unit_id'] = $this->settings['measure_unit'];
        $values['reference'] = $moloni_reference;
        $values['ean'] = $opencartProduct['ean'];

        if(isset($this->settings['products_description_moloni']) && !empty($this->settings['products_description_moloni'])){
            $values['summary'] = mb_substr(preg_replace('/&lt;([\s\S]*?)&gt;/s', '', ($opencartProduct['description'])), 0, 250);
        }

        if (!empty($opencartProduct['product_category']) && is_array($opencartProduct['product_category'])) {
            $category = $this->model_catalog_category->getCategory(end($opencartProduct['product_category']));
            $values['category_id'] = $this->toolCategoryHandler($category['name']);
        } else {
            $values['category_id'] = $this->toolCategoryHandler('Sem categoria');
        }

        if ($opencartProduct['subtract']) {
            $values['type'] = '1';
            $values['has_stock'] = '1';
            $values['stock'] = $opencartProduct['quantity'];
            $values['at_product_category'] = $this->settings['products_at_category'];
        } else {
            $values['type'] = '2';
            $values['has_stock'] = '0';
        }

        $taxes = $this->eventTaxesHandler($opencartProduct);

        if (!empty($taxes) && is_array($taxes)) {
            $values['taxes'] = $taxes;
        } else {
            $values['exemption_reason'] = $this->settings['products_tax_exemption'];
        }

        $this->moloni->products->save($values);
    }

    /**
     * Searches for opencart taxes in Moloni and return them
     *
     * @param array $oc_product Opencart Product
     *
     * @return false|array
     */
    private function eventTaxesHandler($oc_product)
    {
        $taxes = false;

        $storeGeoZone = $this->model_setting_setting->getSettingValue('config_geocode', $this->store_id);

        if (empty($storeGeoZone)) {
            $storeCountryId = $this->model_setting_setting->getSettingValue('config_country_id', $this->store_id);
            $storeZoneId = $this->model_setting_setting->getSettingValue('config_zone_id', $this->store_id);
            $storeGeoZone = $this->ocdb->getZoneToGeoZone($storeZoneId, $storeCountryId);

            if (!empty($storeGeoZone) && is_array($storeGeoZone) && isset($storeGeoZone[0]['geo_zone_id'])) {
                $storeGeoZone = $storeGeoZone[0]['geo_zone_id'];
            }
        }

        if ((int)$this->settings['products_tax'] === 0) {
            $tax_rules = $this->ocdb->getTaxRules($oc_product['tax_class_id']);

            foreach ($tax_rules as $tax_order => $tax_rule) {
                $oc_tax = $this->ocdb->getTaxRate($tax_rule['tax_rate_id'], $storeGeoZone);

                if (empty($oc_tax)) {
                    continue;
                }

                foreach ($this->moloni_taxes as $moloni_tax) {
                    if ((($oc_tax['type'] === 'P' && $moloni_tax['saft_type'] == 1) || ($oc_tax['type'] === 'F' && $moloni_tax['saft_type'] > 1))
                        && round($oc_tax['rate'], 5) === round($moloni_tax['value'], 5)) {
                        $taxes[] = ['tax_id' => $moloni_tax['tax_id'], 'value' => $moloni_tax['value'], 'order' => $tax_order, 'cumulative' => '1'];
                        break;
                    }
                }
            }
        } else {
            foreach ($this->moloni_taxes as $moloni_tax) {
                if ((int)$moloni_tax['tax_id'] === (int)$this->settings['products_tax']) {
                    $taxes[] = ['tax_id' => $moloni_tax['tax_id'], 'value' => $moloni_tax['value'], 'order' => '0', 'cumulative' => '1'];
                    break;
                }
            }
        }

        return $taxes;
    }

    public function toolsPriceHandler($product, $taxes = false, $order = false)
    {
        if (!$order) {
            $order = $this->current_order;
        }

        if ($this->company['currency_id'] == '1') {
            if ($this->settings['products_tax'] == 0) {
                $values['price_without_taxes'] = $this->currency->convert($product['price'], $this->_myOrder['currency'], 'EUR');
            } else {
                $values['price_without_taxes'] = ($this->currency->convert($product['price'] + $product['tax'], $this->_myOrder['currency'], 'EUR') * 100) / (100 + $taxes[0]['value']);
            }
        } else {
            $values['price_without_taxes'] = $this->currency->convert($product['price'], $this->_myOrder['currency'], 'EUR');
        }

        return $values['price_without_taxes'];
    }

    public function toolsDiscountsHandlers($totals)
    {
        $discount['document'] = 0;
        $discount['products'] = 0;

        foreach ($totals as $total) {
            $start = strpos($total['title'], '(') + 1;
            $end = strrpos($total['title'], ')');
            switch ($total['code']) {
                case 'total' :
                    $this->_myOrder['net_value'] = $this->_myOrder['has_exchange'] ? ($this->currency->convert(abs($total['value']), $this->_myOrder['currency'], 'EUR')) : abs($total['value']);
                    break;

                case 'sub_total' :
                    $discount['sub_total'] = $this->_myOrder['has_exchange'] ? ($this->currency->convert(abs($total['value']), $this->_myOrder['currency'], 'EUR')) : abs($total['value']);
                    break;

                case 'coupon' :
                    if ($start && $end) {
                        $discount['coupon'] = array(
                            'code' => substr($total['title'], $start, $end - $start),
                            'value' => $this->_myOrder['has_exchange'] ? ($this->currency->convert(abs($total['value']), $this->_myOrder['currency'], 'EUR')) : abs($total['value']),
                            'shipping' => $this->ocdb->getShippingDiscount(substr($total['title'], $start, $end - $start))
                        );
                    }
                    break;

                case 'voucher':
                    if ($start && $end) {
                        $discount['voucher'] = array(
                            'code' => substr($total['title'], $start, $end - $start),
                            'value' => $this->_myOrder['has_exchange'] ? ($this->currency->convert(abs($total['value']), $this->_myOrder['currency'], 'EUR')) : abs($total['value'])
                        );
                    }
                    break;
            }
        }

        if (isset($discount['coupon'])) {
            if ($discount['coupon']['shipping']) {
                $discount['shipping'] = 100;
            } else {
                $discount['products'] = (($discount['coupon']['value'] * 100) / $discount['sub_total']);
                $discount['shipping'] = 0;
            }
        }

        if (isset($discount['voucher'])) {
            $discount['document'] = (($discount['voucher']['value'] * 100) / $discount['sub_total']);
        }

        return $discount;
    }

    public function toolsTaxesHandler($oc_product, $order = false)
    {
        $taxes = false;
        if (!$order) {
            $order = $this->current_order;
        }

        if ($this->settings['products_tax'] == 0) {
            $tax_rules = $this->ocdb->getTaxRules($oc_product['tax_class_id']);
            foreach ($tax_rules as $tax_order => $tax_rule) {
                $geo_zone = ($tax_rule['based'] == 'shipping') ? $this->ocdb->getClientGeoZone($order['shipping_country_id'], $order['shipping_zone_id']) : $this->ocdb->getClientGeoZone($order['payment_country_id'], $order['payment_zone_id']);
                $geo_zone_id = !empty($geo_zone) ? $geo_zone : 0;
                $oc_tax = $this->ocdb->getTaxRate($tax_rule['tax_rate_id'], $geo_zone_id);

                if (empty($oc_tax)) {
                    continue;
                }

                foreach ($this->moloni_taxes as $moloni_tax) {
                    if ((($oc_tax['type'] === 'P' && $moloni_tax['saft_type'] == 1) || ($oc_tax['type'] === 'F' && $moloni_tax['saft_type'] > 1))
                        && (($this->company['country_id'] != 1) || ($this->company['country_id'] == 1 && empty($taxes))) &&
                        (float)round($oc_tax['rate'], 5) === (float)round($moloni_tax['value'], 5)) {
                        $taxes[] = array('tax_id' => $moloni_tax['tax_id'], 'value' => $moloni_tax['value'], 'order' => $tax_order, 'cumulative' => '1');
                        break;
                    }
                }
            }
        } else {
            foreach ($this->moloni_taxes as $moloni_tax) {
                if ((int)$moloni_tax['tax_id'] === (int)$this->settings['products_tax']) {
                    $taxes[] = array('tax_id' => $moloni_tax['tax_id'], 'value' => $moloni_tax['value'], 'order' => '0', 'cumulative' => '1');
                    break;
                }
            }
        }

        return $taxes;
    }

    public function toolPaymentMethodHandler($name, $methods = false)
    {
        if (!$methods) {
            $methods = $this->moloni->payment_methods->getAll();
        }

        foreach ($methods as $payment) {
            if (strcasecmp($name, $payment['name']) == 0) {
                return $payment['payment_method_id'];
            }
        }

        $return = $this->moloni->payment_methods->insert(array('name' => $name));
        return isset($return['payment_method_id']) ? $return['payment_method_id'] : false;
    }

    public function toolDeliveryMethodHandler($name, $methods = false)
    {
        $nameMethod = preg_replace('/\(([^\)]*)\)/', '', $name, 1);

        while(ctype_space(substr($nameMethod, -1))){
            $nameMethod = substr($nameMethod, 0,-1);
        }

        if (!$methods) {
            $methods = $this->moloni->delivery_methods->getAll();
        }

        foreach ($methods as $delivery) {
            if (strcasecmp($nameMethod, $delivery['name']) == 0) {
                return $delivery['delivery_method_id'];
            }
        }

        $return = $this->moloni->delivery_methods->insert(array('name' => $nameMethod));
        return isset($return['delivery_method_id']) ? $return['delivery_method_id'] : false;
    }

    public function toolCountryHandler($country_iso_2)
    {
        foreach ($this->countries as $country) {
            if (strcasecmp($country_iso_2, $country['iso_3166_1']) == 0) {
                return $country['country_id'];
            }
        }

        // In case we don't find a country, we return 1 (Portugal)
        return '1';
    }

    public function toolCategoryHandler($category_name)
    {
        $ml_categories = $this->moloni->categories->getAllCached();

        if (!$ml_categories) {
            $ml_categories = $this->moloni->categories->getAllRecursive(0);
        }

        foreach ($ml_categories as $category) {
            if (strcasecmp($category['name'], $category_name) === 0) {
                return $category['category_id'];
            }
        }

        $values['name'] = $category_name;
        $values['parent_id'] = '0';
        $insert = $this->moloni->categories->insert($values);

        return $insert['category_id'];
    }

    public function toolsExchangeHandler($from, $to)
    {
        $moloni_exchanges = $this->moloni->currency_exchange->getALl();
        foreach ($moloni_exchanges as $exchange) {
            if ($exchange['name'] == $from . '/' . $to) {
                return $exchange['to'];
            }
        }

        return '0';
    }

    public function toolNumberCreator($order)
    {
        $result = $this->moloni->customers->getNextNumber();
        return $result['number'];
    }

    public function toolValidateZipCode($zip_code)
    {
        $zip_code = trim(str_replace(' ', '', $zip_code));
        $zip_code = preg_replace('/[^0-9]/', '', $zip_code);

        if (strlen($zip_code) == 7) {
            $zip_code = $zip_code[0] . $zip_code[1] . $zip_code[2] . $zip_code[3] . '-' . $zip_code[4] . $zip_code[5] . $zip_code[6];
        }

        if (strlen($zip_code) == 6) {
            $zip_code = $zip_code[0] . $zip_code[1] . $zip_code[2] . $zip_code[3] . '-' . $zip_code[4] . $zip_code[5] . '0';
        }

        if (strlen($zip_code) == 5) {
            $zip_code = $zip_code[0] . $zip_code[1] . $zip_code[2] . $zip_code[3] . '-' . $zip_code[4] . '00';
        }

        if (strlen($zip_code) == 4) {
            $zip_code .= '-' . '000';
        }

        if (strlen($zip_code) == 3) {
            $zip_code .= '0-' . '000';
        }

        if (strlen($zip_code) == 2) {
            $zip_code .= '00-' . '000';
        }

        if (strlen($zip_code) == 1) {
            $zip_code .= '000-' . '000';
        }

        if (strlen($zip_code) == 0) {
            $zip_code = '1000-100';
        }

        return (preg_match("/[0-9]{4}\-[0-9]{3}/", $zip_code)) ? $zip_code : '1000-100';
    }

    public function toolRemoveExtraTax($total_value, $moloni_tax_value)
    {
        if(isset($this->settings['remove_extra_tax']) && empty($this->settings['remove_extra_tax'])){
            $priceExtraTax = (float)($total_value);
        } else {
            $priceExtraTax = $total_value / (float)(1 . '.' . $moloni_tax_value);
        }

        return $this->_myOrder['has_exchange'] ? $this->currency->convert($priceExtraTax, $this->_myOrder['currency'], 'EUR') : $priceExtraTax;
    }

    public function toolRemoveExtraTaxShipping($total_value, $moloni_tax_value)
    {
        if(isset($this->settings['remove_extra_tax_shipping']) && empty($this->settings['remove_extra_tax_shipping'])){
            $priceExtraTaxShipping = (float)($total_value);
        } else {
            $priceExtraTaxShipping = $total_value / (float)(1 . '.' . $moloni_tax_value);
        }

        return $this->_myOrder['has_exchange'] ? $this->currency->convert($priceExtraTaxShipping, $this->_myOrder['currency'], 'EUR') : $priceExtraTaxShipping;
    }

    /**
     * Writes a log in logs directory
     *
     * @param string|array $message Data do store in log
     *
     * @return void
     */
    private function toolWriteLog($message)
    {
        if (!defined('DIR_LOGS')) {
            return;
        }

        $directory = DIR_LOGS . 'moloni';

        if (!is_dir($directory) && !mkdir($directory) && !is_dir($directory)) {
            return;
        }

        $fileName = date('Ymd') . '.log';
        $logFile = fopen($directory . '/' . $fileName, 'ab');

        fwrite($logFile, '[' . date('Y-m-d H:i:s') . '] ' . print_r($message, true) . PHP_EOL);
    }

    /**
     * Return log url
     *
     * @return string
     */
    public function toolDownloadLog()
    {
        if (!defined('DIR_LOGS')) {
            return;
        }

        $path = DIR_LOGS . '/moloni/' .  date('Ymd') . '.log';

        if (!file_exists($path)) {
            return;
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header("Cache-Control: no-cache, must-revalidate");
        header("Expires: 0");
        header('Content-Disposition: attachment; filename="'.basename($path).'"');
        header('Content-Length: ' . filesize($path));
        header('Pragma: public');

        flush();

        readfile($path);
    }
}
