<?php
namespace app\admin\controller;

use app\admin\model\SurveyModel;
use app\admin\model\TopicDesModel;
use app\admin\model\TopicModel;
use think\Db;
use think\Exception;
use think\facade\App;
use think\facade\Env;
use think\facade\Log;
use think\Request;

class Topic extends Base
{
    public function index(Request $request)
    {
        if ($request->isPost()) {
            try{
                Log::error($request->param());
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
            } catch (Exception $exception) {
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
        return $this->fetch('./topic/index');
    }


    public function add(Request $request) {
        if ($request->isPost()) {
            $requestData = $this->validation($request->post(), 'Topic');
            Db::startTrans();
            try {
                $insertAllData = [];
                $topicModel = new TopicModel();
                $topicDesModel = new TopicDesModel();
                $t_id = $topicModel->insertGetId([
                    's_id'       => $requestData['s_id'],
                    't_name'       => $requestData['t_name'],
                    'type'=> $requestData['type'],
                    'is_required'    => $requestData['is_required'],
                ]);
                foreach ($requestData['t_description'] as $v) {
                    $insertAllData[] = [
                        't_id' => $t_id,
                        'content' => $v,
                    ];
                }
                $topicDesModel->where("t_id", $t_id)->delete();
                $topicDesModel->insertAll($insertAllData);
                Db::commit();
                return $this->responseToJson([],'添加成功');
            } catch (\Exception $e) {
                Db::rollback();
                return $this->responseToJson([],'添加失败'.$e->getMessage() , 201);
            }
        }
        $s_id = $request->param('s_id', 0);
        if ($s_id !== 0) {
            $this->assign('s_id', $s_id);
        } else {
            return $this->alertInfo('未获取到问卷主键');
        }
        $this->checkIsRelease($s_id);
        $surveyModel = new SurveyModel();
        $result = $surveyModel->where("id", $s_id)->find();
        if (is_null($result)) {
            return $this->alertInfo('相关参数未获取');
        } else {
            return $this->fetch('./topic/add');
        }
    }

    public function update(Request $request)
    {
        if ($request->isPost()) {
            $requestData = $this->validation($request->post(), 'Topic');
            Db::startTrans();
            try {
                $insertAllData = [];
                $topicModel = new TopicModel();
                $topicDesModel = new TopicDesModel();
                $topicModel->where("id", $requestData['id'])->update([
                    't_name'       => $requestData['t_name'],
                    'type'=> $requestData['type'],
                    'is_required'    => $requestData['is_required'],
                ]);
                foreach ($requestData['t_description'] as $v) {
                    $insertAllData[] = [
                        't_id' => $requestData['id'],
                        'content' => $v,
                    ];
                }
                $topicDesModel->where("t_id", $requestData['id'])->delete();
                $topicDesModel->insertAll($insertAllData);
                // 提交事务
                Db::commit();
                return $this->responseToJson([],'编辑成功');
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                return $this->responseToJson([],'编辑失败'.$e->getMessage() , 201);
            }
        }
        if ($request->has("id") && !empty($request->param("id"))) {
            $id = $request->param("id");
            $topicModel = new TopicModel();
            $topicDesModel = new TopicDesModel();
            $topicData = $topicModel->where("id", $id)->find();
            if (is_null($topicData)) {
                return $this->alertInfo('未获取到问卷问题');
            } else {
                $this->checkIsRelease($topicData->s_id);
            }
            $topicDesData = $topicDesModel->where("t_id", $id)->column("content");
            $topicDesData = json_decode(json_encode($topicDesData), true);
            if (empty($topicDesData)) {
                $this->assign('firstDes', []);
                $this->assign('otherDes', []);
            } else {
                $this->assign('firstDes', $topicDesData[0]);
                unset($topicDesData[0]);
                $this->assign('otherDes', $topicDesData);
            }
            $this->assign('topicData', $topicData);
            return $this->fetch('./topic/edit');
        } else {
            return $this->alertInfo('相关参数未获取');
        }
    }


    public function delete(Request $request)
    {
        if ($request->has("ids") && !empty($request->param("ids"))) {
            $ids = $request->param("ids");
            $topicModel = new TopicModel();
            $topicDesModel = new TopicDesModel();
            $s_ids = $topicModel->whereIn("id", $ids)->column("s_id");
            $s_ids = array_unique($s_ids);
            $status = SurveyModel::whereIn("id", $s_ids)->value("status");
            if ($status == 2) {
                return $this->responseToJson([],'当前问卷已发布，无法删除' , 201);
            }
            Db::startTrans();
            try{
                $topicModel->destroy($ids);
                $topicDesModel->whereIn("t_id", $ids)->delete();
                Db::commit();
                return $this->responseToJson([],'删除成功' , 200);
            }catch (\Exception $e) {
                Db::rollback();
                return $this->responseToJson([],'删除失败'.$e->getMessage() , 201);
            }
        } else {
            return $this->responseToJson([],'相关参数未获取' , 201);
        }
    }


    public function upload(Request $request)
    {
        if ($request->isPost()) {
            $s_id = $request->param("s_id", 0);
            $file = $request->file("files");
            if (empty($file)) {
                return $this->responseToJson([],'未获取到上传文件' , 201);
            }
            if (empty($s_id)) {
                return $this->responseToJson([],'未获取到相关参数' , 201);
            }
            $fileUrl = $_FILES['files']['tmp_name'];//文件临时存放路径
            $fileName = $_FILES['files']['name'];//文件名称
            //上传另存
//            $info = $file->rule('uniqid')->move( '.'.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'uploads');
//            $fileUrl = App::getRootPath() .'public'. DIRECTORY_SEPARATOR.'uploads'. DIRECTORY_SEPARATOR.$info->getSaveName();
            if (empty($fileUrl)) {
                return $this->responseToJson([],'请选择要导入的文件' , 201);
            }
            $type = strtolower(trim(substr(strrchr($fileName, '.'), 1)));//获取文件类型
            if ($type != 'csv') {
                return $this->responseToJson([],'请选择csv类型的文件上传' , 201);
            }
            //开始读取文件
            $handle = fopen($fileUrl, "r");
            if(!$handle){
                return $this->responseToJson([],'文件打开失败' , 201);
            }
            $topicDesInfo = array();
            $hang = 0;
            while ($data = fgetcsv($handle)) {
                $hang++;
                foreach ($data as $i => $val)
                    $data[$i] = mb_convert_encoding($val, "UTF-8", "GBK");
                if (empty($data[0]) || empty($data[1]) || empty($data[2])) {
                    return $this->responseToJson([],'请检查文件必填项' , 201);
                    break;
                }
                $type        = $data[1];
                $t_name      = $data[0];
                $is_required = $data[2];
                $content     = array_diff($data, [$type, $t_name, $is_required]);
                $topicDesInfo[] = [
                    's_id'        => $s_id,
                    't_name'      => $t_name,
                    'type'        => $type,
                    'is_required' => $is_required,
                    'description' => $content
                ];
            }
            unset($topicDesInfo[0]);
            fclose($handle);
            $topicDesModel = new TopicDesModel();
            $len_result = count($topicDesInfo);
            if ($len_result == 0)
                return $this->responseToJson([],'文件没有任何数据' , 201);
            $result = $topicDesModel->importDesInfo($topicDesInfo);
            if ($result !== false) {
                return $this->responseToJson([],"导入文件成功，共计{$result}条" , 200);
            } else {
                return $this->responseToJson([],'导入文件失败' , 201);
            }
        }
        $s_id = $request->param('s_id', 0);
        if ($s_id !== 0) {
            $this->assign('s_id', $s_id);
        } else {
            exit($this->fetch('./404',[
                'msg' => '未获取到问卷主键'
            ]));
        }
        $surveyModel = new SurveyModel();
        $result = $surveyModel->where("id", $s_id)->find();
        if (is_null($result)) {
            return $this->alertInfo('相关参数未获取');
        } else {
            $this->checkIsRelease($s_id);
            return $this->fetch('./topic/upload');
        }
    }


    public function validation($data, $name)
    {
        $valid = $this->validate($data, $name);
        if (true !== $valid) {
            exit($this->responseToJson([], $valid, 201, false));
        }
//        Log::error($data);
        if ($data['type'] != 3) {
            foreach ($data['t_description'] as $key => $value) {
                $num = $key + 1;
                if (mb_strlen($value ,'UTF8') < 2) {
                    exit($this->responseToJson([], "第{$num}个问题描述字符数少于2字符", 201, false));
                }
                if (mb_strlen($value ,'UTF8') > 100) {
                    exit($this->responseToJson([], "第{$num}个问题描述字符数大于100字符", 201, false));
                }
            }
        }
        return $data;
    }
}
