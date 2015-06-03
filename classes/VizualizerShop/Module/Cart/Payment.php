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
 * カートから購入完了までのステップを処理するモジュール
 *
 * @package VizualizerShop
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerShop_Module_Cart_Payment extends Vizualizer_Plugin_Module
{
    function execute($params)
    {
        // カートのモデルを取得
        $loader = new Vizualizer_Plugin("shop");
        $cart = $loader->loadModel("Cart");

        if($post["customer_ship_id"] > 0){
            // customer_ship_idが渡された場合は顧客でかつ、配送先を指定されたと判断する。
            $ship = $loader->loadModel("CustomerShip");
            $ship->findByPrimaryKey($post["customer_ship_id"]);
            if($ship->customer_ship_id > 0){
                if(!$cart->setCustomerShip($ship)){
                    // 配送先の設定ができなかった場合はエラー
                    throw new Vizualizer_Exception_Invalid("customer_ship_id", "指定した配送先が正しくありません。");
                }
            }
        }elseif(!empty($post["sei"]) && !empty($post["mei"])){
            $memberLoader = new Vizualizer_Plugin("member");
            // トランザクションの開始
            $customer = $memberLoader->loadModel("Customer");
            foreach($post as $key => $value){
                $customer->$key = $value;
            }
            $cart->setCustomer($customer, $post["with_register"]);
            $ship = $loader->loadModel("CustomerShip");
            if(!empty($post["customer_to_ship"])){
                foreach($post as $key => $value){
                    $shipKey = "ship_".$key;
                    $ship->$shipKey = $value;
                }
            }else{
                foreach($post as $key => $value){
                    $ship->$key = $value;
                }
            }
            $cart->setCustomerShip($ship);
        }
    }
}
