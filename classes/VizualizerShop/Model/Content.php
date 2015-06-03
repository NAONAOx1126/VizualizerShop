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
 * ショップ用のコンテンツに使用する内部データモデルです。
 */
class VizualizerShop_Model_ContentData extends VizualizerShop_Model_MallModel
{
    /**
     * コンストラクタ
     *
     * @param $values モデルに初期設定する値
     */
    public function __construct($values = array())
    {
        $loader = new Vizualizer_Plugin("shop");
        parent::__construct($loader->loadTable("Contents"), $values);
    }
}

/**
 * ショップ用のコンテンツのモデルです。
 *
 * @package VizualizerShop
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerShop_Model_Content extends VizualizerShop_Model_MallModel
{
    /**
     * コンテンツのデータ管理用配列
     */
    private $contents;

    /**
     * コンストラクタ
     *
     * @param $values モデルに初期設定する値
     */
    public function __construct($values = array())
    {
        // コンテンツ用データを展開
        $this->contents = array();
        $loader = new Vizualizer_Plugin("shop");
        $contentData = $loader->loadModel("ContentData");
        $contentDatas = $contentData->findAllBy(array());
        foreach($contentDatas as $contentData){
            $this->contents[$contentData->content_key] = $contentData;
        }
    }

    /**
     * データベースのカラムのデータを取得する。
     */
    public function __get($name)
    {
        if ($name == "company_id") {
            return $this->limitCompanyId();
        } elseif (isset($this->contents[$name])) {
            return $this->contents[$name]->content;
        }
        return null;
    }

    /**
     * データベースのカラムを主キー以外についてのみ登録する。
     * また、レコード作成日は未設定の場合のみ設定可能。
     */
    public function __set($name, $value)
    {
        if ($name == "company_id") {
            // ショップのIDに対しての設定は不可
        } elseif (isset($this->contents[$name])) {
            // トランザクションの開始
            $connection = Vizualizer_Database_Factory::begin("shop");
            try {
                $this->contents[$name]->content = $value;
                $this->contents[$name]->save();

                // エラーが無かった場合、処理をコミットする。
                Vizualizer_Database_Factory::commit($connection);
            } catch (Exception $e) {
                Vizualizer_Database_Factory::rollback($connection);
                throw new Vizualizer_Exception_Database($e);
            }
        } else {
            // トランザクションの開始
            $connection = Vizualizer_Database_Factory::begin("shop");
            try {
                $loader = new Vizualizer_Plugin("shop");
                $contentData = $loader->loadModel("ContentData");
                $contentData->company_id = $this->limitCompanyId();
                $contentData->content_key = $name;
                $contentData->content = $value;
                $contentData->save();

                // エラーが無かった場合、処理をコミットする。
                Vizualizer_Database_Factory::commit($connection);
            } catch (Exception $e) {
                Vizualizer_Database_Factory::rollback($connection);
                throw new Vizualizer_Exception_Database($e);
            }
        }
    }

    /**
     * そのカラムが設定されているかどうかをチェックする。
     */
    public function __isset($name)
    {
        return isset($this->contents[$name]);
    }

    /**
     * オブジェクトを文字列として出力する。
     */
    public function __toString()
    {
        return var_export($this->values(), true);
    }

    /**
     * コンテンツのデータを配列形式で取得する
     * @return multitype:NULL
     */
    public function values()
    {
        $result = array("company_id" => $this->limitCompanyId());
        foreach($this->contents as $name => $content){
            $result[$name] = $content->content;
        }
        return $result;
    }
}
