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
 * 重量別配送のモデルです。
 *
 * @package VizualizerShop
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerShop_Model_ShipWeight extends VizualizerShop_Model_MallModel
{
    /**
     * コンストラクタ
     *
     * @param $values モデルに初期設定する値
     */
    public function __construct($values = array())
    {
        $loader = new Vizualizer_Plugin("shop");
        parent::__construct($loader->loadTable("ShipWeights"), $values);
    }

    /**
     * 主キーでデータを取得する。
     *
     * @param $ship_weight_id 重量配送ID
     */
    public function findByPrimaryKey($ship_weight_id)
    {
        $this->findBy(array("ship_weight_id" => $ship_weight_id));
    }

    /**
     * 商品オプションのデータを取得する。
     */
    public function addressShip($address)
    {
        $loader = new Vizualizer_Plugin("shop");
        $model = $loader->loadModel("ShipAddress");
        $addresses = $model->findAllBy(array("ship_weight_id" => $this->ship_weight_id, "inpre:address_prefix" => $address), "LENGTH(address_prefix)", true);
        return $addresses->current();
    }
}
