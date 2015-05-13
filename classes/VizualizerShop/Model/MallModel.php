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
 * モール用の仮想モデルです。
 *
 * @package VizualizerShop
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerShop_Model_MallModel extends Vizualizer_Plugin_Model
{

    /**
     * データベースモデルを初期化する。
     * 初期の値を配列で渡すことで、その値でモデルを構築する。
     */
    public function __construct($accessTable, $values = array())
    {
        parent::__construct($accessTable, $values);
    }

    /**
     * 組織IDで制限がかかっているかどうかを返す
     * @return boolean
     */
    private function isLimitedCompany()
    {
        if(Vizualizer_Configure::get("shop_mall_activated")){
            return true;
        }
        return false;
    }

    /**
     * 制限の対象となっている組織IDを返す
     */
    private function limitCompanyId()
    {
        if($this->isLimitedCompany()){
            $shopCode = preg_replace("/\\.".preg_quote(Vizualizer_Configure::get("shop_mall_domain"))."$/", "", $_SERVER["SERVER_NAME"]);
            // ショップコードから対応する法人を取得
            $loader = new Vizualizer_Plugin("admin");
            $model = $loader->loadModel("Company");
            $model->findBy(array("company_code" => $shopCode));
            if($model->company_id > 0){
                return $model->company_id;
            }
        }
        return 0;
    }

    /**
     * レコードが作成可能な場合に、レコードを作成します。
     */
    public function create()
    {
        if ($this->isLimitedCompany() && $this->limitCompanyId() > 0) {
            $this->company_id = $this->limitCompanyId();
        }
        return parent::create();
    }

    /**
     * レコードを特定のキーで検索する。
     * 複数件ヒットした場合は、最初の１件をデータとして取得する。
     */
    public function findBy($values = array())
    {
        if ($this->isLimitedCompany() && $this->limitCompanyId() > 0) {
            $values["company_id"] = $this->limitCompanyId();
        }
        return parent::findBy($values);
    }

    /**
     * レコードを特定のキーで検索する。
     */
    public function findAllBy($values = array(), $order = "", $reverse = false, $forceOperator = false)
    {
        if ($this->isLimitedCompany() && $this->limitCompanyId() > 0) {
            $values["company_id"] = $this->limitCompanyId();
        }
        return parent::findAllBy($values, $order, $reverse, $forceOperator);
    }

    /**
     * レコードを特定のキーで検索する。
     */
    public function queryAllBy($select)
    {
        if ($this->isLimitedCompany() && $this->limitCompanyId() > 0) {
            $select->addWhere("company_id = ?", array($this->limitCompanyId()));
        }
        return parent::queryAllBy($select);
    }

    /**
     * レコードの件数を取得する。
     */
    public function countBy($values = array(), $columns = "*")
    {
        if ($this->isLimitedCompany() && $this->limitCompanyId() > 0) {
            $values["company_id"] = $this->limitCompanyId();
        }
        return parent::countBy($values, $columns);
    }

    /**
     * 指定したトランザクション内にて主キーベースでデータの保存を行う。
     * 主キーが存在しない場合は何もしない。
     * また、モデル内のカラムがDBに無い場合はスキップする。
     * データ作成日／更新日は自動的に設定される。
     */
    public function save($ignoreOperator = false)
    {
        if ($this->isLimitedCompany() && $this->limitCompanyId() > 0) {
            $this->company_id = $this->limitCompanyId();
        }
        return parent::save($ignoreOperator);
    }

    /**
     * 指定したトランザクション内にて主キーベースでデータの保存を行う。
     * 主キーが存在しない場合は何もしない。
     * また、モデル内のカラムがDBに無い場合はスキップする。
     * データ作成日／更新日は自動的に設定される。
     */
    public function saveAll($list)
    {
        // 主キーのデータが無かった場合はInsert
        $insert = new Vizualizer_Query_InsertIgnore($this->access);
        foreach ($list as $index => $data) {
            // データ作成日／更新日は自動的に設定する。
            if ($this->isLimitedCompany() && $this->limitCompanyId() > 0) {
                $data["company_id"] = $this->limitCompanyId();
            }
            $data["create_time"] = $data["update_time"] = Vizualizer_Data_Calendar::now()->date("Y-m-d H:i:s");
            $insert->execute($data);
            foreach ($this->primary_keys as $key) {
                if (empty($data[$key])) {
                    $list[$index][$key] = $insert->lastInsertId();
                }
            }
        }
        return $list;
    }
}
