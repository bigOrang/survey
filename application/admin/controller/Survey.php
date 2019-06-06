<?php
namespace app\admin\controller;

use app\admin\model\CategoryModel;
use app\admin\model\SurveyModel;
use app\admin\model\SurveyTypeModel;
use app\admin\model\TopicDesModel;
use app\admin\model\TopicModel;
use think\Db;
use think\Exception;
use think\facade\Log;
use think\Request;

class Survey extends Base
{
    public function index(Request $request)
    {
        if ($request->isPost()) {
            try{
                $limit = $request->param('limit', 10);
                $searchData = $request->param();
                $survey = new SurveyModel();
                $data = $survey->alias("b")->where(function ($query) use ($searchData){
                    //模糊搜索
                    if (isset($searchData['search']) || !empty($searchData['search']))
                        $query->where('b.title', 'like','%'.$searchData['search'].'%');
                })
                    ->leftJoin("t_survey_type a","a.id=b.survey_user")
                    ->field("b.*,a.name")->order("id","desc")->paginate($limit);
                $data = json_decode(json_encode($data),true);
            } catch (Exception $exception) {
                Log::error('获取数据错误：'. $exception->getMessage());
                $data = ['total' => 0, 'rows' => []];
            }
            return [
                'total' => $data['total'],
                'rows' => $data['data']
            ];
        }
        return $this->fetch('./survey/index');
    }

    public function add(Request $request) {
        if ($request->isPost()) {
            $requestData = $this->validation($request->post(), 'Survey');
            try {
                $survey = new SurveyModel();
                $survey->insert([
                    'title'     => trim($requestData['title']),
                    'start_time'=> $requestData['start_time'],
                    'end_time'  => $requestData['end_time'],
                    'survey_user'=> $requestData['survey_user'],
                    'submit_at' => date("Y-m-d H:i:s"),
                    'description'=> isset($requestData['description']) ? trim($requestData['description']) : '',
                ]);
                return $this->responseToJson([],'添加成功');
            } catch (\Exception $e) {
                return $this->responseToJson([],'添加失败'.$e->getMessage() , 201);
            }
        }
        $surveyTypeModel = new SurveyTypeModel();
        $types = $surveyTypeModel->where("is_show",1)->select();
        $this->assign("types", $types);
        return $this->fetch('./survey/add');
    }

    public function update(Request $request)
    {
        if ($request->isPost()) {
            $requestData = $this->validation($request->post(), 'Survey');
            try {
                $survey = new SurveyModel();
                $survey->where("id", $requestData['id'])->update([
                    'title'     => trim($requestData['title']),
                    'start_time'=> $requestData['start_time'],
                    'end_time'  => $requestData['end_time'],
                    'survey_user'=> $requestData['survey_user'],
                    'submit_at' => date("Y-m-d H:i:s"),
                    'description'=> isset($requestData['description']) ? trim($requestData['description']) : '',
                ]);
                return $this->responseToJson([],'编辑成功');
            } catch (\Exception $e) {
                return $this->responseToJson([],'编辑失败'.$e->getMessage() , 201);
            }
        }
        if ($request->has("id") && !empty($request->param("id"))) {
            $id = $request->param("id");
            $this->checkIsRelease($id);
            $data = SurveyModel::where("id", $id)->find();
            $data = json_decode(json_encode($data),true);
            $surveyTypeModel = new SurveyTypeModel();
            $types = $surveyTypeModel->where("is_show",1)->select();
            $this->assign("types", $types);
            $this->assign("data", $data);
            return $this->fetch('./survey/edit');
        } else {
            return $this->responseToJson([],'相关参数未获取' , 201);
        }
    }


    public function release(Request $request)
    {
        if ($request->has("ids") && !empty($request->param("ids"))) {
            $ids = $request->param("ids");
            try{
                $data = SurveyModel::where("id", $ids)->findOrEmpty();
                if (empty($data) || strtotime($data->end_time) < time()) {
                    return $this->responseToJson([],'问卷无法发布,当前时间已超过问卷结束时间' , 201);
                }
                $topics = TopicModel::where("s_id", $ids)->count();
                if ($topics > 0) {
                    $result = SurveyModel::where("id", $ids)->update(['status' => 2]);
                    if ($result) {
                        return $this->responseToJson([],'发布成功' , 200);
                    } else {
                        return $this->responseToJson([],'该问卷已发布' , 201);
                    }
                } else {
                    return $this->responseToJson([],'问卷无法发布，当前问卷没有相关问题' , 201);
                }
            }catch (Exception $e) {
                return $this->responseToJson([],'发布失败'.$e->getMessage() , 201);
            }
        } else {
            return $this->responseToJson([],'相关参数未获取' , 201);
        }
    }

    public function resultData(Request $request)
    {

    }

    public function delete(Request $request)
    {
        if ($request->has("ids") && !empty($request->param("ids"))) {
            $ids = $request->param("ids");
            Db::startTrans();
            try{
                SurveyModel::destroy(function($query) use ($ids) {
                    $query->whereIn("id",$ids);
                });
                $t_ids = TopicModel::whereIn("s_id", $ids)->column("id");
                TopicModel::destroy(function($query) use ($ids) {
                    $query->whereIn("s_id",$ids);
                });
                TopicDesModel::whereIn("t_id", $t_ids)->delete();
                Db::commit();
                return $this->responseToJson([],'删除成功' , 200);
            }catch (Exception $e) {
                Db::rollback();
                return $this->responseToJson([],'删除失败'.$e->getMessage() , 201);
            }
        } else {
            return $this->responseToJson([],'相关参数未获取' , 201);
        }
    }

    public function validation($data, $name)
    {
        $valid = $this->validate($data, $name);
        if (true !== $valid) {
            exit($this->responseToJson([], $valid, 201, false));
        }
        if (strtotime($data['start_time']) >= strtotime($data['end_time'])) {
            exit($this->responseToJson([], '开始时间应该小于结束时间', 201, false));
        }
        return $data;
    }
}
