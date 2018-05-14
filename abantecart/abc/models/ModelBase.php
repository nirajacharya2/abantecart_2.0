<?php

namespace abc\models;

use Illuminate\Database\Eloquent\Model as OrmModel;

class AModelBase extends OrmModel
{
    const CREATED_AT = 'date_added';
    const UPDATED_AT = 'date_modified';

}