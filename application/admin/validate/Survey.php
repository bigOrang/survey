<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2017 河源市卓锐科技有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------
// | 开源协议 ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------

namespace app\admin\validate;

use think\Validate;

/**
 * 客服验证器
 * @package app\cms\validate
 * @author 蔡伟明 <314013107@qq.com>
 */
class Survey extends Validate
{
    // 定义验证规则
    protected $rule = [
        "title|问卷标题"      => "require|max:15|min:2|unique:SurveyModel,title",
        "survey_user|问卷对象"      => "require",
        "start_time|开始时间"       => "require",
        "end_time|结束时间"       => "require",
        "description|问卷描述"       => "max:100",
    ];

    protected $message  =   [
        'title.require' => '问卷标题不能为空',
        'title.max' => '问卷标题不能超过15个字符',
        'title.min' => '问卷标题不能少于2个字符',
        'title.unique' => '问卷标题不能重复',
        'survey_user.require'  => '问卷对象不能为空',
        'start_time.require'  => '开始时间不能为空',
        'end_time.require'  => '结束时间不能为空',
        'description.max' => '问卷描述不能超过100个字符',
    ];
}
