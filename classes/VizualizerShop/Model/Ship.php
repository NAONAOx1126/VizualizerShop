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
 * 配送のモデルです。
 *
 * @package VizualizerShop
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerShop_Model_Ship extends VizualizerShop_Model_MallModel
{
    /**
     * 発送待ち
     */
    const SHIP_NEW = 0;

    /**
     * 発送済み
     */
    const SHIP_SHIPPED = 1;

    /**
     * コンストラクタ
     *
     * @param $values モデルに初期設定する値
     */
    public function __construct($values = array())
    {
        $loader = new Vizualizer_Plugin("shop");
        parent::__construct($loader->loadTable("Ships"), $values);
    }

    /**
     * 主キーでデータを取得する。
     *
     * @param $ship_id 配送ID
     */
    public function findByPrimaryKey($ship_id)
    {
        $this->findBy(array("ship_id" => $ship_id));
    }

    /**
     * 商品オプションのデータを取得する。
     */
    public function specialShips()
    {
        $loader = new Vizualizer_Plugin("shop");
        $model = $loader->loadModel("SpecialShip");
        return $model->findAllByShipId($this->ship_id);
    }

    /**
     * 商品オプションのデータを取得する。
     */
    public function shipTimes()
    {
        $loader = new Vizualizer_Plugin("shop");
        $model = $loader->loadModel("ShipTime");
        return $model->findAllByShipId($this->ship_id);
    }

    /**
     * 配送先情報から送料を算出
     */
    public function ShipFee($pref, $address1, $address2)
    {
        $specials = $this->specialShips();
        $loader = new Vizualizer_Plugin("address");
        $model = $loader->loadModel("Pref");
        foreach ($specials as $special) {
            $model->findByPrimaryKey($special->pref_id);
            if ($model->pref_name == $pref) {
                if (empty($special->address_prefix) || preg_match("/^".preg_quote($special->address_prefix, "/")."/iu") > 0) {
                    return $special->ship_fee;
                }
            }
        }
        return $this->ship_fee;
    }
}
