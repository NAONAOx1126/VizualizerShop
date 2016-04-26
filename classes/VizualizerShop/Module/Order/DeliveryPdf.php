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
class VizualizerShop_Module_Order_DeliveryPdf extends Vizualizer_Plugin_Module_Pdf
{

    function execute($params)
    {
        $post = Vizualizer::request();
        $attr = Vizualizer::attr();

        // PDFを初期化
        $this->startDocument();
        $lightFont = $this->setFontByTTF(VIZUALIZER_SITE_ROOT."/fonts/GenShinGothic-Light.ttf");
        $normalFont = $this->setFontByTTF(VIZUALIZER_SITE_ROOT."/fonts/GenShinGothic-Normal.ttf");
        $regularFont = $this->setFontByTTF(VIZUALIZER_SITE_ROOT."/fonts/GenShinGothic-Regular.ttf");

        // 注文データを取得
        if ($params->get("customer_only", "0") === "1") {
            $search = $post["search"];
            $search["customer_id"] = $attr[VizualizerMember::KEY]->customer_id;
            $post->set("search", $search);
        }
        $search_org = $search = $post["search"];
        if (!empty($post["order_ids"])) {
            $order_ids = explode(",", $post["order_ids"]);
            $search["in:order_id"] = $order_ids;
            $post->set("search", $search);
        }
        $this->executeImpl($params, "Shop", "Order", $params->get("result", "orders"));
        $post->set("search", $search_org);
        $orders = $attr["orders"];

        // ショップの情報を取得
        if (class_exists("VizualizerAdmin") && !empty($attr[VizualizerAdmin::KEY])) {
            $loader = new Vizualizer_Plugin("admin");
            $company = $loader->loadModel("Company");
            $company->findByPrimaryKey($attr[VizualizerAdmin::KEY]->company_id);
        }
        // コンテンツ情報取得
        $loader = new Vizualizer_Plugin("shop");
        $content = $loader->loadModel("Content");

        foreach ($orders as $order) {
            foreach ($order->orderShips() as $orderShip) {
                // ページを初期化
                $this->startPage();

                // タイトルを出力
                $this->text(595 - 340, 60, 18, "納　品　書", true);
                // 注文IDと注文日を出力
                $this->setFont($lightFont);
                $this->boxtext(595 - 222, 26, 200, 10, 10, "注文ID：".sprintf("%010d", $order->order_code), false, "right");
                $this->boxtext(595 - 222, 38, 200, 10, 10, "注文日：".date("Y/m/d", strtotime($order->order_time)), false, "right");
                // 名前を出力
                $this->setFont($regularFont);
                $this->text(595 - 569, 109, 24, $order->order_sei." ".$order->order_mei." 様");
                $this->setFont($lightFont);
                $this->text(595 - 567, 151, 10, "〒".$order->order_zip1);
                $this->text(595 - 567, 164, 10, $order->order_pref." ".$order->order_address1);
                $this->text(595 - 567, 177, 10, $order->order_address2);
                $this->text(595 - 567, 190, 10, "TEL：".$order->order_tel1);
                $this->text(595 - 567, 203, 10, "MAIL：".$order->order_email);
                // 合計金額を出力
                $this->setFont($regularFont);
                $this->text(595 - 567, 245, 16, "合計金額：¥ ".number_format($order->payment_total));
                $this->text(595 - 567, 265, 11, "決済方法：".$order->payment_name);
                // ショップのロゴを出力
                if (!empty($content->roaster_logo)) {
                    $this->image(595 - 201, 80, "/upload/".$content->roaster_logo, 0, 60);
                }
                // ショップの情報を出力
                $this->setFont($lightFont);
                if (preg_match("/^〒([0-9-]+)([^0-9-]+)([0-9-]+.*)$/", str_replace("　", " ", $content->company_address), $p)) {
                    $this->text(595 - 201, 151, 10, "〒".trim($p[1]));
                    $this->text(595 - 201, 164, 10, trim($p[2]));
                    $this->text(595 - 201, 177, 10, trim($p[3]));
                }
                $this->text(595 - 201, 190, 10, "TEL：".$content->company_phone_number);
                $this->text(595 - 201, 203, 10, "MAIL：".$content->company_contact);
                $this->setFont($regularFont);
                $this->text(595 - 201, 230, 14, $content->shop_name);
                $this->text(595 - 201, 254, 14, $content->company_name);
                // 説明文を設定
                $this->setFont($lightFont);
                $this->boxtext(595 - 529, 309, 463, 11, 7, "この度は、当ショップで商品をお買い求めいただき誠にありがとうございます。下記の通り納品いたします。", false, "center");
                $this->boxtext(595 - 529, 320, 463, 11, 7, "内容をご確認の上、お気づきの点がございましたら、お手数ですが当ショップまでご一報くださいますようお願い申し上げます。", false, "center");
                // 明細タイトル行を出力
                $this->boxtext(595 - 571, 362, 54, 24, 9, "No.", true, "center");
                $this->boxtext(595 - 517, 362, 220, 24, 9, "商品名", true, "center");
                $this->boxtext(595 - 297, 362, 98, 24, 9, "単価", true, "center");
                $this->boxtext(595 - 199, 362, 76, 24, 9, "個数", true, "center");
                $this->boxtext(595 - 123, 362, 101, 24, 9, "小計（税込み）", true, "center");
                // 明細データ行出力
                $outputLines = 0;
                foreach ($orderShip->orderDetails() as $orderDetail) {
                    $this->boxtext(595 - 571, 386 + $outputLines * 24, 54, 24, 9, $orderDetail->product_code, true, "center");
                    $this->boxtext(595 - 517, 386 + $outputLines * 24, 220, 24, 9, $orderDetail->product_name, true, "center");
                    $this->boxtext(595 - 297, 386 + $outputLines * 24, 98, 24, 9, "¥ ".number_format($orderDetail->price), true, "right");
                    $this->boxtext(595 - 199, 386 + $outputLines * 24, 76, 24, 9, number_format($orderDetail->quantity), true, "center");
                    $this->boxtext(595 - 123, 386 + $outputLines * 24, 101, 24, 9, "¥ ".number_format($orderDetail->price * $orderDetail->quantity), true, "right");
                    $outputLines ++;
                }
                for ($i = $outputLines; $i < 10; $i ++) {
                    $this->rect(595 - 571, 386 + $i * 24, 54, 24, 0.5);
                    $this->rect(595 - 517, 386 + $i * 24, 220, 24, 0.5);
                    $this->rect(595 - 297, 386 + $i * 24, 98, 24, 0.5);
                    $this->rect(595 - 199, 386 + $i * 24, 76, 24, 0.5);
                    $this->rect(595 - 123, 386 + $i * 24, 101, 24, 0.5);
                }

                // 合計データ行出力
                $this->boxtext(595 - 297, 637, 178, 24, 9, "小計", true, "center");
                $this->boxtext(595 - 119, 637, 97, 24, 9, "¥ ".number_format($order->subtotal), true, "right");
                $this->boxtext(595 - 297, 661, 178, 24, 9, "手数料", true, "center");
                $this->boxtext(595 - 119, 661, 97, 24, 9, "¥ ".number_format($order->charge), true, "right");
                $this->boxtext(595 - 297, 685, 178, 24, 9, "送料", true, "center");
                $this->boxtext(595 - 119, 685, 97, 24, 9, "¥ ".number_format($order->ship_fee), true, "right");
                $this->boxtext(595 - 297, 709, 178, 24, 9, "合計金額", true, "center");
                $this->boxtext(595 - 119, 709, 97, 24, 9, "¥ ".number_format($order->payment_total), true, "right");
            }
        }

        $this->output();
    }
}
