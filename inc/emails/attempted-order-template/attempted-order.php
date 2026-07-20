<?php
class AttemptedOrder {

    public function renderHtml( $attempted_order ) {
        $customer = json_decode( $attempted_order->customer );
        $cart = json_decode( $attempted_order->cart_content );
        $cart_items = $cart->cart_contents;

        include "attempted-order-body.php";
    }
}

?>