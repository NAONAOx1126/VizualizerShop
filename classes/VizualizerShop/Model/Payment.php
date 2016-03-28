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
 * 決済のモデルです。
 *
 * @package VizualizerShop
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerShop_Model_Payment extends VizualizerShop_Model_MallModel
{
    /**
     * 決済未了
     */
    const PAYMENT_NEW = 0;

    /**
     * 決済済み
     */
    const PAYMENT_PAYED = 1;

    /**
     * コンストラクタ
     *
     * @param $values モデルに初期設定する値
     */
    public function __construct($values = array())
    {
        $loader = new Vizualizer_Plugin("shop");
        parent::__construct($loader->loadTable("Payments"), $values);
    }

    /**
     * 主キーでデータを取得する。
     *
     * @param $payment_id 決済ID
     */
    public function findByPrimaryKey($payment_id)
    {
        $this->findBy(array("payment_id" => $payment_id));
    }

    public function getCharge($total)
    {
        if ($this->charge5_total > 0 && $this->charge5_total < $total) {
            return $this->charge5;
        }
        if ($this->charge4_total > 0 && $this->charge4_total < $total) {
            return $this->charge4;
        }
        if ($this->charge3_total > 0 && $this->charge3_total < $total) {
            return $this->charge3;
        }
        if ($this->charge2_total > 0 && $this->charge2_total < $total) {
            return $this->charge2;
        }
        return $this->charge1;
    }

    /**
     * トークンのリストを取得する。
     */
    public function tokens($customer_id = 0){
        if($customer_id > 0){
            $loader = new Vizualizer_Plugin("shop");
            $model = $loader->loadModel("PaymentToken");
            return $model->findAllByCustomerPayment($customer_id, $this->payment_id);
        }
        return array();
    }
}
