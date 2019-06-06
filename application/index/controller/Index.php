<?php
namespace app\index\controller;

use app\index\model\SurveyModel;
use app\index\model\SurveyUserModel;
use app\index\model\TopicDesModel;
use app\index\model\TopicModel;
use think\Db;
use think\Exception;
use think\facade\Log;
use think\Request;

class Index extends Base
{
    public function index(Request $request)
    {
        return $this->fetch('./index/index');
    }

    public function gettopicdata(Request $request)
    {
        if ($request->isPost()) {
            $surveyModel = new SurveyModel();
            $limit = $request->param('limit', 10);
            $page = $request->param('page', 1);
            $isWrite = $request->param('is_write', 1);
            $title = $request->param('title');
            $all = $page * $limit;
            $userId = session("index_user_id");
            $userType = session("index_user_type");
            if ($userId == 1)
                $field = "a.*, NULL as info_id";
            else
                $field = "a.*, CASE WHEN b.user_id = '{$userId}' THEN '1' ELSE NULL END AS info_id";
            $query = $surveyModel->alias("a")->where("a.status", 2);
//            if ($userType === 0) { //老师身份
//                $query->whereIn("a.survey_user",'1,3,4');
//            } elseif($userType == 1) { //学生身份
//                $query->whereIn("a.survey_user",'1,2,4');
//            } else { //匿名
//                $query->where("a.survey_user",'1');
//            }
            if (!empty($title)) {
                $query->where("a.title","LIKE","%". $title."%");
            }
            if ($isWrite == 2) { //未填写
                $query->where("b.id",NULL);
            }
            if ($isWrite == 3) { //已填写
                $query->where('b.id','not null');
            }
            $query->leftJoin("t_survey_user b","a.id=b.s_id")
                ->leftJoin("t_survey_type c","c.id=a.survey_user")
                ->where("a.end_time",">=",date("Y-m-d H:i:s"))
                ->where("a.start_time","<=",date("Y-m-d H:i:s"))
                ->where(function ($q) use ($userId) {
//                    if ($userId !== 1)
//                        $q->where("b.user_id", $userId)->whereOr("b.user_id","null");
                })
                ->field($field)->group("a.id");
            $data = $query->limit($all)->select();
            $data = json_decode(json_encode($data), true);
            foreach ($data as $key=>$val) {
                $data[$key]['start_time'] = date("Y-m-d", strtotime($val['start_time']));
                $data[$key]['end_time'] = date("Y-m-d", strtotime($val['end_time']));
            }
            return $this->responseToJson($data,'success');
        } else {
            return $this->responseToJson([],'不被允许的获取方式',201);
        }
    }

