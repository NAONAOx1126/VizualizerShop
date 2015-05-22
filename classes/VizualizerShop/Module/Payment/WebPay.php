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

use WebPay\WebPay;

/**
 * WEBPAYの決済を仕様した決済処理を実行する。
 *
 * @package VizualizerShop
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerShop_Module_Payment_WebPay extends Vizualizer_Plugin_Module
{
    const WEBPAY_SECRET_KEY = "webpay_secret";

    function execute($params)
    {
        // カートのデータを呼び出し
        $loader = new Vizualizer_Plugin("shop");
        $cart = $loader->loadModel("Cart");

        // 購入データの作成が完了した場合、WEBPAYの決済処理を実行する。

        // トランザクションの開始
        $connection = Vizualizer_Database_Factory::begin("shop");

        try {
            $post = Vizualizer::request();

            // 購入のデータを作成し登録する。（エラー時にロールバックで戻す必要があるため）
            $usePoint = "";
            if($params->check("point")){
                $usePoint = $post[$params->get("point")];
            }
            $result = $cart->purchase("", $usePoint, $params->get("order_status", 0));
            if($result instanceof VizualizerShop_Model_CustomerSubscription){
                // 定期購入の場合
                $customer = $result->customer();
                $webpay = new WebPay(Vizualizer_Configure::get(WEBPAY_SECRET_KEY));
                $data = array();
                $data["card"] = $post["webpay-token"];
                $data["email"] = $customer->email;
                $wpCustomer = $webpay->customer->create($data);
                $customer->customer_code = $wpCustomer->id;
                $customer->save();

                $subscription = $result->subscription();
                $product = $subscription->productOption()->product();

                $data = array();
                $data["customer"] = $wpCustomer->id;
                if($cart->isLimitedCompany()){
                    $adminLoader = new Vizualizer_Plugin("admin");
                    $company = $adminLoader->loadModel("Company");
                    $company->findByPrimaryKey($subscription->company_id);
                    $data["shop"] = $company->description;
                }
                $data["amount"] = $subscription->price;
                $data["currency"] = $params->get("currency", "jpy");
                $data["period"] = "month";
                $data["description"] = $product->product_name;
                $recursion = $webpay->recursion->create($data);
                $result->customer_subscription_code = $recursion->id;
                $result->save();
            }elseif($result instanceof VizualizerShop_Model_Order){
                // 通常注文の場合
                $webpay = new WebPay(Vizualizer_Configure::get(WEBPAY_SECRET_KEY));
                $data = array();
                $data["amount"] = $order->payment_total;
                $data["currency"] = $params->get("currency", "jpy");
                $data["card"] = $post["webpay-token"];
                $wpOrder = $webpay->charge->create($data);
                $order->order_code = $wpOrder->id;
                $order->save();
            }else{
                throw new Vizualizer_Exception_Invalid("result", "Invalid result for cart purchase.");
            }

            // エラーが無かった場合、処理をコミットする。
            Vizualizer_Database_Factory::commit($connection);
        } catch (Exception $e) {
            Vizualizer_Database_Factory::rollback($connection);
            throw new Vizualizer_Exception_Database($e);
        }

    }
}
