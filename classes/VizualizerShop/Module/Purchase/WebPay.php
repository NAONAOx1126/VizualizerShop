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
class VizualizerShop_Module_Purchase_WebPay extends Vizualizer_Plugin_Module
{
    const WEBPAY_SECRET_KEY = "webpay_secret";

    function execute($params)
    {
        // カートのデータを呼び出し
        $loader = new Vizualizer_Plugin("shop");
        $cart = $loader->loadModel("Cart");

        // トランザクションの開始
        $memberConnection = Vizualizer_Database_Factory::begin("member");
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
            $webpay = new WebPay(Vizualizer_Configure::get(self::WEBPAY_SECRET_KEY));

            // 正常に成功した場合、登録するとしていた場合は、カード情報を登録する。
            if ($post["save_card"] === "1" && substr_compare($post["webpay-token"], "tok_", 0, 4) === 0 && $result->customer_id > 0) {
                // 顧客情報を取得する。
                $memberLoader = new Vizualizer_Plugin("member");
                $customer = $memberLoader->loadModel("Customer");
                $customer->findByPrimaryKey($result->customer_id);

                // 顧客登録を行い、トークンを取得する。
                $data = array();
                $data["card"] = $post["webpay-token"];
                $data["email"] = $customer->email;
                Vizualizer_Logger::writeDebug(print_r($data, true));
                $wpCustomer = $webpay->customer->create($data);
                $post->set("webpay-token", $wpCustomer->id);

                // 取得した顧客のIDをトークンとして処理する。
                $paymentToken = $loader->loadModel("PaymentToken");
                $paymentToken->findBy(array("customer_id" => $result->customer_id, "payment_id" => $result->payment_id, "token" => $post["webpay-token"]));
                if (!($paymentToken->payment_token_id > 0)) {
                    $paymentToken->customer_id = $result->customer_id;
                    $paymentToken->payment_id = $result->payment_id;
                    $paymentToken->token = $post["webpay-token"];
                    $paymentToken->save();
                }
            }

            if($result instanceof VizualizerShop_Model_CustomerSubscription){
                // 定期購入の場合
                $memberLoader = new Vizualizer_Plugin("member");
                $customer = $memberLoader->loadModel("Customer");
                $customer->findByPrimaryKey($result->customer_id);
                $subscription = $result->subscription();
                $product = $subscription->productOption()->product();

                if (substr_compare($post["webpay-token"], "tok_", 0, 4) === 0) {
                    $data = array();
                    $data["card"] = $post["webpay-token"];
                    $data["email"] = $customer->email;
                    Vizualizer_Logger::writeDebug(print_r($data, true));
                    $wpCustomer = $webpay->customer->create($data);
                    $result->customer_code = $wpCustomer->id;
                } elseif (substr_compare($post["webpay-token"], "cus_", 0, 4) === 0) {
                    $result->customer_code = $post["webpay-token"];
                } else {
                    throw new Vizualizer_Exception_Invalid("webpay-token", "渡されたトークンが正しくありません");
                }
                Vizualizer_Logger::writeDebug(print_r($result->toArray(), true));
                $result->update();

                $data = array();
                $data["customer"] = $result->customer_code;
                if($cart->isLimitedCompany()){
                    $adminLoader = new Vizualizer_Plugin("admin");
                    $company = $adminLoader->loadModel("Company");
                    $company->findByPrimaryKey($subscription->company_id);
                    if($company->company_extra_code != ""){
                        $data["shop"] = $company->company_extra_code;
                    }
                }
                $data["amount"] = $subscription->price;
                $data["currency"] = $params->get("currency", "jpy");
                $data["period"] = "month";
                $data["description"] = $product->product_name;
                Vizualizer_Logger::writeDebug(print_r($data, true));
                $recursion = $webpay->recursion->create($data);
                $result->customer_subscription_code = $recursion->id;
                Vizualizer_Logger::writeDebug(print_r($result->toArray(), true));
                $result->update();
            }elseif($result instanceof VizualizerShop_Model_Order){
                // 通常注文の場合
                $data = array();
                if($cart->isLimitedCompany()){
                    $adminLoader = new Vizualizer_Plugin("admin");
                    $company = $adminLoader->loadModel("Company");
                    $company->findByPrimaryKey($result->company_id);
                    if($company->company_extra_code != ""){
                        $data["shop"] = $company->company_extra_code;
                    }
                }
                $data["amount"] = $result->payment_total;
                $data["currency"] = $params->get("currency", "jpy");
                if (substr_compare($post["webpay-token"], "tok_", 0, 4) === 0) {
                    $data["card"] = $post["webpay-token"];
                } elseif (substr_compare($post["webpay-token"], "cus_", 0, 4) === 0) {
                    $data["customer"] = $post["webpay-token"];
                } else {
                    throw new Vizualizer_Exception_Invalid("webpay-token", "渡されたトークンが正しくありません");
                }
                $wpOrder = $webpay->charge->create($data);
                $result->order_code = $wpOrder->id;
                $result->save();
            }else{
                throw new Vizualizer_Exception_Invalid("result", "Invalid result for cart purchase.");
            }

            // エラーが無かった場合、処理をコミットする。
            Vizualizer_Database_Factory::commit($memberConnection);
            Vizualizer_Database_Factory::commit($connection);
        } catch (Exception $e) {
            Vizualizer_Database_Factory::rollback($memberConnection);
            Vizualizer_Database_Factory::rollback($connection);
            throw $e;
        }

    }
}
