<?php
/**
 * @author Bozhidar Slaveykov
 * @email bobi@microweber.com
 * @site microweber.com
 */

class Modules_Microweber_Helper
{
	/**
	 * Get random password
	 *
	 * @param number $length
	 * @param boolean $complex
	 * @return string
	 */
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
}