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
 * 自動で売上更新するためのバッチです。
 *
 * @package VizualizerShop
 * @author Naohisa Minagawa <info@vizualizer.jp>
 */
class VizualizerShop_Batch_UpdateSales extends Vizualizer_Plugin_Batch
{
    public function getName()
    {
        return "Update Sales";
    }

    public function getFlows()
    {
        return array("updateSales");
    }

    /**
     * 売上更新処理を実施。
     *
     * @param $params バッチ自体のパラメータ
     * @param $data バッチで引き回すデータ
     * @return バッチで引き回すデータ
     */
    protected function updateSales($params, $data)
    {
        $loader = new Vizualizer_Plugin("Admin");
        $company = $loader->loadModel("Company");
        $companys = $company->findAllBy(array());

        foreach ($companys as $company) {
            $loader = new Vizualizer_Plugin("Shop");
            $order = $loader->loadModel("Order");
            $orders = $order->findAllBy(array("company_id" => $company->company_id, "gt:total" => "0", "ge:order_time" => date("Y-m-01 00:00:00", strtotime("-1 year"))));
            $salesData = array();
            foreach ($orders as $order) {
                $month = date("Y-m-01", strtotime($order->order_time));
                if(!array_key_exists($month, $salesData)){
                    $salesData[$month] = 0;
                }
                $salesData[$month] += $order->total;
            }

            $connection = Vizualizer_Database_Factory::begin("shop");
            try {
                foreach($salesData as $month => $amount){
                    $sales = $loader->loadModel("Sales");
                    $sales->findByCompanyMonth($company->company_id, $month);
                    $sales->company_id = $company->company_id;
                    $sales->sales_month = $month;
                    $sales->amount = $amount;
                    $sales->save();
                }

                Vizualizer_Database_Factory::commit($connection);
            } catch (Exception $e) {
                Vizualizer_Database_Factory::rollback($connection);
                throw new Vizualizer_Exception_Database($e);
            }
        }

        return $data;
    }
}
