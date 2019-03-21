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

class Index extends Base
{
    public function index(Request $request)
    {
        return $this->fetch('./index');
    }
}
