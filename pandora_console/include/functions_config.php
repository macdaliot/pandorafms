<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

/**
 * @package Include
 * @subpackage Config
 */

/**
 * Creates a single config value in the database.
 * 
 * @param string Config token to create.
 * @param string Value to set.
 *
 * @return bool Config id if success. False on failure.
 */
function config_create_value ($token, $value) {
	return db_process_sql_insert ('tconfig',
		array ('value' => $value,
			'token' => $token));
}

/**
 * Update a single config value in the database.
 * 
 * If the config token doesn't exists, it's created.
 * 
 * @param string Config token to update.
 * @param string New value to set.
 *
 * @return bool True if success. False on failure.
 */
function config_update_value ($token, $value) {
	global $config;
	
	if ($token == 'list_ACL_IPs_for_API') {
		$value = str_replace(array("\r\n", "\r", "\n"), ";", io_safe_output($value));
	}
	
	if (!isset ($config[$token])) {
		$config[$token] = $value;
		return (bool) config_create_value ($token, $value);
	}
	
	/* If it has not changed */
	if ($config[$token] == $value)
		return true;
	
	$config[$token] = $value;
	
	return (bool) db_process_sql_update ('tconfig', 
		array ('value' => $value),
		array ('token' => $token));
}

/**
 * Updates all config values in case setup page was invoked 
 */
