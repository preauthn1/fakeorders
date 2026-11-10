<?php

namespace app\controller\v1;

use app\common\BaseController;
use app\services\PledgeLongServices;
use app\services\SysServices;
use support\Request;

class PledgeController extends BaseController
{
    /**
     * 获取理财配置
     * @return \support\Response
     */
    public function pledge_long_config()
    {
        $pledge_long_config = SysServices::getConfigValue('pledge_long');
        $getPledgeLongStat = PledgeLongServices::getPledgeLongStat();
        $getPledgeLong_arr = [];
        foreach ($getPledgeLongStat as $key => $value) {
            $getPledgeLong_arr["cycle_" . $value['cycle']] = $value['total_amount'];
        }
        if ($pledge_long_config['status'] == 1) {
            $pledge_cycles = $pledge_long_config['pledge_cycles'];
            $pledge_cycles_arr = [];
            foreach ($pledge_cycles as $pledge_cycle) {
                if ($pledge_cycle['status'] == 1) {
                    if (isset($getPledgeLong_arr["cycle_" . $pledge_cycle['days']])) {
                        $pledge_cycle['total_amount'] = $getPledgeLong_arr["cycle_" . $pledge_cycle['days']];
                    } else {
                        $pledge_cycle['total_amount'] = 0;
                    }
                    $pledge_cycles_arr[] = $pledge_cycle;
                }
            }
            $out_data = [
                'code' => 0,
                'data' => $pledge_cycles_arr
            ];
        } else {
            $out_data = [
                'code' => 0,
                'data' => []
            ];
        }
        return self::returnMsg($out_data);
    }

    /**
     * 添加理财
     * @param Request $request
     * @return \support\Response
     */
    public function add_pledge_long(Request $request)
    {
        $user_id = $this->user_id;
        $amount = $request->input('amount', 0);
        $cycle = $request->input('cycle', 0);
        $pledge_long_config = SysServices::getConfigValue('pledge_long');
        if ($pledge_long_config['status'] == 1) {
            foreach ($pledge_long_config['pledge_cycles'] as $pledge_cycle) {
                if ($pledge_cycle['days'] == $cycle && $pledge_cycle['status'] == 1) {
                    $profit = $pledge_cycle['rate'];
                    break;
                }
            }
            if (empty($profit)) {
                $out_data = [
                    'code' => 1,
                    'msg' => '质押周期错误'
                ];
                return self::returnMsg($out_data);
            }
            $data = [
                'amount' => $amount,
                'cycle' => $cycle,
                'profit' => $profit
            ];
            $out_data = PledgeLongServices::addPledgeLong($user_id, $data);
        } else {
            $out_data = [
                'code' => 1,
                'msg' => '质押暂未开放'
            ];
        }
        return self::returnMsg($out_data);
    }

    /**
     * 获取理财列表
     * @param Request $request
     * @return \support\Response
     */
    public function pledge_long_list(Request $request)
    {
        $user_id = $this->user_id;
        $status = $request->get('status', 1);
        $limit = $request->get('limit', 10);
        $pledge = PledgeLongServices::getPledgeLongList($user_id, $status, $limit);
        $out_data = [
            'code' => 0,
            'data' => [
                'pledge' => $pledge
            ]
        ];
        return self::returnMsg($out_data);
    }

    /**
     * 获取理财图表
     * @return \support\Response
     */
    public function pledge_long_chart()
    {
        $data = PledgeLongServices::getPledgeLongChart(7);
        $out_data = [
            'code' => 0,
            'data' => $data
        ];
        return self::returnMsg($out_data);
    }
}
