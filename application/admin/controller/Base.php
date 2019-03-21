<?php
namespace app\admin\controller;

use app\admin\model\SurveyModel;
use think\Controller;
use think\Db;
use think\facade\Log;

class Base extends Controller
{
    public function initialize()
    {
        $user_id = input('get.user_id');
        $school_id = input('get.school_id');
        $client_id = input('get.client_id');
        if (!empty($user_id) && !empty($school_id) && !empty($client_id)) {
            session('auth_status', 0);
            session('user_id', $user_id);
            $url = "https://mcpapi.iyuyun.net:18443/oauth/service/config/admin?user_id=" . $user_id . "&service_id=" . $client_id;
//            $url = "http://47.100.19.248:8000/oauth/service/config/admin?user_id=" . $user_id . "&service_id=" . $client_id;
            $header = [
                "Content-Type:application/json;charset=UTF-8",
                "school_id:" . $school_id,
                'mdc_value:12345-54321-12345-54321-12345',
            ];
            $res = $this->curlRequest($url, 'get', $header,'',  []);
            if (empty($res) || $res['error_code'] != 1000) {
                exit($this->fetch('./403',[
                    'msg' => '身份认证失败'
                ]));
            }
            if ($res['extra']['status'] == 1) {
                session('auth_status', $res['extra']['status']);
                session('school_id', $school_id);
            } else {
                session('auth_status',1);
                session('school_id', $school_id);
//                exit($this->fetch('./403',[
//                    'msg' => '没有权限'
//                ]));
            }
        } else {
            if (empty(session('auth_status'))) {
                exit($this->fetch('./403',[
                    'msg' => '身份过期，请重新登陆!'
                ]));
            }
        }
        if (empty($school_id)) {
            $school_id = session('school_id');
        }
        $db = Db::table('t_sys_mod_biz_db')->where('school_id', $school_id)->find();
        if ($db) {
            $config = [
                'type'            => 'mysql',
                'hostname'        => $db['db_server'],
                'database'        => $db['db_name'],
                'username'        => $db['db_user'],
                'password'        => $db['db_pass'],
                'hostport'        => $db['db_port'],
                'dsn'             => '',
                'params'          => [],
                'charset'         => 'utf8',
                'prefix'          => '',
            ];
            session('db-config_'.$school_id, $config);
        } else {
            exit($this->fetch('./404',[
                'msg' => '初始化数据失败!'
            ]));
        }
    }
    /**
     * @param array $data
     * @param string $msg
     * @param int $code
     * @param bool $default
     * @return array|string
     */
    public function responseToJson($data = [], $msg = 'ok', $code = 200, $default = true) {
        if ($default) {
            return [
                'code' => $code,
                'msg' => $msg,
                'data' => $data,
            ];
        }
        return json_encode([
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
        ], JSON_UNESCAPED_UNICODE);
    }

    public function getTeacher()
    {
        //获取token
        $client_id = session("client_id");
        $tokenService = config('api.getToken');
        $teacherService = config('api.getTeacher');
        $basicHeader[] = "Authorization: Basic ".base64_encode("{$client_id}:{$tokenService['basic']['password']}"); //添加头，在name和pass处填写对应账号密码
        $tokenHeader = ['school_id:' . session("school_id")];
        $tokenHeader = array_merge($tokenHeader, $basicHeader);
        $tokenService['body']['username'] = session("user_id");
        $token = $this->curlRequest($tokenService['url'], $tokenService['method'], $tokenHeader, $tokenService['body'], []);
        if (!isset($token['access_token'])) {
            exit($this->fetch('./500',[
                'msg' => '获取token失败'
            ]));
        } else {
            $bearerHeader[] = "Authorization: Bearer ".$token['access_token'];
            $teacherHeader = [
                'school_id:' . session("school_id"),
                'master_key:' . $teacherService['header']['master_key'],
                'mdc_value:' . $teacherService['header']['mdc_value'],
            ];
            $teacherHeader = array_merge($teacherHeader, $bearerHeader);
            $res = $this->curlRequest($teacherService['url'], $teacherService['method'], $teacherHeader, $teacherService['body'], []);
            if ($res['error_code'] == 1000) {
                return $res['extra'];
            } else {
                exit($this->fetch('./500',[
                    'msg' => '获取教师记录失败'
                ]));
            }
        }
    }


    function curlRequest($url, $method = 'POST', $header = [], $data = '', $contentType = ['Content-Type:application/json'])
    {
        $headers = array_merge($contentType, [
            "Connection:Keep-Alive",
            'Accept:application/json',
        ], $header);
        // setting the curl parameters.
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        // turning off the server and peer verification(TrustManager Concept).
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        // setting the POST FIELD to curl
        $method = strtoupper($method);
        switch ($method) {
            case"GET" :
                curl_setopt($ch, CURLOPT_HTTPGET, 1);
                break;
            case "POST":
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                break;
            case "PUT" :
                curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                break;
            case "DELETE":
                curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                break;
        }
        // getting response from server
        $response = curl_exec($ch);

        //close the connection
        curl_close($ch);
        //return the response
        if (stristr($response, 'HTTP 404') || $response == '') {
            return [
                'error_code' => '1001',
                'error_msg' => '请求错误'
            ];
        }

        return is_string($response) ? json_decode($response, true) : $response;
    }

    /**
     * 消息提示模板
     * @param string $msg 提示信息
     * @param string $url 跳转地址
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    protected function alertInfo($msg = '')
    {
        return $this->fetch('./common/alert-info', [
            'msg' => $msg,
        ]);
    }


    public function checkIsRelease($id)
    {
        $status = SurveyModel::whereIn("id", $id)->value("status");
        if ($status == 1) {
            return true;
        } else {
            exit($this->alertInfo('当前问卷已发布，无法再次添加/修改'));
        }
    }
}
