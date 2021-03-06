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
 * 商品タグのモデルです。
 *
 * @package VizualizerShop
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerShop_Model_ProductTag extends Vizualizer_Plugin_Model
{

    /**
     * コンストラクタ
     *
     * @param $values モデルに初期設定する値
     */
    public function __construct($values = array())
    {
        $loader = new Vizualizer_Plugin("shop");
        parent::__construct($loader->loadTable("ProductTags"), $values);
    }

    /**
     * 主キーでデータを取得する。
     *
     * @param $product_tag_id 商品オプションID
     */
    public function findByPrimaryKey($product_tag_id)
    {
        $this->findBy(array("product_tag_id" => $product_tag_id));
    }

    /**
     * 商品IDでデータを取得する。
     */
    public function findAllByProductId($product_id)
    {
        return $this->findAllBy(array("product_id" => $product_id));
    }

    /**
     * タグIDでデータを取得する。
     */
    public function findAllByCategoryId($tag_id)
    {
        return $this->findAllBy(array("tag_id" => $tag_id));
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
     * カテゴリデータを取得する。
     */
    public function tag()
    {
        $loader = new Vizualizer_Plugin("shop");
        $model = $loader->loadModel("Tag");
        $model->findByPrimaryKey($this->tag_id);
        return $model;
    }
}
