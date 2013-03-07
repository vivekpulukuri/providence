<?php
/** ---------------------------------------------------------------------
 * app/models/ca_data_importers.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012-2013 Whirl-i-Gig
 *
 * For more information visit http://www.CollectiveAccess.org
 *
 * This program is free software; you may redistribute it and/or modify it under
 * the terms of the provided license as published by Whirl-i-Gig
 *
 * CollectiveAccess is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTIES whatsoever, including any implied warranty of 
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
 *
 * This source code is free and modifiable under the terms of 
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
 * the "license.txt" file for details, or visit the CollectiveAccess web site at
 * http://www.CollectiveAccess.org
 * 
 * @package CollectiveAccess
 * @subpackage models
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */
 
 /**
   *
   */

require_once(__CA_LIB_DIR__.'/core/ModelSettings.php');
require_once(__CA_LIB_DIR__.'/ca/BundlableLabelableBaseModelWithAttributes.php');
require_once(__CA_LIB_DIR__.'/ca/Import/DataReaderManager.php');
require_once(__CA_LIB_DIR__.'/ca/Utils/DataMigrationUtils.php');
require_once(__CA_MODELS_DIR__."/ca_data_importer_labels.php");
require_once(__CA_MODELS_DIR__."/ca_data_importer_groups.php");
require_once(__CA_MODELS_DIR__."/ca_data_importer_items.php");
require_once(__CA_MODELS_DIR__."/ca_data_import_events.php");
require_once(__CA_LIB_DIR__.'/core/Parsers/PHPExcel/PHPExcel.php');
require_once(__CA_LIB_DIR__.'/core/Parsers/PHPExcel/PHPExcel/IOFactory.php');
require_once(__CA_LIB_DIR__.'/core/Logging/KLogger/KLogger.php');
require_once(__CA_LIB_DIR__.'/core/Db/Transaction.php');

BaseModel::$s_ca_models_definitions['ca_data_importers'] = array(
 	'NAME_SINGULAR' 	=> _t('data importer'),
 	'NAME_PLURAL' 		=> _t('data importers'),
	'FIELDS' 			=> array(
		'importer_id' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN, 
				'IDENTITY' => true, 'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('CollectiveAccess id'), 'DESCRIPTION' => _t('Unique numeric identifier used by CollectiveAccess internally to identify this importer')
		),
		'importer_code' => array(
				'FIELD_TYPE' => FT_TEXT, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => true, 
				'DEFAULT' => '',
				'LABEL' => _t('Importer code'), 'DESCRIPTION' => _t('Unique alphanumeric identifier for this importer.'),
				'UNIQUE_WITHIN' => array()
				//'REQUIRES' => array('is_administrator')
		),
		'table_num' => array(
				'FIELD_TYPE' => FT_NUMBER, 'DISPLAY_TYPE' => DT_HIDDEN,
				'DONT_USE_AS_BUNDLE' => true,
				'DISPLAY_WIDTH' => 40, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Importer type'), 'DESCRIPTION' => _t('Indicates type of item importer is used for.'),
				'BOUNDS_CHOICE_LIST' => array(
					_t('objects') => 57,
					_t('object lots') => 51,
					_t('entities') => 20,
					_t('places') => 72,
					_t('occurrences') => 67,
					_t('collections') => 13,
					_t('storage locations') => 89,
					_t('loans') => 133,
					_t('movements') => 137,
					_t('tours') => 153,
					_t('tour stops') => 155,
					_t('object representations') => 56,
					_t('representation annotations') => 82,
					_t('lists') => 36,
					_t('list items') => 33
				)
		),
		'settings' => array(
				'FIELD_TYPE' => FT_VARS, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 88, 'DISPLAY_HEIGHT' => 15,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Settings'), 'DESCRIPTION' => _t('Importer settings')
		),
		'worksheet' => array(
				'FIELD_TYPE' => FT_FILE, 'DISPLAY_TYPE' => DT_FIELD, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => '',
				'LABEL' => _t('Importer worksheet'), 'DESCRIPTION' => _t('Archived copy of worksheet used to create the importer.')
		),
		'deleted' => array(
				'FIELD_TYPE' => FT_BIT, 'DISPLAY_TYPE' => DT_OMIT, 
				'DISPLAY_WIDTH' => 10, 'DISPLAY_HEIGHT' => 1,
				'IS_NULL' => false, 
				'DEFAULT' => 0,
				'LABEL' => _t('Is deleted?'), 'DESCRIPTION' => _t('Indicates if the importer is deleted or not.'),
				'BOUNDS_VALUE' => array(0,1)
		)
	)
);
	
class ca_data_importers extends BundlableLabelableBaseModelWithAttributes {
	# ---------------------------------
	# --- Object attribute properties
	# ---------------------------------
	# Describe structure of content object's properties - eg. database fields and their
	# associated types, what modes are supported, et al.
	#

	# ------------------------------------------------------
	# --- Basic object parameters
	# ------------------------------------------------------
	# what table does this class represent?
	protected $TABLE = 'ca_data_importers';
	      
	# what is the primary key of the table?
	protected $PRIMARY_KEY = 'importer_id';

	# ------------------------------------------------------
	# --- Properties used by standard editing scripts
	# 
	# These class properties allow generic scripts to properly display
	# records from the table represented by this class
	#
	# ------------------------------------------------------

	# Array of fields to display in a listing of records from this table
	protected $LIST_FIELDS = array('importer_id');

	# When the list of "list fields" above contains more than one field,
	# the LIST_DELIMITER text is displayed between fields as a delimiter.
	# This is typically a comma or space, but can be any string you like
	protected $LIST_DELIMITER = ' ';

	# What you'd call a single record from this table (eg. a "person")
	protected $NAME_SINGULAR;

	# What you'd call more than one record from this table (eg. "people")
	protected $NAME_PLURAL;

	# List of fields to sort listing of records by; you can use 
	# SQL 'ASC' and 'DESC' here if you like.
	protected $ORDER_BY = array('importer_id');

	# If you want to order records arbitrarily, add a numeric field to the table and place
	# its name here. The generic list scripts can then use it to order table records.
	protected $RANK = '';
	
	# ------------------------------------------------------
	# Hierarchical table properties
	# ------------------------------------------------------
	protected $HIERARCHY_TYPE				=	null;
	protected $HIERARCHY_LEFT_INDEX_FLD 	= 	null;
	protected $HIERARCHY_RIGHT_INDEX_FLD 	= 	null;
	protected $HIERARCHY_PARENT_ID_FLD		=	null;
	protected $HIERARCHY_DEFINITION_TABLE	=	null;
	protected $HIERARCHY_ID_FLD				=	null;
	protected $HIERARCHY_POLY_TABLE			=	null;
	
	# ------------------------------------------------------
	# Change logging
	# ------------------------------------------------------
	protected $UNIT_ID_FIELD = null;
	protected $LOG_CHANGES_TO_SELF = false;
	protected $LOG_CHANGES_USING_AS_SUBJECT = array(
		"FOREIGN_KEYS" => array(
		
		),
		"RELATED_TABLES" => array(
		
		)
	);	
	
	# ------------------------------------------------------
	# Labeling
	# ------------------------------------------------------
	protected $LABEL_TABLE_NAME = 'ca_data_importer_labels';
	
	# ------------------------------------------------------
	# ID numbering
	# ------------------------------------------------------
	protected $ID_NUMBERING_ID_FIELD = 'importer_code';	// name of field containing user-defined identifier
	protected $ID_NUMBERING_SORT_FIELD = null;			// name of field containing version of identifier for sorting (is normalized with padding to sort numbers properly)
	protected $ID_NUMBERING_CONTEXT_FIELD = null;		// name of field to use value of for "context" when checking for duplicate identifier values; if not set identifer is assumed to be global in scope; if set identifer is checked for uniqueness (if required) within the value of this field

	
	# ------------------------------------------------------
	# $FIELDS contains information about each field in the table. The order in which the fields
	# are listed here is the order in which they will be returned using getFields()

	protected $FIELDS;
	