function config_update_config () {
	global $config;
	
	/* If user is not even log it, don't try this */
	if (! isset ($config['id_user']))
		return false;
	
	if (! check_acl ($config['id_user'], 0, "PM") && ! is_user_admin ($config['id_user']))
		return false;
	
	$update_config = (bool) get_parameter ('update_config');
	
	if ($update_config) {
		db_pandora_audit("Setup", "Setup has changed");
	}
	else {
		return false;
	}
	
	$sec2 = get_parameter_get('sec2');
	switch($sec2) {
		case 'godmode/setup/setup':
			$section_setup = get_parameter ('section');
			//////// MAIN SETUP
			// Setup now is divided in different tabs
			switch ($section_setup) {
				case 'general':
					config_update_value ('language', (string) get_parameter ('language'));
					config_update_value ('remote_config', (string) get_parameter ('remote_config'));
					config_update_value ('loginhash_pwd', (string) get_parameter ('loginhash_pwd'));
					config_update_value ('timesource', (string) get_parameter ('timesource'));
					config_update_value ('autoupdate', (bool) get_parameter ('autoupdate'));
					config_update_value ('https', (bool) get_parameter ('https'));
					config_update_value ('attachment_store', (string) get_parameter ('attachment_store'));
					config_update_value ('list_ACL_IPs_for_API', (string) get_parameter('list_ACL_IPs_for_API'));
					config_update_value ('api_password', get_parameter('api_password'));
					config_update_value ('activate_gis', (bool) get_parameter ('activate_gis'));
					config_update_value ('integria_enabled', get_parameter ('integria_enabled'));
					config_update_value ('integria_inventory', get_parameter ('integria_inventory'));
					config_update_value ('integria_api_password', get_parameter ('integria_api_password'));
					config_update_value ('integria_url', get_parameter ('integria_url'));
					config_update_value ('activate_netflow', (bool) get_parameter ('activate_netflow'));
					$timezone = (string) get_parameter ('timezone');
					if ($timezone != "") {
						config_update_value ('timezone', $timezone);
					}
					config_update_value ('sound_alert', get_parameter('sound_alert'));
					config_update_value ('sound_critical', get_parameter('sound_critical'));
					config_update_value ('sound_warning', get_parameter('sound_warning'));
					# Update of Pandora FMS license 
					$update_manager_installed = db_get_value('value', 'tconfig', 'token', 'update_manager_installed');
					
					if ($update_manager_installed == 1) {
						$license_info_key = get_parameter('license_info_key', '');
						if (!empty($license_info_key)) {
							$values = array("value" => $license_info_key);
							$where = array("key" => 'customer_key');
							$update_manage_settings_result = db_process_sql_update('tupdate_settings', $values, $where);
						}
					}
					config_update_value ('public_url', get_parameter('public_url'));
					config_update_value ('referer_security', get_parameter('referer_security'));
					config_update_value ('event_storm_protection', get_parameter('event_storm_protection'));
					config_update_value ('command_snapshot', get_parameter('command_snapshot'));
					break;
				case 'enterprise':
					if (isset($config['enterprise_installed']) && $config['enterprise_installed'] == 1) {
						config_update_value ('trap2agent', (string) get_parameter ('trap2agent'));
						config_update_value ('acl_enterprise', get_parameter ('acl_enterprise'));
						config_update_value ('metaconsole', get_parameter ('metaconsole'));
						config_update_value ('collection_max_size', get_parameter('collection_max_size'));
						config_update_value ('event_replication', (int)get_parameter('event_replication'));
						if ((int)get_parameter('event_replication') == 1) {
							config_update_value ('replication_interval', (int)get_parameter('replication_interval'));
							config_update_value ('replication_dbhost', (string)get_parameter('replication_dbhost'));
							config_update_value ('replication_dbname', (string)get_parameter('replication_dbname'));
							config_update_value ('replication_dbuser', (string)get_parameter('replication_dbuser'));
							config_update_value ('replication_dbpass', (string)get_parameter('replication_dbpass'));
							config_update_value ('replication_dbport', (string)get_parameter('replication_dbport'));
							config_update_value ('replication_mode', (string)get_parameter('replication_mode'));
						}
						config_update_value ('log_collector', (bool)get_parameter('log_collector'));
						
						$inventory_changes_blacklist = get_parameter('inventory_changes_blacklist', array());
						config_update_value ('inventory_changes_blacklist', implode(',',$inventory_changes_blacklist));
						
					}
					break;
				case 'pass':
					if (isset($config['enterprise_installed']) && $config['enterprise_installed'] == 1) {
						config_update_value ('enable_pass_policy', get_parameter('enable_pass_policy'));
						config_update_value ('pass_size', get_parameter('pass_size'));
						config_update_value ('pass_expire', get_parameter('pass_expire'));
						config_update_value ('first_login',  get_parameter('first_login'));
						config_update_value ('mins_fail_pass', get_parameter('mins_fail_pass'));
						config_update_value ('number_attempts', get_parameter('number_attempts'));
						config_update_value ('pass_needs_numbers', get_parameter('pass_needs_numbers'));
						config_update_value ('pass_needs_symbols', get_parameter('pass_needs_symbols'));
						config_update_value ('enable_pass_policy_admin', get_parameter('enable_pass_policy_admin'));
						config_update_value ('enable_pass_history', get_parameter('enable_pass_history'));
						config_update_value ('compare_pass', get_parameter('compare_pass'));
					}
					break;
				case 'auth':
					//////// AUTHENTICATION SETUP
					config_update_value ('auth', get_parameter ('auth'));
					config_update_value ('autocreate_remote_users', get_parameter ('autocreate_remote_users'));
					config_update_value ('default_remote_profile', get_parameter ('default_remote_profile'));
					config_update_value ('default_remote_group', get_parameter ('default_remote_group'));
					config_update_value ('autocreate_blacklist', get_parameter ('autocreate_blacklist'));
					
					config_update_value ('ad_server', get_parameter ('ad_server'));
					config_update_value ('ad_port', get_parameter ('ad_port'));
					config_update_value ('ad_start_tls', get_parameter ('ad_start_tls'));
					config_update_value ('ad_domain', get_parameter ('ad_domain'));
					
					config_update_value ('ldap_server', get_parameter ('ldap_server'));
					config_update_value ('ldap_port', get_parameter ('ldap_port'));
					config_update_value ('ldap_version', get_parameter ('ldap_version'));
					config_update_value ('ldap_start_tls', get_parameter ('ldap_start_tls'));
					config_update_value ('ldap_base_dn', get_parameter ('ldap_base_dn'));
					config_update_value ('ldap_login_attr', get_parameter ('ldap_login_attr'));
					
					config_update_value ('rpandora_server', get_parameter ('rpandora_server'));
					config_update_value ('rpandora_port', get_parameter ('rpandora_port'));
					config_update_value ('rpandora_dbname', get_parameter ('rpandora_dbname'));
					config_update_value ('rpandora_user', get_parameter ('rpandora_user'));
					config_update_value ('rpandora_pass', get_parameter ('rpandora_pass'));
					
					config_update_value ('rbabel_server', get_parameter ('rbabel_server'));
					config_update_value ('rbabel_port', get_parameter ('rbabel_port'));
					config_update_value ('rbabel_dbname', get_parameter ('rbabel_dbname'));
					config_update_value ('rbabel_user', get_parameter ('rbabel_user'));
					config_update_value ('rbabel_pass', get_parameter ('rbabel_pass'));
					
					config_update_value ('rintegria_server', get_parameter ('rintegria_server'));
					config_update_value ('rintegria_port', get_parameter ('rintegria_port'));
					config_update_value ('rintegria_dbname', get_parameter ('rintegria_dbname'));
					config_update_value ('rintegria_user', get_parameter ('rintegria_user'));
					config_update_value ('rintegria_pass', get_parameter ('rintegria_pass'));
					/////////////
					break;
				case 'perf':
					//////// PERFORMANCE SETUP
					config_update_value ('event_purge', get_parameter ('event_purge'));
					$check_metaconsole_events_history = get_parameter ('metaconsole_events_history', -1);
					if ($check_metaconsole_events_history != -1)	
						config_update_value ('metaconsole_events_history', get_parameter ('metaconsole_events_history'));
					config_update_value ('trap_purge', get_parameter ('trap_purge'));
					config_update_value ('string_purge', get_parameter ('string_purge'));
					config_update_value ('audit_purge', get_parameter ('audit_purge'));
					config_update_value ('gis_purge', get_parameter ('gis_purge'));
					config_update_value ('days_purge', (int) get_parameter ('days_purge'));
					config_update_value ('days_delete_unknown', (int) get_parameter ('days_delete_unknown'));
					config_update_value ('days_compact', (int) get_parameter ('days_compact'));
					config_update_value ('step_compact', (int) get_parameter ('step_compact'));
					config_update_value ('sla_period', (int) get_parameter ('sla_period'));
					config_update_value ('event_view_hr', (int) get_parameter ('event_view_hr'));
					config_update_value ('realtimestats', get_parameter ('realtimestats'));
					config_update_value ('stats_interval', get_parameter ('stats_interval'));
					config_update_value ('agentaccess', (int) get_parameter ('agentaccess'));
					config_update_value ('compact_header', (bool) get_parameter ('compact_header'));
					config_update_value ('num_files_attachment', (int) get_parameter ('num_files_attachment'));
					/////////////
					break;
					
				case 'vis':
					//////// VISUAL STYLES SETUP
					config_update_value ('date_format', (string) get_parameter ('date_format'));
					config_update_value ('prominent_time', (string) get_parameter ('prominent_time'));
					config_update_value ('graph_color1', (string) get_parameter ('graph_color1'));
					config_update_value ('graph_color2', (string) get_parameter ('graph_color2'));
					config_update_value ('graph_color3', (string) get_parameter ('graph_color3'));
					config_update_value ('graph_res', (int) get_parameter ('graph_res'));
					$style = (string) get_parameter ('style');
					if ($style != $config['style'])
						$style = substr ($style, 0, strlen ($style) - 4);
					config_update_value ('style', $style);
					config_update_value ('block_size', (int) get_parameter ('block_size'));
					config_update_value ('round_corner', (bool) get_parameter ('round_corner'));
					config_update_value ('status_images_set', (string) get_parameter ('status_images_set'));
					config_update_value ('fontpath', (string) get_parameter ('fontpath'));
					config_update_value ('font_size', get_parameter('font_size'));
					config_update_value ('flash_charts', (bool) get_parameter ('flash_charts'));
					config_update_value ('custom_logo', (string) get_parameter ('custom_logo'));
					config_update_value ('enable_refr', get_parameter('enable_refr'));
					config_update_value ('refr', get_parameter('refr'));
					config_update_value ('vc_refr', get_parameter('vc_refr'));
					config_update_value ('agent_size_text_small', get_parameter('agent_size_text_small'));
					config_update_value ('agent_size_text_medium', get_parameter('agent_size_text_medium'));
					config_update_value ('module_size_text_small', get_parameter('module_size_text_small'));
					config_update_value ('module_size_text_medium', get_parameter('module_size_text_medium'));
					config_update_value ('description_size_text', get_parameter('description_size_text'));
					config_update_value ('item_title_size_text', get_parameter('item_title_size_text'));
					config_update_value ('gis_label', get_parameter ('gis_label'));
					config_update_value ('gis_default_icon', get_parameter ('gis_default_icon'));
					
					$interval_values = get_parameter ('interval_values');
					
					// Add new interval value if is provided
					$interval_value = (float) get_parameter ('interval_value', 0);
					
					if ($interval_value > 0) {
						$interval_unit = (int) get_parameter ('interval_unit');
						$new_interval = $interval_value * $interval_unit;
						
						if ($interval_values === '') {
							$interval_values = $new_interval;
						}
						else {
							$interval_values_array = explode(',',$interval_values);
							if(!in_array($new_interval, $interval_values_array)) {
								$interval_values_array[] = $new_interval;
								$interval_values = implode(',',$interval_values_array);
							}
						}
					}
					
					// Delete interval value if is required
					$interval_to_delete = (float) get_parameter('interval_to_delete');
					if ($interval_to_delete > 0) {
						$interval_values_array = explode(',',$interval_values);
						foreach ($interval_values_array as $k => $iva) {
							if ($interval_to_delete == $iva) {
								unset($interval_values_array[$k]);
							}
						}
						$interval_values = implode(',',$interval_values_array);
					}
					
				config_update_value ('interval_values', $interval_values);
				
				break;
			case 'net':
				config_update_value ('netflow_path', get_parameter ('netflow_path'));
				config_update_value ('netflow_interval', (int)get_parameter ('netflow_interval'));
				config_update_value ('netflow_daemon', get_parameter ('netflow_daemon'));
				config_update_value ('netflow_nfdump', get_parameter ('netflow_nfdump'));
				config_update_value ('netflow_nfexpire', get_parameter ('netflow_nfexpire'));
				config_update_value ('netflow_max_resolution', (int)get_parameter ('netflow_max_resolution'));
				config_update_value ('netflow_disable_custom_lvfilters', get_parameter ('netflow_disable_custom_lvfilters'));
				config_update_value ('netflow_max_lifetime', (int) get_parameter ('netflow_max_lifetime'));
				break;
			case 'log':
				config_update_value ('log_dir', get_parameter('log_dir'));
				config_update_value ('log_max_lifetime', (int)get_parameter('log_max_lifetime'));
				break;
			
		}
	
	
	}
	
	enterprise_include_once('include/functions_policies.php');
	$enterprise = enterprise_include_once ('include/functions_skins.php');
	if ($enterprise !== ENTERPRISE_NOT_HOOK) {
		$config['relative_path'] = get_parameter('relative_path', $config['relative_path']);
	}
}

