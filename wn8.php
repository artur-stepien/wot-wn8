<?php
/**
 * @package     Wargaming.API
 * @version     1.1
 * @author      Artur Stępień (artur.stepien@bestproject.pl)
 * @copyright   Copyright (C) 2015 Artur Stępień, All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

namespace WoT;
	
/**
 * Class processing accounts WN8 stat. This class require Wargaming\API class to work. 
 * You can download latest version from here: https://github.com/artur-stepien/wargaming-papi
 */
class WN8 {
	
	// Wargaming/API instance
	protected $api;
	
	// Contains expectd tank values
	protected $expected_tank_values = array();
	
	// Should application search for info about tanks missing in WN8 calculation
	protected $search_missing_tanks = false;
	
	// Accurate calculation (removes OP and missing tanks from account summary data)
	protected $accurate_calculation = false;
	
	// Account WN8
	protected $wn8;
	
	// Account ID
	protected $account_id;
	
	// Tanks missing in expected tank values that user drive (excluded from WN8 acounting)
	public $missing_tanks;
	
	/**
	 * Create instance of WN8 class and calculate WN8 stats for given account. If you already have account_id set it in $search, 
	 * if not set $search to account nickname you search for. Application will find account_id. If $missing_search is set to TRUE, application will use aditional API query 
	 * to get Tankopedia tanks info (name etc.) for tanks missing in WN8 calculation (those excluded from expected tank values). Application use settings from API instance. 
	 * So if you want to get WN8 of an account on RU cluster set proper server in Wargaming\API instance set in $api. Same for language.
	 * 
	 * @param   \Wargaming\API   $api                            Wargaming\API instance version at least 1.04. It is used to retrieve data from Wargaming servers.
	 * @param   string/integer   $search                         If $search is integer type application will assume it is account_id, if it is string application 
	 *                                                           will search for account with nickname set to $search
	 * @param   bool             $accurate_calculation           If $accurate_calculation is set to TRUE, application will remove OP tanks same as tanks missing in expected tank values 
	 *                                                           from account summary. Warning: Accurate calculation is from 25% to 35% slower.
	 * @param   bool             $missing_search                 If $missing_search is set to TRUE, application will also add informations about tanks missing in calculation (missing in expected tank values)
	 * @param   array            $expected_tank_values_version   Array of expected tank values from wnefficiency.net (vBAddict.net)
	 *                                                           in WN8 calculation (those who are excluded from expected tank values due to being OP or lack of statistic data)
	 * 
	 * @throws \Exception
	 */
	public function __construct(\Wargaming\API $api, $search, $accurate_calculation = false, $missing_search = false, $expected_tank_values_version = 21 ) {
		$this->api = $api;
		$this->accurate_calculation = $accurate_calculation;
		$this->search_missing_tanks = $missing_search;
		
		
		// Moved loading of expected tank values to seperate method to allow method overriding
		$this->loadExpectedTankValues($expected_tank_values_version);
		
		// If user provided nickname instead of account_id, get account_id
		if( is_string($search) ) {
			
			// Get account from Wargaming servers
			$account = $api->get('wot/account/list', array('fields'=>'account_id','type'=>'exact', 'search'=>$search));
		
			// If account found, store its account_id
			if( is_array($account) AND !empty($account) ) {
				
				$this->account_id = current($account)->account_id;
				
			// Account not found so return exception
			} else {
				
				throw new \Exception('Account <b>'.$search.'</b> not found on selected server.', '404');
				
			}
			
		// User provided account_id, use it to get stats
		} else {
			
			$this->account_id = $search;
			
		}

	}
	
	/**
	 * Moved loading of expected tank values to seperate method to allow method overriding. 
	 * You can use this method to load expected tank values from database or memmory cache.
	 * 
	 * @param   Integer   $version   Expected tank values version
	 * @since   1.1
	 */
	protected function loadExpectedTankValues($version) {
		
		// If we dont have expected tank values download them
		if ( !file_exists(dirname(__FILE__).'/expected_tank_values_'.$version.'.json') ) {
			file_put_contents(
				dirname(__FILE__).'/expected_tank_values_'.$version.'.json', 
				file_get_contents('http://www.wnefficiency.net/exp/expected_tank_values_'.$version.'.json')
			);
		}
		
		// Load expected tank values
		$buff = json_decode( file_get_contents(dirname(__FILE__).'/expected_tank_values_'.$version.'.json') )->data;
		foreach ( $buff AS $tank ) {
			
			// Load tanks values and index them by Tank ID
			$this->expected_tank_values[$tank->IDNum] = $tank;
			
		}
	}
	
