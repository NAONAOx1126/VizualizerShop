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
 * 注文の詳細データを取得する。
 *
 * @package VizualizerShop
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerShop_Module_Order_Download extends Vizualizer_Plugin_Module_Download
{
    private $search_org;

    private $orderStatuses;
    private $shipmentStatuses;
    private $paymentStatuses;

    function execute($params)
    {
        $post = Vizualizer::request();
        $result = array();
        $this->orderStatuses = array("仮注文", "注文済み", "入荷待ち", "保留", "顧客問い合わせ中", "処理完了");
        $this->paymentStatuses = array("未決済", "決済失敗", "決済完了");
        $this->shipmentStatuses = array("発送待ち", "発送保留", "発送済み", "返送／再発送待ち");
        $this->search_org = $search = $post["search"];
        if (!empty($post["order_ids"])) {
            $order_ids = explode(",", $post["order_ids"]);
            $search["in:order_id"] = $order_ids;
            $post->set("search", $search);
        }
        $this->executeImpl($params, "Shop", "OrderView", $result);
    }

    protected function filterData($data){
        $data->order_status_name = $this->orderStatuses[$data->order_status];
        $data->payment_status_name = $this->paymentStatuses[$data->payment_status];
        $data->shipment_status_name = $this->shipmentStatuses[$data->shipment_status];
        return $data;
    }

    protected function postprocess()
    {
        $post = Vizualizer::request();
        $post->set("search", $this->search_org);
    }
}
