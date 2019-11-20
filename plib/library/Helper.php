<?php
// Copyright 1999-2017. Parallels IP Holdings GmbH.

class Modules_Microweber_Helper
{

	public static function getRandomPassword($length = 16, $complex = false)
	{
		$alphabet = 'ghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

		if ($complex) {
			$alphabet .= '-~!@#%^*()_+,./;:[]{}\|';
		}

		$pass = [];
		$alphaLength = strlen($alphabet) - 1;
		for ($i = 0; $i < $length; $i ++) {
			$n = rand(0, $alphaLength);
			$pass[] = $alphabet[$n];
		}
		return implode($pass);
	}
	
	public static function getJsonFromUrl($url, $postfields = [])
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		
		if (!empty($postfields)) {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
		}
		
		curl_setopt($ch, CURLOPT_TIMEOUT, 20);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		
		$data = curl_exec($ch);
		
		curl_close($ch);
		
		return @json_decode($data, true);
	}
}