	/**
	 * Calculate account WN8
	 * 
	 * @return   Integer
	 */
	protected function getWN8() {
		
		// If WN8 was not calculated yet
		if( is_null($this->wn8) ) {
			
			// Get account_id
			$account_id = $this->account_id;
			
			// Get summary values
			$summary = $this->api->get('wot/account/info',array(
			   'fields'=>'statistics.all.battles,statistics.all.frags,statistics.all.damage_dealt,statistics.all.dropped_capture_points,statistics.all.spotted,statistics.all.wins',
			   'account_id'=>$account_id
			))->$account_id->statistics->all;
			
			// Get tanks values
			$tanks = $this->api->get('wot/account/tanks',array('fields'=>'tank_id,statistics.battles','account_id'=>$account_id))->$account_id;
			
			// If this account has no tanks data skip calculation and return 0
			if( empty($tanks)) {
				
				$this->wn8 = 0;
				return $this->wn8;
				
			}

			// WN8 expected calculation
			$expDAMAGE = $expFRAGS = $expSPOT = $expDEF = $expWIN = 0;
			
			// Tanks missing in expected tank values but existing in account
			$missing = array();
			
			// Calculated account expected values
			foreach( $tanks AS $tank ) {

				// Tank exists in expected tank values
				if( isset($this->expected_tank_values[$tank->tank_id]) ) {
					
					// Expected values for current tank
					$expected = $this->expected_tank_values[$tank->tank_id];
					
					// Battles on current tank
					$tank_battles = $tank->statistics->battles;
					
					// Calculate expected values for current tank
					$expDAMAGE += $expected->expDamage * $tank_battles;
					$expSPOT += $expected->expSpot * $tank_battles;
					$expFRAGS += $expected->expFrag * $tank_battles;
					$expDEF += $expected->expDef * $tank_battles;
					$expWIN += 0.01*$expected->expWinRate * $tank_battles;           
					
				// Tank missing in expected tank values so add it to the list
				} else {
					
					$missing [] = $tank->tank_id;
					
				}
			}
			
			// User want accurate calculation
			if( $this->accurate_calculation AND !empty($missing) ) {
				
				// Get missing tanks stats from API server
				$missing_tanks = $this->api->get('wot/tanks/stats',array('tank_id'=>implode(',',$missing),'fields'=>'tank_id,all.battles,all.frags,all.damage_dealt,all.dropped_capture_points,all.spotted,all.wins','account_id'=>$account_id))->$account_id;
				
				// Reduce account summary data
				foreach( $missing_tanks AS $tank ) {
					$summary->damage_dealt -= $tank->all->damage_dealt;
					$summary->spotted -= $tank->all->spotted;
					$summary->frags -= $tank->all->frags;
					$summary->dropped_capture_points -= $tank->all->dropped_capture_points;
					$summary->wins -= $tank->all->wins;
				}
			}

			// If there are missing tanks and searching for info is set to TRUE, get those values
			if( !empty($missing) AND $this->search_missing_tanks ) {
				$this->missing_tanks = $this->api->get('wot/encyclopedia/tankinfo',array('tank_id'=>implode(',',$missing),'fields'=>'localized_name'));
			}

			// Calculate WN8
			$rDAMAGE = $summary->damage_dealt / $expDAMAGE;
			$rSPOT = $summary->spotted / $expSPOT;
			$rFRAG = $summary->frags / $expFRAGS;
			$rDEF = $summary->dropped_capture_points / $expDEF;
			$rWIN = $summary->wins / $expWIN;  

			$rWINc    = max(0,                      ($rWIN    - 0.71) / (1 - 0.71) );
			$rDAMAGEc = max(0,                      ($rDAMAGE - 0.22) / (1 - 0.22) );
			$rFRAGc   = max(0, min($rDAMAGEc + 0.2, ($rFRAG   - 0.12) / (1 - 0.12)));
			$rSPOTc   = max(0, min($rDAMAGEc + 0.1, ($rSPOT   - 0.38) / (1 - 0.38)));
			$rDEFc    = max(0, min($rDAMAGEc + 0.1, ($rDEF    - 0.10) / (1 - 0.10)));

			$wn8 = 980*$rDAMAGEc + 210*$rDAMAGEc*$rFRAGc + 155*$rFRAGc*$rSPOTc + 75*$rDEFc*$rFRAGc + 145*MIN(1.8,$rWINc);
			
			// Ok we have WN8, store it
			$this->wn8 = round($wn8, 2);  
		}
			
		// Return our mighty number
		return $this->wn8;	
	}
	
	/**
	 * Returns account WN8
	 * 
	 * @return float
	 */
	public function __toString() {
		return (string)$this->getWN8();
	}
	
}