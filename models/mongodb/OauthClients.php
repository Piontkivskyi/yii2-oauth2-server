<?php

namespace filsh\yii2\oauth2server\models\mongodb;

use Yii;

/**
 * This is the model class for table "oauth_clients".
 *
 * @property \mongoId |string $_id
 * @property \mongoId |string $client_id
 * @property \MongoId |string $user_id
 * @property string $client_secret
 * @property string $redirect_uri
 * @property string $grant_types
 * @property string $scope
 *
 * @property OauthAccessTokens[] $oauthAccessTokens
 * @property OauthAuthorizationCodes[] $oauthAuthorizationCodes
 * @property OauthRefreshTokens[] $oauthRefreshTokens
 */
class OauthClients extends \yii\mongodb\ActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public static function collectionName()
	{
		return 'oauth_clients';
	}

	/**
	 * @inheritdoc
	 * @return array
	 */
	public function attributes()
	{
		return [
			'_id',
			'client_id',
			'client_secret',
			'redirect_uri',
			'grant_types',
			'scope',
			'user_id'
		];
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['client_id', 'client_secret', 'redirect_uri', 'grant_types'], 'required'],
			[['user_id'], 'string'],
			[['client_id', 'client_secret'], 'string', 'max' => 32],
			[['redirect_uri'], 'string', 'max' => 1000],
			[['grant_types'], 'string', 'max' => 100],
			[['scope'], 'string', 'max' => 2000]
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'client_id' => 'Client ID',
			'client_secret' => 'Client Secret',
			'redirect_uri' => 'Redirect Uri',
			'grant_types' => 'Grant Types',
			'scope' => 'Scope',
			'user_id' => 'User ID',
		];
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getOauthAccessTokens()
	{
		return $this->hasMany(OauthAccessTokens::className(), ['client_id' => 'client_id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getOauthAuthorizationCodes()
	{
		return $this->hasMany(OauthAuthorizationCodes::className(), ['client_id' => 'client_id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getOauthRefreshTokens()
	{
		return $this->hasMany(OauthRefreshTokens::className(), ['client_id' => 'client_id']);
	}
}