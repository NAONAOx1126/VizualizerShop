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
 * 配送のリストを取得する。
 *
 * @package VizualizerShop
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerShop_Module_ShipAddress_MultipleSave extends Vizualizer_Plugin_Module_List
{

    function execute($params)
    {
        $post = Vizualizer::request();
        if ($post["add"] || $post["save"]) {
            if ($post["ship_weight_id"] > 0) {
                $search = $post["search"];
                $search["ship_weight_id"] = $post["ship_weight_id"];
                $post->set("search", $search);
            } elseif ($post["ship_id"] > 0) {
                $search = $post["search"];
                $search["ship_id"] = $post["ship_id"];
                $search["ship_weight_id"] = "0";
                $post->set("search", $search);
            }
            $this->executeImpl($params, "Shop", "ShipAddress", $params->get("result", "ship_addresses"));
            $post->set("search", $search_org);

            $attr = Vizualizer::attr();
            $list = $attr["ship_addresses"];

            // トランザクションの開始
            $connection = Vizualizer_Database_Factory::begin("shop");

            try {
                $shipFees = $post["ship_fee"];
                foreach ($list as $data) {
                    if (is_numeric($shipFees[$data->address_prefix])) {
                        $data->ship_fee = $shipFees[$data->address_prefix];
                        $data->save();
                        unset($shipFees[$data->address_prefix]);
                    } else {
                        $data->delete();
                    }
                }
                $loader = new Vizualizer_Plugin("shop");
                foreach ($shipFees as $address_prefix => $ship_fee) {
                    if (is_numeric($ship_fee)) {
                        $data = $loader->loadModel("ShipAddress");
                        $data->ship_id = $post["ship_id"];
                        $data->ship_weight_id = $post["ship_weight_id"];
                        $data->address_prefix = $address_prefix;
                        $data->ship_fee = $ship_fee;
                        $data->save();
                    }
                }

                // エラーが無かった場合、処理をコミットする。
                Vizualizer_Database_Factory::commit($connection);
            } catch (Exception $e) {
                Vizualizer_Database_Factory::rollback($connection);
                throw new Vizualizer_Exception_Database($e);
            }
        }

    }
}
