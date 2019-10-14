<?php

namespace app\index\model;
//use Think\Model;
class Cabinet
{
    public function cabinet()
    {
//        return db('warehouse')->where('is_del',0)->paginate(2);
        return $this->hasOne('warehouse')->slect();
    }

}

