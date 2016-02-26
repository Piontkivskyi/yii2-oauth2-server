<?php

namespace filsh\yii2\oauth2server\storage;

use OAuth2\Storage\PublicKeyInterface;
use OAuth2\Storage\ScopeInterface;

class Mongo extends \OAuth2\Storage\Mongo implements PublicKeyInterface, ScopeInterface
{
	public $dsn;
	public $connection = 'mongodb';

	/**
	 * @param null $connection
	 * @param array $config
	 * @throws \yii\base\InvalidConfigException
	 * @throws \yii\mongodb\Exception
	 */
	public function __construct($connection = null, $config = [])
	{
		if ($connection === null) {
			if ($this->connection !== null && \Yii::$app->has($this->connection)) {
				$db = \Yii::$app->get($this->connection);
				if (!($db instanceof \yii\mongodb\Connection)) {
					throw new \yii\base\InvalidConfigException('Connection component must implement \yii\mongodb\Connection.');
				}

				$connection = $db->getDatabase()->mongoDb;
			}
		} else {
			$connection = new \yii\mongodb\Connection([
				'dsn' => $this->dsn,
			]);
			$connection = $connection->getDatabase()->mongoDb;
		}

		parent::__construct($connection, $config);
	}

	/* ClientCredentialsInterface */
	public function checkClientCredentials($client_id, $client_secret = null)
	{
		if ($result = $this->collection('client_table')->findOne([
			'$or' => [
				['client_id' => $client_id],
				//['client_id' => new \MongoId($client_id)]
			]
		])
		) {
			return $result['client_secret'] == $client_secret;
		}

		return false;
	}

	public function isPublicClient($client_id)
	{
		if (!$result = $this->collection('client_table')->findOne([
			'$or' => [
				['client_id' => $client_id],
				//['client_id' => new \MongoId($client_id)]
			]
		])
		) {
			return false;
		}

		return empty($result['client_secret']);
	}

	/* ClientInterface */
	public function getClientDetails($client_id)
	{
		$result = $this->collection('client_table')->findOne([
			'$or' => [
				['client_id' => $client_id],
				//['client_id' => new \MongoId($client_id)]
			]
		]);

		return is_null($result) ? false : $result;
	}

	public function setClientDetails($client_id, $client_secret = null, $redirect_uri = null, $grant_types = null, $scope = null, $user_id = null)
	{
		if ($this->getClientDetails($client_id)) {
			$this->collection('client_table')->update(
				['client_id' => $client_id],
				[
					'$set' => [
						'client_secret' => $client_secret,
						'redirect_uri' => $redirect_uri,
						'grant_types' => $grant_types,
						'scope' => $scope,
						'user_id' => $user_id,
					]
				]
			);
		} else {
			$client = [
				'client_id' => $client_id,
				'client_secret' => $client_secret,
				'redirect_uri' => $redirect_uri,
				'grant_types' => $grant_types,
				'scope' => $scope,
				'user_id' => $user_id,
			];
			$this->collection('client_table')->insert($client);
		}

		return true;
	}

	public function checkRestrictedGrantType($client_id, $grant_type)
	{
		$details = $this->getClientDetails($client_id);
		if (isset($details['grant_types'])) {
			$grant_types = explode(' ', $details['grant_types']);

			return in_array($grant_type, $grant_types);
		}

		// if grant_types are not defined, then none are restricted
		return true;
	}

	/* AccessTokenInterface */
	public function getAccessToken($access_token)
	{
		$token = $this->collection('access_token_table')->findOne(['access_token' => $access_token]);

		return is_null($token) ? false : $token;
	}

	public function setAccessToken($access_token, $client_id, $user_id, $expires, $scope = null)
	{
		// if it exists, update it.
		if ($this->getAccessToken($access_token)) {
			$this->collection('access_token_table')->update(
				['access_token' => $access_token],
				[
					'$set' => [
						'client_id' => $client_id,
						'expires' => $expires,
						'user_id' => $user_id,
						'scope' => $scope
					]
				]
			);
		} else {
			$token = [
				'access_token' => $access_token,
				'client_id' => $client_id,
				'expires' => $expires,
				'user_id' => $user_id,
				'scope' => $scope
			];
			$this->collection('access_token_table')->insert($token);
		}

		return true;
	}

	public function unsetAccessToken($access_token)
	{
		$this->collection('access_token_table')->remove(['access_token' => $access_token]);
	}

	/* AuthorizationCodeInterface */
	public function getAuthorizationCode($code)
	{
		$code = $this->collection('code_table')->findOne(['authorization_code' => $code]);

		return is_null($code) ? false : $code;
	}

	public function setAuthorizationCode($code, $client_id, $user_id, $redirect_uri, $expires, $scope = null, $id_token = null)
	{
		// if it exists, update it.
		if ($this->getAuthorizationCode($code)) {
			$this->collection('code_table')->update(
				['authorization_code' => $code],
				[
					'$set' => [
						'client_id' => $client_id,
						'user_id' => $user_id,
						'redirect_uri' => $redirect_uri,
						'expires' => $expires,
						'scope' => $scope,
						'id_token' => $id_token,
					]
				]
			);
		} else {
			$token = [
				'authorization_code' => $code,
				'client_id' => $client_id,
				'user_id' => $user_id,
				'redirect_uri' => $redirect_uri,
				'expires' => $expires,
				'scope' => $scope,
				'id_token' => $id_token,
			];
			$this->collection('code_table')->insert($token);
		}

		return true;
	}

