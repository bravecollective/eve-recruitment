<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecruitmentAd extends Model
{
    protected $table = 'recruitment_ad';

    /**
     * Requirements relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function requirements()
    {
        return $this->hasMany('App\Models\RecruitmentRequirement', 'recruitment_id');
    }
}