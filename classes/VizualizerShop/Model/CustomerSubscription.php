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
 * 定期購入契約のモデルです。
 *
 * @package VizualizerShop
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerShop_Model_CustomerSubscription extends Vizualizer_Plugin_Model
{
    const STATUS_INACTIVE = 0;

    const STATUS_ACTIVE = 1;

    /**
     * コンストラクタ
     *
     * @param $values モデルに初期設定する値
     */
    public function __construct($values = array())
    {
        $loader = new Vizualizer_Plugin("shop");
        parent::__construct($loader->loadTable("CustomerSubscriptions"), $values);
    }

    /**
     * 主キーでデータを取得する。
     *
     * @param $customer_subscription_id 定期購入契約ID
     */
    public function findByPrimaryKey($customer_subscription_id)
    {
        $this->findBy(array("customer_subscription_id" => $customer_subscription_id));
    }

    /**
     * 購読マスタを取得する。
     */
    public function subscription()
    {
        $loader = new Vizualizer_Plugin("shop");
        $model = $loader->loadModel("Subscription");
        $model->findByPrimaryKey($this->subscription_id);
        return $model;
    }

    /**
     * 定期購入の情報を元に注文を作成
     */
    public function purchase($order_code = ""){
        // カートを生成
        $loader = new Vizualizer_Plugin("shop");
        $cart = $loader->loadModel("Cart");

        // 顧客情報をカートに設定
        $memberLoader = new Vizualizer_Plugin("member");
        $customer = $memberLoader->loadModel("Customer");
        $customer->findByPrimaryKey($this->customer_id);
        $cart->setCustomer($customer);

        // 配送先情報をカートに設定
        $customerShip = $loader->loadModel("CustomerShip");
        $customerShip->findByPrimaryKey($this->customer_id);
        $cart->setCustomerShip($customerShip);

        // 決済情報をカートに設定
        $payment = $loader->loadModel("Payment");
        $payment->findByPrimaryKey($this->payment_id);
        $cart->setPayment($payment);

        // 配送情報をカートに設定
        $ship = $loader->loadModel("Ship");
        $payment->findByPrimaryKey($this->ship_id);
        $cart->setShip($ship);

        // カートに商品を追加
        $cart->clearProducts();
        $cart->addProductById($this->subscription()->product_option_id);

        // 購入を確定
        $cart->purchase($order_code);
    }
}
