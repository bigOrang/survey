<?php
/**
 * Created by PhpStorm.
 * User: id_orange
 * Date: 2019/2/15
 * Time: 13:43
 */

namespace app\admin\model;


use think\Model;
use think\model\concern\SoftDelete;

class SurveyTypeModel extends BaseModel
{
    protected $table = "t_survey_type";

}