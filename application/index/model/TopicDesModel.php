<?php
/**
 * Created by PhpStorm.
 * User: id_orange
 * Date: 2019/2/15
 * Time: 13:43
 */

namespace app\index\model;



use think\Db;
use think\facade\Log;

class TopicDesModel extends BaseModel
{
    protected $table = "t_survey_topic_des";


    public function importDesInfo($topicDesInfo)
    {
        $desInfo = array_values($topicDesInfo);
        Db::startTrans();
        try {
            $insertNum = 0;
            $insertAll = [];
            foreach ($desInfo as $key => $value) {
                //判断本次导入数据，是否存在数据库
                $topModel = new TopicModel();
                $result = $topModel->where('s_id', $value['s_id'])->where("t_name", $value['t_name'])->count();
                Log::error($result);
                if (empty($result)) {
                    $insertNum++;
                    $topDes = array_values($value['description']);
                    unset($value['description']);
                    $t_id = $topModel->insertGetId($value);
                    foreach ($topDes as $i=>$v) {
                        $insertAll[] = ['t_id' => $t_id, 'content' => $v];
                    }
                }
            }
            Log::alert($insertAll);
            $topicDesModel = new TopicDesModel();
            $topicDesModel->insertAll($insertAll);
            Db::commit();
            return $insertNum;
        } catch (\Exception $e) {
            Db::rollback();
            Log::error($e->getMessage());
            return false;
        }
    }
}