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

    const STATUS_SUSPENDED = 2;

    const STATUS_CANCEL = 3;

    /**
     * コンストラクタ
     *
     * @param $values モデルに初期設定する値
     */
    public function __construct($values = array())
    {
        $loader = new Vizualizer_Plugin("shop");
        parent::__construct($loader->loadTable("SubscriptionView"), $values);
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
     * 顧客配送先を取得する。
     */
    public function customer()
    {
        $loader = new Vizualizer_Plugin("member");
        $model = $loader->loadModel("Customer");
        $model->findByPrimaryKey($this->customer_id);
        return $model;
    }

    /**
     * 顧客配送先を取得する。
     */
    public function customerShip()
    {
        $loader = new Vizualizer_Plugin("shop");
        $model = $loader->loadModel("CustomerShip");
        $model->findByPrimaryKey($this->customer_ship_id);
        return $model;
    }

    /**
     * 決済方法を取得する。
     */
    public function payment()
    {
        $loader = new Vizualizer_Plugin("shop");
        $model = $loader->loadModel("Payment");
        $model->findByPrimaryKey($this->payment_id);
        return $model;
    }

    /**
     * 配送方法を取得する。
     */
    public function ship()
    {
        $loader = new Vizualizer_Plugin("shop");
        $model = $loader->loadModel("Ship");
        $model->findByPrimaryKey($this->ship_id);
        return $model;
    }

    /**
     * 配送方法を取得する。
     */
    public function orders()
    {
        $loader = new Vizualizer_Plugin("shop");
        $model = $loader->loadModel("Order");
        return $model->findAllBy(array("customer_subscription_id" => $this->customer_subscription_id));
    }

    /**
     * 配送方法を取得する。
     */
    public function orderViews()
    {
        $loader = new Vizualizer_Plugin("shop");
        $model = $loader->loadModel("OrderView");
        return $model->findAllBy(array("customer_subscription_id" => $this->customer_subscription_id));
    }

    /**
     * 小計を取得する。
     */
    public function getSubtotal()
    {
        $product = $this->subscription()->productOption()->product();
        return $product->price;
    }

    /**
     * 決済手数料を取得する。
     */
    public function getCharge()
    {
        return $this->payment()->getCharge($this->getSubtotal());
    }

    /**
     * 配送料を取得する。
     */
    public function getShipFee()
    {
        $product = $this->subscription()->productOption()->product();
        $customerShip = $this->customerShip();
        return $this->ship()->getShipFee($product->weight, $customerShip->pref, $customerShip->address1, $customerShip->address2);
    }
}
