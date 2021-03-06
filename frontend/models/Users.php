<?php

namespace frontend\models;

/**
 * This is the model class for table "users".
 *
 * @property int              $id
 * @property int              $city_id
 * @property string           $name
 * @property string           $email
 * @property string           $password
 * @property string           $rating
 * @property int              $orders
 * @property int              $failures
 * @property int              $popularity
 * @property string           $dt_add
 * @property Accounts[]       $accounts
 * @property Chats[]          $chats
 * @property Chats[]          $chats0
 * @property Favorites[]      $favorites
 * @property Favorites[]      $favorites0
 * @property Responds[]       $responds
 * @property Reviews[]        $reviews
 * @property Specialization[] $specializations
 * @property Tasks[]          $tasks
 * @property Tasks[]          $tasks0
 * @property Cities           $city
 */
class Users extends \yii\db\ActiveRecord implements \yii\web\IdentityInterface
{
    public static function findIdentity($id)
    {
        return self::findOne($id);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
    }

    public function getId()
    {
        return $this->getPrimaryKey();
    }

    public function getAuthKey()
    {
    }

    public function validateAuthKey($authKey)
    {
    }

    public function validatePassword($password)
    {
        return \Yii::$app->security->validatePassword($password, $this->password);
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'users';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['city_id', 'orders', 'failures', 'popularity'], 'integer'],
            [['name', 'email', 'password'], 'required'],
            [['rating'], 'number'],
            [['dt_add'], 'safe'],
            [['name', 'email'], 'string', 'max' => 60],
            [['password'], 'string', 'max' => 128],
            ['email', 'unique'],
            [['city_id'], 'exist', 'skipOnError' => true, 'targetClass' => Cities::className(), 'targetAttribute' => ['city_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'city_id' => 'City ID',
            'name' => 'Name',
            'email' => 'Email',
            'password' => 'Password',
            'rating' => 'рейтинг',
            'orders' => 'число заказов',
            'failures' => 'число провалов',
            'popularity' => 'популярность',
            'dt_add' => 'дата регистрации',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAccounts()
    {
        return $this->hasMany(Accounts::className(), ['user_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChats()
    {
        return $this->hasMany(Chats::className(), ['receiver_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChats0()
    {
        return $this->hasMany(Chats::className(), ['sender_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFavorites()
    {
        return $this->hasMany(Favorites::className(), ['user_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFavorites0()
    {
        return $this->hasMany(Favorites::className(), ['favorite_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getResponds()
    {
        return $this->hasMany(Responds::className(), ['author_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getReviewsAmount()
    {
        return $this->hasMany(Reviews::className(), ['user_id' => 'id'])->count();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getReviews()
    {
        return $this->hasMany(Reviews::className(), ['user_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSpecialization()
    {
        return $this->hasMany(Specialization::className(), ['user_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTasks()
    {
        return $this->hasMany(Tasks::className(), ['author_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTasks0()
    {
        return $this->hasMany(Tasks::className(), ['executor_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCity()
    {
        return $this->hasOne(Cities::className(), ['id' => 'city_id']);
    }
}