    public function detail(Request $request)
    {
        if ($request->isPost()) {
            $insertData = [];
            $surveyUserModel = new SurveyUserModel();
            $s_id = $request->param("s_id", 0);
            $user_id = session("index_user_id") == 1 ? 0 : session("index_user_id");
            if (!empty($user_id)) {
                $isWrite = $surveyUserModel->where("user_id", $user_id)->where("s_id", $s_id)->find();
                if ($isWrite) {
                    return $this->responseToJson([],'当前问卷您已填写过，无需再次填写',201);
                }
            }
            if ($request->has('radio')) {
                $radioArr = $request->param("radio");
                if (!empty($radioArr)) {
                    foreach ($radioArr as $key => $value) {
                        $insertData[] = ['s_id'=>$s_id, 't_id'=>$key, 'td_id' => $value, 'answer' => '','user_id' => $user_id, 'user_name' => session("index_user_name")];
                    }
                }
            }
            if ($request->has('input')) {
                $inputArr = $request->param("input");
                if (!empty($inputArr)) {
                    foreach ($inputArr as $key => $value) {
                        $insertData[] = ['s_id'=>$s_id, 't_id'=>$key, 'td_id' => 0, 'answer' => $value, 'user_id' => $user_id, 'user_name' => session("index_user_name")];
                    }
                }
            }
            if ($request->has('checkbox')) {
                $checkBoxArr = $request->param("checkbox");
                if (!empty($checkBoxArr)) {
                    foreach ($checkBoxArr as $key => $value) {
                        foreach ($value as $info) {
                            $insertData[] = ['s_id'=>$s_id, 't_id'=>$key, 'td_id' => $info, 'answer' => '','user_id' => $user_id, 'user_name' => session("index_user_name")];
                        }
                    }
                }
            }
            Db::startTrans();
            try{
                $result = $surveyUserModel->insertAll($insertData);
                Db::commit();
                if ($result) {
                    return $this->responseToJson(['target_url'=>url('/index/index/index')],'问卷提交成功！！');
                } else {
                    return $this->responseToJson([],'提交问卷失败！！', 201);
                }
            }catch (\Exception $exception) {
                Db::rollback();
                Log::error('--------errordata------');
                Log::error($insertData);
                Log::error($exception->getMessage());
                return $this->responseToJson([],'提交问卷失败',201);
            }
        }
        $id = $request->param("id",0);
        if (empty($id)) {
            exit($this->alertInfo("相关参数未获取"));
        }
//        if (session("index_user_type") === 0) {
//            $survey_user = ['1','3','4'];
//        } elseif(session("index_user_type") == 1) {
//            $survey_user = ['1','2','4'];
//        } else {
//            $survey_user = ['1'];
//        }
        $data = SurveyModel::where("id", $id)->find();
        if ($data->status == 1)
            exit($this->alertInfo("当前问卷未发布"));
        if (strtotime($data->end_time) < time())
            exit($this->alertInfo("当前问卷访问时间已过期"));
//        if (!in_array($data->survey_user, $survey_user))
//            exit($this->alertInfo("访问问卷详情被拒绝"));
        $topicModel = new TopicModel();
        $topicDesModel = new TopicDesModel();
        $surveyUserModel = new SurveyUserModel();
        $isWrite = $surveyUserModel->where("user_id", session("index_user_id"))->where("s_id", $id)->find();
        if ($isWrite) {
            exit($this->alertInfo("当前问卷您已填写过，无需再次填写"));
        }
        $question = $topicModel->alias("a")->where("a.s_id", $id)->select();
        $q_ids    = $topicModel->alias("a")->where("a.s_id", $id)->where("type","<>","3")->column("id");
        $answer   = $topicDesModel->whereIn("t_id",$q_ids)->select();
        $answer   = json_decode(json_encode($answer), true);
        $question = json_decode(json_encode($question), true);
        foreach ($question as $key => $value) {
            $question[$key]['content'] = [];
            foreach ($answer as $list => $info) {
                if ($info['t_id'] == $value['id']) {
                    $question[$key]['content'][] = ['des_id' => $info['id'],'top_id' => $info['t_id'], 'content' => $info['content']];
                    unset($answer[$list]);
                }
            }
        }
        $this->assign("title", $data->title);
        $this->assign("s_id", $id);
        $this->assign("question", $question);
        return $this->fetch('./index/detail');
    }

    public function judge(Request $request)
    {
        if ($request->isPost()) {
            $errorMsg = '';
            $id = $request->param("id",0);
            if (empty($id)) {
                $errorMsg = "相关参数未获取";
            }
//            if (session("index_user_type") === 0) {
//                $survey_user = ['1','3','4'];
//            } elseif(session("index_user_type") == 1) {
//                $survey_user = ['1','2','4'];
//            } else {
//                $survey_user = ['1'];
//            }
            $data = SurveyModel::where("id", $id)->find();
            if ($data->status == 1)
                $errorMsg = "当前问卷未发布";
            if (strtotime($data->end_time) < time())
                $errorMsg = "当前问卷访问时间已过期";
//            if (!in_array($data->survey_user, $survey_user))
//                $errorMsg = "访问问卷详情被拒绝";
            $surveyUserModel = new SurveyUserModel();
            $isWrite = $surveyUserModel->where("user_id", session("index_user_id"))->where("s_id", $id)->find();
            if ($isWrite)
                $errorMsg = "当前问卷您已填写过，无需再次填写";
            if (empty($errorMsg)) {
                return $this->responseToJson([],'success',200);
            } else {
                return $this->responseToJson([], $errorMsg,201);
            }
        }
    }

    public function validation($data, $name)
    {
        $valid = $this->validate($data, $name);
        if (true !== $valid) {
            exit($this->responseToJson([], $valid, 201, false));
        }
        if (empty(trim($data['title']))) {
            exit($this->responseToJson([], '咨询标题不能为空', 201, false));
        }
        return $data;
    }
}
