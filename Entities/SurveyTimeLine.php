<?php

namespace Modules\SAS\Entities;

use App\Models\DecommissionReason;
use App\Models\ModelBase;
use App\Models\Survey;

class SurveyTimeLine extends ModelBase
{
    protected $table = 'tbl_survey_timeline';

    protected $fillable = [
        'survey_id',
        'pause_timestamp',
        'pause',
        'pause_reason',
        'restart',
        'paused_time',
        'sent_back'
    ];

    public function survey(){
        return $this->hasOne(Survey::class, 'id','survey_id');
    }

    public function reason(){
        return $this->hasOne(DecommissionReason::class, 'id','pause_reason');
    }

    public function getPausedTimeDisplayAttribute(){
        $pausedTime = explode(" ",$this->attributes['paused_time']);
        $day = sprintf("%02d", $pausedTime[0] ?? 0) . ' Day(s) ';
        $hour = sprintf("%02d", $pausedTime[1] ?? 0) . ' Hour(s) ';
        $minute = sprintf("%02d", $pausedTime[2] ?? 0) . ' Min(s)';
        return $day . $hour. $minute;
    }

}
