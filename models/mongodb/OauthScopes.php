<?php

namespace filsh\yii2\oauth2server\models\mongodb;

use Yii;

/**
 * This is the model class for table "oauth_scopes".
 *
 * @property \mongoId |string $_id
 * @property string $scope
 * @property integer $is_default
 *
 */
class OauthScopes extends \yii\mongodb\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function collectionName()
    {
        return 'oauth_scopes';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['scope', 'is_default'], 'required'],
            [['is_default'], 'integer'],
            [['scope'], 'string', 'max' => 2000]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'scope' => 'Scope',
            'is_default' => 'Is Default',
        ];
    }
}