<?php
/**
 * Microweber auto provision plesk plugin
 * Author: Bozhidar Slaveykov
 * @email: info@microweber.com
 * Copyright: Microweber CMS
 */

class Modules_Microweber_MarketplaceConnector
{
	/**
	 * Package manager urls
	 *
	 * @var array
	 */
	public $package_urls = [ 
		'https://packages.microweberapi.com/packages.json'
	];

	/**
	 * Set WHMCS Url
	 * @var bool
	 */
	public $whmcs_url = false;

    public $licenses = [];

    public function set_license($license)
    {
        $this->licenses = $license;
    }
    
    public function add_license($license)
    {
        $this->licenses[] = $license;
    }

    public function set_whmcs_url($url) {
		if (!empty($url)) {
			$this->whmcs_url = $url;
			$this->update_package_urls();
		}
	}
	
	public function update_package_urls() {
		
		$whmcsUrl = $this->whmcs_url . '/index.php?m=microweber_addon&function=get_package_manager_urls';
		$whmcsPackageUrls = $this->_get_content_from_url($whmcsUrl);
		$whmcsPackageUrls = json_decode($whmcsPackageUrls, TRUE);
		if (is_array($whmcsPackageUrls) && !empty($whmcsPackageUrls)) {
			$this->set_package_urls($whmcsPackageUrls);
		}
	}
	
	public function add_package_urls($urls) {
		if (is_array($urls) && !empty($urls)) {
			foreach($urls as $url) {
				$this->add_package_url($url);
			}
		}
	}
	
	public function set_package_urls($urls) {
		if (is_array($urls) && !empty($urls)) {
			$this->package_urls = [];
			foreach($urls as $url) {
				$this->add_package_url($url);
			}
		}
	}
	
	public function add_package_url($originalUrl) {
		$url = trim($originalUrl);
		$this->package_urls[] = $url; 
	}
	
	
	/**
	 * Get package urls
	 * @return string[]
	 */
	public function get_packages_urls()
	{
		return $this->package_urls;
	}
	
	/**
	 * Get available packages
	 *
	 * @return array[]|unknown
	 */
	public function get_packages()
	{
		$allowed_package_types = array(
			'microweber-template',
			'microweber-module'
		);
		$return = array();
		$packages = array();
		$packages_by_type = array();
		if ($this->package_urls) {
			foreach ($this->package_urls as $url) {
				$package_manager_resp = $this->_get_content_from_url($url);
				$package_manager_resp = @json_decode($package_manager_resp, true);
				if ($package_manager_resp and isset($package_manager_resp['packages']) and is_array($package_manager_resp['packages'])) {
					$packages = array_merge($packages, $package_manager_resp['packages']);
				}
			}
		}


		if ($packages) {
			foreach ($packages as $pk => $package) {
				$version_type = false;
				$package_item = $package;
				$last_item = array_pop($package_item);
				if (isset($last_item['type'])) {
					$version_type = $last_item['type'];
					$package['latest_version'] = $last_item;
				}
				if ($version_type and in_array($version_type, $allowed_package_types)) {
					$return[$pk] = $package;
					if (! isset($packages_by_type[$version_type])) {
						$packages_by_type[$version_type] = array();
					}
					$packages_by_type[$version_type][$pk] = $package;
				}
			}
		}
		return $packages_by_type;
	}
	
	/**
	 * Get available templates
	 *
	 * @return boolean[]|unknown[]
	 */
	public function get_templates()
	{
		$templates = $this->get_packages();

		$return = array();
		if ($templates and isset($templates["microweber-template"])) {
			foreach ($templates["microweber-template"] as $pk => $template) {
                $return[$pk] = $template;
			}
		}
		
		return $return;
	}
	
	public function get_templates_download_urls()
	{
		$download_urls = [];
		
		$templates = $this->get_templates();

		if (is_array($templates) && !empty($templates)) {
			foreach ($templates as $template) {
				if (isset($template['latest_version'])) {

                    if (isset($template['latest_version']['dist']['type']) && $template['latest_version']['dist']['type'] == 'license_key') {
                        continue;
                    }

					$download_urls[] = [
						'version'=>$template['latest_version']['version'],
						'name'=>$template['latest_version']['name'],
						'target_dir'=>$template['latest_version']['target-dir'],
						'download_url'=>$template['latest_version']['dist']['url']
					];
					
				}
			}
		}
		
		return $download_urls;
	}

    public function get_modules_download_urls()
    {

        $download_urls = [];
        $packages = $this->get_packages();
        if (!empty($packages)) {
            foreach ($packages as $packageName=>$packageVersions) {
                foreach ($packageVersions as $packageVersion) {
                    if (isset($packageVersion['latest_version'])) {

                        if (isset($packageVersion['latest_version']['dist']['type']) && $packageVersion['latest_version']['dist']['type'] == 'license_key') {
                            continue;
                        }

                        $download_urls[] = [
                            'version'=>$packageVersion['latest_version']['version'],
                            'name'=>$packageVersion['latest_version']['name'],
                            'target_dir'=>$packageVersion['latest_version']['target-dir'],
                            'download_url'=>$packageVersion['latest_version']['dist']['url']
                        ];

                    }
                }
            }
        }

        return $download_urls;
    }

    /**
     * Get content from url
     * @param unknown $url
     * @return unknown
     */
    private function _get_content_from_url($url)
    {
        if (in_array('curl', get_loaded_extensions())) {
            $ch = curl_init();

            $headers = [];
            if (defined('MW_VERSION')) {
                $headers[] = "MW_VERSION: " . MW_VERSION;
            }

            if (!empty($this->licenses)) {
                $headers[] = "Authorization: Basic " . base64_encode('license:' . base64_encode(json_encode($this->licenses)));
            }

            $opts = [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_POSTFIELDS => "",
            ];
            if (!empty($headers)) {
                $opts[CURLOPT_HTTPHEADER] = $headers;
            }

            curl_setopt_array($ch, $opts);

            $data = curl_exec($ch);
            
            curl_close($ch);
            return $data;
        } else {
            return @file_get_contents($url);
        }
    }
}
