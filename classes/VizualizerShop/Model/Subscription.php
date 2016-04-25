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
        // 実際の開始日を計算
        $date += $this->order_margin * 24 * 3600;
        $result = null;
        // 当月の該当日を検索
        for ($i = 1; $i <= $this->orders; $i ++) {
            $weekKey = "week".$i;
            $weekdayKey = "weekday".$i;
            $firstWeekday = date("w", strtotime(date("Y-m-01")));
            $targetDay = ($this->$weekKey - 1) * 7 + (($firstWeekday <= $this->$weekdayKey)?($this->$weekdayKey - $firstWeekday + 1):($this->$weekdayKey - $firstWeekday + 8));
            $targetDate = strtotime(date("Y-m-".$targetDay." 23:59:59"));
            if ($date < $targetDate && ($result == null || $targetDate < $result)) {
                $result = $targetDate;
            }
        }
        // 翌月の該当日を検索
        for ($i = 1; $i <= $this->orders; $i ++) {
            $weekKey = "week".$i;
            $weekdayKey = "weekday".$i;
            $firstWeekday = date("w", strtotime(date("Y-m-01", strtotime("+1 month"))));
            $targetDay = ($this->$weekKey - 1) * 7 + (($firstWeekday <= $this->$weekdayKey)?($this->$weekdayKey - $firstWeekday + 1):($this->$weekdayKey - $firstWeekday + 8));
            $targetDate = strtotime(date("Y-m-".$targetDay." 23:59:59", strtotime("+1 month")));
            if ($date < $targetDate && ($result == null || $targetDate < $result)) {
                $result = $targetDate;
            }
        }

        return $result;
    }

    public function getWeekdayName($weekday) {
        if ($weekday >= 0 && $weekday < 7) {
            return mb_substr("日月火水木金土", $weekday, 1);
        }
        return "";
    }
}
