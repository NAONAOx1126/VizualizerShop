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
 * オプションのモデルです。
 *
 * @package VizualizerShop
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerShop_Model_Option extends Vizualizer_Plugin_Model
{

    /**
     * コンストラクタ
     *
     * @param $values モデルに初期設定する値
     */
    public function __construct($values = array())
    {
        $loader = new Vizualizer_Plugin("shop");
        parent::__construct($loader->loadTable("Options"), $values);
    }

    /**
     * 主キーでデータを取得する。
     *
     * @param $option_id オプションID
     */
    public function findByPrimaryKey($option_id)
    {
        $this->findBy(array("option_id" => $option_id));
    }

    /**
     * 種別IDでデータを取得する。
     * @param int $option_type_id 種別ID
     */
    public function findAllByTypeId($option_type_id)
    {
        return $this->findAllBy(array("option_type_id" => $option_type_id));
    }

    /**
     * 種別データを取得する。
     */
    public function type()
    {
        $loader = new Vizualizer_Plugin("shop");
        $model = $loader->loadModel("OptionType");
        $model->findByPrimaryKey($this->option_type_id);
        return $model;
    }
}
