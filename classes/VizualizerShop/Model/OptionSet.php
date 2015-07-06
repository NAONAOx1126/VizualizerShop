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
 * オプションセットのモデルです。
 *
 * @package VizualizerShop
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerShop_Model_OptionSet extends Vizualizer_Plugin_Model
{

    /**
     * コンストラクタ
     *
     * @param $values モデルに初期設定する値
     */
    public function __construct($values = array())
    {
        $loader = new Vizualizer_Plugin("shop");
        parent::__construct($loader->loadTable("OptionSets"), $values);
    }

    /**
     * 主キーでデータを取得する。
     *
     * @param $option_set_id オプションセットID
     */
    public function findByPrimaryKey($option_set_id)
    {
        $this->findBy(array("option_set_id" => $option_set_id));
    }

    /**
     * 親オプションセットIDからデータを取得する。
     */
    public function findAllByParentOptionSetId($parent_option_set_id)
    {
        return $this->findAllBy(array("parent_option_set_id" => $parent_option_set_id));
    }

    /**
     * 商品IDからデータを取得する。
     */
    public function findAllByProductId($product_id)
    {
        return $this->findAllBy(array("product_id" => $product_id));
    }

    /**
     * オプションIDからデータを取得する。
     */
    public function findAllByOptionId($option_id)
    {
        return $this->findAllBy(array("option_id" => $option_id));
    }

    /**
     * 親オプションセットのデータを取得する。
     */
    public function parentOptionSet(){
        $loader = new Vizualizer_Plugin("shop");
        $model = $loader->loadModel("OptionSet");
        $model->findByPrimaryKey($this->parent_option_set_id);
        return $model;
    }

    /**
     * 子オプションセットのデータを取得する。
     */
    public function childOptionSets()
    {
        $loader = new Vizualizer_Plugin("shop");
        $model = $loader->loadModel("OptionSet");
        return $model->findAllByParentOptionSetId($this->option_set_id);
    }

    /**
     * 商品データを取得する。
     */
    public function product()
    {
        $loader = new Vizualizer_Plugin("shop");
        $model = $loader->loadModel("Product");
        $model->findByPrimaryKey($this->product_id);
        return $model;
    }

    /**
     * オプションデータを取得する。
     */
    public function option()
    {
        $loader = new Vizualizer_Plugin("shop");
        $model = $loader->loadModel("Option");
        $model->findByPrimaryKey($this->option_id);
        return $model;
    }

    /**
     * 商品オプションのデータを取得する。
     */
    public function productOptions()
    {
        $loader = new Vizualizer_Plugin("shop");
        $model = $loader->loadModel("ProductOption");
        return $model->findAllByOptionSetId($this->option_set_id);
    }
}
