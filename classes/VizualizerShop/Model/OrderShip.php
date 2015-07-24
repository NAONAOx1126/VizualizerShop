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
 * 注文配送のモデルです。
 *
 * @package VizualizerShop
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerShop_Model_OrderShip extends Vizualizer_Plugin_Model
{

    /**
     * コンストラクタ
     *
     * @param $values モデルに初期設定する値
     */
    public function __construct($values = array())
    {
        $loader = new Vizualizer_Plugin("shop");
        parent::__construct($loader->loadTable("OrderShips"), $values);
    }

    /**
     * 主キーでデータを取得する。
     *
     * @param $order_ship_id 注文配送ID
     */
    public function findByPrimaryKey($order_ship_id)
    {
        $this->findBy(array("order_ship_id" => $order_ship_id));
    }

    /**
     * 注文配送先情報を取得する
     */
    public function orderDetails(){
        $loader = new Vizualizer_Plugin("shop");
        $model = $loader->loadModel("OrderDetail");
        return $model->findAllBy(array("order_ship_id" => $this->order_ship_id));
    }

    /**
     * 注文情報を取得する
     */
    public function order(){
        $loader = new Vizualizer_Plugin("shop");
        $model = $loader->loadModel("Order");
        $model->findByPrimaryKey($this->order_id);
        return $model;
    }

    /**
     * データを登録する。
     */
    public function save($ignoreOperator = false)
    {
        $shipped = false;
        if (array_key_exists("shipment_status", $this->values_org) && array_key_exists("shipment_status", $this->values)) {
            if ($this->values_org["shipment_status"] != 2 && $this->values_org["shipment_status"] != 3 && $this->values["shipment_status"] == 2) {
                $shipped = true;
            }
        }
        parent::save($ignoreOperator);
        if($shipped && Vizualizer_Configure::exists("shipped_mail_title") && Vizualizer_Configure::exists("shipped_mail_template")){
            // メールの内容を作成
            $title = Vizualizer_Configure::get("shipped_mail_title");
            $templateName = Vizualizer_Configure::get("shipped_mail_template");
            $attr = Vizualizer::attr();
            $template = $attr["template"];
            if(!empty($template)){
                $body = $template->fetch($templateName.".txt");

                // ショップの情報を取得
                $loader = new Vizualizer_Plugin("admin");
                $company = $loader->loadModel("Company");
                $order = $this->order();
                if($order->isLimitedCompany() && $order->limitCompanyId() > 0){
                    $company->findByPrimaryKey($order->limitCompanyId());
                }else{
                    $company->findBy(array());
                }

                // 購入者にメール送信
                $mail = new Vizualizer_Sendmail();
                $mail->setFrom($company->email);
                $mail->setTo($order->order_email);
                $mail->setSubject($title);
                $mail->addBody($body);
                $mail->send();

                // ショップにメール送信
                $mail = new Vizualizer_Sendmail();
                $mail->setFrom($order->order_email);
                $mail->setTo($company->email);
                $mail->setSubject($title);
                $mail->addBody($body);
                $mail->send();
            }
        }
    }
}
