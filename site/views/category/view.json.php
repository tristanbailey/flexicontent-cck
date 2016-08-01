<?php
/**
 * @version 1.5 stable $Id: view.html.php 1959 2014-09-18 00:15:15Z ggppdk $
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

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport('legacy.view.legacy');
jimport('joomla.filesystem.file');

/**
 * HTML View class for the Category View
 *
 * @package Joomla
 * @subpackage FLEXIcontent
 * @since 1.0
 */
class FlexicontentViewCategory extends JViewLegacy
{
	/**
	 * Creates the page's display
	 *
	 * @since 1.0
	 */
	function display( $tpl = null )
	{
		// Initialize framework variables
		$user     = JFactory::getUser();
		$aid      = JAccess::getAuthorisedViewLevels($user->id);
		
		// Get model
		$model  = $this->getModel();
		
		
		// Get the category, loading category data and doing parameters merging
		$category = $this->get('Category');
		
		// Get category parameters as VIEW's parameters (category parameters are merged parameters in order: layout(template-manager)/component/ancestors-cats/category/author/menu)
		$params   = $category->parameters;
		
		
		
		// ***********************
		// Get data from the model
		// ***********************
		
		$items = $this->get('Data');
		
		// Get field values
		$_vars = null;
		FlexicontentFields::getItemFields($items, $_vars, $_view='category', $aid);
		
		// Zero unneeded search index text
		foreach ($items as $item) $item->search_index = '';
		
		// Nullify creator / modifier emails for JSON view
		foreach ($items as $item)
		{
			$item->cmail = null;
			$item->mmail = null;
		}
		
		// Use &test=1 to test / preview item data of first item
		if (JRequest::getCmd('test', 0))
		{
			$item = reset($items); echo "<pre>"; print_r($item); exit;
		}
		
		// Output items in JSON FORMAT
		echo @json_encode( $items );
	}
}
?>
