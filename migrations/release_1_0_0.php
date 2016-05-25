<?php
/**
 *
 * Xiti marker extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2013 phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace Aurelienazerty\xitiHttps\migrations;

class release_1_0_0 extends \phpbb\db\migration\migration {
	
	public function effectively_installed() {
		return !empty($this->config['xiti_id']);
	}

	public function update_data() {
		return array(
			array(
				'config.add', array('xiti_id', '')
			),
			array(
				'config.add', array('xiti_prefix', 'forum--')
			)
		);
	}
}
