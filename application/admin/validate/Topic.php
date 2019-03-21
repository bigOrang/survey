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
class Topic extends Validate
{
    // 定义验证规则
    protected $rule = [
        'type|问题分类'     => "require",
        'is_required|是否必填'     => "require",
        "t_name|问题名称"       => "require|max:50|min:2",
        "t_description|问题题目描述"       => "require",
    ];

    protected $message  =   [
        'type.require' => '问题分类不能为空',
        'is_required.require'  => '是否必填不能为空',
        't_name.require' => '问题名称不能为空',
        't_name.max' => '问题名称不能多于50个字符',
        't_name.min' => '问题名称不能少于2个字符',
        't_description.require' => '问题题目描述不能为空',
//        't_description.max' => '问题题目描述不能多于100个字符',
//        't_description.min' => '问题题目描述不能少于2个字符',
    ];
}
