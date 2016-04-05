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
 * 配送のリストをページング付きで取得する。
 *
 * @package VizualizerShop
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerShop_Module_ShipAddress_Page extends Vizualizer_Plugin_Module_Page
{

    function execute($params)
    {
        $post = Vizualizer::request();
            if ($post["ship_weight_id"] > 0) {
            $search = $post["search"];
            $search["ship_weight_id"] = $post["ship_weight_id"];
            $post->set("search", $search);
        } elseif ($post["ship_id"] > 0) {
            $search = $post["search"];
            $search["ship_id"] = $post["ship_id"];
            $post->set("search", $search);
        }
        $this->executeImpl($params, "Shop", "ShipAddress", $params->get("result", "ship_addresses"));
        if ($params->get("default", "none") == "prefecture") {
            $attr = Vizualizer::attr();
            $loader = new Vizualizer_Plugin("shop");
            $prefectures = array("北海道", "青森県", "岩手県", "宮城県", "秋田県", "山形県", "福島県", "東京都", "神奈川県", "埼玉県", "千葉県", "茨城県", "栃木県", "群馬県", "山梨県", "新潟県", "長野県", "富山県", "石川県", "福井県", "愛知県", "岐阜県", "静岡県", "三重県", "大阪府", "兵庫県", "京都府", "滋賀県", "奈良県", "和歌山県", "鳥取県", "島根県", "岡山県", "広島県", "山口県", "徳島県", "香川県", "愛媛県", "高知県", "福岡県", "佐賀県", "長崎県", "熊本県", "大分県", "宮崎県", "鹿児島県", "沖縄県");
            $data = array();
            foreach ($prefectures as $prefecture) {
                $data[$prefecture] = $loader->loadModel("ShipAddress", array("ship_id" => $post["ship_id"], "ship_weight_id" => $post["ship_weight_id"], "address_prefix" => $prefecture, "ship_fee" => ""));
            }
            foreach ($attr["ship_addresses"] as $address) {
                $data[$address->address_prefix] = $address;
            }
            $attr["ship_addresses"] = $data;
        }
    }
}
