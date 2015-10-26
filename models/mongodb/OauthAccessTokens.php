<?php

namespace filsh\yii2\oauth2server\models\mongodb;

use Yii;

/**
 * This is the model class for table "oauth_access_tokens".
 *
 * @property \mongoId |string $_id
 * @property \mongoId |string $client_id
 * @property \mongoId |string $user_id
 * @property string $access_token
 * @property string $expires
 * @property string $scope
 *
 * @property OauthClients $client
 */
class OauthAccessTokens extends \yii\mongodb\ActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public static function collectionName()
	{
		return 'oauth_access_tokens';
	}

	/**
	 * @inheritdoc
	 * @return array
	 */
	public function attributes()
	{
		return [
			'_id',
			'access_token',
			'client_id',
			'user_id',
			'expires',
			'scope'
		];
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['access_token', 'client_id', 'expires'], 'required'],
			[['user_id'], 'integer'],
			[['expires'], 'safe'],
			[['access_token'], 'string', 'max' => 40],
			[['client_id'], 'string', 'max' => 32],
			[['scope'], 'string', 'max' => 2000]
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'access_token' => 'Access Token',
			'client_id' => 'Client ID',
			'user_id' => 'User ID',
			'expires' => 'Expires',
			'scope' => 'Scope',
		];
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getClient()
	{
		return $this->hasOne(OauthClients::className(), ['client_id' => 'client_id']);
	}
}