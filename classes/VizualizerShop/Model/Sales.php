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
 * ショップ用の売上のモデルです。
 *
 * @package VizualizerShop
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerShop_Model_Sales extends Vizualizer_Plugin_Model
{
    /**
     * コンストラクタ
     *
     * @param $values モデルに初期設定する値
     */
    public function __construct($values = array())
    {
        $loader = new Vizualizer_Plugin("shop");
        parent::__construct($loader->loadTable("Sales"), $values);
    }

    /**
     * 主キーでデータを取得する。
     *
     * @param $sales_id 売上ID
     */
    public function findByPrimaryKey($sales_id)
    {
        $this->findBy(array("sales_id" => $sales_id));
    }

    /**
     * 法人IDと年月でデータを取得する。
     *
     * @param $company_id 法人ID
     * @param $month 年月を表す日付文字列
     */
    public function findByCompanyMonth($company_id, $month)
    {
        $this->findBy(array("company_id" => $company_id, "sales_month" => date("Y-m-01", strtotime($month))));
    }

    /**
     * 法人IDでデータを取得する。
     *
     * @param $company_id 法人ID
     */
    public function findAllByCompanyId($company_id)
    {
        return $this->findAllBy(array("company_id" => $company_id), "sales_month");
    }
}
