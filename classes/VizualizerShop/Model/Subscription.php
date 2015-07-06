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
 * 定期購入のモデルです。
 *
 * @package VizualizerShop
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerShop_Model_Subscription extends VizualizerShop_Model_MallModel
{

    /**
     * コンストラクタ
     *
     * @param $values モデルに初期設定する値
     */
    public function __construct($values = array())
    {
        $loader = new Vizualizer_Plugin("shop");
        parent::__construct($loader->loadTable("Subscriptions"), $values);
    }

    /**
     * 主キーでデータを取得する。
     *
     * @param $subscription_id 定期購入ID
     */
    public function findByPrimaryKey($subscription_id)
    {
        $this->findBy(array("subscription_id" => $subscription_id));
    }

    /**
     * 商品データを取得する。
     */
    public function productOption()
    {
        $loader = new Vizualizer_Plugin("shop");
        $model = $loader->loadModel("ProductOption");
        $model->findByPrimaryKey($this->product_option_id);
        return $model;
    }

    /**
     * 次の発送日を取得する。
     *
     * @param int $date 指定日付のtime値
     * @return 次の発送日に該当するtime値
     */
    public function getNextDelivery($date = null){
        // 日付未指定もしくは過去の日付の場合は当日の日付を設定
        if($date == null || $date < time()){
            $date = time();
        }
        // 翌日以降から発送可能曜日に該当する日を取得
        for($i = 1; $i <= 7; $i ++){
            $targetDate = strtotime("+".$i." day", $date);
            $weekday = strtolower(date("l", $targetDate));
            if($this->$weekday){
                return $targetDate;
            }
        }
        return null;
    }
}
