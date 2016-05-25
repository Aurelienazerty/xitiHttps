<?php
/**
*
* Xiti marker.
*
* @copyright (c) 2014 phpBB Limited <https://www.phpbb.com>
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/

namespace Aurelienazerty\xitiHttps\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
* Event listener
*/
class listener implements EventSubscriberInterface {
	
	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\user */
	protected $user;
	
	/** @var string */
	protected $pageTitle;

	/**
	* Constructor
	*
	* @param \phpbb\config\config        $config             Config object
	* @param \phpbb\template\template    $template           Template object
	* @param \phpbb\user                 $user               User object
	* @access public
	*/
	public function __construct(\phpbb\config\config $config, \phpbb\user $user) {
		$this->config = $config;
		$this->user = $user;
	}

	/**
	* Assign functions defined in this class to event listeners in the core
	*
	* @return array
	* @static
	* @access public
	*/
	static public function getSubscribedEvents() {
		return array(
			'core.acp_board_config_edit_add'	=> 'add_xiti_configs',
			'core.page_header'					=> 'load_xiti_analytics',
			'core.page_footer'					=> 'ping_xiti',
		);
	}

	/**
	* Load Google Analytics js code
	*
	* @return null
	* @access public
	*/
	public function load_xiti_analytics($events) {
		$this->pageTitle = $events['page_title'];
	}
	
	public function ping_xiti() {
		$xitiId = $this->config['xiti_id'];
		$tagXiti = $this->config['xiti_prefix'] . $this->_xtTraiter($this->pageTitle);
		
		if (isset($_SERVER['HTTP_REFERER'])) {
			$referer = $_SERVER['HTTP_REFERER'];
		} else {
			$referer = '';
		}
		$part = array('host' => $_SERVER['REMOTE_ADDR']);
		$urlXiti = "http://logv27.xiti.com/bcg.xiti?s=" . $xitiId . "&p=". $tagXiti . "&hl" . date('Gxixs') ."&ref=" . $referer;
		$this->_post_without_wait($urlXiti, $part);
	}
	
	private function _post_without_wait($url, $params = array()) {
		$post_params = array();
		foreach ($params as $key => &$val) {
			if (is_array($val)) {
				$val = implode(',', $val);
			}
			$post_params[] = $key.'='.urlencode($val);
		}
		$post_string = implode('&', $post_params);

		$parts=parse_url($url);
		
		if (isset($params['host'])) {
			$host = $params['host'];
		} else {
			$host = $parts['host'];
		}

		$fp = fsockopen($parts['host'],
				isset($parts['port'])?$parts['port']:80,
				$errno, $errstr, 30);

		$out = "POST ".$parts['path']." HTTP/1.1\r\n";
		$out.= "Host: ".$parts['host']."\r\n";
		if (isset($_SERVER['HTTP_USER_AGENT']) && !empty($_SERVER['HTTP_USER_AGENT'])) {
			$out .= 'User-Agent: ' . $_SERVER['HTTP_USER_AGENT'];
		}
		if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && !empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
			$out .= 'Accept-Language: ' . $_SERVER['HTTP_ACCEPT_LANGUAGE'];
		}
		if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && !empty($_SERVER['HTTP_ACCEPT_ENCODING'])) {
			$out .= 'Accept-Encoding: ' . $_SERVER['HTTP_ACCEPT_ENCODING'];
		}
		if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
			$out .= 'Referer: ' . $_SERVER['HTTP_REFERER'];
		}
		$out.= "Content-Type: application/x-www-form-urlencoded\r\n";
		$out.= "Content-Length: ".strlen($post_string)."\r\n";
		$out.= "Connection: Close\r\n\r\n";
		if (isset($post_string)) {
			$out.= $post_string;
		}

		fwrite($fp, $out);
		fclose($fp);
	}

	/**
	* Add config vars to ACP Board Settings
	*
	* @param object $event The event object
	* @return null
	* @access public
	*/
	public function add_xiti_configs($event) {
		// Load language file
		$this->user->add_lang_ext('Aurelienazerty/xitiHttps', 'xiti_acp');

		// Add a config to the settings mode, after board_timezone
		if ($event['mode'] == 'settings' && isset($event['display_vars']['vars']['board_timezone'])) {
			// Store display_vars event in a local variable
			$display_vars = $event['display_vars'];

			// Define the new config vars
			$config_vars = array(
				'legendXiti' => 'ACP_XITI_TITLE',
				'xiti_id' => array(
					'lang' => 'ACP_XITI_ID',
					'type' => 'text:20:10',
					'validate' 	=> 'string',
					'explain' => true,
				),
				'xiti_prefix' => array(
					'lang' => 'ACP_XITI_PREFIX',
					'type' => 'text:40:20',
					'explain' => true,
				)
			);

			// Add the new config vars after board_timezone in the display_vars config array
			$insert_after = array('after' => 'board_timezone');
			$display_vars['vars'] = phpbb_insert_config_array($display_vars['vars'], $config_vars, $insert_after);
			// Update the display_vars event with the new array
			$event['display_vars'] = $display_vars;
		}
	}
	
	/**
	 * Make page id
	 */
	private function _xtTraiter($nompage) {
		$nompage = strtolower($nompage);
		$nompage = html_entity_decode($nompage, ENT_NOQUOTES, 'UTF-8');
		$search = array ("@[éèêëÊË]@i","@[àâäÂÄ]@i","@[îïÎÏ]@i","@[ûùüÛÜ]@i","@[ôöÔÖ]@i","@[ç]@i","@[ ]@i","@[^a-zA-Z0-9_]@");
		$replace = array ("e","a","i","u","o","c","-","-");
		$nompage = preg_replace($search, $replace, $nompage);
		return $nompage;
	} 
}
