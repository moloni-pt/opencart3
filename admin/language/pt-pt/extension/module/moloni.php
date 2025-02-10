<?php
/* Extension listing */
$_['heading_title'] = 'Moloni';
$_['error_permission'] = 'Erro, não tens permissões para aceder!';
$_['text_success'] = 'Sucesso, alteraste o módulo moloni!';

$_['login_text'] = 'Entra com os teus dados moloni';
$_['login_username'] = 'Email';
$_['login_password'] = 'Password';
$_['login_register'] = 'Registar uma nova conta moloni';
$_['login_login'] = 'Login';

$_['moloni_reference'] = 'Moloni Ref.';
$_['create_moloni_document'] = "Enviar para o moloni";

$_['orders_list'] = "Lista de encomendas";
$_['documents_lists'] = "Lista de documentos";

$_['products_import_success'] = "Importação efetuada com sucesso";
$_['no_products_found'] = "Nenhum produto encontrado";
$_['success'] = "Sucesso";
$_['alert'] = "Alerta";

$_['notification']['imported_n_products'] = "Foram importados %s produtos";
$_['notification']['updated_n_products'] = "Foram atualizados %s produtos";
$_['notification']['imported_product_reference'] = "Artigo importado (referência):";
$_['notification']['updated_product_reference'] = "Artigo atualizado (referência):";

$_['tooltip']['document_set'] = 'Selecciona a série de documentos que queres usar ao emitir um documento.';
$_['tooltip']['document_type'] = 'Podes depois converter o documento directamente na tua conta moloni.';
$_['tooltip']['document_status'] = "Escolhe o estado do documento que queres inserir. Podes sempre fechar o documento depois na tua área de cliente.";
$_['tooltip']['shipping_details'] = "Inclui detalhes de transporte no documento.";
$_['tooltip']['shipping_document'] = "Criar uma guia de transporte juntamente com o documento.";

$_['tooltip']['cae'] = "Escolhe o teu código de actividade económica";

$_['tooltip']['client_email'] = "Enviar email com o documento para o cliente.";
$_['tooltip']['client_update'] = "Actualizar os dados do cliente se já existir com o mesmo contribuinte.";
$_['tooltip']['client_prefix'] = "Choose a prefir for the client reference. If you choose for example 'MOLONI', the client reference will start with MOLONI.";
$_['tooltip']['client_vat'] = "Choose the custom field you want to use as VAT number";
$_['tooltip']['client_maturity_date'] = "Choose the maturity date to be used as default for this customer";

$_['tooltip']['products_tax'] = "If your prices already have taxes included, select the tax you used. If your taxes are correct in Opencart, we can use the correct taxes";
$_['tooltip']['shipping_tax'] = "If your prices already have taxes included, select the tax you used. If your taxes are correct in Opencart, we can use the correct taxes";
$_['tooltip']['products_tax_exemption'] = "Choose an exemption reason to be used only when a product does not have a tax associated.";
$_['tooltip']['shipping_tax_exemption'] = "Choose an exemption reason to be used only when the shipping method does not have a tax associated.";
$_['tooltip']['products_prefix'] = "Choose a prefir for the product reference. If you choose for example 'MOLONI', the product reference will start with MOLONI.";
$_['tooltip']['import_products'] = "Todos os artigos da sua conta Moloni serão importados";
$_['tooltip']['import_tax_class'] = "Escolha a classe de taxas que será aplicada aos artigos importados";
$_['tooltip']['import_product_since'] = "Escolha a data a partir da qual serão importados os artigos. Por defeito serão importados todos os artigos criados/modificados nos últimos 7 dias";

$_['tooltip']['measure_unit'] = "Choose a unit measurement to be used by default in your Moloni products.";

$_['tooltip']['order_auto'] = "Create a documento automatically when the order is set to paid.";
$_['tooltip']['moloni_options_reference'] = "Enable the use of referecence sufixes in options of type 'Select', this is usefull to create unique products in Moloni.";
$_['tooltip']['products_auto'] = "Create products automatically when they are inserted or edited in Opencart.";
$_['tooltip']['products_description_document'] = "Incluir resumo de artigos em documentos.";
$_['tooltip']['products_description_moloni'] = "Incluir resumo de artigos na criação da ficha de artigo.";
$_['tooltip']['remove_extra_tax'] = "Remover IVA nas taxas extra (e.g Taxas de pagamento).";
$_['tooltip']['remove_extra_tax_shipping'] = "Remover IVA nos portes";
$_['tooltip']['replace_white_space'] = "Substituir espaços em branco por underscore na referência do produto";

