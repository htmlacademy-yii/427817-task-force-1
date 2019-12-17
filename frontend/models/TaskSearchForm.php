<?php

namespace app\models;

use yii\base\Model;

/**
 * Task search form.
 */
class TaskSearchForm extends Model
{
    public $period;
    public $same_city;
    public $is_distant;
    public $categories;
    public $title;

    public function rules()
    {
        return [
            [['same_city', 'is_distant'], 'boolean'],
            ['same_city', 'default', 'value' => false],
            ['is_distant', 'default', 'value' => false],
            ['categories', 'default', 'value' => []],
            ['period', 'in', 'range' => ['day', 'week', 'month']],
            ['title', 'trim'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'same_city' => 'Мой город',
            'is_distant' => 'Удаленная работа',
        ];
    }

    public function getInterval()
    {
        switch ($this->period) {
            case 'day': $term = '24 hour'; break;
            case 'week': $term = '7 day'; break;
            case 'month': $term = '30 day';
        }

        return 'INTERVAL ' . $term;
    }

    public function search($query)
    {
        if ($this->title) {
            $query->andWhere(['like', 'title', $this->title]);
        }

        $query->andWhere(($this->is_distant) ? 'address IS NULL' : 'address IS NOT NULL');
        $query->andWhere('dt_add >= DATE_SUB(now(),' . $this->interval . ')');

        if ($this->categories) {
            $query->andWhere(['category_id' => $this->categories]);
        }
    }
}