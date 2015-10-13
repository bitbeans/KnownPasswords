<?php
 /*
 * This file is part of the Laravel 5 KnownPassword package.
 *
 * (c) 2015 Christian Hermann
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 *
 * @author    	Christian Hermann
 * @package     KnownPasswords
 * @copyright   (c) 2015 Chistian Hermann <c.hermann@bitbeans.de>
 * @link        https://github.com/bitbeans/KnownPasswords
 * @link        https://knownpasswords.org
 */

namespace Bitbeans\KnownPasswords;

use Config;

class KnownPasswords {

	/**#@+
	 * @access private
	 */

	/**
	 * The clients private key.
	 */
	var $_privatekey;

	/**
	 * The clients public key.
	 */
	var $_publickey;

	/**
	 * The servers signature public key.
	 */
	var $_serverSignaturePublicKey;

	/**
	 * The servers signature public key.
	 */
	var $_serverEncryptionPublicKey;

	/**
	 * The API URL.
	 * @var string
	 */
	var $_apiurl;

	/**
	 * Constructor
	 *
	 * Sets up the object
	 * @param    array  $config     The client configuration
	 * @access public
	 */
	public function __construct( $config = array() )
	{
		$this->_privatekey                = (isset($config['publickey']) && !empty($config['publickey'])) ? $privatekey : \Sodium\hex2bin(config('knownpasswords.PRIVATE_KEY'));
		$this->_publickey		          = \Sodium\crypto_sign_publickey_from_secretkey($this->_privatekey);
		$this->_apiurl                    = "https://knownpasswords.org"; //don`t change this value
		$this->_serverSignaturePublicKey  = \Sodium\hex2bin("e1426419742ee9f34831d3deaead88a511dc6fb635e3187427012457031e538a"); //don`t change this value
		$this->_serverEncryptionPublicKey = \Sodium\hex2bin("8609896949b031fb3109e6ed5564801ab6a839c88cc8b159c4d4771513b4564e"); //don`t change this value

		if (!$this->_privatekey)
		{
			throw new \Exception('Check your PRIVATE_KEY');
		}

		$this->test_curl_ssl_support();
	}

	/**
	 * Test if Curl support SSL
	 * Will throw exception if curl was not complied with SSL support
	 */
	private function test_curl_ssl_support()
	{
		if (!($version = curl_version()) || !($version['features'] & CURL_VERSION_SSL))
		{
			throw new \Exception('HTTPS requested while Curl not compiled with SSL');
		}
	}

	/**
	 * Check if a password is known by the knownpassword.org API.
	 *
	 * @param string $password      	The password to check.
	 * @param string $passwordFormat    The format of the given password (Blake2b, Sha512, Cleartext) [Default: Blake2b].
	 * @return mixed               		Exception on error, true if the password is known and false if the password is unknown.
	 * @access public
	 */
	public function checkPassword($password, $passwordFormat = "Blake2b")
	{
		$apiData = array();
		switch ($passwordFormat) {
			case "Blake2b":
				$apiData = array(
					"Blake2b" => $password
				);  
				break;
			case "Sha512":
				$apiData = array(
					"Sha512" => $password
				);
				break;
			case "Cleartext":
				$apiData = array(
					"Cleartext" => $password
				);
				break;
			default:
			   throw new \Exception("Unknown passwordFormat.");
		}
		$nonce = \Sodium\randombytes_buf(24);
		$signature = \Sodium\crypto_sign_detached($nonce, $this->_privatekey);
		$clearJson = json_encode($apiData);  
		$encryptionNonce = \Sodium\randombytes_buf(\Sodium\CRYPTO_BOX_NONCEBYTES);
		$encryptionKeyPair = \Sodium\crypto_box_keypair();
		$encryptionSecretkey = \Sodium\crypto_box_secretkey($encryptionKeyPair);
		$encryptionPublickey = \Sodium\crypto_box_publickey($encryptionKeyPair);
		$encryptionKeyPair = \Sodium\crypto_box_keypair_from_secretkey_and_publickey(
			$encryptionSecretkey,
			$this->_serverEncryptionPublicKey
		);
		$ciphertext = \Sodium\crypto_box(
			$clearJson,
			$encryptionNonce,
			$encryptionKeyPair
		);
		$encryptedApiData = array(
			"PublicKey" => \Sodium\bin2hex($encryptionPublickey),
			"Nonce" => \Sodium\bin2hex($encryptionNonce),
			"Ciphertext" => \Sodium\bin2hex($ciphertext)
		);  
														
		$data_string = json_encode($encryptedApiData);                                                                                   
		$ch = curl_init($this->_apiurl."/checkpassword");                                                                      
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                                   
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);   
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_HEADER, 1);  
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);                                              
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
			'Content-Type: application/json',                                                                                
			'Content-Length: ' . strlen($data_string),
			'User-Agent: ' . 'Laravel 5', 
			'X-Public: '.\Sodium\bin2hex($this->_publickey), 
			'X-Nonce: '.\Sodium\bin2hex($nonce), 
			'X-Signature: '.\Sodium\bin2hex($signature)                                                                
		));  

		if(!$result = curl_exec($ch)) 
		{ 
			throw new \Exception("Request failed");
		} 
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header = substr($result, 0, $header_size);
		$headers = get_headers_from_curl_response($header);
		if ((array_key_exists("http_code", $headers[0])) && (array_key_exists("X-Powered-By", $headers[0])) && (array_key_exists("X-Signature", $headers[0]))) {
			$httpCode = $headers[0]["http_code"];
			$responsePowered = $headers[0]["X-Powered-By"];
			$responseSignature = $headers[0]["X-Signature"];
			$responseNonce = $headers[0]["X-Nonce"];
			if (($httpCode === "HTTP/1.1 200 OK") || ($httpCode === "HTTP/2.0 200 OK")) {
				if ($responsePowered === "bitbeans") {
					// validate the response signature
					if(!\Sodium\crypto_sign_verify_detached(
						\Sodium\hex2bin($responseSignature),
						\Sodium\crypto_generichash(\Sodium\hex2bin($responseNonce), null, 64),
						$this->_serverSignaturePublicKey)) 
					{
						throw new \Exception("Invalid signature");
					}
				}
				else {
					throw new \Exception("Invalid server");
				}
			}
			else {
				throw new \Exception("Invalid response code");
			}
		}
		else {
			throw new \Exception("Invalid header");
		}

		$result = substr($result, $header_size);
		curl_close($ch); 

		$resultJson = json_decode($result);

		$decryptionKeyPair = \Sodium\crypto_box_keypair_from_secretkey_and_publickey(
			$encryptionSecretkey,
			\Sodium\hex2bin($resultJson->{'publicKey'})
		);

		$plaintext = \Sodium\crypto_box_open(
			\Sodium\hex2bin($resultJson->{'ciphertext'}),
			\Sodium\hex2bin($resultJson->{'nonce'}),
			$decryptionKeyPair
		);
		if ($plaintext === FALSE) {
			throw new \Exception("Malformed message or invalid MAC");
		}

		$plaintextJson = json_decode($plaintext);

		return !$plaintextJson->{'FoundPassword'};
	}
}