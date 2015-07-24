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
 * 購入時決済を含まない購入処理を実行する。
 *
 * @package VizualizerShop
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerShop_Module_Purchase_Purchase extends Vizualizer_Plugin_Module
{
    function execute($params)
    {
        // カートのデータを呼び出し
        $loader = new Vizualizer_Plugin("shop");
        $cart = $loader->loadModel("Cart");

        if(!empty($cart->payment->payment_module_name)){
            $purchase = $loader->loadModule("Purchase.".$cart->payment->payment_module_name);
            $purchase->execute($params);
        }else{
            // トランザクションの開始
            $connection = Vizualizer_Database_Factory::begin("shop");

            try {
                $post = Vizualizer::request();

                // 購入のデータを作成し登録する。（エラー時にロールバックで戻す必要があるため）
                $usePoint = "";
                if($params->check("point")){
                    $usePoint = $post[$params->get("point")];
                }
                $cart->setUsePoint($usePoint);
                $cart->setOrderStatus($params->get("order_status", 0));
                $result = $cart->purchase("");

                // エラーが無かった場合、処理をコミットする。
                Vizualizer_Database_Factory::commit($connection);
            } catch (Exception $e) {
                Vizualizer_Database_Factory::rollback($connection);
                throw new Vizualizer_Exception_Database($e);
            }
        }
    }
}
