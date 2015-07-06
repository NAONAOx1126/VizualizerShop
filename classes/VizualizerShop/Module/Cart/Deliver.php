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
 * 配送先選択画面を処理するモジュール
 *
 * @package VizualizerShop
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerShop_Module_Cart_Deliver extends Vizualizer_Plugin_Module
{
    function execute($params)
    {
        // ログイン中の顧客情報を取得
        $customer = Vizualizer_Session::set(VizualizerMember::SESSION_KEY);
        if($customer && $customer->customer_id > 0){
            // 配送先のモデルを取得
            $loader = new Vizualizer_Plugin("shop");
            $ship = $loader->loadModel("CustomerShip");

            // カートにcustomerを登録
            $cart = $loader->loadModel("Cart");
            $cart->setCustomer($customer);

            // トランザクションの開始
            $connection = Vizualizer_Database_Factory::begin("shop");
            try{
                // registerで配送先の情報を登録
                if (!empty($post["register"])) {
                    foreach($post as $key => $value){
                        $ship->$key = $value;
                    }
                    $ship->customer_id = $customer->customer_id;
                    $ship->save();
                }

                // deleteで配送先の情報を削除
                if (!empty($post["delete"]) && $post["customer_ship_id"] > 0) {
                    $ship->findByPrimaryKey($post["customer_ship_id"]);
                    if($ship->customer_ship_id == $post["customer_ship_id"]){
                        $ship->delete();
                    }
                }

                // エラーが無かった場合、処理をコミットする。
                Vizualizer_Database_Factory::commit($connection);
            } catch (Exception $e) {
                Vizualizer_Database_Factory::rollback($connection);
                throw new Vizualizer_Exception_Database($e);
            }

            // 配送先のリストを取得
            $ships = $loader->findAllByCustomerId($customer->customer_id);
        }
    }
}
