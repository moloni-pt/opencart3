<?php

class ControllerEventMoloni extends Controller {
    // model/checkout/order/addOrderHistory/after
    public function eventCreateDocument(&$route, &$args, &$output)
    {
        if(isset($args[0]) && isset($args[1])){
            if(in_array($args[1], ['5','15','11','3'])){
                $explodeUrl = explode("=",$this->request->server['HTTP_REFERER']);
                $user_token = substr($explodeUrl[2], 0, 32);
                $this->response->redirect("http://127.0.0.1/admin/index.php?route=extension/module/moloni/invoice&amp;user_token=".$user_token."&amp;order_id=".$args[0]."&amp;evento=moloni");
            }
        }
    }
}
