<?php

/**
 * Copyright (C) 2012 Vizualizer All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @author    Naohisa Minagawa <info@vizualizer.jp>
 * @copyright Copyright (c) 2010, Vizualizer
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache License, Version 2.0
 * @since PHP 5.3
 * @version   1.0.0
 */

/**
 * 入力された購入者情報をカートに設定するモジュール
 *
 * @package VizualizerShop
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerShop_Module_Purchase_Customer extends Vizualizer_Plugin_Module
{
    function execute($params)
    {
        // 入力パラメータを取得
        $post = Vizualizer::request();

        // カートのモデルを取得
        $loader = new Vizualizer_Plugin("shop");
        $cart = $loader->loadModel("Cart");

        // 入力データから顧客データを構築
        $customerData = array();
        foreach($post as $key => $value){
            if(preg_match("/^".$params->get("prefix", "order")."_(.+)$", $key, $p) > 0){
                $customerData[$p[1]] = $value;
            }
        }
        $memberLoader = new Vizualizer_Plugin("member");
        $customer = $memberLoader->loadModel("Customer", $customerData);

        // 構築した顧客データをカートに設定
        $cart->setCustomer($customer, $params->get("register", false));

        // 送信先が違う場合のチェックと送信先が同じ場合のチェックを捕捉
        if($post["other_ships"] != "1" || $post["same_ships"] > 0){
            $customerShip = $loader->loadModel("CutsomerShip", $customerData);
            $customerShip = $loader->loadModel("CutsomerShip", $customerData);
            $cart->setCustomerShip($customerShip);
        }
    }
}
