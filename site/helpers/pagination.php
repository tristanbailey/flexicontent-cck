<?php
/**
 * @version 1.5 stable $Id: controller.php 1433 2012-08-13 22:20:28Z ggppdk $
 * @package Joomla
 * @subpackage FLEXIcontent
 * @copyright (C) 2009 Emmanuel Danan - www.vistamedia.fr
 * @license GNU/GPL v2
 * 
 * FLEXIcontent is a derivative work of the excellent QuickFAQ component
 * @copyright (C) 2008 Christoph Lukes
 * see www.schlu.net for more information
 *
 * FLEXIcontent is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the 
 * GNU General Public License for more details.
 */

// Check to ensure this file is within the rest of the framework
defined('JPATH_PLATFORM') or die;

// Avoid problems with extensions that implement JPagination, instead of extending it, and have already load it
if ( !class_exists('JPagination') )
{
	jimport('cms.pagination.pagination');
}

/**
 * Pagination Class.  Provides a common interface for content pagination for the
 * Joomla! Platform.
 *
 * @package     Joomla.Platform
 * @subpackage  HTML
 * @since       11.1
 */
class FCPagination extends JPagination
{
	/**
	 * Create and return the pagination result set counter string, ie. Results 1-10 of 42
	 *
	 * @access	public
	 * @return	string	Pagination result set counter string
	 * @since	1.5
	 */
	function getResultsCounter() {
		// Initialize variables
		$app  = JFactory::getApplication();
		$view = JRequest::getCMD('view');
		$html = null;
		$fromResult = $this->limitstart + 1;

		// If the limit is reached before the end of the list
		if ($this->limitstart + $this->limit < $this->total) {
			$toResult = $this->limitstart + $this->limit;
		} else {
			$toResult = $this->total;
		}

		// If there are results found
		$fc_view_total = 0; //(int) $app->getUserState('fc_view_total_'.$view);
		if (!$fc_view_total) $fc_view_total = $this->total;
		
		$is_featured_only = $app->getUserState('use_limit_before_search_filt') == 2;
		if ($fc_view_total > 0)
		{
			// Check for maximum allowed of results
			$fc_view_limit_max = JRequest::getWord('view')!='search'  ?  0  :  (int) $app->getUserState('fc_view_limit_max_'.$view);
			$items_total_msg = $fc_view_limit_max && ($this->total >= $fc_view_limit_max) ? 'FLEXI_ITEM_S_OR_MORE' : 'FLEXI_ITEM_S';
			
			$html =
				 '<span class="flexi label item_total_label'.($is_featured_only ? ' label-success' : '').'">'.JText::_( $is_featured_only ? 'FLEXI_FEATURED' : 'FLEXI_TOTAL')."</span> "
				.'<span class="flexi item_total_value">'.$fc_view_total." " .JText::_( $items_total_msg )."</span>"
				.'<span class="flexi label item_total_label">'.JText::_( 'FLEXI_DISPLAYING')."</span> "
				.'<span class="flexi item_total_value">'.$fromResult ." - " .$toResult ." " .JText::_( 'FLEXI_ITEM_S')."</span>"
				;
		}
		
		else
		{
			$html = "\n" . JText::_('JLIB_HTML_NO_RECORDS_FOUND');
		}
		
		return $html;
	}
	
	
	/**
	 * Create and return the pagination data object.
	 *
	 * @return  object  Pagination data object.
	 *
	 * @since   11.1
	 */
	// **************************************************************************************************************************************
	// Need to call parent function to avoid problems WITH SH404SEF as SH404SEF and other SEF components have custom pagination link creation
	// **************************************************************************************************************************************
	protected function _buildDataObject()
	{
		// Workaround for JRoute not allowing url-encoded ampersand %26 in values of variables
		$data = parent::_buildDataObject();
		if (!empty($data->pages)) foreach($data->pages as $i => $page) {
			$page->link = str_replace('__amp__', '%26', $page->link);
		}
		if (!empty($data->start->link)) $data->start->link = str_replace('__amp__', '%26', $data->start->link);
		if (!empty($data->end->link))   $data->end->link   = str_replace('__amp__', '%26', $data->end->link);
		if (!empty($data->next->link))     $data->next->link     = str_replace('__amp__', '%26', $data->next->link);
		if (!empty($data->previous->link)) $data->previous->link = str_replace('__amp__', '%26', $data->previous->link);
		return $data;
	}
	
}
