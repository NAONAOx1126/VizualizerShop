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
 * 商品のモデルです。
 *
 * @package VizualizerShop
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerShop_Model_Product extends VizualizerShop_Model_MallModel
{

    /**
     * コンストラクタ
     *
     * @param $values モデルに初期設定する値
     */
    public function __construct($values = array())
    {
        $loader = new Vizualizer_Plugin("shop");
        parent::__construct($loader->loadTable("Products"), $values);
    }

    /**
     * 主キーでデータを取得する。
     *
     * @param $product_id 商品ID
     */
    public function findByPrimaryKey($product_id)
    {
        $this->findBy(array("product_id" => $product_id));
    }

    /**
     * 商品カテゴリのデータを取得する。
     */
    public function productCategorys()
    {
        $loader = new Vizualizer_Plugin("shop");
        $model = $loader->loadModel("ProductCategory");
        return $model->findAllByProductId($this->product_id);
    }

    /**
     * 商品タグのデータを取得する。
     */
    public function productTags()
    {
        $loader = new Vizualizer_Plugin("shop");
        $model = $loader->loadModel("ProductTag");
        return $model->findAllByProductId($this->product_id);
    }

    /**
     * オプションセットのデータを取得する。
     */
    public function optionSets()
    {
        $loader = new Vizualizer_Plugin("shop");
        $model = $loader->loadModel("OptionSet");
        return $model->findAllByProductId($this->product_id);
    }

    /**
     * 商品オプションのデータを取得する。
     */
    public function productOptions()
    {
        $loader = new Vizualizer_Plugin("shop");
        $model = $loader->loadModel("ProductOption");
        return $model->findAllByProductId($this->product_id);
    }

}