	public function expireAuthorizationCode($code)
	{
		$this->collection('code_table')->remove(['authorization_code' => $code]);

		return true;
	}

	/* UserCredentialsInterface */
	public function checkUserCredentials($username, $password)
	{
		if ($user = $this->getUser($username)) {
			return $this->checkPassword($user, $password);
		}

		return false;
	}

	public function getUserDetails($username)
	{
		if ($user = $this->getUser($username)) {
			$user['user_id'] = $user['username'];
		}

		return $user;
	}

	/* RefreshTokenInterface */
	public function getRefreshToken($refresh_token)
	{
		$token = $this->collection('refresh_token_table')->findOne(['refresh_token' => $refresh_token]);

		return is_null($token) ? false : $token;
	}

	public function setRefreshToken($refresh_token, $client_id, $user_id, $expires, $scope = null)
	{
		$token = [
			'refresh_token' => $refresh_token,
			'client_id' => $client_id,
			'user_id' => $user_id,
			'expires' => $expires,
			'scope' => $scope
		];
		$this->collection('refresh_token_table')->insert($token);

		return true;
	}

	public function unsetRefreshToken($refresh_token)
	{
		$this->collection('refresh_token_table')->remove(['refresh_token' => $refresh_token]);

		return true;
	}

	// plaintext passwords are bad!  Override this for your application
	protected function checkPassword($user, $password)
	{
		return $user['password'] == $password;
	}

	public function getUser($username)
	{
		$result = $this->collection('user_table')->findOne(['username' => $username]);

		return is_null($result) ? false : $result;
	}

	public function setUser($username, $password, $firstName = null, $lastName = null)
	{
		if ($this->getUser($username)) {
			$this->collection('user_table')->update(
				['username' => $username],
				[
					'$set' => [
						'password' => $password,
						'first_name' => $firstName,
						'last_name' => $lastName
					]
				]
			);
		} else {
			$user = [
				'username' => $username,
				'password' => $password,
				'first_name' => $firstName,
				'last_name' => $lastName
			];
			$this->collection('user_table')->insert($user);
		}

		return true;
	}

	public function getClientKey($client_id, $subject)
	{
		$result = $this->collection('jwt_table')->findOne([
			'$or' => [
				[
					'client_id' => $client_id,
					'subject' => $subject],
				[
					'client_id' => new \MongoId($client_id),
					'subject' => $subject
				]
			]
		]);

		return is_null($result) ? false : $result['key'];
	}

	public function getClientScope($client_id)
	{
		if (!$clientDetails = $this->getClientDetails($client_id)) {
			return false;
		}

		if (isset($clientDetails['scope'])) {
			return $clientDetails['scope'];
		}

		return null;
	}

	public function getJti($client_id, $subject, $audience, $expiration, $jti)
	{
		//TODO: Needs mongodb implementation.
		//throw new \Exception('getJti() for the MongoDB driver is currently unimplemented.');
	}

	public function setJti($client_id, $subject, $audience, $expiration, $jti)
	{
		//TODO: Needs mongodb implementation.
		//throw new \Exception('setJti() for the MongoDB driver is currently unimplemented.');
	}

	/***** @TODO need tests ****** */
	/**
	 * @param null $client_id
	 * @return bool
	 */
	public function getPublicKey($client_id = null)
	{
		if (!$result = $this->collection('public_key_table')->findOne([
			'$or' => [
				['client_id' => $client_id],
				//['client_id' => new \MongoId($client_id)]
			]
		])
		) {
			return false;
		}

		return $result['public_key'];
	}

	public function getPrivateKey($client_id = null)
	{
		if (!$result = $this->collection('public_key_table')->findOne([
			'$or' => [
				['client_id' => $client_id],
				//['client_id' => new \MongoId($client_id)]
			]
		])
		) {
			return false;
		}

		return $result['private_key'];
	}

	/**
	 * SELECT count(scope) as count FROM %s WHERE scope IN (%s)', 'scope_table', $whereIn
	 * @param $scope
	 * @return bool
	 */
	public function scopeExists($scope)
	{
		$scope = explode(' ', $scope);

		if (!$result = $this->collection('scope_table')->count(['scope' => ['$in' => $scope]])) {
			return false;
		}

		return $result == count($scope);
	}

	public function getEncryptionAlgorithm($client_id = null)
	{
//		$stmt = $this->db->prepare($sql = sprintf('SELECT encryption_algorithm FROM %s WHERE client_id=:client_id OR client_id IS NULL ORDER BY client_id IS NOT NULL DESC', $this->config['public_key_table']));
//
//		$stmt->execute(compact('client_id'));
//		if ($result = $stmt->fetch(\PDO::FETCH_ASSOC)) {
//			return $result['encryption_algorithm'];
//		}
//
//		return 'RS256';
		//TODO: Needs mongodb implementation.
		throw new \Exception('getEncryptionAlgorithm() for the MongoDB driver is currently unimplemented.');
	}

	public function getDefaultScope($client_id = null)
	{
//		$stmt = $this->db->prepare(sprintf('SELECT scope FROM %s WHERE is_default=:is_default', $this->config['scope_table']));
//		$stmt->execute(array('is_default' => true));
//
//		if ($result = $stmt->fetchAll(\PDO::FETCH_ASSOC)) {
//			$defaultScope = array_map(function ($row) {
//				return $row['scope'];
//			}, $result);
//
//			return implode(' ', $defaultScope);
//		}
//
//		return null;

		//TODO: Needs mongodb implementation.
		//throw new \Exception('getDefaultScope() for the MongoDB driver is currently unimplemented.');
	}
}