	/**
	 * Settings delegate - implements methods for setting, getting and using 'settings' var field
	 */
	public $SETTINGS;
	
	
	public static $s_num_import_errors = 0;
	public static $s_num_records_skipped = 0;
	public static $s_import_error_list = array();
	
	
	# ------------------------------------------------------
	public function __construct($pn_id=null) {
		// Filter list of tables importers can be used for to those enabled in current config
		BaseModel::$s_ca_models_definitions['ca_data_importers']['FIELDS']['table_num']['BOUNDS_CHOICE_LIST'] = caFilterTableList(BaseModel::$s_ca_models_definitions['ca_data_importers']['FIELDS']['table_num']['BOUNDS_CHOICE_LIST']);
		
		parent::__construct($pn_id);
		
		$this->initSettings();
	}
	# ------------------------------------------------------
	protected function initLabelDefinitions() {
		parent::initLabelDefinitions();
	}
	# ------------------------------------------------------
	protected function initSettings() {
		$va_settings = array();
		
		$va_settings['importer_type'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_SELECT,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'options' => $this->getAvailableImporterTypes(),
			'label' => _t('Importer type'),
			'description' => _t('Set importer type, i.e. the format of the source data.  Currently supported: XLSX, XLS, and MYSQL')
		);
		$va_settings['type'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_SELECT,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Record type'),
			'description' => _t('Type to set all imported records to. If import includes a mapping to type_id, that will be privileged and the type setting will be ignored.')
		);
		$va_settings['numInitialRowsToSkip'] = array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_FIELD,
			'width' => 4, 'height' => 1,
			'takesLocale' => false,
			'default' => 0,
			'label' => _t('Initial rows to skip'),
			'description' => _t('The number of rows at the top of the data set to skip. Use this setting to skip over column headers in spreadsheets and similar data.')
		);
		$va_settings['name'] = array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Mapping name'),
			'description' => _t('Human readable name of the import mapping.  Pending implementation.')
		);
		$va_settings['code'] = array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Mapping identifier'),
			'description' => _t('Arbitrary alphanumeric code for the import mapping (no special characters or spaces).  Pending implementation.')
		);
		$va_settings['table'] = array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Map to table'),
			'description' => _t('Sets the CollectiveAccess table for the imported data.  Pending implementation.')
		);
		$va_settings['existingRecordPolicy'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'options' => array(
				_t('none') => 'none',
				_t('skip_on_idno') => 'skip_on_idno',
				_t('merge_on_idno') => 'merge_on_idno',
				_t('overwrite_on_idno') => 'overwrite_on_idno',
				_t('skip_on_preferred_labels') => 'skip_on_preferred_labels',
				_t('merge_on_preferred_labels') => 'merge_on_preferred_labels',
				_t('overwrite_on_preferred_labels') => 'overwrite_on_preferred_labels',
				_t('merge_on_idno_and_preferred_labels') => 'merge_on_idno_and_preferred_labels',
				_t('overwrite_on_idno_and_preferred_labels') => 'overwrite_on_idno_and_preferred_labels'
			),
			'label' => _t('Existing record policy'),
			'description' => _t('Determines how existing records are checked for and handled by the import mapping.  Pending implementation.')
		);
		$va_settings['archiveMapping'] = array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'label' => _t('Archive mapping?'),
			'description' => _t('Set to yes to save the mapping spreadsheet; no to delete it from the server after import.  Pending implementation.')
		);
		$va_settings['archiveDataSets'] = array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'options' => array(
				_t('yes') => 1,
				_t('no') => 0
			),
			'label' => _t('Archive data sets?'),
			'description' => _t('Set to yes to save the data spreadsheet or no to delete it from the server after import.  Pending implementation.')
		);
		$va_settings['errorPolicy'] = array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'options' => array(
				_t('ignore') => "ignore",
				_t('stop') => "stop"
			),
			'label' => _t('Error policy'),
			'description' => _t('Determines how errors are handled for the import.  Options are to ignore the error, stop the import when an error is encountered and to receive a prompt when the error is encountered.')
		);
		
		$this->SETTINGS = new ModelSettings($this, 'settings', $va_settings);
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getAvailableImporterTypes() {
		return array(
			'CSV' 		=> 'CSV',
			'TAB' 		=> 'TAB',
			'XSLX' 		=> 'XLSX',
			'MySQL' 	=> 'MySQL'
		);
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function addImportItem($pa_values){
		
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function addGroup($ps_group_code, $ps_destination, $pa_settings=null, $pa_options=null){
		if(!$this->getPrimaryKey()) return false;
		
		$t_group = new ca_data_importer_groups();
		$t_group->setMode(ACCESS_WRITE);
		$t_group->set('importer_id', $this->getPrimaryKey());
		$t_group->set('group_code', $ps_group_code);
		$t_group->set('destination', $ps_destination);
		
		if (is_array($pa_settings)) {
			foreach($pa_settings as $vs_k => $vs_v) {
				$t_group->setSetting($vs_k, $vs_v);
			}
		}
		$t_group->insert();
		
		if ($t_group->numErrors()) {
			$this->errors = $t_group->errors;
			return false;
		}
		
		if (isset($pa_options['returnInstance']) && $pa_options['returnInstance']) {
			return $t_group;
		}
		return $t_group->getPrimaryKey();
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getGroups(){
		if(!$this->getPrimaryKey()) return false;
		
		$vo_db = $this->getDb();
		
		$qr_groups = $vo_db->query("
			SELECT * 
			FROM ca_data_importer_groups 
			WHERE importer_id = ?
		",$this->getPrimaryKey());
		
		$va_return = array();
		while($qr_groups->nextRow()){
			$va_return[(int)$qr_groups->get("group_id")] = $qr_groups->getRow();
		}
		
		return $va_return;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getGroupIDs(){
		if(is_array($va_groups = $this->getGroups())){
			return $va_groups;
		} else {
			return array();
		}
	}
	# ------------------------------------------------------
	public function getItems(){
		if(!$this->getPrimaryKey()) return false;
		
		$vo_db = $this->getDb();
		
		$qr_items = $vo_db->query("
			SELECT * 
			FROM ca_data_importer_items 
			WHERE importer_id = ?
		",$this->getPrimaryKey());
		
		$va_return = array();
		while($qr_items->nextRow()){
			$va_return[$qr_items->get("item_id")] = $qr_items->getRow();
			$va_return[$qr_items->get("item_id")]['settings'] = caUnserializeForDatabase($va_return[$qr_items->get("item_id")]['settings']);
		}
		
		return $va_return;
	}
	# ------------------------------------------------------
	public function getItemIDs(){
		if(is_array($va_items = $this->getItems())){
			return $va_items;
		} else {
			return array();
		}
	}
	# ------------------------------------------------------
	/**
	 * Remove group and all associated items
	 * 
	 * @param type $ps_group_code
	 */
	public function removeGroup($pn_group_id){
		$t_group = new ca_data_importer_groups();
		
		if(!in_array($pn_group_id, $this->getGroupIDs())){
			return false; // don't delete groups from other importers
		}
		
		if($t_group->load($pn_group_id)){
			$t_group->setMode(ACCESS_WRITE);
			$t_group->removeItems();
			$t_group->delete();
		} else {
			return false;
		}
	}
	# ------------------------------------------------------
	public function removeAllGroups(){
		foreach($this->getGroupIDs() as $vn_group_id){
			$this->removeGroup($vn_group_id);
		}
	}
	# ------------------------------------------------------
	/**
	 * Remove importer group using its group code
	 * 
	 * @param string $ps_group_code
	 */
	public function removeGroupByCode($ps_group_code){
		$t_group = new ca_data_importer_groups();
		
		if($t_group->load(array("code" => $ps_group_code))){
			$t_group->setMode(ACCESS_WRITE);
			$t_group->removeItems();
			$t_group->delete();
		} else {
			return false;
		}
	}
	# ------------------------------------------------------
	public function removeItem($pn_item_id){
		$t_item = new ca_data_importer_items();
		
		if(!in_array($pn_item_id, $this->getItemIDs())){
			return false; // don't delete items from other importers
		}
		
		if($t_item->load($pn_item_id)){
			$t_item->setMode(ACCESS_WRITE);
			$t_item->delete();
		} else {
			return false;
		}
	}
	# ------------------------------------------------------
	/**
	 * Reroutes calls to method implemented by settings delegate to the delegate class
	 */
	public function __call($ps_name, $pa_arguments) {
		if (method_exists($this->SETTINGS, $ps_name)) {
			return call_user_func_array(array($this->SETTINGS, $ps_name), $pa_arguments);
		}
		die($this->tableName()." does not implement method {$ps_name}");
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public static function loadImporterFromFile($ps_source, $pa_options=null) {
		global $g_ui_locale_id;
		$vn_locale_id = (isset($pa_options['locale_id']) && (int)$pa_options['locale_id']) ? (int)$pa_options['locale_id'] : $g_ui_locale_id;
		
		$o_excel = PHPExcel_IOFactory::load($ps_source);
		//$o_excel->setActiveSheet(1);
		$o_sheet = $o_excel->getActiveSheet();
		
		$vn_row = 0;
		
		$va_settings = array();
		$va_mappings = array();
		
		$va_refineries = RefineryManager::getRefineryNames();
		foreach ($o_sheet->getRowIterator() as $o_row) {
			if ($vn_row == 0) {	// skip first row
				$vn_row++;
				continue;
			}
			
			//$o_cells = $o_row->getCellIterator();
			//$o_cells->setIterateOnlyExistingCells(false); 
			
			$vn_row_num = $o_row->getRowIndex();
			$o_cell = $o_sheet->getCellByColumnAndRow(0, $vn_row_num);
			$vs_mode = (string)$o_cell->getValue();
			
			switch($vs_mode) {
				default:
				case 'SKIP':
					continue(2);
					break;
				case 'Mapping':
				case 'Constant':
					$o_source = $o_sheet->getCellByColumnAndRow(1, $o_row->getRowIndex());
					$o_dest = $o_sheet->getCellByColumnAndRow(2, $o_row->getRowIndex());
					
					$o_group = $o_sheet->getCellByColumnAndRow(3, $o_row->getRowIndex());
					$o_options = $o_sheet->getCellByColumnAndRow(4, $o_row->getRowIndex());
					$o_refinery = $o_sheet->getCellByColumnAndRow(5, $o_row->getRowIndex());
					$o_refinery_options = $o_sheet->getCellByColumnAndRow(6, $o_row->getRowIndex());
					$o_orig_values = $o_sheet->getCellByColumnAndRow(7, $o_row->getRowIndex());
					$o_replacement_values = $o_sheet->getCellByColumnAndRow(8, $o_row->getRowIndex());
					$o_source_desc = $o_sheet->getCellByColumnAndRow(9, $o_row->getRowIndex());
					$o_notes = $o_sheet->getCellByColumnAndRow(10, $o_row->getRowIndex());
					
					if (!($vs_group = trim((string)$o_group->getValue()))) {
						$vs_group = '_group_'.(string)$o_source->getValue()."_{$vn_row}";
					}
					
					$vs_source = trim((string)$o_source->getValue());
					
					if ($vs_mode == 'Constant') {
						$vs_source = "_CONSTANT_:{$vn_row_num}:{$vs_source}";
					}
					$vs_destination = trim((string)$o_dest->getValue());
					
					if (!$vs_source) { 
						print "Warning: skipped mapping at row {$vn_row_num} because source was not defined\n";
						continue(2);
					}
					if (!$vs_destination) { 
						print "Warning: skipped mapping at row {$vn_row_num} because destination was not defined\n";
						continue(2);
					}
					
					$va_options = null;
					if ($vs_options_json = (string)$o_options->getValue()) { 
						if (is_null($va_options = @json_decode($vs_options_json, true))) {
							print "Warning: invalid options for group {$vs_group}/source {$vs_source}\n";
						}
					}
					
					if ($vs_mode == 'Mapping') {
						$vs_refinery = trim((string)$o_refinery->getValue());
					
						$va_refinery_options = null;
						if ($vs_refinery && ($vs_refinery_options_json = (string)$o_refinery_options->getValue())) {
							if (!in_array($vs_refinery, $va_refineries)) {
								print _t("Warning: refinery %1 does not exist", $vs_refinery)."\n";
							} else {
								if (is_null($va_refinery_options = json_decode($vs_refinery_options_json, true))) {
									print "Warning: invalid refinery options for group {$vs_group}/source {$vs_source} = $vs_refinery_options_json\n";
								}
							}
						}
					} else {
						// Constants don't use refineries
						$vs_refinery = $va_refinery_options = null;
					}
					
					$va_mapping[$vs_group][$vs_source][] = array(
						'destination' => $vs_destination,
						'options' => $va_options,
						'refinery' => $vs_refinery,
						'refinery_options' => $va_refinery_options,
						'source_description' => (string)$o_source_desc->getValue(),
						'notes' => (string)$o_notes->getValue(),
						'original_values' => preg_split("![\n\r]{1}!", mb_strtolower((string)$o_orig_values->getValue())),
						'replacement_values' => preg_split("![\n\r]{1}!", mb_strtolower((string)$o_replacement_values->getValue()))
					);
					
					break;
				case 'Setting':
					$o_setting_name = $o_sheet->getCellByColumnAndRow(1, $o_row->getRowIndex());
					$o_setting_value = $o_sheet->getCellByColumnAndRow(2, $o_row->getRowIndex());
					$va_settings[(string)$o_setting_name->getValue()] = (string)$o_setting_value->getValue();
					break;
			}
			$vn_row++;
		}
		
		// Do checks on mapping
		if (!$va_settings['code']) { 
			print "You must set a code for your mapping!\n";
			return;
		}
		
		$o_dm = Datamodel::load();
		if (!($t_instance = $o_dm->getInstanceByTableName($va_settings['table']))) {
			print _t("Mapping target table %1 is invalid\n", $va_settings['table']);
			return;
		}
		
		if (!$va_settings['name']) { $va_settings['name'] = $va_settings['code']; }
		
		//print_R($va_settings);
		//print_R($va_mapping);
		
		
		$t_importer = new ca_data_importers();
		$t_importer->setMode(ACCESS_WRITE);
		
		// Remove any existing mapping
		if ($t_importer->load(array('importer_code' => $va_settings['code']))) {
			$t_importer->delete(true, array('hard' => true));
			if ($t_importer->numErrors()) {
				print _t("Could not delete existing mapping for %1: %2", $va_settings['code'], join("; ", $t_importer->getErrors()))."\n";
				return;
			}
		}
		
		// Create new mapping
		$t_importer->set('importer_code', $va_settings['code']);
		$t_importer->set('table_num', $t_instance->tableNum());
		
		unset($va_settings['code']);
		unset($va_settings['table']);
		foreach($va_settings as $vs_k => $vs_v) {
			$t_importer->setSetting($vs_k, $vs_v);
		}
		$t_importer->insert();
		
		if ($t_importer->numErrors()) {
			print _t("Error creating mapping: %1", join("; ", $t_importer->getErrors()))."\n";
			return;
		}
		
		$t_importer->addLabel(array('name' => $va_settings['name']), $vn_locale_id, null, true);
		
		if ($t_importer->numErrors()) {
			print _t("Error creating mapping name: %1", join("; ", $t_importer->getErrors()))."\n";
			return;
		}
		
		foreach($va_mapping as $vs_group => $va_mappings_for_group) {
			$vs_group_dest = ca_data_importers::_getGroupDestinationFromItems($va_mappings_for_group);
			if (!$vs_group_dest) { 
				$va_item = array_shift(array_shift($va_mappings_for_group));
				print _t("Skipped items for %1 because no common grouping could be found", $va_item['destination'])."\n";
				continue;
			}
			
			$t_group = $t_importer->addGroup($vs_group, $vs_group_dest, array(), array('returnInstance' => true));
			
			// Add items
			foreach($va_mappings_for_group as $vs_source => $va_mappings_for_source) {
				foreach($va_mappings_for_source as $va_row) {
					$va_item_settings = array();
					$va_item_settings['refineries'] = array($va_row['refinery']);
				
					$va_item_settings['original_values'] = $va_row['original_values'];
					$va_item_settings['replacement_values'] = $va_row['replacement_values'];
				
					if (is_array($va_row['options'])) {
						foreach($va_row['options'] as $vs_k => $vs_v) {
							$va_item_settings[$vs_k] = $vs_v;
						}
					}
					if (is_array($va_row['refinery_options'])) {
						foreach($va_row['refinery_options'] as $vs_k => $vs_v) {
							$va_item_settings[$va_row['refinery'].'_'.$vs_k] = $vs_v;
						}
					}
					
					$t_group->addItem($vs_source, $va_row['destination'], $va_item_settings, array('returnInstance' => true));
				}
			}
		}
		
		return $t_importer;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function _getGroupDestinationFromItems($pa_items) {
		$va_acc = null;
		foreach($pa_items as $vn_item_id => $va_items_for_source) {
			foreach($va_items_for_source as $va_item) {
				if (is_null($va_acc)) { 
					$va_acc = explode(".", $va_item['destination']);
				} else {
					$va_tmp = explode(".", $va_item['destination']);
				
					$vn_len = sizeof($va_acc);
					for($vn_i=$vn_len - 1; $vn_i >= 0; $vn_i--) {
						if (!isset($va_tmp[$vn_i]) || ($va_acc[$vn_i] != $va_tmp[$vn_i])) {
							for($vn_x = $vn_i; $vn_x < $vn_len; $vn_x++) {
								unset($va_acc[$vn_x]);
							}
						}
					}
				}
			}
		}
		
		return join(".", $va_acc);
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function mappingExists($ps_mapping) {
		$t_importer = new ca_data_importers();
		if($t_importer->load(array('importer_code' => $ps_mapping))) {
			return $t_importer;
		}
		return false;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function logImportError($ps_message, $pa_options=null) {
		ca_data_importers::$s_num_import_errors++;
		
		if ($vb_skipped = (isset($pa_options['skip']) && ($pa_options['skip'])) ? true : false) {
			ca_data_importers::$s_num_records_skipped++;
		}
		
		if (!is_array($pa_options)) { $pa_options = array(); }
		
		$r_ncurses_win = (isset($pa_options['window']) && $pa_options['window']) ? $pa_options['window'] : null;
		$o_log = (isset($pa_options['log']) && $pa_options['log']) ? $pa_options['log'] : null;
		
		$vb_dont_output = (!isset($pa_options['dontOutput']) || !$pa_options['dontOutput'] || $r_ncurses_win) ?  true : false;
		
		$vs_display_message = "(".date("Y-m-d H:i:s").") {$ps_message}";
		array_unshift(ca_data_importers::$s_import_error_list, $vs_display_message);
		
		// 
		// Output to screen as text
		//
		if (!$vb_dont_output) { print "{$ps_message}\n"; }
		
		
		// 
		// Output to screen via NCurses
		//
		if ($r_ncurses_win) {
			ncurses_getmaxyx($r_ncurses_win, $vn_max_y, $vn_max_x);
			
			foreach(ca_data_importers::$s_import_error_list as $vn_i => $vs_message) {
				if ($vn_i >= ($vn_max_y - 1)) { break; }
				ncurses_mvwaddstr($r_ncurses_win, $vn_i+1, 2, mb_substr($vs_message, 0, $vn_max_x - 4));
			}
			
			ncurses_refresh();
			ncurses_wrefresh($r_ncurses_win);
		}
		
		//
		// Log message
		//
		if ($o_log) { $o_log->logError($ps_message); }
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function formatValuesForLog($pa_values) {
		$va_list = array();
		
		if (is_array($pa_values)) {
			foreach($pa_values as $vs_element => $va_values) {
				if (!is_array($va_values)) {
					$va_list[] = "{$vs_element} = {$va_values}";
				} else {
					$va_list[] = "{$vs_element} = ".ca_data_importers::formatValuesForLog($va_values);
				}
			}
		} else {
			$va_list[] = $pa_values;;
		}
		return join("; ", $va_list);
	}
	# ------------------------------------------------------
	/**
	 * 
	 *
	 * @param string $ps_source
	 * @param string $ps_mapping
	 * @param array $pa_options
	 *		user_id = user to execute import for
	 *		description = Text describing purpose of import to be logged.
	 *		showCLIProgressBar = Show command-line progress bar. Default is false.
	 *		format = Format of data being imported. MANDATORY
	 *		useNcurses = Use ncurses library to format output
	 *		logDirectory = path to directory where logs should be written
	 *		logLevel = KLogger constant for minimum log level to record. Default is KLogger::INFO. Constants are, in descending order of shrillness:
	 *			KLogger::EMERG = Emergency messages (system is unusable)
	 *			KLogger::ALERT = Alert messages (action must be taken immediately)
	 *			KLogger::CRIT = Critical conditions
	 *			KLogger::ERR = Error conditions
	 *			KLogger::WARN = Warnings
	 *			KLogger::NOTICE = Notices (normal but significant conditions)
	 *			KLogger::INFO = Informational messages
	 *			KLogger::DEBUG = Debugging messages
	 */
	static public function importDataFromSource($ps_source, $ps_mapping, $pa_options=null) {
		ca_data_importers::$s_num_import_errors = 0;
		ca_data_importers::$s_num_records_skipped = 0;
		ca_data_importers::$s_import_error_list = array();
		
		if (!($t_mapping = ca_data_importers::mappingExists($ps_mapping))) {
			return null;
		}
		
		$o_event = ca_data_import_events::newEvent(isset($pa_options['user_id']) ? $pa_options['user_id'] : null, $pa_options['format'], $ps_source, isset($pa_options['description']) ? $pa_options['description'] : '');
		
		$o_trans = new Transaction();
		$t_mapping->setTransaction($o_trans);
		
		if (!is_array($pa_options) || !isset($pa_options['logLevel']) || !$pa_options['logLevel']) {
			$pa_options['logLevel'] = KLogger::INFO;
		}
		if (!is_array($pa_options) || !isset($pa_options['logDirectory']) || !$pa_options['logDirectory'] || !file_exists($pa_options['logDirectory'])) {
			$pa_options['logDirectory'] = ".";
		}
		$o_log = new KLogger($pa_options['logDirectory'], $pa_options['logLevel']);
		
		$vb_show_cli_progress_bar 	= (isset($pa_options['showCLIProgressBar']) && ($pa_options['showCLIProgressBar'])) ? true : false;
		if ($vb_use_ncurses = (isset($pa_options['useNcurses']) && ($pa_options['useNcurses'])) ? true : false) {
			$vb_use_ncurses = caCLIUseNcurses();
		}
		$vb_use_ncurses = false;
		$vn_error_window_height = null;
		if($vb_use_ncurses) {
			$r_ncurse = ncurses_init();
			$r_screen = ncurses_newwin( 0, 0, 0, 0); 
			ncurses_border(0,0, 0,0, 0,0, 0,0);
			
			ncurses_getmaxyx($r_screen, $vn_max_y, $vn_max_x);
			
			$vn_error_window_height = $vn_max_y - 8;
			$r_errors = ncurses_newwin($vn_error_window_height, $vn_max_x - 4, 4, 2);
			ncurses_wborder($r_errors, 0,0, 0,0, 0,0, 0,0);
			
			ncurses_wattron($r_errors, NCURSES_A_REVERSE);
			ncurses_mvwaddstr($r_errors, 0, 1, _t(" Recent errors "));
			ncurses_wattroff($r_errors, NCURSES_A_REVERSE);
			
			$r_progress = ncurses_newwin(3, $vn_max_x - 4, $vn_max_y-4, 2);
			ncurses_wborder($r_progress, 0,0, 0,0, 0,0, 0,0);
			
			ncurses_wattron($r_progress, NCURSES_A_REVERSE);
			ncurses_mvwaddstr($r_progress, 0, 1, _t(" Progress "));
			ncurses_wattroff($r_progress, NCURSES_A_REVERSE);
			
			$r_status = ncurses_newwin(3, $vn_max_x - 4, 1, 2);
			ncurses_wborder($r_status, 0,0, 0,0, 0,0, 0,0);
			
			ncurses_wattron($r_status, NCURSES_A_REVERSE);
			ncurses_mvwaddstr($r_status, 0, 1, _t(" Import status "));
			ncurses_wattroff($r_status, NCURSES_A_REVERSE);
			
			ncurses_refresh();
			ncurses_wrefresh($r_progress);
			ncurses_wrefresh($r_errors);
			ncurses_wrefresh($r_status);
		}
		
		$o_log->logInfo(_t('Started import of %1 using mapping %2', $ps_source, $ps_mapping));
		
	
		global $g_ui_locale_id;	// constant locale set by index.php for web requests
		$vn_locale_id = (isset($pa_options['locale_id']) && (int)$pa_options['locale_id']) ? (int)$pa_options['locale_id'] : $g_ui_locale_id;
		
		$o_dm = $t_mapping->getAppDatamodel();
		
		if ($vb_show_cli_progress_bar){
			print CLIProgressBar::start(0, _t('Reading %1', $ps_source), array('window' => $r_progress));
		}
	
		$t = new Timer();
	
		// Open file 
		$ps_format = (isset($pa_options['format']) && $pa_options['format']) ? $pa_options['format'] : null;	
		if (!($o_reader = $t_mapping->getDataReader($ps_source, $ps_format))) {
			ca_data_importers::logImportError(_t("Could not open source %1 (format=%2)", $ps_source, $ps_format), array('window' => $r_errors, 'log' => $o_log));
			if($vb_use_ncurses) { ncurses_end(); }
			$o_trans->rollback();
			return false;
		}
		if (!$o_reader->read($ps_source)) {
			ca_data_importers::logImportError(_t("Could not read source %1 (format=%2)", $ps_source, $ps_format), array("window" => $r_errors, 'log' => $o_log));
			if($vb_use_ncurses) { ncurses_end(); }
			$o_trans->rollback();
			return false;
		}
		
		$o_log->logDebug(_t('Finished reading input source at %1 seconds', $t->getTime(4)));
		
		if ($vb_show_cli_progress_bar){
			print CLIProgressBar::start($o_reader->numRows(), _t('Importing from %1', $ps_source), array('window' => $r_progress));
		}
		
		// What are we importing?
		$vn_table_num = $t_mapping->get('table_num');
		if (!($t_subject = $o_dm->getInstanceByTableNum($vn_table_num))) {
			// invalid table
			if($vb_use_ncurses) { ncurses_end(); }
			$o_trans->rollback();
			return false;
		}
		$t_subject->setTransaction($o_trans);
		
		$t_label = $t_subject->getLabelTableInstance();
		$t_label->setTransaction($o_trans);
		
		$vs_subject_table = $t_subject->tableName();
		$vs_type_id_fld = $t_subject->getTypeFieldName();
		$vs_idno_fld = $t_subject->getProperty('ID_NUMBERING_ID_FIELD');
		
		// get mapping groups
		$va_mapping_groups = $t_mapping->getGroups();
		$va_mapping_items = $t_mapping->getItems();
		
		//
		// Mapping-level settings
		//
		$vs_type_mapping_setting = $t_mapping->getSetting('type');
		$vn_num_initial_rows_to_skip = $t_mapping->getSetting('numInitialRowsToSkip');
		if (!in_array($vs_import_error_policy = $t_mapping->getSetting('errorPolicy'), array('ignore', 'stop'))) {
			$vs_import_error_policy = 'ignore';
		}
		
		if (!in_array(	
			$vs_existing_record_policy = $t_mapping->getSetting('existingRecordPolicy'),
			array(
				'none', 'skip_on_idno', 'skip_on_preferred_labels',
				'merge_on_idno', 'merge_on_preferred_labels', 'merge_on_idno_and_preferred_labels',
			 	'overwrite_on_idno', 'overwrite_on_preferred_labels', 'overwrite_on_idno_and_preferred_labels'
			)
		)) {
			$vs_existing_record_policy = 'none';
		}		
		
		
		// Analyze mapping for figure out where type, idno and preferred label are coming from
		$vn_type_id_mapping_item_id = $vn_idno_mapping_item_id = null;
		$va_preferred_label_mapping_ids = array();
		foreach($va_mapping_items as $vn_item_id => $va_item) {
			$vs_destination = $va_item['destination'];
			
			if (sizeof($va_dest_tmp = explode(".", $vs_destination)) >= 2) {
				if (($va_dest_tmp[0] == $vs_subject_table) && ($va_dest_tmp[1] == 'preferred_labels')) {
					if (isset($va_dest_tmp[2])) {
						$va_preferred_label_mapping_ids[$vn_item_id] = $va_dest_tmp[2];
					} else {
						$va_preferred_label_mapping_ids[$vn_item_id] = $t_subject->getLabelDisplayField();
					}
					continue;
				}
			}
			
			switch($vs_destination) {
				case "{$vs_subject_table}.{$vs_type_id_fld}":
					$vn_type_id_mapping_item_id = $vn_item_id;
					break;
				case "{$vs_subject_table}.{$vs_idno_fld}":
					$vn_idno_mapping_item_id = $vn_item_id;
					break;
			}
		}
		
		$va_items_by_group = array();
		foreach($va_mapping_items as $vn_item_id => $va_item) {
			$va_items_by_group[$va_item['group_id']][$va_item['item_id']] = $va_item;
		}
		
		
		$o_log->logDebug(_t('Finished analyzing mapping at %1 seconds', $t->getTime(4)));
		
		// 
		// Run through rows
		//
		$vn_row = 0;
		$vn_rows_processed = 0;
		while ($o_reader->nextRow()) {
			$vs_preferred_label_for_log = null;
			
		
			if ($vn_row < $vn_num_initial_rows_to_skip) {	// skip over initial header rows
				$vn_row++;
				continue;
			}
			
			$vn_row++;
			
			$t->startTimer();
			$o_log->logDebug(_t('Started reading row %1 at %2 seconds', $vn_row, $t->getTime(4)));
			
			$t_subject = $o_dm->getInstanceByTableNum($vn_table_num);
			$t_subject->setTransaction($o_trans);
			$t_subject->setMode(ACCESS_WRITE);
			
			// Update status display
			if($vb_use_ncurses && $vn_rows_processed) {
				ncurses_mvwaddstr($r_status, 1, 2, 
					_t("Items processed/skipped: %1/%2", $vn_rows_processed, ca_data_importers::$s_num_records_skipped).str_repeat(" ", 5).
					_t("Errors: %1 (%2)", ca_data_importers::$s_num_import_errors, sprintf("%3.1f", (ca_data_importers::$s_num_import_errors/ $vn_rows_processed) * 100)."%").str_repeat(" ", 6).
					_t("Mapping: %1", $ps_mapping).str_repeat(" ", 5).
					_t("Source: %1", $ps_source).str_repeat(" ", 5).
					date("Y-m-d H:i:s").str_repeat(" ", 5)
				);
				ncurses_refresh();
				ncurses_wrefresh($r_status);
			}
			
			
			//
			// Get data for current row
			//
			
			$va_row = $o_reader->getRow();
			
			//
			// Perform mapping and insert
			//
			
			// Get minimal info for imported row (type_id, idno, label)
			
			// Get type
			if ($vn_type_id_mapping_item_id) {
				// Type is specified in row
				$vs_type = ca_data_importers::getValueFromSource($va_mapping_items[$vn_type_id_mapping_item_id], $o_reader);
			} else {
				// Type is constant for all rows
				$vs_type = $vs_type_mapping_setting;	
			}
			
			
			// Get idno
			$vs_idno = null;
			if ($vn_idno_mapping_item_id) {
				// idno is specified in row
				$vs_idno = ca_data_importers::getValueFromSource($va_mapping_items[$vn_idno_mapping_item_id], $o_reader);
			} else {
				$vs_idno = "%";
			}
			$vb_idno_is_template = (bool)preg_match('![%]+!', $vs_idno);
			
			//
			// Look for existing record?
			//
			if ($vs_existing_record_policy != 'none') {
				// get preferred labels
				$va_pref_label_values = array();
				foreach($va_preferred_label_mapping_ids as $vn_preferred_label_mapping_id => $vs_preferred_label_mapping_fld) {
					$va_pref_label_values[$vs_preferred_label_mapping_fld] = ca_data_importers::getValueFromSource($va_mapping_items[$vn_preferred_label_mapping_id], $o_reader);
				}
				
				switch($vs_existing_record_policy) {
					case 'skip_on_idno':
						if (!$vb_idno_is_template && $t_subject->load(array($t_subject->getProperty('ID_NUMBERING_ID_FIELD') => $vs_idno))) {
							$o_log->logInfo(_t('[%1] Skipped import because of existing record matched on identifier by policy %2', $vs_idno, $vs_existing_record_policy));
							ca_data_importers::$s_num_records_skipped++;
							continue(2);	// skip because idno matched
						}
						break;
					case 'skip_on_preferred_labels':
						if (is_array($va_ids = $t_subject->getIDsByLabel($va_pref_label_values, null, $vs_type)) && sizeof($va_ids)) {
							$o_log->logInfo(_t('[%1] Skipped import because of existing record matched on label by policy %2', $vs_idno, $vs_existing_record_policy));
							ca_data_importers::$s_num_records_skipped++;
							continue(2);	// skip because label matched
						}
						break;
					case 'merge_on_idno_and_preferred_labels':
					case 'merge_on_idno':
						if (!$vb_idno_is_template && $t_subject->load(array('idno' => $vs_idno))) {
							$o_log->logInfo(_t('[%1] Merged with existing record matched on identifer by policy %2', $vs_idno, $vs_existing_record_policy));
							break;
						}
						if ($vs_existing_record_policy == 'merge_on_idno') { break; }	// fall through if merge_on_idno_and_preferred_labels
					case 'merge_on_preferred_labels':
						if (is_array($va_ids = $t_subject->getIDsByLabel($va_pref_label_values, null, $vs_type)) && sizeof($va_ids)) {
							$t_subject->load($va_ids[0]);
							$o_log->logInfo(_t('[%1] Merged with existing record matched on label by policy %2', $vs_idno, $vs_existing_record_policy));
						}
						break;	
					case 'overwrite_on_idno_and_preferred_labels':
					case 'overwrite_on_idno':
						if (!$vb_idno_is_template && $vs_idno && $t_subject->load(array($t_subject->getProperty('ID_NUMBERING_ID_FIELD') => $vs_idno))) {
					
							$t_subject->setMode(ACCESS_WRITE);
							$t_subject->delete(true, array('hard' => true));
							if ($t_subject->numErrors()) {
								$o_log->logError(_t('[%1] Could not delete existing record matched on identifier by policy %2', $vs_idno, $vs_existing_record_policy));
								// Don't stop?
							} else {
								$o_log->logInfo(_t('[%1] Overwrote existing record matched on identifier by policy %2', $vs_idno, $vs_existing_record_policy));
								$t_subject->clear();
								break;
							}
						}
						if ($vs_existing_record_policy == 'overwrite_on_idno') { break; }	// fall through if overwrite_on_idno_and_preferred_labels
					case 'overwrite_on_preferred_labels':
						if (is_array($va_ids = $t_subject->getIDsByLabel($va_pref_label_values, null, $vs_type)) && sizeof($va_ids)) {
							$t_subject->load($va_ids[0]);
							$t_subject->setMode(ACCESS_WRITE);
							$t_subject->delete(true, array('hard' => true));
							
							if ($t_subject->numErrors()) {
								$o_log->logError(_t('[%1] Could not delete existing record matched on label by policy %2', $vs_idno, $vs_existing_record_policy));
								// Don't stop?
							} else {
								$o_log->logInfo(_t('[%1] Overwrote existing record matched on label by policy %2', $vs_idno, $vs_existing_record_policy));
								break;
							}
							$t_subject->clear();
						}
						break;
				}
			}
			
			
			if ($vb_show_cli_progress_bar) {
				print CLIProgressBar::next(1, _t("Importing %1", $vs_idno), array('window' => $r_progress));
			}
			
			$vb_output_subject_preferred_label = false;
			$va_content_tree = array();
			
			foreach($va_items_by_group as $vn_group_id => $va_items) {
				$va_group = $va_mapping_groups[$vn_group_id];
				$vs_group_destination = $va_group['destination'];
				
				$va_group_tmp = explode(".", $vs_group_destination);
				if ((sizeof($va_items) < 2) && (sizeof($va_group_tmp) > 2)) { array_pop($va_group_tmp); }
				$vs_target_table = $va_group_tmp[0];
				if (!($t_target = $o_dm->getInstanceByTableName($vs_target_table, true))) {
					// Invalid target table
					continue;
				}
			
				
				unset($va_parent);
				$va_ptr =& $va_content_tree;
				
				foreach($va_group_tmp as $vs_tmp) {
					if(!is_array($va_ptr[$vs_tmp])) { $va_ptr[$vs_tmp] = array(); }
					$va_ptr =& $va_ptr[$vs_tmp];
					if ($vs_tmp == $vs_target_table) {	// add numeric index after table to ensure repeat values don't overwrite each other
						$va_parent =& $va_ptr;
						$va_ptr[] = array();
						$va_ptr =& $va_ptr[sizeof($va_ptr)-1];
					}
				}
				
				foreach($va_items as $vn_item_id => $va_item) {
					$vm_val = ca_data_importers::getValueFromSource($va_item, $o_reader);
					
					// Get location in content tree for addition of new content
					$va_item_dest = explode(".",  $va_item['destination']);
					$vs_item_terminal = $va_item_dest[sizeof($va_item_dest)-1];
					
					if (isset($va_item['settings']['restrictToTypes']) && is_array($va_item['settings']['restrictToTypes']) && !in_array($vs_type, $va_item['settings']['restrictToTypes'])) {
						if ($va_parent && is_array($va_parent)) { array_pop($va_parent); }	// remove empty container array
						$o_log->logInfo(_t('[%1] Skipped row %2 because of type restriction', $vs_idno, $vn_row));
						continue(2);
					}
					
					if (isset($va_item['settings']['skipGroupIfEmpty']) && (bool)$va_item['settings']['skipGroupIfEmpty'] && !strlen($vm_val)) {
						if ($va_parent && is_array($va_parent)) { array_pop($va_parent); }	// remove empty container array
						$o_log->logInfo(_t('[%1] Skipped group %2 because value for %3 is empty', $vs_idno, $vn_group_id, $vs_item_terminal));
						continue(2);
					}
					if (isset($va_item['settings']['skipGroupIfValue']) && is_array($va_item['settings']['skipGroupIfValue']) && strlen($vm_val) && in_array($vm_val, $va_item['settings']['skipGroupIfValue'])) {
						if ($va_parent && is_array($va_parent)) { array_pop($va_parent); }	// remove empty container array
						$o_log->logInfo(_t('[%1] Skipped group %2 because value for %3 matches value %4', $vs_idno, $vn_group_id, $vs_item_terminal, $vm_val));
						continue(2);
					}
					if (isset($va_item['settings']['skipGroupIfNotValue']) && is_array($va_item['settings']['skipGroupIfNotValue']) && strlen($vm_val) && !in_array($vm_val, $va_item['settings']['skipGroupIfNotValue'])) {
						if ($va_parent && is_array($va_parent)) { array_pop($va_parent); }	// remove empty container array
						$o_log->logInfo(_t('[%1] Skipped group %2 because value for %3 matches is not in list of values', $vs_idno, $vn_group_id, $vs_item_terminal));
						continue(2);
					}
					if (isset($va_item['settings']['skipRowIfEmpty']) && (bool)$va_item['settings']['skipRowIfEmpty'] && !strlen($vm_val)) {
						if ($va_parent && is_array($va_parent)) { array_pop($va_parent); }	// remove empty container array
						$o_log->logInfo(_t('[%1] Skipped row %2 because value for %3 in group %4 is empty', $vs_idno, $vn_row, $vs_item_terminal, $vn_group_id));
						continue(3);
					}
					if (isset($va_item['settings']['skipRowIfValue']) && is_array($va_item['settings']['skipRowIfValue']) && strlen($vm_val) && in_array($vm_val, $va_item['settings']['skipRowIfValue'])) {
						if ($va_parent && is_array($va_parent)) { array_pop($va_parent); }	// remove empty container array
						$o_log->logInfo(_t('[%1] Skipped row %2 because value for %3 in group %4 matches value %5', $vs_idno, $vn_row, $vs_item_terminal, $vn_group_id));
						continue(3);
					}
					if (isset($va_item['settings']['skipRowIfNotValue']) && is_array($va_item['settings']['skipRowIfNotValue']) && strlen($vm_val) && !in_array($vm_val, $va_item['settings']['skipRowIfNotValue'])) {
						if ($va_parent && is_array($va_parent)) { array_pop($va_parent); }	// remove empty container array
						$o_log->logInfo(_t('[%1] Skipped row %2 because value for %3 in group %4 is not in list of values', $vs_idno, $vn_row, $vs_item_terminal, $vn_group_id, $vm_val));
						continue(3);
					}
					if (isset($va_item['settings']['default']) && strlen($va_item['settings']['default']) && !strlen($vm_val)) {
						$vm_val = $va_item['settings']['default'];
					}
					
					
					if($vn_type_id_mapping_item_id && ($vn_item_id == $vn_type_id_mapping_item_id)) { 
						if ($va_parent && is_array($va_parent)) { array_pop($va_parent); }	// remove empty container array
						continue; 
					}
					
					// Apply prefix/suffix *AFTER* setting default
					if (isset($va_item['settings']['prefix']) && strlen($va_item['settings']['prefix'])) {
						$vm_val = $va_item['settings']['prefix'].$vm_val;
					}
					if (isset($va_item['settings']['suffix']) && strlen($va_item['settings']['suffix'])) {
						$vm_val .= $va_item['settings']['suffix'];
					}
					
					if (isset($va_item['settings']['formatWithTemplate']) && strlen($va_item['settings']['formatWithTemplate'])) {
						$vm_val = caProcessTemplate($va_item['settings']['formatWithTemplate'], $va_row);
					}
					
					
					// Get mapping error policy
					$vb_item_error_policy_is_default = false;
					if (!isset($va_item['settings']['errorPolicy']) || !in_array($vs_item_error_policy = $va_item['settings']['errorPolicy'], array('ignore', 'stop'))) {
						$vs_item_error_policy = 'ignore';
						$vb_item_error_policy_is_default = true;
					}
					
					//
					if (isset($va_item['settings']['relationshipType']) && strlen($vs_rel_type = $va_item['settings']['relationshipType']) && ($vs_target_table != $vs_subject_table)) {
						$va_parent[sizeof($va_parent)-1]['_relationship_type'] = $vs_rel_type;
					}
					
					// Is it a constant value?
					if (preg_match("!^_CONSTANT_:[\d]+:(.*)!", $va_item['source'], $va_matches)) {
						$va_ptr[$vs_item_terminal] = $va_matches[1];		// Set it and go onto the next item
						continue;
					}
					
					// Perform refinery call (if required)
					if (isset($va_item['settings']['refineries']) && is_array($va_item['settings']['refineries'])) {
						foreach($va_item['settings']['refineries'] as $vs_refinery) {
							if (!$vs_refinery) { continue; }
							if ($o_refinery = RefineryManager::getRefineryInstance($vs_refinery)) {
								$va_refined_values = $o_refinery->refine($va_content_tree, $va_group, $va_item, $va_row, array('source' => $ps_source, 'subject' => $t_subject, 'locale_id' => $vn_locale_id));
							
								if ($o_refinery->returnsMultipleValues()) {
									$va_p = array_pop($va_parent);
									foreach($va_refined_values as $va_refined_value) {
										$va_refined_value['_errorPolicy'] = $vs_item_error_policy;
										$va_parent[] = array_merge($va_p, $va_refined_value);
									}
								} else {
									$va_ptr['_errorPolicy'] = $vs_item_error_policy;
									$va_ptr[$vs_item_terminal] = $va_refined_values;
								}
								
								continue(2);
							}
						}
					}
					
					$vn_max_length = (!is_array($vm_val) && isset($va_item['settings']['maxLength']) && (int)$va_item['settings']['maxLength']) ? (int)$va_item['settings']['maxLength'] : null;
					
					if (isset($va_item['settings']['delimiter']) && strlen($vs_item_delimiter = $va_item['settings']['delimiter'])) {
						$va_val_list = explode($vs_item_delimiter, $vm_val);
						array_pop($va_parent);	// remove empty slot for "regular" value
						
						// Add delimited values
						foreach($va_val_list as $vs_list_val) {
							$vs_list_val = trim(ca_data_importers::replaceValue($vs_list_val, $va_item));
							if ($vn_max_length && (mb_strlen($vs_list_val) > $vn_max_length)) {
								$vs_list_val = mb_substr($vs_list_val, 0, $vn_max_length);
							}
							$va_parent[] = array($vs_item_terminal => array($vs_item_terminal => $vs_list_val, '_errorPolicy' => $vs_item_error_policy));
						}
						
						$vn_row++;
						continue;	// Don't add "regular" value below
					}
					
					if ($vn_max_length && (mb_strlen($vm_val) > $vn_max_length)) {
						$vm_val = mb_substr($vm_val, 0, $vn_max_length);
					}
					
					switch($vs_item_terminal) {
						case 'preferred_labels':
						case 'nonpreferred_labels':
							if ($t_instance = $o_dm->getInstanceByTableName($vs_target_table, true)) {
								$va_ptr[$t_instance->getLabelDisplayField()] = '';
								$va_ptr =& $va_ptr[$t_instance->getLabelDisplayField()];
							}
							
							if (!$vb_item_error_policy_is_default || !isset($va_ptr['_errorPolicy'])) {
								if (is_array($va_ptr)) { $va_ptr['_errorPolicy'] = $vs_item_error_policy; }
							}
							$va_ptr = $vm_val;
							if ($vs_item_terminal == 'preferred_labels') { $vs_preferred_label_for_log = $vm_val; }
							
							break;
						default:
							$va_ptr[$vs_item_terminal] = $vm_val;
							if (!$vb_item_error_policy_is_default || !isset($va_ptr['_errorPolicy'])) {
								if (is_array($va_ptr)) { $va_ptr['_errorPolicy'] = $vs_item_error_policy; }
							}
							break;
					}	
				}
				
			}
			
			$vn_row++;
				
			$o_log->logDebug(_t('Finished building content tree for %1 at %2 seconds', $vs_idno, $t->getTime(4)));
			
			//
			// Process data in subject record
			//
			//print_r($va_content_tree);
			//die("END\n\n");
			//continue;
			
			if (!$t_subject->getPrimaryKey()) {
				$o_event->beginItem($vn_row, $t_subject->tableNum(), 'I') ;
				$t_subject->setMode(ACCESS_WRITE);
				$t_subject->set($vs_type_id_fld, $vs_type);
				if ($vb_idno_is_template) {
					$t_subject->setIdnoTWithTemplate($vs_idno);
				} else {
					$t_subject->set($vs_idno_fld, $vs_idno);
				}
				
				$t_subject->insert();
				if ($vs_error = DataMigrationUtils::postError($t_subject, _t("Could not insert new record"), array('dontOutputLevel' => true, 'dontPrint' => true))) {
					ca_data_importers::logImportError($vs_error, array("window" => $r_errors, 'log' => $o_log, 'skip' => true));
					if ($vs_import_error_policy == 'stop') {
						$o_log->logAlert(_t('Import stopped due to import error policy'));
						if($vb_use_ncurses) { ncurses_end(); }
						
						$o_event->endItem($t_subject->getPrimaryKey(), __CA_DATA_IMPORT_ITEM_FAILURE__, _t('Failed to import %1', $vs_idno));
						
						$o_trans->rollback();
						return false;
					}
					continue;
				}
				$o_log->logDebug(_t('Created idno %1 at %2 seconds', $vs_idno, $t->getTime(4)));
			} else {
				$o_event->beginItem($vn_row_id, $t_subject->tableNum(), 'U') ;
				// update
				$t_subject->setMode(ACCESS_WRITE);
				if ($vb_idno_is_template) {
					$t_subject->setIdnoTWithTemplate($vs_idno);
				} else {
					$t_subject->set($vs_idno_fld, $vs_idno);
				}
				
				$t_subject->update();
				if ($vs_error = DataMigrationUtils::postError($t_subject, _t("Could not update matched record"), array('dontOutputLevel' => true, 'dontPrint' => true))) {
					ca_data_importers::logImportError($vs_error, array("window" => $r_errors, 'log' => $o_log, 'skip' => true));
					if ($vs_import_error_policy == 'stop') {
						$o_log->logAlert(_t('Import stopped due to import error policy'));
						if($vb_use_ncurses) { ncurses_end(); }
						
						$o_event->endItem($t_subject->getPrimaryKey(), __CA_DATA_IMPORT_ITEM_FAILURE__, _t('Failed to import %1', $vs_idno));
						
						$o_trans->rollback();
						return false;
					}
					continue;
				}
				
				if (sizeof($va_preferred_label_mapping_ids)) {
					$t_subject->removeAllLabels(__CA_LABEL_TYPE_PREFERRED__);
					if ($vs_error = DataMigrationUtils::postError($t_subject, _t("Could not update remove preferred labels from matched record"), array('dontOutputLevel' => true, 'dontPrint' => true))) {
						ca_data_importers::logImportError($vs_error, array("window" => $r_errors, 'log' => $o_log, 'skip' => true));
						if ($vs_import_error_policy == 'stop') {
							$o_log->logAlert(_t('Import stopped due to import error policy'));
							if($vb_use_ncurses) { ncurses_end(); }
							$o_trans->rollback();
							
							$o_event->endItem($t_subject->getPrimaryKey(), __CA_DATA_IMPORT_ITEM_FAILURE__, _t('Failed to import %1', $vs_idno));
						
							return false;
						}
					}
				}
				
				$o_log->logDebug(_t('Updated idno %1 at %2 seconds', $vs_idno, $t->getTime(4)));
			}
			
		
			foreach($va_content_tree as $vs_table_name => $va_content) {
				if ($vs_table_name == $vs_subject_table) {		
					foreach($va_content as $vn_i => $va_element_data) {
						foreach($va_element_data as $vs_element => $va_element_content) {	
								if (is_array($va_element_content)) { 
									$vs_item_error_policy = $va_element_content['_errorPolicy'];
									unset($va_element_content['_errorPolicy']); 
								} else {
									$vs_item_error_policy = null;
								}
								
								$t_subject->setMode(ACCESS_WRITE);
								switch($vs_element) {
									case 'preferred_labels':									
										$t_subject->addLabel(
											$va_element_content, $vn_locale_id, null, true
										);
										if ($t_subject->numErrors() == 0) {
											$vb_output_subject_preferred_label = true;
										}
										
										if ($vs_error = DataMigrationUtils::postError($t_subject, _t("[%1] Could not add preferred label:", $vs_idno), __CA_DATA_IMPORT_ERROR__, array('dontOutputLevel' => true, 'dontPrint' => true))) {
											ca_data_importers::logImportError($vs_error, array("window" => $r_errors, 'log' => $o_log, 'skip' => true));
											$t_subject->delete(true, array('hard' => true));
											
											if ($vs_import_error_policy == 'stop') {
												$o_log->logAlert(_t('Import stopped due to import error policy %1', $vs_import_error_policy));
												if($vb_use_ncurses) { ncurses_end(); }
												
												$o_event->endItem($t_subject->getPrimaryKey(), __CA_DATA_IMPORT_ITEM_FAILURE__, _t('Failed to import %1', $vs_idno));
						
												$o_trans->rollback();
												return false;
											}
											if ($vs_item_error_policy == 'stop') {
												$o_log->logAlert(_t('Import stopped due to mapping error policy'));
												if($vb_use_ncurses) { ncurses_end(); }
												$o_trans->rollback();
												return false;
											}
											continue(5);
										}
										break;
									case 'nonpreferred_labels':									
										$t_subject->addLabel(
											$va_element_content, $vn_locale_id, null, false
										);
										
										if ($vs_error = DataMigrationUtils::postError($t_subject, _t("[%1] Could not add non-preferred label:", $vs_idno), __CA_DATA_IMPORT_ERROR__, array('dontOutputLevel' => true, 'dontPrint' => true))) {
											ca_data_importers::logImportError($vs_error, array("window" => $r_errors, 'log' => $o_log));
											if ($vs_item_error_policy == 'stop') {
												$o_log->logAlert(_t('Import stopped due to mapping error policy'));
												if($vb_use_ncurses) { ncurses_end(); }
												
												$o_event->endItem($t_subject->getPrimaryKey(), __CA_DATA_IMPORT_ITEM_FAILURE__, _t('Failed to import %1', $vs_idno));
												
												$o_trans->rollback();
												return false;
											}
										}

										break;
									default:
										if ($t_subject->hasField($vs_element)) {
											$t_subject->set($vs_element, $va_element_content[$vs_element]);
											break;
										}
									
										if (is_array($va_element_content)) { $va_element_content['locale_id'] = $vn_locale_id; }
										
										$t_subject->addAttribute($va_element_content, $vs_element);
										
										$t_subject->update();

										if ($vs_error = DataMigrationUtils::postError($t_subject, _t("[%1] Invalid %2; values were %3: ", $vs_idno, $vs_element, ca_data_importers::formatValuesForLog($va_element_content)), __CA_DATA_IMPORT_ERROR__, array('dontOutputLevel' => true, 'dontPrint' => true))) {
											ca_data_importers::logImportError($vs_error, array("window" => $r_errors, 'log' => $o_log));
											if ($vs_item_error_policy == 'stop') {
												$o_log->logAlert(_t('Import stopped due to mapping error policy'));
												if($vb_use_ncurses) { ncurses_end(); }
												
												$o_event->endItem($t_subject->getPrimaryKey(), __CA_DATA_IMPORT_ITEM_FAILURE__, _t('Failed to import %1', $vs_idno));
												
												$o_trans->rollback();
												return false;
											}
										}

										break;
								}
						}
					} 
				} else {
					// related
					
					foreach($va_content as $vn_i => $va_element_data) {
							$va_data_for_rel_table = $va_element_data;
							unset($va_data_for_rel_table['preferred_labels']);
							unset($va_data_for_rel_table['_relationship_type']);
							unset($va_data_for_rel_table['_type']);
							unset($va_data_for_rel_table['_parent_id']);
							
							switch($vs_table_name) {
								case 'ca_entities':
									if (
										(isset($va_element_data['idno']['idno']) && ($t_entity = ca_entities::find(array('idno' => $va_element_data['idno']['idno']))) && ($vn_rel_id = $t_entity->getPrimaryKey()))
										||
										($vn_rel_id = DataMigrationUtils::getEntityID($va_element_data['preferred_labels'], $va_element_data['_type'], $vn_locale_id, $va_data_for_rel_table, array('log' => $o_log, 'transaction' => $o_trans, 'importEvent' => $o_event, 'importEventSource' => $pn_row)))
									) {
										if (!$va_element_data['_relationship_type']) { break; }
										$t_subject->addRelationship($vs_table_name, $vn_rel_id, trim($va_element_data['_relationship_type']));
										
										if ($vs_error = DataMigrationUtils::postError($t_subject, _t("[%1] Could not add related entity with relationship %2", $vs_idno, trim($va_element_data['_relationship_type'])), __CA_DATA_IMPORT_ERROR__, array('dontOutputLevel' => true, 'dontPrint' => true))) {
											ca_data_importers::logImportError($vs_error, array("window" => $r_errors, 'log' => $o_log));
											if ($vs_item_error_policy == 'stop') {
												$o_log->logAlert(_t('Import stopped due to mapping error policy'));
												if($vb_use_ncurses) { ncurses_end(); }
												$o_trans->rollback();
												return false;
											}
										}
									}
									break;
								case 'ca_places':
									if (
										(isset($va_element_data['idno']['idno']) && ($t_place = ca_places::find(array('idno' => $va_element_data['idno']['idno']))) && ($vn_rel_id = $t_place->getPrimaryKey()))
										||
										($vn_rel_id = DataMigrationUtils::getPlaceID($va_element_data['preferred_labels']['name'], $va_element_data['_parent_id'], $va_element_data['_type'], $vn_locale_id, $va_data_for_rel_table, array('log' => $o_log, 'transaction' => $o_trans, 'importEvent' => $o_event, 'importEventSource' => $pn_row)))
									) {
										if (!$va_element_data['_relationship_type']) { break; }
										$t_subject->addRelationship($vs_table_name, $vn_rel_id, trim($va_element_data['_relationship_type']));
										
										if ($vs_error = DataMigrationUtils::postError($t_subject, _t("[%1] Could not add related place with relationship %2:", $vs_idno, trim($va_element_data['_relationship_type'])), __CA_DATA_IMPORT_ERROR__, array('dontOutputLevel' => true, 'dontPrint' => true))) {
											ca_data_importers::logImportError($vs_error, array("window" => $r_errors, 'log' => $o_log));
											if ($vs_item_error_policy == 'stop') {
												$o_log->logAlert(_t('Import stopped due to mapping error policy'));
												if($vb_use_ncurses) { ncurses_end(); }
												$o_trans->rollback();
												return false;
											}
										}
									}
									break;
								case 'ca_collections':
									if (
										(isset($va_element_data['idno']['idno']) && ($t_collection = ca_collections::find(array('idno' => $va_element_data['idno']['idno']))) && ($vn_rel_id = $t_collection->getPrimaryKey()))
										||
										($vn_rel_id = DataMigrationUtils::getCollectionID($va_element_data['preferred_labels']['name'], $va_element_data['_type'], $vn_locale_id, $va_data_for_rel_table, array('log' => $o_log, 'transaction' => $o_trans, 'importEvent' => $o_event, 'importEventSource' => $pn_row)))
									) {
										if (!$va_element_data['_relationship_type']) { break; }
										$t_subject->addRelationship($vs_table_name, $vn_rel_id, $va_element_data['_relationship_type']);
										
										if ($vs_error = DataMigrationUtils::postError($t_subject, _t("[%1] Could not add related collection with relationship %2:", $vs_idno, trim($va_element_data['_relationship_type'])), __CA_DATA_IMPORT_ERROR__, array('dontOutputLevel' => true, 'dontPrint' => true))) {
											ca_data_importers::logImportError($vs_error, array("window" => $r_errors, 'log' => $o_log));
											if ($vs_item_error_policy == 'stop') {
												$o_log->logAlert(_t('Import stopped due to mapping error policy'));
												if($vb_use_ncurses) { ncurses_end(); }
												$o_trans->rollback();
												return false;
											}
										}
									}
									break;
								case 'ca_occurrences':
									if (
										(isset($va_element_data['idno']['idno']) && ($t_occurrence = ca_occurrences::find(array('idno' => $va_element_data['idno']['idno']))) && ($vn_rel_id = $t_occurrence->getPrimaryKey()))
										||
										($vn_rel_id = DataMigrationUtils::getOccurrenceID($va_element_data['preferred_labels']['name'], $va_element_data['_parent_id'], $va_element_data['_type'], $vn_locale_id, $va_data_for_rel_table, array('log' => $o_log, 'transaction' => $o_trans, 'importEvent' => $o_event, 'importEventSource' => $pn_row)))
									) {
										if (!$va_element_data['_relationship_type']) { break; }
										$t_subject->addRelationship($vs_table_name, $vn_rel_id, trim($va_element_data['_relationship_type']));
										
										if ($vs_error = DataMigrationUtils::postError($t_subject, _t("[%1] Could not add related occurrence with relationship %2:", $vs_idno, trim($va_element_data['_relationship_type'])), __CA_DATA_IMPORT_ERROR__, array('dontOutputLevel' => true, 'dontPrint' => true))) {
											ca_data_importers::logImportError($vs_error, array("window" => $r_errors, 'log' => $o_log));
											if ($vs_item_error_policy == 'stop') {
												$o_log->logAlert(_t('Import stopped due to mapping error policy'));
												if($vb_use_ncurses) { ncurses_end(); }
												$o_trans->rollback();
												return false;
											}
										}
									}
									break;
								case 'ca_storage_locations':
									if (
										(isset($va_element_data['idno']['idno']) && ($t_location = ca_storage_locations::find(array('idno' => $va_element_data['idno']['idno']))) && ($vn_rel_id = $t_location->getPrimaryKey()))
										||
										($vn_rel_id = DataMigrationUtils::getStorageLocationID($va_element_data['preferred_labels']['name'], $va_element_data['_parent_id'], $va_element_data['_type'], $vn_locale_id, $va_data_for_rel_table, array('log' => $o_log, 'transaction' => $o_trans, 'importEvent' => $o_event, 'importEventSource' => $pn_row)))
									) {
										if (!$va_element_data['_relationship_type']) { break; }
										$t_subject->addRelationship($vs_table_name, $vn_rel_id, trim($va_element_data['_relationship_type']));
										
										if ($vs_error = DataMigrationUtils::postError($t_subject, _t("[%1] Could not add related storage location with relationship %2:", $vs_idno, trim($va_element_data['_relationship_type'])), __CA_DATA_IMPORT_ERROR__, array('dontOutputLevel' => true, 'dontPrint' => true))) {
											ca_data_importers::logImportError($vs_error, array("window" => $r_errors, 'log' => $o_log));
											if ($vs_item_error_policy == 'stop') {
												$o_log->logAlert(_t('Import stopped due to mapping error policy'));
												if($vb_use_ncurses) { ncurses_end(); }
												$o_trans->rollback();
												return false;
											}
										}
									}
									break;
								case 'ca_list_items':
									$va_data_for_rel_table['is_enabled'] = 1;
									if (
										(isset($va_element_data['idno']['idno']) && ($t_list_item = ca_list_items::find(array('idno' => $va_element_data['idno']['idno']))) && ($vn_rel_id = $t_list_item->getPrimaryKey()))
										||
										($vn_rel_id = DataMigrationUtils::getListItemID($va_data_for_rel_table['list_id'], $va_element_data['preferred_labels']['name_singular'], $va_element_data['_type'], $vn_locale_id, $va_data_for_rel_table, array('log' => $o_log, 'transaction' => $o_trans, 'importEvent' => $o_event, 'importEventSource' => $pn_row)))
									) {
										if (!$va_element_data['_relationship_type']) { break; }
										$t_subject->addRelationship($vs_table_name, $vn_rel_id, trim($va_element_data['_relationship_type']));
										
										if ($vs_error = DataMigrationUtils::postError($t_subject, _t("[%1] Could not add related list item with relationship %2:", $vs_idno, trim($va_element_data['_relationship_type'])), __CA_DATA_IMPORT_ERROR__, array('dontOutputLevel' => true, 'dontPrint' => true))) {
											ca_data_importers::logImportError($vs_error, array("window" => $r_errors, 'log' => $o_log));
											if ($vs_item_error_policy == 'stop') {
												$o_log->logAlert(_t('Import stopped due to mapping error policy'));
												if($vb_use_ncurses) { ncurses_end(); }
												$o_trans->rollback();
												return false;
											}
										}
									}
									break;
								// TODO: loans and movements
							 }
					}
				}
			}
			
			
			// $t_subject->update();
// 
// 			if ($vs_error = DataMigrationUtils::postError($t_subject, _t("[%1] Invalid %2; values were %3: ", $vs_idno, 'attributes', ca_data_importers::formatValuesForLog($va_element_content)), __CA_DATA_IMPORT_ERROR__, array('dontOutputLevel' => true, 'dontPrint' => true))) {
// 				ca_data_importers::logImportError($vs_error, array("window" => $r_errors, 'log' => $o_log));
// 				if ($vs_item_error_policy == 'stop') {
// 					$o_log->logAlert(_t('Import stopped due to mapping error policy'));
// 					if($vb_use_ncurses) { ncurses_end(); }
// 					
// 					$o_event->endItem($t_subject->getPrimaryKey(), __CA_DATA_IMPORT_ITEM_FAILURE__, _t('Failed to import %1', $vs_idno));
// 					
// 					$o_trans->rollback();
// 					return false;
// 				}
// 			}
// 										
			$o_log->logDebug(_t('Finished inserting content tree for %1 at %2 seconds into database', $vs_idno, $t->getTime(4)));
			
			if(!$vb_output_subject_preferred_label) {
				$t_subject->addLabel(
					array($t_subject->getLabelDisplayField() => '???'), $vn_locale_id, null, true
				);
				
				if ($vs_error = DataMigrationUtils::postError($t_subject, _t("[%1] Could not add default label", $vs_idno), __CA_DATA_IMPORT_ERROR__, array('dontOutputLevel' => true, 'dontPrint' => true))) {
					ca_data_importers::logImportError($vs_error, array("window" => $r_errors, 'log' => $o_log));
					if ($vs_import_error_policy == 'stop') {
						$o_log->logAlert(_t('Import stopped due to import error policy'));
						if($vb_use_ncurses) { ncurses_end(); }
						$o_event->endItem($t_subject->getPrimaryKey(), __CA_DATA_IMPORT_ITEM_FAILURE__, _t('Failed to import %1', $vs_idno));
						$o_trans->rollback();
						return false;
					}
				}
			}
			
			$o_log->logInfo(_t('[%1] Imported %2 as %3 ', $vs_idno, $vs_preferred_label_for_log, $vs_subject_table_name));
			$o_event->endItem($t_subject->getPrimaryKey(), __CA_DATA_IMPORT_ITEM_SUCCESS__, _t('Imported %1', $vs_idno));
			$vn_rows_processed++;
			
		}
		
		$o_log->logInfo(_t('Import of %1 completed using mapping %2: %3 imported/%4 skipped/%5 errors', $ps_source, $ps_mapping, $vn_rows_processed, ca_data_importers::$s_num_records_skipped, ca_data_importers::$s_num_import_errors));
		
		if ($vb_show_cli_progress_bar) {
			print CLIProgressBar::finish();
		}
		
		if($vb_use_ncurses) { ncurses_end(); }
		$o_trans->commit();
		return true;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getDataReader($ps_source, $ps_format=null) {
		//$o_reader_manager = new DataReaderManager();
		
		return DataReaderManager::getDataReaderForFormat($ps_format);
		
		if (!$ps_format) {
			// TODO: try to figure out format from source
		}
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function guessSourceFormat($ps_source) {
		// TODO: implement	
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function getValueFromSource($pa_item, $po_reader) {
		$vm_value = trim($po_reader->get($pa_item['source']));
		
		return ca_data_importers::replaceValue($vm_value, $pa_item);
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function replaceValue($pm_value, $pa_item) {
		if (strlen($pm_value) && is_array($pa_item['settings']['original_values'])) {
			if (($vn_index = array_search(trim(mb_strtolower($pm_value)), $pa_item['settings']['original_values'])) !== false) {
				$pm_value = $pa_item['settings']['replacement_values'][$vn_index];
			}
		}
		
		$pm_value = trim($pm_value);
		
		if (!$pm_value && isset($pa_item['settings']['default']) && strlen($pa_item['settings']['default'])) {
			$pm_value = $pa_item['settings']['default'];
		}
		return $pm_value;
	}
	# ------------------------------------------------------
	public function __destruct() {
		//ncurses_end();
	}
	# ------------------------------------------------------
}
?>