/**
 * Process config variables
 */
function config_process_config () {
	global $config;
	
	$configs = db_get_all_rows_in_table ('tconfig');
	
	if (empty ($configs)) {
		include ($config["homedir"]."/general/error_emptyconfig.php");
		exit;
	}
	
	/* Compatibility fix */
	foreach ($configs as $c) {
		$config[$c['token']] = $c['value'];
	}
	
	if (!isset ($config['language'])) {
		config_update_value ('language', 'en');
	}
	
	if (isset ($config['homeurl']) && $config['homeurl'][0] != '/') {
		$config['homeurl'] = '/'.$config['homeurl'];
	}
	
	if (!isset ($config['date_format'])) {
		config_update_value ('date_format', 'F j, Y, g:i a');
	}
	
	if (!isset ($config['event_view_hr'])) {
		config_update_value ('event_view_hr', 8);
	}
	
	if (!isset ($config['loginhash_pwd'])) {
		config_update_value ('loginhash_pwd', rand (0, 1000) * rand (0, 1000)."pandorahash");
	}
	
	if (!isset ($config["trap2agent"])) {
		config_update_value ('trap2agent', 0);
	}
	
	if (!isset ($config["sla_period"]) || empty ($config["sla_period"])) {
		config_update_value ('sla_period', SECONDS_1WEEK);
	}
	
	if (!isset ($config["prominent_time"])) {
		// Prominent time tells us what to show prominently when a timestamp is
		// displayed. The comparation (... days ago) or the timestamp (full date)
		config_update_value ('prominent_time', 'comparation');
	}
	
	if (!isset ($config["timesource"])) {
		// Timesource says where time comes from (system or mysql)
		config_update_value ('timesource', 'system');
	}
	
	if (!isset ($config["https"])) {
		// Sets whether or not we want to enforce https. We don't want to go to a
		// potentially unexisting config by default
		config_update_value ('https', false);
	}
	
	if (!isset ($config["compact_header"])) {
		config_update_value ('compact_header', false);
	}
	
	if (!isset ($config["num_files_attachment"])) {
		config_update_value ('num_files_attachment', 100);
	}
	
	if (!isset ($config['status_images_set'])) {
		config_update_value ('status_images_set', 'default');
	}
	
	// Load user session
	if (isset ($_SESSION['id_usuario']))
		$config["id_user"] = $_SESSION["id_usuario"];
	
	if (!isset ($config["round_corner"])) {
		config_update_value ('round_corner', false);
	}
	
	if (!isset ($config["agentaccess"])) {
		config_update_value ('agentaccess', true);
	}
	
	if (!isset ($config["timezone"])) {
		config_update_value ('timezone', "Europe/Berlin");
	}
	
	if (!isset ($config["stats_interval"])) {
		config_update_value ('stats_interval', SECONDS_5MINUTES);
	}
	
	if (!isset ($config["realtimestats"])) {
		config_update_value ('realtimestats', 1);
	}
	
	if (!isset ($config["event_purge"])) {
		config_update_value ('event_purge', 15);
	}
	
	if (!isset ($config["metaconsole_events_history"])) {
		config_update_value ('metaconsole_events_history', 0);
	}
	
	if (!isset ($config["trap_purge"])) {
		config_update_value ('trap_purge', 7);
	}
	
	if (!isset ($config["string_purge"])) {
		config_update_value ('string_purge', 14);
	}
	
	if (!isset ($config["audit_purge"])) {
		config_update_value ('audit_purge', 30);
	}
	
	if (!isset ($config["acl_enterprise"])) {
		config_update_value ('acl_enterprise', 0);
	}
	
	if (!isset ($config["metaconsole"])) {
		config_update_value ('metaconsole', 0);
	}
	
	if (!isset ($config["gis_purge"])) {
		config_update_value ('gis_purge', 7);
	}
	
	if (!isset ($config["collection_max_size"])) {
		config_update_value ('collection_max_size', 1000000);
	}
	
	if (!isset ($config["event_replication"])) {
		config_update_value ('event_replication', 0);
	}
	
	if (!isset ($config["replication_interval"])) {
		config_update_value ('replication_interval', 120);
	}
	
	if (!isset ($config["replication_dbhost"])) {
		config_update_value ('replication_dbhost', "");
	}
	
	if (!isset ($config["replication_dbname"])) {
		config_update_value ('replication_dbname', "");
	}
	
	if (!isset ($config["replication_dbuser"])) {
		config_update_value ('replication_dbuser', "");
	}
	
	if (!isset ($config["replication_dbpass"])) {
		config_update_value ('replication_dbpass', "");
	}
	
	if (!isset ($config["replication_dbport"])) {
		config_update_value ('replication_dbport', "");
	}
	
	if (!isset ($config["replication_mode"])) {
		config_update_value ('replication_mode', "only_validated");
	}
	
	if (!isset ($config["log_collector"])) {
		config_update_value ('log_collector', 0);
	}
	
	if (!isset ($config["log_dir"])) {
		config_update_value ('log_dir', '/var/spool/pandora/data_in/log');
	}
	
	if (!isset ($config["log_max_lifetime"])) {
		config_update_value ('log_max_lifetime', 15);
	}
	
	if (!isset ($config["font_size"])) {
		config_update_value ('font_size', 6);
	}
	
	/* 
	 *Parse the ACL IP list for access API
	 */
	$temp_list_ACL_IPs_for_API = array();
	if (isset($config['list_ACL_IPs_for_API'])) {
		if (!empty($config['list_ACL_IPs_for_API'])) {
			$temp_list_ACL_IPs_for_API = explode(';', $config['list_ACL_IPs_for_API']);
		}
	}
	$config['list_ACL_IPs_for_API'] = $temp_list_ACL_IPs_for_API;
	$keysConfig = array_keys($config);
	
	
	// This is not set here. The first time, when no
	// setup is done, update_manager extension manage it
	// the first time make a conenction and disable itself
	// Not Managed here !
	
	// if (!isset ($config["autoupdate"])){
	// 	config_update_value ('autoupdate', true);
	// }
	
	require_once ($config["homedir"]."/include/auth/mysql.php");
	
	// Next is the directory where "/attachment" directory is placed, to upload files stores. 
	// This MUST be writtable by http server user, and should be in pandora root. 
	// By default, Pandora adds /attachment to this, so by default is the pandora console home dir
	if (!isset ($config['attachment_store'])) {
		config_update_value ( 'attachment_store', $config['homedir'].'/attachment');
	}
	
	if (!isset ($config['fontpath'])) {
		config_update_value ( 'fontpath', $config['homedir'].'/include/fonts/smallfont.ttf');
	}
	
	if (!isset ($config['style'])) {
		config_update_value ( 'style', 'pandora');
	}
	
	if (!isset ($config['flash_charts'])) {
		config_update_value ( 'flash_charts', true);
	}
	
	if (!isset ($config["custom_logo"])) {
		config_update_value ('custom_logo', 'none.png');
	}
	
	if (!isset ($config['history_db_enabled'])) {
		config_update_value ( 'history_db_enabled', false);
	}
	
	if (!isset ($config['history_db_host'])) {
		config_update_value ( 'history_db_host', '');
	}
	
	if (!isset ($config['history_db_port'])) {
		config_update_value ( 'history_db_port', 3306);
	}
	
	if (!isset ($config['history_db_name'])) {
		config_update_value ( 'history_db_name', 'pandora');
	}
	
	if (!isset ($config['history_db_user'])) {
		config_update_value ( 'history_db_user', 'pandora');
	}
	
	if (!isset ($config['history_db_pass'])) {
		config_update_value ( 'history_db_pass', '');
	}
	
	if (!isset ($config['history_db_days'])) {
		config_update_value ( 'history_db_days', 0);
	}
	
	if (!isset ($config['history_db_step'])) {
		config_update_value ( 'history_db_step', 0);
	}
	
	if (!isset ($config['history_db_delay'])) {
		config_update_value ( 'history_db_delay', 0);
	}
	
	if (!isset ($config['activate_gis'])) {
		config_update_value ( 'activate_gis', 0);
	}
	
	if (!isset ($config['activate_netflow'])) {
		config_update_value ( 'activate_netflow', 0);
	}
	
	if (!isset ($config['netflow_path'])) {
		config_update_value ( 'netflow_path', '/var/spool/pandora/data_in/netflow');
	}
	
	if (!isset ($config['netflow_interval'])) {
		config_update_value ( 'netflow_interval', SECONDS_10MINUTES);
	}
	
	if (!isset ($config['netflow_daemon'])) {
		config_update_value ( 'netflow_daemon', '/usr/bin/nfcapd');
	}
	
	if (!isset ($config['netflow_nfdump'])) {
		config_update_value ( 'netflow_nfdump', '/usr/bin/nfdump');
	}
	
	if (!isset ($config['netflow_nfexpire'])) {
		config_update_value ( 'netflow_nfexpire', '/usr/bin/nfexpire');
	}
		
	if (!isset ($config['netflow_max_resolution'])) {
		config_update_value ( 'netflow_max_resolution', '50');
	}
	
	if (!isset ($config['netflow_disable_custom_lvfilters'])) {
		config_update_value('netflow_disable_custom_lvfilters', 100);
	}

	if (!isset ($config['netflow_disable_custom_lvfilters'])) {
		config_update_value ( 'netflow_disable_custom_lvfilters', 0);
	}

	if (!isset ($config['netflow_max_lifetime'])) {
		config_update_value ( 'netflow_max_lifetime', '5');
	}
	
	if (!isset ($config['auth'])) {
		config_update_value ( 'auth', 'mysql');
	}
	
	if (!isset ($config['autocreate_remote_users'])) {
		config_update_value ('autocreate_remote_users', 0);
	}
	
	if (!isset ($config['autocreate_blacklist'])) {
		config_update_value ('autocreate_blacklist', '');
	}
	
	if (!isset ($config['default_remote_profile'])) {
		config_update_value ('default_remote_profile', 0);
	}
	
	if (!isset ($config['default_remote_group'])) {
		config_update_value ('default_remote_group', 0);
	}
	
	if (!isset ($config['ldap_server'])) {
		config_update_value ( 'ldap_server', 'localhost');
	}
	
	if (!isset ($config['ldap_port'])) {
		config_update_value ( 'ldap_port', 389);
	}
	
	if (!isset ($config['ldap_version'])) {
		config_update_value ( 'ldap_version', '3');
	}
	
	if (!isset ($config['ldap_start_tls'])) {
		config_update_value ( 'ldap_start_tls', 0);
	}
	
	if (!isset ($config['ldap_base_dn'])) {
		config_update_value ( 'ldap_base_dn', 'ou=People,dc=edu,dc=example,dc=org');
	}
	
	if (!isset ($config['ldap_login_attr'])) {
		config_update_value ( 'ldap_login_attr', 'uid');
	}
	
	if (!isset ($config['ad_server'])) {
		config_update_value ( 'ad_server', 'localhost');
	}
	
	if (!isset ($config['ad_port'])) {
		config_update_value ( 'ad_port', 389);
	}
	
	if (!isset ($config['ad_start_tls'])) {
		config_update_value ( 'ad_start_tls', 0);
	}
	
	if (!isset ($config['ad_domain'])) {
		config_update_value ( 'ad_domain', '');
	}
	
	if (!isset ($config['rpandora_server'])) {
		config_update_value ( 'rpandora_server', 'localhost');
	}
	
	if (!isset ($config['rpandora_port'])) {
		config_update_value ( 'rpandora_port', 3306);
	}
	
	if (!isset ($config['rpandora_dbname'])) {
		config_update_value ( 'rpandora_dbname', 'pandora');
	}
	
	if (!isset ($config['rpandora_user'])) {
		config_update_value ( 'rpandora_user', 'pandora');
	}
	
	if (!isset ($config['rpandora_pass'])) {
		config_update_value ( 'rpandora_pass', '');
	}
	
	if (!isset ($config['rbabel_server'])) {
		config_update_value ( 'rbabel_server', 'localhost');
	}
	
	if (!isset ($config['rbabel_port'])) {
		config_update_value ( 'rbabel_port', 3306);
	}
	
	if (!isset ($config['rbabel_dbname'])) {
		config_update_value ( 'rbabel_dbname', 'babel');
	}
	
	if (!isset ($config['rbabel_user'])) {
		config_update_value ( 'rbabel_user', 'babel');
	}
	
	if (!isset ($config['rbabel_pass'])) {
		config_update_value ( 'rbabel_pass', '');
	}
	
	if (!isset ($config['rintegria_server'])) {
		config_update_value ( 'rintegria_server', 'localhost');
	}
	
	if (!isset ($config['rintegria_port'])) {
		config_update_value ( 'rintegria_port', 3306);
	}
	
	if (!isset ($config['rintegria_dbname'])) {
		config_update_value ( 'rintegria_dbname', 'integria');
	}
	
	if (!isset ($config['rintegria_user'])) {
		config_update_value ( 'rintegria_user', 'integria');
	}
	
	if (!isset ($config['rintegria_pass'])) {
		config_update_value ( 'rintegria_pass', '');
	}
	
	if (!isset ($config['integria_enabled'])) {
		config_update_value ( 'integria_enabled', '0');
	}
	
	if (!isset ($config['integria_api_password'])) {
		config_update_value ( 'integria_api_password', '');
	}
	
	if (!isset ($config['integria_inventory'])) {
		config_update_value ( 'integria_inventory', '0');
	}
	
	if (!isset ($config['integria_url'])) {
		config_update_value ( 'integria_url', '');
	}
	
	if (!isset ($config['autoupdate'])) {
		config_update_value ( 'autoupdate', 1);
	}
	
	if (!isset ($config['api_password'])) {
		config_update_value( 'api_password', '');
	}
	
	if(defined('METACONSOLE')) {
		if (!isset ($config['show_vc'])) {
			config_update_value('show_vc', 1);
		}
		
		if (!isset ($config['meta_num_elements'])) {
			config_update_value('meta_num_elements', 100);
		}
	}
	
	if (!isset ($config['relative_path']) && (isset ($_POST['nick'])
		|| isset ($config['id_user'])) && isset($config['enterprise_installed'])) {
		
		$isFunctionSkins = enterprise_include_once ('include/functions_skins.php');
		if ($isFunctionSkins !== ENTERPRISE_NOT_HOOK) {
			
			// Try to update user table in order to refresh skin inmediatly
			$is_user_updating = get_parameter("sec2", "");
			
			if ($is_user_updating == 'operation/users/user_edit') {
				$id = get_parameter_get ("id", $config["id_user"]); // ID given as parameter
				$user_info = get_user_info ($id);
				 
				//If current user is editing himself or if the user has UM (User Management) rights on any groups the user is part of AND the authorization scheme allows for users/admins to update info
				if (($config["id_user"] == $id || check_acl ($config["id_user"], users_get_groups ($id), "UM")) && $config["user_can_update_info"]) {
					$view_mode = false;
				} else {
					$view_mode = true;
				}
				
				if (isset ($_GET["modified"]) && !$view_mode) { 
					$upd_info["id_skin"] = get_parameter ("skin", $user_info["id_skin"]);
					$return_update_skin = update_user ($id, $upd_info);
				}
			}
			
			if (isset($config['id_user']))
				$relative_path = enterprise_hook('skins_set_image_skin_path',array($config['id_user']));
			else
				$relative_path = enterprise_hook('skins_set_image_skin_path',array(get_parameter('nick')));
			$config['relative_path'] = $relative_path;
		}
	}
	
	if (!isset ($config['dbtype'])) {
		config_update_value ('dbtype', 'mysql');
	}
	
	if (!isset ($config['enable_refr'])) {
		config_update_value ('enable_refr', 0);
	}
	
	if (!isset ($config['vc_refr'])) {
		config_update_value ('vc_refr', 60);
	}
	
	if (!isset ($config['refr'])) {
		config_update_value ('refr', '');
	}
	
	if (!isset($config['agent_size_text_small'])) {
		config_update_value ('agent_size_text_small', 18);
	}
	
	if (!isset($config['agent_size_text_medium'])) {
		config_update_value ('agent_size_text_medium', 50);
	}
	
	if (!isset($config['module_size_text_small'])) {
		config_update_value ('module_size_text_small', 25);
	}
	
	if (!isset($config['module_size_text_medium'])) {
		config_update_value ('module_size_text_medium', 50);
	}
	
	if (!isset($config['description_size_text'])) {
		config_update_value ('description_size_text', 60);
	}
	
	if (!isset($config['item_title_size_text'])) {
		config_update_value ('item_title_size_text', 45);
	}
	
	if (!isset($config['gis_label'])) {
		config_update_value ('gis_label', 0);
	}
	
	if (!isset($config['gis_default_icon'])) {
		config_update_value ('gis_default_icon', "marker");
	}
	
	if (!isset($config['interval_values'])) {
		config_update_value ('interval_values', "");
	}
	
	if (!isset($config['public_url'])) {
		config_update_value ('public_url', "");
	}
	
	if (!isset($config['referer_security'])) {
		config_update_value ('referer_security', 0);
	}
	
	if (!isset($config['event_storm_protection'])) {
		config_update_value ('event_storm_protection', 0);
	}
	
	if (!isset($config['command_snapshot'])) {
		config_update_value ('command_snapshot', 1);
	}
	/* Finally, check if any value was overwritten in a form */
	config_update_config();
}

