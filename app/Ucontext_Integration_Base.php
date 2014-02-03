<?php

// Copyright 2013 - Summit Media Concepts LLC - http://SummitMediaConcepts.com

class Ucontext_Integration_Base
{

	public static function isValidLicense($force = FALSE)
	{
		$last_datetime = get_option('rlm_last_license_check_'.Ucontext_Base::$name, 0);

		if (strlen(trim(get_option('rlm_license_key_'.Ucontext_Base::$name))) == 32)
		{
			if ($force || $last_datetime < (time() - 86400))
			{
				$package = array(
				'method'		=> 'checkLicense',
				'site_url'		=> site_url(),
				'handle'		=> Ucontext_Base::$name,
				'license_key'	=> get_option('rlm_license_key_'.Ucontext_Base::$name)
				);

				$response = self::requestApi($package);

				if (is_wp_error($response))
				{
					update_option('rlm_notification_'.Ucontext_Base::$name, 'Unable to contact RLM API: '.$response->get_error_message());
				}
				else
				{
					$result = self::decrypt($response['body']);

					if (!(int)$result['valid'])
					{
						update_option('rlm_notification_'.Ucontext_Base::$name, '<div class="ucontext_error"><strong>'.$result['error_title'].'</strong>'.$result['error_desc'].'</div>');
					}
					else
					{
						update_option('rlm_notification_'.Ucontext_Base::$name, '');
					}

					update_option('rlm_license_status_'.Ucontext_Base::$name, (int)$result['valid']);
					update_option('rlm_last_license_check_'.Ucontext_Base::$name, time());

					if (@$result['latest_version'])
					{
						update_option('rlm_version_'.Ucontext_Base::$name, $result['latest_version']);
					}

					return $result['valid'];
				}
			}
			else
			{
				return get_option('rlm_license_status_'.Ucontext_Base::$name, 0);
			}
		}
		else
		{
			return get_option('rlm_license_status_'.Ucontext_Base::$name, 0);
		}
	}

	public static function requestApi($package)
	{
		$args = array(
		'method'		=> 'POST',
		'timeout'		=> 30,
		'redirection'	=> 5,
		'httpversion'	=> '1.0',
		'blocking'		=> true,
		'body'			=> array('data' => self::encrypt($package))
		);

		return wp_remote_post('http://www.ucontext.com/wp-admin/admin-ajax.php?action=rlm_api&rlm_app='.Ucontext_Base::$name, $args);
	}

	public static function encrypt($s_data)
	{
		$s_data = serialize($s_data);

		$result = '';

		for ($i = 0; $i < strlen($s_data); $i++)
		{
			$s_char		= substr($s_data, $i, 1);
			$s_key_char	= substr(Ucontext_Integration::$crypt_key, ($i % strlen(Ucontext_Integration::$crypt_key)) - 1, 1);
			$s_char		= chr(ord($s_char) + ord($s_key_char));

			$result .= $s_char;
		}

		$result = self::encode_base64($result);

		return $result;
	}

	public static function decrypt($s_data)
	{
		$result = '';

		$s_data   = self::decode_base64($s_data);

		for ($i = 0; $i < strlen($s_data); $i++)
		{
			$s_char		= substr($s_data, $i, 1);
			$s_key_char	= substr(Ucontext_Integration::$crypt_key, ($i % strlen(Ucontext_Integration::$crypt_key)) - 1, 1);
			$s_char		= chr(ord($s_char) - ord($s_key_char));

			$result .= $s_char;
		}

		$result = unserialize($result);

		return $result;
	}

	public static function encode_base64($s_data)
	{
		$result = strtr(base64_encode($s_data), '+/', '-_');

		return $result;
	}

	public static function decode_base64($s_data)
	{
		$result = base64_decode(strtr($s_data, '-_', '+/'));

		return $result;
	}
}