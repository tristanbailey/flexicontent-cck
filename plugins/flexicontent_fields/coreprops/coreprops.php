<?php
defined("_JEXEC") or die("Restricted Access");

class plgFlexicontent_fieldsCoreprops extends JPlugin
{
	static $field_types = array('coreprops');
	
	// ***********
	// CONSTRUCTOR
	// ***********
	
	function __construct( &$subject, $params )
	{
		parent::__construct( $subject, $params );
		JPlugin::loadLanguage('plg_flexicontent_fields_coreprops', JPATH_ADMINISTRATOR);
	}
	
	
	
	// *******************************************
	// DISPLAY methods, item form & frontend views
	// *******************************************
	
	// Method to create field's HTML display for item form
	function onDisplayField(&$field, &$item)
	{
		if ( !in_array($field->field_type, self::$field_types) ) return;
		
		$props_type = $field->parameters->get( 'props_type' ) ;
		
		$field->html = '';
	}
	
	
	// Method to create field's HTML display for frontend views
	public function onDisplayFieldValue(&$field, $item, $values=null, $prop='display')
	{
		if ( !in_array($field->field_type, self::$field_types) ) return;
		
		static $all_langs = null;
		static $cat_links = array();
		static $acclvl_names = null;
		
		$remove_space = $field->parameters->get( 'remove_space', 0 ) ;
		$pretext		= FlexicontentFields::replaceFieldValue( $field, $item, $field->parameters->get( 'pretext', '' ), 'pretext' );
		$posttext		= FlexicontentFields::replaceFieldValue( $field, $item, $field->parameters->get( 'posttext', '' ), 'posttext' );
		$separatorf	= $field->parameters->get( 'separatorf', 1 ) ;
		$opentag		= FlexicontentFields::replaceFieldValue( $field, $item, $field->parameters->get( 'opentag', '' ), 'opentag' );
		$closetag		= FlexicontentFields::replaceFieldValue( $field, $item, $field->parameters->get( 'closetag', '' ), 'closetag' );
		
		// Microdata (classify the field values for search engines)
		$itemprop    = $field->parameters->get('microdata_itemprop');
		
		if($pretext)  { $pretext  = $remove_space ? $pretext : $pretext . ' '; }
		if($posttext) { $posttext = $remove_space ? $posttext : ' ' . $posttext; }
		
		switch($separatorf)
		{
			case 0:
			$separatorf = '&nbsp;';
			break;

			case 1:
			$separatorf = '<br class="fcclear" />';
			break;

			case 2:
			$separatorf = '&nbsp;|&nbsp;';
			break;

			case 3:
			$separatorf = ',&nbsp;';
			break;

			case 4:
			$separatorf = $closetag . $opentag;
			break;

			case 5:
			$separatorf = '';
			break;

			default:
			$separatorf = '&nbsp;';
			break;
		}
		
		$props_type = $field->parameters->get('props_type');
		switch($props_type)
		{
			case 'language':
				if ($all_langs===null) {
					$all_langs= FLEXIUtilities::getLanguages($hash='code');
				}
				$lang_data = $all_langs->{$item->language};
				
				$field->{$prop} = @$lang_data->title_native ? $lang_data->title_native : $lang_data->name;
				break;
			
			case 'alias':
				$field->{$prop} = $item->{$props_type};
				break;
			
			case 'category':
				$link_maincat = $field->parameters->get('link_maincat', 1);
				if ($link_maincat)
				{
					$maincatid = isset($item->maincatid) ? $item->maincatid : $item->catid;   // maincatid is used by item view
					if ( !isset($cat_links[$maincatid]) )
					{
						$maincat_slug = $item->maincatid  ?  $item->maincatid.':'.$item->maincat_alias : $item->catid;
						$cat_links[$maincatid] = JRoute::_(FlexicontentHelperRoute::getCategoryRoute($maincat_slug));
					}
				}
				
				$maincat_title =  !empty($item->maincat_title) ? $item->maincat_title : 'catid: '.$item->catid;
				$field->{$prop} = $link_maincat ?
					'<a class="fc_coreprop fc_maincat link_' .$field->name. '" href="' . $cat_links[$maincatid] . '">' . $maincat_title . '</a>' :
					$maincat_title;
				break;
			
			case 'access':
				if ($acclvl_names===null) {
					$acclvl_names = flexicontent_db::getAccessNames();
				}
				$field->{$prop} = isset($acclvl_names[$item->access])  ?  $acclvl_names[$item->access]  :  'unknown access level id: '.$item->access;
				break;
			
			default:
				$field->{$prop} = $props_type;
				break;
		}
			
		if (strlen($field->{$prop})) {
			$field->{$prop} = $opentag.$pretext. $field->{$prop} .$posttext.$closetag;
		}
	}
	
