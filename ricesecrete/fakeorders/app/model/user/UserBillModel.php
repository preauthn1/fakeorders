<?php

namespace app\model\user;

use app\model\Base;
use DateTime;
use support\Cache;
use think\facade\Db;

class UserBillModel extends Base
{
    /**
     * 与模型关联的表名
     *
     * @var string
     */
    protected $table = 'ex_user_bill';

    protected $autoWriteTimestamp = false;
    /**
     * 模型数据表对应的完整数据表名称 sql
     * @var string
     */
    protected $table_create_sql = "CREATE TABLE `ex_user_bill` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '编号',
  `created_at` int DEFAULT NULL COMMENT '创建时间',
  `is_demo` tinyint(1) DEFAULT '0' COMMENT '是否是虚拟用户0不是 1是',
  `user_id` int DEFAULT '0' COMMENT '用户id',
  `admin_id` int DEFAULT '0' COMMENT '操作管理员id',
  `order_id` int DEFAULT '0' COMMENT '订单来源id',
  `from_user_id` int DEFAULT '0' COMMENT '用户来源id',
  `change` tinyint(1) DEFAULT '1' COMMENT '1增加 2减少',
  `title` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '账单标题',
  `remarks` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '备注信息',
  `money` decimal(20,8) DEFAULT '0.00000000' COMMENT '操作金额',
  `before_money` decimal(20,8) DEFAULT '0.00000000' COMMENT '操作前金额',
  `after_money` decimal(20,8) DEFAULT '0.00000000' COMMENT '操作后金额',
  `type` tinyint DEFAULT '1' COMMENT '钱包类型',
  `detail_type` tinyint DEFAULT '0' COMMENT '操作类型',
  `day` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '年月日',
  `month` varchar(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '年月',
  `year` varchar(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '年',
  PRIMARY KEY (`id`),
  KEY `is_demo` (`is_demo`),
  KEY `user_id` (`user_id`),
  KEY `admin_id` (`admin_id`),
  KEY `order_id` (`order_id`),
  KEY `from_user_id` (`from_user_id`),
  KEY `created_at` (`created_at`,`user_id`),
  KEY `user` (`is_demo`,`user_id`,`change`),
  KEY `day` (`day`),
  KEY `month` (`month`),
  KEY `year` (`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='用户账单表';";

    /**
     * 按月份设置表名
     * @param string $month YYYYMM 格式
     * @return $this
     */
    public function tableByMonth($month = null)
    {
        if (!$month) {
            $month = date('Ym');
        }

        // 验证月份格式
        if (!preg_match('/^\d{6}$/', $month)) {
            throw new \InvalidArgumentException('Invalid month format, should be YYYYMM');
        }

        $this->table = 'ex_user_bill_' . $month;
        return $this;
    }

    /**
     * 获取指定月份的表名
     * @param string $month YYYYMM 格式
     * @return string
     */
    public function getMonthTableName($month = null)
    {
        if (!$month) {
            $month = date('Ym');
        }

        return 'ex_user_bill_' . $month;
    }

    /**
     * 创建指定月份的表
     * @param string $month YYYYMM 格式
     * @return bool
     */
    public function createMonthTable($month = null)
    {
        if (!$month) {
            $month = date('Ym');
        }
        // 验证月份格式
        if (!preg_match('/^\d{6}$/', $month)) {
            throw new \InvalidArgumentException('Invalid month format, should be YYYYMM');
        }
        $tableName = $this->getMonthTableName($month);
        $redis_key = "creat_table_" . $tableName;
        $redis = Cache::store('redis');
        $is_table = $redis->get($redis_key);
        if ($is_table) {
            return $tableName;
        }

        // 使用 ThinkORM 的 Db 类执行原生 SQL
        $createSql = str_replace('`ex_user_bill`', "`{$tableName}`", $this->table_create_sql);

        try {
            print_r($createSql);
            Db::execute($createSql);
            \support\Log::info("Successfully created month table: " . $tableName);
            $redis->set($redis_key, 1);
            return $tableName;
        } catch (\Exception $e) {
            // 如果表已存在，也认为创建成功
            if (strpos($e->getMessage(), 'already exists') !== false) {
                \support\Log::info("Month table already exists: " . $tableName);
                $redis->set($redis_key, 1);
                return $tableName;
            }
            \support\Log::error("Failed to create month table {$tableName}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 获取指定时间范围的账单数据
     * @param array $conditions
     * @param string $fieldBy
     * @param string $order
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \Exception
     */
    public function getBillsByTimeRange($conditions = [], $fieldBy = 'created_at', $order = 'DESC', $page = 1, $limit = 20)
    {
        $allResults = [];
        $totalCount = 0;
        $offset = ($page - 1) * $limit;
        $fetchedCount = 0;
        if (empty($fieldBy)) {
            $fieldBy = 'created_at';
        }
        if (isset($conditions['created_at'])) {
            $startTime = $conditions['created_at'][0];
            $endTime = $conditions['created_at'][1];
            if (empty($startTime)) {
                $startTime = strtotime('-7 days');
            }
            if (empty($endTime)) {
                $endTime = time();
            }
            unset($conditions['created_at']);
        } else {
            $startTime = strtotime('-7 days');
            $endTime = time();
        }
        // 遍历涉及的月份
        $current = new DateTime(date('Y-m-01', $startTime));
        $end = new DateTime(date('Y-m-01 23:59:59', $endTime));
        // 第一遍遍历：统计总数
        $tempCurrent = clone $current;
        while ($tempCurrent <= $end) {
            $month = $tempCurrent->format('Ym');
            // 创建并切换到对应月份的表
            $this->createMonthTable($month);
            $this->tableByMonth($month);

            // 构建查询条件
            $query = clone $this;

            // 添加时间条件
            $monthStartTime = strtotime($tempCurrent->format('Y-m-01'));
            $monthEndTime = strtotime($tempCurrent->format('Y-m-t 23:59:59'));
            // 实际查询时间范围
            $actualStartTime = max($startTime, $monthStartTime);
            $actualEndTime = min($endTime, $monthEndTime);

            $query = $query->where('created_at', '>=', $actualStartTime)
                ->where('created_at', '<=', $actualEndTime);
            // 添加其他查询条件
            foreach ($conditions as $field => $value) {
                if (is_array($value)) {
                    $query = $query->whereIn($field, $value);
                } else {
                    $query = $query->where($field, $value);
                }
            }

            // 统计当前表的数据条数
            $count = $query->count();
            $totalCount += $count;

            // 移动到下个月
            $tempCurrent->modify('+1 month');
        }

        while ($current <= $end) {
            $month = $current->format('Ym');
            // 创建并切换到对应月份的表
            $this->createMonthTable($month);
            $this->tableByMonth($month);

            // 构建查询条件
            $query = clone $this;

            // 添加时间条件
            $monthStartTime = strtotime($current->format('Y-m-01'));
            $monthEndTime = strtotime($current->format('Y-m-t 23:59:59'));
            // 实际查询时间范围
            $actualStartTime = max($startTime, $monthStartTime);
            $actualEndTime = min($endTime, $monthEndTime);

            $query = $query->where('created_at', '>=', $actualStartTime)
                ->where('created_at', '<=', $actualEndTime);
            // 添加其他查询条件
            foreach ($conditions as $field => $value) {
                if (is_array($value)) {
                    $query = $query->whereIn($field, $value);
                } else {
                    $query = $query->where($field, $value);
                }
            }
            // 计算还需要多少数据
            $needMore = $limit - count($allResults);
            if ($needMore <= 0) {
                break;
            }
            // 根据已获取数据量计算当前表的偏移量
            $currentOffset = max(0, $offset - $fetchedCount);
            // 执行查询（带分页）
            $collection = $query->order($fieldBy, $order)
                ->limit($currentOffset, $needMore)
                ->select();

            if (!empty($collection)) {
                $results = $collection->toArray();
            } else {
                $results = [];
            }
            $allResults = array_merge($allResults, $results);
            $fetchedCount += count($results);
            // 移动到下个月
            $current->modify('+1 month');
        }
        return [$allResults, $totalCount];
    }

    /**
     * 使用原生 SQL 的 UNION 查询
     * @param array $conditions 查询条件
     * @param string $fieldBy 排序字段
     * @param string $order 排序方式
     * @param int $page 页码
     * @param int $limit 每页数量
     * @return array
     */
    public function getBillsByRawUnion($conditions = [], $fieldBy = 'created_at', $order = 'DESC', $page = 1, $limit = 20)
    {
        if (empty($fieldBy)) {
            $fieldBy = 'created_at';
        }
        if (isset($conditions['created_at'])) {
            $startTime = $conditions['created_at'][0];
            $endTime = $conditions['created_at'][1];
            if (empty($startTime)) {
                $startTime = strtotime('-7 days');
            }
            if (empty($endTime)) {
                $endTime = time();
            }

        } else {
            $startTime = strtotime('-7 days');
            $endTime = time();
        }
        $conditions['created_at'][0] = $startTime;
        $conditions['created_at'][1] = $endTime;
        $current = new DateTime(date('Y-m-01', $startTime));
        $end = new DateTime(date('Y-m-01 23:59:59', $endTime));
        $months = $this->getMonthRange($current, $end);
        // 确保所有涉及的表都存在
        foreach ($months as $month) {
            $this->createMonthTable($month);
        }

        // 构建 UNION 查询SQL
        $unionParts = [];
        $params = [];
        // 定义允许的查询字段
        $allowedConditionFields = ['user_id', 'admin_id', 'order_id', 'from_user_id', 'change', 'type', 'detail_type', 'created_at'];
        foreach ($months as $month) {
            $tableName = $this->getMonthTableName($month);
            $unionParts[] = "SELECT * FROM `{$tableName}` WHERE 1=1";
            // 遍历所有允许的条件字段
            foreach ($allowedConditionFields as $field) {
                if (isset($conditions[$field])) {
                    $value = $conditions[$field];
                    // 处理不同类型的条件值
                    if (is_array($value)) {
                        // 处理 IN 条件
                        if (!empty($value)) {
                            if ($field == 'created_at') {
                                //AND `created_at` BETWEEN 1748707200 AND 1753977599
                                //AND `created_at` IN (?,?)
                                $placeholders = str_repeat('? AND ', count($value) - 1) . '?';
                                $unionParts[count($unionParts) - 1] .= " AND `{$field}` BETWEEN {$placeholders}";
                                foreach ($value as $val) {
                                    $params[] = $val;
                                }
                            } else {
                                $unionParts[count($unionParts) - 1] .= " AND `{$field}` IN (?)";
                                //把$value 变成字符串 ,隔开的字符串
                                $params[] = implode(',', $value[1]);
                            }
                        }
                    } else {
                        // 处理等值条件
                        $unionParts[count($unionParts) - 1] .= " AND `{$field}` = ?";
                        $params[] = $value;
                    }
                }
            }
        }
        $offset = ($page - 1) * $limit;
        $sql = "(" . implode(") UNION ALL (", $unionParts) . ") ORDER BY `{$fieldBy}` {$order} LIMIT {$offset}, {$limit}";

        // 使用 ThinkORM 执行查询
        $data = Db::query($sql, $params);
        // 获取总数
        $countSql = "SELECT COUNT(*) as total FROM ((" . implode(") UNION ALL (", $unionParts) . ")) as union_table";
        $totalResult = Db::query($countSql, $params);
        $total = $totalResult[0]['total'];

        return [$data, $total];
    }

    /**
     * 插入数据到对应月份的表
     * @param array $data
     * @return bool|int
     */
    public function insertToMonthTable($data)
    {
        // 确保有创建时间
        if (!isset($data['created_at'])) {
            $data['created_at'] = time();
        }

        $month = date('Ym', $data['created_at']);
        // 创建并切换到对应月份的表
        $this->createMonthTable($month);
        $this->tableByMonth($month);

        //获取最后一条数据的id
        $lastRecord = $this->order('id', 'desc')->limit(1)->find();
        $lastId = $lastRecord ? $lastRecord['id'] : 0;
        $data['number'] = $month . ($lastId + 1);

        // 插入数据
        return $this->save($data);
    }

    /**
     * 获取最近几个月的数据
     * @param int $months 月份数
     * @param array $conditions 查询条件
     * @return array
     */
    public function getLastMonthsBills($conditions, $months = 3)
    {
        $results = [];
        $current = new DateTime();
        for ($i = 0; $i < $months; $i++) {
            $month = $current->format('Ym');
            // 创建并切换到对应月份的表
            $this->createMonthTable($month);
            $this->tableByMonth($month);

            // 构建查询
            $query = clone $this;

            // 添加查询条件
            foreach ($conditions as $field => $value) {
                if (is_array($value)) {
                    $query = $query->whereIn($field, $value);
                } else {
                    $query = $query->where($field, $value);
                }
            }

            // 执行查询
            $monthResults = $query->select()->toArray();
            $results = array_merge($results, $monthResults);

            // 移动到上个月
            $current->modify('-1 month');
        }

        return $results;
    }

    /**
     * 获取指定时间段内的所有账单
     * @param $startDate
     * @param $endDate
     * @return array
     */
    public function getMonthRange($startDate, $endDate)
    {
        //获取时间段内所有月份
        $months = [];
        while ($startDate <= $endDate) {
            $months[] = $startDate->format('Ym');
            $startDate->modify('+1 month');
        }
        return $months;
    }
}
