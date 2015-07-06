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
 * 特別配送のモデルです。
 *
 * @package VizualizerShop
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerShop_Model_SpecialShip extends Vizualizer_Plugin_Model
{

    /**
     * コンストラクタ
     *
     * @param $values モデルに初期設定する値
     */
    public function __construct($values = array())
    {
        $loader = new Vizualizer_Plugin("shop");
        parent::__construct($loader->loadTable("SpecialShips"), $values);
    }

    /**
     * 主キーでデータを取得する。
     *
     * @param $special_ship_id 特別配送ID
     */
    public function findByPrimaryKey($special_ship_id)
    {
        $this->findBy(array("special_ship_id" => $special_ship_id));
    }

    /**
     * 配送IDでデータを取得する。
     */
    public function findAllByShipId($ship_id)
    {
        return $this->findAllBy(array("ship_id" => $ship_id), "LENGTH(".$this->access->address_prefix.")", true);
    }

    /**
     * 配送方法データを取得する。
     */
    public function ship()
    {
        $loader = new Vizualizer_Plugin("shop");
        $model = $loader->loadModel("Ship");
        $model->findByPrimaryKey($this->ship_id);
        return $model;
    }
}