function config_check () {
	global $config;
	
	// At this first version I'm passing errors using session variables, because the error management
	// is done by an AJAX request. Better solutions could be implemented in the future :-)
	
	// Check default password for "admin"
	$is_admin = db_get_value('is_admin', 'tusuario', 'id_user', $config['id_user']);
	if ($is_admin) {
		$hashpass = db_get_sql ("SELECT password FROM tusuario WHERE id_user = 'admin'");
		if ($hashpass == "1da7ee7d45b96d0e1f45ee4ee23da560"){
			$config["alert_cnt"]++;
			$_SESSION["alert_msg"] .= ui_print_error_message(
				array('message' => __('Default password for "Admin" user has not been changed.').'</h3>'.'<p>'.__('Please change the default password because is a common vulnerability reported.'),
					'no_close' => true, 'force_style' => 'color: #000000 !important'), '', true);
		}
	}
	
	if (!is_writable ("attachment")) {
		$config["alert_cnt"]++;
		$_SESSION["alert_msg"] .= ui_print_error_message(
			array('message' => __('Attachment directory is not writable by HTTP Server').'</h3>'.'<p>'.__('Please check that the web server has write rights on the {HOMEDIR}/attachment directory'),
				'no_close' => true, 'force_style' => 'color: #000000 !important'), '', true);
	}
	
	// Get remote file dir.
	$remote_config = db_get_value_filter('value', 'tconfig', array('token' => 'remote_config'));
	
	if (enterprise_installed()) {
		if (!is_writable ($remote_config)){
			$config["alert_cnt"]++;
			$_SESSION["alert_msg"] .= ui_print_error_message(
				array('message' => __('Remote configuration directory is not writtable for the console').' - $remote_config',
				'no_close' => true, 'force_style' => 'color: #000000 !important'), '', true);
		}
		
		$remote_config_conf = $remote_config . "/conf";
		if (!is_writable ($remote_config_conf)){
			$config["alert_cnt"]++;
			$_SESSION["alert_msg"] .= ui_print_error_message(
				array('message' => __('Remote configuration directory is not writtable for the console').' - $remote_config',
				'no_close' => true, 'force_style' => 'color: #000000 !important'), '', true);
		}
		
		$remote_config_col = $remote_config . "/collections";
		if (!is_writable ($remote_config_col)){
			$config["alert_cnt"]++;
			$_SESSION["alert_msg"] .= ui_print_error_message(
				array('message' => __('Remote configuration directory is not writtable for the console').' - $remote_config',
				'no_close' => true, 'force_style' => 'color: #000000 !important'), '', true);
		}
	}
	
	// Check attachment directory (too much files?)
	
	$filecount = count(glob($config["homedir"]."/attachment/*"));
	// N temporal files of trash should be enough for most people.
	if ($filecount > $config['num_files_attachment']) {
		$config["alert_cnt"]++;
		$_SESSION["alert_msg"] .= ui_print_error_message(
			array('title' => __('Too much files in your tempora/attachment directory'),
			'message' => __("There are too much files in attachment directory. This is not fatal, but you should consider cleaning up your attachment directory manually"). " ( $filecount ". __("files") . " )",
			'no_close' => true, 'force_style' => 'color: #000000 !important'), '', true);
	}
	
	// Check database maintance
	$db_maintance = db_get_value_filter ('value', 'tconfig', array('token' => 'db_maintance')); 
	$now = date("U");
	
	// First action in order to know if it's a new installation or db maintenance never have been executed 
	$first_action = db_get_value_filter('utimestamp', 'tsesion', array('1 = 1', 'order' => 'id_sesion ASC'));
	$fresh_installation = $now - $first_action;
	
	$resta = $now - $db_maintance;
	// ~ about 50 hr
	if (($resta > 190000 AND $fresh_installation> 190000)){
		$config["alert_cnt"]++;
		$_SESSION["alert_msg"] .= ui_print_error_message(
			array('title' => __("Database maintance problem"),
			'message' => __('Your database is not well maintained. Seems that it have more than 48hr without a proper maintance. Please review Pandora FMS documentation about how to execute this maintance process (pandora_db.pl) and enable it as soon as possible'),
			'no_close' => true, 'force_style' => 'color: #000000 !important'), '', true);
	}
	
	$fontpath = db_get_value_filter('value', 'tconfig', array('token' => 'fontpath'));
	if (($fontpath == "") OR (!file_exists ($fontpath))) {
		$config["alert_cnt"]++;
		$_SESSION["alert_msg"] .= ui_print_error_message(
			array('title' => __("Default font doesnt exist"),
			'message' => __('Your defined font doesnt exist or is not defined. Please check font parameters in your config'),
			'no_close' => true, 'force_style' => 'color: #000000 !important'), '', true);
	}
	
	global $develop_bypass;
	
	if ($develop_bypass == 1){
		$config["alert_cnt"]++;
		$_SESSION["alert_msg"] .= ui_print_error_message(
			array('title' => __("Developer mode is enabled"),
			'message' => __('Your Pandora FMS has the "develop_bypass" mode enabled. This is a developer mode and should be disabled in a production system. This value is written in the main index.php file'),
			'no_close' => true, 'force_style' => 'color: #000000 !important'), '', true);
	}
	
	if (isset($_SESSION['new_update'])) {
		if (!empty($_SESSION['return_installation_open'])) {
			if (!$_SESSION['return_installation_open']['return']) {
				foreach ($_SESSION['return_installation_open']['text'] as $message) {
					$config["alert_cnt"]++;
					$_SESSION["alert_msg"] .= ui_print_error_message(
						array('title' => __("Error first setup Open update"),
						'message' => $message,
						'no_close' => true, 'force_style' => 'color: #000000 !important'), '', true);
				}
			}
		}
		if ($_SESSION['new_update'] == 'new') {
			$config["alert_cnt"]++;
			$_SESSION["alert_msg"] .= ui_print_info_message(
				array('title' => __("New update of Pandora Console"),
				'message' => __('There is a new update please go to menu operation and into extensions <a style="font-weight:bold;" href="index.php?sec=extensions&sec2=extensions/update_manager">go to Update Manager</a> for more details.'),
				'no_close' => true, 'force_style' => 'color: #000000 !important'), '', true);
		}
	}
	
	if (enterprise_installed()) {
		if ($config['update_manager_installed'] == 1) {
			um_db_connect ('mysql', $config['dbhost'], $config['dbuser'],
				$config['dbpass'], $config['dbname'], $config['dbport']);
			
			$settings = um_db_load_settings ();
			
			$result_check_keygen = check_keygen($settings);
			
			if (!empty($result_check_keygen)) {
				$config["alert_cnt"]++;
				$_SESSION["alert_msg"] .= $result_check_keygen;
			}
		}
	}
	else {
		if (isset($config['update_manager_installed'])) {
			if ($config['update_manager_installed'] == 1) {
				require_once("extensions/update_manager/lib/functions.ajax.php");
				
				$result_check_keygen = check_keygen_online();
				
				if (!empty($result_check_keygen)) {
					$config["alert_cnt"]++;
					$_SESSION["alert_msg"] .= $result_check_keygen;
				}
			}
		}
	}
}

?>
