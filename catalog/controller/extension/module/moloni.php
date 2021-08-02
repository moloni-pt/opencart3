<?php

class ControllerExtensionModuleMoloni extends Controller {
    // model/checkout/order/addOrderHistory/after
    public function eventCreateDocument(&$route, &$args, &$output)
    {
        $domain = explode("route=", $this->request->server['HTTP_REFERER']);
        $token = explode("user_token=", $this->request->server['HTTP_REFERER']);
        $token = substr($token[1], 0, 32);
        $validRoute = 'checkout/order/addOrderHistory';
        $validStatus = [3, 5, 11, 15];
        $orderId = 0;
        $orderStatus = 0;

        if (!empty($args) && count($args) > 1) {
            $orderId = (int)$args[0];
            $orderStatus = (int)$args[1];
        }

        if ($validRoute === $route && $orderId > 0 && in_array($orderStatus, $validStatus)) {
            $this->response->redirect($domain[0] . "route=extension/module/moloni/invoice&amp;user_token=" . $token . "&amp;order_id=" . $orderId . "&amp;evento=moloni");
        }
    }
}
