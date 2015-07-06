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
 * 決済トークンのモデルです。
 *
 * @package VizualizerShop
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerShop_Model_PaymentToken extends VizualizerShop_Model_MallModel
{
    /**
     * コンストラクタ
     *
     * @param $values モデルに初期設定する値
     */
    public function __construct($values = array())
    {
        $loader = new Vizualizer_Plugin("shop");
        parent::__construct($loader->loadTable("PaymentTokens"), $values);
    }

    /**
     * 主キーでデータを取得する。
     *
     * @param $payment_id 決済ID
     */
    public function findByPrimaryKey($payment_token_id)
    {
        $this->findBy(array("payment_token_id" => $payment_token_id));
    }

    /**
     * 顧客IDと決済IDからデータを取得する。
     */
    public function findAllByCustomerPayment($customer_id, $payment_id){
        return $this->findAllBy(array("customer_id" => $customer_id, "payment_id" => $payment_id));
    }

    /**
     * 決済の情報を取得する。
     */
    public function payment(){
        $loader = new Vizualizer_Plugin("shop");
        $model = $loader->loadModel("Payment");
        $model->findByPrimaryKey($this->payment_id);
        return $model;
    }

    /**
     * トークンの情報を取得する。
     */
    public function getInfo(){
        $webpay = new WebPay\WebPay($this->payment()->payment_secret);
        $customer = $webpay->customer->retrieve($this->token);
        return $customer->activeCard;
    }
}