	// *********************************
	// CATEGORY/SEARCH FILTERING METHODS
	// *********************************
	
	// Method to display a search filter for the advanced search view
	function onAdvSearchDisplayFilter(&$filter, $value='', $formName='searchForm')
	{
		if ( !in_array($filter->field_type, self::$field_types) ) return;
		
		$props_type = $filter->parameters->get('props_type');
		//if ($props_type == 'language') {
		//	$filter->parameters->set( 'display_filter_as_s', 1 );  // Only supports a basic filter of single text search input
		//}
		
		//$indexed_elements = in_array($props_type, array('language'));
		
		$this->onDisplayFilter($filter, $value, $formName, $isSearchView=1);
		//if ($props_type =='...') {
		//	$this->onDisplayFilter($filter, $value, $formName, $isSearchView=1);
		//} else {
		//	FlexicontentFields::createFilter($filter, $value, $formName, $indexed_elements);
		//}
	}
	
	
	

	// Method to display a category filter for the category view
	function onDisplayFilter(&$filter, $value='', $formName='adminForm', $isSearchView=0)
	{
		if ( !in_array($filter->field_type, self::$field_types) ) return;
		
		$db = JFactory::getDBO();
		$formfieldname = 'filter_'.$filter->id;
		
		$_s = $isSearchView ? '_s' : '';
		$display_filter_as = $filter->parameters->get( 'display_filter_as'.$_s, 0 );  // Filter Type of Display
		$filter_as_range = in_array($display_filter_as, array(2,3,8)) ;
		
		// Create first prompt option of drop-down select
		$label_filter = $filter->parameters->get( 'display_label_filter'.$_s, 2 ) ;
		$first_option_txt = $label_filter==2 ? $filter->label : JText::_('FLEXI_ALL');
		
		// Prepend Field's Label to filter HTML
		//$filter->html = $label_filter==1 ? $filter->label.': ' : '';
		$filter->html = '';
		
		$props_type = $filter->parameters->get('props_type');
		switch ($props_type)
		{
			case 'language':
				// WARNING: we can not use column alias in from, join, where, group by, can use in having (some DB e.g. mysql) and in order-by
				// partial SQL clauses
				$filter->filter_valuesselect = ' i.language AS value, CASE WHEN CHAR_LENGTH(lg.title_native) THEN lg.title_native ELSE lg.title END as text';
				$filter->filter_valuesfrom   = ' FROM #__content AS i ';
				$filter->filter_valuesjoin   =
					' JOIN #__languages AS lg ON i.language = lg.lang_code'.
					' JOIN #__flexicontent_fields_item_relations as fi ON i.id=fi.item_id';
				$filter->filter_valueswhere  = ' AND lg.published <> 0';
				// full SQL clauses
				$filter->filter_groupby = ' GROUP BY i.language ';
				$filter->filter_having  = null;   // this indicates to use default, space is use empty
				$filter->filter_orderby = null;   // use default, no ordering done to improve speed, it will be done inside PHP code
				
				FlexicontentFields::createFilter($filter, $value, $formName);
			break;
			
			default:
				$filter->html	.= 'CORE property field of type: '.$props_type.' can not be used as search filter';
			break;
		}
		
		// a. If field filter has defined a custom SQL query to create filter (drop-down select) options, execute it and then create the options
		if ( !empty($query) )
		{
			$db->setQuery($query);
			$lists = $db->loadObjectList();
			
			// Add the options
			$options = array();
			$_inner_lb = $label_filter==2 ? $filter->label : JText::_('FLEXI_CLICK_TO_LIST');
			if ($display_filter_as == 6)
			{
				if ($label_filter==2)
				{
					$options[] = JHTML::_('select.option', '', $_inner_lb, 'value', 'text', $_disabled = true);
				}
			}
			else
				$options[] = JHTML::_('select.option', '', '- '.$first_option_txt.' -');
			foreach ($lists as $list) $options[] = JHTML::_('select.option', $list->value, $list->text . ($count_column ? ' ('.$list->found.')' : '') );
		}
		
		// b. If field filter has defined drop-down select options the create the drop-down select form field
		if ( !empty($options) )
		{
			// Make use of select2 lib
			flexicontent_html::loadFramework('select2');
			$classes  = " use_select2_lib". @ $extra_classes;
			$extra_param = '';
			
			// MULTI-select: special label and prompts
			if ($display_filter_as == 6)
			{
				$classes .= ' fc_prompt_internal';
				
				// Add field's LABEL internally or click to select PROMPT (via js)
				$extra_param  = ' data-placeholder="'.flexicontent_html::escapeJsText($_inner_lb,'s').'"';
				
				// Add type to filter PROMPT (via js)
				$extra_param .= ' data-fc_prompt_text="'.flexicontent_html::escapeJsText(JText::_('FLEXI_TYPE_TO_FILTER'),'s').'"';
			}
			
			// Create HTML tag attributes
			$attribs_str  = ' class="fc_field_filter'.$classes.'" '.$extra_param;
			$attribs_str .= $display_filter_as==6 ? ' multiple="multiple" size="20" ' : '';
			//$attribs_str .= ($display_filter_as==0 || $display_filter_as==6) ? ' onchange="document.getElementById(\''.$formName.'\').submit();"' : '';
			
			// Filter name and id
			$filter_ffname = 'filter_'.$filter->id;
			$filter_ffid   = $formName.'_'.$filter->id.'_val';
			
			if ( !is_array($value) )  $value = array($value);
			if ( count($value==1) && !strlen( reset($value) ) )  $value = array();
			
			// Create filter
			if ($display_filter_as != 6)
				$filter->html	.= JHTML::_('select.genericlist', $options, $filter_ffname.'[]', $attribs_str, 'value', 'text', $value, $filter_ffid);
			else
				$filter->html	.=
					($label_filter==2 && count($value) ? ' <span class="badge fc_mobile_label" style="display:none;">'.JText::_($filter->label).'</span> ' : '').
					JHTML::_('select.genericlist', $options, $filter_ffname.'[]', $attribs_str, 'value', 'text', ($label_filter==2 && !count($value) ? array('') : $value), $filter_ffid);
		}
		
		// Special CASE for some filters, do some replacements
		//if ( $props_type == 'alias') $filter->html = str_replace('_', ' ', $filter->html);
	}
	
	
 	// Method to get the active filter result (an array of item ids matching field filter, or subquery returning item ids)
	// This is for content lists e.g. category view, and not for search view
	function getFiltered(&$filter, $value, $return_sql=true)
	{
		if ( !in_array($filter->field_type, self::$field_types) ) return;
		
		$props_type = $filter->parameters->get('props_type');
		switch ($props_type)
		{
			case 'language':
				$filter->filter_colname    = 'language';
				$filter->filter_valuesjoin = ' ';   // ... a space, (indicates not needed)
				$filter->filter_valueformat = ' ';
				
				$query = FlexicontentFields::getFiltered($filter, $value, $return_sql);
				break;
			default:
				return $return_sql ? ' AND i.id IN (0) ' : array(0);
				break;
		}
		if ( !$return_sql ) {
			//echo "<br>plgFlexicontent_fieldsCoreprops::getFiltered() -- [".$filter->name."]  doing: <br>". $query."<br><br>\n";
			$db = JFactory::getDBO();
			$db->setQuery($query);
			$filtered = $db->loadColumn();
			if ($db->getErrorNum())  JFactory::getApplication()->enqueueMessage(__FUNCTION__.'(): SQL QUERY ERROR:<br/>'.nl2br($db->getErrorMsg()),'error');
			return $filtered;
		} else {
			return $query;
		}
	}
	
	
	// Method to get the active filter result (an array of item ids matching field filter, or subquery returning item ids)
	// This is for search view
	function getFilteredSearch(&$filter, $value, $return_sql=true)
	{
		if ( !in_array($filter->field_type, self::$field_types) ) return;
		
		$props_type = $filter->parameters->get('props_type');
		switch ($props_type)
		{
			case 'language':
				$query = " SELECT DISTINCT c.id "
					." FROM #__content AS c "
					." WHERE c.language='".implode('',$value)."' ";
				break;
			default:
				return $return_sql ? ' AND i.id IN (0) ' : array(0);
				break;
		}
		if ( !$return_sql ) {
			//echo "<br>plgFlexicontent_fieldsCoreprops::getFiltered() -- [".$filter->name."]  doing: <br>". $query."<br><br>\n";
			$db = JFactory::getDBO();
			$db->setQuery($query);
			$filtered = $db->loadColumn();
			if ($db->getErrorNum())  JFactory::getApplication()->enqueueMessage(__FUNCTION__.'(): SQL QUERY ERROR:<br/>'.nl2br($db->getErrorMsg()),'error');
			return $filtered;
		} else {
			return ' AND i.id IN ('. $query .')';
		}
	}
}
?>