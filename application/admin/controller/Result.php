<?php
/**
 * Created by PhpStorm.
 * User: id_orange
 * Date: 2019/3/4
 * Time: 13:26
 */

namespace app\admin\controller;


use app\admin\model\SurveyUserModel;
use app\admin\model\TopicDesModel;
use app\admin\model\TopicModel;
use think\Db;
use think\facade\Log;
use think\Request;

class Result extends Base
{
    public function index(Request $request)
    {
        if ($request->isPost()) {
            try{
                $limit = $request->param('limit', 10);
                $s_id = $request->param('s_id', 0);
                $searchData = $request->param();
                $topicModel = new TopicModel();
                $data = $topicModel->alias("c")->where(function ($query) use ($searchData){
                    //模糊搜索
                    if (isset($searchData['search']) || !empty($searchData['search']))
                        $query->where('c.t_name', 'like','%'.$searchData['search'].'%');
                    //分类搜索
                    if (isset($searchData['category']) || !empty($searchData['category']))
                        $query->where('c.type',$searchData['category']);
                })  ->field("c.*,a.title,GROUP_CONCAT(b.content) as t_description")
                    ->leftJoin("t_survey a","a.id=c.s_id")
                    ->leftJoin("t_survey_topic_des b","c.id=b.t_id")
                    ->where("c.s_id",$s_id)
                    ->group("c.id")->paginate($limit);
                $data = json_decode(json_encode($data),true);
            } catch (\Exception $exception) {
                Log::error('获取数据错误：'. $exception->getMessage());
                $data = ['total' => 0, 'rows' => []];
            }
            return [
                'total' => $data['total'],
                'rows' => $data['data']
            ];
        }
        $id = $request->param("id",0);
        if ($id !== 0) {
            $this->assign("s_id", $id);
        } else {
            return $this->alertInfo('未获取到问卷主键');
        }
        return $this->fetch('./result/index');
    }

    public function detail(Request $request)
    {
        $id = $request->param("id", 0);
        if ($id !== 0) {
            $this->assign("id", $id);
        } else {
            return $this->alertInfo('未获取到问卷主键');
        }
        $topic = new TopicModel();
        $data = $topic->where("id", $id)->field("t_name,type")->find();
        $topicUser = new SurveyUserModel();
        $query = $topicUser->alias("a")->leftJoin("t_survey_topic_des b","b.id=a.td_id")->where("a.t_id", $id)->group("a.user_id");
        if ($data->type !== 3) {
            $query->field("a.*,GROUP_CONCAT(`b`.`content`) AS content");
        } else {
            $query->field("a.*,a.answer as content");
        }
        $info = $query->select();
        $info = json_decode(json_encode($info), true);

        $this->assign("t_id", $id);
        $this->assign("name", $data->t_name);
        $this->assign("data", $info);
        return $this->fetch('./result/detail');
    }

    public function export(Request $request)
    {
        if ($request->isPost()) {
            $t_id = $request->post("param");
            if ($t_id == 0) {
                return $this->alertInfo('未获取到问卷主键');
            }
            $topic = new TopicModel();
            $data = $topic->where("id", $t_id)->field("t_name,type")->find();
            $topicUser = new SurveyUserModel();
            $query = $topicUser->alias("a")->leftJoin("t_survey_topic_des b","b.id=a.td_id")->where("a.t_id", $t_id)->group("a.user_id");
            if ($data->type !== 3) {
                $query->field("a.*,GROUP_CONCAT(`b`.`content`) AS content");
            } else {
                $query->field("a.*,a.answer as content");
            }
            $num = $query->count();
            if (empty($num)) {
                return $this->responseToJson([],'当前详情无数据' , 201);
            } else {
                $url = url("result/export");
                return $this->responseToJson(['url'=>$url,'id'=>$t_id, 'num' => $num],"已获取到{$num}条数据" , 200);
            }
        } else {
            if ($request->has("id") && !empty($request->get("id"))) {
                $t_id = $request->get("id");
                $num  = $request->get("num");
                $header = array('用户工号', '用户名', '问题',  '问题类型', '用户答案');
                $dataRow = [0=>'user_id',1=>'user_name',2=>'t_name',3=>'type',4=>'content'];
                $this->exportAllCsv($num, $header, $t_id, $dataRow,"问卷调查用户回答详情".date("Ymd"));
            }else {
                return $this->responseToJson([],'相关参数未获取' , 201);
            }
        }
    }



    /**
     * 导出CSV
     * @param $num
     * @param $header
     * @param $sql
     * @param $dataRow
     * @param string $fileName
     */
    public function exportAllCsv($num, $header, $t_id, $dataRow, $fileName = 'CSV文件')
    {
        $limit = 10000;
        $offset = 0;
        $topic = new TopicModel();
        $topicData = $topic->where("id", $t_id)->field("t_name,type")->find();
        //将数据写入 导出文档
        header('Content-Type:application/csv');
        header('Content-Disposition:attachment;filename='.$fileName.'.csv');
        $fp = fopen('php://output', 'w');
        ob_start();
        foreach ($header as $key => $val) {
            $header[$key] = iconv('UTF-8', 'GBK//IGNORE', $val);
        }
        fputcsv($fp, $header);
        //循环获取导出数据并写入，一次最多取10000条
        for (; $offset < $num; $offset += $limit) {

            $topicUser = new SurveyUserModel();
            $query = $topicUser->alias("a")->leftJoin("t_survey_topic_des b","b.id=a.td_id")->where("a.t_id", $t_id)->group("a.user_id");
            if ($topicData->type !== 3) {
                $query->field("a.*,GROUP_CONCAT(`b`.`content`) AS content");
            } else {
                $query->field("a.*,a.answer as content");
            }
            $data = $query->limit($limit)->select();
            $data = json_decode(json_encode($data), true);
//            //循环写入
            foreach ($data as $row) {
                $line = array();
                $line[$dataRow[0]] = $row[$dataRow[0]];
                $line[$dataRow[1]] = $row[$dataRow[1]];
                $line[$dataRow[2]] = $topicData->t_name;
                $line[$dataRow[3]] = $topicData->type == 1 ? '单选题' : ( $topicData->type == 2 ? '多选题' : '简答题');
                $line[$dataRow[4]] = $row[$dataRow[4]];
                foreach ($line as $key => $val) {
                    $line[$key] = trim(mb_convert_encoding($val, 'gbk'));
                }
                fputcsv($fp, $line);
            }
        }
        fclose($fp);
        echo ob_get_clean();
    }

    public function show(Request $request)
    {
        $t_id = $request->param("id");
        if ($t_id == 0) {
            return $this->alertInfo('未获取到问卷主键');
        }
        $topicDesModel = new TopicDesModel();
        $data = $topicDesModel->where("t_id", $t_id)->select();
        $legendData = $selected = $seriesData = [];
        foreach ($data as $key=>$value) {
            $topicUserModel = new SurveyUserModel();
            $num = $topicUserModel->where("td_id", $value->id)->where("user_id","<>","0")->count();
            $legendData[] = $value->content;
            $selected[] = [$value->content => true];
            $seriesData[] = ['name' => $value->content, 'value' => $num];
        }
        $result['legendData'] = $legendData;
        $result['selected'] = $selected;
        $result['seriesData'] = $seriesData;
        $this->assign("result",json_encode($result, JSON_UNESCAPED_UNICODE));
        return $this->fetch('./result/show');
    }

}