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
 * 注文のリストを取得する。
 *
 * @package VizualizerShop
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerShop_Module_Order_MultipleSave extends Vizualizer_Plugin_Module_List
{

    function execute($params)
    {
        $post = Vizualizer::request();
        if ($post["add"] || $post["save"]) {
            if ($params->get("customer_only", "0") === "1") {
                $attr = Vizualizer::attr();
                $search = $post["search"];
                $search["customer_id"] = $attr[VizualizerMember::KEY]->customer_id;
                $post->set("search", $search);
            }
            $this->executeImpl($params, "Shop", "OrderView", $params->get("result", "orders"));
            $attr = Vizualizer::attr();
            $list = $attr["orders"];

            // トランザクションの開始
            $connection = Vizualizer_Database_Factory::begin("shop");

            try {
                $loader = new Vizualizer_Plugin("shop");

                foreach ($list as $data) {
                    $order = $loader->loadModel("Order");
                    $order->findByPrimaryKey($data->order_id);
                    foreach ($post as $name => $value) {
                        $order->$name = $value;
                    }
                    $order->save();
                    $orderShip = $loader->loadModel("OrderShip");
                    $orderShip->findByPrimaryKey($data->order_ship_id);
                    foreach ($post as $name => $value) {
                        $orderShip->$name = $value;
                    }
                    $orderShip->save();
                    $orderDetail = $loader->loadModel("OrderDetail");
                    $orderDetail->findByPrimaryKey($data->order_detail_id);
                    foreach ($post as $name => $value) {
                        $orderDetail->$name = $value;
                    }
                    $orderDetail->save();
                }

                // エラーが無かった場合、処理をコミットする。
                Vizualizer_Database_Factory::commit($connection);

                // 画面をリロードする。
                if (!$this->continue) {
                    // 登録に使用したキーを無効化
                    $this->removeInput("add");
                    $this->removeInput("save");

                    $this->reload();
                }
            } catch (Exception $e) {
                Vizualizer_Database_Factory::rollback($connection);
                throw new Vizualizer_Exception_Database($e);
            }
        }

    }
}