$_['tooltip']['debug_console'] = "Show a console with all the requests to moloni API.";
$_['tooltip']['git_username'] = "Choose the Github username you which to update from (default: nunong21)";
$_['tooltip']['git_repository'] = "Choose the Github repository you which to update from (default: opencart3)";
$_['tooltip']['git_branch'] = "Choose the Github branch you which to update from (default: master)";

$_['tooltip']['store_location'] = "Choose the delivery departure address to use. If you choose the default, we will use your moloni account settings";

$_['label']['yes'] = "Yes";
$_['label']['no'] = "No";

$_['label']['products'] = "Products";
$_['label']['clients'] = "Clients";
$_['label']['products_clients'] = "Products and clients";
$_['label']['document_settings'] = "Document Settings";
$_['label']['shipping'] = "Shipping";
$_['label']['orders'] = "Orders";
$_['label']['tools'] = "Ferramentas";
$_['label']['none'] = "Nenhuma";
$_['label']['import_product_since'] = "Importar artigos desde";


$_['label']['document'] = "Document";
$_['label']['document_set'] = "Document set";
$_['label']['document_type'] = "Document Type";
$_['label']['document_status'] = "Status";
$_['label']['cae'] = "EAC.";
$_['label']['shipping_details'] = "Shipping Details";
$_['label']['shipping_document'] = "Shipping Document";
$_['label']['store_location'] = "Delivery Departure Address";

$_['label']['invoices'] = "Invoices";
$_['label']['invoiceReceipts'] = "Invoice Receipts";
$_['label']['simplifiedInvoices'] = "Simplified Invoices";
$_['label']['billsOfLading'] = "Bills of Landing";
$_['label']['deliveryNotes'] = "Delivery Notes";
$_['label']['purchaseOrder'] = "Nota Encomenda";
$_['label']['internalDocuments'] = "Internal Documents";
$_['label']['estimates'] = "Estimates";

$_['label']['client_update'] = "Update client";
$_['label']['client_email'] = "Send email";
$_['label']['client_prefix'] = "Ref. Prefix";
$_['label']['client_vat'] = "Vat field";
$_['label']['client_maturity_date'] = "Maturity Date";

$_['label']['draft'] = "Draft";
$_['label']['closed'] = "Closed";

$_['label']['products_tax'] = "Products Tax";
$_['label']['shipping_tax'] = "Shipping Tax";
$_['label']['products_tax_exemption'] = "Tax exemption";
$_['label']['shipping_tax_exemption'] = "Shipping tax exemption";
$_['label']['let_opencart_decide'] = "Let opencart decide";
$_['label']['products_prefix'] = "Ref. Prefix";
$_['label']['import_products'] = "Importar artigos";
$_['label']['update_import_products'] = "Atualizar dados de artigos";
$_['label']['update_import_products_name'] = "Título";
$_['label']['update_import_products_stock'] = "Stock";
$_['label']['update_import_products_price'] = "Preço";
$_['label']['update_import_products_image'] = "Imagem";
$_['label']['import_tax_class'] = "Tax class dos artigos importados";
$_['label']['products_at_category'] = "AT Category";
$_['label']['measure_unit'] = "Unidade de categoria";

$_['label']['products_auto'] = "Criar produtos automaticamente";
$_['label']['products_description_document'] = "Incluir resumo de artigo em documentos";
$_['label']['products_description_moloni'] = "Incluir resumo na ficha de artigo";
$_['label']['remove_extra_tax'] = "Remover IVA nas taxas extra";
$_['label']['remove_extra_tax_shipping'] = "Remover IVA nos portes";
$_['label']['moloni_options_reference'] = "Use references on options";
$_['label']['replace_white_space'] = "Substituir espaço por underscore";

$_['label']['order_since'] = "Show orders since";
$_['label']['order_auto'] = "Create order when paid";
$_['label']['order_status'] = "Order status";

$_['label']['order_table_customer_info'] = "Customer";
$_['label']['order_table_order_status'] = "Status";
$_['label']['order_table_store_name'] = "Store";
$_['label']['order_table_order_date'] = "Date";
$_['label']['order_table_order_total'] = "Total";
$_['label']['order_table_order_actions'] = "Actions";
$_['label']['order_table_number'] = "Number";
$_['label']['order_table_body_wait'] = "Aguarde, a obter dados";

$_['label']['developers'] = "Developers";
$_['label']['debug_console'] = "Debug console";

$_['label']['git_username'] = "Git username";
$_['label']['git_repository'] = "Git repository";
$_['label']['git_branch'] = "Git branch";

$_['label']['download_logs'] = "Descarregar registos";
